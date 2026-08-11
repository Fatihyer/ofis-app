<?php
  
namespace App\Exports;
  
use App\Models\Fromexcel;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;
  
class GaranticartImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    
    public function model(array $row)
    {
        // Format 'tutar' to remove thousand separators and convert comma to dot for decimals
        $tutar = str_replace(['.', ','], ['', '.'], $row[4]);
    
        return new Fromexcel([
            'tarih'     => Carbon::createFromFormat('d/m/Y', $row[0]),
            'aciklama'  => $row[1], 
            'etiket'    => $row[2],
            'tutar'     => $tutar,  // Ensure 'tutar' is formatted correctly
            'dekont'    => $row[3],
            'acente_id' => request()->input('acente_id'),
            'kur_id'    => request()->input('kur_id'),
        ]);
    }
}  