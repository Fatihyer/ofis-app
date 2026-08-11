<?php
namespace App\Http\Controllers;
use Twilio\Rest\Client;


class TestController extends Controller
{

public function testTwilioSMS()
{
    try {
        $twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $message = $twilio->messages->create(
            '+905325423007', // ALICI numara (Fransa için örnek: +33612345678)
            [
                'from' => config('services.twilio.from'), // TWILIO NUMARAN
                'body' => 'ParisVia test mesajı via Twilio'
            ]
        );

        return response()->json([
            'status' => 'success',
            'sid' => $message->sid
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
}