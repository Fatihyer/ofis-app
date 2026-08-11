<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\Transfer;
use App\Models\Acente;
use App\Models\Hareket;
use App\Models\Post;
use App\Models\Mission;
use App\Models\Vehicule;
use App\Models\DriverVehicleOvernight;
use App\Models\Status;
use App\Models\Servicetype;
use App\Models\Trajet;
use App\Notifications\TransferConfirm;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Firma;
use File;
use App\Models\Option;
use Log;
use PDF;
use App\Models\Sirket;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Helpers\LogActivity;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\HareketHelper;
use App\Services\TwilioService;
use App\Services\TransferDriverAssignmentService;
use App\Services\TransferEnRouteCalculator;
use Illuminate\Support\Facades\Schema;
use DB;

class TransfersController extends Controller
{
    protected $transferids;
    protected $dispoids;
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Responses
     */
     public function __construct()
    {
        $this->middleware('auth');
        $officeTransferMethods = [
            'index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'showtransferPDF', 'showMissionPDF',
            'list', 'getTrajets', 'createWithTrajets', 'updateWithTrajets', 'ofisStart', 'ofisStartnull',
            'guncel', 'updatetable', 'clone', 'addconge', 'saveconge', 'updateClientStatus', 'smsgonder',
            'updatePlanningAssignment', 'assignPlanningFutureVehicle', 'vehicleAvailability'
        ];
        $this->middleware('role:Admin|ofis|transport')->only($officeTransferMethods);
        $this->middleware('permission:transfers.view')->only([
            'index', 'show', 'showtransferPDF', 'showMissionPDF', 'list', 'getTrajets'
        ]);
        $this->middleware('permission:transfers.create')->only([
            'create', 'store', 'createWithTrajets', 'clone', 'addconge', 'saveconge'
        ]);
        $this->middleware('permission:transfers.update')->only([
            'edit', 'update', 'updateWithTrajets', 'ofisStart', 'ofisStartnull', 'guncel', 'updatetable',
            'updateClientStatus', 'smsgonder'
        ]);
        $this->middleware('permission:transfers.delete')->only(['destroy']);
        $this->middleware('permission:transfers.operations')->only(['updatePlanningAssignment', 'assignPlanningFutureVehicle']);

    // sadece HTTP request'te çalışsın (CLI'da asla)
    if (!app()->runningInConsole()) {
        $this->initializeOption('transferid', 'transferids');
        $this->initializeOption('dispoid', 'dispoids');
    }
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





    private function secondDriverIdFromRequest(Request $request): ?int
    {
        if (!Schema::hasColumn('transfers', 'second_driver_id')) {
            return null;
        }

        $value = $request->input('second_driver_id');
        return $value ? (int) $value : null;
    }

    private function syncSecondDriverFromRequest(Transfer $transfer, Request $request, $date = null): void
    {
        app(TransferDriverAssignmentService::class)->syncSecondDriver(
            $transfer,
            $this->secondDriverIdFromRequest($request),
            Auth::id(),
            $date ?: $transfer->start_date
        );
    }




    private function importantTransferFieldsChanged(array $changes): bool
    {
        $importantFields = [
            'start_date', 'end_date', 'ofis_start', 'from', 'target', 'vehicule_id',
            'vehicle_provider_acente_id', 'external_vehicle_note', 'external_vehicle_price',
            'driver_id', 'second_driver_id', 'pax', 'comments', 'servicetype_id', 'mission'
        ];

        return count(array_intersect(array_keys($changes), $importantFields)) > 0;
    }

    private function markDriverReconfirmationRequired(Transfer $transfer, string $reason): void
    {
        $now = now();
        $userId = Auth::id();
        $primaryHadResponse = (bool) ($transfer->driver_app_confirmed_at ?? null) || (bool) ($transfer->driver_app_refused_at ?? null);
        $secondHadResponse = Schema::hasColumn('transfers', 'second_driver_id')
            && ($transfer->second_driver_id ?? null)
            && ((bool) ($transfer->second_driver_app_confirmed_at ?? null) || (bool) ($transfer->second_driver_app_refused_at ?? null));

        if ($primaryHadResponse) {
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_required_at')) {
                $transfer->driver_app_reconfirm_required_at = $now;
            }
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_required_by')) {
                $transfer->driver_app_reconfirm_required_by = $userId;
            }
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_reason')) {
                $transfer->driver_app_reconfirm_reason = $reason;
            }
            $transfer->driver_app_confirmed_at = null;
            $transfer->driver_app_confirmed_by = null;
            if (Schema::hasColumn('transfers', 'driver_app_refused_at')) {
                $transfer->driver_app_refused_at = null;
            }
            if (Schema::hasColumn('transfers', 'driver_app_refused_by')) {
                $transfer->driver_app_refused_by = null;
            }
        }

        if ($secondHadResponse) {
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_required_at')) {
                $transfer->second_driver_app_reconfirm_required_at = $now;
            }

            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_required_by')) {
                $transfer->second_driver_app_reconfirm_required_by = $userId;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_reason')) {
                $transfer->second_driver_app_reconfirm_reason = $reason;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')) {
                $transfer->second_driver_app_confirmed_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_by')) {
                $transfer->second_driver_app_confirmed_by = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_refused_at')) {
                $transfer->second_driver_app_refused_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_refused_by')) {
                $transfer->second_driver_app_refused_by = null;
            }
        }

        if ($primaryHadResponse || $secondHadResponse) {
            $transfer->save();
            LogActivity::addToLog('Driver reconfirmation required', $transfer->post_id, 'Transfer ID: ' . $transfer->id . ' - ' . $reason);
        }
    }

    private function updatePrimaryHareketAcente(Transfer $transfer, int $driverId): void
    {
        $query = $transfer->harekets();

        if (Schema::hasTable('transfer_driver_harekets')) {
            $secondHareketIds = DB::table('transfer_driver_harekets')
                ->where('transfer_id', $transfer->id)
                ->where('driver_role', 'second_driver')
                ->whereNotNull('hareket_id')
                ->pluck('hareket_id')
                ->toArray();

            if (!empty($secondHareketIds)) {
                $query->whereNotIn('id', $secondHareketIds);
            }
        }

        $query->update(['acente_id' => $driverId]);
    }

    public function index()
    {
        $vehicules = Vehicule::where('sales',NULL)->orderBy('name')->pluck('name', 'id'); // örnek

        $drivers = Acente::whereHas('firmas', function ($query) {
            $query->whereIn('firma_id', $this->transferids);
        })->orderBy('name')->pluck('name', 'id');   
        $servicetype = Servicetype::orderBy('name')->pluck('name', 'id'); // örnek

    
        return view('transfert.index', compact('vehicules', 'drivers'));
    }
    
    public function list(Request $request)
    {
        $vehicules =Vehicule::where('sales',NULL)->orderBy('name')->pluck('name', 'id'); // örnek
    
      /*  $drivers = Acente::whereHas('firmas', function ($query) {
            $query->whereIn('firma_id', $this->transferids);
        })->orderBy('name')->pluck('name', 'id');
    */
        $drivers = Acente::orderBy('name')->pluck('name', 'id');

        $query = Transfer::with(['vehicule', 'driver', 'status.color'])
        ->select('transfers.*')
        ->orderBy('start_date', 'asc'); // ð burası önemli
    
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', $request->start_date);
        }
        if ($request->filled('airportshuttle')) {
            $airportshuttle = $request->input('airportshuttle');
        
            $query->whereHas('post.acente', function ($q) use ($airportshuttle) {
                if ($airportshuttle === 'yes') {
                    $q->where('airportshuttle', true);
                } elseif ($airportshuttle === 'no') {
                    $q->where('airportshuttle', false);
                }
            });
        }
        return Datatables()->of($query)
            ->addColumn('vehicule_select', function ($row) use ($vehicules) {
                return view('transfert.partials.vehicule_select', compact('row', 'vehicules'))->render();
            })
           

            ->addColumn('id', function ($row) {
                return '<a href="' . route('transfers.show', $row->id) . '" class="btn btn-sm btn-outline-info">#' . $row->id . '</a>';
            })
            ->addColumn('date_range', function ($row) {
                $start = $row->start_date ? \Carbon\Carbon::parse($row->start_date)->format('H:i') : '-';
                $end = $row->end_date ? \Carbon\Carbon::parse($row->end_date)->format('H:i') : '-';
                $date = $row->start_date ? \Carbon\Carbon::parse($row->start_date)->format('Y-m-d') : '-';
            
                return "<div class='text-nowrap'>
                            <strong>{$date}</strong><br>
                            <small>{$start} → {$end}</small>
                        </div>";
            })
         ->addColumn('from_to', function ($row) {
                    $from = $row->from ?? '-';
                    $target = $row->target ?? '-';
                    $km = $row->km ?? '-';

                    $fromEscaped = e($from);
                    $targetEscaped = e($target);

                    $fromShort = mb_strlen($from) > 20 ? mb_substr($from, 0, 20) . '…' : $from;
                    $targetShort = mb_strlen($target) > 20 ? mb_substr($target, 0, 20) . '…' : $target;

                    return "<div class='text-nowrap'>
                                <strong data-bs-toggle='tooltip' data-placement='top' title=\"{$fromEscaped}\">{$fromShort}</strong><br>
                                <i class='fas fa-arrow-down'></i><br>
                                <strong data-bs-toggle='tooltip' data-placement='bottom' title=\"{$targetEscaped}\">{$targetShort}</strong><br>
                                <small class='text-muted'>{$km} km</small>
                                <button class='btn btn-sm btn-outline-info show-trajets ml-2' title='Güzergah Detayı' data-id='{$row->id}'>
                                    <i class='fas fa-route'></i>
                                </button>
                            </div>";
                })


            ->addColumn('acente_name', function ($row) {
                $acente = optional($row->post->acente);
                $color = $acente->color ?? '#6c757d'; // fallback: Bootstrap secondary rengi
                $name = $acente->name ?? '-';

                return "<span class='badge' style='background-color: {$color}; color: #fff;'>{$name}</span>";
            })
            ->addColumn('post_id', function ($row) {
                return '<a href="' . route('posts.show', $row->post_id) . '" class="btn btn-sm btn-outline-secondary">#' . $row->post_id . '</a>';
            })
            ->addColumn('driver_select', function ($row) use ($drivers) {
                return view('transfert.partials.driver_select', compact('row', 'drivers'))->render();
            })
            ->addColumn('ofis_start_input', function ($row) {
                return '<input type="datetime-local" class="form-control form-control-sm update-ofis-start" data-id="'.$row->id.'" value="'.($row->ofis_start ? \Carbon\Carbon::parse($row->ofis_start)->format('Y-m-d\TH:i') : '').'">';
            })

          
           ->addColumn('service_type_name', function ($row) {
                    $name = optional($row->servicetype)->name ?? '-';
                    $color = optional($row->servicetype->color)->name ?? 'secondary'; // fallback renk

                    return "<span class='badge badge-{$color}'>{$name}</span>";
                })
            ->setRowClass(function ($row) {
                if ($row->status && $row->status->color) {
                    return 'table-' . $row->status->color->name;
                }
                return '';
            })
            ->rawColumns(['id','vehicule_select', 'driver_select', 'ofis_start_input', 'date_range', 'from_to', 'service_type_name','acente_name', 'post_id'])
            ->make(true);
    }
    
    public function updatetable(Request $request)
            {
                $request->validate([
                    'id' => 'required|integer|exists:transfers,id',
                    'field' => 'required|string|in:vehicule_id,driver_id,ofis_start',
                    'value' => 'required'
                ]);
            
                $transfer = Transfer::findOrFail($request->id); // << BU SATIRI ÜSTE AL
            
                if ($request->field === 'ofis_start' && strtotime($request->value) > strtotime($transfer->start_date)) {
                    return response()->json(['success' => false, 'message' => 'Ofis çıkışı transfer başlangıç saatinden önce olmalı!'], 422);
                }

                if ($request->field === 'vehicule_id' && $this->vehicleChangeBlocked($transfer, $request->value)) {
                    return response()->json(['success' => false, 'message' => 'Araç bloke edildi, değiştirilemez.'], 422);
                }
            
                $transfer->{$request->field} = $request->value;
                $transfer->save();
                LogActivity::addToLog($request->field , $transfer->post_id,'Updated');
                return response()->json(['success' => true, 'message' => 'Güncelleme başarılı']);
            }
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

     private function vehicleLockEnabled(): bool
     {
         return Schema::hasColumn('transfers', 'vehicle_locked');
     }

     private function vehicleIsLocked(Transfer $transfer): bool
     {
         return $this->vehicleLockEnabled() && (bool) $transfer->vehicle_locked;
     }

     private function vehicleChangeBlocked(Transfer $transfer, $newVehicleId): bool
     {
         return $this->vehicleIsLocked($transfer)
             && (int) $transfer->vehicule_id !== (int) $newVehicleId
             && !auth()->user()?->hasRole('Superadmin');
     }

     private function applyVehicleLockFromRequest(Transfer $transfer, Request $request): void
     {
         if (! $this->vehicleLockEnabled() || ! auth()->user()?->hasRole('Superadmin') || ! $request->has('vehicle_locked')) {
             return;
         }

         $locked = $request->boolean('vehicle_locked');
         if ((bool) $transfer->vehicle_locked !== $locked) {
             $transfer->vehicle_locked_at = $locked ? now() : null;
             $transfer->vehicle_locked_by = $locked ? auth()->id() : null;
         }

         $transfer->vehicle_locked = $locked;
     }

     public function getTrajets($id)
     {
         $transfer = Transfer::with(['trajets' => function ($q) {
             $q->orderBy('order');
         }])->findOrFail($id);
     
         $rows = $transfer->trajets;
     
         $origin = urlencode(optional($rows->first())->google_address);
         $destination = urlencode(optional($rows->last())->google_address);
         $waypoints = $rows->slice(1, -1)
    ->pluck('google_address')
    ->filter()
    ->map(function ($address) {
        return urlencode($address);
    })
    ->implode('|');
     
         return view('transfert.partials.trajets-modal', compact('transfer', 'rows', 'origin', 'destination', 'waypoints'));
     }
     
    public function create()
    {
    
    }
    public function createWithTrajets($postId)
{
    $post = Post::findOrFail($postId); // İlgili postu bul
    $post->start_date = Carbon::parse($post->start_date);
    $servicetype=Servicetype::orderBy('name')->pluck('name','id');
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
    $firmas = Firma::orderBy('name')->pluck('name', 'id');
    $acentes = Acente::orderBy('name')->pluck('name', 'id');
    $defaultDepotAddress = app(TransferEnRouteCalculator::class)->defaultDepotAddress();

    return view('transfert.newcreate', compact('post','servicetype','vehicules','firmas','acentes','defaultDepotAddress'));
}
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the request data
       // Validate the request data
    $this->validate($request, [
        'start_date' => 'required|date',
        'post_id' => 'required',
        'servicetype_id' => 'required',
        'pax' => 'required|integer',
        'km' => 'required|integer',
        'vehicule_id' => 'required|integer',
        'vehicle_provider_acente_id' => 'nullable|integer|exists:acentes,id',
        'external_vehicle_note' => 'nullable|string|max:255',
        'external_vehicle_price' => 'nullable|numeric|min:0',
        'vehicle_locked' => 'nullable|boolean',
        'ofis_date' => 'nullable|date',
        'ofis_time' => 'nullable|date_format:H:i',
        'driver_id' => 'required|integer',
        'second_driver_id' => 'nullable|integer|different:driver_id',
        'trajets.*.type' => 'required|string',
        'trajets.*.from' => 'required|string',
        'trajets.*.google_address' => 'nullable|string|max:255',
        'trajets.*.datetime' => [
            'required',
            'date',
            function ($attribute, $value, $fail) {
                $isPastDate = strtotime($value) < strtotime('today');
                if ($isPastDate && !auth()->user()->hasRole('Superadmin')) {
                    $fail('Only superadmins can modify past dates.');
                }
            },
        ],
        'trajets.*.order' => 'required|integer',
    ]);
    $trajets = $request->trajets;

    if (empty($trajets) || !is_array($trajets)) {
        return redirect()->back()->withErrors(['trajets' => 'At least one trajet is required.'])->withInput();
    }
    usort($trajets, function ($a, $b) {
        return strtotime($a['datetime']) <=> strtotime($b['datetime']);
    });

    // Determine start_date and end_date excluding Depots
    $startDate = null;
    foreach ($trajets as $trajet) {
        if ($trajet['type'] !== 'depot') {
            $startDate = $trajet['datetime'];
            break;
        }
    }

    $endDate = null;
    foreach (array_reverse($trajets) as $trajet) {
        if ($trajet['type'] !== 'depot') {
            $endDate = $trajet['datetime'];
            break;
        }
    }
     // Determine the "from" location
     $firstFrom = null;
     foreach ($trajets as $trajet) {
         if ($trajet['type'] !== 'depot') {
             $firstFrom = $trajet['from'];
             break;
         }
     }
 
     // Determine the "target" location
     $lastTarget = null;
     foreach (array_reverse($trajets) as $trajet) {
         if ($trajet['type'] !== 'depot') {
             $lastTarget = $trajet['from'];
             break;
         }
     }
     $firstDepotDatetime = null;
     foreach ($trajets as $trajet) {
        if ($trajet['type'] === 'depot') {
            $firstDepotDatetime = $trajet['datetime'];
            break;
        }
    }
    // Create the Transfer
    $transfer = new Transfer;
    $transfer->start_date = $startDate; // Start date excluding Depot
    $transfer->end_date = $endDate; // End date excluding Depot
    $transfer->servicetype_id = $request->servicetype_id;
    $transfer->from = $firstFrom; // Start location excluding Depot
    $transfer->target = $lastTarget; // End location excluding Depot
    $transfer->pax = $request->pax;
    $transfer->comments = $request->comments;
    $transfer->vehicule_id = $request->vehicule_id;
    $transfer->vehicle_provider_acente_id = $request->vehicle_provider_acente_id ?: null;
    $transfer->external_vehicle_note = $request->external_vehicle_note;
    $transfer->external_vehicle_price = $request->external_vehicle_price;
    $transfer->driver_id = $request->driver_id;
    $transfer->km = $request->km;
    $transfer->post_id = $request->post_id;
    $transfer->mission = $request->mission ? $request->mission : 0;
    $transfer->accueil = $request->accueil ? $request->accueil : 0;
    $transfer->status_id = 2;
    $selectedDepotId = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'depot_id')
        ? ($request->input('depot_id') ?: optional(app(\App\Services\DepotResolver::class)->depotForVehicle((int) $request->vehicule_id))->id)
        : null;
    $plannedEnRoute = app(TransferEnRouteCalculator::class)->calculate(
        $trajets,
        (int) $request->servicetype_id,
        (int) $request->vehicule_id,
        (int) $request->driver_id,
        $selectedDepotId ? (int) $selectedDepotId : null
    );
    $transfer->ofis_start = $plannedEnRoute ? $plannedEnRoute->toDateTimeString() : $firstDepotDatetime;
    if (\Illuminate\Support\Facades\Schema::hasColumn('transfers', 'depot_id')) {
        $transfer->depot_id = $selectedDepotId ?: null;
        $transfer->depot_source = $request->filled('depot_id') ? 'manual' : ($selectedDepotId ? 'vehicule' : null);
    }
    $transfer->save();
    $this->syncSecondDriverFromRequest($transfer, $request, $startDate);

    // Add trajets in chronological order. Form order can be stale when a depot is added later.
    foreach ($trajets as $index => $trajetData) {
        $trajet = new Trajet();
        $trajet->transfer_id = $transfer->id;
        $trajet->type = $trajetData['type'];
        $trajet->from = $trajetData['from'];
        $trajet->google_address = $trajetData['google_address'] ?? null;
        $trajet->datetime = $trajetData['datetime'];
        $trajet->order = $index + 1;
        $trajet->save();
    }

   
    
        // Create a related hareket record
        HareketHelper::create($transfer, [

    'aciklama'  => 'Transfer created',
    'tarih'     => $startDate,
    'post_id'   => $request->post_id,
    'amount'    => 0,
    'ab'        => 2,
    'kur_id'    => 1,
    'acente_id' => $request->driver_id,
]);
    
        LogActivity::addToLog('TransferCreate.', $transfer->post_id,'Created');
    
      


   // return redirect()->back()->with('flash_message', 'Transfer and trajets added successfully: ' . $transfer->id);
  return redirect()->route('posts.show',$transfer->post_id)->with('flash_message', 'Post related to the transfer has been accessed.');    
      }
    public function updateWithTrajets(Request $request, Transfer $transfer)

    {
         // Validate the request data
    $this->validate($request, [
       
        'post_id' => 'required',
        'servicetype_id' => 'required',
        'pax' => 'required|integer',
        'km' => 'required|integer',
        'vehicule_id' => 'required|integer',
        'vehicle_provider_acente_id' => 'nullable|integer|exists:acentes,id',
        'external_vehicle_note' => 'nullable|string|max:255',
        'external_vehicle_price' => 'nullable|numeric|min:0',
        'vehicle_locked' => 'nullable|boolean',
        'driver_id' => 'required|integer',
        'second_driver_id' => 'nullable|integer|different:driver_id',
        'trajets.*.type' => 'required|string',
        'trajets.*.from' => 'required|string',
        'trajets.*.google_address' => 'nullable|string|max:255',
        'trajets.*.datetime' => [
            'required',
            'date',
            function ($attribute, $value, $fail) {
                $twoDaysAgo = strtotime('-2 days');
                $inputDate = strtotime($value);
            
                if ($inputDate < $twoDaysAgo && !auth()->user()->hasRole('Superadmin')) {
                    $fail('Only superadmins can modify dates older than 2 days.');
                }
            },
            
        ],
        'trajets.*.order' => 'required|integer',
    ]);

    $trajets = $request->trajets;

    if (empty($trajets) || !is_array($trajets)) {
        return redirect()->back()->withErrors(['trajets' => 'At least one trajet is required.'])->withInput();
    }

    // Sort trajets by datetime
    usort($trajets, function ($a, $b) {
        return strtotime($a['datetime']) <=> strtotime($b['datetime']);
    });

    // Determine start_date and end_date excluding Depots
    $startDate = null;
    foreach ($trajets as $trajet) {
        if ($trajet['type'] !== 'depot') {
            $startDate = $trajet['datetime'];
            break;
        }
    }

    $endDate = null;
    foreach (array_reverse($trajets) as $trajet) {
        if ($trajet['type'] !== 'depot') {
            $endDate = $trajet['datetime'];
            break;
        }
    }

    // Determine the "from" location
    $firstFrom = null;
    foreach ($trajets as $trajet) {
        if ($trajet['type'] !== 'depot') {
            $firstFrom = $trajet['from'];
            break;
        }
    }

    // Determine the "target" location
    $lastTarget = null;
    foreach (array_reverse($trajets) as $trajet) {
        if ($trajet['type'] !== 'depot') {
            $lastTarget = $trajet['from'];
            break;
        }
    }

    $firstDepotDatetime = null;
    foreach ($trajets as $trajet) {
        if ($trajet['type'] === 'depot') {
            $firstDepotDatetime = $trajet['datetime'];
            break;
        }
    }

    if (strtotime($transfer->start_date) < strtotime('yesterday')) {
        // Kullanıcının role kontrolü
        if (!auth()->user()->hasRole('Superadmin')) {
            return response()->json([
                'error' => 'Only superadmins can update transfers with past start dates.'
            ], 403);
        }
    }

    if ($this->vehicleChangeBlocked($transfer, $request->vehicule_id)) {
        return redirect()->back()->withErrors('Araç bloke edildi, değiştirilemez.')->withInput();
    }

    // En route automatique: uniquement si le champ est vide.
    // Une valeur existante (saisie manuellement ou calculee a la creation) ne doit jamais etre ecrasee pendant une modification.
    $shouldCalculateEnRoute = empty($transfer->ofis_start);
    $selectedDepotId = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'depot_id')
        ? ($request->input('depot_id') ?: optional(app(\App\Services\DepotResolver::class)->depotForVehicle((int) $request->vehicule_id))->id ?: $transfer->depot_id)
        : null;
    $plannedEnRoute = $shouldCalculateEnRoute
        ? app(TransferEnRouteCalculator::class)->calculate(
            $trajets,
            (int) $request->servicetype_id,
            (int) $request->vehicule_id,
            (int) $request->driver_id,
            $selectedDepotId ? (int) $selectedDepotId : null
        )
        : null;

    // Update the Transfer
    $transfer->start_date = $startDate; // Start date excluding Depot
    $transfer->end_date = $endDate; // End date excluding Depot
    $transfer->servicetype_id = $request->servicetype_id;
    $transfer->from = $firstFrom; // Start location excluding Depot
    $transfer->target = $lastTarget; // End location excluding Depot
    $transfer->pax = $request->pax;
    $transfer->comments = $request->comments;
    $transfer->vehicule_id = $request->vehicule_id;
    $transfer->vehicle_provider_acente_id = $request->vehicle_provider_acente_id ?: null;
    $transfer->external_vehicle_note = $request->external_vehicle_note;
    $transfer->external_vehicle_price = $request->external_vehicle_price;
    $transfer->driver_id = $request->driver_id;
    $transfer->km = $request->km;
    $transfer->post_id = $request->post_id;
    $transfer->mission = $request->mission ? $request->mission : 0;
    $transfer->accueil = $request->accueil ? $request->accueil : 0;
    if (\Illuminate\Support\Facades\Schema::hasColumn('transfers', 'depot_id')) {
        $transfer->depot_id = $selectedDepotId ?: null;
        $transfer->depot_source = $request->filled('depot_id') ? 'manual' : ($selectedDepotId ? 'vehicule' : null);
    }
    if ($shouldCalculateEnRoute) {
        $transfer->ofis_start = $plannedEnRoute ? $plannedEnRoute->toDateTimeString() : $firstDepotDatetime;
    }
    $this->applyVehicleLockFromRequest($transfer, $request);
    $transfer->status_id = 2;
    $transfer->save();
    if ($this->importantTransferFieldsChanged($transfer->getChanges())) {
        $this->markDriverReconfirmationRequired($transfer, 'Service modifié après confirmation');
    }
    $this->syncSecondDriverFromRequest($transfer, $request, $startDate);

    // Update or Create Trajets
    $existingTrajets = $transfer->trajets()->get();
    $existingTrajetIds = $existingTrajets->pluck('id')->toArray();

    $updatedTrajetIds = [];
    foreach ($trajets as $index=>$trajetData) {
        if (isset($trajetData['id']) && in_array($trajetData['id'], $existingTrajetIds)) {
            // Update existing trajet
            $trajet = Trajet::find($trajetData['id']);
            $trajet->type = $trajetData['type'];
            $trajet->from = $trajetData['from'];
            $trajet->google_address = $trajetData['google_address'] ?? null;
            $trajet->datetime = $trajetData['datetime'];
            $trajet->order = $index + 1;
            $trajet->save();
            $updatedTrajetIds[] = $trajet->id;
        } else {
            // Create new trajet
            $trajet = new Trajet();
            $trajet->transfer_id = $transfer->id;
            $trajet->type = $trajetData['type'];
            $trajet->from = $trajetData['from'];
            $trajet->google_address = $trajetData['google_address'] ?? null;
            $trajet->datetime = $trajetData['datetime'];
            $trajet->order = $index + 1; 
            $trajet->save();
            $updatedTrajetIds[] = $trajet->id;
        }
    }

    // Delete removed trajets
    $trajetsToDelete = array_diff($existingTrajetIds, $updatedTrajetIds);
    Trajet::whereIn('id', $trajetsToDelete)->delete();
    $hareket = Transfer::find($transfer->id);
   $mainHareket = $transfer->harekets()->first();

if ($mainHareket) {
    $mainHareket->update([
        'tarih'     => $startDate,
        'acente_id' => $request->driver_id,
    ]);
}


    LogActivity::addToLog('TransferUpdated.', $transfer->post_id, 'Updated');

  // return redirect()->back()->with('flash_message', 'Transfer and trajets updated successfully: ' . $transfer->id);
   return redirect()->route('posts.show',$transfer->post_id)->with('flash_message', 'Post related to the transfer has been updated.');    

    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Transfer  $transfer
     * @return \Illuminate\Http\Response
     */
    public function clone($id)
{
    if (request()->getHost() === 'ofis2025.parisvia.com') {
    abort(403, '2025 ortamında clone kapalı');
}

    // Find the transfer to be cloned
    $originalTransfer = Transfer::with('trajets')->find($id);

    if (!$originalTransfer) {
        return redirect()->back()->with('error_message', 'Transfer not found.');
    }
    if ($originalTransfer->conge) {
        return redirect()->back()->with('error_message', 'Ce transfert est un congé /evenementet ne peut pas être cloné');
    }

    // Clone the transfer
    $clone = $originalTransfer->replicate();
    $clone->called_at = null;
    if (Schema::hasColumn('transfers', 'second_driver_id')) {
        $clone->second_driver_id = null;
    }
    if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')) {
        $clone->second_driver_app_confirmed_at = null;
    }
    if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_by')) {
        $clone->second_driver_app_confirmed_by = null;
    }
    if (Schema::hasColumn('transfers', 'driver_app_refused_at')) {
        $clone->driver_app_refused_at = null;
    }
    if (Schema::hasColumn('transfers', 'driver_app_refused_by')) {
        $clone->driver_app_refused_by = null;
    }
    if (Schema::hasColumn('transfers', 'second_driver_app_refused_at')) {
        $clone->second_driver_app_refused_at = null;
    }
    if (Schema::hasColumn('transfers', 'second_driver_app_refused_by')) {
        $clone->second_driver_app_refused_by = null;
    }
    $clone->deleted_at = null;
    // Generate a unique mission URL
     do {
        $newToken = Str::uuid()->toString();
    } while (Transfer::where('confirmation_token', $newToken)->exists());

    $clone->confirmation_token = $newToken;
    $shortCode = Str::random(8);
    while (Transfer::where('mission_url', $shortCode)->exists()) {
        $shortCode = Str::random(8);
    }

    $clone->mission_url = $shortCode;
    $clone->status_id = 2; // Set the cloned transfer's status to 'Pending' or similar
    $clone->save();

    // Clone associated trajets
    foreach ($originalTransfer->trajets as $trajet) {
        $clonedTrajet = $trajet->replicate();
        $clonedTrajet->transfer_id = $clone->id;
        $clonedTrajet->save();
    }

    // Create a new hareket for the cloned transfer
   HareketHelper::create($clone, [
    'aciklama'  => 'Transfer cloned',
    'tarih'     => $clone->start_date,
    'post_id'   => $clone->post_id,
    'amount'    => 0,
    'ab'        => 2,
    'kur_id'    => 1,
    'acente_id' => $clone->driver_id,
]);

    // Log the cloning activity
    LogActivity::addToLog('TransferClone.', $clone->post_id, 'Cloned transfer with ID: ' . $clone->id);

    return redirect()->back()->with('flash_message', 'Transfer cloned successfully: ' . $clone->id);
}

  
    public function show($id)
    {
      $transfers=Transfer::findOrFail($id);
      $status=Status::pluck('name','id');
      $overnightDates = [
          Carbon::parse($transfers->start_date)->copy()->subDay()->toDateString(),
          Carbon::parse($transfers->start_date)->toDateString(),
      ];
      $overnights = Schema::hasTable('driver_vehicle_overnights')
          ? DriverVehicleOvernight::with(['driver', 'vehicule'])
              ->whereNull('deleted_at')
              ->where(function ($query) use ($transfers, $overnightDates) {
                  $query->where('transfer_id', $transfers->id);

                  if ($transfers->driver_id) {
                      $query->orWhere(function ($subQuery) use ($transfers, $overnightDates) {
                          $subQuery->where('driver_id', $transfers->driver_id)
                              ->whereIn('overnight_date', $overnightDates);

                          if ($transfers->vehicule_id) {
                              $subQuery->where(function ($vehicleQuery) use ($transfers) {
                                  $vehicleQuery->where('vehicule_id', $transfers->vehicule_id)
                                      ->orWhereNull('vehicule_id');
                              });
                          }
                      });
                  }
              })
              ->orderBy('overnight_date')
              ->orderByDesc('id')
              ->get()
          : collect();
      if (file_exists(public_path('voucher'.'/'.$id)))
      {$files =File::allFiles(public_path('voucher'.'/'.$id)); }
      else {$files=array();}
      return view ('transfert.show', compact('transfers','status','files','overnights'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Transfer  $transfer
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        
      
        $transfer = Transfer::with('trajets')->findOrFail($id);
        $transfer->start_date = $transfer->start_date ? Carbon::parse($transfer->start_date) : null;
        $transfer->end_date = $transfer->end_date ? Carbon::parse($transfer->end_date) : null;
        // Retrieve necessary data for dropdowns, if any
        $serviceTypes = ServiceType::orderBy('name')->pluck('name', 'id'); // Example service type data
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
        $firmas = Firma::orderBy('name')->pluck('name', 'id');
        $acentes = Acente::orderBy('name')->pluck('name', 'id');
        $defaultDepotAddress = app(TransferEnRouteCalculator::class)->defaultDepotAddress();
        
        return view('transfert.newedit', compact('transfer', 'serviceTypes', 'vehicules', 'firmas', 'acentes', 'defaultDepotAddress'));
    }

    /** 
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Transfer  $transfer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$transfer)
    {
    $confirm = Transfer::findOrFail($transfer);
     // echo $request->status_id;
    if ($confirm->driver_id)
        {
          $confirm->status_id=$request->status_id;
           $confirm->dcomments=$request->dcomments;
         $confirm->save();
           LogActivity::addToLog('File Status Changed ('.$confirm->status_id.') or driver comment added',$confirm->post_id,'konfirmed'); 
         
       $adminuser=User::whereHas('roles', function ($q)  {
        $q->where('name', 'admin');
        })->get();
        foreach ($adminuser as $user)
         {
          $user->notify(new TransferConfirm($confirm));
          }
        return redirect()->back()->with('flash_message','The transfer is confirmed', $transfer);
        
        }
        else
        {return redirect()->back()->with('flash_message','PLEASE SELECT DRİVER OR PROVİDER');
         }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Transfer  $transfer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transfer $transfer)
    {
        if (strtotime($transfer->start_date) < strtotime('yesterday')) {
            // Kullanıcının role kontrolü
            if (!auth()->user()->hasRole('Superadmin')) {
                return response()->json([
                    'error' => 'Only superadmins can delete transfers with past start dates.'
                ], 403);
            }
        }
       
       
        if (!Auth::user()->hasPermissionTo('Administer roles & permissions')) {
            return redirect()->back()->with('flash_message', 'ONLY ADMIN CAN DELETE A TRANSFER');
        }
        // Check if the transfer has an associated mission
        if (Mission::where('transfer_id', $transfer->id)->exists()) {
            return redirect()->back()->with('flash_message', 'THE TRANSFER HAS A MISSION');
        }
    
        // Retrieve the related harekets
        $hareket = $transfer->harekets()->first();
    
        // Check if hareket exists and has an amount or related payment with cari > 0
        if ($hareket && ($hareket->amount>0 || ($hareket->payment && $hareket->payment->cari > 0))) {
            return redirect()->back()->with('flash_message', 'THE TRANSFER HAS AN AMOUNT, PLEASE DELETE IT BEFORE DELETING THE TRANSFER');
        }
    
        // Delete the hareket and the transfer
        if ($hareket) {
            $hareket->delete();
        }
        $transfer->trajets()->delete();
        LogActivity::addToLog('TransferDeleted: (' . $transfer->start_date . ')', $transfer->post_id, 'ID was deleted: ' . $transfer->id);
        $transfer->delete();
    
        return redirect()->back()->with('flash_message', 'THE TRANSFER WAS DELETED');
    }
    

    public function vehicleAvailability(Request $request)
    {
        $request->validate([
            'vehicule_id' => 'required|integer|exists:vehicules,id',
            'start' => 'required|date',
            'end' => 'required|date',
            'transfer_id' => 'nullable|integer',
        ]);

        $start = Carbon::parse($request->input('start'));
        $end = Carbon::parse($request->input('end'));

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addHours(2);
        }

        $vehicule = Vehicule::findOrFail((int) $request->input('vehicule_id'));
        $currentTransferId = (int) $request->input('transfer_id');

        $conflicts = Transfer::with([
                'post:id,title,acente_id',
                'post.acente:id,name',
                'driver:id,name',
                'status:id,name',
            ])
            ->where('vehicule_id', $vehicule->id)
            ->when($currentTransferId > 0, function ($query) use ($currentTransferId) {
                $query->where('id', '!=', $currentTransferId);
            })
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<', $end->toDateTimeString())
                    ->where('end_date', '>', $start->toDateTimeString());
            })
            ->whereDoesntHave('status', function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%annul%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%cancel%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%iptal%']);
            })
            ->orderBy('start_date')
            ->limit(8)
            ->get();

        return response()->json([
            'available' => $conflicts->isEmpty(),
            'vehicule' => $vehicule->name,
            'period' => [
                'start' => $start->format('d/m/Y H:i'),
                'end' => $end->format('d/m/Y H:i'),
            ],
            'conflicts' => $conflicts->map(function (Transfer $transfer) {
                return [
                    'id' => $transfer->id,
                    'post_id' => $transfer->post_id,
                    'post_title' => optional($transfer->post)->title,
                    'agency' => optional(optional($transfer->post)->acente)->name,
                    'driver' => optional($transfer->driver)->name,
                    'status' => optional($transfer->status)->name,
                    'start' => optional($transfer->start_date)->format('d/m/Y H:i'),
                    'end' => optional($transfer->end_date)->format('d/m/Y H:i'),
                    'url' => route('transfers.show', $transfer->id),
                ];
            })->values(),
        ]);
    }

   public function guncel(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:transfers,id',
        'servicetype_id' => 'required',
        'pax' => 'required|integer',
        'vehicule_id' => 'required|integer',
        'vehicle_provider_acente_id' => 'nullable|integer|exists:acentes,id',
        'external_vehicle_note' => 'nullable|string|max:255',
        'external_vehicle_price' => 'nullable|numeric|min:0',
        'vehicle_locked' => 'nullable|boolean',
        'ofis_date' => 'nullable|date',
        'ofis_time' => 'nullable|date_format:H:i',
        'driver_id' => 'required|integer',
        'second_driver_id' => 'nullable|integer|different:driver_id',
    ]);

    $transfer = Transfer::findOrFail($request->id);

    // ð Geçmiş tarih güvenliği
    if (strtotime($transfer->start_date) < strtotime('yesterday')
        && !auth()->user()->hasRole('Superadmin')) {
        abort(403, 'Past transfer cannot be updated');
    }

    // ð Congé kilidi
    if ($transfer->conge) {
        return back()->withErrors('Congé transfer cannot be modified');
    }

    $original = $transfer->getOriginal();

    $ofisStartValue = $transfer->ofis_start;
    if ($request->has('ofis_date') || $request->has('ofis_time')) {
        $hasOfisDate = filled($request->ofis_date);
        $hasOfisTime = filled($request->ofis_time);

        if ($hasOfisDate !== $hasOfisTime) {
            return back()->withErrors('En route date et heure doivent être renseignées ensemble.')->withInput();
        }

        $ofisStartValue = null;
        if ($hasOfisDate && $hasOfisTime) {
            $ofisStartValue = Carbon::parse($request->ofis_date . ' ' . $request->ofis_time)->format('Y-m-d H:i:s');

            if (strtotime($ofisStartValue) > strtotime($transfer->start_date)) {
                return back()->withErrors("En route doit être avant l'heure de départ du transfert.")->withInput();
            }
        }
    }

    if ($this->vehicleChangeBlocked($transfer, $request->vehicule_id)) {
        return back()->withErrors('Araç bloke edildi, değiştirilemez.')->withInput();
    }

    $this->applyVehicleLockFromRequest($transfer, $request);

    // ❗ status_id'ye DOKUNMUYORUZ
    $transfer->update([
        'servicetype_id' => $request->servicetype_id,
        'pax' => $request->pax,
        'comments' => $request->comments,
        'ofis_start' => $ofisStartValue,
        'vehicule_id' => $request->vehicule_id,
        'vehicle_provider_acente_id' => $request->vehicle_provider_acente_id ?: null,
        'external_vehicle_note' => $request->external_vehicle_note,
        'external_vehicle_price' => $request->external_vehicle_price,
        'driver_id' => $request->driver_id,
        'mission' => $request->mission ?? 0,
    ]);
    if ($this->importantTransferFieldsChanged($transfer->getChanges())) {
        $this->markDriverReconfirmationRequired($transfer, 'Service modifié après confirmation');
    }
    $this->syncSecondDriverFromRequest($transfer, $request, $transfer->start_date);

    // ð Transfer hareketini güncelle (sadece acente)
    $this->updatePrimaryHareketAcente($transfer, (int) $request->driver_id);

    LogActivity::addToLog(
        'Transfer edited',
        $transfer->post_id,
        json_encode(array_diff_assoc($transfer->getAttributes(), $original))
    );

    return back()->with('flash_message', 'Transfer updated');
}



    private function resolveDriverAppActor(Transfer $transfer): array
    {
        $user = Auth::user();
        $isOffice = $user && ($user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || $user->hasAnyPermission(['transfers.operations', 'ofis', 'transport']));
        $linkedAcentes = $user ? $user->acentes()->pluck('acentes.id')->toArray() : [];

        $linkedAcentes = array_map('intval', $linkedAcentes);
        $isPrimaryDriver = in_array((int) $transfer->driver_id, $linkedAcentes, true);
        $isSecondDriver = Schema::hasColumn('transfers', 'second_driver_id') && in_array((int) ($transfer->second_driver_id ?? 0), $linkedAcentes, true);

        if (!$isOffice && !$isPrimaryDriver && !$isSecondDriver) {
            abort(403, 'Ce transfert ne vous est pas attribué.');
        }

        return [$user, $isPrimaryDriver, $isSecondDriver];
    }

    public function driverAppConfirm(Request $request, Transfer $transfer)
    {
        [$user, $isPrimaryDriver, $isSecondDriver] = $this->resolveDriverAppActor($transfer);
        $isOffice = $user && ($user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || $user->hasAnyPermission(['transfers.operations', 'ofis', 'transport']));
        $requestedDriverRole = $request->input('driver_role');

        if ($isOffice && $requestedDriverRole === 'second_driver' && Schema::hasColumn('transfers', 'second_driver_id') && ($transfer->second_driver_id ?? null)) {
            $isPrimaryDriver = false;
            $isSecondDriver = true;
        } elseif ($isOffice && $requestedDriverRole === 'primary_driver') {
            $isPrimaryDriver = true;
            $isSecondDriver = false;
        }

        if ($isSecondDriver && !$isPrimaryDriver && Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')) {
            $transfer->second_driver_app_confirmed_at = now();
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_by')) {
                $transfer->second_driver_app_confirmed_by = $user?->id;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_refused_at')) {
                $transfer->second_driver_app_refused_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_refused_by')) {
                $transfer->second_driver_app_refused_by = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_required_at')) {
                $transfer->second_driver_app_reconfirm_required_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_required_by')) {
                $transfer->second_driver_app_reconfirm_required_by = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_reason')) {
                $transfer->second_driver_app_reconfirm_reason = null;
            }
        } else {
            $transfer->driver_app_confirmed_at = now();
            $transfer->driver_app_confirmed_by = $user?->id;
            if (Schema::hasColumn('transfers', 'driver_app_refused_at')) {
                $transfer->driver_app_refused_at = null;
            }
            if (Schema::hasColumn('transfers', 'driver_app_refused_by')) {
                $transfer->driver_app_refused_by = null;
            }
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_required_at')) {
                $transfer->driver_app_reconfirm_required_at = null;
            }
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_required_by')) {
                $transfer->driver_app_reconfirm_required_by = null;
            }
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_reason')) {
                $transfer->driver_app_reconfirm_reason = null;
            }
        }
        $transfer->save();

        $confirmedRoleLabel = ($isSecondDriver && !$isPrimaryDriver) ? '2e chauffeur' : 'chauffeur principal';
        LogActivity::addToLog('Driver app confirmed', $transfer->post_id, 'Transfer ID: ' . $transfer->id . ' - ' . $confirmedRoleLabel . ($isOffice ? ' confirmé par office/admin' : ''));

        return redirect()->back()->with('flash_message', 'Service confirmé pour le ' . $confirmedRoleLabel . '.');
    }

    public function driverAppRefuse(Request $request, Transfer $transfer)
    {
        [$user, $isPrimaryDriver, $isSecondDriver] = $this->resolveDriverAppActor($transfer);

        $request->validate([
            'driver_refusal_reason' => 'nullable|string|max:500',
        ]);

        $reason = trim((string) $request->input('driver_refusal_reason', ''));

        if ($isSecondDriver && !$isPrimaryDriver && Schema::hasColumn('transfers', 'second_driver_app_refused_at')) {
            $transfer->second_driver_app_refused_at = now();
            if (Schema::hasColumn('transfers', 'second_driver_app_refused_by')) {
                $transfer->second_driver_app_refused_by = $user?->id;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')) {
                $transfer->second_driver_app_confirmed_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_by')) {
                $transfer->second_driver_app_confirmed_by = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_required_at')) {
                $transfer->second_driver_app_reconfirm_required_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_required_by')) {
                $transfer->second_driver_app_reconfirm_required_by = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_reason')) {
                $transfer->second_driver_app_reconfirm_reason = null;
            }
        } else {
            if (!Schema::hasColumn('transfers', 'driver_app_refused_at')) {
                return redirect()->back()->with('flash_message', 'La colonne de refus chauffeur manque dans la base.');
            }
            $transfer->driver_app_refused_at = now();
            if (Schema::hasColumn('transfers', 'driver_app_refused_by')) {
                $transfer->driver_app_refused_by = $user?->id;
            }
            $transfer->driver_app_confirmed_at = null;
            $transfer->driver_app_confirmed_by = null;
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_required_at')) {
                $transfer->driver_app_reconfirm_required_at = null;
            }
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_required_by')) {
                $transfer->driver_app_reconfirm_required_by = null;
            }
            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_reason')) {
                $transfer->driver_app_reconfirm_reason = null;
            }
        }

        $transfer->save();

        $logDetail = 'Transfer ID: ' . $transfer->id;
        if ($reason !== '') {
            $logDetail .= ' - Motif: ' . $reason;
        }
        LogActivity::addToLog('Driver app refused', $transfer->post_id, $logDetail);

        return redirect()->back()->with('flash_message', 'Service refusé par le chauffeur.');
    }

    public function updatePlanningAssignment(Request $request)
    {
        $request->validate([
            'transfer_id' => 'required|integer|exists:transfers,id',
            'driver_id' => 'required|integer|exists:acentes,id',
            'second_driver_id' => 'nullable|integer|exists:acentes,id|different:driver_id',
            'vehicule_id' => 'nullable|integer|exists:vehicules,id',
            'planning_date' => 'nullable|date',
            'depot_id' => 'nullable|integer|exists:depots,id',
        ]);

        $transfer = Transfer::findOrFail($request->transfer_id);

        if (strtotime($transfer->start_date) < strtotime('yesterday') && !auth()->user()->hasRole('Superadmin')) {
            abort(403, 'Past transfer cannot be updated');
        }

        if ($transfer->conge) {
            return redirect()->back()->withErrors('Congé transfer cannot be modified');
        }

        $oldDriverId = $transfer->driver_id;
        $oldDriverName = optional($transfer->driver)->name;
        $oldVehicleId = $transfer->vehicule_id;
        $oldVehiclePlate = optional($transfer->vehicule)->plaka;
        $oldVehicleName = optional($transfer->vehicule)->name;
        $oldSecondDriverId = Schema::hasColumn('transfers', 'second_driver_id') ? ($transfer->second_driver_id ?? null) : null;
        $oldSecondDriverName = $oldSecondDriverId ? optional($transfer->secondDriver)->name : null;
        $newDriver = Acente::findOrFail($request->driver_id);
        $newVehicleId = $request->filled('vehicule_id') ? (int) $request->vehicule_id : null;
        if ($this->vehicleChangeBlocked($transfer, $newVehicleId)) {
            return redirect()->back()->withErrors('Araç bloke edildi, değiştirilemez.');
        }
        $newVehicle = $newVehicleId ? Vehicule::findOrFail($newVehicleId) : null;
        if ($newVehicle && $request->filled('depot_id') && Schema::hasColumn('vehicules', 'depot_id') && (int) ($newVehicle->depot_id ?? 0) !== (int) $request->depot_id) {
            return redirect()->back()->withErrors('Ce véhicule appartient à un autre dépôt.');
        }
        $newSecondDriverId = $this->secondDriverIdFromRequest($request);
        $newSecondDriver = $newSecondDriverId ? Acente::findOrFail($newSecondDriverId) : null;

        $assignmentChanged = ((int) $oldDriverId !== (int) $newDriver->id) || ((int) $oldVehicleId !== (int) $newVehicleId) || ((int) $oldSecondDriverId !== (int) $newSecondDriverId);

        $transfer->driver_id = $newDriver->id;
        $transfer->vehicule_id = $newVehicleId;
        if (Schema::hasColumn('transfers', 'depot_id') && Schema::hasColumn('vehicules', 'depot_id')) {
            $transfer->depot_id = $newVehicle ? ($newVehicle->depot_id ?: null) : ($request->filled('depot_id') ? (int) $request->depot_id : $transfer->depot_id);
            $transfer->depot_source = $newVehicle && $newVehicle->depot_id ? 'vehicule' : ($request->filled('depot_id') ? 'manual' : $transfer->depot_source);
        }
        $transfer->save();
        if ($assignmentChanged) {
            $this->markDriverReconfirmationRequired($transfer, 'Affectation chauffeur / véhicule modifiée');
        }
        $this->syncSecondDriverFromRequest($transfer, $request, $transfer->start_date);

        $this->updatePrimaryHareketAcente($transfer, (int) $newDriver->id);

        if ($assignmentChanged) {
            LogActivity::addToLog(
                'Planning chauffeur / véhicule modifié',
                $transfer->post_id,
                json_encode([
                    'transfer_id' => $transfer->id,
                    'driver' => [
                        'old_id' => $oldDriverId,
                        'old_name' => $oldDriverName,
                        'new_id' => $newDriver->id,
                        'new_name' => $newDriver->name,
                    ],
                    'second_driver' => [
                        'old_id' => $oldSecondDriverId,
                        'old_name' => $oldSecondDriverName,
                        'new_id' => $newSecondDriverId,
                        'new_name' => optional($newSecondDriver)->name,
                    ],
                    'vehicle' => [
                        'old_id' => $oldVehicleId,
                        'old_plaque' => $oldVehiclePlate,
                        'old_name' => $oldVehicleName,
                        'new_id' => $newVehicleId,
                        'new_plaque' => optional($newVehicle)->plaka,
                        'new_name' => optional($newVehicle)->name ?: ($newVehicleId ? null : 'Véhicule extérieur / ---'),
                    ],
                ], JSON_UNESCAPED_UNICODE)
            );
        }

        $date = $request->filled('planning_date')
            ? Carbon::parse($request->planning_date)->toDateString()
            : Carbon::parse($transfer->start_date)->toDateString();

        $params = ['date' => $date];
        if ($request->filled('depot_id')) {
            $params['depot_id'] = (int) $request->depot_id;
        }

        return redirect()->route('planning.demain', $params)
            ->with('flash_message', 'Affectation chauffeur / véhicule mise à jour.');
    }

    public function assignPlanningFutureVehicle(Request $request)
    {
        $data = $request->validate([
            'transfer_id' => 'required|integer|exists:transfers,id',
            'vehicule_id' => 'required|integer|exists:vehicules,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'depot_id' => 'nullable|integer|exists:depots,id',
        ]);

        $transfer = Transfer::with(['vehicule'])->findOrFail($data['transfer_id']);

        if (strtotime($transfer->start_date) < strtotime('yesterday') && !auth()->user()->hasRole('Superadmin')) {
            abort(403, 'Past transfer cannot be updated');
        }

        if ($transfer->conge) {
            return redirect()->back()->withErrors('Congé transfer cannot be modified');
        }

        $vehicle = Vehicule::findOrFail($data['vehicule_id']);
        if ($this->vehicleChangeBlocked($transfer, $vehicle->id)) {
            return redirect()->back()->withErrors('Araç bloke edildi, değiştirilemez.');
        }
        if (!$vehicle->real || $vehicle->sales) {
            return redirect()->back()->withErrors('Sélectionnez un véhicule réel.');
        }
        if (!empty($data['depot_id']) && Schema::hasColumn('vehicules', 'depot_id') && (int) ($vehicle->depot_id ?? 0) !== (int) $data['depot_id']) {
            return redirect()->back()->withErrors('Ce véhicule appartient à un autre dépôt.');
        }

        $oldVehicleId = $transfer->vehicule_id;
        $oldVehiclePlate = optional($transfer->vehicule)->plaka;
        $oldVehicleName = optional($transfer->vehicule)->name;

        $transfer->vehicule_id = $vehicle->id;
        if (Schema::hasColumn('transfers', 'depot_id') && Schema::hasColumn('vehicules', 'depot_id')) {
            $transfer->depot_id = $vehicle->depot_id ?: null;
            $transfer->depot_source = $vehicle->depot_id ? 'vehicule' : null;
        }
        $transfer->save();

        if ((int) $oldVehicleId !== (int) $vehicle->id) {
            $this->markDriverReconfirmationRequired($transfer, 'Affectation véhicule modifiée');
            LogActivity::addToLog(
                'Planning futur véhicule affecté',
                $transfer->post_id,
                json_encode([
                    'transfer_id' => $transfer->id,
                    'vehicle' => [
                        'old_id' => $oldVehicleId,
                        'old_plaque' => $oldVehiclePlate,
                        'old_name' => $oldVehicleName,
                        'new_id' => $vehicle->id,
                        'new_plaque' => $vehicle->plaka,
                        'new_name' => $vehicle->name,
                    ],
                ], JSON_UNESCAPED_UNICODE)
            );
        }

        $params = [];
        if (!empty($data['start_date'])) {
            $params['start_date'] = Carbon::parse($data['start_date'])->toDateString();
        }
        if (!empty($data['end_date'])) {
            $params['end_date'] = Carbon::parse($data['end_date'])->toDateString();
        }
        if (!empty($data['depot_id'])) {
            $params['depot_id'] = (int) $data['depot_id'];
        }

        return redirect()->route('planning.futur', $params)
            ->with('flash_message', 'Véhicule affecté au transfert #' . $transfer->id . '.');
    }

    public function showdriver($id)
   {
    $transfers=Transfer::findOrFail($id);
    $user = Auth::user();
    $isOffice = $user && ($user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || $user->hasAnyPermission(['transfers.operations', 'ofis', 'transport']));
    $linkedAcentes = $user ? $user->acentes()->pluck('acentes.id')->map(fn ($id) => (int) $id)->toArray() : [];
    $isPrimaryDriver = in_array((int) $transfers->driver_id, $linkedAcentes, true);
    $isSecondDriver = Schema::hasColumn('transfers', 'second_driver_id') && in_array((int) ($transfers->second_driver_id ?? 0), $linkedAcentes, true);
    if (!$isOffice && !$isPrimaryDriver && !$isSecondDriver) {
        abort(403, 'Ce transfert ne vous est pas attribué.');
    }
    $status=Status::pluck('name','id');
    if (file_exists(public_path('voucher'.'/'.$id)))
    {$files =File::allFiles(public_path('voucher'.'/'.$id)); }
    else {$files=array();}
    return view ('transfert.showdriver', compact('transfers','status','files'));
   }
   public function showtransferPDF(Request $request)
   {
       try {
           $id = $request->id;
           $transfers = Transfer::findOrFail($id);
           $sirket = Sirket::find(2); // Assuming 'id' 2 corresponds to the desired row
   
           // Check if $transfers is not null and it's an instance of Transfer model
           if ($transfers instanceof Transfer && $sirket instanceof Sirket) {
               $data = [
                   'transfers' => $transfers,
                   'sirket' => $sirket,
               ];
   
               $pdf = PDF::loadView('transfert.show-pdf', $data);
   
               return $pdf->stream('show-pdf.pdf');
           } else {
               return "Transfer not found or invalid data.";
           }
       } catch (\Exception $e) {
           return $e->getMessage(); // Return any specific error message for debugging purposes
       }
   }

   public function showMissionPDF($id)
   {
    $relations = ['driver', 'vehicule', 'trajets', 'post.acente', 'post.client', 'missionr'];
    if (Schema::hasColumn('transfers', 'second_driver_id')) {
        $relations[] = 'secondDriver';
    }

    $transfer = Transfer::with($relations)->findOrFail($id);
    $sirket = Sirket::find(2); 
    $surplaceMinBefore = Option::where('name', 'surplaceMinBefore')->first();
    $pdf = PDF::loadView('transfert.showmission', compact('transfer','sirket', 'surplaceMinBefore'));
 
    $fileName = 'feuille_de_route_' . $transfer->id . '.pdf';
    return $pdf->download($fileName);
   }
   
   public function smsgonder($id, TwilioService $twilio)
   {
   $transfers = Transfer::findOrFail($id);

   $mesg  = date("d-m-Y H:i", strtotime($transfers->end_date));
   $mesg .= $transfers->from;
   $mesg .= $transfers->target;
   $mesg .= $transfers->pax;
   $mesg .= $transfers->comments;
   $to = $transfers->driver->tel;

   if ($to) {
       $twilio->sendMessage($to, $mesg);
       return back()->with('flash_message', 'Message Sended');
   }

   return back()->with('flash_message', 'No Phone Number');
   }




   public function driverNoteUpdate(Request $request, Transfer $transfer)
   {
       $user = Auth::user();
       $isOffice = $user && ($user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || $user->hasAnyPermission(['transfers.operations', 'ofis', 'transport']));
       $linkedAcentes = $user ? $user->acentes()->pluck('acentes.id')->map(fn ($id) => (int) $id)->toArray() : [];
       $isPrimaryDriver = in_array((int) $transfer->driver_id, $linkedAcentes, true);
       $isSecondDriver = Schema::hasColumn('transfers', 'second_driver_id') && in_array((int) ($transfer->second_driver_id ?? 0), $linkedAcentes, true);

       if (!$isOffice && !$isPrimaryDriver && !$isSecondDriver) {
           abort(403, 'Ce transfert ne vous est pas attribué.');
       }

       $validated = $request->validate([
           'dcomments' => 'nullable|string|max:2000',
       ]);

       $transfer->dcomments = $validated['dcomments'] ?? null;
       $transfer->save();

       LogActivity::addToLog('Driver note updated.', $transfer->post_id, 'Transfer ID: ' . $transfer->id);

       return redirect()->back()->with('flash_message', 'Note chauffeur enregistrée.');
   }


   public function driverSignatureUpload(Request $request, Transfer $transfer)
   {
       $user = Auth::user();
       $isOffice = $user && ($user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || $user->hasAnyPermission(['transfers.operations', 'ofis', 'transport']));
       $linkedAcentes = $user ? $user->acentes()->pluck('acentes.id')->map(fn ($id) => (int) $id)->toArray() : [];
       $isPrimaryDriver = in_array((int) $transfer->driver_id, $linkedAcentes, true);
       $isSecondDriver = Schema::hasColumn('transfers', 'second_driver_id') && in_array((int) ($transfer->second_driver_id ?? 0), $linkedAcentes, true);

       if (!$isOffice && !$isPrimaryDriver && !$isSecondDriver) {
           abort(403, 'Ce transfert ne vous est pas attribué.');
       }

       $validated = $request->validate([
           'signature' => ['required', 'string'],
       ]);

       if (!preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
           return back()->withErrors(['signature' => 'Signature invalide.']);
       }

       $directory = public_path('voucher/' . $transfer->id);
       if (!is_dir($directory)) {
           mkdir($directory, 0775, true);
       }

       $imageData = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
       $fileName = 'signature_chauffeur_' . time() . '.png';
       file_put_contents($directory . '/' . $fileName, $imageData);

       LogActivity::addToLog('Signature chauffeur uploaded.', $transfer->post_id, 'Transfer ID: ' . $transfer->id);

       return redirect()->back()->with('flash_message', 'Signature enregistrée.');
   }


   public function voucherupload(Request $request) {

    $validated = $request->validate([
       'image' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,heic,heif|max:12288',
       'id' => 'required',
       'post_id' => 'nullable',
     ]);

   $host = $request->getHost();
$stroreFile = $host === 'ofis.tittravel.com' ? 'voucher' : 'voucher';
   $image = $request->file('image');
   $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
   $directory = public_path($stroreFile.'/'.$validated['id']);

   if (!is_dir($directory)) {
       mkdir($directory, 0775, true);
   }

   $image->move($directory, $imageName);
   LogActivity::addToLog('Voucher Uploaded.', $validated['post_id'] ?? null, 'Transfer ID: ' . $validated['id'] . ' | File: ' . $imageName);
   return redirect()->back()->with('flash_message','Photo envoyée avec succès : '.$imageName);
   }



   public function ofisStart(Request $request)
   { 
    $transfer=Transfer::findOrFail($request->transfer_id);
    if ($transfer->start_date>$request->ofis_start) {
      return redirect()->back()->with('flash_message','Bourget deparı transfer saatinden once olmalı');
    }
    $transfer->ofis_start=$request->ofis_start;
    $transfer->save();
    
    return redirect()->back()->with('flash_message','THE TRANSFERT BOURGET TIME IS UPDATED');
  }


  public function ofisStartnull(Request $request)
  { 
   $transfer=Transfer::findOrFail($request->transfer_id);
   $transfer->ofis_start=null;
   $transfer->save();
   
   return redirect()->back()->with('flash_message','THE TRANSFERT BOURGET TIME IS null');
 }
  
  
 public function updateClientStatus(Request $request, $id)
    {
        $request->validate([
            'client_status_id' => 'required|in:1,0', // 1 for Yes, 0 for No
        ]);

        $transfer = Transfer::findOrFail($id);
        $transfer->client_status_id = $request->client_status_id;
        $transfer->save();

        return back()->with('success', 'Client status updated successfully.');

    }
    
  public function addconge($id)  

  {
    $post = Post::findOrFail($id); // İlgili postu bul
    $congearray=Option::where('name','conge')->first();


    if ($congearray && !empty($congearray->value)) {
        $conge = explode(',', $congearray->value); // Virgülle ayrılmış ID'leri diziye çevir
        
        // Servicetype modelinde bu ID'lere sahip olan kayıtları çek
        $servicetype = Servicetype::whereIn('id', $conge)
            ->orderBy('name')
            ->pluck('name', 'id');
    } else {
        $servicetype = collect(); // Boş koleksiyon döndür
    }

    $firmaIds = [3];
    $drivers = Acente::whereHas('firmas', function ($query) use ($firmaIds) {
        $query->whereIn('firma_id', $firmaIds);
    })->orderBy('name')->pluck('name', 'id');
   
    return view ('transfert.conge', compact('id','servicetype', 'drivers'));
    
 

  }

  public function saveconge(Request $request, $id)  

  {
    $post = Post::findOrFail($id); // İlgili postu bul
    $this->validate($request, [
          
        
        'start_date' => 'required|date',
        'servicetype_id' => 'required',
        'driver_id' => 'required|integer',
        'end_date' => 'required|integer|min:1',
    ]);

    $servicetype=Servicetype::where('id',$request->servicetype_id)->first();
    $firstFrom = $servicetype->name;
    $lastTarget = $servicetype->name;
    $vehicules = Vehicule::whereNull('sales')->orderBy('name')->pluck('name', 'id');
    $firmas = Firma::orderBy('name')->pluck('name', 'id');

    $startDate = Carbon::parse($request->start_date);
    $days = (int) $request->end_date;
    $endDate = $startDate->addDays($days - 1)->endOfDay();
   
    $transfer = new Transfer;
    $transfer->start_date = $request->start_date; 
    $transfer->end_date = $endDate; 
    $transfer->servicetype_id = $request->servicetype_id;
    $transfer->from = $firstFrom; // Start location excluding Depot
    $transfer->target = $lastTarget; // End location excluding Depot
    $transfer->pax = 0;
    $transfer->comments = $request->comments;
    $transfer->vehicule_id =0;
    $transfer->driver_id = $request->driver_id?$request->driver_id:0;
    $transfer->km = 0;
    $transfer->post_id = $id;
    $transfer->mission = 0;
    $transfer->accueil =  0;
    $transfer->status_id = 2;
    $transfer->conge = 1;
    $transfer->ofis_start = $request->start_date;
    $transfer->save();
    LogActivity::addToLog('TransferCreate.', $transfer->post_id,'Created');
    return redirect()->route('posts.show',$transfer->post_id)->with('flash_message', 'Post related to the transfer has been accessed.');


   // return view ('transfert.conge', compact('id','servicetype','vehicules', 'firmas'));

 

  }

///////android api

  public function getTransfers(Request $request)
    {
        $dateOption = $request->input('dateOption', 'default');
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
                if ($request->input('start_date')) {
                    $start_date = date('Y-m-d', strtotime($request->input('start_date')));
                    $end_date = date('Y-m-d', strtotime($request->input('start_date') . " + 1 day"));
                }
                break;
        }

        // Tarih kontrolü
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
        // 'surplaceMinBefore' değerini al
    $surplaceMinBefore = Option::where('name', 'surplaceMinBefore')->first();
    $minutesToSubtract = optional($surplaceMinBefore)->value ?? 0; // Eğer null ise varsayılan olarak 0 al

        $transfers = Transfer::with(['servicetype','status'])->whereBetween('start_date', [$start_date, $end_date])
            ->where(function ($q) use ($acentes) {
                $q->whereIn('driver_id', $acentes);
                if (Schema::hasColumn('transfers', 'second_driver_id')) {
                    $q->orWhereIn('second_driver_id', $acentes);
                }
            })
            ->orderBy('start_date')
            ->get();
            \Log::info("Transfer API çağrıldı, Kullanıcı: " . auth()->user()->id);

            return response()->json([
                'success' => true,
                'transfers' => $transfers->map(function ($transfer) {
                    return [
                        'id' => $transfer->id,
                        'post_id' => $transfer->post_id,
                        'start_date' => Carbon::parse($transfer->start_date)->format('Y-m-d H:i:s'), // Dakika düşülmüş hali
                        'ofis_start' => Carbon::parse($transfer->ofis_start)->format('Y-m-d H:i:s'),
                        'servicetype_id' => $transfer->servicetype_id,
                        'from' => $transfer->from,
                        'target' => $transfer->target,
                        'status_id' => $transfer->status_id,
                        'comments' => $transfer->comments,
                        'dcomments' => $transfer->dcomments,
                        'servicetype' => [
                            'id' => $transfer->servicetype->id,
                            'name' => $transfer->servicetype->name,
                        ],
                        'status' => [
                            'id' => $transfer->status->id,
                            'name' => $transfer->status->name,
                        ]
                    ];
                }),
            ], 200);
    }

    public function getTransferDetail($id)
{
    $transfer = Transfer::with(['servicetype', 'status','post.client']) // İlişkili verileri ekleyelim
        ->where('id', $id)
        ->first();

    if (!$transfer) {
        return response()->json(['error' => 'Transfer not found'], 404);
    }
    $surplaceMinBefore = Option::where('name', 'surplaceMinBefore')->first();
    $minutesToSubtract = optional($surplaceMinBefore)->value ?? 0; // Eğer null ise varsayılan olarak 0 al
    // **Misafir Bilgisini Alalım**
    $misafirList =[];
    $whatsappLink = null;
    if ($transfer->post && $transfer->post->client) {
        foreach ($transfer->post->client as $client) {
            $phoneNumber = preg_replace('/\D/', '', $client->tel); // Sadece rakamları al
            $misafirList[] = [
                'name' => $client->name,
                'surname' => $client->surname,
                'phone' => $client->tel,
                'whatsapp_link' => !empty($phoneNumber) ? "https://wa.me/" . $phoneNumber : null
            ];
             }
    }
    $trajetsList = [];
    foreach ($transfer->trajets as $index => $trajet) {
        $trajetDetails = ($index + 1) . '. ';
        if (date("Y-m-d") == date('Y-m-d', strtotime($trajet->datetime))) {
            $trajetDetails .= date('H:i', strtotime($trajet->datetime));
        } else {
            $trajetDetails .= date('d/m/Y H:i', strtotime($trajet->datetime));
        }
        $trajetDetails .= ' - ' . $trajet->type . ': ' . $trajet->from . ' ' . $trajet->google_address;

        $trajetsList[] = [
            'id' => $trajet->id,
            'details' => $trajetDetails
        ];
    }
    $ofis_start = Carbon::parse($transfer->ofis_start)->format('H:i');
    $surplace = Carbon::parse($transfer->start_date)->subMinutes(15)->format('H:i'); // `surplace` örnek hesaplama
    $start_date = Carbon::parse($transfer->start_date)->format('H:i');

    // **end_date Gün Kontrolü**
    $end_date_format = (Carbon::parse($transfer->end_date)->format('Y-m-d') == Carbon::parse($transfer->start_date)->format('Y-m-d')) 
        ? Carbon::parse($transfer->end_date)->format('H:i') 
        : Carbon::parse($transfer->end_date)->format('Y-m-d H:i');
           
        $origin=false;
        $destination=false;
        $waypoints=false;
       $googleAddresses = $transfer->trajets()->orderBy('order')->pluck('google_address');
        $origin = $googleAddresses->first(); // İlk değer origin
        $destination = $googleAddresses->last(); // Son değer destination
        $waypoints = $googleAddresses->slice(1, $googleAddresses->count() - 2)->implode('|'); // Aradakiler waypoints
                                 
        
        
        
        return response()->json([
                'success' => true,
                'transfer' => [
                    'id' => $transfer->id,
                    'post_id' => $transfer->post_id,
                    'from' => $transfer->from,
                    'target' => $transfer->target,
                    'pax' => $transfer->pax,
                    'status_id' => $transfer->status_id,
                    'created_at' => Carbon::parse($transfer->created_at)->format(' H:i:s'),
                    'updated_at' => Carbon::parse($transfer->updated_at)->format('Y-m-d H:i:s'),
                    'surplace' => Carbon::parse($transfer->start_date)->subMinutes($minutesToSubtract)->format('H:i'), // Dakika düşülmüş hali
                    'start_date' => Carbon::parse($transfer->start_date)->format('H:i:s'), // 
                    'end_date'  => Carbon::parse($transfer->end_date)->format('H:i:s'), // 
                    'ofis_start' => Carbon::parse($transfer->ofis_start)->format('H:i:s'),
                    'vehicule' => $transfer->vehicule, 
                    'comments' => $transfer->comments,    
                    'servicetype' => $transfer->servicetype,
                    'status' => $transfer->status,
                    'accueil' => $transfer->accueil??0,
                    'mission' => $transfer->mission??0,
                    'timetable' => [
                        'ofisStart' => $ofis_start,
                        'sur_place' => $surplace,
                        'startDate' => $start_date,
                        'endDate' => $end_date_format,
                    ],
                   
                        'origin' => $origin,
                        'destination' => $destination,
                        'waypoints' => $waypoints,
                  
                ],
                'misafirler' => $misafirList, // Misafir bilgisini JSON yanıtına ekledik
                'trajets' => $trajetsList // Trajets bilgisi eklendi
            ]);
    }
    public function confirmTransfer(Request $request, $id)
    {
        $transfer = Transfer::find($id);
    
        if (!$transfer) {
            return response()->json(['success' => false, 'message' => 'Transfer bulunamadı'], 404);
        }
    
        // Status_id'yi güncelle
        $updated = $transfer->update([
            'status_id' => 3 // Transfer durumu "3" olarak güncellenecek
        ]);
    
        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Transfer başarıyla güncellendi',
                'transfer' => $transfer
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Güncelleme başarısız oldu'
            ], 500);
        }
    }
   

}
  
