<?php
  
namespace App\Exports;
  
use App\Models\Fromexcel;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;
  
class GarantiImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    
    public function model(array $row)
    {
    
  
     return new Fromexcel([
            'tarih'     => Carbon::createFromFormat('d/m/Y',$row['0']),
            'aciklama'    => $row[1], 
            'etiket' =>$row[2],
            'tutar'=>$row[3],
            'dekont'=>$row[5],
            'acente_id'=>request()->input('acente_id'),
            'kur_id'=>request()->input('kur_id'),
      ]);
       
    }
}