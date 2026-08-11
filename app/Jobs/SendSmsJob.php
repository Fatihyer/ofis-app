<?php

namespace App\Jobs;

use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;
    public $message;

    public function __construct($phone, $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    public function handle(TwilioService $twilio)
    {
          \Log::info('📤 SendSmsJob çalıştı', [
        'phone' => $this->phone,
        'message' => $this->message
    ]);


        $twilio->sendMessage($this->phone, $this->message);
    }
}
