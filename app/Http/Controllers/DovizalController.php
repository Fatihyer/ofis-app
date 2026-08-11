<?php

namespace App\Http\Controllers;

use App\Models\Dovizal;
use App\Models\Kur;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Models\Option;


class DovizalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
  
     public function __construct() {
      //
      $this->middleware('auth');
        $this->middleware(['role:Admin|ofis|transport']);
    }
  
    public function urlyap($tarih)
    {
       $date = Carbon::createFromFormat('Y-m-d', $tarih);
       if($date->dayOfWeek == Carbon::SUNDAY)
        {
          $date->subDays(2);
        }
      if($date->dayOfWeek == Carbon::SATURDAY)
        {
          $date->subDays(1);
        }
       $tarih=$date->format('Y-m-d');   
        
         $gelentarihbol = explode('-',trim($tarih));
         $gelentarihay  = $gelentarihbol[1];
         $gelentarihgun = $gelentarihbol[2];
         $gelentarihyil = $gelentarihbol[0];
         $tcmb = 'https://www.tcmb.gov.tr/kurlar/'.$gelentarihyil.$gelentarihay.'/'.$gelentarihgun.$gelentarihay.$gelentarihyil.'.xml';
        return $this->$tcmb=$tcmb;
    } 
    
    public function tomorrow($tarih)
    {
       $date = Carbon::createFromFormat('Y-m-d', $tarih);
       $date->addDays(1);
       $date->format('Y-m-d'); 
       return $this->yarin=$date;
    }
   public function yesterday($tarih)
    {
       $date = Carbon::createFromFormat('Y-m-d', $tarih);
       $date->subDays(1);
       $date->format('Y-m-d'); 
       return $this->dun=$date;
    } 
    
    public function index(Request $request)
    {
      if ($request->input('start_date')==true)
          {
        $tcmb=$this->urlyap($request->input('start_date')); 
        $tomorrow=$this->tomorrow($request->input('start_date'));
        $yesterday=$this->yesterday($request->input('start_date'));  
        $today=$request->input('start_date');
          }
     else {   
      $tcmb = "http://www.tcmb.gov.tr/kurlar/today.xml"; 
      $today=date('Y-m-d'); 
      $tomorrow=date("Y-m-d", strtotime( '+1 days' ) ); 
      $yesterday=date("Y-m-d", strtotime( '-1 days' ) );  
      } 
   
      try {
      if ($conn = simplexml_load_file($tcmb))
      {
   
     $usdal=$conn->Currency[0]->ForexBuying;
      $euroal=$conn->Currency[3]->ForexBuying;
    

      }
      else
      {
      $euroal=0; 
      $usdal=0;
      }
    } catch (\Exception $e) {
      // Handle the error when the XML file is not found
      $euroal = 0;
      $usdal = 0;
  }
      $eurid=Option::where('name','eurid')->firstOrFail();
      $usdid=Option::where('name','usdid')->firstOrFail();
       $eurkur=Dovizal::where(
        ['tarih'=>$today,
         'kur_id'=>$eurid['value']
        ] )->first();
      $usdkur=Dovizal::where(
        ['tarih'=>$today,
         'kur_id'=>$usdid['value']
        ] )->first();
    $kurs=Kur::pluck('name','id');
    //print_r($conn);
     return view('dovizal.index', compact('usdal','euroal','tomorrow','yesterday','kurs','eurid','usdid','eurkur','usdkur','today'));
    }
  
   

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      $this->validate($request, [
            'value'=>'required',
            'tarih'=>'required',
            'kur_id'=>'required',
         ]);  
      
      $acente = Dovizal::create($request->all());  
     return redirect()->route('dovizs.index', ['start_date'=>$request->tarih]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\dovizal  $dovizal
     * @return \Illuminate\Http\Response
     */
    public function show(dovizal $dovizal)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\dovizal  $dovizal
     * @return \Illuminate\Http\Response
     */
    public function edit(dovizal $dovizal)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\dovizal  $dovizal
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
     $this->validate($request, [
            'value'=>'required',
            'tarih'=>'required',
            'kur_id'=>'required',
         ]);  
     
     $doviz=Dovizal::findOrFail($id);
     $doviz->update($request->all());
    return redirect()->route('dovizs.index', ['start_date'=>$request->tarih]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\dovizal  $dovizal
     * @return \Illuminate\Http\Response
     */
    public function destroy(dovizal $dovizal)
    {
        //
    }
}
