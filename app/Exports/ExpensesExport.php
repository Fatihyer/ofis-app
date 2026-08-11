<?php

namespace App\Exports;

use App\Models\Hareket;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class ExpensesExport implements FromView
{
    
    public function __construct($id)
    {
        $this->id = $id;
        
        
    }
    public function view(): View
    {
     
      
      return view('posts.export-balance', [
        //  'harekets' => Hareket::where('acente_id',$this->id)->orderby('tarih', 'asc')->get(),
          'harekets' => Hareket::where('post_id',$this->id)
           ->orderby('tarih','asc')->get(), 
            'bakiye'=>0 
      
        ]);
      
    }
}
