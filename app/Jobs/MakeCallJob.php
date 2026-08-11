<?php

namespace App\Jobs;

use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;
    public $url;

    public function __construct($phone, $url)
    {
        $this->phone = $phone;
        $this->url = $url;
    }

    public function handle(TwilioService $twilio)
    {
        $twilio->makeCall($this->phone, $this->url);
    }
}
