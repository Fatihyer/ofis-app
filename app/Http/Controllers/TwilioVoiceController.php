<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use App\Models\Transfer;

class TwilioVoiceController extends Controller
{
    public function message(Request $request)
    {
        $transferId = $request->input('id');
        $transfer = Transfer::find($transferId);

        if (!$transfer) {
            return response('<?xml version="1.0" encoding="UTF-8"?><Response><Say voice="alice" language="fr-FR">Transfert non trouvé.</Say></Response>', 200)
                ->header('Content-Type', 'application/xml');
        }

        $heureSurPlace = Carbon::parse($transfer->start_date)
            ->subMinutes(15)
            ->locale('fr')
            ->timezone('Europe/Paris')
            ->isoFormat('HH:mm');

        $message = "Bonjour de Paris Via , Sur place prévu à $heureSurPlace pour votre transfert. Merci de vous préparer.";

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say voice="alice" language="fr-FR">' . $message . '</Say>
</Response>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
