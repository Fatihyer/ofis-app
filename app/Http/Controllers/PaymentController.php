<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Color;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   public function __construct() {
    $this->middleware('auth');
    $this->middleware('permission:payments.view')->only(['index', 'show']);
    $this->middleware('permission:payments.create')->only(['create', 'store']);
    $this->middleware('permission:payments.update')->only(['edit', 'update', 'guncel']);
    $this->middleware('permission:payments.delete')->only(['destroy']);
    }
    public function index()
    {
         $payments = Payment::orderby('id', 'desc')->paginate(100); //show only 5 items at a time in descending order
         $colors=Color::pluck('name', 'id');
         return view('payments.index', compact('payments','colors'));// 
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
            'name'=>'required|max:100',
                        
            ]);
          
       $payment= Payment::create($request->all());
      return redirect()->route('payments.index')
            ->with('flash_message', 'Payments name ,
             '. $payment->name.' created');
    }
   public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
                        
            ]);  
      $id = $request->input('id');
      $payment=Payment::findOrFail($id);
      $payment->name = $request->input('name');
     $payment->cari = $request->input('cari');
      $payment->color_id = $request->input('color_id');  
      $payment->save();

        return redirect()->route('payments.index', 
             $payment->id)->with('flash_message', 
            'Exchange name, '.  $payment->name.' updated');  
   
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
