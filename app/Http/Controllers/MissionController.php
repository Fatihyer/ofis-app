<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Transfer;
use App\Models\Mission;
use App\Models\UserAttendance;
use App\Models\Option;
use Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Carbon;


class MissionController extends Controller
{

    public function __construct()
    {
   $this->middleware('auth')->except(['publicIndex', 'publicConfirm', 'publicRefuse', 'publicStart', 'publicOnplace', 'publicOnboard', 'publicFinish', 'publicFinishDepot', 'publicUpdateKilometers']);
    }
    private function isOfficeUser($user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && (
            $user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport'])
            || $user->hasAnyPermission(['transfers.operations', 'ofis', 'transport'])
        );
    }

    private function driverAcenteIds($user = null): array
    {
        $user = $user ?: Auth::user();
        return $user ? $user->acentes()->pluck('acentes.id')->map(fn ($id) => (int) $id)->toArray() : [];
    }

    private function scopeForDriver($query)
    {
        if ($this->isOfficeUser()) {
            return $query;
        }

        $ids = $this->driverAcenteIds();
        if (!count($ids)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($ids) {
            $q->whereIn('driver_id', $ids);
            if (Schema::hasColumn('transfers', 'second_driver_id')) {
                $q->orWhereIn('second_driver_id', $ids);
            }
        });
    }

    private function abortUnlessTransferAccessible(Transfer $transfer): void
    {
        if ($this->isOfficeUser()) {
            return;
        }

        $ids = $this->driverAcenteIds();
        $isPrimaryDriver = in_array((int) $transfer->driver_id, $ids, true);
        $isSecondDriver = Schema::hasColumn('transfers', 'second_driver_id') && in_array((int) ($transfer->second_driver_id ?? 0), $ids, true);

        if (!$isPrimaryDriver && !$isSecondDriver) {
            abort(403, 'Cette mission ne vous est pas attribuée.');
        }
    }

    private function abortUnlessMissionAccessible(Mission $mission): void
    {
        $mission->loadMissing('transfer');
        if (!$mission->transfer) {
            abort(404);
        }

        $this->abortUnlessTransferAccessible($mission->transfer);
    }

    private function plannedEnRouteAt(Transfer $transfer): Carbon
    {
        $startAt = Carbon::parse($transfer->start_date, 'Europe/Paris');
        $planned = $transfer->ofis_start
            ? Carbon::parse($transfer->ofis_start, 'Europe/Paris')
            : $startAt->copy()->subHour();

        if ($planned->gt($startAt)) {
            $planned = $startAt->copy()->subHour();
        }

        return $planned->setTimezone('Europe/Paris');
    }

    private function driverConfirmationMissingMessage(Transfer $transfer): ?string
    {
        $user = Auth::user();

        if ($user && !$this->isOfficeUser($user)) {
            $ids = $this->driverAcenteIds($user);
            $isPrimaryDriver = in_array((int) $transfer->driver_id, $ids, true);
            $isSecondDriver = Schema::hasColumn('transfers', 'second_driver_id')
                && in_array((int) ($transfer->second_driver_id ?? 0), $ids, true)
                && !$isPrimaryDriver;

            if ($isSecondDriver) {
                return $transfer->second_driver_app_confirmed_at
                    ? null
                    : 'Veuillez confirmer le service avant de démarrer la mission.';
            }

            return $transfer->driver_app_confirmed_at
                ? null
                : 'Veuillez confirmer le service avant de démarrer la mission.';
        }

        if (!$transfer->driver_app_confirmed_at) {
            return 'Le chauffeur principal doit confirmer le service avant le démarrage de la mission.';
        }

        $hasSecondDriver = Schema::hasColumn('transfers', 'second_driver_id') && !empty($transfer->second_driver_id);
        if ($hasSecondDriver && !$transfer->second_driver_app_confirmed_at) {
            return 'Le 2e chauffeur doit confirmer le service avant le démarrage de la mission.';
        }

        return null;
    }

    private function missionStartBlocker(Transfer $transfer): ?string
    {
        $confirmationMessage = $this->driverConfirmationMissingMessage($transfer);
        if ($confirmationMessage) {
            return $confirmationMessage;
        }

        $plannedEnRouteAt = $this->plannedEnRouteAt($transfer);
        if (Carbon::now('Europe/Paris')->lt($plannedEnRouteAt)) {
            return 'La mission ne peut pas démarrer avant l’heure En route prévue: '
                . $plannedEnRouteAt->format('d/m/Y H:i') . '.';
        }

        return null;
    }

    public static function publicMissionTokenFor(int $transferId): string
    {
        $encodedId = strtoupper(base_convert((string) $transferId, 10, 36));
        $signature = substr(hash_hmac('sha256', (string) $transferId, config('app.key')), 0, 8);
        return $encodedId . '-' . $signature;
    }

    private function transferIdFromPublicToken(string $token): int
    {
        if (!preg_match('/^([A-Z0-9]+)-([a-f0-9]{8})$/i', $token, $matches)) {
            abort(404);
        }

        $transferId = (int) base_convert(strtoupper($matches[1]), 36, 10);
        $expected = self::publicMissionTokenFor($transferId);
        if (!hash_equals(strtolower($expected), strtolower($token))) {
            abort(404);
        }

        return $transferId;
    }

    private function transferFromPublicToken(string $token): Transfer
    {
        $relations = ['driver', 'vehicule', 'servicetype', 'post.client', 'trajets', 'missionr'];
        if (Schema::hasColumn('transfers', 'second_driver_id')) {
            $relations[] = 'secondDriver';
        }

        $transfer = Transfer::with($relations)
            ->findOrFail($this->transferIdFromPublicToken($token));

        if ((int) $transfer->status_id === 1) {
            abort(404);
        }

        return $transfer;
    }

    private function missionFromPublicToken(string $token, int $missionId): Mission
    {
        $transfer = $this->transferFromPublicToken($token);
        $mission = Mission::findOrFail($missionId);
        if ((int) $mission->transfer_id !== (int) $transfer->id) {
            abort(404);
        }

        return $mission;
    }

    public function index($transferid)
    {
       
        $transfer = Transfer::with(['driver', 'vehicule', 'servicetype', 'post.client', 'trajets', 'missionr'])->where('id', $transferid)->first();
        if (!$transfer) {
            abort(404);
        }
        $this->abortUnlessTransferAccessible($transfer);
        $mission=Mission::where('transfer_id',$transfer->id)->first();
       
        
        return view('transfert.mission', compact('transfer','mission'));
    }

    public function publicIndex(string $token)
    {
        request()->attributes->set('public_mission', true);
        $transfer = $this->transferFromPublicToken($token);
        $mission = Mission::where('transfer_id', $transfer->id)->first();
        $publicMissionToken = $token;
        $publicDriverRole = request()->query('driver_role') === 'second_driver' ? 'second_driver' : 'primary_driver';

        return view('transfert.mission', compact('transfer', 'mission', 'publicMissionToken', 'publicDriverRole'));
    }

    public function publicConfirm(Request $request, string $token)
    {
        $transfer = $this->transferFromPublicToken($token);
        $driverRole = $request->input('driver_role') === 'second_driver' ? 'second_driver' : 'primary_driver';
        $hasSecondDriver = Schema::hasColumn('transfers', 'second_driver_id') && !empty($transfer->second_driver_id);

        if ($driverRole === 'second_driver' && $hasSecondDriver && Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')) {
            $transfer->second_driver_app_confirmed_at = $this->zaman();
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_by')) {
                $transfer->second_driver_app_confirmed_by = null;
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
            $transfer->driver_app_confirmed_at = $this->zaman();
            $transfer->driver_app_confirmed_by = null;

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

        return redirect()->route('mission.public', $token)->with('flash_message', 'Service confirmé. Vous pouvez suivre la mission ici.');
    }

    public function publicRefuse(Request $request, string $token)
    {
        $transfer = $this->transferFromPublicToken($token);
        $driverRole = $request->input('driver_role') === 'second_driver' ? 'second_driver' : 'primary_driver';
        $hasSecondDriver = Schema::hasColumn('transfers', 'second_driver_id') && !empty($transfer->second_driver_id);

        if ($driverRole === 'second_driver' && $hasSecondDriver && Schema::hasColumn('transfers', 'second_driver_app_refused_at')) {
            $transfer->second_driver_app_refused_at = $this->zaman();
            if (Schema::hasColumn('transfers', 'second_driver_app_refused_by')) {
                $transfer->second_driver_app_refused_by = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')) {
                $transfer->second_driver_app_confirmed_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_by')) {
                $transfer->second_driver_app_confirmed_by = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_reconfirm_reason')) {
                $reason = trim((string) $request->input('driver_refusal_reason', 'Refus public 2e chauffeur'));
                $transfer->second_driver_app_reconfirm_reason = mb_substr($reason, 0, 255);
            }
        } else {
            if (Schema::hasColumn('transfers', 'driver_app_refused_at')) {
                $transfer->driver_app_refused_at = $this->zaman();
            }
            if (Schema::hasColumn('transfers', 'driver_app_refused_by')) {
                $transfer->driver_app_refused_by = null;
            }

            $transfer->driver_app_confirmed_at = null;
            $transfer->driver_app_confirmed_by = null;

            if (Schema::hasColumn('transfers', 'driver_app_reconfirm_reason')) {
                $reason = trim((string) $request->input('driver_refusal_reason', 'Refus public chauffeur'));
                $transfer->driver_app_reconfirm_reason = mb_substr($reason, 0, 255);
            }
        }

        $transfer->save();

        return redirect()->route('mission.public', $token)->withErrors(['mission' => 'Service refusé. Merci, l’opération est informée.']);
    }

    public function listm()
    {
        $today = Carbon::today();

        // Define the start and end of today
        $startOfDay = $today->copy()->startOfDay();
        $endOfDay = $today->copy()->endOfDay();
     
        $canEditMissions = $this->isOfficeUser();
        $missions = Mission::with(['transfer.driver', 'transfer.vehicule', 'transfer.servicetype', 'transfer.post.acente', 'startuser', 'user', 'officeNoteUser'])
            ->whereBetween('hareket', [$startOfDay, $endOfDay])
            ->whereHas('transfer', function ($query) {
                $this->scopeForDriver($query);
            })
            ->orderBy('hareket')
            ->get();
        $missionTransferIds = $missions->pluck('transfer_id')->filter()->unique();
        $notStartedTransfers = $this->scopeForDriver(Transfer::with(['driver', 'vehicule', 'servicetype', 'post.acente'])
            ->where('mission', true)
            ->whereBetween('start_date', [$startOfDay, $endOfDay])
            ->whereNotIn('id', $missionTransferIds))
            ->orderBy('start_date')
            ->get();
        $selectedDate = $today->format('Y-m-d');
      return view('mission.index', compact('missions', 'selectedDate', 'notStartedTransfers', 'canEditMissions'));
        }

    public function missionsByDate(Request $request)
    {
        // Retrieve the selected date from the request
        $selectedDate = $request->input('selected_date');
    
        // Define the start and end of the day for the selected date
        $startOfDay = Carbon::parse($selectedDate)->startOfDay();
        $endOfDay = Carbon::parse($selectedDate)->endOfDay();
    
        // Retrieve missions within the time range of 00:00 to 23:59 for the selected date
        $canEditMissions = $this->isOfficeUser();
        $missions = Mission::with(['transfer.driver', 'transfer.vehicule', 'transfer.servicetype', 'transfer.post.acente', 'startuser', 'user', 'officeNoteUser'])
            ->whereBetween('hareket', [$startOfDay, $endOfDay])
            ->whereHas('transfer', function ($query) {
                $this->scopeForDriver($query);
            })
            ->orderBy('hareket')
            ->get();
        $missionTransferIds = $missions->pluck('transfer_id')->filter()->unique();
        $notStartedTransfers = $this->scopeForDriver(Transfer::with(['driver', 'vehicule', 'servicetype', 'post.acente'])
            ->where('mission', true)
            ->whereBetween('start_date', [$startOfDay, $endOfDay])
            ->whereNotIn('id', $missionTransferIds))
            ->orderBy('start_date')
            ->get();
    
        // Pass the selected date and missions to the view
        return view('mission.index', compact('selectedDate', 'missions', 'notStartedTransfers', 'canEditMissions'));
    }
    public function start(Request $request, $transferid)
{
    // Kullanıcının kilometre bilgisi doğrulanıyor
    $request->validate([
        
    'depart_km' => 'required|numeric|min:0|max:5000000',


    ]);

    $transfer = Transfer::findOrFail($transferid);
    $this->abortUnlessTransferAccessible($transfer);

    if ($message = $this->missionStartBlocker($transfer)) {
        return redirect()->back()->withErrors(['mission' => $message]);
    }

    // Transfer ID'ye bağlı mevcut bir görev kontrol ediliyor
    $mission = Mission::where('transfer_id', $transferid)->first();
    if ($mission) {
        return redirect()->back()->with('error', 'Mission already exists!');
    }

    // En son kullanıcı yoklamasını alıyoruz
    $lastAttendance = UserAttendance::latest()->first();
    $start_user_id = Auth::id() ?: ($lastAttendance ? $lastAttendance->user_id : null);

    // Yeni görev oluşturuluyor
    Mission::create([
        'transfer_id' => $transferid,
        'hareket' => $this->zaman(),
        'start_user_id' => $start_user_id,
        'depart_km' => $request->depart_km, // Kullanıcıdan alınan kilometre bilgisi
    ]);

    return redirect()->back()->with('flash_message', 'Mission Started!');
}

    public function onplace($missionid)
    {
        $mission = Mission::findOrFail($missionid);
        $this->abortUnlessMissionAccessible($mission);
        $mission->surplace = $this->zaman();
        $mission->save();
       
       return redirect()->back()->with('flash_message','Surplace');
    }
    public function onboard($missionid)
    {
        $mission = Mission::findOrFail($missionid);
        $this->abortUnlessMissionAccessible($mission);
        $mission->taked = $this->zaman();
        $mission->save();
       
       return redirect()->back()->with('flash_message','Client Embarque');
    }
    public function finish($missionid)
    {
        $mission = Mission::findOrFail($missionid);
        $this->abortUnlessMissionAccessible($mission);
        $lastAttendance = UserAttendance::latest()->first();
        $mission->user_id = Auth::id() ?: optional($lastAttendance)->user_id;
        $mission->finish = $this->zaman();
        $mission->save();
       
       return redirect()->back()->with('flash_message','Mission Fini Merci');
    }
    public function finishMissionDepot(Request $request, $id)
    {
        // Validate input
        $validated = $request->validate([
            'finish_km' => 'required|numeric|min:0',
            'cleaningStatus' => 'required|in:yes,no',
        ]);
        $cleaningStatus = $validated['cleaningStatus'] === 'yes' ? true : false;
        $mission = Mission::findOrFail($id);
        $this->abortUnlessMissionAccessible($mission);
        if ($mission->depart_km !== null && $validated['finish_km'] < $mission->depart_km) {
            return redirect()->back()->withErrors(['finish_km' => 'Le kilométrage final ne peut pas être inférieur au kilométrage de départ.']);
        }
        $mission->finish_km = $validated['finish_km'];
        $mission->cleaning_status = $cleaningStatus;
        $mission->finish_depot = $this->zaman();
        $mission->save();
       

       return redirect()->back()->with('flash_message','Mission Fini Merci');
    }



    public function updateKilometers(Request $request, $id)
    {
        $mission = Mission::findOrFail($id);
        $this->abortUnlessMissionAccessible($mission);
        $validated = $request->validate([
            'depart_km' => 'required|numeric|min:0|max:5000000',
            'finish_km' => 'nullable|numeric|min:0|max:5000000',
        ]);

        if (isset($validated['finish_km']) && $validated['finish_km'] !== null && $validated['finish_km'] < $validated['depart_km']) {
            return redirect()->back()->withErrors(['finish_km' => 'Le kilométrage final ne peut pas être inférieur au kilométrage de départ.']);
        }

        $mission->depart_km = $validated['depart_km'];
        if (array_key_exists('finish_km', $validated)) {
            $mission->finish_km = $validated['finish_km'];
        }
        $mission->save();

        return redirect()->back()->with('flash_message', 'Kilométrage mis à jour.');
    }

    public function zaman()
    {
        $dateNow = \Carbon\Carbon::now();
        $dateNow->setTimezone('Europe/Paris');
        return $dateNow;

    }
    public function updateTimes(Request $request)
{
    $validatedData = $request->validate([
        'transfers_id' => 'required|exists:transfers,id',
        'new_hareket' => 'nullable|date',
        'new_surplace' => 'nullable|date',
        'new_taked' => 'nullable|date',
        'new_finish' => 'nullable|date',
        'office_note' => 'nullable|string|max:5000',
    ]);

    $transfers = Transfer::with('missionr')->findOrFail($request->input('transfers_id'));
    $this->abortUnlessTransferAccessible($transfers);

    $mission = $transfers->missionr;
    if (!$mission) {
        return back()->withErrors(['mission' => 'Mission introuvable.']);
    }

    $canEditOfficeFields = $this->isOfficeUser();

    $fields = [
        'new_hareket' => 'hareket',
        'new_surplace' => 'surplace',
        'new_taked' => 'taked',
        'new_finish' => 'finish',
    ];

    $hasUpdate = false;
    foreach ($fields as $input => $column) {
        if ($request->filled($input)) {
            $mission->{$column} = date('Y-m-d H:i:s', strtotime($request->input($input)));
            $hasUpdate = true;
        }
    }

    if ($request->has('office_note')) {
        if (! $canEditOfficeFields) {
            abort(403, 'Seul le bureau peut modifier la note mission.');
        }

        $officeNote = $request->input('office_note');
        if ((string) ($mission->office_note ?? '') !== (string) ($officeNote ?? '')) {
            $mission->office_note = $officeNote;
            $mission->office_note_updated_at = now();
            $mission->office_note_updated_by = Auth::id();
        }
        $hasUpdate = true;
    }

    if (!$hasUpdate) {
        return back()->withErrors(['times' => 'Aucune heure ou note à mettre à jour.']);
    }

    $sequence = [
        'hareket' => 'En route',
        'surplace' => 'Sur place',
        'taked' => 'Client à bord',
        'finish' => 'Dépose',
    ];
    $previousValue = null;
    $previousLabel = null;
    foreach ($sequence as $column => $label) {
        if (!$mission->{$column}) {
            continue;
        }
        $currentValue = Carbon::parse($mission->{$column});
        if ($previousValue && $currentValue->lt($previousValue)) {
            return back()->withErrors(['times' => $label.' ne peut pas être avant '.$previousLabel.'.']);
        }
        $previousValue = $currentValue;
        $previousLabel = $label;
    }

    $mission->save();

    return back()->with('success', 'Heures mises à jour.');
}

public function registerTime(Request $request)
    {
        $request->validate([
            'mission_id' => 'required|exists:missions,id',
            'user_id'=> 'required',
        ]);

        $mission = Mission::findOrFail($request->mission_id);
        $this->abortUnlessMissionAccessible($mission);
      //  if ($mission->start_user_id == $request->user_id)
      //  {
        $mission->confirmed_at = $this->zaman();  // Assuming you have a 'confirmed_at' column in your missions table
        $mission->save();
        return response()->json(['message' => 'Teşekkur ederiz emeğe saygı.']);

     //    }
     //    else {
            // Return a response indicating the user is not authorized to register time for this mission
     //       return response()->json(['error' => 'cin atasözü: çizmedin ki kesesin. , Buna ancak Permanastaki arkadaş yapabilur'], 403);
    //     }

    }
    public function registerTimeend(Request $request)
    {
        $request->validate([
            'mission_id' => 'required|exists:missions,id',
        ]);

        $mission = Mission::findOrFail($request->mission_id);
        $this->abortUnlessMissionAccessible($mission);
      //   if ($mission->user_id==$request->user_id)
      //   {
        $mission->finish_confirmed_at = $this->zaman();  // Assuming you have a 'confirmed_at' column in your missions table
        $mission->save();
        return response()->json(['message' => 'Teşekkur ederiz emeğe saygı.']);

   //  }
   //  else {
       // Return a response indicating the user is not authorized to register time for this mission
   //     return response()->json(['error' => 'cin atasözü: çizmedin ki kesesin. , Buna ancak Permanastaki arkadaş yapabilur'], 403);
    //      }
    }   


    public function getMissionDetails($transferid)
{
    $mission = Mission::where('transfer_id', $transferid)->first();
    $transfer = Transfer::with(['servicetype', 'status','post.client']) ->where('id', $transferid)
                ->first();

    if (!$mission) {
        $mission==false;
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
      //  $trajetDetails .= ' - ' . $trajet->type . ': ' . $trajet->from . ' ' . $trajet->google_address;

        $trajetsList[] = [
            'id' => $trajet->id,
            'details' => $trajetDetails,
            'google_address' => $trajet->google_address,
            'adres' => $trajet->from,
            'type' => $trajet->type
        ];
    }
    $ofis_start = Carbon::parse($transfer->ofis_start)->format('H:i');
    $surplace = Carbon::parse($transfer->start_date)->subMinutes(15)->format('H:i'); // `surplace` örnek hesaplama
    $start_date = Carbon::parse($transfer->start_date)->format('H:i');
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
    
        $firstgoogleadress = null;
     foreach ($transfer->trajets as $trajet) {
         if ($trajet['type'] !== 'depot') {
            $firstgoogleadress = $trajet['google_address'];
             break;
         }
     }
      

    return response()->json([
        'success' => true,
        'mission' => $mission,
        'transfert' => [
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
                    'vehicule' => $transfer->vehicule->name, 
                    'comments' => $transfer->comments,    
                    'servicetype' => $transfer->servicetype->name,
                    'status' => $transfer->status,
                    'firstgoogleadress' => $firstgoogleadress,
                    'guzergah' => $trajetsList,
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
                'misafirler' => $misafirList// Misafir bilgisini JSON yanıtına ekledik
               // Trajets bilgisi eklendi
            ]);
}
public function startMissionapi(Request $request, $transferid) // ✅ ÇÖZÜM: Request eklendi!
{
    $transfer = Transfer::findOrFail($transferid);
    if ($message = $this->missionStartBlocker($transfer)) {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 422);
    }

    $existingMission = Mission::where('transfer_id', $transferid)->exists();
    if ($existingMission) {
        return response()->json([
            'success' => false,
            'message' => 'Mission already exists!',
        ], 409); // 409 Conflict hatası döndür
    }
    
    
    // Validasyon
    $request->validate([
        
    'depart_km' => 'required|numeric|min:0|max:5000000',


        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    // Konum bilgisi JSON formatına dönüştürülüyor
    $startlocalisation = json_encode([
        'latitude' => $request->input('latitude'),
        'longitude' => $request->input('longitude')
    ]);

    // Transfer ID'ye bağlı mevcut bir görev kontrol ediliyor
    $mission = Mission::where('transfer_id', $transferid)->first();
    if ($mission) {
        return response()->json([
            'success' => false,
            'error' => 'Mission already exists!'
        ], 400);
    }

    // En son kullanıcı yoklamasını alıyoruz
    $lastAttendance = UserAttendance::latest()->first();
    $start_user_id = $lastAttendance ? $lastAttendance->user_id : null;

    // Yeni görev oluşturuluyor
    $mission = Mission::create([
        'transfer_id' => $transferid,
        'hareket' => $this->zaman(), // Eğer 'zaman()' fonksiyonun yoksa now() kullan
        'start_user_id' => $start_user_id,
        'depart_km' => $request->input('depart_km'), // ✅ `input()` ile güvenli erişim
        'startlocalisation' => $startlocalisation, 
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Mission started successfully!',
        'mission' => $mission,
        'transfert' => $transfer
    ], 201);
}

public function surPlaceapi(Request $request,$transferid)
{
    $mission = Mission::where('transfer_id', $transferid)->first();
    if (!$mission) {
        return response()->json([
            'success' => false,
            'message' => 'Mission not found!',
        ], 404);
    }

    

    $surPlacelocalisation= json_encode([
        'latitude' => $request->input('latitude'),
        'longitude' => $request->input('longitude')
    ]);
    $mission->surplace = $this->zaman();
    $mission->surPlacelocalisation = $surPlacelocalisation;
    $mission->save();

        
     $transfert = Transfer::find($transferid);
    return response()->json([
        'success' => true,
        'message' => 'Surplace time updated successfully!',
        'mission' => $mission,
       'transfert' => $transfert
    ], 201);
}


public  function markOnBoardapi($transferid)
{
    $mission = Mission::where('transfer_id', $transferid)->first();
    if (!$mission) {
        return response()->json([
            'success' => false,
            'message' => 'Mission not found!',
        ], 404);
    }
    $mission->taked = $this->zaman();
   
    $mission->save();
   
    $transfert = Transfer::find($transferid);
    return response()->json([
        'success' => true,
        'message' => 'Taked time updated successfully!',
        'mission' => $mission,
       'transfert' => $transfert
    ], 201);
}
public function finishMissionapi($transferid)
{
    $mission = Mission::where('transfer_id', $transferid)->first();
    if (!$mission) {
        return response()->json([
            'success' => false,
            'message' => 'Mission not found!',
        ], 404);
    }
    $mission->finish=$this->zaman();
    $mission->save();
   
    $transfert = Transfer::find($transferid);
    return response()->json([
        'success' => true,
        'message' => 'Mission Finished return to depot!',
        'mission' => $mission,
       'transfert' => $transfert
    ], 201);
}
public function finishmissiondepotapi(Request $request,$transferid)
{
    $mission = Mission::where('transfer_id', $transferid)->first();
    if (!$mission) {
        return response()->json([
            'success' => false,
            'message' => 'Mission not found!',
        ], 404);
    }
    $validated = $request->validate([
        'finish_km' => 'required|numeric|min:0',
         'cleaningStatus' => 'required'
    ]);
    $cleaningStatus = $validated['cleaningStatus'];
   
    $mission->finish_km = $validated['finish_km'];
    $mission->cleaning_status = $cleaningStatus;
    $mission->finish_depot = $this->zaman();
    $mission->save();
    $transfert = Transfer::find($transferid);
    return response()->json([
        'success' => true,
        'message' => 'Mission Finished return to depot!',
        'mission' => $mission,
       'transfert' => $transfert
    ], 201);

}
    public function publicStart(Request $request, string $token)
    {
        $transfer = $this->transferFromPublicToken($token);
        if ($message = $this->missionStartBlocker($transfer)) {
            return redirect()->back()->withErrors(['mission' => $message]);
        }

        $request->validate([
            'depart_km' => 'required|numeric|min:0|max:5000000',
        ]);

        if (Mission::where('transfer_id', $transfer->id)->exists()) {
            return redirect()->back()->withErrors(['mission' => 'Mission déjà démarrée.']);
        }

        Mission::create([
            'transfer_id' => $transfer->id,
            'hareket' => $this->zaman(),
            'start_user_id' => null,
            'depart_km' => $request->depart_km,
        ]);

        return redirect()->route('mission.public', $token)->with('flash_message', 'Mission démarrée.');
    }

    public function publicOnplace(string $token, int $mission)
    {
        $missionModel = $this->missionFromPublicToken($token, $mission);
        $missionModel->surplace = $this->zaman();
        $missionModel->save();

        return redirect()->route('mission.public', $token)->with('flash_message', 'Sur place enregistré.');
    }

    public function publicOnboard(string $token, int $mission)
    {
        $missionModel = $this->missionFromPublicToken($token, $mission);
        $missionModel->taked = $this->zaman();
        $missionModel->save();

        return redirect()->route('mission.public', $token)->with('flash_message', 'Client à bord enregistré.');
    }

    public function publicFinish(string $token, int $mission)
    {
        $missionModel = $this->missionFromPublicToken($token, $mission);
        $missionModel->finish = $this->zaman();
        $missionModel->user_id = null;
        $missionModel->save();

        return redirect()->route('mission.public', $token)->with('flash_message', 'Dépose enregistrée.');
    }

    public function publicFinishDepot(Request $request, string $token, int $mission)
    {
        $missionModel = $this->missionFromPublicToken($token, $mission);
        $validated = $request->validate([
            'finish_km' => 'required|numeric|min:0',
            'cleaningStatus' => 'required|in:yes,no',
        ]);

        if ($missionModel->depart_km !== null && $validated['finish_km'] < $missionModel->depart_km) {
            return redirect()->back()->withErrors(['finish_km' => 'Le kilométrage final ne peut pas être inférieur au kilométrage de départ.']);
        }

        $missionModel->finish_km = $validated['finish_km'];
        $missionModel->cleaning_status = $validated['cleaningStatus'] === 'yes';
        $missionModel->finish_depot = $this->zaman();
        $missionModel->save();

        return redirect()->route('mission.public', $token)->with('flash_message', 'Mission terminée au dépôt.');
    }

    public function publicUpdateKilometers(Request $request, string $token, int $mission)
    {
        $missionModel = $this->missionFromPublicToken($token, $mission);
        $validated = $request->validate([
            'depart_km' => 'required|numeric|min:0|max:5000000',
            'finish_km' => 'nullable|numeric|min:0|max:5000000',
        ]);

        if (isset($validated['finish_km']) && $validated['finish_km'] !== null && $validated['finish_km'] < $validated['depart_km']) {
            return redirect()->back()->withErrors(['finish_km' => 'Le kilométrage final ne peut pas être inférieur au kilométrage de départ.']);
        }

        $missionModel->depart_km = $validated['depart_km'];
        if (array_key_exists('finish_km', $validated)) {
            $missionModel->finish_km = $validated['finish_km'];
        }
        $missionModel->save();

        return redirect()->route('mission.public', $token)->with('flash_message', 'Kilométrage mis à jour.');
    }

}
