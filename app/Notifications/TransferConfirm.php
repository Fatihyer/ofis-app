<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Auth;
use Carbon\Carbon;
class TransferConfirm extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($confirm)
    {
      $this->transfer=$confirm;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
     public function toDatabase($notifiable)
    {
        return [
            'confirmtime'=>Carbon::now(),
            'user'=>Auth::user()->name,
            'transfer'=>$this->transfer
        ];
    }
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
