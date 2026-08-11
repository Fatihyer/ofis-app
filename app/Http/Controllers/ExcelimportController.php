<?php
namespace App\Http\Controllers;
use App\Models\Acente;  
use App\Models\Fromexcel;
use Illuminate\Http\Request;
use App\Exports\GarantiImport;
use App\Exports\GaranticartImport;
use App\Models\Offset;
use App\Models\Firma;
use App\Models\Kur;

//use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;
  
class ExcelimportController extends Controller
{
  
  public function __construct() {
    //
    $this->middleware('auth');
      $this->middleware(['role:Admin|ofis|transport']);
  }
    public function index()
    {
     $bankalist=Fromexcel::where('offset_id',null)->take(5)->get();
      $firmas=Firma::orderby('name')->pluck('name','id')->toArray() ; 
      $acentes =Acente::orderBy('name')->pluck('name', 'id');
     return view('excel.lists',compact('bankalist','acentes','firmas'));
      
    }
      
    public function importExportView()
    {
      // $acentes=Acente::orderby('name')->pluck('name','id')->toArray() ; 
       $acentes = Acente::whereHas('firmas', function($query) {
        $query->where('firmas.id', 7);
    })->orderBy('name')->pluck('name', 'id')->toArray();
       
       $kurs=Kur::orderBy('name')->pluck('name', 'id'); 
      return view('excel.import',compact('acentes','kurs'));
    }

    public function  importExportcard()
    {
      // $acentes=Acente::orderby('name')->pluck('name','id')->toArray() ; 
       $acentes = Acente::whereHas('firmas', function($query) {
        $query->where('firmas.id', 7);
    })->orderBy('name')->pluck('name', 'id')->toArray();
       
       $kurs=Kur::orderBy('name')->pluck('name', 'id'); 
      return view('excel.importcard',compact('acentes','kurs'));
    }

   
   
    /**
    * @return \Illuminate\Support\Collection
    */
    public function export() 
    {
      //  return Excel::download(new UsersExport, 'users.xlsx');
    }
   
    /**
    * @return \Illuminate\Support\Collection
    */
    public function import(Request $request ) 
    {
        Excel::import(new GarantiImport,request()->file('file'));
           
         return redirect()->route('listexcel')
            ->with('flash_message', 'File Insered');
    }
    public function importcard(Request $request ) 
    {
        Excel::import(new GaranticartImport,request()->file('file'));
           
         return redirect()->route('listexcel')
            ->with('flash_message', 'File Insered');
    }  

  public function destroy($id)
  {
    $delete=Fromexcel::find($id)->delete();
     return redirect()->route('listexcel')
            ->with('flash_message', 'id deleted');
    
  }
  public function addoffset(Request $request, $id)
{
    $request->validate([
        'a_acente_id' => 'required|integer',
        'b_acente_id' => 'required|integer|different:a_acente_id',
        'aciklama'    => 'required|string',
        'tarih'       => 'required|date',
        'amount'      => 'required|numeric',
        'kur_id'      => 'required|integer',
    ]);

    $rawAmount = (float) $request->amount;
    $amount    = abs($rawAmount); // 🔒 HER ZAMAN POZİTİF

    /*
     | Banka perspektifi:
     | negatif → para çıktı → a_acente borçlu
     | pozitif → para girdi → b_acente borçlu
    */
    if ($rawAmount < 0) {
        $debitAcente  = $request->a_acente_id;
        $creditAcente = $request->b_acente_id;
    } else {
        $debitAcente  = $request->b_acente_id;
        $creditAcente = $request->a_acente_id;
    }

    /* OFFSET */
    $offset = Offset::create([
        'tarih'       => $request->tarih,
        'a_acente_id' => $debitAcente,
        'b_acente_id' => $creditAcente,
        'aciklama'    => $request->aciklama,
    ]);

    /* BORÇ HAREKETİ */
    $offset->harekets()->create([
        'aciklama'  => $request->aciklama,
        'tarih'     => $request->tarih,
        'post_id'   => 0,
        'amount'    => $amount,
        'ab'        => 2, // BORÇ
        'kur_id'    => $request->kur_id,
        'acente_id' => $debitAcente,
    ]);

    /* ALACAK HAREKETİ */
    $offset->harekets()->create([
        'aciklama'  => $request->aciklama,
        'tarih'     => $request->tarih,
        'post_id'   => 0,
        'amount'    => $amount,
        'ab'        => 1, // ALACAK
        'kur_id'    => $request->kur_id,
        'acente_id' => $creditAcente,
    ]);

    /* EXCEL SATIRINI KİLİTLE */
    Fromexcel::whereKey($id)->update([
        'offset_id' => $offset->id,
    ]);

    return redirect()
        ->route('listexcel')
        ->with('flash_message', "Offset #{$offset->id} başarıyla oluşturuldu");
}

    
    
}