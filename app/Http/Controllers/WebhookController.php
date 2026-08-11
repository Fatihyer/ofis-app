<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleIncomingMessage(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body');

        // Process the incoming message
        Log::info("Incoming message from $from: $body");

        // Respond to the message if needed
        return response('Message received', 200);
    }

    public function handleFallback(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body');

        // Process the fallback message
        Log::error("Fallback message from $from: $body");

        return response('Fallback handled', 200);
    }

    public function handleStatusCallback(Request $request)
    {
        $messageSid = $request->input('MessageSid');
        $messageStatus = $request->input('MessageStatus');

        // Process the status callback
        Log::info("Message SID $messageSid has status $messageStatus");

        return response('Status callback received', 200);
    }
}
