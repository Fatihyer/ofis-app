<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Exports\EfaturaImport;
use App\Models\Efatura;
use App\Models\Acente;
use Illuminate\Support\Facades\Schema;

//use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;
  
class EfaturaController extends Controller
{
  
       public function __construct() {
    //  $this->middleware(['auth', 'isAdmin'])->except('index', 'show');
        $this->middleware(['auth', 'isAdmin']);
      }
    public function index()
    {
       $efaturaTableReady = Schema::hasTable('efaturas');
       $efaturalar = $efaturaTableReady ? Efatura::get() : collect();
       $acentes = Acente::orderBy('name')->pluck('name', 'id');
   
        return view('efatura.index', compact('efaturalar', 'acentes', 'efaturaTableReady'));
    }
    public function yukle()
    {
        return view('efatura.yukle');
    }
    
    public function import()
    {
        if (!Schema::hasTable('efaturas')) {
            return redirect()->route('efatura')
                ->with('error', 'La table efaturas est absente. Import impossible pour le moment.');
        }

        Excel::import(new EfaturaImport, request()->file('file'));
           
        return redirect()->route('efatura')
            ->with('flash_message', 'Fichier importé.');
    }
       
  
    
    
}