<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use Illuminate\Http\Request;
use App\Models\Color;

class FirmaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {
        //
          $this->middleware(['role:Admin|ofis|transport']);
      }
    public function index()
    {
       $colors=Color::pluck('name', 'id'); 
       $firmas = Firma::orderby('id', 'desc')->paginate(100); //show only 5 items at a time in descending order
     // echo 'selam';  
      
      return view('firma.index', compact('firmas','colors'));
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
          
       $firma= Firma::create($request->all());
      return redirect()->route('firmas.index')
            ->with('flash_message', 'Statue,
             '. $firma->name.' created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Firma  $firma
     * @return \Illuminate\Http\Response
     */
    public function show(Firma $firma)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Firma  $firma
     * @return \Illuminate\Http\Response
     */
    public function edit(Firma $firma)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Firma  $firma
     * @return \Illuminate\Http\Response
     */
    public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
                        
            ]);  
     $id = $request->input('id');
      $state=Firma::findOrFail($id);
      $state->name = $request->input('name');
      $state->color_id = $request->input('color_id');
        $state->save();

        return redirect()->route('firmas.index', 
             $state->id)->with('flash_message', 
            'State, '.  $state->name.' updated');  
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Firma  $firma
     * @return \Illuminate\Http\Response
     */
    public function destroy(Firma $firma)
    {
        //
    }
}
