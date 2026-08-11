<?php

namespace App\Http\Controllers;

use App\Models\Offset;
use App\Models\Acente;
use App\Models\Kur;
use Illuminate\Http\Request;
use App\Helpers\HareketHelper;
use Illuminate\Support\Facades\DB;

class OffsetController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:Admin|ofis']);
    }

    /* ---------------------------------------------------
     | LIST
     --------------------------------------------------- */
    public function index(Request $request)
{
    $query = Offset::query()->with('harekets');

    /* 🔍 SEARCH */
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('aciklama', 'like', "%{$search}%")
              ->orWhere('id', 'like', "%{$search}%")
              ->orWhereHas('harekets', function ($hq) use ($search) {
                  $hq->where('amount', 'like', "%{$search}%");
              });
        });
    }

    /* 📅 DATE RANGE */
    if ($request->filled('start_date') && $request->filled('end_date')) {
        $query->whereBetween('tarih', [
            $request->start_date,
            $request->end_date
        ]);
    }

    /* ↕️ SORT */
    if ($request->filled('sort_by') && $request->filled('order')) {
        $allowedSorts = ['id', 'tarih', 'aciklama', 'created_at'];
        $sortBy = in_array($request->sort_by, $allowedSorts)
            ? $request->sort_by
            : 'tarih';

        $order = $request->order === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $order);
    } else {
        $query->orderBy('tarih', 'desc');
    }

    /* 📄 PAGINATION */
    $offsets = $query->paginate($request->get('per_page', 10));

    $kurs = Kur::pluck('name', 'id');

    return view('offsets.index', compact('offsets', 'kurs'));
}

    /* ---------------------------------------------------
     | CREATE
     --------------------------------------------------- */
    public function create()
    {
        return view('offsets.create', [
            'acentes' => Acente::orderBy('name')->pluck('name', 'id'),
            'kurs'    => Kur::pluck('name', 'id'),
        ]);
    }

    /* ---------------------------------------------------
     | STORE
     --------------------------------------------------- */
    public function store(Request $request)
    {
        $request->validate([
            'a_acente_id' => 'required|different:b_acente_id',
            'b_acente_id' => 'required',
            'aciklama'    => 'required|string',
            'tarih'       => 'required|date',
            'amount'      => 'required|numeric|min:0',
            'kur_id'      => 'required|exists:kurs,id',
        ], $this->validationMessages());

        $tarih = $this->buildDateTime($request);

        $offset = Offset::create([
            'tarih'       => $tarih,
            'a_acente_id' => $request->a_acente_id,
            'b_acente_id' => $request->b_acente_id,
            'aciklama'    => $request->aciklama,
        ]);

        HareketHelper::createOffset($offset, [
            'aciklama' => $request->aciklama,
            'tarih'    => $tarih,
            'amount'   => $request->amount,
            'kur_id'   => $request->kur_id,
            'from'     => $request->a_acente_id,
            'to'       => $request->b_acente_id,
        ]);

        return redirect()
            ->route('offsets.index')
            ->with('flash_message', 'Offset başarıyla oluşturuldu');
    }

    /* ---------------------------------------------------
     | EDIT
     --------------------------------------------------- */
    public function edit($id)
    {
        return view('offsets.edit', [
            'offset'  => Offset::with('harekets')->findOrFail($id),
            'acentes' => Acente::orderBy('name')->pluck('name', 'id'),
            'kurs'    => Kur::pluck('name', 'id'),
        ]);
    }

    /* ---------------------------------------------------
     | UPDATE
     --------------------------------------------------- */
    public function update(Request $request, $id)
    {
        $request->validate([
            'a_acente_id' => 'required|different:b_acente_id',
            'b_acente_id' => 'required',
            'aciklama'    => 'required|string',
            'tarih'       => 'required|date',
            'amount'      => 'required|numeric|min:0',
            'kur_id'      => 'required|exists:kurs,id',
        ], $this->validationMessages());

        $tarih = $this->buildDateTime($request);

        $offset = Offset::with('harekets')->findOrFail($id);

        $offset->update([
            'tarih'       => $tarih,
            'a_acente_id' => $request->a_acente_id,
            'b_acente_id' => $request->b_acente_id,
            'aciklama'    => $request->aciklama,
        ]);

        // Eski hareketleri temizle (2 adet olmalı)
        $offset->harekets()->delete();

        HareketHelper::createOffset($offset, [
            'aciklama' => $request->aciklama,
            'tarih'    => $tarih,
            'amount'   => $request->amount,
            'kur_id'   => $request->kur_id,
            'from'     => $request->a_acente_id,
            'to'       => $request->b_acente_id,
        ]);

        return redirect()
            ->route('offsets.index')
            ->with('flash_message', 'Offset güncellendi');
    }


    private function buildDateTime(Request $request): string
    {
        $time = $request->filled('time') ? $request->time : '00:00';

        return trim($request->tarih . ' ' . $time);
    }

    private function validationMessages(): array
    {
        return [
            'amount.min' => 'Le montant ne peut pas être négatif.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être numérique.',
            'a_acente_id.different' => 'Les deux comptes doivent être différents.',
        ];
    }

    /* ---------------------------------------------------
     | DELETE
     --------------------------------------------------- */
    public function destroy($id)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('Superadmin'), 403);

        $offset = Offset::with('harekets')->findOrFail($id);

        // Güvenlik: post bağlıysa silme
        if ($offset->harekets()->where('post_id', '>', 0)->exists()) {
            return back()->withErrors(['Bu offset bir post’a bağlı, silinemez']);
        }

        $offset->harekets()->delete();
        $offset->delete();

        return back()->with('flash_message', 'Offset silindi');
    }

    public function multiCreate()
{
    return view('offsets.multicreate', [
        'acentes' => Acente::orderBy('name')->pluck('name', 'id'),
        'kurs'    => Kur::pluck('name', 'id'),
    ]);
}
  public function multiStore(Request $request)
{
    $request->validate([
        'a_acente_id'   => 'required|integer',
        'tarih'         => 'required|date',
        'kur_id'        => 'required|exists:kurs,id',
        'b_acente_id'   => 'required|array',
        'b_acente_id.*' => 'required|integer|different:a_acente_id',
        'amount'        => 'required|array',
        'amount.*'      => 'required|numeric|min:0',
        'aciklama'      => 'required|array',
        'aciklama.*'    => 'required|string',
        'ab'            => 'required|array',
        'ab.*'          => 'required|in:1,2',
    ]);

    $tarih = $request->tarih . ' ' . ($request->time ?? '00:00:00');

    DB::transaction(function () use ($request, $tarih) {

        foreach ($request->b_acente_id as $i => $bAcenteId) {

            $amount = abs($request->amount[$i]);

            // ab = 1 → a_acente → b_acente
            // ab = 2 → b_acente → a_acente
            if ((int)$request->ab[$i] === 1) {
                $from = $request->a_acente_id;
                $to   = $bAcenteId;
            } else {
                $from = $bAcenteId;
                $to   = $request->a_acente_id;
            }

            $offset = Offset::create([
                'tarih'       => $tarih,
                'a_acente_id' => $from,
                'b_acente_id' => $to,
                'aciklama'    => $request->aciklama[$i],
            ]);

            HareketHelper::createOffset($offset, [
                'aciklama' => $request->aciklama[$i],
                'tarih'    => $tarih,
                'amount'   => $amount,
                'kur_id'   => $request->kur_id,
                'from'     => $from,
                'to'       => $to,
            ]);
        }
    });

    return redirect()
        ->route('offsets.index')
        ->with('flash_message', 'Toplu offset kayıtları başarıyla oluşturuldu');
}

}
