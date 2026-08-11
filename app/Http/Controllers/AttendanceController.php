<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAttendance;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AttendanceController extends Controller
{
   
    public function __construct() {
        //
        $this->middleware('auth');
          $this->middleware(['role:Admin|ofis|transport']);
      }
  
  
      public function index(Request $request)
      {
          // Tüm kullanıcıları çek
          $users = User::all();
      
          // Filtreleme sorgusu
          $query = UserAttendance::query();
      
          // Kullanıcı filtresi
          if ($request->filled('user_id')) {
              $query->where('user_id', $request->user_id);
          }
      
          // Tarih aralığı filtresi
          if ($request->filled('start_date') && $request->filled('end_date')) {
              $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
          }
      
          // Sıralama ve verileri çekme
          $userAttendances = $query->orderBy('created_at', 'desc')->get();
      
          return view('users.user_attendances', compact('userAttendances', 'users'));
      }
   
   
    public function recordAttendance(Request $request)
    {
      /* $request->validate([
            'permanence_name' => 'required|string|max:255',
        ]);*/

        $attendance = new UserAttendance();
        $attendance->user_id = Auth::id();
        $attendance->operation_user_id=0;
        $attendance->permanence_name =Auth::user()->name;
        $attendance->save();
        return back()->with('status', 'Demande Sende');
     
    }
    public function recordOperation(Request $request)
    {
     /*   $request->validate([
            'permanence_name' => 'required|string|max:255',
        ]);*/

        $attendance = new UserAttendance();
        $attendance->user_id = Auth::id();
        $attendance->operation_user_id=Auth::id();
        $attendance->permanence_name =Auth::user()->name;
        $attendance->save();

        return back()->with('status', 'Operation Sende');
     
    }
    public function recordParisgezgini(Request $request)
    {
     /*   $request->validate([
            'permanence_name' => 'required|string|max:255',
        ]);*/

        $attendance = new UserAttendance();
        $attendance->user_id = Auth::id();
        $attendance->parisgezgini_user_id=Auth::id();
        $attendance->permanence_name =Auth::user()->name;
        $attendance->save();

        return back()->with('status', 'Parisgezgini Sende');
     
    }
    public function getLastAttendance()
    {
        $lastAttendance = UserAttendance::where('operation_user_id',0)->where('parisgezgini_user_id', 0)->latest()->first();


        return response()->json(['data' => $lastAttendance], 200);
    } 
    public function getLastOperation()
    {
        $lastOperation = UserAttendance::where('operation_user_id','>',0) // Ensures it's not null
        ->latest()
        ->first();


        return response()->json(['data' => $lastOperation], 200);
    } 
    public function getLastParisgezgini()
    {
        $lastParisgezgini = UserAttendance::where('parisgezgini_user_id','>',0) // Ensures it's not null
        ->latest()
        ->first();


        return response()->json(['data' => $lastParisgezgini], 200);
    }  

    

}
 