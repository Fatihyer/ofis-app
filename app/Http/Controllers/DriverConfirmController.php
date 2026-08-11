<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Illuminate\Http\Request;

class DriverConfirmController extends Controller
{
    public function confirm(Transfer $transfer, Request $request)
    {
        $phone = $transfer->driver?->tel;

        if (!$phone || $request->token !== sha1($transfer->id . $phone)) {
            abort(403, 'Lien invalide');
        }

        $transfer->driver_confirmed_at = now();
        $transfer->driver_response = 'confirmed';
        $transfer->save();

        return view('driver.confirmed');
    }
}
