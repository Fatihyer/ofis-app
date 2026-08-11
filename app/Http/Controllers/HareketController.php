<?php

namespace App\Http\Controllers;

use App\Models\Hareket;
use Illuminate\Http\Request;
use App\Models\Acente;
use App\Models\Payment;
use App\Models\Offset;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use App\Helpers\HareketHelper;
use App\Helpers\LogActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class HareketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Hareket  $hareket
     * @return \Illuminate\Http\Response
     */
    public function show(Hareket $hareket)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Hareket  $hareket
     * @return \Illuminate\Http\Response
     */
    public function edit(Hareket $hareket)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Hareket  $hareket
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
    $request->validate([
        'amount'      => 'required|numeric|min:0',
        'kur_id'      => 'required',
        'payment_id'  => 'nullable',
    ]);

    DB::transaction(function () use ($request, $id) {

        $hareket = Hareket::findOrFail($id);

        /** 1️⃣ ANA HAREKET */
        $hareket->update([
            'aciklama'      => $request->aciklama,
            'amount'        => abs($request->amount),
            'kur_id'        => $request->kur_id,
            'payment_id'    => $request->payment_id,
            'default_price' => $request->default_price,
            'invoiceno'     => $request->invoiceno,
        ]);

        /** 2️⃣ VARSA ESKİ OFFSET’İ TEMİZLE */
        if ($hareket->offset_id) {
            $hareket->offset->harekets()->delete();
            $hareket->offset->delete();
            $hareket->update(['offset_id' => null]);
        }

        /** 3️⃣ CASH PAYMENT → OFFSET */
        if (
            $request->filled('cash') &&
            $request->cash > 0 &&
            $request->payment_id == 2 &&
            $hareket->post &&
            $hareket->post->acente_id != $hareket->acente_id
        ) {
            $offset = Offset::create([
                'tarih'        => $hareket->hareketable->start_date,
                'a_acente_id'  => $hareket->post->acente_id,
                'b_acente_id'  => $hareket->acente_id,
                'aciklama'     => 'Cash transfer',
            ]);

            HareketHelper::createOffset($offset, [
                'aciklama' => 'Cash transfer',
                'tarih'    => $hareket->hareketable->start_date,
                'amount'   => $request->cash,
                'kur_id'   => $request->kur_id,
                'from'     => $hareket->post->acente_id,
                'to'       => $hareket->acente_id,
            ]);

            $hareket->update(['offset_id' => $offset->id]);
        }

        LogActivity::addToLog(
    'Transfer price edited. Hareket ID: '.$hareket->id,
    $hareket->post_id,
    json_encode([
        'amount' => $request->amount,
        'kur' => $request->kur_id,
        'payment' => $request->payment_id,
        'invoice' => $request->invoiceno
    ])
);
    });
    return response()->json([
        'success' => true,
        'id'      => $id,
    ]);
}
  

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Hareket  $hareket
     * @return \Illuminate\Http\Response
     */
    public function destroy(Hareket $hareket)
    {
        //
    }
    public function bakiye($id)
{
    $sum = Hareket::where('acente_id', $id)
        ->selectRaw("
            kur_id,
            SUM(
                CASE
                    WHEN ab = 2 THEN amount
                    WHEN ab = 1 THEN -amount
                END
            ) as tpl
        ")
        ->groupBy('kur_id')
        ->get();

    foreach ($sum as $bakiye) {
        echo number_format(abs($bakiye->tpl), 2),
             ' ',
             $bakiye->kur->short_name,
             ' ',
             ($bakiye->tpl >= 0 ? 'Créditeur' : 'Débiteur'),
             '<br>';
    }
}
    
}
