<?php

namespace App\Http\Controllers;

use App\Services\HermesApiService;
use App\Services\HermesEngineAlertService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HermesController extends Controller
{
    public function engineAlerts(HermesEngineAlertService $alerts): JsonResponse
    {
        $user = Auth::user();
        $canSeeHermesAlerts = $user && (
            $user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport'])
            || $user->hasAnyPermission(['hermes.alerts', 'hermes.view', 'ofis', 'transport'])
        );

        if (!$canSeeHermesAlerts) {
            return response()->json(['messages' => []]);
        }

        $hermesAlerts = $alerts->alerts(false);

        return response()->json([
            'alerts' => $hermesAlerts,
            'messages' => array_map(function ($alert) {
                return is_array($alert) ? ($alert['message'] ?? '') : (string) $alert;
            }, $hermesAlerts),
        ]);
    }

    /**
     * 1) Hermes araç listesi (/units) + DB vehicules eşleşmesi (vehicules.hermes_uid)
     */
    public function getVehicles(HermesApiService $hermes)
    {
        $raw = $hermes->get('/units');

        // API bazen {"data": [...]} şeklinde sarmalı döner
        if (is_array($raw) && isset($raw['data']) && is_array($raw['data'])) {
            $vehicles = $raw['data'];
        } elseif (is_array($raw) && array_is_list($raw)) {
            $vehicles = $raw;
        } else {
            $vehicles = [];
        }

        $uids = collect($vehicles)
            ->pluck('uid')
            ->filter()
            ->values()
            ->all();

        $vehiculeMap = DB::table('vehicules')
            ->whereIn('hermes_uid', $uids)
            ->select('id', 'name', 'plaka', 'hermes_uid')
            ->get()
            ->keyBy('hermes_uid'); // KEY = hermes_uid

        return view('hermes.index', [
            'vehicles' => $vehicles,
            'vehiculeMap' => $vehiculeMap,
        ]);
    }

    /**
     * 2) Araç detay / last_position
     */
    public function getVehicleLocation(string $uid, HermesApiService $hermes): JsonResponse
    {
        $data = $hermes->get("/units/{$uid}");

        if (!$data || !isset($data['last_position'])) {
            return response()->json(['error' => 'Konum bilgisi yok'], 404);
        }

        return response()->json([
            'latitude'    => $data['last_position']['latitude'] ?? null,
            'longitude'   => $data['last_position']['longitude'] ?? null,
            'status'      => $data['last_position']['status']['label'] ?? null,
            'status_code' => $data['last_position']['status']['status'] ?? null,
            'date'        => $data['last_position']['date'] ?? null,
        ]);
    }

    /**
     * 3) Filonun raporu (QUERY param: ?day=YYYY-MM-DD)
     * Default: Paris dün
     */
    public function fleetDayView(Request $request, HermesApiService $hermes)
    {
        $tz = 'Europe/Paris';
        $dayKey = $request->query('day')
            ? Carbon::parse($request->query('day'), $tz)->toDateString()
            : Carbon::yesterday($tz)->toDateString();

        // Geçmiş günler için API yerine DB arşivini kullan
        if ($dayKey < Carbon::today($tz)->toDateString()) {
            return redirect()->route('hermes.fleet.db', ['day' => $dayKey]);
        }

        return $this->fleetDayCore($hermes, $dayKey);
    }

    /**
     * 4) Filonun raporu (dün sabit) — DB arşivine yönlendir
     */
    public function fleetYesterdayView(HermesApiService $hermes)
    {
        $tz = 'Europe/Paris';
        $dayKey = Carbon::yesterday($tz)->toDateString();
        return redirect()->route('hermes.fleet.db', ['day' => $dayKey]);
    }

    /**
     * 5) Track-info raw debug
     * /hermes/debug/track/{unitUid}
     */
    public function debugUnit(string $unitUid, HermesApiService $hermes): JsonResponse
{
    $unit = $hermes->get("/units/{$unitUid}");

    // büyük olabilir, kritik alanları ayıklayalım
    $resource = $unit['resource'] ?? null;
    $last = $unit['last_position'] ?? null;

    return response()->json([
        'unitUid' => $unitUid,
        'has_resource' => !empty($resource),
        'resource' => $resource,                 // burada uid/id/name var mı bak
        'last_position_status' => $last['status'] ?? null,
        'last_position_date' => $last['date'] ?? null,
        'keys' => is_array($unit) ? array_keys($unit) : null,
        'raw_sample' => is_array($unit) ? array_slice($unit, 0, 0) : null, // boş bırakıyorum
    ]);
}

public function debugUnitsList(HermesApiService $hermes): JsonResponse
{
    $units = $hermes->get('/units');
    if (!is_array($units)) $units = [];

    // ilk 5 taneyi küçük örnek yapalım
    $sample = array_slice($units, 0, 5);

    $mapped = array_map(function($u){
        return [
            'uid' => $u['uid'] ?? null,
            'name' => $u['name'] ?? null,
            'immat' => $u['immat'] ?? null,
            'resource' => $u['resource'] ?? null, // burada gelir mi?
            'last_status' => $u['last_position']['status']['status'] ?? null,
            'last_date' => $u['last_position']['date'] ?? null,
        ];
    }, $sample);

    return response()->json([
        'count' => count($units),
        'raw' => $units,
        'sample' => $mapped,
    ]);
}

public function debugTrackInfo(string $unitUid, HermesApiService $hermes): JsonResponse
{
    $track = $hermes->get("/units/{$unitUid}/track-info");
    if (!is_array($track)) $track = [];

    // liste mi?
    $isList = array_keys($track) === range(0, count($track) - 1);

    // ilk 3 gün örneği
    $sample = $isList ? array_slice($track, 0, 3) : $track;

    // resUid/resId dolu mu kontrol için hızlı sayım
    $resUidCount = 0;
    $resIdCount = 0;
    if ($isList) {
        foreach ($track as $row) {
            if (!is_array($row)) continue;
            if (!empty($row['resUid'])) $resUidCount++;
            if (!empty($row['resId'])) $resIdCount++;
        }
    }

    return response()->json([
        'unitUid' => $unitUid,
        'type' => $isList ? 'list' : 'assoc',
        'rows' => count($track),
        'resUid_non_null_rows' => $resUidCount,
        'resId_non_null_rows' => $resIdCount,
        'sample' => $sample,
        'note' => 'Row icinde resUid/resId veya events icinde resource var mi bak',
    ]);
}

    public function debugTrackRaw(string $unitUid, HermesApiService $hermes): JsonResponse
    {
        $track = $hermes->get("/units/{$unitUid}/track-info");

        $keys = is_array($track) ? array_keys($track) : null;

        $sample = null;
        if (is_array($track)) {
            // numerik array ise ilk 2 eleman
            if (array_keys($track) === range(0, count($track) - 1)) {
                $sample = array_slice($track, 0, 2);
            } else {
                // assoc ise ilk 3 key
                $firstKeys = array_slice($keys, 0, 3);
                $sample = [];
                foreach ($firstKeys as $k) $sample[$k] = $track[$k];
            }
        }

        return response()->json([
            'unitUid' => $unitUid,
            'type' => gettype($track),
            'top_level_keys' => $keys,
            'sample' => $sample,
        ]);
    }

    /**
     * 6) Hermes resources listeleme (pagination ile hepsini çeker) + linkli acente isimleri
     */
    public function getResources(HermesApiService $hermes)
    {
        $all = [];

        $offset = 0;
        $limit = 200;

        while (true) {
            $chunk = $hermes->get('/resources', [
                'offset' => $offset,
                'limit' => $limit,
            ]);

            if (!is_array($chunk) || count($chunk) === 0) break;

            $all = array_merge($all, $chunk);

            if (count($chunk) < $limit) break;

            $offset += $limit;
            if ($offset > 50000) break;
        }

        // aktif linkler + acente adı (tek query)
        $linkedRows = DB::table('hermes_resource_links as l')
            ->leftJoin('acentes as a', 'a.id', '=', 'l.acente_id')
            ->where('l.active', 1)
            ->select([
                'l.hermes_resource_uid as uid',
                'l.acente_id as acente_id',
                DB::raw('COALESCE(a.name, "") as acente_name'),
            ])
            ->get();

        // uid => acente_id
        $uidToAcenteId = $linkedRows->pluck('acente_id', 'uid');

        // uid => "#id — name"
        $uidToAcenteLabel = $linkedRows->mapWithKeys(function ($r) {
            $name = trim((string) $r->acente_name);
            return [$r->uid => $name !== '' ? "#{$r->acente_id} — {$name}" : "#{$r->acente_id}"];
        });

        usort($all, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        return view('hermes.resources', [
            'resources' => $all,
            'count' => count($all),
            'uidToAcenteId' => $uidToAcenteId,
            'uidToAcenteLabel' => $uidToAcenteLabel,
            'pagination' => [
                'offset' => $offset,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * 7) /resources/{uid}/working-time (deprécié) - dünkü deneme (Paris)
     * /hermes/resources/{resUid}/working-time-yesterday
     */
    public function resourceYesterdayWorkingTime(string $resUid, HermesApiService $hermes): JsonResponse
    {
        $tz = 'Europe/Paris';
        $dayKey = Carbon::yesterday($tz)->toDateString();

        $data = $hermes->get("/resources/{$resUid}/working-time", [
            'day' => $dayKey,
        ]);

        if (empty($data)) {
            $data = $hermes->get("/resources/{$resUid}/working-time");
        }

        $filtered = $data;
        if (is_array($data)) {
            $isList = array_keys($data) === range(0, count($data) - 1);
            if ($isList) {
                $filtered = array_values(array_filter($data, function ($row) use ($dayKey) {
                    if (!is_array($row)) return false;
                    return ($row['day'] ?? $row['date'] ?? null) === $dayKey;
                }));
            }
        }

        return response()->json([
            'resUid' => $resUid,
            'day' => $dayKey,
            'raw' => $data,
            'filtered' => $filtered,
        ]);
    }

    /**
     * 8) Hermes Resource ↔ Acente link kaydet
     */
    public function linkHermesResource(Request $request)
    {
        $data = $request->validate([
            'acente_id' => 'required|integer',
            'hermes_resource_uid' => 'required|string|max:64',
        ]);

        $acenteId = (int) $data['acente_id'];
        $uid = $data['hermes_resource_uid'];

        DB::transaction(function () use ($acenteId, $uid) {

            // UID başka acente'ye bağlıysa pasif et
            DB::table('hermes_resource_links')
                ->where('hermes_resource_uid', $uid)
                ->update([
                    'active' => 0,
                    'updated_at' => now(),
                ]);

            // Acente'nin eski aktif linkini pasif et
            DB::table('hermes_resource_links')
                ->where('acente_id', $acenteId)
                ->where('active', 1)
                ->update([
                    'active' => 0,
                    'updated_at' => now(),
                ]);

            // Yeni aktif link insert (tarihçe kalsın)
            DB::table('hermes_resource_links')->insert([
                'acente_id' => $acenteId,
                'hermes_resource_uid' => $uid,
                'active' => 1,
                'linked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', "Eşleşme kaydedildi: acente #{$acenteId} ↔ {$uid}");
    }

    /**
     * 9) Hermes Resource ↔ Acente link kaldır (aktif=0)
     */
    public function unlinkHermesResource(Request $request)
    {
        $data = $request->validate([
            'acente_id' => 'required|integer',
        ]);

        $acenteId = (int) $data['acente_id'];

        DB::table('hermes_resource_links')
            ->where('acente_id', $acenteId)
            ->where('active', 1)
            ->update([
                'active' => 0,
                'updated_at' => now(),
            ]);

        return back()->with('success', "Eşleşme kaldırıldı: acente #{$acenteId}");
    }

    /**
     * 10) Ajax acente arama (Select2)
     * /hermes/acentes/search?q=...
     */
    public function searchAcentes(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('acentes')
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        $results = $rows->map(fn($r) => [
            'id' => $r->id,
            'text' => "#{$r->id} — {$r->name}",
        ])->values();

        return response()->json(['results' => $results]);
    }

    /* =========================================================
     *  PRIVATE: Fleet core + helpers
     * ========================================================= */

    private function fleetDayCore(HermesApiService $hermes, string $dayKey)
    {
        $tz = 'Europe/Paris';

        $vehicles = DB::table('vehicules')
            ->whereNotNull('hermes_uid')
            ->select(['id','name','plaka','hermes_uid'])
            ->orderBy('name')
            ->get();

        $results = [];
        $trackCache = [];

        foreach ($vehicles as $v) {
            $unitUid = $v->hermes_uid;

            if (!isset($trackCache[$unitUid])) {
                $trackCache[$unitUid] = $hermes->get("/units/{$unitUid}/track-info");
            }

            $trackRaw = $trackCache[$unitUid];
            $dayRow = $this->findTrackDayRowAnyShape($trackRaw, $dayKey);

            $availableDays = [];
            if (is_array($trackRaw)) {
                $rows = $this->extractTrackDayRows($trackRaw);
                $availableDays = array_values(array_unique(array_filter(array_map(fn($r) => $r['day'] ?? null, $rows))));
                sort($availableDays);
            }

            if (!$dayRow) {
                $results[] = [
                    'vehicule' => $this->formatVehiculeRow($v),
                    'date' => $dayKey,
                    'data_unavailable' => true,
                    'distance_km' => null,
                    'driving_seconds' => null,
                    'first_move' => null,
                    'last_move' => null,
                    'max_speed' => null,
                    'available_days' => $availableDays,
                    'reason' => in_array($dayKey, $availableDays, true)
                        ? 'track-info bu günü listeliyor ama row bulunamadı (shape farklı olabilir)'
                        : 'retention: track-info bu tarihi vermiyor',
                ];
                continue;
            }

            $results[] = [
                'vehicule' => $this->formatVehiculeRow($v),
                'date' => $dayKey,
                'data_unavailable' => false,
                'distance_km' => $dayRow['distance'] ?? null,
                'driving_seconds' => $dayRow['duration'] ?? null,
                'first_move' => $this->minuteOfDayToParisDateTime($dayKey, $dayRow['beginDay'] ?? null, $tz),
                'last_move'  => $this->minuteOfDayToParisDateTime($dayKey, $dayRow['endDay'] ?? null, $tz),
                'max_speed' => $dayRow['maxSpeed'] ?? null,
                'available_days' => $availableDays,
                'reason' => null,
            ];
        }

        $totalKm = 0.0;
        $totalSec = 0;
        $unavailable = 0;

        foreach ($results as $r) {
            if ($r['data_unavailable']) { $unavailable++; continue; }
            if (is_numeric($r['distance_km'])) $totalKm += (float)$r['distance_km'];
            if (is_numeric($r['driving_seconds'])) $totalSec += (int)$r['driving_seconds'];
        }

        return view('hermes.fleet_yesterday', [
            'date' => $dayKey,
            'results' => $results,
            'summary' => [
                'vehicle_count' => count($results),
                'unavailable_count' => $unavailable,
                'total_km' => round($totalKm, 1),
                'total_hhmm' => gmdate('H:i', $totalSec),
                'total_seconds' => $totalSec,
            ],
        ]);
    }

    private function extractTrackDayRows($track): array
    {
        $rows = [];
        $stack = [$track];

        while (!empty($stack)) {
            $cur = array_pop($stack);
            if (!is_array($cur)) continue;

            if (isset($cur['day']) && (
                array_key_exists('distance', $cur) ||
                array_key_exists('duration', $cur) ||
                array_key_exists('beginDay', $cur) ||
                array_key_exists('endDay', $cur) ||
                array_key_exists('events', $cur)
            )) {
                $rows[] = $cur;
                continue;
            }

            foreach ($cur as $v) {
                if (is_array($v)) $stack[] = $v;
            }
        }

        return $rows;
    }

    private function findTrackDayRowAnyShape($track, string $dayKey): ?array
    {
        $rows = $this->extractTrackDayRows($track);
        foreach ($rows as $r) {
            if (($r['day'] ?? null) === $dayKey) return $r;
        }
        return null;
    }

    private function minuteOfDayToParisDateTime(string $dayKey, $minuteOfDay, string $tz): ?string
    {
        if ($minuteOfDay === null || !is_numeric($minuteOfDay)) return null;

        $m = (int) $minuteOfDay;
        $h = intdiv($m, 60);
        $min = $m % 60;

        return Carbon::createFromFormat('Y-m-d H:i', "{$dayKey} " . sprintf('%02d:%02d', $h, $min), $tz)
            ->toDateTimeString();
    }

    private function formatVehiculeRow($veh): array
    {
        return [
            'id' => $veh->id ?? null,
            'name' => $veh->name ?? null,
            'plaka' => $veh->plaka ?? null,
            'hermes_uid' => $veh->hermes_uid ?? null,
        ];
    }
    public function fleetDbView(Request $request)
{
    $tz = 'Europe/Paris';

    // default: dün (Paris)
    $dayKey = $request->get('day')
        ?: Carbon::yesterday($tz)->toDateString();

    // DB: stats + vehicules join
    $rows = DB::table('hermes_daily_stats as s')
        ->join('vehicules as v', 'v.id', '=', 's.vehicule_id')
        ->whereDate('s.day', $dayKey)
        ->select([
            's.id',
            's.day',
            's.vehicule_id',
            's.distance_km',
            's.duration_sec',
            's.begin_minute',
            's.end_minute',
            's.max_speed',
            's.raw',
            's.created_at',
            's.updated_at',
            'v.name as vehicule_name',
            'v.plaka as vehicule_plaka',
            'v.hermes_uid as vehicule_hermes_uid',
        ])
        ->orderBy('v.name')
        ->get();

    // Toplamlar
    $totalKm = 0.0;
    $totalSec = 0;

    foreach ($rows as $r) {
        if (is_numeric($r->distance_km)) $totalKm += (float)$r->distance_km;
        if (is_numeric($r->duration_sec)) $totalSec += (int)$r->duration_sec;
    }

    // yardımcı: dakika->HH:MM
    $dailyQuality = $this->summarizeQualityWarnings($rows);

    $minToTime = function ($min) {
        if ($min === null || !is_numeric($min)) return null;
        $m = (int)$min;
        $h = intdiv($m, 60);
        $mm = $m % 60;
        return sprintf('%02d:%02d', $h, $mm);
    };

    return view('hermes.fleet_db', [
        'date' => $dayKey,
        'rows' => $rows,
        'summary' => [
            'vehicle_count' => $rows->count(),
            'total_km' => round($totalKm, 1),
            'total_seconds' => $totalSec,
            'total_hhmm' => gmdate('H:i', $totalSec),
        ],
        'minToTime' => $minToTime,
    ]);
}
public function driverVehicleDailyView(Request $request)
{
    $tz = 'Europe/Paris';

    $dayKey = $request->get('day')
        ?: \Carbon\Carbon::yesterday($tz)->toDateString();

    $rows = DB::table('hermes_driver_daily_stats as d')
        ->leftJoin('vehicules as v', 'v.id', '=', 'd.vehicule_id')
        ->leftJoin('acentes as a', 'a.id', '=', 'd.acente_id') // driver_id = acente_id
        ->whereDate('d.day', $dayKey)
        ->select([
            'd.id',
            'd.day',
            'd.vehicule_id',
            'd.acente_id',
            'd.distance_km',
            'd.driving_sec',
            'd.begin_minute',
            'd.end_minute',
            'd.max_speed',
            'd.updated_at',
            'v.name as vehicule_name',
            'v.plaka as vehicule_plaka',
            'a.name as driver_name',
        ])
        ->orderByRaw('COALESCE(d.driving_sec,0) DESC')
        ->get();

    $systemStats = $this->driverVehicleSystemStatsForDay($dayKey);

    $totalKm = 0.0;
    $totalSec = 0;
    $totalPlannedSec = 0;
    $totalMissionSec = 0;

    foreach ($rows as $r) {
        $driverStats = $systemStats[((int) $r->acente_id) . ':' . ((int) $r->vehicule_id)] ?? null;
        $r->planning_sec = $driverStats['planning_sec'] ?? 0;
        $r->mission_sec = $driverStats['mission_sec'] ?? 0;
        $r->transfer_count = $driverStats['transfer_count'] ?? 0;
        $r->time_gap_sec = (int)($r->driving_sec ?? 0) - (int)$r->planning_sec;
        $r->quality_warnings = $this->qualityWarningsForRow($r);

        if (is_numeric($r->distance_km)) $totalKm += (float)$r->distance_km;
        if (is_numeric($r->driving_sec)) $totalSec += (int)$r->driving_sec;
        $totalPlannedSec += (int)$r->planning_sec;
        $totalMissionSec += (int)$r->mission_sec;
    }

    $minToTime = function ($min) {
        if ($min === null || !is_numeric($min)) return null;
        $m = (int)$min;
        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    };

    $secToHHMM = function ($sec) {
        if ($sec === null || !is_numeric($sec)) return null;
        $s = (int)$sec;
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        return sprintf('%02d:%02d', $h, $m);
    };

    $dailyQuality = $this->summarizeQualityWarnings($rows);

    return view('hermes.driver_vehicle_daily', [
        'date' => $dayKey,
        'rows' => $rows,
        'summary' => [
            'count' => $rows->count(),
            'total_km' => round($totalKm, 1),
            'total_hhmm' => sprintf('%02d:%02d', intdiv($totalSec, 3600), intdiv($totalSec % 3600, 60)),
            'planning_hhmm' => sprintf('%02d:%02d', intdiv($totalPlannedSec, 3600), intdiv($totalPlannedSec % 3600, 60)),
            'mission_hhmm' => sprintf('%02d:%02d', intdiv($totalMissionSec, 3600), intdiv($totalMissionSec % 3600, 60)),
        ],
        'minToTime' => $minToTime,
        'secToHHMM' => $secToHHMM,
        'quality' => $dailyQuality,
    ]);
}

public function driverWorkingDailyView(\Illuminate\Http\Request $request)
{
    $tz = 'Europe/Paris';
    $dayKey = $request->get('day') ?: Carbon::yesterday($tz)->toDateString();

    $rows = DB::table('hermes_driver_daily_working_stats as s')
        ->leftJoin('acentes as a', 'a.id', '=', 's.acente_id')
        ->whereDate('s.day', $dayKey)
        ->select([
            's.acente_id','s.day','s.working_sec','s.driving_sec','s.rest_sec','s.other_sec',
            's.begin_minute','s.end_minute','s.updated_at',
            'a.name as driver_name'
        ])
        ->orderByDesc('s.driving_sec')
        ->get();

    $systemStats = $this->driverSystemStatsForPeriod($dayKey, $dayKey);
    $seenDriverIds = [];

    foreach ($rows as $r) {
        $driverId = (int) $r->acente_id;
        $seenDriverIds[] = $driverId;
        $driverStats = $systemStats[$driverId] ?? null;
        $r->planning_sec = $driverStats['planning_sec'] ?? 0;
        $r->mission_sec = $driverStats['mission_sec'] ?? 0;
        $r->transfer_count = $driverStats['transfer_count'] ?? 0;
        $r->time_gap_sec = (int)($r->driving_sec ?? 0) - (int)$r->planning_sec;
        $r->source = 'hermes';
        $r->quality_warnings = $this->qualityWarningsForRow($r);

        if (is_numeric($r->begin_minute) && is_numeric($r->end_minute) && (int) $r->end_minute >= (int) $r->begin_minute) {
            $r->presence_sec = ((int) $r->end_minute - (int) $r->begin_minute) * 60;
        } else {
            $r->presence_sec = null;
        }
    }

    foreach ($systemStats as $driverId => $driverStats) {
        if (in_array($driverId, $seenDriverIds, true)) {
            continue;
        }

        $rows->push((object) [
            'acente_id' => $driverId,
            'day' => $dayKey,
            'driver_name' => $driverStats['driver_name'] ?? '',
            'working_sec' => 0,
            'driving_sec' => 0,
            'rest_sec' => 0,
            'other_sec' => 0,
            'begin_minute' => null,
            'end_minute' => null,
            'updated_at' => null,
            'presence_sec' => null,
            'planning_sec' => $driverStats['planning_sec'] ?? 0,
            'mission_sec' => $driverStats['mission_sec'] ?? 0,
            'transfer_count' => $driverStats['transfer_count'] ?? 0,
            'time_gap_sec' => -1 * (int)($driverStats['planning_sec'] ?? 0),
            'source' => 'planning',
            'quality_warnings' => ['planning_sans_hermes'],
        ]);
    }

    $rows = $rows->sortByDesc(fn ($row) => max((int)($row->driving_sec ?? 0), (int)($row->planning_sec ?? 0), (int)($row->presence_sec ?? 0)))->values();

    return view('hermes.drivers_working_daily_fr', [
        'date' => $dayKey,
        'rows' => $rows,
        'summary' => $this->sumWorkingRows($rows),
        'fmt' => $this->workingFormatters(),
        'quality' => $this->summarizeQualityWarnings($rows),
    ]);
}

public function driverWorkingWeeklyView(\Illuminate\Http\Request $request)
{
    $tz = 'Europe/Paris';
    $ref = $request->get('day') ?: Carbon::yesterday($tz)->toDateString();
    $start = Carbon::parse($ref, $tz)->startOfWeek(Carbon::MONDAY)->toDateString();
    $end   = Carbon::parse($ref, $tz)->endOfWeek(Carbon::SUNDAY)->toDateString();

    $rows = DB::table('hermes_driver_daily_working_stats as s')
        ->leftJoin('acentes as a', 'a.id', '=', 's.acente_id')
        ->whereBetween('s.day', [$start, $end])
        ->groupBy('s.acente_id','a.name')
        ->selectRaw("
            s.acente_id,
            COALESCE(a.name,'') as driver_name,
            MIN(s.day) as period_start,
            MAX(s.day) as period_end,
            SUM(COALESCE(s.working_sec,0)) as working_sec,
            SUM(COALESCE(s.driving_sec,0)) as driving_sec,
            SUM(COALESCE(s.rest_sec,0)) as rest_sec,
            SUM(COALESCE(s.other_sec,0)) as other_sec,
            MIN(s.begin_minute) as begin_minute,
            MAX(s.end_minute) as end_minute,
            MAX(s.updated_at) as updated_at,
            COUNT(*) as days_count
        ")
        ->orderByDesc('driving_sec')
        ->get();

    $comparisonEnd = $this->comparisonEndForHermesWorkingPeriod($start, $end);
    $systemStats = $this->driverSystemStatsForPeriod($start, $comparisonEnd);
    $seenDriverIds = [];

    foreach ($rows as $r) {
        $driverId = (int) $r->acente_id;
        $seenDriverIds[] = $driverId;
        $driverStats = $systemStats[$driverId] ?? null;
        $r->planning_sec = $driverStats['planning_sec'] ?? 0;
        $r->mission_sec = $driverStats['mission_sec'] ?? 0;
        $r->transfer_count = $driverStats['transfer_count'] ?? 0;
        $r->time_gap_sec = (int)($r->driving_sec ?? 0) - (int)$r->planning_sec;
        $r->source = 'hermes';
        $r->quality_warnings = $this->qualityWarningsForRow($r);
    }

    foreach ($systemStats as $driverId => $driverStats) {
        if (in_array($driverId, $seenDriverIds, true)) {
            continue;
        }

        $rows->push((object) [
            'acente_id' => $driverId,
            'driver_name' => $driverStats['driver_name'] ?? '',
            'period_start' => $start,
            'period_end' => $end,
            'working_sec' => 0,
            'driving_sec' => 0,
            'rest_sec' => 0,
            'other_sec' => 0,
            'begin_minute' => null,
            'end_minute' => null,
            'updated_at' => null,
            'days_count' => 0,
            'planning_sec' => $driverStats['planning_sec'] ?? 0,
            'mission_sec' => $driverStats['mission_sec'] ?? 0,
            'transfer_count' => $driverStats['transfer_count'] ?? 0,
            'time_gap_sec' => -1 * (int)($driverStats['planning_sec'] ?? 0),
            'source' => 'planning',
            'quality_warnings' => ['planning_sans_hermes'],
        ]);
    }

    $rows = $rows->sortByDesc(fn ($row) => max((int)($row->driving_sec ?? 0), (int)($row->planning_sec ?? 0)))->values();
    $quality = $this->summarizeQualityWarnings($rows);

    return view('hermes.drivers_working_weekly_fr', [
        'ref' => $ref,
        'start' => $start,
        'end' => $end,
        'comparisonEnd' => $comparisonEnd,
        'rows' => $rows,
        'summary' => $this->sumWorkingRows($rows),
        'fmt' => $this->workingFormatters(),
        'quality' => $quality,
    ]);
}

public function driverWorkingMonthlyView(\Illuminate\Http\Request $request)
{
    $tz = 'Europe/Paris';
    $ref = $request->get('month') ?: Carbon::yesterday($tz)->format('Y-m'); // 2026-02
    $start = Carbon::createFromFormat('Y-m', $ref, $tz)->startOfMonth()->toDateString();
    $end   = Carbon::createFromFormat('Y-m', $ref, $tz)->endOfMonth()->toDateString();

    $rows = DB::table('hermes_driver_daily_working_stats as s')
        ->leftJoin('acentes as a', 'a.id', '=', 's.acente_id')
        ->whereBetween('s.day', [$start, $end])
        ->groupBy('s.acente_id','a.name')
        ->selectRaw("
            s.acente_id,
            COALESCE(a.name,'') as driver_name,
            MIN(s.day) as period_start,
            MAX(s.day) as period_end,
            SUM(COALESCE(s.working_sec,0)) as working_sec,
            SUM(COALESCE(s.driving_sec,0)) as driving_sec,
            SUM(COALESCE(s.rest_sec,0)) as rest_sec,
            SUM(COALESCE(s.other_sec,0)) as other_sec,
            MIN(s.begin_minute) as begin_minute,
            MAX(s.end_minute) as end_minute,
            MAX(s.updated_at) as updated_at,
            COUNT(*) as days_count
        ")
        ->orderByDesc('driving_sec')
        ->get();

    $comparisonEnd = $this->comparisonEndForHermesWorkingPeriod($start, $end);
    $systemStats = $this->driverSystemStatsForPeriod($start, $comparisonEnd);
    $seenDriverIds = [];

    foreach ($rows as $r) {
        $driverId = (int) $r->acente_id;
        $seenDriverIds[] = $driverId;
        $driverStats = $systemStats[$driverId] ?? null;
        $r->planning_sec = $driverStats['planning_sec'] ?? 0;
        $r->mission_sec = $driverStats['mission_sec'] ?? 0;
        $r->transfer_count = $driverStats['transfer_count'] ?? 0;
        $r->time_gap_sec = (int)($r->driving_sec ?? 0) - (int)$r->planning_sec;
        $r->source = 'hermes';
        $r->quality_warnings = $this->qualityWarningsForRow($r);
    }

    foreach ($systemStats as $driverId => $driverStats) {
        if (in_array($driverId, $seenDriverIds, true)) {
            continue;
        }

        $rows->push((object) [
            'acente_id' => $driverId,
            'driver_name' => $driverStats['driver_name'] ?? '',
            'period_start' => $start,
            'period_end' => $end,
            'working_sec' => 0,
            'driving_sec' => 0,
            'rest_sec' => 0,
            'other_sec' => 0,
            'begin_minute' => null,
            'end_minute' => null,
            'updated_at' => null,
            'days_count' => 0,
            'planning_sec' => $driverStats['planning_sec'] ?? 0,
            'mission_sec' => $driverStats['mission_sec'] ?? 0,
            'transfer_count' => $driverStats['transfer_count'] ?? 0,
            'time_gap_sec' => -1 * (int)($driverStats['planning_sec'] ?? 0),
            'source' => 'planning',
            'quality_warnings' => ['planning_sans_hermes'],
        ]);
    }

    $rows = $rows->sortByDesc(fn ($row) => max((int)($row->driving_sec ?? 0), (int)($row->planning_sec ?? 0)))->values();
    $quality = $this->summarizeQualityWarnings($rows);

    return view('hermes.drivers_working_monthly_fr', [
        'ref' => $ref,
        'start' => $start,
        'end' => $end,
        'comparisonEnd' => $comparisonEnd,
        'rows' => $rows,
        'summary' => $this->sumWorkingRows($rows),
        'fmt' => $this->workingFormatters(),
        'quality' => $quality,
    ]);
}

private function comparisonEndForHermesWorkingPeriod(string $startDate, string $endDate): string
{
    $latestHermesDay = DB::table('hermes_driver_daily_working_stats')
        ->whereBetween('day', [$startDate, $endDate])
        ->max('day');

    if (!$latestHermesDay) {
        return $endDate;
    }

    return Carbon::parse($latestHermesDay)->lt(Carbon::parse($endDate))
        ? Carbon::parse($latestHermesDay)->toDateString()
        : $endDate;
}

private function driverVehicleSystemStatsForDay(string $dayKey): array
{
    $tz = 'Europe/Paris';
    $start = Carbon::parse($dayKey, $tz)->startOfDay()->toDateTimeString();
    $end = Carbon::parse($dayKey, $tz)->endOfDay()->toDateTimeString();

    $stats = [];

    $planningRows = DB::table('transfers as t')
        ->whereNull('t.deleted_at')
        ->whereNotNull('t.driver_id')
        ->whereNotNull('t.vehicule_id')
        ->where('t.driver_id', '>', 0)
        ->where(function ($q) use ($start, $end) {
            $q->whereBetween('t.start_date', [$start, $end])
              ->orWhereBetween('t.end_date', [$start, $end])
              ->orWhere(function ($qq) use ($start, $end) {
                  $qq->where('t.start_date', '<=', $start)->where('t.end_date', '>=', $end);
              });
        })
        ->groupBy('t.driver_id', 't.vehicule_id')
        ->selectRaw('t.driver_id as acente_id, t.vehicule_id, COUNT(*) as transfer_count, SUM(GREATEST(0, TIMESTAMPDIFF(SECOND, GREATEST(t.start_date, ?), LEAST(t.end_date, ?)))) as planning_sec', [$start, $end])
        ->get();

    foreach ($planningRows as $row) {
        $key = ((int) $row->acente_id) . ':' . ((int) $row->vehicule_id);
        $stats[$key] = [
            'transfer_count' => (int) $row->transfer_count,
            'planning_sec' => (int) $row->planning_sec,
            'mission_sec' => 0,
        ];
    }

    $missionRows = DB::table('missions as m')
        ->join('transfers as t', 't.id', '=', 'm.transfer_id')
        ->whereNull('t.deleted_at')
        ->whereNotNull('t.driver_id')
        ->whereNotNull('t.vehicule_id')
        ->where('t.driver_id', '>', 0)
        ->whereBetween('t.start_date', [$start, $end])
        ->groupBy('t.driver_id', 't.vehicule_id')
        ->selectRaw('t.driver_id as acente_id, t.vehicule_id, SUM(CASE WHEN m.hareket IS NOT NULL AND m.finish IS NOT NULL THEN GREATEST(0, TIMESTAMPDIFF(SECOND, m.hareket, m.finish)) ELSE 0 END) as mission_sec')
        ->get();

    foreach ($missionRows as $row) {
        $key = ((int) $row->acente_id) . ':' . ((int) $row->vehicule_id);
        if (!isset($stats[$key])) {
            $stats[$key] = [
                'transfer_count' => 0,
                'planning_sec' => 0,
                'mission_sec' => 0,
            ];
        }
        $stats[$key]['mission_sec'] = (int) $row->mission_sec;
    }

    return $stats;
}

private function driverSystemStatsForPeriod(string $startDate, string $endDate): array
{
    $tz = 'Europe/Paris';
    $start = Carbon::parse($startDate, $tz)->startOfDay()->toDateTimeString();
    $end = Carbon::parse($endDate, $tz)->endOfDay()->toDateTimeString();

    $stats = [];

    $planningRows = DB::table('transfers as t')
        ->leftJoin('acentes as a', 'a.id', '=', 't.driver_id')
        ->whereNull('t.deleted_at')
        ->whereNotNull('t.driver_id')
        ->where('t.driver_id', '>', 0)
        ->where(function ($q) use ($start, $end) {
            $q->whereBetween('t.start_date', [$start, $end])
              ->orWhereBetween('t.end_date', [$start, $end])
              ->orWhere(function ($qq) use ($start, $end) {
                  $qq->where('t.start_date', '<=', $start)->where('t.end_date', '>=', $end);
              });
        })
        ->groupBy('t.driver_id', 'a.name')
        ->selectRaw('t.driver_id as acente_id, COALESCE(a.name, "") as driver_name, COUNT(*) as transfer_count, SUM(GREATEST(0, TIMESTAMPDIFF(SECOND, GREATEST(t.start_date, ?), LEAST(t.end_date, ?)))) as planning_sec', [$start, $end])
        ->get();

    foreach ($planningRows as $row) {
        $driverId = (int) $row->acente_id;
        $stats[$driverId] = [
            'driver_name' => $row->driver_name,
            'transfer_count' => (int) $row->transfer_count,
            'planning_sec' => (int) $row->planning_sec,
            'mission_sec' => 0,
        ];
    }

    $missionRows = DB::table('missions as m')
        ->join('transfers as t', 't.id', '=', 'm.transfer_id')
        ->leftJoin('acentes as a', 'a.id', '=', 't.driver_id')
        ->whereNull('t.deleted_at')
        ->whereNotNull('t.driver_id')
        ->where('t.driver_id', '>', 0)
        ->whereBetween('t.start_date', [$start, $end])
        ->groupBy('t.driver_id', 'a.name')
        ->selectRaw('t.driver_id as acente_id, COALESCE(a.name, "") as driver_name, SUM(CASE WHEN m.hareket IS NOT NULL AND m.finish IS NOT NULL THEN GREATEST(0, TIMESTAMPDIFF(SECOND, m.hareket, m.finish)) ELSE 0 END) as mission_sec')
        ->get();

    foreach ($missionRows as $row) {
        $driverId = (int) $row->acente_id;
        if (!isset($stats[$driverId])) {
            $stats[$driverId] = [
                'driver_name' => $row->driver_name,
                'transfer_count' => 0,
                'planning_sec' => 0,
                'mission_sec' => 0,
            ];
        }
        $stats[$driverId]['mission_sec'] = (int) $row->mission_sec;
    }

    return $stats;
}

/* =========================
   Helpers
========================= */


private function qualityWarningsForRow($row): array
{
    $warnings = [];
    $hermesSec = (int)($row->driving_sec ?? 0);
    $planningSec = (int)($row->planning_sec ?? 0);
    $missionSec = (int)($row->mission_sec ?? 0);
    $transferCount = (int)($row->transfer_count ?? 0);

    if (empty($row->acente_id)) {
        $warnings[] = 'chauffeur_manquant';
    }

    if ($hermesSec > 0 && $planningSec === 0) {
        $warnings[] = 'planning_manquant';
    }

    if ($planningSec > 0 && $missionSec === 0) {
        $warnings[] = 'mission_non_renseignee';
    }

    if (abs($hermesSec - $planningSec) > 3600) {
        $warnings[] = 'ecart_important';
    }

    if ($hermesSec > 14 * 3600) {
        $warnings[] = 'activite_hermes_tres_longue';
    }

    if (is_numeric($row->begin_minute ?? null) && is_numeric($row->end_minute ?? null) && (int) $row->end_minute > (int) $row->begin_minute) {
        $amplitudeSec = ((int) $row->end_minute - (int) $row->begin_minute) * 60;
        if ($hermesSec > $amplitudeSec + 300) {
            $warnings[] = 'activite_superieure_amplitude';
        }
    }

    if (($row->source ?? 'hermes') === 'planning') {
        $warnings[] = 'planning_sans_hermes';
    }

    if ($transferCount > 1 && $missionSec === 0) {
        $warnings[] = 'plusieurs_transferts_sans_mission';
    }

    return array_values(array_unique($warnings));
}

private function summarizeQualityWarnings($rows): array
{
    $labels = [
        'chauffeur_manquant' => 'Chauffeur manquant',
        'planning_manquant' => 'Hermes sans planning',
        'mission_non_renseignee' => 'Mission non renseignée',
        'ecart_important' => 'Écart > 1h',
        'planning_sans_hermes' => 'Planning sans Hermes',
        'plusieurs_transferts_sans_mission' => 'Plusieurs transferts sans mission',
        'activite_hermes_tres_longue' => 'Activité Hermes > 14h',
        'activite_superieure_amplitude' => 'Activité > amplitude',
    ];

    $counts = [];
    foreach ($rows as $row) {
        foreach (($row->quality_warnings ?? []) as $warning) {
            $counts[$warning] = ($counts[$warning] ?? 0) + 1;
        }
    }

    $items = [];
    foreach ($counts as $key => $count) {
        $items[] = [
            'key' => $key,
            'label' => $labels[$key] ?? $key,
            'count' => $count,
        ];
    }

    return [
        'items' => $items,
        'total' => array_sum($counts),
    ];
}

private function workingFormatters(): array
{
    $secToHHMM = function ($sec) {
        if ($sec === null || !is_numeric($sec)) return '00:00';
        $sec = (int)$sec;
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        return sprintf('%02d:%02d', $h, $m);
    };

    $minToTime = function ($min) {
        if ($min === null || !is_numeric($min)) return '--:--';
        $min = (int)$min;
        return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
    };

    return compact('secToHHMM','minToTime');
}

private function sumWorkingRows($rows): array
{
    $totWork = 0; $totDrive = 0; $totRest = 0; $totPlanning = 0; $totMission = 0;
    foreach ($rows as $r) {
        $totWork  += (int)($r->working_sec ?? 0);
        $totDrive += (int)($r->driving_sec ?? 0);
        $totRest  += (int)($r->rest_sec ?? 0);
        $totPlanning += (int)($r->planning_sec ?? 0);
        $totMission += (int)($r->mission_sec ?? 0);
    }
    return [
        'count' => count($rows),
        'working_sec' => $totWork,
        'driving_sec' => $totDrive,
        'rest_sec' => $totRest,
        'planning_sec' => $totPlanning,
        'mission_sec' => $totMission,
    ];
}

}
