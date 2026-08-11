<?php

namespace App\Exports;

use App\Models\Acente;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AcenteDetailledExport implements FromView, ShouldAutoSize
{
    private int $id;
    private string $startDate;
    private string $endDate;

    public function __construct($id, $startDate, $endDate)
    {
        $this->id = (int) $id;
        $this->startDate = Carbon::parse($startDate)->toDateString();
        $this->endDate = Carbon::parse($endDate)->toDateString();
    }

    public function view(): View
    {
        $startAt = Carbon::parse($this->startDate)->startOfDay();
        $endAt = Carbon::parse($this->endDate)->endOfDay();

        $acente = Acente::findOrFail($this->id);
        $posts = Post::with([
                'status',
                'transfer.servicetype',
                'transfer.vehicule',
                'transfer.driver',
                'transfer.harekets.kur',
            ])
            ->where('acente_id', $this->id)
            ->whereBetween('start_date', [$startAt, $endAt])
            ->orderBy('start_date', 'asc')
            ->get();

        return view('acentes.export-detailled', [
            'acente' => $acente,
            'posts' => $posts,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }
}
