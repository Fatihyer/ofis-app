<?php
  
namespace App\Exports;
  
use App\Models\Efatura;
use App\Models\Acente;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;
  
class EfaturaImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    
    public function model(array $row)
    {
   
    $vdno = Acente::where('vdno', $row[1])->first();
    if (isset($vdno->id))   
    {
        $acente=$vdno->id;
    }
    else
    {
        $acente=false ;
    }    

     return new Efatura([
            'name'    => $row[0],
            'vd'    => $row[1],
            'fatno' =>$row[2],
            'date'  =>Carbon::createFromFormat('d.m.Y',$row['3']),
            'price'=> str_replace(',', '.', $row[4]),
            'senaryo'=>$row[5],
            'durum'=>$row[6],
            'tip'=>$row[7],
            'acente_id'=>$acente,
      ]);
       
    }
}

