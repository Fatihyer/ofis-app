<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ClaudePricingService
{
    private string $systemPrompt;

    public function __construct()
    {
        $this->systemPrompt = $this->buildSystemPrompt();
    }

    public function chat(array $messages): string
    {
        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])
        ->timeout(config('services.anthropic.timeout', 30))
        ->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model', 'claude-haiku-4-5'),
            'max_tokens' => 1024,
            'system'     => $this->systemPrompt,
            'messages'   => $messages,
        ]);

        $response->throw();

        return data_get($response->json(), 'content.0.text', '');
    }

    private function buildSystemPrompt(): string
    {
        $rules = DB::table('vehicle_price_rules')
            ->where('active', 1)
            ->orderBy('vehicle_type')
            ->orderBy('service_type')
            ->get();

        $rulesText = '';
        foreach ($rules as $r) {
            $rulesText .= sprintf(
                "- %s / %s: taban %s€ (%s km + %sh dahil), ekstra km: %s€/km, ekstra saat: %s€/h, gece: %s€/h, minimum: %s€\n",
                $r->vehicle_type,
                $r->service_type,
                $r->base_rate,
                $r->included_km,
                $r->included_hours,
                $r->extra_km_rate,
                $r->extra_hour_rate,
                $r->night_extra_hour_rate,
                $r->minimum_charge
            );
        }

        $vehicleLabels = [
            'SEDAN_4'    => 'Berline / Sedan (4 kişi)',
            'CLASS_V'    => 'Mercedes Class V (6-7 kişi)',
            'VAN_8'      => 'Van (8 kişi)',
            'SPRINTER_19'=> 'Sprinter (19 kişi)',
            'MINIBUS_30' => 'Minibüs (30 kişi)',
            'COACH_45'   => 'Otobüs (45 kişi)',
            'COACH_50'   => 'Otobüs (50 kişi)',
            'COACH_55'   => 'Otobüs (55 kişi)',
            'COACH_60'   => 'Otobüs (60 kişi)',
        ];

        $vehicleInfo = '';
        foreach ($vehicleLabels as $code => $label) {
            $vehicleInfo .= "- $code = $label\n";
        }

        $historicalSection = $this->buildHistoricalSection();
        $occupancySection  = $this->buildOccupancySection();

        return <<<PROMPT
Tu es l'assistant de tarification de ParisVia, une société de transport VTC basée à Paris.
Tu réponds en français par défaut, mais tu peux aussi répondre en turc ou en anglais selon la langue du client.

TYPES DE SERVICE:
- transfer: trajet point à point (A→B)
- dispo: mise à disposition avec chauffeur (forfait heures + km)

VÉHICULES DISPONIBLES:
$vehicleInfo

TARIFS DE BASE (HT, marge 15% incluse, TVA 10%):
$rulesText

RÈGLES DE CALCUL:
1. Transfer: base_rate si km ≤ included_km ET heures ≤ included_hours
   Sinon: base_rate + (km_extra × extra_km_rate) + (h_extra × extra_hour_rate)
2. Dispo: idem avec les tarifs dispo
3. Frais chauffeur hors zone: repas 25€, hôtel 120€ par nuit
4. Péages et carburant: en sus (estimés séparément)
5. Prix affiché = HT. TTC = HT × 1.10

$historicalSection

$occupancySection

AJUSTEMENT DYNAMIQUE DES PRIX:
- En haute saison (juin, juillet, août, septembre) ou pendant les vacances scolaires: ajouter 10-20% au tarif de base
- En basse saison (janvier, février, novembre): réduire de 5-10%
- Si la disponibilité du jour est ÉLEVÉE (>10 réservations): ajouter 15-25% (forte demande)
- Si la disponibilité est MODÉRÉE (6-10 réservations): ajouter 5-10%
- Si la disponibilité est FAIBLE (<6 réservations): tarif normal ou -5%
- Vérifier les prix historiques réels pour calibrer: si la fourchette historique suggère un prix plus élevé, l'utiliser

Quand un client demande un devis:
- Identifie: type de service, véhicule, km estimés, durée, nombre de passagers, date souhaitée
- Calcule le prix HT en partant du tarif de base
- Applique l'ajustement saisonnier et de disponibilité selon la date
- Compare avec les prix historiques pour valider la cohérence
- Donne le prix HT et TTC
- Propose le véhicule adapté si non précisé

Sois concis et professionnel.
PROMPT;
    }

    private function buildHistoricalSection(): string
    {
        try {
            $rows = DB::table('talep_days')
                ->select([
                    DB::raw("CASE
                        WHEN vehicle_type LIKE '%COACH%' OR vehicle_type LIKE '%TEMSA%' OR vehicle_type LIKE '%TOURISMO%' OR vehicle_type LIKE '%IVECO%' THEN 'BUS/COACH'
                        WHEN vehicle_type LIKE '%SPRINTER%' THEN 'SPRINTER_19'
                        WHEN vehicle_type LIKE '%VAN%' OR vehicle_type LIKE '%Vito%' THEN 'VAN_8'
                        WHEN vehicle_type LIKE '%CLASS V%' THEN 'CLASS_V'
                        ELSE NULL
                    END as vehicle_cat"),
                    DB::raw('MONTH(service_date) as month'),
                    DB::raw('COUNT(*) as cnt'),
                    DB::raw('ROUND(AVG(admin_price),0) as avg_price'),
                    DB::raw('MIN(admin_price) as min_price'),
                    DB::raw('MAX(admin_price) as max_price'),
                ])
                ->whereNotNull('admin_price')
                ->where('admin_price', '>', 50)
                ->whereRaw("vehicle_type IS NOT NULL")
                ->groupBy('vehicle_cat', 'month')
                ->havingRaw('vehicle_cat IS NOT NULL AND cnt >= 2')
                ->orderBy('vehicle_cat')
                ->orderBy('month')
                ->get();

            if ($rows->isEmpty()) {
                return '';
            }

            $monthNames = [
                1=>'jan',2=>'fév',3=>'mar',4=>'avr',5=>'mai',6=>'jun',
                7=>'jul',8=>'aoû',9=>'sep',10=>'oct',11=>'nov',12=>'déc',
            ];

            $text = "PRIX HISTORIQUES RÉELS (par jour de prestation, admin-validés):\n";
            foreach ($rows as $r) {
                $mon = $monthNames[$r->month] ?? $r->month;
                $text .= sprintf(
                    "- %s / %s: moy %d€, fourchette %d-%d€ (%d prestations)\n",
                    $r->vehicle_cat,
                    $mon,
                    $r->avg_price,
                    $r->min_price,
                    $r->max_price,
                    $r->cnt
                );
            }

            return $text;
        } catch (\Throwable) {
            return '';
        }
    }

    private function buildOccupancySection(): string
    {
        try {
            $rows = DB::table('talep_days')
                ->select([
                    'service_date',
                    DB::raw('COUNT(DISTINCT talep_id) as bookings'),
                ])
                ->whereBetween('service_date', [
                    now()->toDateString(),
                    now()->addDays(120)->toDateString(),
                ])
                ->groupBy('service_date')
                ->orderBy('service_date')
                ->get();

            if ($rows->isEmpty()) {
                return '';
            }

            $highDays    = [];
            $moderateDays = [];

            foreach ($rows as $r) {
                if ($r->bookings > 10) {
                    $highDays[] = $r->service_date . '(' . $r->bookings . ')';
                } elseif ($r->bookings >= 6) {
                    $moderateDays[] = $r->service_date . '(' . $r->bookings . ')';
                }
            }

            $lines = ["DISPONIBILITÉ / TAUX D'OCCUPATION (120 prochains jours):"];

            if ($highDays) {
                $lines[] = "- Forte demande (>10 résa): " . implode(', ', array_slice($highDays, 0, 15));
            }
            if ($moderateDays) {
                $lines[] = "- Demande modérée (6-10 résa): " . implode(', ', array_slice($moderateDays, 0, 15));
            }
            if (!$highDays && !$moderateDays) {
                $lines[] = "- Disponibilité globalement normale sur les 120 prochains jours";
            }

            return implode("\n", $lines);
        } catch (\Throwable) {
            return '';
        }
    }
}
