<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\TwilioService;
use App\Models\Transfer;
use App\Models\TransferMessage;
use App\Models\Message;
use Illuminate\Support\Str;
use Twilio\Rest\Client;



class WhatsAppController extends Controller
{
    public function send(Request $request)
{
    $transfer = Transfer::with(['servicetype', 'vehicule', 'driver', 'trajets'])->findOrFail($request->transfer_id);

    if (!$transfer->confirmation_token) {
        $transfer->confirmation_token = Str::uuid();
        $transfer->save();
    }

    $confirmationLink = route('transfer.confirm', $transfer->confirmation_token);

    $whatsappText = '<<SERVICE DE TRANSPORT PUBLIC DE PERSONNES - BILLET COLLECTIF>>' . PHP_EOL .
        '(Arrêté du 14 février 1986 – Article.5) et ordre de mission (Arrêté du 6 janvier 1993 – Article 3)' . PHP_EOL . PHP_EOL .
        'Date: ' . date('d-m-Y D', strtotime($transfer->start_date)) . PHP_EOL .
        '*En Route: ' . date('H:i', strtotime($request->ofis ?? $transfer->start_date)) . '*' . PHP_EOL .
        '*Heure prise en charge: ' . date('H:i', strtotime($transfer->start_date . ' -15 minutes')) . '*' . PHP_EOL .
        'Heure de dépose: ' . date('H:i', strtotime($transfer->end_date)) . PHP_EOL .
        'Service: ' . $transfer->servicetype->name . PHP_EOL .
        'Passagers: ' . $transfer->pax . ' pax' . PHP_EOL .
        '*Lieu de Prise en Charge: ' . $transfer->from . '*' . PHP_EOL .
        'Lieux de depose: ' . $transfer->target . PHP_EOL .
        'Vehicule: ' . $transfer->vehicule->name . PHP_EOL .
        'Chauffeur: ' . $transfer->driver->name . PHP_EOL .
        'Commentaires: ' . $transfer->comments . PHP_EOL .
        'Contact: ' . PHP_EOL . $request->misafir . PHP_EOL . PHP_EOL .
        'Trajets: ' . PHP_EOL;

    foreach ($transfer->trajets as $index => $trajet) {
        $whatsappText .= ($index + 1) . '. ';
        $whatsappText .= (date("Y-m-d") == date('Y-m-d', strtotime($trajet->datetime)))
            ? date('H:i', strtotime($trajet->datetime))
            : date('d/m/Y H:i', strtotime($trajet->datetime));
        $whatsappText .= ' - ' . $trajet->type . ': ' . $trajet->from . ' ' . $trajet->google_address . PHP_EOL;
    }

    $whatsappText .= PHP_EOL . '👉 Veuillez cliquer ici pour confirmer que vous avez reçu les informations : ' . $confirmationLink;

    $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));

    $message = $twilio->messages->create(
        'whatsapp:' . $transfer->phone_number,
        [
            'from' => 'whatsapp:' . config('services.twilio.whatsapp_from'),
            'body' => $whatsappText,
            'statusCallback' => route('twilio.status'),
        ]
    );

    Message::create([
        'transfer_id' => $transfer->id,
        'to' => $transfer->phone_number,
        'body' => $whatsappText,
        'sid' => $message->sid,
        'status' => $message->status,
    ]);

    return back()->with('success', 'Mesaj gönderildi!');
}


public function sendWhatsAppTemplateMessage($to, $templateName, array $params)
{
    $client = new Client(config('services.twilio.sid'), config('services.twilio.token'));
    $whatsappFrom = 'whatsapp:' . config('services.twilio.whatsapp_from');

    return $client->messages->create(
        'whatsapp:' . $to,
        [
            'from' => $whatsappFrom,
            'contentSid' => null, // boş bırak
            'contentVariables' => json_encode([]), // boş bırak
            'persistentAction' => [],
            'provideFeedback' => true,
            'statusCallback' => route('twilio.status'),

            // Veya daha klasik template mesaj:
            'messagingServiceSid' => null, // sadece gerekirse
            'body' => null,
            'contentTemplate' => [
                'name' => $templateName,
                'language' => ['code' => 'tr'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => array_map(function ($item) {
                            return ['type' => 'text', 'text' => $item];
                        }, $params),
                    ],
                ],
            ],
        ]
    );
}
}
