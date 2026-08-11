<?php

namespace App\Http\Controllers;

use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Transfer;
use App\Models\Color;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class VehiculeController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('permission:vehicules.view')->only(['index', 'show', 'vehiculesusage', 'controleDocs', 'storeDocument']);
        $this->middleware('permission:vehicules.create')->only(['create', 'store']);
        $this->middleware('permission:vehicules.update')->only(['edit', 'update', 'guncel', 'destroyDocument']);
        $this->middleware('permission:vehicules.delete')->only(['destroy']);
    }

    public function index()
    {
        $colors = Color::pluck('name', 'id');
        $vehicules = Vehicule::with(['kilometers' => function($query) {
            $query->orderBy('created_at', 'desc');
        }, 'maintenances' => function($query) {
            $query->orderBy('service_date', 'desc');
        }])->get();
        $documentCounts = $this->vehicleDocumentCounts($vehicules);
        return view('vehicules.index', compact('vehicules', 'colors', 'documentCounts'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:100',
        ]);

        // Create the vehicule
        $vehicule = new Vehicule($request->all());
        $vehicule->sales = $request->has('sales') ? 1 : 0; // Set the sales field based on the checkbox
        $vehicule->save();

        return redirect()->route('vehicules.index')
            ->with('flash_message', 'Vehicule, ' . $vehicule->name . ' created');
    }

    public function show(Request $request, $id)
{
    $vehicule = Vehicule::with(['kilometers' => fn($q) => $q->orderBy('created_at', 'desc'),
                                'maintenances' => fn($q) => $q->orderBy('service_date', 'desc')])->findOrFail($id);

    $start_date = $request->input('start_date');
    $end_date = $request->input('end_date');

    $transfers = Transfer::where('vehicule_id', $id)
        ->when($start_date, function ($query, $start_date) {
            return $query->where('start_date', '>=', $start_date);
        })
        ->when($end_date, function ($query, $end_date) {
            return $query->where('end_date', '<=', $end_date);
        })
        ->when(!$start_date && !$end_date, function ($query) {
            return $query->orderBy('start_date', 'desc'); // Order by the most recent transfer if no date filters are applied
        })
        ->get();
      

    $vehicules = Vehicule::whereNull('sales')->get(); // To populate the select dropdown
    $documents = $this->vehicleDocuments($vehicule);

    return view('vehicules.show', compact('vehicule', 'transfers', 'start_date', 'end_date', 'vehicules', 'documents'));
}



    public function edit(Vehicule $vehicule)
    {
        //
    }

    public function guncel(Request $request, Vehicule $vehicule)
    {
        $this->validate($request, [
            'name' => 'required|max:100',
        ]);

        $id = $request->input('id');
        $vehicule = Vehicule::findOrFail($id);
        $vehicule->name = $request->input('name');
        $vehicule->plaka = $request->input('plaka');
        $vehicule->capacity = $request->input('capacity');
        $vehicule->yil = $request->input('yil');
        $vehicule->control       = $request->input('control') ?: null;
        $vehicule->sigorta       = $request->input('sigorta') ?: null;
        $vehicule->ead_date      = $request->input('ead_date') ?: null;
        $vehicule->ext_date      = $request->input('ext_date') ?: null;
        $vehicule->lim_date      = $request->input('lim_date') ?: null;
        $vehicule->tach_date     = $request->input('tach_date') ?: null;
        $vehicule->vid_date      = $request->input('vid_date') ?: null;
        $vehicule->licence_count = $request->input('licence_count') ?: null;
        $vehicule->remarques     = $request->input('remarques') ?: null;
        $vehicule->hermes_uid    = $request->input('hermes_uid');
        $vehicule->sales         = $request->has('sales') ? 1 : NULL;
        $vehicule->real          = $request->has('real') ? 1 : NULL;
        $vehicule->enpanne       = $request->has('enpanne') ? 1 : NULL;
        $vehicule->save();

        return redirect()->route('vehicules.index')
            ->with('flash_message', 'Vehicule, ' . $vehicule->name . ' updated');
    }

    public function destroy(Request $request, $id)
    {
        $varmi = Transfer::where('vehicule_id', $id)->pluck('id', 'post_id');
        $say = count($varmi);
        if ($say > 0) {
            foreach ($varmi as $key => $var) {
                echo "Transfer id:", $key, "File", "<a href=\"", route('posts.show', $var), "\">", $var, "</a><br/>";
            }
        } else {
            $vehicule = Vehicule::findOrFail($id);
            $vehicule->delete();

            return Redirect::back()->with('flash_message', 'delete');
        }
    }

    public function controleDocs()
    {
        $vehicules = Vehicule::where('real', 1)
            ->whereNull('sales')
            ->orderBy('name')
            ->get(['id', 'name', 'plaka', 'yil', 'control', 'sigorta',
                   'ead_date', 'ext_date', 'lim_date', 'tach_date', 'vid_date',
                   'licence_count', 'remarques', 'enpanne']);

        return view('vehicules.controle-docs', compact('vehicules'));
    }

    public function storeDocument(Request $request, Vehicule $vehicule)
    {
        $data = $request->validate([
            'document_type' => 'required|string|max:80',
            'document_file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,webp,heic,doc,docx,xls,xlsx,csv,txt,xml,zip',
        ]);

        $file = $request->file('document_file');
        $directory = $this->vehicleDocumentDirectory($vehicule);
        File::ensureDirectoryExists($directory);

        $type = Str::slug($data['document_type']) ?: 'document';
        $originalBase = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalBase = Str::slug($originalBase) ?: 'fichier';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'file');
        $filename = now()->format('Ymd_His') . '_' . $type . '_' . $originalBase . '.' . $extension;

        $file->move($directory, $filename);

        return redirect()
            ->route('vehicules.show', ['vehicule' => $vehicule->id, 'tab' => 'documents'])
            ->with('flash_message', 'Document ajouté.');
    }

    public function destroyDocument(Vehicule $vehicule, string $filename)
    {
        $filename = basename($filename);
        $path = $this->vehicleDocumentDirectory($vehicule) . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($path)) {
            File::delete($path);
        }

        return redirect()
            ->route('vehicules.show', ['vehicule' => $vehicule->id, 'tab' => 'documents'])
            ->with('flash_message', 'Document supprimé.');
    }

    private function vehicleDocumentCounts($vehicules): array
    {
        return $vehicules->mapWithKeys(function (Vehicule $vehicule) {
            $directory = $this->vehicleDocumentDirectory($vehicule);

            return [$vehicule->id => File::isDirectory($directory) ? count(File::files($directory)) : 0];
        })->all();
    }

    private function vehicleDocuments(Vehicule $vehicule): array
    {
        $directory = $this->vehicleDocumentDirectory($vehicule);

        if (!File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(function ($file) use ($vehicule) {
                $filename = $file->getFilename();
                $type = 'Document';

                if (preg_match('/^\\d{8}_\\d{6}_([^_]+)_/', $filename, $matches)) {
                    $type = Str::headline(str_replace('-', ' ', $matches[1]));
                }

                return [
                    'filename' => $filename,
                    'type' => $type,
                    'name' => $filename,
                    'url' => asset('vehicule_documents/' . $vehicule->id . '/' . rawurlencode($filename)),
                    'extension' => strtoupper($file->getExtension() ?: '-'),
                    'size_kb' => round($file->getSize() / 1024, 1),
                    'uploaded_at' => Carbon::createFromTimestamp($file->getMTime()),
                ];
            })
            ->values()
            ->all();
    }

    private function vehicleDocumentDirectory(Vehicule $vehicule): string
    {
        return public_path('vehicule_documents/' . $vehicule->id);
    }

    public function vehiculesusage(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfMonth();
        $requestedMode = $request->input('mode', 'daily');
        $mode = in_array($requestedMode, ['daily', 'monthly', 'chart'], true) ? $requestedMode : 'daily';
        $vehicleId = $request->input('vehicule_id');

        $allVehicles = Vehicule::query()
            ->where('real', 1)
            ->whereNull('sales')
            ->orderBy('plaka')
            ->orderBy('name')
            ->get(['id', 'name', 'plaka', 'hermes_uid']);

        $vehicles = $vehicleId
            ? $allVehicles->where('id', (int) $vehicleId)->values()
            : $allVehicles;
        $vehicleMap = $vehicles->keyBy('id');
        $vehicleIds = $vehicles->pluck('id')->filter()->values();

        $transferDaily = Transfer::query()
            ->selectRaw('vehicule_id, DATE(start_date) as period_date, COUNT(*) as transfer_count, COALESCE(SUM(km), 0) as planned_km, MIN(start_date) as first_service, MAX(start_date) as last_service')
            ->whereIn('vehicule_id', $vehicleIds)
            ->whereNotNull('vehicule_id')
            ->where('status_id', '<>', 1)
            ->whereBetween(DB::raw('DATE(start_date)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('vehicule_id', DB::raw('DATE(start_date)'))
            ->get()
            ->keyBy(fn ($row) => $row->vehicule_id . '|' . $row->period_date);

        $hermesDaily = DB::table('hermes_daily_stats')
            ->selectRaw('vehicule_id, day as period_date, COALESCE(SUM(distance_km), 0) as real_km, COALESCE(SUM(duration_sec), 0) as duration_sec, MIN(begin_minute) as begin_minute, MAX(end_minute) as end_minute, MAX(max_speed) as max_speed')
            ->whereIn('vehicule_id', $vehicleIds)
            ->whereBetween('day', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('vehicule_id', 'day')
            ->get()
            ->keyBy(fn ($row) => $row->vehicule_id . '|' . $row->period_date);

        $dailyKeys = $transferDaily->keys()->merge($hermesDaily->keys())->unique();
        $dailyRows = $dailyKeys->map(function ($key) use ($transferDaily, $hermesDaily, $vehicleMap) {
            [$vehiculeId, $day] = explode('|', $key);
            $vehicle = $vehicleMap->get((int) $vehiculeId);
            $transfer = $transferDaily->get($key);
            $hermes = $hermesDaily->get($key);
            $plannedKm = (float) ($transfer->planned_km ?? 0);
            $realKm = (float) ($hermes->real_km ?? 0);

            return (object) [
                'vehicule_id' => (int) $vehiculeId,
                'date' => $day,
                'vehicule_name' => $vehicle?->name ?? 'Véhicule supprimé',
                'plaka' => $vehicle?->plaka,
                'has_hermes' => !empty($vehicle?->hermes_uid),
                'transfer_count' => (int) ($transfer->transfer_count ?? 0),
                'planned_km' => $plannedKm,
                'real_km' => $realKm,
                'gap_km' => $realKm - $plannedKm,
                'duration_sec' => (int) ($hermes->duration_sec ?? 0),
                'begin_minute' => $hermes->begin_minute ?? null,
                'end_minute' => $hermes->end_minute ?? null,
                'max_speed' => $hermes->max_speed ?? null,
                'first_service' => $transfer->first_service ?? null,
                'last_service' => $transfer->last_service ?? null,
            ];
        })->sortByDesc(fn ($row) => $row->date . '-' . str_pad($row->vehicule_id, 6, '0', STR_PAD_LEFT))->values();

        $monthlyRows = $dailyRows
            ->groupBy(fn ($row) => $row->vehicule_id . '|' . Carbon::parse($row->date)->format('Y-m'))
            ->map(function ($rows, $key) {
                [$vehiculeId, $month] = explode('|', $key);
                $first = $rows->first();
                $transferCount = (int) $rows->sum('transfer_count');
                $plannedKm = (float) $rows->sum('planned_km');
                $realKm = (float) $rows->sum('real_km');
                $durationSec = (int) $rows->sum('duration_sec');
                $usedDays = $rows->filter(fn ($row) => $row->transfer_count > 0 || $row->real_km > 0)->count();
                $hermesDays = $rows->filter(fn ($row) => $row->real_km > 0)->count();
                $serviceDays = $rows->filter(fn ($row) => $row->transfer_count > 0)->count();

                return (object) [
                    'vehicule_id' => (int) $vehiculeId,
                    'month' => $month,
                    'vehicule_name' => $first->vehicule_name,
                    'plaka' => $first->plaka,
                    'has_hermes' => $first->has_hermes,
                    'transfer_count' => $transferCount,
                    'active_days' => $usedDays,
                    'hermes_days' => $hermesDays,
                    'service_days' => $serviceDays,
                    'planned_km' => $plannedKm,
                    'real_km' => $realKm,
                    'gap_km' => $realKm - $plannedKm,
                    'avg_real_km_day' => $usedDays > 0 ? $realKm / $usedDays : 0,
                    'avg_real_km_transfer' => $transferCount > 0 ? $realKm / $transferCount : 0,
                    'duration_sec' => $durationSec,
                    'max_speed' => $rows->max('max_speed'),
                ];
            })
            ->sortByDesc(fn ($row) => $row->month . '-' . str_pad($row->vehicule_id, 6, '0', STR_PAD_LEFT))
            ->values();

        $chartRows = $monthlyRows->sortByDesc('real_km')->take(20)->values();
        $chartData = [
            'labels' => $chartRows->map(fn ($row) => trim(($row->plaka ?: 'Sans plaque') . ' - ' . $row->vehicule_name))->values(),
            'usedDays' => $chartRows->pluck('active_days')->map(fn ($value) => (int) $value)->values(),
            'realKm' => $chartRows->pluck('real_km')->map(fn ($value) => round((float) $value, 1))->values(),
            'plannedKm' => $chartRows->pluck('planned_km')->map(fn ($value) => round((float) $value, 1))->values(),
        ];

        $totals = [
            'vehicles' => $vehicleMap->count(),
            'daily_rows' => $dailyRows->count(),
            'monthly_rows' => $monthlyRows->count(),
            'transfers' => $dailyRows->sum('transfer_count'),
            'planned_km' => $dailyRows->sum('planned_km'),
            'real_km' => $dailyRows->sum('real_km'),
            'duration_sec' => $dailyRows->sum('duration_sec'),
        ];

        return view('vehicules.vehicle-usage', compact(
            'vehicles',
            'allVehicles',
            'dailyRows',
            'monthlyRows',
            'chartData',
            'totals',
            'startDate',
            'endDate',
            'mode',
            'vehicleId'
        ));
    }

}
