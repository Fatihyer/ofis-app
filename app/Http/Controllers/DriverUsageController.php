<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transfer;
use App\Models\Acente;
use Carbon\Carbon;
USE App\Models\Option;
use DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;

class DriverUsageController extends Controller
{
    private function driverTransfersQuery($driverId)
    {
        return Transfer::query()->where(function ($query) use ($driverId) {
            $query->where('driver_id', $driverId);
            if (Schema::hasColumn('transfers', 'second_driver_id')) {
                $query->orWhere('second_driver_id', $driverId);
            }
        });
    }
 
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('permission:transfers.operations');
      }
    public function index()
    {
        // Default to today's date
        $date = Carbon::today();
        return $this->getboardTransfersForDate($date);
    }
    public function boardindex()
    {
        // Default to today's date
        $date = Carbon::today();
        return $this->getboardTransfersForDate($date);
    }

    public function viewByDate(Request $request)
    {
        $date = Carbon::parse($request->input('date'));
        Session::put('start_date', $request->input('start_date'));
        return $this->getTransfersForDate($date);
    }
    public function boardviewByDate(Request $request)
    {
        $date = Carbon::parse($request->input('date'));
        Session::put('start_date', $request->input('start_date'));
        return $this->getboardTransfersForDate($date);
    }

    public function viewBySpecificDate($date)
    {
        $date = Carbon::parse($date);
        return $this->getTransfersForDate($date);
    }
    public function boardviewBySpecificDate($date)
    {
        $date = Carbon::parse($date);
        return $this->getboardTransfersForDate($date);
    }
    private function getTransfersForDate($date)
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Fetch all acentes where suivi = true
        $allAcentes = Acente::where('suivi', true)->pluck('name', 'id');

        // Fetch transfer data for the specified date along with acente names
        $transfers = Transfer::with('driver','serviceType','trajets','vehicule')
                             ->where(function($query) use ($startOfDay, $endOfDay) {
                                 $query->whereBetween('start_date', [$startOfDay, $endOfDay])
                                       ->orWhereBetween('end_date', [$startOfDay, $endOfDay]);
                             })
                             ->orderBy('start_date')
                             ->get(); 

        // Group transfers by acente
       
        $usedAcenteIds = $transfers->pluck('driver.id')->unique();
        $unusedAcentes = $allAcentes->filter(function ($name, $id) use ($usedAcenteIds) {
            return !$usedAcenteIds->contains($id);
        });
        $acentes = $transfers->groupBy('driver.name');

        // Determine unused acentes
       

        return view('driver-usage.index', compact('acentes', 'unusedAcentes', 'date'));
    }
    private function getBoardTransfersForDate($date)
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Fetch all acentes where suivi = true
        $allAcentes = Acente::where('suivi', true)->pluck('name', 'id');

        // Fetch transfer data for the specified date along with acente names
        $transfers = Transfer::with('driver','serviceType','trajets','vehicule')
                             ->where(function($query) use ($startOfDay, $endOfDay) {
                                 $query->whereBetween('start_date', [$startOfDay, $endOfDay])
                                       ->orWhereBetween('end_date', [$startOfDay, $endOfDay]);
                             })
                             ->orderBy('start_date')
                             ->get(); 

        // Group transfers by acente
       
        $usedAcenteIds = $transfers->pluck('driver.id')->unique();
        $unusedAcentes = $allAcentes->filter(function ($name, $id) use ($usedAcenteIds) {
            return !$usedAcenteIds->contains($id);
        });
        $acentes = $transfers->groupBy('driver.name');

        // Determine unused acentes
       

        return view('driver-usage.driverindex', compact('acentes', 'unusedAcentes', 'date'));
    }



        public function getDriverWorkDays()
        {
            $today = Carbon::now();

        // Suivi true olan sürücüleri alın
        $drivers = Acente::where('suivi', true)->get();

       
        foreach ($drivers as $driver) {
            $driverId = $driver->id;

            // Sürücünün en son çalıştığı tarihi alın
            $lastWorkedDate = $this->driverTransfersQuery($driverId)
                ->where('servicetype_id', '!=', 63)
                ->max('start_date');

            if ($lastWorkedDate) {
                $lastWorkedDate = Carbon::parse($lastWorkedDate);

                // En son çalışmadığı günü bul
                $currentDate = $lastWorkedDate->copy();
                while ($currentDate->greaterThanOrEqualTo(Carbon::parse($lastWorkedDate)->subDays(30))) {
                    $worked = $this->driverTransfersQuery($driverId)
                        ->whereDate('start_date', '=', $currentDate->toDateString())
                        ->where('servicetype_id', '!=', 63)
                        ->exists();

                    if (!$worked) {
                        break;
                    }
                    $currentDate->subDay();
                }

                // En son çalışmadığı günden bugüne kadar olan tarih aralığını oluşturun
                $periodStart = $currentDate->copy()->addDay();
                $periodEnd = $today;
                $daysWorked = 0;

                // Tüm günleri almak için manuel olarak bir tarih aralığı oluşturun
                $currentDate = $periodStart->copy();
                while ($currentDate->lte($periodEnd)) {
                    $worked = $this->driverTransfersQuery($driverId)
                        ->whereDate('start_date', '=', $currentDate->toDateString())
                        ->where('servicetype_id', '!=', 63)
                        ->exists();

                    if ($worked) {
                        $daysWorked++;
                    }
                    $currentDate->addDay();
                }

                $driverWorkDays[] = [
                    'driver_id' => $driverId,
                    'driver_name' => $driver->name,
                    'days_worked' => $daysWorked,
                ];
            } else {
                // Eğer sürücü hiç çalışmadıysa, gün sayısı 0 olsun
                $driverWorkDays[] = [
                    'driver_id' => $driverId,
                    'driver_name' => $driver->name,
                    'days_worked' => 0,
                ];
            }
        }
    
        return response()->json(['driverWorkDays' => $driverWorkDays]);
        }  


        public function getDriverHours(Request $request, $driverId)
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(15);

        $hoursWorked = $this->driverTransfersQuery($driverId)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->where('servicetype_id', '!=', 63)
            ->select(
                DB::raw('DATE(start_date) as date'),
                DB::raw('SUM(TIMESTAMPDIFF(HOUR, start_date, end_date)) as hours_worked')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->keyBy('date');

        // Fill in the missing days with "congé"
        $allDays = collect();
        for ($date = $endDate->copy(); $date->gte($startDate); $date->subDay()) {
            $formattedDate = $date->format('Y-m-d');
            if (isset($hoursWorked[$formattedDate])) {
                $allDays->put($formattedDate, $hoursWorked[$formattedDate]->hours_worked . ' hours');
            } else {
                $allDays->put($formattedDate, 'congé');
            }
        }

        $result = $allDays->map(function ($item, $key) {
            return ['date' => $key, 'hours_worked' => $item];
        })->values();

        return response()->json(['hoursWorked' => $result]);
    }


    public function getDriverMonthlyCalendar(Request $request, $driverId, $year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $hoursWorked = $this->driverTransfersQuery($driverId)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->where('servicetype_id', '!=', 63)
            ->select(
                DB::raw('DATE(start_date) as date'),
                DB::raw('SUM(TIMESTAMPDIFF(HOUR, start_date, end_date)) as hours_worked')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->keyBy('date');

        $daysInMonth = $startDate->daysInMonth;
        $calendar = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startDate->copy()->day($day)->format('Y-m-d');
            $calendar[$date] = isset($hoursWorked[$date]) ? $hoursWorked[$date]->hours_worked . ' hours' : 'congé';
        }

        return response()->json(['calendar' => $calendar]);
    }

    private function mergedWorkingMinutes(array $intervals): int
    {
        if (empty($intervals)) {
            return 0;
        }

        usort($intervals, function ($a, $b) {
            return $a[0]->timestamp <=> $b[0]->timestamp;
        });

        $merged = [];
        foreach ($intervals as [$start, $end]) {
            if (empty($merged)) {
                $merged[] = [$start->copy(), $end->copy()];
                continue;
            }

            $lastIndex = count($merged) - 1;
            if ($start->lte($merged[$lastIndex][1])) {
                if ($end->gt($merged[$lastIndex][1])) {
                    $merged[$lastIndex][1] = $end->copy();
                }
                continue;
            }

            $merged[] = [$start->copy(), $end->copy()];
        }

        return collect($merged)->sum(function ($interval) {
            return $interval[1]->diffInMinutes($interval[0]);
        });
    }

    public function removeSuivi(Acente $acente)
    {
        $acente->update(['suivi' => 0]);

        return back()->with('flash_message', 'Chauffeur retiré du suivi.');
    }

    public function heuredetravail($weekOffset = 0)
    {
        $gecmisgun = 7;
        $startDate = Carbon::tomorrow()->subDays($gecmisgun + ($weekOffset * 7))->startOfDay();
        $endDate = Carbon::tomorrow()->subDays($gecmisgun + ($weekOffset * 7))->endOfDay();
        $ilkgun = Carbon::tomorrow()->subDays($gecmisgun + ($weekOffset * 7))->startOfDay();
        $driverNamesById = Acente::where('suivi', 1)
            ->orderBy('name')
            ->pluck('name', 'id');
        $drivers = $driverNamesById->keys()->toArray();
        $driversay = count($drivers);
        $dayNumber = 0;
        $totalWorkingTime = [];
        $drivername = [];
        $dayNames = [];
        $notWorkedCounts = [];
        $workDetails = [];
        $congeValuesString = Option::where('name', 'conge')->value('value');
        $excludedServiceTypeIds = collect(explode(',', (string) $congeValuesString))
            ->map(fn ($item) => (int) trim($item))
            ->filter()
            ->values()
            ->all();
        if (empty($excludedServiceTypeIds)) {
            $excludedServiceTypeIds = [63];
        }

        // Array to manually translate day names to French
        $dayTranslations = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche',
        ];

        while ($dayNumber <= $gecmisgun) {
            $x = 0;
            while ($x < $driversay) {
                $driverId = $drivers[$x];
                $totalWorkingTime[$driverId][$dayNumber] = 0;
                $workDetails[$driverId][$dayNumber] = [];

                $transfers = $this->driverTransfersQuery($driverId)
                    ->with(['vehicule', 'missionr', 'servicetype'])
                    ->where('start_date', '>=', $startDate)
                    ->whereNotIn('servicetype_id', $excludedServiceTypeIds)
                    ->where('start_date', '<=', $endDate)
                    ->orderBy('driver_id')
                    ->orderBy('start_date')
                    ->get();

                $drivername[$driverId] = $driverNamesById[$driverId] ?? 'Chauffeur';
                $workingIntervals = [];
                $vanTransfers = [];
                $amplitudeSources = [];

                foreach ($transfers as $key => $value) {
                    $mission = $value->missionr;
                    $startValue = $value->ofis_start ?: ($mission && $mission->hareket ? $mission->hareket : $value->start_date);
                    $endValue = $mission && $mission->finish ? $mission->finish : $value->end_date;

                    $start = Carbon::parse($startValue);
                    $end = Carbon::parse($endValue);
                    if ($end->lt($start)) {
                        continue;
                    }

                    $workingIntervals[] = [$start, $end];
                    $vehicle = $value->vehicule;
                    $hasHermes = $vehicle && !empty($vehicle->hermes_uid);
                    $vehicleName = $vehicle ? trim(($vehicle->plaka ?: '').' '.($vehicle->name ?: '')) : '';

                    if ($vehicle && !$hasHermes && (int)($vehicle->real ?? 0) === 1) {
                        $vanTransfers[] = '#'.$value->id.' '.($vehicleName ?: 'Véhicule sans Hermes');
                    }

                    $amplitudeSources[] = '#'.$value->id.' '.Carbon::parse($startValue)->format('H:i').'-'.Carbon::parse($endValue)->format('H:i');
                }

                $totalWorkingTime[$driverId][$dayNumber] = $this->mergedWorkingMinutes($workingIntervals);
                if (!empty($vanTransfers) || !empty($amplitudeSources)) {
                    $workDetails[$driverId][$dayNumber] = [
                        'vans' => array_values(array_unique($vanTransfers)),
                        'amplitudes' => $amplitudeSources,
                    ];
                }
                $x++;
            }
            $dayOfWeek = Carbon::parse($ilkgun)->addDays($dayNumber)->format('l');
            $dayNames[$dayNumber] = $dayTranslations[$dayOfWeek] . ', ' . Carbon::parse($ilkgun)->addDays($dayNumber)->format('d-m-Y');
            $startDate->addDay();
            $endDate->addDay();
            $dayNumber++;
        }

        // Count the number of days each driver did not work
        foreach ($totalWorkingTime as $driverId => $daysWorked) {
            $notWorkedCounts[$driverId] = 0;
            foreach ($daysWorked as $workedHours) {
                if ($workedHours == 0) {
                    $notWorkedCounts[$driverId]++;
                }
            }
        }

        return view('transfert.heuredetravail', compact('drivername', 'totalWorkingTime', 'workDetails', 'gecmisgun', 'ilkgun', 'weekOffset', 'dayNames', 'notWorkedCounts'));
    }

    public function monthlyCalendar(Request $request)
    {
        $driverId = $request->input('driver_id');
        $month = $request->input('month', Carbon::now()->format('m'));
        $year = $request->input('year', Carbon::now()->format('Y'));

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $driver = Acente::find($driverId);
        $daysInMonth = $startDate->daysInMonth;

        $workingDays = [];
        $notWorkingDays = [];

        // 'conge' option'unun value'sunu al ve diziye çevir
        $congeValuesString = Option::where('name', 'conge')->value('value');
        $excludedServiceTypeIds = collect(explode(',', $congeValuesString))->map(function ($item) {
            return (int) trim($item); // boşlukları silip integer'a çevir
        })->toArray();

        // Initialize all days in the month as not working days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $notWorkingDays[] = $startDate->copy()->addDays($day - 1)->format('Y-m-d');
        }

        $transfers = $this->driverTransfersQuery($driverId)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->whereNotIn('servicetype_id', $excludedServiceTypeIds)
            ->orderBy('start_date')
            ->get();

        foreach ($transfers as $transfer) {
            $workingDay = Carbon::parse($transfer->start_date)->format('Y-m-d');
            if (($key = array_search($workingDay, $notWorkingDays)) !== false) {
                unset($notWorkingDays[$key]);
            }
            $workingDays[] = $workingDay;
        }

        $notWorkingDays = array_values($notWorkingDays);

        return view('transfert.monthlyCalendar', compact('driver', 'notWorkingDays', 'workingDays', 'month', 'year', 'daysInMonth'));
    }


    public function showDriverCalendar(Request $request)
{
    $month = $request->input('month');
    $year = $request->input('year', date('Y')); // Default to the current year if not provided
    $driverId = $request->input('driver_id');

    // 'conge' option'unun value'sunu al ve diziye çevir
    $congeValuesString = Option::where('name', 'conge')->value('value');
    $excludedServiceTypeIds = collect(explode(',', $congeValuesString))->map(function ($item) {
        return (int) trim($item); // boşlukları silip integer'a çevir
    })->toArray();

    // Fetch drivers as an associative array
    $drivers = Acente::where('suivi', 1)
        ->select('id', 'name')
        ->get()
        ->pluck('name', 'id')
        ->toArray();

    $workTimeQuery = Transfer::with('driver')
        ->select(
            'driver_id',
            DB::raw('DATE(start_date) as date'),
            DB::raw('SUM(TIMESTAMPDIFF(MINUTE, start_date, end_date)) as total_minutes')
        )
        ->when($driverId, function ($query, $driverId) {
            return $query->where(function ($driverQuery) use ($driverId) {
                $driverQuery->where('driver_id', $driverId);
                if (Schema::hasColumn('transfers', 'second_driver_id')) {
                    $driverQuery->orWhere('second_driver_id', $driverId);
                }
            });
        })
        ->when($month, function ($query, $month) use ($year) {
            return $query->whereYear('start_date', $year)
                         ->whereMonth('start_date', $month);
        })
        ->whereNotIn('servicetype_id', $excludedServiceTypeIds)
        ->whereNotNull('driver_id')
        ->groupBy('driver_id', DB::raw('DATE(start_date)'));

    $workTime = $workTimeQuery->get();

    // Calculate worked and not worked days
    $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
    $workedDates = $workTime->pluck('date')->unique()->filter(function ($date) use ($month, $year) {
        $parsedDate = Carbon::parse($date);
        return $parsedDate->month == $month && $parsedDate->year == $year;
    });
    $workedDays = $workedDates->count();
    $notWorkedDays = $daysInMonth - $workedDays;

    // Ensure notWorkedDays is not negative
    if ($notWorkedDays < 0) {
        $notWorkedDays = 0;
    }

    // Process data for the calendar view
    $events = $workTime->map(function ($item) {
        $driverName = $item->driver ? $item->driver->name : 'Unknown Driver';
        $hours = floor($item->total_minutes / 60);
        $minutes = $item->total_minutes % 60;
        return [
            'title' => "{$driverName}: {$hours}h {$minutes}m",
            'start' => $item->date,
            'allDay' => true,
             'color' => '#28a745', // Green color for worked days
            'textColor' => '#fff', // White text color
            'description' => "Driver: {$driverName}, Worked: {$hours}h {$minutes}m",
        ];
    });

    return view('driver.daily_work_time', [
        'events' => $events,
        'drivers' => $drivers,
        'selectedMonth' => $month,
        'selectedYear' => $year,
        'selectedDriverId' => $driverId,
        'workedDays' => $workedDays,
        'notWorkedDays' => $notWorkedDays,
    ]);
}
    
}
