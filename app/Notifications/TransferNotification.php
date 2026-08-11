<?php

namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Transfer;

class TransferNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $transfer;

    /**
     * Create a new message instance.
     *
     * @param  Transfer  $transfer
     * @return void
     */
    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
       
        $subject = 'Transfer ID: ' . $this->transfer->id;
        return $this->from('driver@ofis.francepanoramic.com', 'Paris Via api')
                     ->subject($subject)
                     ->view('emails.transfer_email');
    }
}
