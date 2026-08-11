<?php

namespace App\Http\Controllers;

use App\Models\Talep;
use App\Services\GoogleAdsFormLeadConversionUploader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GoogleAdsLeadController extends Controller
{
    public function store(Request $request)
    {
        $configuredToken = (string) config('services.parisvia_wordpress.lead_token');
        $requestToken = (string) $request->header('X-ParisVia-Lead-Token');

        if ($configuredToken === '') {
            Log::error('Google Ads lead endpoint token is not configured.');

            return response()->json([
                'success' => false,
                'message' => 'Lead endpoint is not configured.',
            ], 503);
        }

        if (! hash_equals($configuredToken, $requestToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $data = $request->validate([
            'wp_request_id' => 'nullable|integer',
            'talep_tarihi' => 'nullable|date',
            'lang' => 'nullable|string|max:10',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:80',
            'country' => 'nullable|string|max:100',
            'customer_email' => 'nullable|email|max:255',
            'pickup_location' => 'nullable|string',
            'dropoff_location' => 'nullable|string',
            'pickup_datetime' => 'nullable|date',
            'total_pax' => 'nullable|integer|min:1',
            'service_type' => 'nullable|string|max:100',
            'vehicle_type' => 'nullable|string|max:100',
            'luggage_count' => 'nullable|integer|min:0',
            'retour' => 'nullable|string|max:30',
            'retour_datetime' => 'nullable|date',
            'car_sur_place' => 'nullable|string|max:30',
            'trip_details' => 'nullable|string',
            'client_notes' => 'nullable|string',
            'gclid' => 'nullable|string|max:255',
            'gbraid' => 'nullable|string|max:255',
            'wbraid' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'landing_page' => 'nullable|string|max:2048',
            'page_url' => 'nullable|string|max:2048',
            'referrer' => 'nullable|string|max:2048',
            'ip_address' => 'nullable|string|max:45',
            'user_agent' => 'nullable|string|max:1024',
            'event_name' => 'nullable|string|max:100',
        ]);

        $wpRequestId = $data['wp_request_id'] ?? null;
        if ($wpRequestId) {
            $existingLead = DB::table('google_ads_leads')
                ->where('wp_request_id', $wpRequestId)
                ->first();

            if ($existingLead) {
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'talep_id' => $existingLead->talep_id,
                    'google_ads_lead_id' => $existingLead->id,
                ]);
            }
        }

        $now = now('Europe/Paris');
        $talepDate = $this->parseDateTime($data['talep_tarihi'] ?? null) ?? $now;
        $pickupDate = $this->parseDateTime($data['pickup_datetime'] ?? null);
        $returnDate = $this->parseDateTime($data['retour_datetime'] ?? null);

        $result = DB::transaction(function () use ($request, $data, $now, $talepDate, $pickupDate, $returnDate) {
            $talepPayload = [
                'user_id' => 106,
                'talep_tarihi' => $talepDate,
                'talep_kanali' => 'Paris Via Web',
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'pickup_location' => $data['pickup_location'] ?? null,
                'dropoff_location' => $data['dropoff_location'] ?? null,
                'total_pax' => $data['total_pax'] ?? null,
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'service_type' => $data['service_type'] ?? null,
                'currency' => 'EUR',
                'relance_yapildi' => 0,
                'konfirme_durumu' => 'En attente',
                'is_manual_override' => 0,
                'uzun_mesaj' => $this->buildLongMessage($data, $pickupDate, $returnDate),
            ] + (Schema::hasColumn('talepler', 'country') ? [
                'country' => $data['country'] ?? null,
            ] : []);
            $talep = Talep::create($talepPayload);

            $leadId = DB::table('google_ads_leads')->insertGetId([
                'created_at' => $now,
                'updated_at' => $now,
                'wp_request_id' => $data['wp_request_id'] ?? null,
                'talep_id' => $talep->id,
                'event_name' => $data['event_name'] ?? 'cansuFormSubmitted',
                'source' => 'parisvia_wordpress',
                'lang' => $data['lang'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'pickup_location' => $data['pickup_location'] ?? null,
                'dropoff_location' => $data['dropoff_location'] ?? null,
                'pickup_datetime' => $pickupDate,
                'passengers' => $data['total_pax'] ?? null,
                'service_type' => $data['service_type'] ?? null,
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'luggage_count' => $data['luggage_count'] ?? null,
                'retour' => $data['retour'] ?? null,
                'retour_datetime' => $returnDate,
                'car_sur_place' => $data['car_sur_place'] ?? null,
                'trip_details' => $data['trip_details'] ?? null,
                'client_notes' => $data['client_notes'] ?? null,
                'gclid' => $data['gclid'] ?? null,
                'gbraid' => $data['gbraid'] ?? null,
                'wbraid' => $data['wbraid'] ?? null,
                'utm_source' => $data['utm_source'] ?? null,
                'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'utm_term' => $data['utm_term'] ?? null,
                'utm_content' => $data['utm_content'] ?? null,
                'landing_page' => $data['landing_page'] ?? null,
                'page_url' => $data['page_url'] ?? null,
                'referrer' => $data['referrer'] ?? null,
                'ip_address' => $data['ip_address'] ?? $request->ip(),
                'user_agent' => $data['user_agent'] ?? substr((string) $request->userAgent(), 0, 1024),
                'request_payload' => json_encode($request->except([]), JSON_UNESCAPED_UNICODE),
                'sync_status' => 'received',
            ]);

            DB::table('google_ads_conversion_uploads')->insertOrIgnore([
                'created_at' => $now,
                'updated_at' => $now,
                'google_ads_lead_id' => $leadId,
                'conversion_action' => 'lead_submit',
                'conversion_name' => 'ParisVia Lead Submit',
                'conversion_time' => $now,
                'conversion_value' => null,
                'currency' => 'EUR',
                'upload_status' => 'pending',
                'upload_attempts' => 0,
            ]);

            return [
                'success' => true,
                'talep_id' => $talep->id,
                'google_ads_lead_id' => $leadId,
            ];
        });

        try {
            app(GoogleAdsFormLeadConversionUploader::class)->uploadLead((int) $result['google_ads_lead_id']);
        } catch (\Throwable $e) {
            Log::warning('Google Ads form lead upload will be retried later.', [
                'google_ads_lead_id' => $result['google_ads_lead_id'],
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json($result);
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value, 'Europe/Paris');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildLongMessage(array $data, ?Carbon $pickupDate, ?Carbon $returnDate): string
    {
        $lines = [
            'Source: ParisVia WordPress form',
            'WordPress Request ID: ' . ($data['wp_request_id'] ?? '-'),
            'Language: ' . ($data['lang'] ?? '-'),
            '',
            'Service Type: ' . ($data['service_type'] ?? '-'),
            'Vehicle Preference: ' . ($data['vehicle_type'] ?? '-'),
            'Passengers: ' . ($data['total_pax'] ?? '-'),
            'Luggage Pieces: ' . ($data['luggage_count'] ?? '-'),
            '',
            'Pickup: ' . ($data['pickup_location'] ?? '-'),
            'Drop-off: ' . ($data['dropoff_location'] ?? '-'),
            'Pickup Date/Time: ' . ($pickupDate ? $pickupDate->format('Y-m-d H:i:s') : '-'),
            'Return Type: ' . ($data['retour'] ?? '-'),
            'Return Date/Time: ' . ($returnDate ? $returnDate->format('Y-m-d H:i:s') : '-'),
            'Vehicle & Driver On Site: ' . ($data['car_sur_place'] ?? '-'),
            '',
            'Flight/Event/Itinerary:',
            $data['trip_details'] ?? '-',
            '',
            'Client Notes:',
            $data['client_notes'] ?? '-',
            '',
            'Google Ads / Attribution:',
            'gclid: ' . ($data['gclid'] ?? '-'),
            'gbraid: ' . ($data['gbraid'] ?? '-'),
            'wbraid: ' . ($data['wbraid'] ?? '-'),
            'utm_source: ' . ($data['utm_source'] ?? '-'),
            'utm_medium: ' . ($data['utm_medium'] ?? '-'),
            'utm_campaign: ' . ($data['utm_campaign'] ?? '-'),
            'utm_term: ' . ($data['utm_term'] ?? '-'),
            'utm_content: ' . ($data['utm_content'] ?? '-'),
            'landing_page: ' . ($data['landing_page'] ?? '-'),
            'page_url: ' . ($data['page_url'] ?? '-'),
            'referrer: ' . ($data['referrer'] ?? '-'),
        ];

        return implode("\n", $lines);
    }
}
