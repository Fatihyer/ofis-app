<?php

namespace App\Services;

use App\Models\Option;
use Illuminate\Support\Facades\Cache;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

class TwilioAccountAlertService
{
    public function messages(): array
    {
        return Cache::remember('twilio_account_alerts_' . now('Europe/Paris')->format('YmdHi'), 300, function () {
            return $this->buildMessages();
        });
    }

    private function buildMessages(): array
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (!$sid || !$token || !config('services.twilio.from')) {
            return ['Twilio alerte: SID, token ou numéro expéditeur manquant. Les SMS/appels ne peuvent pas partir.'];
        }

        try {
            $client = new Client($sid, $token);
            $balance = $client->api->v2010->accounts($sid)->balance->fetch();
            $amount = (float) $balance->balance;
            $currency = $balance->currency ?: 'EUR';
            $threshold = (float) (Option::where('name', 'twilioMinBalance')->value('value') ?? 5);

            if ($amount <= $threshold) {
                return ["Twilio alerte: solde faible {$amount} {$currency}. Rechargez le compte pour éviter l'arrêt des SMS/appels."];
            }

            return [];
        } catch (RestException $e) {
            if ((int) $e->getStatusCode() === 401 || str_contains($e->getMessage(), 'Authenticate')) {
                return ['Twilio alerte: authentification impossible. Vérifiez TWILIO_SID / TWILIO_TOKEN, les SMS et appels échouent actuellement.'];
            }

            return ['Twilio alerte: impossible de vérifier le solde Twilio (' . $e->getMessage() . ').'];
        } catch (\Throwable $e) {
            return ['Twilio alerte: impossible de vérifier le compte Twilio.'];
        }
    }
}
