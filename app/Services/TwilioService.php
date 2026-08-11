<?php
namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected Client $client;
    protected string $whatsappFrom;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (!$sid || !$token) {
            throw new \Exception('Twilio SID veya TOKEN eksik!');
        }

        $this->client = new Client($sid, $token);

        $whatsapp = config('services.twilio.whatsapp_from');

        if (!$whatsapp) {
            throw new \Exception('TWILIO_WHATSAPP_FROM is not configured!');
        }

        $this->whatsappFrom = 'whatsapp:' . $whatsapp;
    }

    public function makeCall(string $to, string $url)
    {
        return $this->client->calls->create(
            $to,
            config('services.twilio.from'),
            ['url' => $url]
        );
    }

    public function sendMessage(string $to, string $message)
    {
        return $this->client->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message
        ]);
    }

    public function getCall(string $callSid)
    {
        return $this->client->calls($callSid)->fetch();
    }

    public function getSms(string $smsSid)
    {
        return $this->client->messages($smsSid)->fetch();
    }

    public function sendWhatsAppMessage(string $to, string $body, ?string $statusCallback = null)
    {
        return $this->client->messages->create(
            'whatsapp:' . $to,
            array_filter([
                'from' => $this->whatsappFrom,
                'body' => $body,
                'statusCallback' => $statusCallback,
            ])
        );
    }

    public function sendWhatsAppTemplateMessage(string $to, string $templateName, array $params)
    {
        return $this->client->messages->create(
            'whatsapp:' . $to,
            [
                'from' => $this->whatsappFrom,
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => 'tr'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => array_map(fn($item) => [
                                'type' => 'text',
                                'text' => $item
                            ], $params),
                        ],
                    ],
                ]
            ]
        );
    }
}
