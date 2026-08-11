<?php

namespace App\Http\Controllers;

use App\Models\Other;
use App\Models\Acente;
use Illuminate\Http\Request;
use App\Models\Option;
use App\Helpers\HareketHelper;
use App\Helpers\LogActivity;
use DB;

class OtherController extends Controller
{
    protected $otherFirmaId;

    public function __construct()
    {
        $this->middleware('auth');

        $this->otherFirmaId = optional(
            Option::where('name', 'otherid')->first()
        )->value;
    }

    /* ---------------------------------------------------
     | CREATE
     --------------------------------------------------- */
    public function create()
    {
        $others = Acente::whereHas('firmas', function ($q) {
            $q->where('id', $this->otherFirmaId);
        })->orderBy('name')->pluck('name', 'id');

        return view('other.create', compact('others'));
    }

    /* ---------------------------------------------------
     | STORE
     --------------------------------------------------- */
    public function store(Request $request)
    {
        $request->validate([
            'acente_id' => 'required|integer',
            'from'      => 'required|date',
            'to'        => 'nullable|date',
            'post_id'   => 'required|integer',
        ]);

        DB::transaction(function () use ($request) {

            $other = Other::create($request->only([
                'acente_id',
                'from',
                'to',
                'post_id',
                'comment'
            ]));

            // Ana muhasebe hareketi (placeholder)
            HareketHelper::create($other, [
                'aciklama'  => $request->comment ?? 'Other service',
                'tarih'     => $request->from,
                'post_id'   => $request->post_id,
                'amount'    => 0,
                'ab'        => 2,
                'kur_id'    => 1,
                'acente_id' => $request->acente_id,
            ]);

            LogActivity::addToLog(
                'Other created',
                $request->post_id,
                'Created'
            );
        });

        return redirect()
            ->route('posts.show', $request->post_id)
            ->with('flash_message', 'Other service oluşturuldu');
    }

    /* ---------------------------------------------------
     | EDIT
     --------------------------------------------------- */
    public function edit($id)
    {
        $other = Other::findOrFail($id);

        $others = Acente::whereHas('firmas', function ($q) {
            $q->where('id', $this->otherFirmaId);
        })->orderBy('name')->pluck('name', 'id');

        return view('other.edit', compact('other', 'others'));
    }

    /* ---------------------------------------------------
     | UPDATE
     --------------------------------------------------- */
    public function update(Request $request, $id)
    {
        $request->validate([
            'acente_id' => 'required|integer',
            'from'      => 'required|date',
            'to'        => 'nullable|date',
            'post_id'   => 'required|integer',
        ]);

        DB::transaction(function () use ($request, $id) {

            $other = Other::findOrFail($id);

            $other->update($request->only([
                'acente_id',
                'from',
                'to',
                'comment'
            ]));

            // SADECE ana hareket
            $mainHareket = $other->harekets()->first();

            if ($mainHareket) {
                $mainHareket->update([
                    'tarih'     => $request->from,
                    'acente_id' => $request->acente_id,
                    'aciklama'  => $request->comment,
                ]);
            }

            LogActivity::addToLog(
                'Other updated',
                $request->post_id,
                'Updated'
            );
        });

        return redirect()
            ->route('posts.show', $request->post_id)
            ->with('flash_message', 'Other service güncellendi');
    }

    /* ---------------------------------------------------
     | DELETE
     --------------------------------------------------- */
    public function destroy(Other $other)
    {
        DB::transaction(function () use ($other) {

            $mainHareket = $other->harekets()->first();

            // Finansal hareket varsa silme
            if ($mainHareket && $mainHareket->amount > 0) {
                abort(403, 'Bu kayıt finansal işlem içeriyor');
            }

            $other->harekets()->delete();
            $other->delete();

            LogActivity::addToLog(
                'Other deleted',
                $other->post_id,
                'Deleted'
            );
        });

        return back()->with('flash_message', 'Other service silindi');
    }

    /* ---------------------------------------------------
     | CLONE
     --------------------------------------------------- */
    public function clone($id)
    {
        DB::transaction(function () use ($id) {

            $original = Other::findOrFail($id);

            $clone = Other::create([
                'acente_id' => $original->acente_id,
                'from'      => $original->from,
                'to'        => $original->to,
                'post_id'   => $original->post_id,
                'comment'   => $original->comment,
            ]);

            HareketHelper::create($clone, [
                'aciklama'  => 'Cloned other service',
                'tarih'     => $clone->from,
                'post_id'   => $clone->post_id,
                'amount'    => 0,
                'ab'        => 2,
                'kur_id'    => 1,
                'acente_id' => $clone->acente_id,
            ]);

            LogActivity::addToLog(
                'Other cloned',
                $clone->post_id,
                'Cloned'
            );
        });

        return back()->with('flash_message', 'Other service klonlandı');
    }
}
