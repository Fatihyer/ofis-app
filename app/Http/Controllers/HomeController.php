<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transfer;
use App\Models\Invoice;
use App\Models\Acente;
use App\Models\Post;
use App\Models\Vehicule;
use App\Models\Depot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use DB;
use App\Models\Option;
use App\Models\Mission;
use App\Models\Hareket;
use Illuminate\Support\Facades\Log;
use Mail;
use Carbon\Carbon; 
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Helpers\LogActivity;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
   $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
{
    $user = Auth::user();
    $isAdmin = $user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport'])
        || $user->hasAnyPermission(['transfers.operations', 'ofis', 'transport']);
    $emptyAssignmentNames = ['-', '--', '---', '----'];
    $depotOptions = $this->operationDepotOptions();
    $depotLabels = $depotOptions->mapWithKeys(fn ($depot) => [(int) $depot->id => $depot->planning_label]);
    $selectedDepotId = $this->selectedOperationDepotId(request(), $depotLabels);

    // Ortak: N+1 kır
    $baseQuery = Transfer::with([
        'depot',
        'status.color',
        'servicetype',
        'post.client',
        'driver',
        'secondDriver',
        'vehicule',
        'trajets',
        'missionr',
        'post.acente',
        'post.user',
    ])->sortable()->orderBy('start_date');

    if ($isAdmin) {
        /** -------------------------
         *  ADMIN / OFIS / TRANSPORT
         *  -------------------------
         *  Tarih önceliği:
         *  1) start_date & end_date (datepicker)
         *  2) dateOption (yesterday/today/tomorrow)
         *  3) default: bugün → +365 gün
         */
        $startParam = request('start_date');
        $endParam   = request('end_date');
        $dateOption = request('dateOption');
        // 1) Varsayılan
        $start = Carbon::today()->startOfDay();
        $end   = Carbon::today()->addDays(365)->endOfDay();
        if ($startParam && $endParam) {
            // Datepicker seçildiyse onu kullan
            $start = Carbon::parse($startParam)->startOfDay();
            $end   = Carbon::parse($endParam)->endOfDay();
        } 
        if ($dateOption) {
    switch ($dateOption) {
        case 'yesterday':
            $start = Carbon::yesterday()->startOfDay();
            $end   = Carbon::yesterday()->endOfDay();
            break;
        case 'tomorrow':
            $start = Carbon::tomorrow()->startOfDay();
            $end   = Carbon::tomorrow()->endOfDay();
            break;
        case 'today':
        default:
            $start = Carbon::today()->startOfDay();
            $end   = Carbon::today()->endOfDay();
            break;
    }
}

        // Filtreler
        $query = (clone $baseQuery)->whereBetween('start_date', [$start, $end]);
        if ($selectedDepotId !== null) {
            $this->applyOperationDepotFilter($query, $selectedDepotId);
        }

       $driverParam = request('driver');
        if ($driverParam !== null && $driverParam !== '' && $driverParam !== 'All' && $driverParam !== '0') {
            $query->where(function ($driverQuery) use ($driverParam) {
                $driverQuery->where('driver_id', $driverParam);
                if (Schema::hasColumn('transfers', 'second_driver_id')) {
                    $driverQuery->orWhere('second_driver_id', $driverParam);
                }
            });
        }

        $vehiculeParam = request('vehicule');
        if ($vehiculeParam !== null && $vehiculeParam !== '' && $vehiculeParam !== 'All' && $vehiculeParam !== '0') {
            $query->where('vehicule_id', $vehiculeParam);
        }

        $acenteParam = request('acente');
        if ($acenteParam !== null && $acenteParam !== '' && $acenteParam !== 'All' && $acenteParam !== '0') {
            $query->whereHas('post', function ($q) use ($acenteParam) {
                $q->where('acente_id', $acenteParam);
            });
        }
        $summaryQuery = clone $query;
        $operationStats = [
            'total' => (clone $summaryQuery)->count(),
            'without_driver' => (clone $summaryQuery)->where(function ($q) use ($emptyAssignmentNames) {
                $q->whereNull('driver_id')
                  ->orWhereHas('driver', function ($driverQuery) use ($emptyAssignmentNames) {
                      $driverQuery->whereIn(DB::raw('TRIM(name)'), $emptyAssignmentNames);
                  });
            })->count(),
            'without_vehicle' => (clone $summaryQuery)
                ->whereHas('servicetype', function ($serviceQuery) {
                    $serviceQuery->where('firma_id', 3);
                })
                ->where(function ($q) use ($emptyAssignmentNames) {
                    $q->whereNull('vehicule_id')
                      ->orWhereHas('vehicule', function ($vehiculeQuery) use ($emptyAssignmentNames) {
                          $vehiculeQuery->whereNull('real')
                              ->orWhereIn(DB::raw('TRIM(name)'), $emptyAssignmentNames);
                      });
                })->count(),
            'missions' => (clone $summaryQuery)->where('mission', true)->count(),
            'pax' => (clone $summaryQuery)->sum('pax'),
        ];

        $visibleOptionRows = (clone $query)
            ->with(['post:id,acente_id'])
            ->get(['id', 'driver_id', 'second_driver_id', 'vehicule_id', 'post_id']);

        $driverIds = $visibleOptionRows->pluck('driver_id')
            ->merge($visibleOptionRows->pluck('second_driver_id'))
            ->filter()
            ->unique()
            ->values();
        $vehiculeIds = $visibleOptionRows->pluck('vehicule_id')->filter()->unique()->values();
        $acenteIds = $visibleOptionRows->pluck('post.acente_id')->filter()->unique()->values();

        $driver = Acente::whereIn('id', $driverIds)
            ->whereNotIn(DB::raw('TRIM(name)'), $emptyAssignmentNames)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $vehicules = Vehicule::whereIn('id', $vehiculeIds)
            ->whereNotNull('real')
            ->whereNotIn(DB::raw('TRIM(name)'), $emptyAssignmentNames)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $acentes = Acente::whereIn('id', $acenteIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $transfers = $query->paginate(30)->appends(request()->except('page'));

        // Home blade’in bekledikleri: (hepsini her zaman doldur)
        $uninvoicedExcludedPostIds = collect(explode(',', (string) Option::where('name', 'uninvoicedExcludedPostIds')->value('value')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();
        $congeIds = collect(explode(',', (string) Option::where('name', 'conge')->value('value')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $faturasizBaseQuery = Post::query()
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
            ->where(function ($q) {
                $q->whereNull('posts.billing_status')
                    ->orWhere('posts.billing_status', '!=', 'do_not_invoice');
            })
            ->when(!empty($uninvoicedExcludedPostIds), fn ($q) => $q->whereNotIn('posts.id', $uninvoicedExcludedPostIds))
            ->where('posts.start_date', '<=', Carbon::today()->endOfDay())
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
            ])
            ->havingRaw('(COUNT(DISTINCT transfers.id) + COUNT(DISTINCT hotels.id) + COUNT(DISTINCT others.id) + COUNT(DISTINCT stocks.id)) > 0');

        $faturasizTotal = DB::query()->fromSub(clone $faturasizBaseQuery, 'dossiers_sans_facture')->count();
        $faturasiz = (clone $faturasizBaseQuery)
            ->orderBy('posts.start_date', 'desc')
            ->orderBy('posts.id', 'desc')
            ->limit(40)
            ->get();

        $nodriver = Transfer::select('post_id')
            ->whereNull('driver_id')
            ->groupBy('post_id')
            ->orderBy('post_id')
            ->get();

        $filelist = Post::orderBy('id', 'desc')->pluck('id', 'id');

        $myDossiers = Post::with(['acente', 'status.color', 'invoice'])
            ->withCount(['transfer', 'hotels', 'others', 'stock'])
            ->where('user_id', $user->id)
            ->whereDate('start_date', '>=', Carbon::today()->toDateString())
            ->orderBy('start_date')
            ->orderBy('id')
            ->limit(12)
            ->get();
        $arabalar = Vehicule::whereNotNull('real')
            ->where(function ($q) {
                $q->whereNull('control')
                  ->orWhereDate('control', '=', '1970-01-01')
                  ->orWhereNull('sigorta')
                  ->orWhereDate('sigorta', '=', '1970-01-01');
            })->get();

        foreach ($arabalar as $araba) {
            session()->flash('toast-danger-' . $araba->id, 'Vehicle ' . ($araba->plaka ?? $araba->id) . ' sigorta/kontrol tarihi girilmemiş.');
        }

        $toastMessages = [];
        $startStr = $start->format('Y-m-d');
        $endStr   = $end->format('Y-m-d');
        $daterangeDisplay = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
        return view('home', compact(
            'transfers',
            'faturasiz',
            'faturasizTotal',
            'acentes',
            'nodriver',
            'driver',
            'filelist',
            'vehicules',
            'arabalar',
            'myDossiers',
            'toastMessages',
            'operationStats',
            'depotOptions',
            'depotLabels',
            'selectedDepotId',
            'startStr',
            'endStr',
            'daterangeDisplay',
        ));
    }


    /** -------------
     *  DRIVER VIEW
     *  -------------
     *  Sürücüde datepicker yok; sadece dateOption kullanılsın.
     */
    $dateOption = request('dateOption', 'today');
    switch ($dateOption) {
        case 'yesterday':
            $start = Carbon::yesterday()->startOfDay();
            $end   = Carbon::yesterday()->endOfDay();
            break;
        case 'tomorrow':
            $start = Carbon::tomorrow()->startOfDay();
            $end   = Carbon::tomorrow()->endOfDay();
            break;
        case 'today':
        default:
            $start = Carbon::today()->startOfDay();
            $end   = Carbon::today()->endOfDay();
            break;
    }

    // Option sınırları ile görünümü kısıtla
    $dayBefore = (int) (Option::where('name', 'kaptanDayBefore')->value('value') ?? 0);
    $dayAfter  = (int) (Option::where('name', 'kaptanDayAfter')->value('value') ?? 0);
    $afterHour = (int) (Option::where('name', 'kaptanDayAfterTime')->value('value') ?? 0);

    $nowHour = (int) now()->format('H');
    $maxEnd  = $nowHour >= $afterHour
        ? Carbon::today()->addDays($dayAfter)->endOfDay()
        : Carbon::today()->endOfDay();
    $minStart = Carbon::today()->subDays($dayBefore)->startOfDay();

    if ($start->lt($minStart)) $start = $minStart;
    if ($end->gt($maxEnd))     $end   = $maxEnd;

    $acenteIds = $user->acentes()->pluck('acentes.id')->toArray();

    $transfers = (clone $baseQuery)
        ->whereBetween('start_date', [$start, $end])
        ->where(function ($q) use ($acenteIds) {
            $q->whereIn('driver_id', $acenteIds);
            if (Schema::hasColumn('transfers', 'second_driver_id')) {
                $q->orWhereIn('second_driver_id', $acenteIds);
            }
        })
        ->paginate(50)
        ->appends(request()->except('page'));

    return view('driver.index', compact('transfers'));
}

public function timeline(Request $request)
{
    $user = Auth::user();
    if (!$user->hasRole('Superadmin')) {
        abort(403);
    }

    $isAdmin = $user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport'])
        || $user->hasAnyPermission(['transfers.operations', 'ofis', 'transport']);

    if (!$isAdmin) {
        return redirect()->route('ev');
    }

    $emptyAssignmentNames = ['-', '--', '---', '----'];
    $depotOptions = $this->operationDepotOptions();
    $depotLabels = $depotOptions->mapWithKeys(fn ($depot) => [(int) $depot->id => $depot->planning_label]);
    $selectedDepotId = $this->selectedOperationDepotId($request, $depotLabels);
    $startParam = $request->input('start_date');
    $endParam = $request->input('end_date');
    $dateOption = $request->input('dateOption');

    $start = Carbon::today()->startOfDay();
    $end = Carbon::today()->endOfDay();

    if ($startParam && $endParam) {
        $start = Carbon::parse($startParam)->startOfDay();
        $end = Carbon::parse($endParam)->endOfDay();
    }

    if ($dateOption) {
        switch ($dateOption) {
            case 'yesterday':
                $start = Carbon::yesterday()->startOfDay();
                $end = Carbon::yesterday()->endOfDay();
                break;
            case 'tomorrow':
                $start = Carbon::tomorrow()->startOfDay();
                $end = Carbon::tomorrow()->endOfDay();
                break;
            case 'today':
            default:
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                break;
        }
    }

    if ($end->lt($start)) {
        $end = $start->copy()->endOfDay();
    }

    $rangeLimited = false;
    if ($start->diffInDays($end) > 13) {
        $end = $start->copy()->addDays(13)->endOfDay();
        $rangeLimited = true;
    }

    $baseQuery = Transfer::with([
        'depot',
        'status.color',
        'servicetype',
        'post.acente',
        'post.user',
        'driver',
        'secondDriver',
        'vehicule',
        'trajets',
        'missionr',
    ])->whereBetween('start_date', [$start, $end]);
    if ($selectedDepotId !== null) {
        $this->applyOperationDepotFilter($baseQuery, $selectedDepotId);
    }

    $driverParam = $request->input('driver');
    if ($driverParam !== null && $driverParam !== '' && $driverParam !== 'All' && $driverParam !== '0') {
        $baseQuery->where(function ($driverQuery) use ($driverParam) {
            $driverQuery->where('driver_id', $driverParam);
            if (Schema::hasColumn('transfers', 'second_driver_id')) {
                $driverQuery->orWhere('second_driver_id', $driverParam);
            }
        });
    }

    $vehiculeParam = $request->input('vehicule');
    if ($vehiculeParam !== null && $vehiculeParam !== '' && $vehiculeParam !== 'All' && $vehiculeParam !== '0') {
        $baseQuery->where('vehicule_id', $vehiculeParam);
    }

    $acenteParam = $request->input('acente');
    if ($acenteParam !== null && $acenteParam !== '' && $acenteParam !== 'All' && $acenteParam !== '0') {
        $baseQuery->whereHas('post', function ($q) use ($acenteParam) {
            $q->where('acente_id', $acenteParam);
        });
    }

    $summaryQuery = clone $baseQuery;
    $operationStats = [
        'total' => (clone $summaryQuery)->count(),
        'without_driver' => (clone $summaryQuery)->where(function ($q) use ($emptyAssignmentNames) {
            $q->whereNull('driver_id')
              ->orWhereHas('driver', function ($driverQuery) use ($emptyAssignmentNames) {
                  $driverQuery->whereIn(DB::raw('TRIM(name)'), $emptyAssignmentNames);
              });
        })->count(),
        'without_vehicle' => (clone $summaryQuery)
            ->whereHas('servicetype', function ($serviceQuery) {
                $serviceQuery->where('firma_id', 3);
            })
            ->where(function ($q) use ($emptyAssignmentNames) {
                $q->whereNull('vehicule_id')
                  ->orWhereHas('vehicule', function ($vehiculeQuery) use ($emptyAssignmentNames) {
                      $vehiculeQuery->whereNull('real')
                          ->orWhereIn(DB::raw('TRIM(name)'), $emptyAssignmentNames);
                  });
            })->count(),
        'confirmed' => (clone $summaryQuery)->where(function ($q) {
            $q->whereNotNull('driver_app_confirmed_at')
              ->orWhereNotNull('driver_confirmed_at');
        })->count(),
        'missions' => (clone $summaryQuery)->where('mission', true)->count(),
    ];

    $visibleOptionRows = (clone $baseQuery)
        ->with(['post:id,acente_id'])
        ->get(['id', 'driver_id', 'second_driver_id', 'vehicule_id', 'post_id']);

    $driverIds = $visibleOptionRows->pluck('driver_id')
        ->merge($visibleOptionRows->pluck('second_driver_id'))
        ->filter()
        ->unique()
        ->values();
    $vehiculeIds = $visibleOptionRows->pluck('vehicule_id')->filter()->unique()->values();
    $acenteIds = $visibleOptionRows->pluck('post.acente_id')->filter()->unique()->values();

    $driver = Acente::whereIn('id', $driverIds)
        ->whereNotIn(DB::raw('TRIM(name)'), $emptyAssignmentNames)
        ->orderBy('name')
        ->pluck('name', 'id')
        ->toArray();

    $vehicules = Vehicule::whereIn('id', $vehiculeIds)
        ->whereNotIn(DB::raw('TRIM(name)'), $emptyAssignmentNames)
        ->orderBy('name')
        ->pluck('name', 'id')
        ->toArray();

    $acentes = Acente::whereIn('id', $acenteIds)
        ->orderBy('name')
        ->pluck('name', 'id')
        ->toArray();

    $transfers = (clone $baseQuery)
        ->orderBy('start_date')
        ->limit(700)
        ->get();

    $minHour = 6;
    $maxHour = 22;
    if ($transfers->isNotEmpty()) {
        $minHour = max(0, min(6, (int) $transfers->min(fn ($transfer) => Carbon::parse($transfer->ofis_start ?: $transfer->start_date)->format('H'))));
        $maxHour = min(24, max(22, (int) $transfers->max(fn ($transfer) => Carbon::parse($transfer->end_date ?: $transfer->start_date)->format('H')) + 1));
    }
    if ($maxHour <= $minHour) {
        $maxHour = min(24, $minHour + 12);
    }
    $hours = range($minHour, $maxHour);

    $timelineDays = $transfers
        ->groupBy(fn ($transfer) => Carbon::parse($transfer->start_date)->format('Y-m-d'))
        ->map(function ($dayTransfers, $date) use ($emptyAssignmentNames) {
            $rows = $dayTransfers
                ->groupBy(function ($transfer) use ($emptyAssignmentNames) {
                    $driverName = trim(optional($transfer->driver)->name ?? '');
                    if (!$transfer->driver || in_array($driverName, $emptyAssignmentNames, true)) {
                        return 'sans_chauffeur';
                    }
                    return 'driver_' . $transfer->driver_id;
                })
                ->map(function ($rowTransfers, $key) {
                    $first = $rowTransfers->first();
                    return (object) [
                        'key' => $key,
                        'name' => $key === 'sans_chauffeur' ? 'Sans chauffeur' : optional($first->driver)->name,
                        'transfers' => $rowTransfers->sortBy('start_date')->values(),
                    ];
                })
                ->sortBy(fn ($row) => $row->key === 'sans_chauffeur' ? 'zzzz' : $row->name)
                ->values();

            return (object) [
                'date' => Carbon::parse($date)->startOfDay(),
                'transfer_count' => $dayTransfers->count(),
                'rows' => $rows,
            ];
        })
        ->sortBy(fn ($day) => $day->date->format('Y-m-d'))
        ->values();

    $startStr = $start->format('Y-m-d');
    $endStr = $end->format('Y-m-d');
    $daterangeDisplay = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');

    return view('home_timeline', compact(
        'timelineDays',
        'transfers',
        'driver',
        'vehicules',
        'acentes',
        'operationStats',
        'depotOptions',
        'depotLabels',
        'selectedDepotId',
        'start',
        'end',
        'startStr',
        'endStr',
        'daterangeDisplay',
        'hours',
        'minHour',
        'maxHour',
        'rangeLimited',
        'emptyAssignmentNames'
    ));
}

    private function operationDepotOptions()
    {
        if (!Schema::hasTable('depots')) {
            return collect();
        }

        return Depot::where(function ($query) {
                $query->where('active', 1)->orWhereNull('active');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'city'])
            ->map(function ($depot) {
                $depot->planning_label = $depot->city ?: str_replace(['Dépôt ', 'Depot '], '', (string) $depot->name);
                $depot->planning_label = $depot->planning_label ?: ($depot->code ?: 'Dépôt #' . $depot->id);
                return $depot;
            });
    }

    private function selectedOperationDepotId(Request $request, $depotLabels): ?int
    {
        $requestedDepotId = $request->input('depot_id');
        $selectedDepotId = $requestedDepotId === null || $requestedDepotId === '' ? null : (int) $requestedDepotId;

        if ($selectedDepotId !== null && !$depotLabels->has($selectedDepotId)) {
            return null;
        }

        return $selectedDepotId;
    }

    private function applyOperationDepotFilter($query, int $selectedDepotId)
    {
        $hasTransferDepot = Schema::hasColumn('transfers', 'depot_id');
        $hasVehicleDepot = Schema::hasColumn('vehicules', 'depot_id');

        if (!$hasTransferDepot && !$hasVehicleDepot) {
            return $query;
        }

        return $query->where(function ($depotQuery) use ($selectedDepotId, $hasTransferDepot, $hasVehicleDepot) {
            if ($hasVehicleDepot) {
                $vehicleFilter = function ($vehicleQuery) use ($selectedDepotId) {
                    $vehicleQuery->where('depot_id', $selectedDepotId);
                };

                $depotQuery->whereHas('vehicule', $vehicleFilter);
            }

            if ($hasTransferDepot) {
                $transferDepotFilter = function ($transferDepotQuery) use ($selectedDepotId, $hasVehicleDepot) {
                    $transferDepotQuery->where('depot_id', $selectedDepotId);

                    if ($hasVehicleDepot) {
                        $transferDepotQuery->where(function ($vehicleFallbackQuery) {
                            $vehicleFallbackQuery->whereNull('vehicule_id')
                                ->orWhereDoesntHave('vehicule')
                                ->orWhereHas('vehicule', function ($vehicleQuery) {
                                    $vehicleQuery->whereNull('depot_id');
                                });
                        });
                    }
                };

                if ($hasVehicleDepot) {
                    $depotQuery->orWhere($transferDepotFilter);
                } else {
                    $depotQuery->where($transferDepotFilter);
                }
            }
        });
    }

    private function hermesEngineToastMessages(Carbon $periodStart, Carbon $periodEnd): array
    {
        $now = Carbon::now('Europe/Paris');
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $checkStart = $periodStart->copy()->greaterThan($todayStart) ? $periodStart->copy() : $todayStart;
        $checkEnd = $periodEnd->copy()->lessThan($todayEnd) ? $periodEnd->copy() : $todayEnd;

        if ($checkStart->greaterThan($checkEnd)) {
            return [];
        }

        $transfersToCheck = Transfer::with(['vehicule', 'driver', 'servicetype', 'post.acente', 'missionr'])
            ->whereBetween('start_date', [$checkStart, $checkEnd])
            ->whereHas('vehicule', function ($q) {
                $q->whereNotNull('real')
                  ->whereNotNull('hermes_uid');
            })
            ->orderBy('start_date')
            ->get();

        if ($transfersToCheck->isEmpty()) {
            return [];
        }

        try {
            $units = app(HermesApiService::class)->get('/units');
        } catch (\Throwable $e) {
            Log::warning('Hermes engine alert check failed', ['error' => $e->getMessage()]);
            return [];
        }

        if (HermesApiService::isDailyLimitPayload($units)) {
            return [data_get($units, 'error.message', 'Hermes: limite quotidienne API atteinte.')];
        }

        if (HermesApiService::isUnavailablePayload($units)) {
            Log::warning('Hermes engine alert skipped: API unavailable', [
                'error' => data_get($units, 'error.message'),
            ]);
            return [];
        }

        if (!is_array($units)) {
            return [];
        }

        $unitStatusByUid = collect($units)
            ->filter(fn ($unit) => !empty($unit['uid']))
            ->mapWithKeys(function ($unit) {
                $statusCode = data_get($unit, 'last_position.status.status');
                return [$unit['uid'] => [
                    'code' => is_numeric($statusCode) ? (int) $statusCode : null,
                    'label' => data_get($unit, 'last_position.status.label', 'Statut inconnu'),
                    'date' => data_get($unit, 'last_position.date'),
                ]];
            });

        $messages = [];
        foreach ($transfersToCheck as $transfer) {
            $controlTime = $transfer->ofis_start
                ? Carbon::parse($transfer->ofis_start, 'Europe/Paris')
                : Carbon::parse($transfer->start_date, 'Europe/Paris')->subHour();

            if ($controlTime->greaterThan($now)) {
                continue;
            }

            if ($transfer->missionr && $transfer->missionr->hareket) {
                continue;
            }

            $vehicule = $transfer->vehicule;
            $status = $unitStatusByUid->get($vehicule->hermes_uid);
            $runningCodes = [1, 3]; // 1 Conduite, 3 Moteur tournant
            $isRunning = $status && in_array($status['code'], $runningCodes, true);

            if ($isRunning) {
                continue;
            }

            $vehicleName = trim(($vehicule->plaka ? $vehicule->plaka . ' - ' : '') . ($vehicule->name ?? 'Véhicule'));
            $driverName = optional($transfer->driver)->name ?: 'chauffeur non défini';
            $serviceName = optional($transfer->servicetype)->name ?: 'service';
            $statusLabel = $status['label'] ?? 'statut Hermes indisponible';
            $messages[] = "Hermes alerte: {$vehicleName} ne semble pas démarré ({$statusLabel}) pour le transfert #{$transfer->id} à "
                . Carbon::parse($transfer->start_date)->format('H:i')
                . " / en route prévu {$controlTime->format('H:i')} ({$driverName}, {$serviceName}).";
        }

        return array_slice($messages, 0, 12);
    }

/*
    {
        $dateOption = app('request')->input('dateOption', 'default');
        switch ($dateOption) {
            case 'yesterday':
                $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $end_date = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = date('Y-m-d 00:00:00');
                $end_date = date('Y-m-d 23:59:59');
                break;
            case 'tomorrow':
                $start_date = date('Y-m-d 00:00:00', strtotime('+1 day'));
                $end_date = date('Y-m-d 23:59:59', strtotime('+1 day'));
                break;
            default:
                $start_date = date('Y-m-d 00:00:00');
                $end_date = date('Y-m-d', strtotime(date("Y-m-d", time()) . " + 365 day"));
                if (app('request')->input('start_date')) {
                    $start_date = date('Y-m-d', strtotime(app('request')->input('start_date')));
                    $end_date = date('Y-m-d', strtotime(app('request')->input('end_date') . " + 1 day"));
                }
                break;
        }
        


        if (app('request')->input('start_date')) {
            $start_date = date('Y-m-d', strtotime(app('request')->input('start_date')));
            $end_date = date('Y-m-d', strtotime(app('request')->input('end_date') . " + 1 day"));
            
        }

        if (Auth::user()->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || Auth::user()->hasAnyPermission(['transfers.operations', 'ofis', 'transport'])) {
            // Admin kullanıcılar için transfer sorguları
            if ((app('request')->input('driver')) && (app('request')->input('driver') != 'All')) {
                $transfers = Transfer::sortable()
                    ->whereBetween('start_date', [$start_date, $end_date])
                    ->where(function ($driverQuery) {
                        $driverQuery->where('driver_id', app('request')->input('driver'));
                        if (Schema::hasColumn('transfers', 'second_driver_id')) {
                            $driverQuery->orWhere('second_driver_id', app('request')->input('driver'));
                        }
                    })
                    ->orderby('start_date')
                    ->paginate(30);
            } elseif (app('request')->input('vehicule') && app('request')->input('vehicule') != 'All') {
                $transfers = Transfer::sortable()
                    ->whereBetween('start_date', [$start_date, $end_date])
                    ->where('vehicule_id', app('request')->input('vehicule'))
                    ->orderby('start_date')
                    ->paginate(30);
            } else {
                $transfers = Transfer::sortable()
                    ->whereBetween('start_date', [$start_date, $end_date])
                    ->orderby('start_date')
                    ->paginate(30);
            }

            $vehicules = Vehicule::whereNull('sales')->pluck('name', 'id')->toArray();
            $invoice = Invoice::select('post_id')->groupBy('post_id')->pluck('post_id')->toArray();
            $groupedInvoicePostIds = $this->groupedInvoicePostIdsQuery()->pluck('post_id')->toArray();
            $uninvoicedExcludedPostIds = collect(explode(',', (string) Option::where('name', 'uninvoicedExcludedPostIds')->value('value')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->values()
                ->all();
            $faturasiz = Post::whereNotIn('id', array_merge($invoice, $groupedInvoicePostIds))
                ->when(!empty($uninvoicedExcludedPostIds), fn ($q) => $q->whereNotIn('id', $uninvoicedExcludedPostIds))
                ->where('start_date', '<', $start_date)
                ->get();
            $nodriver = Transfer::select('post_id')
                ->where('driver_id', false)
                ->orWhereNull('driver_id')
                ->groupBy('post_id')
                ->orderBy('post_id')
                ->get();
            $filelist = Post::orderBy('id', 'desc')->pluck('id', 'id');
            $driver = Acente::whereHas('firmas', function ($query) {
                $query->where('id', 3);
            })->orderBy('name')->pluck('name', 'id')->toArray();
            $acentes = Acente::orderBy('name')->pluck('name', 'id')->toArray();
            // Görevler için sorgu
            $userId = Auth::id();
          /*  $missions = Mission::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->whereNotNull('user_id')
                    ->whereNull('confirmed_at');
            })->orWhere(function ($query) use ($userId) {
                $query->where('start_user_id', $userId)
                    ->whereNotNull('start_user_id')
                    ->whereNull('finish_confirmed_at');
            })->get();

            // Görevler için toast mesajlarını oluşturun
            */
            /*
            $toastMessages = [];/*
            foreach ($missions as $mission) {
                $hareketDate = date('d-m-Y', strtotime($mission->hareket));
                $toastMessages[] = " Tarih: {$hareketDate} Bu görevi henüz onaylamadınız. Lütfen onaylayın.";
            }
            $arabalar = Vehicule::whereNotNull('real')
            ->where(function ($query) {
                $query->whereNull('control')
                      ->orWhere('control', '1970-01-01 00:00:00')
                      ->orWhereNull('sigorta')
                      ->orWhere('sigorta', '1970-01-01 00:00:00');
            })
            ->get();

        // Add a "toast danger" message for each vehicle to the session
        foreach ($arabalar as $araba) {
            session()->flash('toast-danger-' . $araba->id, 'Vehicle ID ' . $araba->plaka . ' Sigorta tarihi veya Kontrol tarihi girilmemiş.');
        }






            return view('home', compact('transfers', 'faturasiz','acentes', 'nodriver', 'driver', 'filelist', 'vehicules', 'toastMessages','arabalar'));
        } else {
            // Diğer kullanıcılar için transfer sorguları
            $daybefore = Option::where('name', 'kaptanDayBefore')->first();
            $dayonce = date('Y-m-d', strtotime(date("Y-m-d", time()) . " -" . $daybefore->value . " day"));
            $dayafter = Option::where('name', 'kaptanDayAfter')->first();
            $dayaftertime = Option::where('name', 'kaptanDayAfterTime')->first();
            $timeis = idate("H");

            if ($timeis >= $dayaftertime->value) {
                $daysonra = date('Y-m-d 23:59', strtotime(date("Y-m-d", time()) . " +" . $dayafter->value . " day"));
            } else {
                $daysonra = date('Y-m-d 23:59', strtotime(date("Y-m-d", time())));
            }
            if ($start_date <= $dayonce) {
                $start_date = $dayonce;
            }
            if ($end_date >= $daysonra) {
                $end_date = $daysonra;
            }
            $acentes = Auth::user()->acentes()->pluck('acentes.id')->toArray();
            $transfers = Transfer::sortable()->where(function ($query) use ($start_date, $end_date, $acentes) {
                $query->whereBetween('start_date', [$start_date, $end_date])
                    ->whereIn('driver_id', $acentes);
            })->orderby('start_date')->paginate(50);

            return view('driver.index', compact('transfers'));

        }
    }
*/


  public function driverApp()
  {
      $user = Auth::user();
      $dateOption = request('dateOption', 'today');

      switch ($dateOption) {
          case 'tomorrow':
              $start = Carbon::tomorrow()->startOfDay();
              $end = Carbon::tomorrow()->endOfDay();
              break;
          case 'yesterday':
              $start = Carbon::yesterday()->startOfDay();
              $end = Carbon::yesterday()->endOfDay();
              break;
          case 'today':
          default:
              $start = Carbon::today()->startOfDay();
              $end = Carbon::today()->endOfDay();
              $dateOption = 'today';
              break;
      }

      $dayBefore = (int) (Option::where('name', 'kaptanDayBefore')->value('value') ?? 0);
      $dayAfter = (int) (Option::where('name', 'kaptanDayAfter')->value('value') ?? 0);
      $afterHour = (int) (Option::where('name', 'kaptanDayAfterTime')->value('value') ?? 0);
      $nowHour = (int) now()->format('H');
      $minStart = Carbon::today()->subDays($dayBefore)->startOfDay();
      $maxEnd = $nowHour >= $afterHour
          ? Carbon::today()->addDays($dayAfter)->endOfDay()
          : Carbon::today()->endOfDay();

      if ($start->lt($minStart)) {
          $start = $minStart;
      }
      if ($end->gt($maxEnd)) {
          $end = $maxEnd;
      }

      $acenteIds = $user->acentes()->pluck('acentes.id')->map(fn ($id) => (int) $id)->toArray();
      $relations = ['driver', 'secondDriver', 'vehicule', 'servicetype', 'status', 'missionr', 'post.client'];

      $transfers = Transfer::with($relations)
          ->whereBetween('start_date', [$start, $end])
          ->where(function ($query) use ($acenteIds) {
              $query->whereIn('driver_id', $acenteIds);
              if (Schema::hasColumn('transfers', 'second_driver_id')) {
                  $query->orWhereIn('second_driver_id', $acenteIds);
              }
          })
          ->orderBy('start_date')
          ->get();

      $stats = [
          'total' => $transfers->count(),
          'confirmed' => $transfers->filter(function ($transfer) use ($acenteIds) {
              $isSecond = Schema::hasColumn('transfers', 'second_driver_id')
                  && in_array((int) ($transfer->second_driver_id ?? 0), $acenteIds, true)
                  && !in_array((int) ($transfer->driver_id ?? 0), $acenteIds, true);
              return $isSecond ? (bool) $transfer->second_driver_app_confirmed_at : (bool) $transfer->driver_app_confirmed_at;
          })->count(),
      ];

      return view('driver.pwa', compact('transfers', 'dateOption', 'stats', 'acenteIds'));
  }


  public function driverAppLocation(Request $request)
  {
      $data = $request->validate([
          'latitude' => 'required|numeric|between:-90,90',
          'longitude' => 'required|numeric|between:-180,180',
          'accuracy' => 'nullable|numeric|min:0',
          'transfer_id' => 'nullable|integer|exists:transfers,id',
      ]);

      $payload = [
          'user_id' => Auth::id(),
          'user_name' => optional(Auth::user())->name,
          'latitude' => (float) $data['latitude'],
          'longitude' => (float) $data['longitude'],
          'accuracy' => isset($data['accuracy']) ? (float) $data['accuracy'] : null,
          'transfer_id' => $data['transfer_id'] ?? null,
          'recorded_at' => now('Europe/Paris')->toDateTimeString(),
      ];

      Cache::put('driver_live_location_' . Auth::id(), $payload, now()->addMinutes(30));

      return response()->json(['success' => true]);
  }

    public function manual()
    {
        return view('other.manual');
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
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function PostActivity($id)
    {
      $file=$id;   
      $logs = LogActivity::PostActivityLists($id);
        return view('log.logActivity',compact('logs','file'));
    }
   public function logActivity()
    {
      $file=false;   
      $logs = LogActivity::logActivityLists();
        return view('log.logActivity',compact('logs','file'));
    }
   public function kaptanShow()
  {
     
    $today = now(); 
    $daybefore=Option::where('name','kaptanDayBefore')->first(); 
     $dayonce=date('Y-m-d',strtotime(date("Y-m-d", time()) . " -".$daybefore->value." day"));
     $acentes=Auth::user()->acentes()->pluck('acentes.id')->toArray();  
     $harekets=Hareket::where('tarih','>=',$dayonce)
     ->where('tarih', '<=', $today)
     ->whereIn('acente_id',$acentes)->orderby('tarih', 'asc')->get();
    return view ('driver.hesap', compact('harekets'));
  }
  public function mail()
{
  Mail::raw('Sending emails with Mailgun and Laravel is easy!', function($message)
	{
		$message->subject('Mailgun and Laravel are awesome!');
		$message->from('operation@francepanoramic.com', 'France Panoramic');
		$message->to('fatihyer@gmail.com');
	});
    return back()->with('success', 'Mail gönderildi.');
}
}
