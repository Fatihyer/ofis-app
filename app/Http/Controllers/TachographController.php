<?php

namespace App\Http\Controllers;

use App\Models\Acente;
use App\Models\TachographDailySummary;
use App\Models\TachographDrive;
use App\Models\TachographDriverCard;
use App\Models\TachographImport;
use App\Models\Transfer;
use App\Services\Tachograph\TachographC1BParser;
use App\Services\Tachograph\TachographGoogleDriveService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TachographController extends Controller
{
    private const MIN_DRIVE_DATE = '2026-06-01 00:00:00';
    private const DEFAULT_DRIVE_FOLDER_ID = '1PNVwBEz2vZ8Eb4IU2uMN5ZkCZ9tytoKf';

    public function index(Request $request, TachographC1BParser $parser, TachographGoogleDriveService $driveService)
    {
        $this->ensureTables();
        $this->backfillDailySummaries($parser);
        $this->maybeAutoSyncDrive($parser, $driveService);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $summaryFrom = $dateFrom ?: self::MIN_DRIVE_DATE;
        $summaryTo = $dateTo ?: now()->toDateString();

        $drivesQuery = TachographDrive::with(['acente', 'transfer.post', 'card'])
            ->orderByDesc('started_at');

        if ($dateFrom) {
            $drivesQuery->whereDate('started_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $drivesQuery->whereDate('started_at', '<=', $dateTo);
        }

        if ($request->filled('acente_id')) {
            $drivesQuery->where('acente_id', $request->input('acente_id'));
        }

        if ($request->filled('match_status')) {
            $drivesQuery->where('match_status', $request->input('match_status'));
        }

        $drives = $drivesQuery->paginate(80)->appends($request->query());

        $cards = TachographDriverCard::with('acente')
            ->orderByRaw('acente_id is null desc')
            ->orderBy('driver_last_name')
            ->get();

        $imports = TachographImport::with('acente')
            ->orderByDesc('imported_at')
            ->limit(20)
            ->get();

        $driverWindow = now()->subMonths(18);
        $driverIds = Transfer::query()
            ->where('start_date', '>=', $driverWindow)
            ->whereNotNull('driver_id')
            ->pluck('driver_id')
            ->merge(Transfer::query()
                ->where('start_date', '>=', $driverWindow)
                ->whereNotNull('second_driver_id')
                ->pluck('second_driver_id'))
            ->filter()
            ->unique()
            ->values();

        $drivers = Acente::query()
            ->whereIn('id', $driverIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'cards' => TachographDriverCard::count(),
            'drives' => TachographDrive::count(),
            'matched' => TachographDrive::where('match_status', 'matched')->count(),
            'unmatched' => TachographDrive::where('match_status', '<>', 'matched')->count(),
        ];

        $weeklySummaries = $this->periodSummaries('week', $summaryFrom, $summaryTo, $request->input('acente_id'));
        $monthlySummaries = $this->periodSummaries('month', $summaryFrom, $summaryTo, $request->input('acente_id'));
        $driveSettings = [
            'folder_id' => $this->tachographSetting('google_drive_folder_id', env('GOOGLE_DRIVE_TACHOGRAPH_FOLDER_ID', self::DEFAULT_DRIVE_FOLDER_ID)),
            'auto_sync' => $this->tachographSetting('google_drive_auto_sync', '1') === '1',
            'last_sync_at' => $this->tachographSetting('google_drive_last_sync_at'),
            'configured' => $driveService->isConfigured(),
            'mode' => $driveService->connectionMode(),
            'oauth_client_id' => $this->tachographSetting('google_drive_client_id', env('GOOGLE_DRIVE_CLIENT_ID', '')),
            'oauth_secret_set' => (bool) ($this->tachographSetting('google_drive_client_secret') ?: env('GOOGLE_DRIVE_CLIENT_SECRET')),
            'connected_at' => $this->tachographSetting('google_drive_connected_at'),
        ];

        return view('tachograph.index', compact('drives', 'cards', 'imports', 'drivers', 'stats', 'dateFrom', 'dateTo', 'weeklySummaries', 'monthlySummaries', 'driveSettings'));
    }

    public function updateDriveSettings(Request $request)
    {
        $this->ensureTables();

        $request->validate([
            'folder_id' => 'nullable|string|max:255',
            'auto_sync' => 'nullable',
            'google_drive_client_id' => 'nullable|string|max:255',
            'google_drive_client_secret' => 'nullable|string|max:255',
        ]);

        $this->setTachographSetting('google_drive_folder_id', trim((string) $request->input('folder_id')));
        $this->setTachographSetting('google_drive_auto_sync', $request->boolean('auto_sync') ? '1' : '0');
        $this->setTachographSetting('google_drive_client_id', trim((string) $request->input('google_drive_client_id')));

        if ($request->filled('google_drive_client_secret')) {
            $this->setTachographSetting('google_drive_client_secret', trim((string) $request->input('google_drive_client_secret')));
        }

        return redirect()
            ->route('tachograph.index')
            ->with('success', 'Configuration Google Drive mise a jour.');
    }

    public function redirectGoogleDrive(TachographGoogleDriveService $driveService)
    {
        $this->ensureTables();

        return redirect()->away($driveService->authUrl($this->googleDriveRedirectUri()));
    }

    public function callbackGoogleDrive(Request $request, TachographGoogleDriveService $driveService)
    {
        $this->ensureTables();

        if ($request->filled('error')) {
            return redirect()
                ->route('tachograph.index')
                ->with('error', 'Google Drive : ' . $request->input('error'));
        }

        if (!$request->filled('code')) {
            return redirect()
                ->route('tachograph.index')
                ->with('error', 'Google Drive code manquant.');
        }

        try {
            $driveService->storeTokenFromCode($request->input('code'), $this->googleDriveRedirectUri());
        } catch (\Throwable $exception) {
            return redirect()
                ->route('tachograph.index')
                ->with('error', 'Google Drive : ' . $exception->getMessage());
        }

        return redirect()
            ->route('tachograph.index')
            ->with('success', 'Compte Google Drive connecte.');
    }

    public function disconnectGoogleDrive()
    {
        $this->ensureTables();

        $this->setTachographSetting('google_drive_oauth_token', null);
        $this->setTachographSetting('google_drive_connected_at', null);

        return redirect()
            ->route('tachograph.index')
            ->with('success', 'Compte Google Drive deconnecte.');
    }

    public function syncDrive(Request $request, TachographC1BParser $parser, TachographGoogleDriveService $driveService)
    {
        $this->ensureTables();

        $folderId = $this->tachographSetting('google_drive_folder_id', env('GOOGLE_DRIVE_TACHOGRAPH_FOLDER_ID', self::DEFAULT_DRIVE_FOLDER_ID));
        if (!$folderId) {
            return redirect()
                ->route('tachograph.index')
                ->with('error', 'Google Drive folder ID manquant.');
        }

        $summary = $this->syncDriveFiles($parser, $driveService, $folderId, (int) $request->input('limit', 25));

        return redirect()
            ->route('tachograph.index')
            ->with('tachograph_summary', $summary);
    }

    public function store(Request $request, TachographC1BParser $parser)
    {
        $this->ensureTables();

        $request->validate([
            'files' => 'required',
            'files.*' => 'file|max:20480',
        ]);

        $summary = [
            'imported_files' => 0,
            'duplicate_files' => 0,
            'created_drives' => 0,
            'matched_drives' => 0,
            'ignored_drives' => 0,
            'errors' => [],
        ];

        foreach ($request->file('files', []) as $file) {
            try {
                $hash = sha1_file($file->getRealPath());
                $existingImport = TachographImport::where('file_hash', $hash)->first();

                if ($existingImport) {
                    $summary['duplicate_files']++;
                    continue;
                }

                $binary = file_get_contents($file->getRealPath());
                $parsed = $parser->parse($binary, $file->getClientOriginalName());
                list($eligibleDrives, $ignoredDrives, $totalDrivingMinutes, $totalDistanceKm) = $this->summarizeAllowedDrives($parsed['drives']);

                if (empty($parsed['card_number'])) {
                    $summary['errors'][] = $file->getClientOriginalName() . ' : carte chauffeur introuvable';
                    continue;
                }

                $card = TachographDriverCard::firstOrNew(['card_number' => $parsed['card_number']]);
                $card->driver_first_name = $parsed['driver_first_name'] ?: $card->driver_first_name;
                $card->driver_last_name = $parsed['driver_last_name'] ?: $card->driver_last_name;

                if (!$card->acente_id) {
                    $matchedAcente = $this->findAcenteForName($card->driver_first_name, $card->driver_last_name);
                    if ($matchedAcente) {
                        $card->acente_id = $matchedAcente->id;
                    }
                }

                $card->last_imported_at = now();
                $card->save();

                $storedPath = Storage::disk('local')->putFileAs(
                    'tachograph/imports',
                    $file,
                    now()->format('YmdHis') . '_' . substr($hash, 0, 12) . '_' . $file->getClientOriginalName()
                );

                $import = TachographImport::create([
                    'tachograph_driver_card_id' => $card->id,
                    'acente_id' => $card->acente_id,
                    'imported_by' => Auth::id(),
                    'file_hash' => $hash,
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_path' => $storedPath,
                    'card_number' => $parsed['card_number'],
                    'driver_first_name' => $parsed['driver_first_name'],
                    'driver_last_name' => $parsed['driver_last_name'],
                    'vehicle_plates' => $parsed['vehicle_plates'],
                    'issuing_authority' => $parsed['issuing_authority'],
                    'activity_from' => $parsed['activity_from'],
                    'activity_to' => $parsed['activity_to'],
                    'records_count' => $parsed['records_count'],
                    'drives_count' => 0,
                    'matched_count' => 0,
                    'ignored_drives_count' => $ignoredDrives,
                    'total_driving_minutes' => $totalDrivingMinutes,
                    'total_distance_km' => $totalDistanceKm,
                    'imported_at' => now(),
                ]);

                $created = 0;
                $matched = 0;
                $this->storeDailySummaries($parsed['daily_summaries'], $import, $card);

                foreach ($eligibleDrives as $driveData) {
                    $drive = TachographDrive::firstOrCreate(
                        ['activity_uid' => $driveData['activity_uid']],
                        array_merge($driveData, [
                            'tachograph_import_id' => $import->id,
                            'tachograph_driver_card_id' => $card->id,
                            'acente_id' => $card->acente_id,
                            'card_number' => $card->card_number,
                            'match_status' => $card->acente_id ? 'unmatched' : 'driver_not_mapped',
                        ])
                    );

                    if ($drive->wasRecentlyCreated) {
                        $created++;
                        $this->matchDriveToTransfer($drive);
                        if ($drive->fresh()->match_status === 'matched') {
                            $matched++;
                        }
                    }
                }

                $import->update([
                    'drives_count' => $created,
                    'matched_count' => $matched,
                ]);

                $summary['imported_files']++;
                $summary['created_drives'] += $created;
                $summary['matched_drives'] += $matched;
                $summary['ignored_drives'] += $ignoredDrives;
            } catch (\Throwable $exception) {
                $summary['errors'][] = $file->getClientOriginalName() . ' : ' . $exception->getMessage();
            }
        }

        return redirect()
            ->route('tachograph.index')
            ->with('tachograph_summary', $summary);
    }

    public function mapCard(Request $request, TachographDriverCard $card)
    {
        $this->ensureTables();

        $request->validate([
            'acente_id' => 'nullable|exists:acentes,id',
        ]);

        $card->update([
            'acente_id' => $request->input('acente_id') ?: null,
        ]);

        TachographDrive::where('card_number', $card->card_number)
            ->update([
                'acente_id' => $card->acente_id,
                'match_status' => $card->acente_id ? 'unmatched' : 'driver_not_mapped',
                'transfer_id' => null,
                'match_score' => null,
                'match_reason' => null,
            ]);

        TachographImport::where('card_number', $card->card_number)
            ->update(['acente_id' => $card->acente_id]);

        TachographDailySummary::where('card_number', $card->card_number)
            ->update(['acente_id' => $card->acente_id]);

        if ($card->acente_id) {
            TachographDrive::where('card_number', $card->card_number)
                ->orderByDesc('started_at')
                ->limit(500)
                ->get()
                ->each(function ($drive) {
                    $this->matchDriveToTransfer($drive);
                });
        }

        return redirect()
            ->route('tachograph.index')
            ->with('success', 'Carte tachograph mise a jour.');
    }

    private function matchDriveToTransfer(TachographDrive $drive)
    {
        if (!$drive->acente_id) {
            $drive->update(['match_status' => 'driver_not_mapped']);
            return;
        }

        $start = Carbon::parse($drive->started_at);
        $end = Carbon::parse($drive->ended_at);
        $windowStart = $start->copy()->subHours(6);
        $windowEnd = $end->copy()->addHours(6);

        $transfers = Transfer::with(['post'])
            ->whereNull('deleted_at')
            ->where(function ($query) use ($drive) {
                $query->where('driver_id', $drive->acente_id)
                    ->orWhere('second_driver_id', $drive->acente_id);
            })
            ->where(function ($query) use ($windowStart, $windowEnd) {
                $query->whereBetween('start_date', [$windowStart, $windowEnd])
                    ->orWhereBetween('end_date', [$windowStart, $windowEnd])
                    ->orWhere(function ($inner) use ($windowStart, $windowEnd) {
                        $inner->where('start_date', '<=', $windowStart)
                            ->where('end_date', '>=', $windowEnd);
                    });
            })
            ->get();

        $bestTransfer = null;
        $bestScore = 0;
        $bestReason = null;

        foreach ($transfers as $transfer) {
            if (!$transfer->start_date) {
                continue;
            }

            $transferStart = Carbon::parse($transfer->start_date);
            $transferEnd = $transfer->end_date ? Carbon::parse($transfer->end_date) : $transferStart->copy()->addHours(2);

            $overlapStart = $start->greaterThan($transferStart) ? $start : $transferStart;
            $overlapEnd = $end->lessThan($transferEnd) ? $end : $transferEnd;
            $overlapMinutes = $overlapEnd->gt($overlapStart) ? $overlapStart->diffInMinutes($overlapEnd) : 0;
            $startDistance = abs($start->diffInMinutes($transferStart, false));
            $endDistance = abs($end->diffInMinutes($transferEnd, false));

            $score = $overlapMinutes;
            $score += max(0, 60 - min(60, $startDistance));
            $score += max(0, 30 - min(30, $endDistance));

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTransfer = $transfer;
                $bestReason = 'overlap ' . $overlapMinutes . ' min, ecart depart ' . $startDistance . ' min';
            }
        }

        if ($bestTransfer && $bestScore >= 15) {
            $drive->update([
                'transfer_id' => $bestTransfer->id,
                'match_status' => 'matched',
                'match_score' => (int) round($bestScore),
                'match_reason' => $bestReason,
            ]);
        } else {
            $drive->update([
                'transfer_id' => null,
                'match_status' => 'unmatched',
                'match_score' => $bestScore ? (int) round($bestScore) : null,
                'match_reason' => $bestScore ? $bestReason : null,
            ]);
        }
    }

    private function findAcenteForName($firstName, $lastName)
    {
        $tokens = array_filter([
            $this->normalizeName($firstName),
            $this->normalizeName($lastName),
        ]);

        if (count($tokens) < 2) {
            return null;
        }

        $candidates = Acente::where(function ($query) use ($firstName, $lastName) {
                if ($firstName) {
                    $query->orWhere('name', 'like', '%' . $firstName . '%');
                }
                if ($lastName) {
                    $query->orWhere('name', 'like', '%' . $lastName . '%');
                }
            })
            ->limit(50)
            ->get();

        $matches = $candidates->filter(function ($acente) use ($tokens) {
            $name = $this->normalizeName($acente->name);
            foreach ($tokens as $token) {
                if (strpos($name, $token) === false) {
                    return false;
                }
            }

            return true;
        });

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function summarizeAllowedDrives(array $drives)
    {
        $cutoff = Carbon::parse(self::MIN_DRIVE_DATE);
        $eligible = [];
        $ignored = 0;
        $totalDrivingMinutes = 0;
        $distanceByDate = [];

        foreach ($drives as $drive) {
            if (Carbon::parse($drive['started_at'])->lt($cutoff)) {
                $ignored++;
                continue;
            }

            $eligible[] = $drive;
            $totalDrivingMinutes += (int) $drive['duration_minutes'];

            if (!empty($drive['source_date']) && isset($drive['daily_distance_km'])) {
                $distanceByDate[$drive['source_date']] = (float) $drive['daily_distance_km'];
            }
        }

        return [
            $eligible,
            $ignored,
            $totalDrivingMinutes,
            array_sum($distanceByDate),
        ];
    }

    private function syncDriveFiles(TachographC1BParser $parser, TachographGoogleDriveService $driveService, string $folderId, int $limit = 25)
    {
        $summary = [
            'imported_files' => 0,
            'duplicate_files' => 0,
            'created_drives' => 0,
            'matched_drives' => 0,
            'ignored_drives' => 0,
            'errors' => [],
        ];

        try {
            $files = $driveService->listC1BFiles($folderId, max(1, min($limit, 100)));
        } catch (\Throwable $exception) {
            $summary['errors'][] = 'Google Drive : ' . $exception->getMessage();
            return $summary;
        }

        foreach ($files as $driveFile) {
            try {
                $filename = $driveFile->getName();
                $binary = $driveService->downloadFileContent($driveFile->getId());
                $result = $this->importTachographBinary($binary, $filename, 'google-drive-' . $driveFile->getId());

                foreach ($summary as $key => $value) {
                    if ($key === 'errors') {
                        continue;
                    }
                    $summary[$key] += $result[$key] ?? 0;
                }

                if (!empty($result['errors'])) {
                    $summary['errors'] = array_merge($summary['errors'], $result['errors']);
                }
            } catch (\Throwable $exception) {
                $summary['errors'][] = $driveFile->getName() . ' : ' . $exception->getMessage();
            }
        }

        $this->setTachographSetting('google_drive_last_sync_at', now()->format('Y-m-d H:i:s'));

        return $summary;
    }

    private function importTachographBinary(string $binary, string $filename, ?string $sourceKey = null)
    {
        $parser = app(TachographC1BParser::class);
        $hash = sha1($binary);
        $existingImport = TachographImport::where('file_hash', $hash)->first();

        if ($existingImport) {
            return [
                'imported_files' => 0,
                'duplicate_files' => 1,
                'created_drives' => 0,
                'matched_drives' => 0,
                'ignored_drives' => 0,
                'errors' => [],
            ];
        }

        $parsed = $parser->parse($binary, $filename);
        list($eligibleDrives, $ignoredDrives, $totalDrivingMinutes, $totalDistanceKm) = $this->summarizeAllowedDrives($parsed['drives']);

        if (empty($parsed['card_number'])) {
            return [
                'imported_files' => 0,
                'duplicate_files' => 0,
                'created_drives' => 0,
                'matched_drives' => 0,
                'ignored_drives' => 0,
                'errors' => [$filename . ' : carte chauffeur introuvable'],
            ];
        }

        $card = TachographDriverCard::firstOrNew(['card_number' => $parsed['card_number']]);
        $card->driver_first_name = $parsed['driver_first_name'] ?: $card->driver_first_name;
        $card->driver_last_name = $parsed['driver_last_name'] ?: $card->driver_last_name;

        if (!$card->acente_id) {
            $matchedAcente = $this->findAcenteForName($card->driver_first_name, $card->driver_last_name);
            if ($matchedAcente) {
                $card->acente_id = $matchedAcente->id;
            }
        }

        $card->last_imported_at = now();
        $card->save();

        $storedName = now()->format('YmdHis') . '_' . substr($hash, 0, 12) . '_' . basename($filename);
        $storedPath = 'tachograph/imports/' . $storedName;
        Storage::disk('local')->put($storedPath, $binary);

        $import = TachographImport::create([
            'tachograph_driver_card_id' => $card->id,
            'acente_id' => $card->acente_id,
            'imported_by' => Auth::id(),
            'file_hash' => $hash,
            'original_filename' => $filename,
            'stored_path' => $storedPath,
            'card_number' => $parsed['card_number'],
            'driver_first_name' => $parsed['driver_first_name'],
            'driver_last_name' => $parsed['driver_last_name'],
            'vehicle_plates' => $parsed['vehicle_plates'],
            'issuing_authority' => $parsed['issuing_authority'],
            'activity_from' => $parsed['activity_from'],
            'activity_to' => $parsed['activity_to'],
            'records_count' => $parsed['records_count'],
            'drives_count' => 0,
            'matched_count' => 0,
            'ignored_drives_count' => $ignoredDrives,
            'total_driving_minutes' => $totalDrivingMinutes,
            'total_distance_km' => $totalDistanceKm,
            'imported_at' => now(),
        ]);

        $created = 0;
        $matched = 0;
        $this->storeDailySummaries($parsed['daily_summaries'], $import, $card);

        foreach ($eligibleDrives as $driveData) {
            $drive = TachographDrive::firstOrCreate(
                ['activity_uid' => $driveData['activity_uid']],
                array_merge($driveData, [
                    'tachograph_import_id' => $import->id,
                    'tachograph_driver_card_id' => $card->id,
                    'acente_id' => $card->acente_id,
                    'card_number' => $card->card_number,
                    'match_status' => $card->acente_id ? 'unmatched' : 'driver_not_mapped',
                ])
            );

            if ($drive->wasRecentlyCreated) {
                $created++;
                $this->matchDriveToTransfer($drive);
                if ($drive->fresh()->match_status === 'matched') {
                    $matched++;
                }
            }
        }

        $import->update([
            'drives_count' => $created,
            'matched_count' => $matched,
        ]);

        return [
            'imported_files' => 1,
            'duplicate_files' => 0,
            'created_drives' => $created,
            'matched_drives' => $matched,
            'ignored_drives' => $ignoredDrives,
            'errors' => [],
        ];
    }

    private function maybeAutoSyncDrive(TachographC1BParser $parser, TachographGoogleDriveService $driveService): void
    {
        if ($this->tachographSetting('google_drive_auto_sync', '1') !== '1') {
            return;
        }

        $folderId = $this->tachographSetting('google_drive_folder_id', env('GOOGLE_DRIVE_TACHOGRAPH_FOLDER_ID', self::DEFAULT_DRIVE_FOLDER_ID));
        if (!$folderId || !$driveService->isConfigured()) {
            return;
        }

        $lastSyncAt = $this->tachographSetting('google_drive_last_auto_sync_at');
        if ($lastSyncAt && Carbon::parse($lastSyncAt)->gt(now()->subMinutes(30))) {
            return;
        }

        try {
            $this->syncDriveFiles($parser, $driveService, $folderId, 10);
            $this->setTachographSetting('google_drive_last_auto_sync_at', now()->format('Y-m-d H:i:s'));
        } catch (\Throwable $exception) {
            $this->setTachographSetting('google_drive_last_auto_sync_at', now()->format('Y-m-d H:i:s'));
        }
    }

    private function storeDailySummaries(array $summaries, TachographImport $import, TachographDriverCard $card)
    {
        $cutoff = Carbon::parse(self::MIN_DRIVE_DATE)->toDateString();

        foreach ($summaries as $summary) {
            if ($summary['source_date'] < $cutoff) {
                continue;
            }

            TachographDailySummary::updateOrCreate(
                ['summary_uid' => $summary['summary_uid']],
                array_merge($summary, [
                    'tachograph_import_id' => $import->id,
                    'tachograph_driver_card_id' => $card->id,
                    'acente_id' => $card->acente_id,
                    'card_number' => $card->card_number,
                ])
            );
        }
    }

    private function backfillDailySummaries(TachographC1BParser $parser)
    {
        if (!Schema::hasTable('tachograph_daily_summaries')) {
            return;
        }

        TachographImport::with('card')
            ->whereNotNull('stored_path')
            ->whereDoesntHave('dailySummaries')
            ->orderByDesc('imported_at')
            ->limit(5)
            ->get()
            ->each(function ($import) use ($parser) {
                $path = storage_path('app/' . $import->stored_path);
                if (!is_file($path)) {
                    return;
                }

                try {
                    $parsed = $parser->parse(file_get_contents($path), $import->original_filename);
                    if ($import->card) {
                        $this->storeDailySummaries($parsed['daily_summaries'], $import, $import->card);
                    }
                } catch (\Throwable $exception) {
                    return;
                }
            });
    }

    private function periodSummaries($period, $dateFrom, $dateTo, $acenteId = null)
    {
        $selectPeriod = $period === 'month'
            ? "DATE_FORMAT(source_date, '%Y-%m')"
            : "YEARWEEK(source_date, 3)";

        $query = TachographDailySummary::query()
            ->with('acente')
            ->select([
                'acente_id',
                DB::raw($selectPeriod . ' as period_key'),
                DB::raw('MIN(source_date) as period_start'),
                DB::raw('MAX(source_date) as period_end'),
                DB::raw('SUM(driving_minutes) as driving_minutes'),
                DB::raw('SUM(work_minutes) as work_minutes'),
                DB::raw('SUM(availability_minutes) as availability_minutes'),
                DB::raw('SUM(rest_minutes) as rest_minutes'),
                DB::raw('SUM(daily_distance_km) as distance_km'),
            ])
            ->whereDate('source_date', '>=', $dateFrom)
            ->whereDate('source_date', '<=', $dateTo)
            ->whereNotNull('acente_id')
            ->groupBy('acente_id', DB::raw($selectPeriod))
            ->orderByDesc('period_start')
            ->orderBy('acente_id');

        if ($acenteId) {
            $query->where('acente_id', $acenteId);
        }

        return $query->limit(80)->get();
    }

    private function normalizeName($value)
    {
        $value = trim((string) $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim($value);
    }

    private function ensureTables()
    {
        if (!Schema::hasTable('tachograph_driver_cards')) {
            Schema::create('tachograph_driver_cards', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('card_number', 32)->unique();
                $table->unsignedBigInteger('acente_id')->nullable()->index();
                $table->string('driver_first_name')->nullable();
                $table->string('driver_last_name')->nullable();
                $table->string('preferred_language', 8)->nullable();
                $table->timestamp('last_imported_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tachograph_imports')) {
            Schema::create('tachograph_imports', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tachograph_driver_card_id')->nullable()->index();
                $table->unsignedBigInteger('acente_id')->nullable()->index();
                $table->unsignedBigInteger('imported_by')->nullable()->index();
                $table->string('file_hash', 64)->unique();
                $table->string('original_filename')->nullable();
                $table->string('stored_path')->nullable();
                $table->string('card_number', 32)->nullable()->index();
                $table->string('driver_first_name')->nullable();
                $table->string('driver_last_name')->nullable();
                $table->text('vehicle_plates')->nullable();
                $table->string('issuing_authority')->nullable();
                $table->date('activity_from')->nullable();
                $table->date('activity_to')->nullable();
                $table->unsignedInteger('records_count')->default(0);
                $table->unsignedInteger('drives_count')->default(0);
                $table->unsignedInteger('matched_count')->default(0);
                $table->unsignedInteger('ignored_drives_count')->default(0);
                $table->unsignedInteger('total_driving_minutes')->default(0);
                $table->decimal('total_distance_km', 10, 2)->nullable();
                $table->unsignedBigInteger('duplicate_of_id')->nullable()->index();
                $table->timestamp('imported_at')->nullable();
                $table->timestamps();
            });
        } else {
            $this->ensureColumn('tachograph_imports', 'vehicle_plates', function (Blueprint $table) {
                $table->text('vehicle_plates')->nullable();
            });
            $this->ensureColumn('tachograph_imports', 'issuing_authority', function (Blueprint $table) {
                $table->string('issuing_authority')->nullable();
            });
            $this->ensureColumn('tachograph_imports', 'ignored_drives_count', function (Blueprint $table) {
                $table->unsignedInteger('ignored_drives_count')->default(0);
            });
            $this->ensureColumn('tachograph_imports', 'total_driving_minutes', function (Blueprint $table) {
                $table->unsignedInteger('total_driving_minutes')->default(0);
            });
            $this->ensureColumn('tachograph_imports', 'total_distance_km', function (Blueprint $table) {
                $table->decimal('total_distance_km', 10, 2)->nullable();
            });
        }

        if (!Schema::hasTable('tachograph_drives')) {
            Schema::create('tachograph_drives', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tachograph_import_id')->index();
                $table->unsignedBigInteger('tachograph_driver_card_id')->nullable()->index();
                $table->unsignedBigInteger('acente_id')->nullable()->index();
                $table->unsignedBigInteger('transfer_id')->nullable()->index();
                $table->string('card_number', 32)->nullable()->index();
                $table->date('source_date')->nullable()->index();
                $table->dateTime('started_at')->index();
                $table->dateTime('ended_at')->index();
                $table->unsignedInteger('duration_minutes')->default(0);
                $table->decimal('daily_distance_km', 10, 2)->nullable();
                $table->string('activity_uid', 64)->unique();
                $table->string('match_status', 32)->default('unmatched')->index();
                $table->integer('match_score')->nullable();
                $table->string('match_reason')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tachograph_daily_summaries')) {
            Schema::create('tachograph_daily_summaries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tachograph_import_id')->nullable()->index();
                $table->unsignedBigInteger('tachograph_driver_card_id')->nullable()->index();
                $table->unsignedBigInteger('acente_id')->nullable()->index();
                $table->string('card_number', 32)->nullable()->index();
                $table->date('source_date')->index();
                $table->unsignedInteger('driving_minutes')->default(0);
                $table->unsignedInteger('work_minutes')->default(0);
                $table->unsignedInteger('availability_minutes')->default(0);
                $table->unsignedInteger('rest_minutes')->default(0);
                $table->unsignedInteger('unknown_minutes')->default(0);
                $table->decimal('daily_distance_km', 10, 2)->nullable();
                $table->string('summary_uid', 64)->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tachograph_settings')) {
            Schema::create('tachograph_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    private function ensureColumn($table, $column, \Closure $definition)
    {
        if (!Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
        }
    }

    private function tachographSetting($key, $default = null)
    {
        if (!Schema::hasTable('tachograph_settings')) {
            return $default;
        }

        $value = DB::table('tachograph_settings')->where('key', $key)->value('value');

        return $value === null ? $default : $value;
    }

    private function googleDriveRedirectUri(): string
    {
        return 'https://ofis.parisvia.com/hermes/tachograph/google/callback';
    }

    private function setTachographSetting($key, $value): void
    {
        DB::table('tachograph_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
