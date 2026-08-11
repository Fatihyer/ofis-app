<?php

namespace App\Exports;

//use App\Hareket;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
//use Carbon\Carbon;
use App\Models\Dovizal;

class InvoicesExport implements FromView
{
    
    public function __construct($invoices)
    {
        $this->invoices = $invoices;
     
        $this->date = $invoices->tarih;
        $this->kur = $invoices->kur_id;
        if (env('FIRMA')=="tit")
          {
             if ($this->kur!=2)
                  {$this->dovisal=Dovizal::where([
                  ['tarih',$this->date ."- INTERVAL 1 DAY"],
                  ['kur_id',$this->kur]])->first();
               }
               else
               {
                 $this->dovisal=false;
               }
        } 
     }
    public function view(): View
    {
      
      return view('invoice.tit', [ 'invoices'=>$this->invoices, 'dovizal'=>$this->dovisal]);
        
    }
}
