<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Talep;
use App\Models\Acente;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Servicetype;
use App\Models\Post;
use App\Models\Transfer;
use App\Models\Trajet;
use App\Models\Client;
use App\Models\TalepDay;
use App\Models\TalepHistory;
use App\Models\Option;
use Yajra\DataTables\Facades\DataTables;
use App\Services\Routing\GoogleRouteService;
use App\Services\Pricing\TalepPricingService;
use App\Services\Pricing\TalepAiPricingService;
use App\Services\AiPricingClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Helpers\HareketHelper;
use App\Helpers\LogActivity;
use App\Notifications\TalepAdminPriceUpdated;
class TalepController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:talep.view')->only(['index', 'show', 'routePreview', 'generateXmlFromText']);
        $this->middleware('permission:talep.update')->except(['index', 'show', 'routePreview', 'generateXmlFromText']);
    }

    public function index(Request $request)
{
    if ($request->ajax()) {
        $data = Talep::with([
            'acente',
            'user',
            'attachments',
            'days',
            'quote',
            'vehicule',
            'serviceType',
            'adminPriceUser',
        ])
        ->select('talepler.*')
        ->latest();

        if ($request->filled('market')) {
            $this->applyMarketFilter($data, $request->input('market'));
        }

        if ($request->filled('status_filter')) {
            $data->where('konfirme_durumu', $request->input('status_filter'));
        }

        if ($request->filled('responsable_id')) {
            $data->where('user_id', $request->input('responsable_id'));
        }

        if ($request->boolean('prix_admin_pending')) {
            $data->where(function ($query) {
                $query->whereNull('final_total')
                    ->orWhere('final_total', '<=', 0);
            });
        }

        return DataTables::of($data)
            ->addColumn('acente_adi', function ($row) {
                return $row->acente ? $row->acente->name : '-';
            })
            ->addColumn('user_adi', function ($row) {
                return $row->user ? $row->user->name : '-';
            })
            ->addColumn('service_type_name', function ($row) {
                return $row->serviceType ? $row->serviceType->name : '-';
            })
            ->addColumn('vehicule_name', function ($row) {
                return $row->vehicule ? $row->vehicule->name : '-';
            })
            ->addColumn('market_display', function ($row) {
                return $this->marketBadgeHtml($this->detectMarket($row));
            })
            ->addColumn('date_display', function ($row) {
                return $row->talep_tarihi ? $row->talep_tarihi->format('d/m/Y H:i') : '-';
            })
            ->addColumn('operation_date_display', function ($row) {
                $firstDay = $row->days ? $row->days->first() : null;

                if (!$firstDay || !$firstDay->service_date) {
                    return '-';
                }

                return \Carbon\Carbon::parse($firstDay->service_date)->format('d/m/Y');
            })
            ->addColumn('client_display', function ($row) {
                $parts = array_filter([
                    $row->customer_name,
                    $row->customer_phone,
                    $row->customer_email,
                ]);
                $client = $parts ? implode('<br>', array_map('e', $parts)) : '-';

                $country = trim((string) ($row->country ?? ''));
                if ($country !== '') {
                    $client .= '<br><span class="country-badge"><i class="fas fa-globe-europe"></i> ' . e($country) . '</span>';
                }

                $client .= '<br>' . $this->marketBadgeHtml($this->detectMarket($row));

                return $client;
            })
            ->addColumn('country_display', function ($row) {
                $country = trim((string) ($row->country ?? ''));

                return $country !== ''
                    ? '<span class="country-badge"><i class="fas fa-globe-europe"></i> ' . e($country) . '</span>'
                    : '-';
            })
            ->addColumn('route_display', function ($row) {
                $firstDay = $row->days->first();
                $lastDay = $row->days->last();
                $pickup = $firstDay->pickup_location ?? $row->pickup_location ?? '-';
                $dropoff = $lastDay->dropoff_location ?? $row->dropoff_location ?? '-';

                return e($pickup) . '<br><span class="text-muted">→ ' . e($dropoff) . '</span>';
            })
            ->addColumn('operation_summary', function ($row) {
                $days = $row->days ? $row->days->count() : 0;
                $distanceMeters = $row->days ? (int) $row->days->sum('distance_meters') : 0;
                $distance = $distanceMeters > 0 ? round($distanceMeters / 1000, 1) . ' km' : '-';
                $fuel = $row->days ? (float) $row->days->sum('fuel_amount') : 0;
                $tolls = $row->days ? (float) $row->days->sum('toll_amount') : 0;

                return $days . ' jour(s)<br><span class="text-muted">' . $distance . '</span>' .
                    '<br><span class="text-muted">Carburant: ' . ($fuel > 0 ? number_format($fuel, 2, ',', ' ') . ' EUR' : '-') . '</span>' .
                    '<br><span class="text-muted">Péages: ' . ($tolls > 0 ? number_format($tolls, 2, ',', ' ') . ' EUR' : '-') . '</span>';
            })
            ->addColumn('price_display', function ($row) {
                $currency = $row->currency ?: 'EUR';
                $adminPending = empty($row->final_total) || (float) $row->final_total <= 0;
                $adminEntered = $this->hasAdminPriceEntered($row);
                $retained = $row->second_discount_price
                    ?? $row->discount_price
                    ?? $row->final_total
                    ?? $row->system_total
                    ?? optional($row->quote)->system_total;
                $retainedText = $retained !== null
                    ? number_format((float) $retained, 2, ',', ' ') . ' ' . $currency
                    : '-';

                $html = '<div class="price-stack compact-price">';
                $html .= '<span>Retenu <strong>' . $retainedText . '</strong></span>';
                $html .= $adminPending
                    ? '<span><strong><span class="price-pending">Admin à traiter</span></strong></span>'
                    : '<span>Admin <strong>' . number_format((float) $row->final_total, 2, ',', ' ') . ' ' . $currency . '</strong></span>';

                if ($row->discount_price !== null || $row->second_discount_price !== null) {
                    $discount = $row->second_discount_price ?? $row->discount_price;
                    $html .= '<span>Remise <strong>' . number_format((float) $discount, 2, ',', ' ') . ' ' . $currency . '</strong></span>';
                    if ($row->discount_valid_until) {
                        $html .= '<span class="text-muted">Valable jusqu&apos;au <strong>' . $row->discount_valid_until->format('d/m/Y') . '</strong></span>';
                    }
                }

                if ($adminEntered) {
                    $html .= '<span class="admin-price-saisi"><i class="fas fa-check-circle"></i> Saisi</span>';
                }

                return $html . '</div>';
            })
            ->addColumn('admin_price_user_name', function ($row) {
                if (!$this->hasAdminPriceEntered($row)) {
                    return '-';
                }

                $badge = '<span class="admin-price-saisi"><i class="fas fa-check-circle"></i> Prix admin saisi</span>';
                $date = $row->admin_price_updated_at ? '<br><span class="text-muted">' . $row->admin_price_updated_at->format('d/m/Y H:i') . '</span>' : '';

                if (!$row->adminPriceUser) {
                    return $badge . '<br><span class="text-muted">Opération</span>' . $date;
                }

                return $badge . '<br>' . e($row->adminPriceUser->name) . $date;
            })
            ->addColumn('status_display', function ($row) {
                $relance = $row->relance_yapildi ? 'Relance: Oui' : 'Relance: Non';
                return e($row->konfirme_durumu ?? '-') . '<br><span class="text-muted">' . $relance . '</span>';
            })
            ->addColumn('day_count', function ($row) {
                return $row->days ? $row->days->count() : 0;
            })
            ->addColumn('attachments', function ($row) {
                $buttons = '';

                if ($row->attachments && $row->attachments->count() > 0) {
                    foreach ($row->attachments as $attachment) {
                        $url = asset('storage/talepler_ekleri/' . $attachment->dosya_adi);
                        $buttons .= '<a href="'.$url.'" target="_blank" class="btn btn-sm btn-outline-primary mb-1">ð</a><br>';
                    }
                }

                return $buttons ?: '-';
            })
            ->addColumn('action', function ($row) {
                $btn = '<a href="'.route('talepler.show', $row->id).'" class="btn btn-sm btn-info">Voir</a> ';
                $btn .= '<a href="'.route('talepler.edit', $row->id).'" class="btn btn-sm btn-primary">Modifier</a> ';
                $btn .= '<button type="button" data-id="'.$row->id.'" class="btn btn-sm btn-danger deleteTalep">Supprimer</button>';
                return $btn;
            })
            ->rawColumns(['market_display', 'client_display', 'country_display', 'route_display', 'operation_summary', 'price_display', 'admin_price_user_name', 'status_display', 'attachments', 'action'])
            ->make(true);
    }

    return view('talepler.index', [
        'marketStats' => $this->marketStats(),
        'responsables' => User::role(['Admin', 'Superadmin'])->orderBy('name', 'asc')->get(['id', 'name']),
    ]);
}

    private function sortedValidOperations(array $operations)
    {
        return collect($operations)
            ->map(function ($operation, $index) {
                $operation['_original_order'] = $index;
                return $operation;
            })
            ->filter(fn ($operation) => $this->operationHasAnyValue($operation))
            ->sortBy(function ($operation) {
                return implode('|', [
                    $operation['service_date'] ?? '9999-12-31',
                    $this->normalizeOperationSortTime($operation['start_time'] ?? null),
                    str_pad((string) ($operation['_original_order'] ?? 0), 6, '0', STR_PAD_LEFT),
                ]);
            })
            ->values()
            ->map(function ($operation) {
                unset($operation['_original_order']);
                return $operation;
            });
    }

    private function operationHasAnyValue(array $operation): bool
    {
        return !empty($operation['service_date'])
            || !empty($operation['pickup_location'])
            || !empty($operation['dropoff_location'])
            || !empty($operation['route_description'])
            || !empty($operation['pax']);
    }

    private function normalizeOperationSortTime($time): string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return '99:99:99';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
            return sprintf('%02d:%02d:00', (int) $matches[1], (int) $matches[2]);
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Throwable) {
            return '99:99:99';
        }
    }

    private function talepFuelConsumptions(): array
    {
        $value = Option::where('name', 'talepFuelConsumptions')->value('value');
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function talepServiceTypeOptionIds(): array
    {
        $optionValue = Option::where('name', 'talepServiceTypeIds')->value('value');

        return collect(explode(',', (string) $optionValue))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function talepServiceTypeOptions(?int $selectedId = null)
    {
        $ids = $this->talepServiceTypeOptionIds();

        if ($selectedId && !in_array((int) $selectedId, $ids, true)) {
            $ids[] = (int) $selectedId;
        }

        if (empty($ids)) {
            return Servicetype::orderBy('name', 'asc')->get();
        }

        return Servicetype::whereIn('id', $ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
            ->get();
    }

    private function hasAdminPriceEntered(Talep $talep): bool
    {
        if ($talep->admin_price_user_id || $talep->admin_price_updated_at) {
            return true;
        }

        if (!$talep->relationLoaded('days') || !$talep->days) {
            return false;
        }

        return $talep->days->contains(function ($day) {
            return $day->admin_price_user_id
                || $day->admin_price_updated_at
                || (float) $day->admin_price > 0;
        });
    }

    private function applyMarketFilter($query, ?string $market): void
    {
        $market = strtolower((string) $market);

        if (!in_array($market, ['latam', 'mena', 'turkiye', 'internet', 'france'], true)) {
            return;
        }

        $userIdsByMarket = $this->marketUserIds();

        if ($market === 'internet') {
            $query->where(function ($q) {
                $q->whereIn('talep_kanali', ['Website', 'Paris Via Web'])
                    ->orWhere('talep_kanali', 'like', '%web%')
                    ->orWhere('talep_kanali', 'like', '%internet%');
            });
            return;
        }

        if (!empty($userIdsByMarket[$market])) {
            $query->whereIn('user_id', $userIdsByMarket[$market]);
        }
    }

    private function marketUserIds(): array
    {
        return [
            'france' => [106, 178],          // Cansu, Begum
            'turkiye' => [123, 141, 9],      // Damla, Gozde Yer, gozde
            'mena' => [179],                 // Hadjer / Hacer
            'latam' => [170],                // Cemile
        ];
    }

    private function marketStats(): array
    {
        $userIdsByMarket = $this->marketUserIds();
        $stats = [];

        foreach ($userIdsByMarket as $market => $userIds) {
            $stats[$market] = Talep::whereIn('user_id', $userIds)->count();
        }

        $stats['internet'] = Talep::where(function ($query) {
            $query->whereIn('talep_kanali', ['Website', 'Paris Via Web'])
                ->orWhere('talep_kanali', 'like', '%web%')
                ->orWhere('talep_kanali', 'like', '%internet%');
        })->count();

        $stats['pending_admin'] = Talep::where(function ($query) {
            $query->whereNull('final_total')
                ->orWhere('final_total', '<=', 0);
        })->count();

        return $stats;
    }

    private function marketBadgeHtml(string $market): string
    {
        $classes = [
            'latam' => 'market-latam',
            'mena' => 'market-mena',
            'turkiye' => 'market-turkiye',
            'internet' => 'market-internet',
            'france' => 'market-france',
        ];
        $labels = [
            'latam' => 'LATAM',
            'mena' => 'MENA',
            'turkiye' => 'Turquie',
            'internet' => 'Internet',
            'france' => 'France',
        ];

        return '<span class="market-badge ' . ($classes[$market] ?? 'market-france') . '">' . ($labels[$market] ?? 'France') . '</span>';
    }

    private function detectMarket($row): string
    {
        $userMarket = [
            106 => 'france',
            178 => 'france',
            123 => 'turkiye',
            141 => 'turkiye',
            9 => 'turkiye',
            179 => 'mena',
            170 => 'latam',
        ];

        if ($row->user_id && isset($userMarket[(int) $row->user_id])) {
            return $userMarket[(int) $row->user_id];
        }

        $channel = $this->normalizeMarketText($row->talep_kanali);
        if (str_contains($channel, 'website') || str_contains($channel, 'web') || str_contains($channel, 'internet')) {
            return 'internet';
        }

        return 'france';
    }

    private function normalizeMarketText($value): string
    {
        $value = mb_strtolower((string) $value, 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $ascii ?: $value;
    }

    public function options()
    {
        $serviceTypes = Servicetype::orderBy('name', 'asc')->get(['id', 'name']);
        $selectedServiceTypeIds = $this->talepServiceTypeOptionIds();
        $fuelConsumptions = $this->talepFuelConsumptions();
        $fuelPricePerLiter = Option::where('name', 'talepFuelPricePerLiter')->value('value') ?: '1.90';
        $vehicleFuelTypes = [
            'SEDAN_4' => 'Sedan 4 pax',
            'CLASS_V' => 'Class V',
            'VAN_8' => 'Van 8 pax',
            'SPRINTER_19' => 'Sprinter 19 pax',
            'MINIBUS_30' => 'Minibus 30 pax',
            'COACH_45' => 'Coach 45 pax',
            'COACH_50' => 'Coach 50 pax',
            'COACH_55' => 'Coach 55 pax',
            'COACH_60' => 'Coach 60 pax',
            'DEFAULT' => 'Default',
        ];

        return view('talepler.options', compact(
            'serviceTypes',
            'selectedServiceTypeIds',
            'fuelConsumptions',
            'fuelPricePerLiter',
            'vehicleFuelTypes'
        ));
    }

    public function updateOptions(Request $request)
    {
        $data = $request->validate([
            'service_type_ids' => 'required|array|min:1',
            'service_type_ids.*' => 'integer|exists:servicetypes,id',
            'fuel_consumptions' => 'nullable|array',
            'fuel_consumptions.*' => 'nullable|numeric|min:0|max:100',
            'fuel_price_per_liter' => 'nullable|numeric|min:0|max:10',
        ]);

        $ids = collect($data['service_type_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        Option::updateOrCreate(
            ['name' => 'talepServiceTypeIds'],
            ['value' => $ids->implode(',')]
        );

        $fuelConsumptions = collect($data['fuel_consumptions'] ?? [])
            ->map(fn ($value) => $value === null || $value === '' ? null : round((float) $value, 2))
            ->filter(fn ($value) => $value !== null && $value > 0)
            ->all();

        Option::updateOrCreate(
            ['name' => 'talepFuelConsumptions'],
            ['value' => json_encode($fuelConsumptions, JSON_UNESCAPED_UNICODE)]
        );

        Option::updateOrCreate(
            ['name' => 'talepFuelPricePerLiter'],
            ['value' => $data['fuel_price_per_liter'] ?? '']
        );

        return redirect()
            ->route('talepler.options')
            ->with('success', 'Les options de demande ont été mises à jour.');
    }

    public function create()
    {
       $acenteler = Acente::orderBy('name', 'asc')->get();
    $kullanicilar = User::orderBy('name', 'asc')->get();
    $vehicules = Vehicule::orderBy('name', 'asc')->get();
   $serviceTypes = $this->talepServiceTypeOptions();
    $depots = Schema::hasTable('depots')
        ? DB::table('depots')->where(function ($query) {
            $query->whereNull('active')->orWhere('active', 1);
        })->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')->get()
        : collect();

        return view('talepler.create', compact('acenteler', 'kullanicilar', 'vehicules', 'serviceTypes', 'depots'));
    }


   public function store(Request $request, TalepPricingService $pricingService, TalepAiPricingService $aiPricingService)
{
    $data = $request->validate([
        'user_id' => 'nullable|exists:users,id',
        'acente_id' => 'nullable|exists:acentes,id',
        'talep_tarihi' => 'required|date',
        'talep_kanali' => 'required|string|max:255',
        'country' => 'nullable|string|max:100',
        'service_type_id' => 'nullable|exists:servicetypes,id',
        'vehicule_id' => 'nullable|exists:vehicules,id',
        'depot_id' => 'nullable|integer',
        'customer_name' => 'nullable|string|max:255',
        'customer_phone' => 'nullable|string|max:50',
        'customer_email' => 'nullable|email|max:255',
        'total_pax' => 'nullable|integer|min:1',
        'verilen_fiyat' => 'nullable|numeric',
        'confirmed_price' => 'nullable|numeric',
        'system_total' => 'nullable|numeric',
        'discount_price' => 'nullable|numeric|min:0',
        'second_discount_price' => 'nullable|numeric|min:0',
        'discount_valid_until' => 'nullable|date',
        'final_total' => 'nullable|numeric',
        'comment_admin' => 'nullable|string',
        'currency' => 'nullable|string|max:10',
        'relance_yapildi' => 'required|boolean',
        'konfirme_durumu' => 'required|string|max:255',
        'uzun_mesaj' => 'nullable|string',
        'internal_notes' => 'nullable|string',

        'operations' => 'nullable|array',
        'operations.*.service_date' => 'nullable|date',
        'operations.*.start_time' => 'nullable',
        'operations.*.end_time' => 'nullable',
        'operations.*.pax' => 'nullable|integer|min:1',
        'operations.*.pickup_location' => 'nullable|string',
        'operations.*.dropoff_location' => 'nullable|string',
        'operations.*.waypoints' => 'nullable|array',
        'operations.*.waypoints.*' => 'nullable|string|max:255',
        'operations.*.service_type' => 'nullable|string|max:255',
        'operations.*.vehicle_type' => 'nullable|string|max:255',
        'operations.*.depot_id' => 'nullable|integer',

        'operations.*.route_description' => 'nullable|string|max:255',
        'operations.*.notes' => 'nullable|string',
        'operations.*.note_equipe' => 'nullable|string',
        'operations.*.note_admin' => 'nullable|string',
        'operations.*.distance_meters' => 'nullable|integer',
        'operations.*.duration_seconds' => 'nullable|integer',
        'operations.*.traffic_duration_seconds' => 'nullable|integer',
        'operations.*.toll_amount' => 'nullable|numeric|min:0',
        'operations.*.toll_currency' => 'nullable|string|max:10',
        'operations.*.decoucher' => 'nullable|numeric|min:0',
        'operations.*.parking' => 'nullable|numeric|min:0',
        'operations.*.checkpoint' => 'nullable|numeric|min:0',
        'operations.*.fuel_amount' => 'nullable|numeric|min:0',
        'operations.*.fuel_liters' => 'nullable|numeric|min:0',
        'operations.*.system_price' => 'nullable|numeric|min:0',
        'operations.*.final_price' => 'nullable|numeric|min:0',
        'operations.*.polyline' => 'nullable|string',
    ]);

    $data['currency'] = $data['currency'] ?? 'EUR';

    if (!auth()->user()?->hasRole('Superadmin')) {
        unset($data['final_total'], $data['comment_admin']);
    } elseif (array_key_exists('final_total', $data) && $data['final_total'] !== null) {
        $data['admin_price_user_id'] = auth()->id();
        $data['admin_price_updated_at'] = now();
        $data['is_manual_override'] = 1;
    }

    if (!Schema::hasColumn('talepler', 'country')) {
        unset($data['country']);
    }
    if (!Schema::hasColumn('talepler', 'comment_admin')) {
        unset($data['comment_admin']);
    }
    if (!Schema::hasColumn('talepler', 'depot_id')) {
        unset($data['depot_id']);
    }

    $operations = $this->sortedValidOperations($data['operations'] ?? []);
    unset($data['operations']);

    $talep = Talep::create($data);

    foreach ($operations as $index => $operation) {
        \App\Models\TalepDay::create((Schema::hasColumn('talep_days', 'depot_id') ? [
            'depot_id' => $operation['depot_id'] ?? null,
        ] : []) + [
            'talep_id' => $talep->id,
            'day_number' => $index + 1,
            'service_date' => $operation['service_date'] ?? null,
            'start_time' => $operation['start_time'] ?? null,
            'end_time' => $operation['end_time'] ?? null,
            'service_type' => $operation['service_type'] ?? null,
            'vehicle_type' => $operation['vehicle_type'] ?? null,
            'pax' => $operation['pax'] ?? null,
            'pickup_location' => $operation['pickup_location'] ?? null,
            'dropoff_location' => $operation['dropoff_location'] ?? null,
            'via_points_json' => !empty($operation['waypoints']) ? array_values(array_filter($operation['waypoints'])) : null,
            'route_description' => $operation['route_description'] ?? null,
            'notes' => $operation['notes'] ?? null,
        ] + (Schema::hasColumn('talep_days', 'note_equipe') ? [
            'note_equipe' => $operation['note_equipe'] ?? null,
        ] : []) + (auth()->user()?->hasRole('Superadmin') && Schema::hasColumn('talep_days', 'note_admin') ? [
            'note_admin' => $operation['note_admin'] ?? null,
        ] : []) + [
            'distance_meters' => $operation['distance_meters'] ?? null,
            'duration_seconds' => $operation['duration_seconds'] ?? null,
            'traffic_duration_seconds' => $operation['traffic_duration_seconds'] ?? null,
            'toll_amount' => $operation['toll_amount'] ?? null,
            'toll_currency' => $operation['toll_currency'] ?? 'EUR',
            'decoucher' => $operation['decoucher'] ?? null,
            'parking' => $operation['parking'] ?? null,
            'checkpoint' => $operation['checkpoint'] ?? null,
            'fuel_amount' => $operation['fuel_amount'] ?? null,
            'fuel_liters' => $operation['fuel_liters'] ?? null,
            'system_price' => $operation['system_price'] ?? null,
            'final_price' => $operation['final_price'] ?? null,
            'polyline' => $operation['polyline'] ?? null,
        ]);
    }

    $createdOperationsCount = $talep->days()->count();
    $this->addTalepHistory($talep, 'Création', 'Demande', null, '#' . $talep->id, 'Demande créée.');
    if ($createdOperationsCount > 0) {
        $this->addTalepHistory($talep, 'Création', 'Opérations', null, $createdOperationsCount, $createdOperationsCount . ' opération(s) créée(s).');
    }
    $this->refreshAutomaticPricing($talep, $pricingService, $aiPricingService);

    return redirect()
        ->route('talepler.edit', $talep->id)
        ->with('success', 'La demande a été créée avec succès.');
}
   public function edit(Talep $talep)
{
    $talep->load([
        'acente',
        'user',
        'attachments',
        'days.route',
        'quote',
        'mailler',
        'vehicule',
        'servicetype',
        'convertedTransfer.post',
    ]);

    $acenteler = Acente::orderBy('name', 'asc')->get();
    $kullanicilar = User::orderBy('name', 'asc')->get();
    $vehicules = Vehicule::orderBy('name', 'asc')->get();
    $serviceTypes = $this->talepServiceTypeOptions($talep->service_type_id);
    $depots = Schema::hasTable('depots')
        ? DB::table('depots')->where(function ($query) {
            $query->whereNull('active')->orWhere('active', 1);
        })->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')->get()
        : collect();

    return view('talepler.edit', compact(
        'talep',
        'acenteler',
        'kullanicilar',
        'vehicules',
        'serviceTypes',
        'depots'
    ));
}

  public function update(Request $request, Talep $talep, TalepPricingService $pricingService, TalepAiPricingService $aiPricingService)
{
    $originalFinalTotal = $talep->final_total;
    $data = $request->validate([
        'user_id' => 'nullable|exists:users,id',
        'acente_id' => 'nullable|exists:acentes,id',
        'talep_tarihi' => 'required|date',
        'talep_kanali' => 'required|string|max:255',
        'country' => 'nullable|string|max:100',
        'service_type_id' => 'nullable|exists:servicetypes,id',
        'vehicule_id' => 'nullable|exists:vehicules,id',
        'depot_id' => 'nullable|integer',
        'customer_name' => 'nullable|string|max:255',
        'customer_phone' => 'nullable|string|max:50',
        'customer_email' => 'nullable|email|max:255',
        'total_pax' => 'nullable|integer|min:1',
        'pickup_location' => 'nullable|string',
        'dropoff_location' => 'nullable|string',
        'verilen_fiyat' => 'nullable|numeric',
        'confirmed_price' => 'nullable|numeric',
        'system_total' => 'nullable|numeric',
        'discount_price' => 'nullable|numeric|min:0',
        'second_discount_price' => 'nullable|numeric|min:0',
        'discount_valid_until' => 'nullable|date',
        'final_total' => 'nullable|numeric',
        'comment_admin' => 'nullable|string',
        'currency' => 'nullable|string|max:10',
        'relance_yapildi' => 'required|boolean',
        'konfirme_durumu' => 'required|string|max:255',
        'uzun_mesaj' => 'nullable|string',
        'internal_notes' => 'nullable|string',

        'operations' => 'nullable|array',
        'operations.*.id' => 'nullable|integer|exists:talep_days,id',
        'operations.*.service_date' => 'nullable|date',
        'operations.*.service_type' => 'nullable|string|max:255',
        'operations.*.vehicle_type' => 'nullable|string|max:255',
        'operations.*.depot_id' => 'nullable|integer',

        'operations.*.start_time' => 'nullable',
        'operations.*.end_time' => 'nullable',
        'operations.*.pax' => 'nullable|integer|min:1',
        'operations.*.pickup_location' => 'nullable|string',
        'operations.*.dropoff_location' => 'nullable|string',
        'operations.*.waypoints' => 'nullable|array',
        'operations.*.waypoints.*' => 'nullable|string|max:255',
        'operations.*.route_description' => 'nullable|string|max:255',
        'operations.*.notes' => 'nullable|string',
        'operations.*.note_equipe' => 'nullable|string',
        'operations.*.note_admin' => 'nullable|string',
        'operations.*.distance_meters' => 'nullable|integer',
        'operations.*.duration_seconds' => 'nullable|integer',
        'operations.*.traffic_duration_seconds' => 'nullable|integer',
        'operations.*.toll_amount' => 'nullable|numeric|min:0',
        'operations.*.toll_currency' => 'nullable|string|max:10',
        'operations.*.decoucher' => 'nullable|numeric|min:0',
        'operations.*.parking' => 'nullable|numeric|min:0',
        'operations.*.checkpoint' => 'nullable|numeric|min:0',
        'operations.*.fuel_amount' => 'nullable|numeric|min:0',
        'operations.*.fuel_liters' => 'nullable|numeric|min:0',
        'operations.*.system_price' => 'nullable|numeric|min:0',
        'operations.*.final_price' => 'nullable|numeric|min:0',
        'operations.*.polyline' => 'nullable|string',
    ]);

    if (!auth()->user()?->hasRole('Superadmin')) {
        unset($data['final_total'], $data['comment_admin']);
    } elseif (array_key_exists('final_total', $data) && $data['final_total'] !== null) {
        if ((string) $originalFinalTotal !== (string) $data['final_total'] || !$talep->admin_price_user_id) {
            $data['admin_price_user_id'] = auth()->id();
            $data['admin_price_updated_at'] = now();
            $data['is_manual_override'] = 1;
        }
    }

    if (!Schema::hasColumn('talepler', 'comment_admin')) {
        unset($data['comment_admin']);
    }
    if (!Schema::hasColumn('talepler', 'country')) {
        unset($data['country']);
    }

    if (!Schema::hasColumn('talepler', 'depot_id')) {
        unset($data['depot_id']);
    }

    $operations = $data['operations'] ?? [];
    unset($data['operations']);
    $operationsSubmitted = $request->has('operations');
    $historyBefore = $talep->only(array_keys($data));
    $notifyAdminPriceUpdated = array_key_exists('final_total', $data)
        && (string) $originalFinalTotal !== (string) ($data['final_total'] ?? null);
    $convertedPost = null;

    DB::transaction(function () use ($talep, $data, $operations, $operationsSubmitted, &$convertedPost) {
        $talep->update($data);

        if ($operationsSubmitted) {
            $validOperations = $this->sortedValidOperations($operations);

            $submittedIds = $validOperations
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            $talep->days()
                ->when(count($submittedIds) > 0, function ($query) use ($submittedIds) {
                    $query->whereNotIn('id', $submittedIds);
                })
                ->when(count($submittedIds) === 0, function ($query) {
                    $query;
                })
                ->delete();

            foreach ($validOperations as $index => $operation) {
                $payload = [
                    'day_number' => $index + 1,
                    'service_date' => $operation['service_date'] ?? null,
                    'start_time' => $operation['start_time'] ?? null,
                    'end_time' => $operation['end_time'] ?? null,
                    'service_type' => $operation['service_type'] ?? null,
                    'vehicle_type' => $operation['vehicle_type'] ?? null,
                    'pax' => $operation['pax'] ?? null,
                    'pickup_location' => $operation['pickup_location'] ?? null,
                    'dropoff_location' => $operation['dropoff_location'] ?? null,
                    'via_points_json' => !empty($operation['waypoints']) ? array_values(array_filter($operation['waypoints'])) : null,
                    'route_description' => $operation['route_description'] ?? null,
                    'notes' => $operation['notes'] ?? null,
                ] + (Schema::hasColumn('talep_days', 'note_equipe') ? [
                    'note_equipe' => $operation['note_equipe'] ?? null,
                ] : []) + (auth()->user()?->hasRole('Superadmin') && Schema::hasColumn('talep_days', 'note_admin') ? [
                    'note_admin' => $operation['note_admin'] ?? null,
                ] : []) + [
                    'distance_meters' => $operation['distance_meters'] ?? null,
                    'duration_seconds' => $operation['duration_seconds'] ?? null,
                    'traffic_duration_seconds' => $operation['traffic_duration_seconds'] ?? null,
                    'toll_amount' => $operation['toll_amount'] ?? null,
                    'toll_currency' => $operation['toll_currency'] ?? 'EUR',
                    'decoucher' => $operation['decoucher'] ?? null,
                    'parking' => $operation['parking'] ?? null,
                    'checkpoint' => $operation['checkpoint'] ?? null,
                    'fuel_amount' => $operation['fuel_amount'] ?? null,
                    'fuel_liters' => $operation['fuel_liters'] ?? null,
                    'system_price' => $operation['system_price'] ?? null,
                    'final_price' => $operation['final_price'] ?? null,
                    'polyline' => $operation['polyline'] ?? null,
                ] + (Schema::hasColumn('talep_days', 'depot_id') ? [
                    'depot_id' => $operation['depot_id'] ?? null,
                ] : []);

                if (!empty($operation['id'])) {
                    $day = $talep->days()->where('id', $operation['id'])->first();

                    if ($day) {
                        $day->update($payload);
                        continue;
                    }
                }

                $talep->days()->create($payload);
            }
        }

        if ($this->isTalepConfirmedStatus($talep->konfirme_durumu)) {
            $convertedPost = $this->ensureTalepConvertedToPostAndTransfers($talep);
        }
    });

    $this->refreshAutomaticPricing($talep, $pricingService, $aiPricingService);
    $this->logTalepChanges($talep, $historyBefore, 'Modification');
    if ($operationsSubmitted) {
        $operationsCount = $talep->days()->count();
        $this->addTalepHistory($talep, 'Modification', 'Opérations', null, $operationsCount, $operationsCount . ' opération(s) enregistrée(s).');
    }

    if ($notifyAdminPriceUpdated) {
        $talep->refresh();
        $this->notifyTalepAdminPriceUpdated($talep, $originalFinalTotal, $talep->final_total);
    }

    $message = 'La demande a été mise à jour avec succès.';
    if ($convertedPost) {
        $message .= ' Dossier créé: #' . $convertedPost->id . '.';
    }

    return redirect()
        ->route('talepler.edit', $talep->id)
        ->with('success', $message);
}

   public function show(Talep $talep)
{
    $talep->load([
        'acente',
        'user',
        'attachments',
        'days.route',
        'quote',
        'histories.user',
        'mailler',
        'vehicule',
        'serviceType',
        'adminPriceUser',
        'convertedTransfer.post.status',
    ]);

    $previousTalep = Talep::where('id', '<', $talep->id)->orderByDesc('id')->first(['id']);
    $nextTalep = Talep::where('id', '>', $talep->id)->orderBy('id')->first(['id']);

    return view('talepler.show', compact('talep', 'previousTalep', 'nextTalep'));
}

   public function recalculatePrice(Talep $talep, TalepPricingService $pricingService)
{
    $talep->loadMissing('quote');
    $oldSystemTotal = $talep->quote?->system_total;
    $quote = $pricingService->calculate($talep, true);
    $this->addTalepHistory($talep, 'Calcul prix', 'Prix AI', $oldSystemTotal, $quote->system_total, 'Prix système recalculé.');

    return redirect()
        ->route('talepler.show', $talep->id)
        ->with('success', 'Le prix système a été recalculé: ' . number_format((float) $quote->system_total, 2, ',', ' ') . ' ' . ($quote->currency ?? 'EUR'));
}

   public function aiPriceSuggestion(Talep $talep, TalepAiPricingService $aiPricing)
{
    abort_unless(auth()->user()?->hasAnyRole(['Superadmin', 'Admin']), 403);

    try {
        return response()->json($aiPricing->calculate($talep));
    } catch (\Throwable $e) {
        \Log::error('Erreur suggestion prix IA Paris Via', [
            'talep_id' => $talep->id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'message' => 'La suggestion de prix est temporairement indisponible.',
        ], 503);
    }
}

   private function refreshAutomaticPricing(
       Talep $talep,
       TalepPricingService $pricingService,
       TalepAiPricingService $aiPricingService
   ): void {
       if (!$talep->days()->exists()) {
           return;
       }

       try {
           $pricingService->calculate($talep->fresh('days'), true);
       } catch (\Throwable $e) {
           \Log::warning('Calcul automatique du devis système impossible', [
               'talep_id' => $talep->id,
               'error' => $e->getMessage(),
           ]);
       }

       try {
           $aiPricingService->calculate($talep->fresh('days'));
       } catch (\Throwable $e) {
           \Log::warning('Calcul automatique de la suggestion IA impossible', [
               'talep_id' => $talep->id,
               'error' => $e->getMessage(),
           ]);
       }
   }

   public function destroy($id)
{
    $talep = Talep::with(['days', 'attachments'])->findOrFail($id);

    \DB::beginTransaction();

    try {
        $talep->days()->delete();
        $talep->attachments()->delete();
        $talep->delete();

        \DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'La demande a été supprimée avec succès.'
        ]);
    } catch (\Throwable $e) {
        \DB::rollBack();

        \Log::error('Erreur lors de la suppression de la demande', [
            'talep_id' => $id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Supprimerme sırasında hata oluştu.'
        ], 500);
    }
}

    public function updateKonfirmeDurumu(Request $request, $id)
    {
        $request->validate([
            'konfirme_durumu' => 'required|string|max:255',
        ]);

        $convertedPost = null;
        $talepForHistory = null;
        $oldStatus = null;
        $newStatus = null;

        DB::transaction(function () use ($id, $request, &$convertedPost, &$talepForHistory, &$oldStatus, &$newStatus) {
            $talep = Talep::with('days')->lockForUpdate()->findOrFail($id);
            $oldStatus = $talep->konfirme_durumu;
            $talep->konfirme_durumu = $request->konfirme_durumu;

            if ($this->isTalepConfirmedStatus($request->konfirme_durumu) && !$talep->confirmed_at) {
                $talep->confirmed_at = now();
            }

            $talep->save();

            if ($this->isTalepConfirmedStatus($talep->konfirme_durumu)) {
                $convertedPost = $this->ensureTalepConvertedToPostAndTransfers($talep);
            }

            $newStatus = $talep->konfirme_durumu;
            $talepForHistory = $talep;
        });

        if ($talepForHistory) {
            $this->addTalepHistory($talepForHistory, 'Modification statut', 'Statut', $oldStatus, $newStatus, 'Statut de la demande mis à jour.');
            if ($convertedPost) {
                $this->addTalepHistory($talepForHistory, 'Conversion', 'Dossier', null, '#' . $convertedPost->id, 'Dossier créé depuis la demande.');
            }
        }

        return response()->json([
            'success' => true,
            'post_id' => $convertedPost?->id,
            'post_url' => $convertedPost ? route('posts.show', $convertedPost->id) : null,
            'message' => $convertedPost
                ? 'La demande est confirmée. Le dossier #' . $convertedPost->id . ' a été créé.'
                : 'Le statut de la demande a été mis à jour.',
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $talep = Talep::findOrFail($id);
        $oldUserId = $talep->user_id;
        $talep->update([
            'user_id' => $request->user_id
        ]);
        $this->addTalepHistory(
            $talep,
            'Modification',
            'Responsable',
            User::find($oldUserId)?->name ?: $oldUserId,
            User::find($request->user_id)?->name ?: $request->user_id,
            'Responsable modifié.'
        );

        return response()->json(['success' => true]);
    }

    public function updateAcente(Request $request, $id)
    {
        $request->validate([
            'acente_id' => 'nullable|exists:acentes,id',
        ]);

        $talep = Talep::findOrFail($id);
        $oldAcenteId = $talep->acente_id;
        $talep->update([
            'acente_id' => $request->acente_id
        ]);
        $this->addTalepHistory(
            $talep,
            'Modification',
            'Agence',
            Acente::find($oldAcenteId)?->name ?: $oldAcenteId,
            Acente::find($request->acente_id)?->name ?: $request->acente_id,
            'Agence modifiée.'
        );

        return response()->json(['success' => true]);
    }

    public function updateFiyat(Request $request, $id)
    {
        if (!auth()->user()?->hasRole('Superadmin')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n’êtes pas autorisé à modifier le prix admin.',
            ], 403);
        }

        $data = $request->validate([
            'final_total' => 'nullable|numeric',
            'comment_admin' => 'nullable|string',
            'discount_price' => 'nullable|numeric|min:0',
            'second_discount_price' => 'nullable|numeric|min:0',
            'discount_valid_until' => 'nullable|date',
        ]);

        $payload = [
            'final_total' => $data['final_total'] ?? null,
            'admin_price_user_id' => auth()->id(),
            'admin_price_updated_at' => now(),
            'is_manual_override' => 1,
        ];

        if (Schema::hasColumn('talepler', 'comment_admin')) {
            $payload['comment_admin'] = $data['comment_admin'] ?? null;
        }
        if (Schema::hasColumn('talepler', 'discount_price')) {
            $payload['discount_price'] = $data['discount_price'] ?? null;
        }
        if (Schema::hasColumn('talepler', 'second_discount_price')) {
            $payload['second_discount_price'] = $data['second_discount_price'] ?? null;
        }
        if (Schema::hasColumn('talepler', 'discount_valid_until')) {
            $payload['discount_valid_until'] = $data['discount_valid_until'] ?? null;
        }

        $talep = Talep::findOrFail($id);
        $historyBefore = $talep->only(array_keys($payload));
        $originalFinalTotal = $talep->final_total;
        $talep->update($payload);
        $this->logTalepChanges($talep, $historyBefore, 'Modification prix admin / remises');
        $talep->load('adminPriceUser');
        if ((string) $originalFinalTotal !== (string) $talep->final_total) {
            $this->notifyTalepAdminPriceUpdated($talep, $originalFinalTotal, $talep->final_total);
        }

        return response()->json([
            'success' => true,
            'message' => 'Le prix admin et les remises ont été mis à jour.',
            'admin_price_user_name' => $talep->adminPriceUser->name ?? '-',
            'admin_price_updated_at' => optional($talep->admin_price_updated_at)->format('d/m/Y H:i'),
        ]);
    }

    public function updateOperationAdminNote(Request $request, Talep $talep, TalepDay $day)
    {
        if (!auth()->user()?->hasRole('Superadmin')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n’êtes pas autorisé à modifier la note admin.',
            ], 403);
        }

        if ((int) $day->talep_id !== (int) $talep->id) {
            abort(404);
        }

        if (!Schema::hasColumn('talep_days', 'note_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'La colonne note_admin est manquante. Exécutez le SQL prévu avant d’enregistrer.',
            ], 422);
        }

        $data = $request->validate([
            'note_admin' => 'nullable|string',
        ]);

        $oldNote = $day->note_admin;
        $day->update([
            'note_admin' => $data['note_admin'] ?? null,
        ]);
        $this->addTalepHistory(
            $talep,
            'Modification',
            'Note admin opération #' . ($day->day_number ?? $day->id),
            $oldNote,
            $day->note_admin,
            'Note admin opération enregistrée.'
        );

        return response()->json([
            'success' => true,
            'message' => 'La note admin a été enregistrée.',
            'note_admin' => $day->note_admin,
        ]);
    }

    public function updateOperationPrices(Request $request, $id)
    {
        if (!auth()->user()?->hasRole('Superadmin')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n’êtes pas autorisé à modifier les prix admin.',
            ], 403);
        }

        $data = $request->validate([
            'prices' => 'required|array',
            'prices.*' => 'nullable|numeric|min:0',
        ]);

        $talep = Talep::with('days')->findOrFail($id);
        $originalFinalTotal = $talep->final_total;
        $prices = collect($data['prices']);
        $total = 0;
        $hasPrice = false;
        $changedPrices = 0;

        DB::transaction(function () use ($talep, $prices, &$total, &$hasPrice, &$changedPrices) {
            foreach ($talep->days as $day) {
                if (!$prices->has((string) $day->id) && !$prices->has($day->id)) {
                    continue;
                }

                $rawPrice = $prices->get((string) $day->id, $prices->get($day->id));
                $price = $rawPrice === null || $rawPrice === '' ? null : (float) $rawPrice;
                if ((string) $day->admin_price !== (string) $price) {
                    $changedPrices++;
                }

                $day->forceFill([
                    'admin_price' => $price,
                    'admin_price_user_id' => auth()->id(),
                    'admin_price_updated_at' => now(),
                ])->save();

                if ($price !== null) {
                    $hasPrice = true;
                    $total += $price;
                }
            }

            $talep->update([
                'final_total' => $hasPrice ? $total : null,
                'admin_price_user_id' => auth()->id(),
                'admin_price_updated_at' => now(),
                'is_manual_override' => 1,
            ]);
        });

        $talep->refresh()->load('adminPriceUser');
        $this->addTalepHistory(
            $talep,
            'Modification prix admin',
            'Prix admin opérations',
            $originalFinalTotal,
            $talep->final_total,
            $changedPrices . ' opération(s) modifiée(s).'
        );
        if ((string) $originalFinalTotal !== (string) $talep->final_total) {
            $this->notifyTalepAdminPriceUpdated($talep, $originalFinalTotal, $talep->final_total);
        }

        return response()->json([
            'success' => true,
            'message' => 'Les prix admin par opération ont été mis à jour.',
            'total' => $talep->final_total,
            'total_formatted' => $talep->final_total !== null
                ? number_format((float) $talep->final_total, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR')
                : '-',
            'admin_price_user_name' => $talep->adminPriceUser->name ?? '-',
            'admin_price_updated_at' => optional($talep->admin_price_updated_at)->format('d/m/Y H:i'),
        ]);
    }

    private function notifyTalepAdminPriceUpdated(Talep $talep, $oldPrice, $newPrice): void
    {
        if (!$talep->user_id || (int) $talep->user_id === (int) auth()->id()) {
            return;
        }

        $user = User::find($talep->user_id);
        if (!$user) {
            return;
        }

        $old = $oldPrice === null || $oldPrice === '' ? null : (float) $oldPrice;
        $new = $newPrice === null || $newPrice === '' ? null : (float) $newPrice;

        $user->notify(new TalepAdminPriceUpdated(
            $talep,
            $old,
            $new,
            auth()->user()?->name
        ));
    }

    private function isTalepConfirmedStatus(?string $status): bool
    {
        $normalized = $this->normalizeMarketText($status);

        return in_array($normalized, ['confirme', 'confirmed'], true);
    }

    private function ensureTalepConvertedToPostAndTransfers(Talep $talep): ?Post
    {
        $talep->refresh()->load(['days', 'acente', 'user', 'vehicule', 'servicetype']);

        if ($talep->converted_transfer_id) {
            $existingTransfer = Transfer::withTrashed()->find($talep->converted_transfer_id);
            if ($existingTransfer && $existingTransfer->post_id) {
                return Post::withTrashed()->find($existingTransfer->post_id);
            }
        }

        if (!$talep->acente_id) {
            throw ValidationException::withMessages([
                'acente_id' => 'Sélectionnez une agence avant de confirmer la demande.',
            ]);
        }

        $days = $talep->days->filter(function ($day) use ($talep) {
            return $day->service_date
                || $day->pickup_location
                || $day->dropoff_location
                || $talep->pickup_location
                || $talep->dropoff_location;
        })->values();

        if ($days->isEmpty()) {
            throw ValidationException::withMessages([
                'operations' => 'Ajoutez au moins une opération avant de confirmer la demande.',
            ]);
        }

        $preparedDays = $days->map(fn ($day) => $this->prepareTalepDayForTransfer($talep, $day));
        $firstStart = $preparedDays->min('start');
        $lastEnd = $preparedDays->max('end');

        $post = Post::create([
            'title' => mb_substr('Demande #' . $talep->id . ' - ' . ($talep->customer_name ?: optional($talep->acente)->name ?: 'Client'), 0, 100),
            'body' => mb_substr($this->buildTalepPostBody($talep), 0, 1000),
            'start_date' => $firstStart,
            'end_date' => $lastEnd,
            'acente_id' => $talep->acente_id,
            'pax' => $talep->total_pax ?: (int) $days->sum('pax') ?: 1,
            'child' => 0,
            'status_id' => 3,
            'user_id' => $talep->user_id ?: auth()->id(),
            'billing_status' => 'to_invoice',
            'payment_status' => 'not_received',
        ]);

        $this->createClientFromTalep($talep, $post);

        $firstTransfer = null;
        foreach ($preparedDays as $prepared) {
            $transfer = $this->createTransferFromPreparedTalepDay($talep, $post, $prepared);
            $firstTransfer = $firstTransfer ?: $transfer;
        }

        if ($firstTransfer) {
            $talep->forceFill([
                'converted_transfer_id' => $firstTransfer->id,
                'confirmed_at' => $talep->confirmed_at ?: now(),
            ])->save();

            LogActivity::addToLog('Demande convertie en dossier.', $post->id, 'Talep #' . $talep->id);
        }

        return $post;
    }

    private function prepareTalepDayForTransfer(Talep $talep, $day): array
    {
        $serviceDate = $day->service_date;
        if (!$serviceDate) {
            throw ValidationException::withMessages([
                'operations' => 'Chaque opération confirmée doit avoir une date de service.',
            ]);
        }

        if (!$day->start_time) {
            throw ValidationException::withMessages([
                'operations' => 'Chaque opération confirmée doit avoir une heure de début.',
            ]);
        }

        $pickup = trim((string) ($day->pickup_location ?: $talep->pickup_location));
        $dropoff = trim((string) ($day->dropoff_location ?: $talep->dropoff_location));

        if (!$this->isUsableRouteStop($pickup) || !$this->isUsableRouteStop($dropoff)) {
            throw ValidationException::withMessages([
                'operations' => 'Chaque opération confirmée doit avoir un lieu de départ et un lieu d’arrivée.',
            ]);
        }

        $date = Carbon::parse($serviceDate)->format('Y-m-d');
        $start = Carbon::parse($date . ' ' . substr((string) $day->start_time, 0, 5));

        if ($day->end_time) {
            $end = Carbon::parse($date . ' ' . substr((string) $day->end_time, 0, 5));
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }
        } elseif ($day->duration_seconds) {
            $end = $start->copy()->addSeconds((int) $day->duration_seconds);
        } else {
            $end = $start->copy()->addHour();
        }

        $waypoints = $day->via_points_json ?? [];
        if (is_string($waypoints)) {
            $decoded = json_decode($waypoints, true);
            $waypoints = is_array($decoded) ? $decoded : [];
        }

        $waypoints = collect((array) $waypoints)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $this->isUsableRouteStop($value))
            ->values()
            ->all();

        return [
            'day' => $day,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'pickup' => $pickup,
            'dropoff' => $dropoff,
            'waypoints' => $waypoints,
            'service_type_id' => $this->resolveServiceTypeIdForTalepDay($talep, $day),
            'vehicule_id' => $talep->vehicule_id ?: $this->defaultVehiculeId(),
            'driver_id' => $this->defaultDriverId(),
            'pax' => $day->pax ?: $talep->total_pax ?: 1,
            'km' => $day->distance_meters ? (int) round(((int) $day->distance_meters) / 1000) : 0,
        ];
    }

    private function createTransferFromPreparedTalepDay(Talep $talep, Post $post, array $prepared): Transfer
    {
        $day = $prepared['day'];

        $transfer = new Transfer();
        $transfer->post_id = $post->id;
        $transfer->start_date = $prepared['start'];
        $transfer->end_date = $prepared['end'];
        $transfer->servicetype_id = $prepared['service_type_id'];
        $transfer->from = $prepared['pickup'];
        $transfer->target = $prepared['dropoff'];
        $transfer->pax = $prepared['pax'];
        $transfer->comments = trim(implode("\n", array_filter([
            'Créé depuis la demande #' . $talep->id,
            $day->route_description,
            $day->notes,
        ])));
        $transfer->vehicule_id = $prepared['vehicule_id'];
        $transfer->driver_id = $prepared['driver_id'];
        $transfer->km = $prepared['km'];
        $transfer->mission = 0;
        $transfer->accueil = 0;
        $transfer->status_id = 2;
        if (Schema::hasColumn('transfers', 'depot_id') && Schema::hasColumn('talep_days', 'depot_id')) {
            $transfer->depot_id = $day->depot_id ?: $talep->depot_id;
            $transfer->depot_source = $day->depot_id ? 'talep_operation' : ($talep->depot_id ? 'talep' : null);
        }
        $transfer->save();

        foreach ($this->buildTrajetsForPreparedTalepDay($prepared) as $trajetData) {
            $trajet = new Trajet();
            $trajet->transfer_id = $transfer->id;
            $trajet->type = $trajetData['type'];
            $trajet->from = $trajetData['from'];
            $trajet->google_address = $trajetData['google_address'];
            $trajet->datetime = $trajetData['datetime'];
            $trajet->order = $trajetData['order'];
            $trajet->save();
        }

        HareketHelper::create($transfer, [
            'aciklama' => 'Transfer créé depuis la demande #' . $talep->id,
            'tarih' => $prepared['start'],
            'post_id' => $post->id,
            'amount' => 0,
            'ab' => 2,
            'kur_id' => 1,
            'acente_id' => $prepared['driver_id'],
        ]);

        return $transfer;
    }

    private function buildTrajetsForPreparedTalepDay(array $prepared): array
    {
        $start = Carbon::parse($prepared['start']);
        $end = Carbon::parse($prepared['end']);
        $waypoints = $prepared['waypoints'];
        $segments = count($waypoints) + 1;
        $totalSeconds = max(60, $end->diffInSeconds($start));

        $trajets = [[
            'type' => 'depart',
            'from' => $prepared['pickup'],
            'google_address' => $prepared['pickup'],
            'datetime' => $start->toDateTimeString(),
            'order' => 1,
        ]];

        foreach ($waypoints as $index => $waypoint) {
            $trajets[] = [
                'type' => 'etape',
                'from' => $waypoint,
                'google_address' => $waypoint,
                'datetime' => $start->copy()->addSeconds((int) round($totalSeconds * (($index + 1) / $segments)))->toDateTimeString(),
                'order' => $index + 2,
            ];
        }

        $trajets[] = [
            'type' => 'arrivee',
            'from' => $prepared['dropoff'],
            'google_address' => $prepared['dropoff'],
            'datetime' => $end->toDateTimeString(),
            'order' => count($trajets) + 1,
        ];

        return $trajets;
    }

    private function createClientFromTalep(Talep $talep, Post $post): void
    {
        if (!$talep->customer_name && !$talep->customer_phone && !$talep->customer_email) {
            return;
        }

        Client::create([
            'title' => '',
            'name' => $talep->customer_name ?: 'Client',
            'surname' => '',
            'email' => $talep->customer_email,
            'tel' => $talep->customer_phone,
            'post_id' => $post->id,
            'comments' => 'Créé depuis la demande #' . $talep->id,
        ]);
    }

    private function buildTalepPostBody(Talep $talep): string
    {
        return trim(implode("\n", array_filter([
            'Demande #' . $talep->id,
            $talep->request_no ? 'Référence: ' . $talep->request_no : null,
            $talep->talep_kanali ? 'Canal: ' . $talep->talep_kanali : null,
            $talep->customer_name ? 'Client: ' . $talep->customer_name : null,
            $talep->customer_phone ? 'Téléphone: ' . $talep->customer_phone : null,
            $talep->customer_email ? 'Email: ' . $talep->customer_email : null,
            $talep->confirmed_price ? 'Prix confirmé: ' . $talep->confirmed_price . ' ' . ($talep->currency ?: 'EUR') : null,
            $talep->uzun_mesaj,
            $talep->internal_notes ? 'Notes internes: ' . $talep->internal_notes : null,
        ])));
    }

    private function resolveServiceTypeIdForTalepDay(Talep $talep, $day): int
    {
        if ($talep->service_type_id) {
            return (int) $talep->service_type_id;
        }

        $label = trim((string) ($day->service_type ?: $talep->service_type));
        if ($label !== '') {
            $exact = Servicetype::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($label, 'UTF-8')])->value('id');
            if ($exact) {
                return (int) $exact;
            }

            $like = Servicetype::where('name', 'like', '%' . $label . '%')->value('id');
            if ($like) {
                return (int) $like;
            }
        }

        return (int) (Servicetype::where('name', 'Transfert')->orderBy('id')->value('id')
            ?: Servicetype::orderBy('id')->value('id'));
    }

    private function defaultDriverId(): int
    {
        return (int) (Acente::whereRaw('TRIM(name) = ?', ['---'])->value('id')
            ?: Acente::whereRaw('TRIM(name) = ?', ['----'])->value('id')
            ?: Acente::orderBy('id')->value('id'));
    }

    private function defaultVehiculeId(): int
    {
        return (int) (Vehicule::whereRaw('TRIM(name) = ?', ['-'])->value('id')
            ?: Vehicule::orderBy('id')->value('id'));
    }

    public function updateRelance(Request $request, $id)
    {
        $request->validate([
            'relance_yapildi' => 'required|boolean',
        ]);

        $talep = Talep::findOrFail($id);
        $oldRelance = $talep->relance_yapildi;
        $talep->update([
            'relance_yapildi' => $request->relance_yapildi
        ]);
        $this->addTalepHistory($talep, 'Modification', 'Relance', $oldRelance, $talep->relance_yapildi, 'Relance mise à jour.');

        return response()->json(['success' => true]);
    }

    public function updateUzunMesaj(Request $request, $id)
    {
        $request->validate([
            'uzun_mesaj' => 'nullable|string',
        ]);

        $talep = Talep::findOrFail($id);
        $oldMessage = $talep->uzun_mesaj;
        $talep->uzun_mesaj = $request->uzun_mesaj;
        $talep->save();
        $this->addTalepHistory($talep, 'Modification', 'Message long', $oldMessage, $talep->uzun_mesaj, 'Message long mis à jour.');

        return response()->json(['success' => true]);
    }

    public function updateInternalNotes(Request $request, $id)
    {
        $request->validate([
            'internal_notes' => 'nullable|string',
        ]);

        $talep = Talep::findOrFail($id);
        $oldNotes = $talep->internal_notes;
        $talep->update([
            'internal_notes' => $request->internal_notes,
        ]);
        $this->addTalepHistory($talep, 'Modification', 'Informations manquantes', $oldNotes, $talep->internal_notes, 'Informations manquantes enregistrées.');

        return response()->json([
            'success' => true,
            'message' => 'Les informations manquantes ont été enregistrées.',
        ]);
    }

    public function storeparisvia(Request $request)
    {
        $data = $request->validate([
            'acente_id' => 'nullable|exists:acentes,id',
            'talep_tarihi' => 'required|date',
            'uzun_mesaj' => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'pickup_location' => 'nullable|string',
            'dropoff_location' => 'nullable|string',
            'total_pax' => 'nullable|integer|min:1',
            'vehicle_type' => 'nullable|string|max:100',
            'service_type' => 'nullable|string|max:100',
        ]);

        $data['user_id'] = 106;
        $data['talep_kanali'] = 'Paris Via Web';
        $data['relance_yapildi'] = 0;
        $data['konfirme_durumu'] = 'En attente';
        $data['currency'] = 'EUR';
        $data['is_manual_override'] = 0;
        if (!Schema::hasColumn('talepler', 'country')) {
            unset($data['country']);
        }

        $talep = Talep::create($data);

        return response()->json([
            'success' => 'La demande a été enregistrée avec succès!',
            'talep_id' => $talep->id,
        ]);
    }


    private function normalizeGeneratedTalepXmlDates(string $xml, string $defaultDateDemande, string $sourceText = ''): string
    {
        $sourceText = strtolower($sourceText);

        $sourceMentionsDate = function (string $value) use ($sourceText): bool {
            $value = trim($value);
            if ($value === '') {
                return false;
            }

            $datePart = substr($value, 0, 10);
            $compactDate = str_replace('-', '', $datePart);
            $slashDate = str_replace('-', '/', $datePart);
            $frDate = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $datePart, $m)
                ? $m[3] . '/' . $m[2] . '/' . $m[1]
                : '';

            return str_contains($sourceText, strtolower($datePart))
                || str_contains($sourceText, strtolower($compactDate))
                || str_contains($sourceText, strtolower($slashDate))
                || ($frDate !== '' && str_contains($sourceText, strtolower($frDate)));
        };

        $invalidDate = function (?string $value) use ($defaultDateDemande, $sourceMentionsDate): bool {
            $value = trim((string) $value);

            if ($value === '' || stripos($value, 'YYYY') !== false || stripos($value, 'HH:MM') !== false) {
                return true;
            }

            // OpenAI sometimes invents old sample dates such as 2023-10-01T10:00.
            // If that exact date is not present in the pasted request, treat it as a placeholder.
            if (preg_match('/^2023-10-\d{2}T?\d{0,2}:?\d{0,2}/', $value) && !$sourceMentionsDate($value)) {
                return true;
            }

            try {
                $generated = Carbon::parse($value, 'Europe/Paris');
                $default = Carbon::parse($defaultDateDemande, 'Europe/Paris');

                if ($generated->lt($default->copy()->subDays(30)) && !$sourceMentionsDate($value)) {
                    return true;
                }
            } catch (\Throwable $e) {
                return true;
            }

            return false;
        };

        $ensureTag = function (string $xml, string $tag) use ($defaultDateDemande, $invalidDate): string {
            $pattern = '/<' . $tag . '>(.*?)<\/' . $tag . '>/s';

            if (preg_match($pattern, $xml, $matches)) {
                if ($invalidDate($matches[1] ?? '')) {
                    return preg_replace($pattern, '<' . $tag . '>' . $defaultDateDemande . '</' . $tag . '>', $xml, 1);
                }

                return $xml;
            }

            return preg_replace('/<demande([^>]*)>/i', '<demande$1>' . "\n  <" . $tag . '>' . $defaultDateDemande . '</' . $tag . '>', $xml, 1);
        };

        $xml = $ensureTag($xml, 'date_demande');

        return $xml;
    }

    public function generateXmlFromText(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|min:5|max:10000',
        ]);

        $modelPath = resource_path('prompts/talep_xml_model.xml');
        $xmlModel = file_exists($modelPath) ? file_get_contents($modelPath) : '';
        $nowParis = now('Europe/Paris');
        $today = $nowParis->toDateString();
        $defaultDateDemande = $nowParis->format('Y-m-d\TH:i');

        if (!config('services.openai.key')) {
            return response()->json([
                'success' => false,
                'message' => 'La configuration OpenAI est manquante.',
            ], 422);
        }

        $response = Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->timeout(45)
            ->post(rtrim(config('services.openai.base_url'), '/') . '/chat/completions', [
                'model' => trim(config('services.openai.model')),
                'temperature' => 0,
                'max_tokens' => 600,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "XML strict, sans markdown. Textes en français. Garde toutes les balises même vides.\nDates Paris (maintenant={$defaultDateDemande}, aujourd'hui={$today}):\n- date_demande = réception; utilise {$defaultDateDemande} si absente\n- date_operation = transport demandé; jamais la date de réception sauf si explicite\nModèle:\n" . $xmlModel,
                    ],
                    [
                        'role' => 'user',
                        'content' => $data['message'],
                    ],
                ],
            ]);

        if (!$response->successful()) {
            $errorCode = data_get($response->json(), 'error.code');
            $message = 'La conversion XML a échoué.';

            if ($response->status() === 429 || $errorCode === 'insufficient_quota') {
                $message = 'Le quota OpenAI est épuisé. Veuillez vérifier la facturation ou la clé API.';
            }

            \Log::error('Talep XML OpenAI failed', [
                'status' => $response->status(),
                'code' => $errorCode,
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        $content = trim((string) data_get($response->json(), 'choices.0.message.content'));
        $content = preg_replace('/^```(?:xml)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        if (preg_match('/<demande[\s\S]*<\/demande>/', $content, $matches)) {
            $content = $matches[0];
        }

        $content = $this->normalizeGeneratedTalepXmlDates($content, $defaultDateDemande, $data['message']);

        return response()->json([
            'success' => true,
            'xml' => $content,
        ]);
    }
 public function routePreview(Request $request, GoogleRouteService $googleRouteService)
{
    try {
        $data = $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'waypoints' => 'nullable|array',
            'waypoints.*' => 'nullable|string',
        ]);

        $waypoints = collect($data['waypoints'] ?? [])
            ->map(fn ($waypoint) => trim((string) $waypoint))
            ->filter(fn ($waypoint) => $this->isUsableRouteStop($waypoint))
            ->values()
            ->all();

        $route = $googleRouteService->compute(
            $data['origin'],
            $data['destination'],
            $waypoints
        );

        return response()->json([
            'success' => true,
            'distance_meters' => $route['distance_meters'],
            'distance_text' => $route['distance_km'] . ' km',
            'duration_seconds' => $route['duration_seconds'],
            'duration_text' => $route['duration_text'],
            'traffic_duration_seconds' => $route['traffic_duration_seconds'],
            'traffic_duration_text' => $route['traffic_duration_text'],
            'polyline' => $route['polyline'],
            'toll_amount' => $route['toll_amount'] ?? null,
            'toll_currency' => $route['toll_currency'] ?? 'EUR',
            'legs_count' => $route['legs_count'] ?? null,
        ]);
    } catch (\Throwable $e) {
        $message = $this->friendlyRoutePreviewError($e->getMessage());

        \Log::error('routePreview failed', [
            'origin' => $data['origin'] ?? null,
            'destination' => $data['destination'] ?? null,
            'waypoints' => $waypoints ?? ($data['waypoints'] ?? []),
            'error' => $e->getMessage(),
            'message' => $message,
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'error_detail' => $message,

        ], 422);
    }
}

    private function friendlyRoutePreviewError(string $error): string
    {
        if (str_contains($error, 'API_KEY_IP_ADDRESS_BLOCKED') || str_contains($error, 'IP address restriction')) {
            return "Google Routes refuse la clé API pour l'adresse IP du serveur. Vérifiez la restriction IP de la clé API Google Routes.";
        }

        if (str_contains($error, 'REQUEST_DENIED') || str_contains($error, 'PERMISSION_DENIED')) {
            return "Google Routes refuse la demande. Vérifiez que l'API Routes est activée et que la clé serveur est autorisée.";
        }

        if (str_contains($error, 'INVALID_ARGUMENT')) {
            return "Google Routes n'arrive pas à lire une adresse ou une étape. Vérifiez le départ, l'arrivée et les étapes intermédiaires.";
        }

        if (str_contains($error, 'Too many') || str_contains($error, 'MAX_ROUTE') || str_contains($error, 'waypoints')) {
            return "L'itinéraire contient trop d'étapes pour Google Routes. Réduisez les étapes ou découpez l'opération.";
        }

        return "Une erreur est survenue lors du calcul de l'itinéraire.";
    }

    private function addTalepHistory(Talep $talep, string $actionType, ?string $fieldName = null, $oldValue = null, $newValue = null, ?string $note = null): void
    {
        if (!Schema::hasTable('talep_histories')) {
            return;
        }

        try {
            TalepHistory::create([
                'talep_id' => $talep->id,
                'user_id' => auth()->id(),
                'action_type' => $actionType,
                'field_name' => $fieldName,
                'old_value' => $this->formatTalepHistoryValue($oldValue),
                'new_value' => $this->formatTalepHistoryValue($newValue),
                'note' => $note,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Talep history could not be written', [
                'talep_id' => $talep->id,
                'action_type' => $actionType,
                'field_name' => $fieldName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logTalepChanges(Talep $talep, array $before, string $actionType = 'Modification'): void
    {
        if (!$before) {
            return;
        }

        $fresh = $talep->fresh();
        if (!$fresh) {
            return;
        }

        foreach ($before as $field => $oldValue) {
            $newValue = $fresh->{$field} ?? null;

            if ($this->formatTalepHistoryValue($oldValue) === $this->formatTalepHistoryValue($newValue)) {
                continue;
            }

            $this->addTalepHistory(
                $fresh,
                $actionType,
                $this->talepHistoryFieldLabel($field),
                $oldValue,
                $newValue
            );
        }
    }

    private function talepHistoryFieldLabel(string $field): string
    {
        return [
            'user_id' => 'Responsable',
            'acente_id' => 'Agence',
            'talep_tarihi' => 'Date demande',
            'talep_kanali' => 'Canal',
            'country' => 'Pays',
            'service_type_id' => 'Type de service',
            'vehicule_id' => 'Véhicule',
            'depot_id' => 'Dépôt',
            'customer_name' => 'Client',
            'customer_phone' => 'Téléphone client',
            'customer_email' => 'Email client',
            'total_pax' => 'Pax',
            'pickup_location' => 'Début',
            'dropoff_location' => 'Fin',
            'verilen_fiyat' => 'Prix donné',
            'confirmed_price' => 'Prix confirmé',
            'system_total' => 'Prix AI',
            'discount_price' => 'Prix remise',
            'second_discount_price' => '2ème remise',
            'final_total' => 'Prix admin',
            'comment_admin' => 'Commentaire admin',
            'currency' => 'Devise',
            'relance_yapildi' => 'Relance',
            'konfirme_durumu' => 'Statut',
            'uzun_mesaj' => 'Message long',
            'internal_notes' => 'Informations manquantes',
            'admin_price_user_id' => 'Utilisateur prix admin',
            'admin_price_updated_at' => 'Date prix admin',
            'is_manual_override' => 'Prix manuel',
        ][$field] ?? $field;
    }

    private function formatTalepHistoryValue($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        } elseif (is_bool($value)) {
            $value = $value ? 'Oui' : 'Non';
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr((string) $value, 0, 1000);
    }

    private function isUsableRouteStop(?string $value): bool
    {
        $normalized = mb_strtolower(trim((string) $value));

        return $normalized !== '' && !in_array($normalized, [
            '-',
            '--',
            '---',
            'n/a',
            'na',
            'non defini',
            'non défini',
            'inconnu',
            'unknown',
            'null',
        ], true);
    }
}
