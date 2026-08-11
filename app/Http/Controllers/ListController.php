<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Models\Hareket;
use App\Models\Transfer;
use Carbon\Carbon;

class ListController extends Controller
{

    
    public function __construct() {
        //  $this->middleware(['auth', 'isAdmin'])->except('index', 'show');
            $this->middleware(['auth', 'isAdmin']);
        
        }
        public function index($tarih=false)
        {
        abort_unless(auth()->user()?->hasRole('Superadmin'), 403, 'Accès réservé aux super administrateurs.');

        $today = Carbon::today();

         if (!$tarih) 
         {
            $tarih = Carbon::today();
         }
         else {
            $tarih = Carbon::parse($tarih);
         }

         $yesterday = $tarih->copy()->subDay();
         $tomorrow = $tarih->copy()->addDay();

         $hareket = Hareket::with(['hareketable.driver', 'hareketable.post'])
            ->whereDate('tarih', $tarih)
            ->whereIn('hareketable_type', [Transfer::class, 'App\Transfer'])
            ->orderBy('tarih')
            ->orderBy('id')
            ->paginate(100);

          return view('list.karzarar', compact('hareket','yesterday','tomorrow','today','tarih'));//
        }


}

