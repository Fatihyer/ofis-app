<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Post, Client, Acente, Message, Vehicule, Firma, Hareket, Service, Servicetype, Status, Sirket, User, Transfer, Kur, Kdv, Payment, Option, Dovizal};
use Auth;
use Session;
use File;
use App\Exports\ExpensesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Helpers\LogActivity;
use App\Helpers\HareketHelper;
use App\Services\TransferEnRouteCalculator;

class PostController extends Controller
{
    protected $transferids;
    protected $dispoids;

    public function __construct() {
        $this->middleware('auth');
        $this->middleware('permission:posts.view')->only(['index', 'show', 'balancefile', 'mismatchTransfers', 'excelhareket', 'userFileStats']);
        $this->middleware('permission:posts.create')->only(['create', 'store', 'createfromtransfert', 'storefromtransfert']);
        $this->middleware('permission:posts.update')->only(['syncDatesFromTransfers', 'updateAllTransferVehicules']);
        $this->middleware('permission:posts.update')->only(['edit', 'update', 'guncel', 'message', 'messageedit', 'updateComment', 'updateStatus']);
        $this->middleware('permission:posts.delete')->only(['destroy']);
        $this->middleware('permission:posts.uninvoiced')->only(['uninvoiced', 'bulkExcludeFromUninvoiced', 'excludeFromUninvoiced', 'restoreToUninvoiced']);

        $this->initializeOption('transferid', 'transferids');
        $this->initializeOption('dispoid', 'dispoids');
    }

    private function initializeOption($name, $property)
{
    // host sadece HTTP'de var; CLI'da boş
    $domain = request()->getHost() ?: ($_SERVER['HTTP_HOST'] ?? null);
    if (!$domain) {
        $this->$property = []; // güvenli default
        return;
    }

    $option = Option::where('name', $name)->first();
    if ($option) {
        $this->$property = array_map('intval', explode(',', $option->value));
        return;
    }

    // Controller init aşamasında redirect riskli; güvenli şekilde boş set et
    $this->$property = [];

    // İstersen logla:
    \Log::warning("Missing option: {$name} for domain {$domain}");
}


    public function userFileStats(Request $request)
    {
        $tz = 'Europe/Paris';
        $startDate = $request->get('start_date') ?: now($tz)->startOfMonth()->toDateString();
        $endDate = $request->get('end_date') ?: now($tz)->toDateString();
        $start = \Carbon\Carbon::parse($startDate, $tz)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate, $tz)->endOfDay();

        $congeIds = collect(explode(',', (string) Option::where('name', 'conge')->value('value')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $posts = Post::query()
            ->leftJoin('users', 'users.id', '=', 'posts.user_id')
            ->leftJoin('transfers', function ($join) use ($congeIds) {
                $join->on('transfers.post_id', '=', 'posts.id')
                    ->whereNull('transfers.deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('transfers.conge')->orWhere('transfers.conge', 0);
                    });

                if (!empty($congeIds)) {
                    $join->whereNotIn('transfers.servicetype_id', $congeIds);
                }
            })
            ->leftJoin('hotels', function ($join) use ($congeIds) {
                $join->on('hotels.post_id', '=', 'posts.id')
                    ->whereNull('hotels.deleted_at');

                if (!empty($congeIds)) {
                    $join->whereNotIn('hotels.servicetype_id', $congeIds);
                }
            })
            ->leftJoin('others', 'others.post_id', '=', 'posts.id')
            ->leftJoin('stocks', 'stocks.post_id', '=', 'posts.id')
            ->whereNull('posts.deleted_at')
            ->whereBetween('posts.created_at', [$start, $end])
            ->groupBy('posts.id', 'posts.user_id', 'users.name')
            ->select([
                'posts.id',
                'posts.user_id',
                DB::raw('COALESCE(users.name, "Utilisateur non défini") as user_name'),
                DB::raw('COUNT(DISTINCT transfers.id) as transfer_count'),
                DB::raw('COUNT(DISTINCT hotels.id) as hotel_count'),
                DB::raw('COUNT(DISTINCT others.id) as other_count'),
                DB::raw('COUNT(DISTINCT stocks.id) as stock_count'),
            ])
            ->get()
            ->map(function ($row) {
                $row->service_count = (int) $row->transfer_count + (int) $row->hotel_count + (int) $row->other_count + (int) $row->stock_count;
                return $row;
            })
            ->filter(fn ($row) => $row->service_count > 0);

        $stats = $posts
            ->groupBy(fn ($row) => $row->user_id ?: 'none')
            ->map(function ($rows) {
                $first = $rows->first();
                return (object) [
                    'user_id' => $first->user_id,
                    'user_name' => $first->user_name,
                    'file_count' => $rows->count(),
                    'service_count' => $rows->sum('service_count'),
                    'transfer_count' => $rows->sum('transfer_count'),
                    'hotel_count' => $rows->sum('hotel_count'),
                    'other_count' => $rows->sum('other_count'),
                    'stock_count' => $rows->sum('stock_count'),
                ];
            })
            ->sortByDesc('file_count')
            ->values();

        $totals = [
            'file_count' => $stats->sum('file_count'),
            'service_count' => $stats->sum('service_count'),
            'transfer_count' => $stats->sum('transfer_count'),
            'hotel_count' => $stats->sum('hotel_count'),
            'other_count' => $stats->sum('other_count'),
            'stock_count' => $stats->sum('stock_count'),
        ];

        return view('posts.user_file_stats', compact('stats', 'totals', 'startDate', 'endDate', 'congeIds'));
    }

    private function ensureAccountingAccessForUninvoiced(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['Superadmin', 'Comptabilité', 'Compta']), 403, 'Accès réservé aux super administrateurs et à la comptabilité.');
    }

    private function uninvoicedExcludedPostIds(): array
    {
        return collect(explode(',', (string) Option::where('name', 'uninvoicedExcludedPostIds')->value('value')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function saveUninvoicedExcludedPostIds(array $ids): void
    {
        $value = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->implode(',');

        Option::updateOrCreate(['name' => 'uninvoicedExcludedPostIds'], ['value' => $value]);
    }

    public function uninvoiced(Request $request)
    {
        $this->ensureAccountingAccessForUninvoiced();
        $tz = 'Europe/Paris';
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date') ?: now($tz)->toDateString();
        $perPage = (int) $request->get('per_page', 100);
        $perPage = in_array($perPage, [50, 100, 200], true) ? $perPage : 100;
        $showExcluded = $request->boolean('show_excluded');
        $excludedPostIds = $this->uninvoicedExcludedPostIds();
        $sort = $request->get('sort', 'agence');
        $sort = in_array($sort, ['agence', 'date', 'dossier'], true) ? $sort : 'agence';

        $start = $startDate ? \Carbon\Carbon::parse($startDate, $tz)->startOfDay() : null;
        $end = \Carbon\Carbon::parse($endDate, $tz)->endOfDay();

        $congeIds = collect(explode(',', (string) Option::where('name', 'conge')->value('value')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $query = Post::query()
            ->leftJoin('acentes', 'acentes.id', '=', 'posts.acente_id')
            ->leftJoin('users', 'users.id', '=', 'posts.user_id')
            ->leftJoin('invoices', function ($join) {
                $join->on('invoices.post_id', '=', 'posts.id')
                    ->whereNull('invoices.deleted_at')
                    ->whereNotNull('invoices.resmi')
                    ->whereRaw("TRIM(invoices.resmi) != ''");
            })
            ->leftJoinSub($this->groupedInvoicePostIdsQuery(), 'grouped_invoice_posts', function ($join) {
                $join->on('grouped_invoice_posts.post_id', '=', 'posts.id');
            })
            ->leftJoin('transfers', function ($join) use ($congeIds) {
                $join->on('transfers.post_id', '=', 'posts.id')
                    ->whereNull('transfers.deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('transfers.conge')->orWhere('transfers.conge', 0);
                    });

                if (!empty($congeIds)) {
                    $join->whereNotIn('transfers.servicetype_id', $congeIds);
                }
            })
            ->leftJoin('hotels', function ($join) use ($congeIds) {
                $join->on('hotels.post_id', '=', 'posts.id')
                    ->whereNull('hotels.deleted_at');

                if (!empty($congeIds)) {
                    $join->whereNotIn('hotels.servicetype_id', $congeIds);
                }
            })
            ->leftJoin('others', 'others.post_id', '=', 'posts.id')
            ->leftJoin('stocks', 'stocks.post_id', '=', 'posts.id')
            ->whereNull('posts.deleted_at')
            ->whereNull('invoices.id')
            ->whereNull('grouped_invoice_posts.post_id')
            ->when(!$showExcluded, function ($q) use ($excludedPostIds) {
                $q->where(function ($qq) {
                    $qq->whereNull('posts.billing_status')->orWhere('posts.billing_status', '!=', 'do_not_invoice');
                });
                if (!empty($excludedPostIds)) {
                    $q->whereNotIn('posts.id', $excludedPostIds);
                }
            })
            ->when($showExcluded, function ($q) use ($excludedPostIds) {
                $q->where(function ($qq) use ($excludedPostIds) {
                    $qq->where('posts.billing_status', 'do_not_invoice');
                    if (!empty($excludedPostIds)) {
                        $qq->orWhereIn('posts.id', $excludedPostIds);
                    }
                });
            })
            ->where('posts.start_date', '<=', $end)
            ->when($start, fn ($q) => $q->where('posts.start_date', '>=', $start))
            ->groupBy('posts.id', 'posts.title', 'posts.start_date', 'posts.end_date', 'posts.pax', 'posts.acente_id', 'posts.billing_status', 'posts.payment_destination', 'posts.payment_status', 'acentes.name', 'users.name')
            ->select([
                'posts.id',
                'posts.title',
                'posts.start_date',
                'posts.end_date',
                'posts.pax',
                'posts.acente_id',
                'posts.billing_status',
                'posts.payment_destination',
                'posts.payment_status',
                DB::raw('COALESCE(acentes.name, "Agence non définie") as acente_name'),
                DB::raw('COALESCE(users.name, "Utilisateur non défini") as user_name'),
                DB::raw('COUNT(DISTINCT transfers.id) as transfer_count'),
                DB::raw('COUNT(DISTINCT hotels.id) as hotel_count'),
                DB::raw('COUNT(DISTINCT others.id) as other_count'),
                DB::raw('COUNT(DISTINCT stocks.id) as stock_count'),
                DB::raw('MIN(COALESCE(transfers.start_date, hotels.from, stocks.tarih, posts.start_date)) as first_service_date'),
                DB::raw('MAX(COALESCE(transfers.start_date, hotels.to, stocks.tarih, posts.end_date)) as last_service_date'),
            ])
            ->havingRaw('(COUNT(DISTINCT transfers.id) + COUNT(DISTINCT hotels.id) + COUNT(DISTINCT others.id) + COUNT(DISTINCT stocks.id)) > 0');

        if ($sort === 'agence') {
            $query->orderBy('acentes.name')->orderBy('posts.start_date', 'desc')->orderBy('posts.id', 'desc');
        } elseif ($sort === 'date') {
            $query->orderByRaw('first_service_date asc')->orderBy('posts.start_date', 'asc')->orderBy('posts.id', 'asc');
        } elseif ($sort === 'dossier') {
            $query->orderBy('posts.id', 'desc');
        } else {
            $query->orderBy('posts.start_date', 'desc')->orderBy('posts.id', 'desc');
        }

        $summaryRows = (clone $query)->get();
        $totals = [
            'file_count' => $summaryRows->count(),
            'service_count' => $summaryRows->sum(fn ($row) => (int) $row->transfer_count + (int) $row->hotel_count + (int) $row->other_count + (int) $row->stock_count),
            'transfer_count' => $summaryRows->sum('transfer_count'),
            'hotel_count' => $summaryRows->sum('hotel_count'),
            'other_count' => $summaryRows->sum('other_count'),
            'stock_count' => $summaryRows->sum('stock_count'),
        ];

        $posts = $query->paginate($perPage)->appends($request->query());

        return view('posts.uninvoiced', compact('posts', 'totals', 'startDate', 'endDate', 'perPage', 'congeIds', 'excludedPostIds', 'showExcluded', 'sort'));
    }

    private function groupedInvoicePostIdsQuery()
    {
        return DB::table('invoices as grouped_invoices')
            ->join('groupinvoice_invoice', 'groupinvoice_invoice.invoice_id', '=', 'grouped_invoices.id')
            ->join('groupinvoices', 'groupinvoices.id', '=', 'groupinvoice_invoice.groupinvoice_id')
            ->whereNull('grouped_invoices.deleted_at')
            ->whereNotNull('grouped_invoices.post_id')
            ->select('grouped_invoices.post_id')
            ->distinct();
    }

    public function excludeFromUninvoiced(Post $post)
    {
        $this->ensureAccountingAccessForUninvoiced();
        $ids = $this->uninvoicedExcludedPostIds();
        $ids[] = $post->id;
        $this->saveUninvoicedExcludedPostIds($ids);
        $post->update(['billing_status' => 'do_not_invoice']);
        LogActivity::addToLog('Excluded from uninvoiced list.', $post->id, 'dossier non facturable');

        return redirect()->back()->with('flash_message', 'Dossier #' . $post->id . ' marqué comme non facturable.');
    }

    public function restoreToUninvoiced(Post $post)
    {
        $this->ensureAccountingAccessForUninvoiced();
        $ids = array_values(array_diff($this->uninvoicedExcludedPostIds(), [$post->id]));
        $this->saveUninvoicedExcludedPostIds($ids);
        $post->update(['billing_status' => 'to_invoice']);
        LogActivity::addToLog('Restored to uninvoiced list.', $post->id, 'dossier à facturer');

        return redirect()->route('posts.uninvoiced', ['show_excluded' => 1])->with('flash_message', 'Dossier #' . $post->id . ' réactivé dans la liste à facturer.');
    }


    public function bulkExcludeFromUninvoiced(Request $request)
    {
        $this->ensureAccountingAccessForUninvoiced();

        $data = $request->validate([
            'post_ids' => 'required|array|min:1',
            'post_ids.*' => 'integer|exists:posts,id',
        ]);

        $selectedIds = collect($data['post_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $ids = array_values(array_unique(array_merge($this->uninvoicedExcludedPostIds(), $selectedIds)));
        $this->saveUninvoicedExcludedPostIds($ids);
        Post::whereIn('id', $selectedIds)->update(['billing_status' => 'do_not_invoice']);

        foreach ($selectedIds as $postId) {
            LogActivity::addToLog('Bulk excluded from uninvoiced list.', $postId, 'dossier non facturable');
        }

        return redirect()->back()->with('flash_message', count($selectedIds) . ' dossier(s) marqué(s) comme non facturable(s).');
    }

    public function index(Request $request)
    {
        $start_date = $request->input('start_date', date('Y-m-d 00:00:00'));
        $end_date = $request->input('end_date', date('Y-m-d', strtotime("+1 year")));
        $per_page = (int) $request->input('per_page', 50);
        $per_page = in_array($per_page, [25, 50, 100, 200], true) ? $per_page : 50;

        $sortMap = [
            'post_id' => 'posts.id',
            'id' => 'posts.id',
            'acente_id' => 'posts.acente_id',
            'start_date' => 'posts.start_date',
            'end_date' => 'posts.end_date',
            'status_id' => 'posts.status_id',
            'pax' => 'posts.pax',
            'updated_at' => 'posts.updated_at',
        ];
        $sort = $request->input('sort', 'post_id');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortColumn = $sortMap[$sort] ?? $sortMap['post_id'];

        $posts = Post::with(['acente.firmas', 'status.color', 'user'])
            ->withCount(['transfer', 'hotels', 'others', 'stock'])
            ->whereBetween('start_date', [$start_date, $end_date])
            ->orderBy($sortColumn, $direction)
            ->paginate($per_page);

        return view('posts.index', compact('posts', 'per_page', 'sort', 'direction', 'start_date', 'end_date'));
    }

    public function create()
    {
        $acente = Acente::orderBy('name')->pluck('name', 'id');
        $status = Status::pluck('name', 'id');
        return view('posts.create', compact('acente', 'status'));
    }

    public function createfromtransfert()
    {
        $acente = Acente::orderBy('name')->pluck('name', 'id');
        $status = Status::pluck('name', 'id');
        $vehicules = Vehicule::whereNull('sales')->orderBy('name')->pluck('name', 'id');
        $drivers = Acente::orderBy('name')->pluck('name', 'id');
        $firmas = Firma::orderBy('name')->pluck('name', 'id');
        $acentes = $acente;
        $servicetypes['transfer'] = Servicetype::where('hizmet', 0)->orderBy('name')->pluck('name', 'id')->toArray();
        $defaultDepotAddress = app(TransferEnRouteCalculator::class)->defaultDepotAddress();

        return view('posts.createfromtransfer', compact('acente', 'status', 'servicetypes', 'vehicules', 'drivers', 'firmas', 'acentes', 'defaultDepotAddress'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:100',
            'start_date' => 'required|date|after:2016-01-01',
            'end_date' => 'required|date|after_or_equal:start_date',
            'acente_id' => 'required',
        ]);

        $post = Post::create($request->all());
        LogActivity::addToLog('Create.', $post->id,'yeni dosya');

        return redirect()->route('posts.show', $post->id)
            ->with('flash_message', 'File, ' . $post->title . ' created');
    }

    public function storefromtransfert(Request $request)
    {
        $request->validate([
            'title' => 'required|max:100',
            'acente_id' => 'required|integer|exists:acentes,id',
            'body' => 'nullable|string|max:1000',
            'pax' => 'nullable|integer|min:0',
            'child' => 'nullable|integer|min:0',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'transfers' => 'required|array|min:1',
            'transfers.*.servicetype_id' => 'required|integer|exists:servicetypes,id',
            'transfers.*.date' => 'required|date',
            'transfers.*.start_time' => 'required|date_format:H:i',
            'transfers.*.end_time' => 'required|date_format:H:i',
            'transfers.*.from' => 'required|string|max:255',
            'transfers.*.target' => 'required|string|max:255',
            'transfers.*.pax' => 'nullable|integer|min:0',
            'transfers.*.comments' => 'nullable|string|max:500',
            'transfers.*.vehicule_id' => 'required|integer|exists:vehicules,id',
            'transfers.*.driver_id' => 'required|integer|exists:acentes,id',
            'transfers.*.km' => 'nullable|integer|min:0',
            'transfers.*.mission' => 'nullable|boolean',
            'transfers.*.accueil' => 'nullable|boolean',
            'transfers.*.vehicle_provider_acente_id' => 'nullable|integer|exists:acentes,id',
            'transfers.*.external_vehicle_note' => 'nullable|string|max:255',
            'transfers.*.external_vehicle_price' => 'nullable|numeric|min:0',
        ]);

        $transferRows = collect($request->input('transfers', []))->values();

        $post = DB::transaction(function () use ($request, $transferRows) {
            $dateTimes = $transferRows->map(function ($row) {
                return [
                    'start' => $row['date'] . ' ' . $row['start_time'],
                    'end' => $row['date'] . ' ' . $row['end_time'],
                ];
            });

            $post = Post::create([
                'title' => $request->input('title'),
                'acente_id' => $request->input('acente_id'),
                'body' => $request->input('body'),
                'pax' => $request->input('pax') ?: $transferRows->sum(fn ($row) => (int) ($row['pax'] ?? 0)),
                'child' => $request->input('child', 0),
                'start_date' => $dateTimes->min('start'),
                'end_date' => $dateTimes->max('end'),
                'status_id' => $request->input('status_id', 3),
                'user_id' => auth()->id(),
            ]);

            foreach ($transferRows as $row) {
                $dateStart = $row['date'] . ' ' . $row['start_time'];
                $dateEnd = $row['date'] . ' ' . $row['end_time'];

                if (strtotime($dateEnd) < strtotime($dateStart)) {
                    $dateEnd = \Carbon\Carbon::parse($dateEnd)->addDay()->toDateTimeString();
                }

                $trajets = [
                    [
                        'type' => 'adress',
                        'from' => $row['from'],
                        'google_address' => $row['from'],
                        'datetime' => $dateStart,
                        'order' => 1,
                    ],
                    [
                        'type' => 'adress',
                        'from' => $row['target'],
                        'google_address' => $row['target'],
                        'datetime' => $dateEnd,
                        'order' => 2,
                    ],
                ];

                $plannedEnRoute = app(TransferEnRouteCalculator::class)->calculate(
                    $trajets,
                    (int) $row['servicetype_id'],
                    (int) $row['vehicule_id'],
                    (int) $row['driver_id']
                );

                $transfer = Transfer::create([
                    'start_date' => $dateStart,
                    'end_date' => $dateEnd,
                    'servicetype_id' => $row['servicetype_id'],
                    'from' => $row['from'],
                    'target' => $row['target'],
                    'pax' => $row['pax'] ?? $request->input('pax', 0),
                    'comments' => $row['comments'] ?? null,
                    'vehicule_id' => $row['vehicule_id'],
                    'vehicle_provider_acente_id' => $row['vehicle_provider_acente_id'] ?? null,
                    'external_vehicle_note' => $row['external_vehicle_note'] ?? null,
                    'external_vehicle_price' => $row['external_vehicle_price'] ?? null,
                    'driver_id' => $row['driver_id'],
                    'km' => $row['km'] ?? 0,
                    'post_id' => $post->id,
                    'mission' => !empty($row['mission']) ? 1 : 0,
                    'accueil' => !empty($row['accueil']) ? 1 : 0,
                    'status_id' => 2,
                    'ofis_start' => $plannedEnRoute ? $plannedEnRoute->toDateTimeString() : null,
                ]);

                foreach ($trajets as $trajetData) {
                    $transfer->trajets()->create($trajetData);
                }

                HareketHelper::create($transfer, [
                    'aciklama'  => 'Transfer created from quick file',
                    'tarih'     => $dateStart,
                    'post_id'   => $post->id,
                    'amount'    => 0,
                    'ab'        => 2,
                    'kur_id'    => 1,
                    'acente_id' => $row['driver_id'],
                ]);
            }

            LogActivity::addToLog('CreateFromTransfer.', $post->id, 'Created from transfer quick form');

            return $post;
        });

        return redirect()->route('posts.show', $post->id)
            ->with('flash_message', 'Dossier ' . $post->title . ' créé avec ses transferts.');
    }

    public function show($id)
    {
        $stroreFile = request()->getHost() == 'ofis.tittravel.com' ? 'imagestit' : 'images';
        $post = Post::findOrFail($id);
        $missingTransferMovements = HareketHelper::ensurePostTransferMovements($post);
        if ($missingTransferMovements > 0) {
            LogActivity::addToLog(
                'MissingTransferMovementsAutoRepaired.',
                $post->id,
                'Created ' . $missingTransferMovements . ' missing transfer movement rows while opening file.'
            );
        }
        $messages = Message::where('post_id', $id)->get();
        $previous = Post::where('id', '<', $post->id)->max('id');
        $servicetypes = $this->getServiceTypes();
        $vehicules = Vehicule::whereNull('sales')
    ->orderBy('name')
    ->get()
    ->mapWithKeys(function($vehicule) {
        $name = $vehicule->name;
        if ($vehicule->enpanne) {
            $name .= ' (En Panne)';
        }
        return [$vehicule->id => $name];
    });
        $sirkets = Sirket::orderBy('name')->pluck('name', 'id');
        $firmas = Firma::orderBy('name')->pluck('name', 'id');
        $acentes = Acente::orderBy('name')->pluck('name', 'id');
        $kurs = Kur::pluck('short_name', 'id');
        $kdvs = Kdv::pluck('name', 'id');
        $payments = Payment::pluck('name', 'id');
    
        $files = file_exists(public_path("$stroreFile/$id")) ? File::allFiles(public_path("$stroreFile/$id")) : [];
    
        $eurid = Option::where('name', 'eurid')->firstOrFail();
        $usdid = Option::where('name', 'usdid')->firstOrFail();
        $tlid = Option::where('name', 'tlid')->firstOrFail();
    
        $eurkur = Dovizal::where(['tarih' => $post->start_date, 'kur_id' => $eurid->value])->first();
        $usdkur = Dovizal::where(['tarih' => $post->start_date, 'kur_id' => $usdid->value])->first();
        $next = Post::where('id', '>', $post->id)->min('id');
    
        // Calculate karzarar
        $karzarar = [];
        foreach ($post->hareket->groupBy('kur_id') as $kurId => $group) {
    $karzarar[$kurId] = $group->sum(function ($h) {
        return $h->ab == 2 ? $h->amount : -$h->amount;
    });
}
    
        // Calculate exchange rate multipliers
        $eurocarpan = $eurkur ? $eurkur->value : 1;
        $usdcarpan = $usdkur ? $usdkur->value : 1;
    
        return view('posts.show', compact(
            'post', 'previous', 'next', 'files', 'messages', 'kdvs', 'servicetypes', 
            'vehicules', 'firmas', 'kurs', 'acentes', 'payments', 'stroreFile', 
            'eurkur', 'usdkur', 'eurid', 'usdid', 'tlid', 'sirkets', 'karzarar', 'eurocarpan', 'usdcarpan'
        ));
    }

    public function updateAllTransferVehicules(Request $request, Post $post)
    {
        $data = $request->validate([
            'vehicule_id' => 'required|integer|exists:vehicules,id',
        ]);

        $limit = now('Europe/Paris')->startOfDay();
        $baseQuery = Transfer::where('post_id', $post->id)
            ->where('start_date', '>=', $limit);
        $skippedPast = Transfer::where('post_id', $post->id)
            ->where('start_date', '<', $limit)
            ->count();

        $oldVehicules = (clone $baseQuery)
            ->pluck('vehicule_id', 'id')
            ->toArray();

        $updated = (clone $baseQuery)->update(['vehicule_id' => $data['vehicule_id']]);

        LogActivity::addToLog('FileVehiclesBulkUpdate.', $post->id, json_encode([
            'old' => $oldVehicules,
            'new_vehicule_id' => (int) $data['vehicule_id'],
            'updated_transfers' => $updated,
            'skipped_past_transfers' => $skippedPast,
            'from_date' => $limit->toDateTimeString(),
        ]));

        return redirect()->route('posts.show', $post->id)
            ->with('flash_message', $updated . ' transfert(s) à partir d’aujourd’hui mis à jour. ' . $skippedPast . ' transfert(s) passé(s) non modifié(s).');
    }
    
    private function getServiceTypes()
    {
        return [
            'transfer' => Servicetype::whereIn('firma_id', $this->transferids)->orderBy('name')->pluck('name', 'id'),
            'dispo' => Servicetype::whereIn('firma_id', $this->dispoids)->orderBy('name')->pluck('name', 'id'),
        ];
    }

    public function edit($id)
    {
        $post = Post::findOrFail($id);
        $acente = Acente::orderBy('name')->pluck('name', 'id');
        $status = Status::pluck('name', 'id');
        $users = User::pluck('name', 'id');
        return view('posts.edit', compact('post', 'acente', 'status', 'users'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:100',
            'start_date' => 'required|date|after:2016-01-01',
            'end_date' => 'required|date|after_or_equal:start_date',
            'acente_id' => 'required',
            'status_id' => 'required',
        ]);
        
        $post = Post::findOrFail($id);
        $oldData = $post->getOriginal();    
      
        $payload = $request->all();
        if (array_key_exists('body', $payload) && $payload['body'] !== null && strlen((string) $payload['body']) > 900) {
            $payload['body'] = mb_strcut((string) $payload['body'], 0, 900, 'UTF-8');
        }

        $post->update($payload);
        $changes = [];
        foreach ($request->except(['_method', '_token']) as $key => $value) {
            if (isset($oldData[$key]) && $oldData[$key] != $value) {
                $changes[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value
                ];
            }
        }
                                   
        LogActivity::addToLog('FileUpdate.', $post->id, json_encode($changes));

        return redirect()->route('posts.show', $post->id)
            ->with('flash_message', 'File, ' . $post->title . ' updated');
          
    }

    public function destroy($id)
    {
        if (Auth::user()->hasPermissionTo('Administer roles & permissions'))
        {
        $post = Post::findOrFail($id);
        $post->delete();

        return redirect()->route('posts.index')
            ->with('flash_message', 'File successfully deleted');

        }
    }


    public function updateBilling(Request $request, Post $post)
    {
        $data = $request->validate([
            'billing_status' => 'required|in:to_invoice,invoiced,do_not_invoice',
            'payment_destination' => 'nullable|in:france,turkey,cash,other',
            'payment_status' => 'required|in:not_received,partial,received',
        ]);

        if ($data['billing_status'] === 'invoiced' && !$post->invoice()
            ->whereNotNull('resmi')
            ->where('resmi', '!=', '')
            ->exists()) {
            $data['billing_status'] = 'to_invoice';
        }

        $oldData = $post->only(['billing_status', 'payment_destination', 'payment_status']);
        $post->update($data);

        LogActivity::addToLog('FacturationPaiementUpdate.', $post->id, json_encode([
            'old' => $oldData,
            'new' => $data,
        ]));

        $redirectTo = $request->input('return_to');

        $redirectHost = $redirectTo ? parse_url($redirectTo, PHP_URL_HOST) : null;

        if ($redirectTo && (!$redirectHost || $redirectHost === $request->getHost())) {
            return redirect($redirectTo)->with('flash_message', 'Facturation et paiement mis a jour.');
        }

        return redirect()->route('posts.show', $post->id)->with('flash_message', 'Facturation et paiement mis a jour.');
    }

    public function message(Request $request)
    {
        $request->validate([
            'tittle' => 'required|max:100',
        ]);

        Message::create($request->all());
        return redirect()->back()->with('flash_message', 'Added message:');
    }

    public function messageedit(Request $request)
    {
        $request->validate([
            'tittle' => 'required|max:100',
        ]);

        $message = Message::findOrFail($request->input('id'));
        $message->update($request->all());

        return redirect()->back()->with('flash_message', 'Message edited');
    }

    public function balancefile()
    {
      
        if (Auth::user()->hasAnyRole(['Superadmin', 'Admin', 'ofis']) || Auth::user()->hasAnyPermission(['balances.view', 'ofis'])) 
            {
       
        $start_date = request('start_date', date('Y-m-d 00:00:00'));
        $end_date = request('end_date', date('Y-m-d', strtotime("+1 year")));

        $posts = Post::query()
        ->with(['acente', 'status.color'])
        ->withSum(['hareket as total_ab1' => function ($q) {
            $q->where('ab', 1);
        }], 'amount')
        ->withSum(['hareket as total_ab2' => function ($q) {
            $q->where('ab', 2);
        }], 'amount')
        ->whereBetween('start_date', [$start_date, $end_date])
        ->orderBy('start_date')
        ->paginate(100);

        $statuses = \App\Models\Status::all();

        return view('posts.karzarar', compact('posts', 'statuses'));
      }
    }
  

    public function excelhareket($id)
    {
        return Excel::download(new ExpensesExport($id), 'gider.xlsx');
    }

    public function updateComment(Request $request)
{
    $post = Post::findOrFail($request->id);
    $post->body = $request->comment;
    $post->save();

    return response()->json(['success'=>true]);
}
public function updateStatus(Request $request, Post $post)
{
    if (!auth()->user()->hasAnyRole(['Superadmin', 'Admin', 'ofis']) && !auth()->user()->hasAnyPermission(['posts.update', 'ofis'])) {
        abort(403);
    }

    $data = $request->validate([
        'status_id' => ['required', 'integer', 'exists:statuses,id'],
    ]);

    $post->update(['status_id' => $data['status_id']]);

    return response()->json(['ok' => true]);
}
public function mismatchTransfers()
{
    $transferStats = Transfer::query()
        ->selectRaw('post_id, MIN(start_date) as first_transfer_start, MAX(end_date) as last_transfer_end, COUNT(*) as transfer_count')
        ->whereNull('deleted_at')
        ->groupBy('post_id');

    $posts = Post::query()
        ->with('acente')
        ->joinSub($transferStats, 'transfer_stats', function ($join) {
            $join->on('transfer_stats.post_id', '=', 'posts.id');
        })
        ->select([
            'posts.*',
            'transfer_stats.first_transfer_start',
            'transfer_stats.last_transfer_end',
            'transfer_stats.transfer_count',
        ])
        ->where(function ($q) {
            $q->whereRaw('DATE(transfer_stats.first_transfer_start) <> DATE(posts.start_date)')
              ->orWhereRaw('DATE(transfer_stats.last_transfer_end) <> DATE(posts.end_date)');
        })
        ->orderBy('posts.start_date', 'desc')
        ->paginate(50);

    $visiblePostIds = $posts->getCollection()->pluck('id');
    $visibleTransfers = Transfer::whereIn('post_id', $visiblePostIds)
        ->whereNull('deleted_at')
        ->orderBy('start_date')
        ->get(['id', 'post_id', 'start_date', 'end_date']);

    $firstTransfers = $visibleTransfers
        ->groupBy('post_id')
        ->map(fn ($rows) => $rows->sortBy('start_date')->first());

    $lastTransfers = $visibleTransfers
        ->groupBy('post_id')
        ->map(fn ($rows) => $rows->sortByDesc('end_date')->first());

    return view('posts.mismatch-transfers', compact('posts', 'firstTransfers', 'lastTransfers'));
}

public function syncDatesFromTransfers(Post $post)
{
    $firstTransfer = Transfer::where('post_id', $post->id)
        ->whereNull('deleted_at')
        ->orderBy('start_date')
        ->first();

    $lastTransfer = Transfer::where('post_id', $post->id)
        ->whereNull('deleted_at')
        ->orderByDesc('end_date')
        ->first();

    if (!$firstTransfer || !$lastTransfer) {
        return back()->with('error', 'Aucun transfert actif trouve pour ce dossier.');
    }

    $oldDates = [
        'start_date' => $post->start_date,
        'end_date' => $post->end_date,
    ];

    $post->start_date = $firstTransfer->start_date;
    $post->end_date = $lastTransfer->end_date;
    $post->save();

    LogActivity::addToLog('PostDatesSyncFromTransfers.', $post->id, json_encode([
        'old' => $oldDates,
        'new' => [
            'start_date' => $post->start_date,
            'end_date' => $post->end_date,
        ],
        'first_transfer_id' => $firstTransfer->id,
        'last_transfer_id' => $lastTransfer->id,
    ]));

    return back()->with('success', 'Dates du dossier #' . $post->id . ' mises a jour selon les transferts.');
}




}
