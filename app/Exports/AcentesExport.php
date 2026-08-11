<?php

namespace App\Exports;

use App\Models\Hareket;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class AcentesExport implements FromView
{
    
    public function __construct($id,$start_date,$end_date)
    {
        $this->id = $id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        
    }
    public function view(): View
    {
     
      
      return view('acentes.export-balance', [
        //  'harekets' => Hareket::where('acente_id',$this->id)->orderby('tarih', 'asc')->get(),
          'harekets' => Hareket::where('acente_id',$this->id)
            ->whereBetween('tarih', [$this->start_date ,$this->end_date])
           ->orderby('tarih','asc')->get(), 
            'bakiye'=>0 
      
        ]);
      
    }
}
