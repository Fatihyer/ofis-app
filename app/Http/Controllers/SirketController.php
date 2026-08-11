<?php

namespace App\Http\Controllers;

use App\Models\Sirket;
use Illuminate\Http\Request;

class SirketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {
        $this->middleware(['role:Admin|ofis']);
    }
    public function index()
    {
        $sirkets = Sirket::orderby('id', 'desc')->paginate(100); //show only 5 items at a time in descending order
         return view('sirkets.index', compact('sirkets'));//
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
          
       $sirkets= Sirket::create($request->all());
      return redirect()->route('sirkets.index')
            ->with('flash_message', 'Sirkets ,
             '. $sirkets->name.' created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Sirket  $sirket
     * @return \Illuminate\Http\Response
     */
    public function show(Sirket $sirket)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Sirket  $sirket
     * @return \Illuminate\Http\Response
     */
    public function edit(Sirket $sirket)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Sirket  $sirket
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sirket $sirket)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Sirket  $sirket
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sirket $sirket)
    {
        //
    }
   public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
               'info'=>'required',            
            ]);  
      $id = $request->input('id');
      $sirkets=Sirket::findOrFail($id);
      $sirkets->name = $request->input('name');
      $sirkets->tel = $request->input('tel');
      $sirkets->email = $request->input('email');
      $sirkets->info = $request->input('info');
     $sirkets->info2 = $request->input('info2');
     $sirkets->logo = $request->input('logo');
      $sirkets->save();

        return redirect()->route('sirkets.index', 
             $sirkets->id)->with('flash_message', 
            'Tax name, '.  $sirkets->name.' updated');  
    }
}
