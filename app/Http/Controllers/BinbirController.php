<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transfer;
use Carbon\Carbon;
use App\Notifications\TransferNotification; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;


use App\Models\Acente;

class BinbirController extends Controller
{  
  public function binbirrez()
  {
    $now = Carbon::now('Europe/Paris');
    $now->subHours(3);///kac saat oncesini buradan ayarla
    $start = $now->format('Y-m-d H:i:s');
    $son=Carbon::now('Europe/Paris');
    $end = $son->addHour(4)->format('Y-m-d H:i:s');

    $transfers = Transfer::where('mission', 1)->where('mission_url', null)->orderBy('start_date')->get();
    foreach ($transfers as $transfer) {
      // Send a message for each transfer
      // Replace the following code with your actual message sending logic
      // For example, you can use a notification or send an email
    $shortCode = Str::random(8);
    while (Transfer::where('mission_url', $shortCode)->exists()) {
      $shortCode = Str::random(8); // Kısa kod benzersiz değilse, yeni bir kısa kod oluşturulur
     }

     $transfer->mission_url = $shortCode;
     $transfer->save();
    
     
   //  Mail::to('reservation@parisvia.com')->send(new TransferNotification($transfer));

      // Send the <message></message>
      // Example: $user->notify(new TransferNotification($message));
      // Replace `TransferNotification` with your actual notification class
               }
  }
 

    
}




