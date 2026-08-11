<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Vehicule;
use App\Models\Talep;
use App\Models\Transfer;
use App\Models\StickyNote;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $today    = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $warn     = Carbon::today()->addDays(30);

        // ── Opérations ──
        $ops = [
            'today'    => Transfer::whereDate('start_date', $today)->count(),
            'tomorrow' => Transfer::whereDate('start_date', $tomorrow)->count(),
            'week'     => Transfer::whereBetween('start_date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()])->count(),
            'month'    => Transfer::whereBetween('start_date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])->count(),
            'no_driver'=> Transfer::whereNull('driver_id')
                            ->whereBetween('start_date', [$today, $today->copy()->addDays(7)])
                            ->count(),
        ];

        // ── Talepler ──
        $talepStatuts = Talep::select('konfirme_durumu', DB::raw('count(*) as total'))
            ->groupBy('konfirme_durumu')
            ->pluck('total', 'konfirme_durumu');

        $talepOrder = ['En attente', 'Devis envoyé', 'En suivi', 'Confirmé', 'Annulé', 'Perdu'];
        $talepColors = [
            'En attente'  => ['bg' => '#fff3cd', 'color' => '#856404', 'border' => '#ffc107'],
            'Devis envoyé'=> ['bg' => '#d1ecf1', 'color' => '#0c5460', 'border' => '#17a2b8'],
            'En suivi'    => ['bg' => '#cce5ff', 'color' => '#004085', 'border' => '#0d6efd'],
            'Confirmé'    => ['bg' => '#d4edda', 'color' => '#155724', 'border' => '#28a745'],
            'Annulé'      => ['bg' => '#f8d7da', 'color' => '#721c24', 'border' => '#dc3545'],
            'Perdu'       => ['bg' => '#e2e3e5', 'color' => '#383d41', 'border' => '#6c757d'],
        ];

        // ── Véhicules ──
        $dateFields = ['control', 'sigorta', 'ead_date', 'ext_date', 'lim_date', 'tach_date', 'vid_date'];
        $zero = '1970-01-01';

        $realVehicules = Vehicule::whereNotNull('real')->whereNull('sales')
            ->get(['id', 'name', 'plaka', 'enpanne', 'control', 'sigorta',
                   'ead_date', 'ext_date', 'lim_date', 'tach_date', 'vid_date']);

        $vTotal   = $realVehicules->count();
        $vPanne   = $realVehicules->where('enpanne', 1)->count();
        $vExpired = 0;
        $vWarn    = 0;
        $docAlerts = [];

        foreach ($realVehicules as $v) {
            $expiredLabels = [];
            $warnLabels    = [];
            $labelMap = [
                'control'  => 'C.T.',
                'sigorta'  => 'Assurance',
                'ead_date' => 'E.A.D.',
                'ext_date' => 'Ext.',
                'lim_date' => 'Lim.',
                'tach_date'=> 'Tach.',
                'vid_date' => 'Vid.',
            ];
            foreach ($dateFields as $field) {
                $val = $v->$field;
                if (!$val || $val === $zero) continue;
                $d = Carbon::parse($val);
                if ($d->lt($today))    $expiredLabels[] = $labelMap[$field];
                elseif ($d->lt($warn)) $warnLabels[]    = $labelMap[$field];
            }
            if ($expiredLabels) {
                $vExpired++;
                $docAlerts[] = [
                    'id'     => $v->id,
                    'name'   => $v->name,
                    'plaka'  => $v->plaka,
                    'type'   => 'exp',
                    'labels' => $expiredLabels,
                ];
            } elseif ($warnLabels) {
                $vWarn++;
                $docAlerts[] = [
                    'id'     => $v->id,
                    'name'   => $v->name,
                    'plaka'  => $v->plaka,
                    'type'   => 'warn',
                    'labels' => $warnLabels,
                ];
            }
        }

        usort($docAlerts, fn($a, $b) => ($a['type'] === 'exp' ? 0 : 1) - ($b['type'] === 'exp' ? 0 : 1));

        // ── Sticky Notes ──
        $stickyNotes = StickyNote::orderBy('order')->get();

        return view('dashboard.index', compact(
            'ops', 'talepStatuts', 'talepOrder', 'talepColors',
            'vTotal', 'vPanne', 'vExpired', 'vWarn', 'docAlerts',
            'stickyNotes'
        ));
    }
}
