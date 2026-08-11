<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Transfer;
use App\Models\Acente;
use App\Models\Vehicule;
use App\Models\Depot;
use App\Models\Option;

use App\Models\Firma;
use App\Models\Servicetype;
use MaddHatter\LaravelFullcalendar\Facades\Calendar;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChartController extends Controller
{
  protected $transferids;
  protected $dispoids;
   public function __construct() {
    $this->middleware('auth');
    $this->middleware('permission:charts.view');
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




  public function file(Request $request)
  {
      try {
          $month = $request->filled('month')
              ? Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
              : Carbon::now()->startOfMonth();
      } catch (\Throwable $e) {
          $month = Carbon::now()->startOfMonth();
      }

      $monthEnd = $month->copy()->endOfMonth();
      $nextMonthStart = $month->copy()->addMonth()->startOfMonth();
      $selectedAgencyId = $request->filled('agency_id') ? (int) $request->input('agency_id') : null;
      $selectedStatusId = $request->filled('status_id') ? (int) $request->input('status_id') : null;

      $filesQuery = Post::with([
              'acente:id,name,color',
              'status:id,name',
              'transfer' => fn ($query) => $query
                  ->with(['driver', 'vehicule', 'servicetype'])
                  ->orderBy('start_date'),
          ])
          ->where('start_date', '<', $nextMonthStart)
          ->where(function ($query) use ($month) {
              $query->whereNull('end_date')->orWhere('end_date', '>=', $month);
          })
          ->when($selectedAgencyId, fn ($query) => $query->where('acente_id', $selectedAgencyId))
          ->when($selectedStatusId, fn ($query) => $query->where('status_id', $selectedStatusId))
          ->orderBy('start_date')
          ->orderBy('id');

      $files = $filesQuery->get();
      $fileIds = $files->pluck('id');

      $monthlyTransfers = Transfer::with(['driver', 'vehicule', 'servicetype'])
          ->whereIn('post_id', $fileIds)
          ->where('start_date', '<', $nextMonthStart)
          ->where(function ($query) use ($month) {
              $query->whereNull('end_date')->orWhere('end_date', '>=', $month);
          })
          ->where('status_id', '<>', 1)
          ->get();

      $dailyWorkload = collect();
      $maxDailyTransfers = 1;
      for ($day = $month->copy(); $day->lte($monthEnd); $day->addDay()) {
          $dayStart = $day->copy()->startOfDay();
          $dayEnd = $day->copy()->endOfDay();
          $dayFiles = $files->filter(function ($file) use ($dayStart, $dayEnd) {
              $start = Carbon::parse($file->start_date)->startOfDay();
              $end = $file->end_date ? Carbon::parse($file->end_date)->endOfDay() : $start->copy()->endOfDay();
              return $start->lte($dayEnd) && $end->gte($dayStart);
          })->count();
          $dayTransfers = $monthlyTransfers->filter(function ($transfer) use ($dayStart, $dayEnd) {
              $start = Carbon::parse($transfer->start_date);
              return $start->between($dayStart, $dayEnd, true);
          })->count();

          $maxDailyTransfers = max($maxDailyTransfers, $dayTransfers);
          $dailyWorkload->push([
              'date' => $day->toDateString(),
              'day' => $day->format('d'),
              'weekday' => ucfirst($day->locale('fr')->isoFormat('ddd')),
              'files' => $dayFiles,
              'transfers' => $dayTransfers,
              'is_today' => $day->isToday(),
          ]);
      }

      $stats = [
          'files' => $files->count(),
          'transfers' => $monthlyTransfers->count(),
          'pax' => (int) $files->sum('pax'),
          'unassigned' => $monthlyTransfers->filter(fn ($transfer) => !$transfer->driver_id || !$transfer->vehicule_id)->count(),
          'confirmed' => $files->where('status_id', 3)->count(),
          'cancelled' => $files->where('status_id', 1)->count(),
      ];

      $agencies = Acente::orderBy('name')->get(['id', 'name']);
      $statuses = \App\Models\Status::orderBy('id')->get(['id', 'name']);
      $file = false;

      return view('chart.fullcalendar', compact(
          'file',
          'month',
          'monthEnd',
          'files',
          'monthlyTransfers',
          'dailyWorkload',
          'maxDailyTransfers',
          'stats',
          'agencies',
          'statuses',
          'selectedAgencyId',
          'selectedStatusId'
      ));
  }
   public function transfer()
            {
                $file=true;
                return view('chart.fullcalendar', compact('file'));
            }
  private function getServiceTypes()
  {
      return [
          'transfer' => Servicetype::whereIn('firma_id', $this->transferids)->orderBy('name')->pluck('name', 'id'),
          'dispo' => Servicetype::whereIn('firma_id', $this->dispoids)->orderBy('name')->pluck('name', 'id'),
      ];
  }          
  public function day(Request $request)
  {
      $servicetypes = $this->getServiceTypes();
      Session::put('start_date', $request->input('start_date'));
  
      if ($request->input('start_date')) {
          $date = Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->startOfDay();
          $dateend = Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->endOfDay();
      } else {
          $date = Carbon::now()->startOfDay();
          $dateend = Carbon::now()->endOfDay();
      }

      $depotOptions = Schema::hasTable('depots')
          ? Depot::where(function ($query) {
                  $query->where('active', 1)->orWhereNull('active');
              })
              ->orderBy('sort_order')
              ->orderBy('name')
              ->get(['id', 'name', 'code', 'city'])
              ->map(function ($depot) {
                  $depot->planning_label = $depot->city ?: str_replace(['Dépôt ', 'Depot '], '', (string) $depot->name);
                  $depot->planning_label = $depot->planning_label ?: ($depot->code ?: 'Dépôt #' . $depot->id);
                  return $depot;
              })
          : collect();
      $depotLabels = $depotOptions->mapWithKeys(fn ($depot) => [(int) $depot->id => $depot->planning_label]);
      $requestedDepotId = $request->input('depot_id');
      $selectedDepotId = $requestedDepotId === null || $requestedDepotId === '' ? null : (int) $requestedDepotId;
      if ($selectedDepotId !== null && !$depotLabels->has($selectedDepotId)) {
          $selectedDepotId = null;
      }
  
      // Ana sorgu: start_date'e göre filtreleme
      $data = Transfer::whereBetween('start_date', [$date, $dateend])
          ->with(['depot', 'post.acente', 'servicetype', 'driver', 'vehicule.depot', 'post.client', 'trajets', 'status', 'harekets.payment', 'missionr.transfer.harekets'])
          ->when($request->filled('acente'), function ($query) use ($request) {
              $query->whereHas('post.acente', function ($q) use ($request) {
                  $q->where('name', $request->input('acente'));
              });
          })
          ->when($selectedDepotId !== null && Schema::hasColumn('transfers', 'depot_id'), function ($query) use ($selectedDepotId) {
              $query->where(function ($depotQuery) use ($selectedDepotId) {
                  $depotQuery->where('depot_id', $selectedDepotId)
                      ->orWhereHas('vehicule', fn ($vehicleQuery) => $vehicleQuery->where('depot_id', $selectedDepotId))
                      ->orWhere(function ($fallbackQuery) use ($selectedDepotId) {
                          $fallbackQuery->whereNull('depot_id')
                              ->whereHas('vehicule', fn ($vehicleQuery) => $vehicleQuery->where('depot_id', $selectedDepotId));
                      });
              });
          })
          ->orderBy('start_date')
          ->get();
  
      // Tarih tuşları
      $tomorrow = $date->copy()->addDay()->toDateString();
      $yesterday = $date->copy()->subDay()->toDateString();
  
      // "conge" array'i
      $event = Option::where('name', 'conge')->first();
      $congeIds = array_map('intval', explode(',', $event->value));
  
      // Acenteleri grupla (sadece var olanlardan)
      $acenteListesi = $data->pluck('post.acente.name')->filter()->unique()->sort()->values();
  
      // Diğer veriler
      $vehicules = Vehicule::whereNull('sales')->orderBy('name')->pluck('name', 'id');
      $firmas = Firma::orderBy('name')->pluck('name', 'id');
  
      return view('chart.day', compact(
          'data', 'tomorrow', 'yesterday', 'date',
          'servicetypes', 'vehicules', 'firmas',
          'event', 'congeIds', 'acenteListesi',
          'depotOptions', 'depotLabels', 'selectedDepotId'
      ));
  }
  


  public function planningDemain(Request $request)
  {
      $date = $request->filled('date')
          ? Carbon::parse($request->input('date'))->startOfDay()
          : Carbon::tomorrow()->startOfDay();
      $startOfDay = $date->copy()->startOfDay();
      $endOfDay = $date->copy()->endOfDay();
      $lookbackStart = $date->copy()->subDays(6)->startOfDay();
      $maxWorkedDays = 6;

      $doubleEquipageEnabled = Schema::hasColumn('transfers', 'second_driver_id');

      $congeOption = Option::where('name', 'conge')->value('value');
      $congeIds = collect(explode(',', (string) $congeOption))
          ->map(fn ($id) => (int) trim($id))
          ->filter()
          ->values()
          ->all();

      $depotOptions = Schema::hasTable('depots')
          ? Depot::where(function ($query) {
                  $query->where('active', 1)->orWhereNull('active');
              })
              ->orderBy('sort_order')
              ->orderBy('name')
              ->get(['id', 'name', 'code', 'city'])
              ->map(function ($depot) {
                  $depot->planning_label = $depot->city ?: str_replace(['Dépôt ', 'Depot '], '', (string) $depot->name);
                  $depot->planning_label = $depot->planning_label ?: ($depot->code ?: 'Dépôt #' . $depot->id);
                  return $depot;
              })
          : collect();
      $depotLabels = $depotOptions->mapWithKeys(fn ($depot) => [(int) $depot->id => $depot->planning_label]);
      $requestedDepotId = $request->input('depot_id');
      $selectedDepotId = $requestedDepotId === null || $requestedDepotId === '' ? null : (int) $requestedDepotId;
      if ($selectedDepotId !== null && !$depotLabels->has($selectedDepotId)) {
          $selectedDepotId = null;
      }

      $transferRelations = ['depot', 'driver.users:id,name,email', 'vehicule.depot', 'servicetype', 'status', 'post.acente'];
      if ($doubleEquipageEnabled) {
          $transferRelations[] = 'secondDriver.users:id,name,email';
      }

      $transfers = Transfer::with($transferRelations)
          ->where(function ($query) use ($startOfDay, $endOfDay) {
              $query->whereBetween('start_date', [$startOfDay, $endOfDay])
                  ->orWhereBetween('end_date', [$startOfDay, $endOfDay]);
          })
          ->where('status_id', '<>', 1)
          ->when($selectedDepotId !== null && Schema::hasColumn('transfers', 'depot_id'), function ($query) use ($selectedDepotId) {
              $query->where(function ($depotQuery) use ($selectedDepotId) {
                  $depotQuery->where('depot_id', $selectedDepotId)
                      ->orWhereHas('vehicule', fn ($vehicleQuery) => $vehicleQuery->where('depot_id', $selectedDepotId))
                      ->orWhere(function ($fallbackQuery) use ($selectedDepotId) {
                          $fallbackQuery->whereNull('depot_id')
                              ->whereHas('vehicule', fn ($vehicleQuery) => $vehicleQuery->where('depot_id', $selectedDepotId));
                      });
              });
          })
          ->orderBy('start_date')
          ->get();

      $serviceTransfers = $transfers->reject(fn ($transfer) => in_array((int) $transfer->servicetype_id, $congeIds, true));
      $usedVehicleIds = $serviceTransfers->pluck('vehicule_id')->filter()->unique()->values();
      $usedDriverIds = $serviceTransfers->pluck('driver_id');
      if ($doubleEquipageEnabled) {
          $usedDriverIds = $usedDriverIds->merge($serviceTransfers->pluck('second_driver_id'));
      }
      $usedDriverIds = $usedDriverIds->filter()->unique()->values();

      $allVehicles = Vehicule::where('real', true)
          ->whereNull('sales')
          ->orderBy('plaka')
          ->orderBy('name')
          ->get();
      if ($selectedDepotId !== null) {
          $allVehicles = $allVehicles
              ->filter(fn ($vehicle) => (int) ($vehicle->depot_id ?? 0) === $selectedDepotId)
              ->values();
      }

      $availableVehicles = $allVehicles
          ->filter(fn ($vehicle) => !$usedVehicleIds->contains($vehicle->id) && !$vehicle->enpanne)
          ->values();
      $brokenVehicles = $allVehicles->filter(fn ($vehicle) => (bool) $vehicle->enpanne)->values();
      $usedVehicles = $allVehicles->filter(fn ($vehicle) => $usedVehicleIds->contains($vehicle->id))->values();

      $driverTypeIds = collect(explode(',', Option::where('name', 'driverId')->value('value') ?: '3,9,12,13,14'))
          ->map(fn ($id) => (int) trim($id))
          ->filter()
          ->values()
          ->all();

      $drivers = Acente::where('suivi', 1)->orderBy('name')->get(['id', 'name', 'tel', 'whatsapp']);
      $driverStats = $drivers->map(function ($driver) use ($date, $lookbackStart, $endOfDay, $maxWorkedDays, $congeIds, $usedDriverIds) {
          $dailyRows = Transfer::query()
              ->selectRaw('DATE(start_date) as work_day, COUNT(*) as transfer_count, COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_date, end_date)), 0) as minutes')
              ->where(function ($q) use ($driver) {
                  $q->where('driver_id', $driver->id);
                  if (Schema::hasColumn('transfers', 'second_driver_id')) {
                      $q->orWhere('second_driver_id', $driver->id);
                  }
              })
              ->where('status_id', '<>', 1)
              ->whereNotIn('servicetype_id', $congeIds ?: [0])
              ->whereBetween(DB::raw('DATE(start_date)'), [$lookbackStart->toDateString(), $endOfDay->toDateString()])
              ->groupBy(DB::raw('DATE(start_date)'))
              ->get()
              ->keyBy('work_day');

          $tomorrowRow = $dailyRows->get($date->toDateString());
          $workedDaysLast7 = $dailyRows->filter(fn ($row, $day) => $day < $date->toDateString() && (int) $row->transfer_count > 0)->count();
          $willWorkTomorrow = $usedDriverIds->contains($driver->id);
          $workedDaysWithTomorrow = $workedDaysLast7 + ($willWorkTomorrow ? 1 : 0);

          return (object) [
              'id' => $driver->id,
              'name' => $driver->name,
              'tel' => $driver->tel,
              'whatsapp' => $driver->whatsapp,
              'will_work' => $willWorkTomorrow,
              'worked_days_last7' => $workedDaysLast7,
              'worked_days_with_target' => $workedDaysWithTomorrow,
              'target_minutes' => (int) ($tomorrowRow->minutes ?? 0),
              'target_transfers' => (int) ($tomorrowRow->transfer_count ?? 0),
              'is_over_limit' => $workedDaysWithTomorrow >= $maxWorkedDays,
          ];
      })->sortByDesc('worked_days_with_target')->values();

      $driverTypes = Firma::whereIn('id', $driverTypeIds)
          ->orderByRaw('FIELD(id, ' . implode(',', $driverTypeIds ?: [0]) . ')')
          ->get(['id', 'name']);

      $assignmentDrivers = Acente::with('firmas:id,name')
          ->where(function ($query) use ($driverTypeIds, $usedDriverIds) {
              $query->whereHas('firmas', function ($q) use ($driverTypeIds) {
                  $q->whereIn('firmas.id', $driverTypeIds);
              })->orWhereIn('acentes.id', $usedDriverIds);
          })
          ->orderBy('name')
          ->get(['id', 'name'])
          ->map(function ($driver) {
              $typeIds = $driver->firmas->pluck('id')->map(fn ($id) => (int) $id)->values();
              $typeNames = $driver->firmas->pluck('name')->implode(', ');
              $isInactive = $driver->firmas->contains('id', 14);
              return (object) [
                  'id' => $driver->id,
                  'name' => $driver->name,
                  'label' => trim($driver->name . ($typeNames ? ' - ' . $typeNames : '')),
                  'type_ids' => $typeIds,
                  'primary_type_id' => $typeIds->first(),
                  'is_inactive' => $isInactive,
              ];
          });

      $assignmentVehicles = $allVehicles->map(function ($vehicle) {
          $label = $vehicle->plaka
              ? trim($vehicle->plaka . ' - ' . $vehicle->name)
              : 'Véhicule extérieur / ---';
          if ($vehicle->enpanne) {
              $label .= ' (Panne)';
          }
          return (object) [
              'id' => $vehicle->id,
              'label' => $label,
              'enpanne' => (bool) $vehicle->enpanne,
          ];
      })->values();

      $availableDrivers = $driverStats
          ->filter(fn ($driver) => !$driver->will_work && !$driver->is_over_limit)
          ->sortBy('name')
          ->values();
      $riskDrivers = $driverStats
          ->filter(fn ($driver) => $driver->is_over_limit || $driver->target_minutes > 600)
          ->values();

      $vehicleConflicts = $serviceTransfers
          ->whereNotNull('vehicule_id')
          ->groupBy('vehicule_id')
          ->flatMap(function ($items) {
              return $this->planningOverlapWarnings($items, 'vehicule');
          })
          ->values();

      $driverConflicts = $serviceTransfers
          ->whereNotNull('driver_id')
          ->groupBy('driver_id')
          ->flatMap(function ($items) {
              return $this->planningOverlapWarnings($items, 'driver');
          })
          ->values();

      $transferWarnings = $serviceTransfers->map(function ($transfer) use ($driverStats) {
          $warnings = [];
          $requiresRealVehicule = (int) optional($transfer->servicetype)->firma_id === 3;
          if ($requiresRealVehicule) {
              if (!$transfer->vehicule_id) {
                  $warnings[] = 'Véhicule non défini';
              } elseif (!$transfer->vehicule) {
                  $warnings[] = 'Véhicule introuvable';
              } elseif ($transfer->vehicule->enpanne) {
                  $warnings[] = 'Véhicule en panne';
              } elseif (!$transfer->vehicule->real) {
                  $warnings[] = 'Véhicule non réel';
              } elseif ($transfer->depot_id && $transfer->vehicule->depot_id && (int) $transfer->depot_id !== (int) $transfer->vehicule->depot_id) {
                  $warnings[] = 'Véhicule dans un autre dépôt';
              }
          }

          if (!$transfer->driver_id) {
              $warnings[] = 'Chauffeur non défini';
          } else {
              if ($transfer->driver && $transfer->driver->users->isEmpty()) {
                  $warnings[] = 'Compte utilisateur chauffeur manquant';
              }
              $driver = $driverStats->firstWhere('id', $transfer->driver_id);
              if ($driver?->is_over_limit) {
                  $warnings[] = 'Chauffeur à surveiller: ' . $driver->worked_days_with_target . ' jours / 7';
              }
              if (($driver->target_minutes ?? 0) > 600) {
                  $warnings[] = 'Journée chauffeur longue';
              }
          }

          return (object) [
              'transfer' => $transfer,
              'warnings' => $warnings,
          ];
      });

      $summary = [
          'transfers' => $serviceTransfers->count(),
          'vehicles_used' => $usedVehicles->count(),
          'vehicles_available' => $availableVehicles->count(),
          'vehicles_broken' => $brokenVehicles->count(),
          'drivers_used' => $usedDriverIds->count(),
          'drivers_available' => $availableDrivers->count(),
          'warnings' => $transferWarnings->sum(fn ($row) => count($row->warnings)) + $vehicleConflicts->count() + $driverConflicts->count(),
      ];

      return view('planning.demain', compact(
          'date',
          'summary',
          'serviceTransfers',
          'transferWarnings',
          'availableVehicles',
          'brokenVehicles',
          'usedVehicles',
          'driverStats',
          'availableDrivers',
          'assignmentDrivers',
          'driverTypes',
          'assignmentVehicles',
          'riskDrivers',
          'vehicleConflicts',
          'driverConflicts',
          'maxWorkedDays',
          'driverTypeIds',
          'depotOptions',
          'depotLabels',
          'selectedDepotId'
      ));
  }

  private function planningOverlapWarnings($items, string $type)
  {
      $sorted = $items->sortBy('start_date')->values();
      $warnings = collect();

      for ($i = 1; $i < $sorted->count(); $i++) {
          $previous = $sorted[$i - 1];
          $current = $sorted[$i];
          if (!$previous->end_date || !$current->start_date) {
              continue;
          }
          if (Carbon::parse($current->start_date)->lt(Carbon::parse($previous->end_date))) {
              $name = $type === 'vehicule'
                  ? ($current->vehicule?->plaka ?: ($current->vehicule?->name ?? 'Véhicule'))
                  : ($current->driver?->name ?? 'Chauffeur');
              $warnings->push((object) [
                  'type' => $type,
                  'name' => $name,
                  'first' => $previous,
                  'second' => $current,
              ]);
          }
      }

      return $warnings;
  }

  private function requestedVehicleTypeKey($vehicule)
  {
      if (!$vehicule || $vehicule->real || $vehicule->sales) {
          return null;
      }

      $label = strtoupper(trim(($vehicule->name ?? '') . ' ' . ($vehicule->plaka ?? '')));

      if (preg_match('/\b(COACH|AUTOCAR)\b/', $label)) {
          return 'coach';
      }

      if (preg_match('/\b(SPRINTER|MINIBUS)\b/', $label)) {
          return 'sprinter';
      }

      if (preg_match('/\b(VAN|VITO)\b|CLASSE\s*V/', $label)) {
          return 'van';
      }

      return null;
  }

  private function requestedVehicleTypeText($type, $count = 1)
  {
      $labels = [
          'coach' => ['singular' => 'coach', 'plural' => 'coachs'],
          'sprinter' => ['singular' => 'sprinter', 'plural' => 'sprinters'],
          'van' => ['singular' => 'van', 'plural' => 'vans'],
      ];

      $label = $labels[$type] ?? ['singular' => $type, 'plural' => $type . 's'];
      $name = ((int) $count > 1) ? $label['plural'] : $label['singular'];

      return (int) $count . ' ' . $name . ' à définir';
  }

  private function requestedVehicleTypeName($type)
  {
      return [
          'coach' => 'Coach à définir',
          'sprinter' => 'Sprinter à définir',
          'van' => 'Van à définir',
      ][$type] ?? 'Véhicule à définir';
  }



  private function vehiclePlanningType($vehicle): string
  {
      $name = mb_strtolower((string) ($vehicle->name ?? ''));
      $capacity = (int) ($vehicle->capacity ?? 0);

      if ($capacity > 0 && $capacity <= 8) {
          return 'van';
      }

      if (str_contains($name, 'class v') || str_contains($name, 'classe v') || str_contains($name, 'vito')) {
          return 'van';
      }

      if (str_contains($name, 'sprinter')) {
          return 'sprinter';
      }

      if ($capacity >= 23 || str_contains($name, 'tourismo') || str_contains($name, 'temsa') || str_contains($name, 'otokar') || str_contains($name, 'bova') || str_contains($name, 'iveco') || str_contains($name, 'coach') || str_contains($name, 'autocar')) {
          return 'coach';
      }

      if ($capacity >= 9) {
          return 'sprinter';
      }

      return 'van';
  }



  public function planningFutur(Request $request)
  {
      $start = $request->filled('start_date')
          ? Carbon::parse($request->input('start_date'))->startOfDay()
          : Carbon::today()->startOfDay();
      $end = $request->filled('end_date')
          ? Carbon::parse($request->input('end_date'))->endOfDay()
          : $start->copy()->addDays(30)->endOfDay();

      if ($end->lt($start)) {
          $end = $start->copy()->addDays(30)->endOfDay();
      }

      $congeServiceTypeIds = Option::where('name', 'conge')
          ->value('value');
      $congeServiceTypeIds = collect(explode(',', (string) $congeServiceTypeIds))
          ->map(fn ($id) => (int) trim($id))
          ->filter()
          ->values()
          ->all();

      $allAssignableVehicles = Vehicule::whereNull('sales')
          ->where('real', true)
          ->orderBy('plaka')
          ->orderBy('name')
          ->get(['id', 'name', 'plaka', 'capacity', 'depot_id', 'enpanne'])
          ->map(function ($vehicle) {
              $vehicle->planning_type = $this->vehiclePlanningType($vehicle);
              return $vehicle;
          });

      $vehicleTypeLabels = [
          'van' => 'Van',
          'sprinter' => 'Sprinter',
          'coach' => 'Coach',
      ];

      $depotOptions = Schema::hasTable('depots')
          ? Depot::where(function ($query) {
                  $query->where('active', 1)->orWhereNull('active');
              })
              ->orderBy('sort_order')
              ->orderBy('name')
              ->get(['id', 'name', 'code', 'city'])
              ->map(function ($depot) {
                  $depot->planning_label = $depot->city ?: str_replace(['Dépôt ', 'Depot '], '', (string) $depot->name);
                  $depot->planning_label = $depot->planning_label ?: ($depot->code ?: 'Dépôt #' . $depot->id);
                  return $depot;
              })
          : collect();

      $depotLabels = $depotOptions->mapWithKeys(fn ($depot) => [(int) $depot->id => $depot->planning_label]);
      $requestedDepotId = $request->input('depot_id');
      $selectedDepotId = $requestedDepotId === null || $requestedDepotId === '' ? null : (int) $requestedDepotId;
      if ($selectedDepotId !== null && !$depotLabels->has($selectedDepotId)) {
          $selectedDepotId = null;
      }

      $assignableVehicles = $selectedDepotId === null
          ? $allAssignableVehicles
          : $allAssignableVehicles
              ->filter(fn ($vehicle) => (int) ($vehicle->depot_id ?? 0) === $selectedDepotId)
              ->values();

      $totalRealVehicles = $assignableVehicles->count();

      $transfers = Transfer::with(['depot', 'vehicule.depot', 'driver', 'secondDriver', 'servicetype', 'post.acente', 'status', 'externalVehicleProvider'])
          ->whereNull('deleted_at')
          ->where('status_id', '<>', 1)
          ->when($selectedDepotId !== null && Schema::hasColumn('transfers', 'depot_id'), function ($query) use ($selectedDepotId) {
              $query->where(function ($depotQuery) use ($selectedDepotId) {
                  $depotQuery->where('depot_id', $selectedDepotId)
                      ->orWhereHas('vehicule', fn ($vehicleQuery) => $vehicleQuery->where('depot_id', $selectedDepotId))
                      ->orWhere(function ($fallbackQuery) use ($selectedDepotId) {
                          $fallbackQuery->whereNull('depot_id')
                              ->whereHas('vehicule', fn ($vehicleQuery) => $vehicleQuery->where('depot_id', $selectedDepotId));
                      });
              });
          })
          ->when(!empty($congeServiceTypeIds), function ($query) use ($congeServiceTypeIds) {
              $query->whereNotIn('servicetype_id', $congeServiceTypeIds);
          })
          ->where(function ($query) use ($start, $end) {
              $query->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($overlap) use ($start, $end) {
                      $overlap->where('start_date', '<', $start)
                          ->where('end_date', '>', $end);
                  });
          })
          ->orderBy('start_date')
          ->get();

      $days = collect();
      $cursor = $start->copy();
      while ($cursor->lte($end)) {
          $dayStart = $cursor->copy()->startOfDay();
          $dayEnd = $cursor->copy()->endOfDay();
          $dayTransfers = $transfers->filter(function ($transfer) use ($dayStart, $dayEnd) {
              $transferStart = $transfer->start_date ? Carbon::parse($transfer->start_date) : null;
              $transferEnd = $transfer->end_date ? Carbon::parse($transfer->end_date) : $transferStart;

              if (!$transferStart) {
                  return false;
              }

              return $transferStart->lte($dayEnd) && $transferEnd->gte($dayStart);
          })->values();

          $realVehicleTransfers = $dayTransfers->filter(function ($transfer) {
              return $transfer->vehicule && $transfer->vehicule->real && !$transfer->vehicule->sales;
          });

          $usedVehicles = $realVehicleTransfers
              ->pluck('vehicule')
              ->filter()
              ->unique('id')
              ->sortBy(fn ($vehicule) => $vehicule->plaka ?: $vehicule->name)
              ->values();

          $subcontractedTransfers = $dayTransfers->filter(function ($transfer) {
              $hasRealVehicle = $transfer->vehicule && $transfer->vehicule->real && !$transfer->vehicule->sales;
              if ($hasRealVehicle) {
                  return false;
              }

              return $transfer->vehicle_provider_acente_id
                  || (float) $transfer->external_vehicle_price > 0
                  || trim((string) $transfer->external_vehicle_note) !== '';
          })->values();

          $subcontractedTransferIds = $subcontractedTransfers->pluck('id');
          $vehicleNotRequiredTransfers = $dayTransfers->filter(function ($transfer) use ($subcontractedTransferIds) {
              $hasRealVehicle = $transfer->vehicule && $transfer->vehicule->real && !$transfer->vehicule->sales;
              $requiresRealVehicle = (int) optional($transfer->servicetype)->firma_id === 3;

              return !$hasRealVehicle
                  && !$requiresRealVehicle
                  && !$subcontractedTransferIds->contains($transfer->id);
          })->values();

          $vehicleNotRequiredTransferIds = $vehicleNotRequiredTransfers->pluck('id');
          $unassignedTransfers = $dayTransfers->filter(function ($transfer) use ($subcontractedTransferIds, $vehicleNotRequiredTransferIds) {
              $hasRealVehicle = $transfer->vehicule && $transfer->vehicule->real && !$transfer->vehicule->sales;
              $requiresRealVehicle = (int) optional($transfer->servicetype)->firma_id === 3;

              return !$hasRealVehicle
                  && $requiresRealVehicle
                  && !$subcontractedTransferIds->contains($transfer->id)
                  && !$vehicleNotRequiredTransferIds->contains($transfer->id);
          })->values();

          $usedVehicleIds = $usedVehicles->pluck('id')->values();
          $availableVehicles = $assignableVehicles
              ->filter(fn ($vehicle) => !$usedVehicleIds->contains($vehicle->id) && !$vehicle->enpanne)
              ->values();
          $availableByType = collect($vehicleTypeLabels)
              ->map(fn ($label, $type) => (object) [
                  'type' => $type,
                  'label' => $label,
                  'count' => $availableVehicles->where('planning_type', $type)->count(),
              ])
              ->values();

          $availableByDepot = $depotLabels
              ->map(function ($label, $depotId) use ($assignableVehicles, $availableVehicles, $vehicleTypeLabels) {
                  $depotVehicles = $availableVehicles
                      ->filter(fn ($vehicle) => (int) ($vehicle->depot_id ?? 0) === (int) $depotId)
                      ->values();
                  $panneCount = $assignableVehicles
                      ->filter(fn ($vehicle) => (int) ($vehicle->depot_id ?? 0) === (int) $depotId && (bool) $vehicle->enpanne)
                      ->count();

                  return (object) [
                      'id' => (int) $depotId,
                      'label' => $label,
                      'count' => $depotVehicles->count(),
                      'panne_count' => $panneCount,
                      'types' => collect($vehicleTypeLabels)
                          ->map(fn ($typeLabel, $type) => (object) [
                              'type' => $type,
                              'label' => $typeLabel,
                              'count' => $depotVehicles->where('planning_type', $type)->count(),
                          ])
                          ->values(),
                  ];
              })
              ->values();

          $vehiclesWithoutDepot = $availableVehicles
              ->filter(fn ($vehicle) => empty($vehicle->depot_id))
              ->values();
          $panneWithoutDepot = $assignableVehicles
              ->filter(fn ($vehicle) => empty($vehicle->depot_id) && (bool) $vehicle->enpanne)
              ->count();
          if ($vehiclesWithoutDepot->isNotEmpty() || $panneWithoutDepot > 0) {
              $availableByDepot->push((object) [
                  'id' => 0,
                  'label' => 'Sans dépôt',
                  'count' => $vehiclesWithoutDepot->count(),
                  'panne_count' => $panneWithoutDepot,
                  'types' => collect($vehicleTypeLabels)
                      ->map(fn ($typeLabel, $type) => (object) [
                          'type' => $type,
                          'label' => $typeLabel,
                          'count' => $vehiclesWithoutDepot->where('planning_type', $type)->count(),
                      ])
                      ->values(),
              ]);
          }

          $days->push((object) [
              'date' => $dayStart->copy(),
              'transfers' => $dayTransfers,
              'transfer_count' => $dayTransfers->count(),
              'vehicle_count' => $usedVehicles->count(),
              'unassigned_count' => $unassignedTransfers->count(),
              'unassigned_transfers' => $unassignedTransfers,
              'subcontracted_count' => $subcontractedTransfers->count(),
              'subcontracted_transfers' => $subcontractedTransfers,
              'vehicle_not_required_count' => $vehicleNotRequiredTransfers->count(),
              'vehicle_not_required_transfers' => $vehicleNotRequiredTransfers,
              'available_count' => $availableVehicles->count(),
              'available_by_type' => $availableByType,
              'available_by_depot' => $availableByDepot,
              'vehicles' => $usedVehicles,
          ]);

          $cursor->addDay();
      }

      $maxVehicleCount = max(1, (int) $days->max('vehicle_count'));
      $totalTransfers = (int) $days->sum('transfer_count');
      $totalUnassignedTransfers = (int) $days->sum('unassigned_count');
      $peakDay = $days->sortByDesc('vehicle_count')->first();

      return view('vehicules.planning_futur', compact(
          'days',
          'start',
          'end',
          'totalRealVehicles',
          'maxVehicleCount',
          'totalTransfers',
          'totalUnassignedTransfers',
          'assignableVehicles',
          'vehicleTypeLabels',
          'depotOptions',
          'depotLabels',
          'selectedDepotId',
          'peakDay'
      ));
  }

  public function vehiculescontrol()
  {
      // Default to today's date
      $date = Carbon::today();
      return $this->getTransfersForDate($date);
  }

  public function viewByDate(Request $request)
    {
        $date = Carbon::parse($request->input('date'));
        return $this->getTransfersForDate($date);
    }
    public function viewBySpecificDate($date)
    {
        $date = Carbon::parse($date);
        return $this->getTransfersForDate($date);
    }


    public function getTransfersForDate($date)
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
    
        // Fetch all vehicles with their depot.
        $allVehicles = Vehicule::leftJoin('depots', 'vehicules.depot_id', '=', 'depots.id')
            ->whereNull('vehicules.sales')
            ->where('vehicules.real', true)
            ->orderBy('vehicules.name')
            ->get([
                'vehicules.id',
                'vehicules.name',
                'vehicules.plaka',
                'vehicules.capacity',
                'depots.name as depot_name',
                'depots.code as depot_code',
            ]);
    
        // Fetch transfer data for the specified date.
        $transfers = Transfer::join('vehicules', 'transfers.vehicule_id', '=', 'vehicules.id')
            ->leftJoin('depots', 'vehicules.depot_id', '=', 'depots.id')
            ->whereNull('vehicules.sales')
            ->where('vehicules.real', true)
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->whereBetween('transfers.start_date', [$startOfDay, $endOfDay])
                    ->orWhereBetween('transfers.end_date', [$startOfDay, $endOfDay]);
            })
            ->orderBy('transfers.start_date')
            ->get([
                'transfers.*',
                'vehicules.name as vehicle_name',
                'vehicules.plaka as vehicle_plate',
                'vehicules.capacity as vehicle_capacity',
                'depots.name as depot_name',
                'depots.code as depot_code',
            ]);
    
        // Group transfers by vehicle.
        $usedVehicleIds = $transfers->pluck('vehicule_id')->unique()->map(fn ($id) => (int) $id);
        $vehicles = $transfers->groupBy('vehicule_id')->map(function ($vehicleTransfers) {
            $vehicleTransfers->max_pax = $vehicleTransfers->max(fn ($transfer) => (int) $transfer->pax);
            $vehicleTransfers->transfer_count = $vehicleTransfers->count();
            return $vehicleTransfers;
        });
        $unusedVehicles = $allVehicles->whereNotIn('id', $usedVehicleIds);
    
        // Fetch last usage date for unused vehicles.
        $unusedVehiclesWithLastUsage = $unusedVehicles->mapWithKeys(function ($vehicle) use ($startOfDay) {
            $lastTransfer = Transfer::where('vehicule_id', $vehicle->id)
                ->where('end_date', '<', $startOfDay)
                ->orderBy('end_date', 'desc')
                ->first();

            return [$vehicle->id => [
                'name' => $vehicle->name,
                'plaka' => $vehicle->plaka,
                'capacity' => $vehicle->capacity,
                'depot_name' => $vehicle->depot_name,
                'depot_code' => $vehicle->depot_code,
                'last_used_at' => $lastTransfer ? $lastTransfer->end_date : null,
            ]];
        });
    
        return view('vehicules.vehiculecontrol', compact('vehicles', 'date', 'unusedVehiclesWithLastUsage'));
    }
  
}
