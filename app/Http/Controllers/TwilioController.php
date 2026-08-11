<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use App\Models\Call;

class TwilioController extends Controller
{
    public function showCallForm()
    {
        $calls = Call::all();
        return view('call', compact('calls'));
    }

    public function makeCall(Request $request)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilio_number = env('TWILIO_PHONE_NUMBER');

        $to = $request->input('phone_number');

        try {
            $client = new Client($sid, $token);

            $call = $client->calls->create(
                $to,
                $twilio_number,
                [
                    'url' => route('twilio.voice-message')
                ]
            );

            Call::create([
                'to' => $to,
                'from' => $twilio_number,
                'status' => 'initiated',
                'call_sid' => $call->sid
            ]);

            return redirect('/ara')->with('message', 'Call initiated successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to initiate call: ' . $e->getMessage());
            return redirect('/ara')->with('error', 'Failed to initiate call: ' . $e->getMessage());
        }
    }

    public function handleWebhook(Request $request)
    {
        $callSid = $request->input('CallSid');
        $callStatus = $request->input('CallStatus');

        $call = Call::where('call_sid', $callSid)->first();
        if ($call) {
            $call->status = $callStatus;
            $call->save();
        }

        return response()->json(['message' => 'Webhook received']);
    }

    public function voiceMessage()
    {
        $response = new \Twilio\Twiml();
        $response->say('Bonjour, ceci est un appel de test de Twilio.', ['voice' => 'alice']);
        return response($response)->header('Content-Type', 'text/xml');
    }
}
