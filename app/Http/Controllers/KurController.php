<?php

namespace App\Http\Controllers;

use App\Models\Kur;
use Illuminate\Http\Request;

class KurController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   public function __construct() {
    //  $this->middleware(['auth', 'isAdmin'])->except('index', 'show');
    $this->middleware(['role:Admin|ofis']);
    
    }
    public function index()
    {
         //$colors=Color::pluck('name', 'id'); 
       $exchanges = Kur::orderby('id', 'desc')->paginate(100); //show only 5 items at a time in descending order
         return view('kur.index', compact('exchanges'));//
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
          
       $exchange= Kur::create($request->all());
      return redirect()->route('kurs.index')
            ->with('flash_message', 'Exchange name ,
             '. $exchange->name.' created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Kur  $kur
     * @return \Illuminate\Http\Response
     */
    public function show(Kur $kur)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Kur  $kur
     * @return \Illuminate\Http\Response
     */
    public function edit(Kur $kur)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Kur  $kur
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Kur $kur)
    {
        //
    }
     public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
                        
            ]);  
     $id = $request->input('id');
      $kur=Kur::findOrFail($id);
      $kur->name = $request->input('name');
      $kur->short_name = $request->input('short_name');
      $kur->icon = $request->input('icon');
      $kur->save();

        return redirect()->route('kurs.index', 
             $kur->id)->with('flash_message', 
            'Exchange name, '.  $kur->name.' updated');  
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Kur  $kur
     * @return \Illuminate\Http\Response
     */
    public function destroy(Kur $kur)
    {
        //
    }
}
