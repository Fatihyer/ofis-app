<?php

namespace App\Http\Controllers;

use App\Models\Kdv;
use Illuminate\Http\Request;

class KdvController extends Controller
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
        $kdvs = Kdv::orderby('id', 'desc')->paginate(100); //show only 5 items at a time in descending order
         return view('kdv.index', compact('kdvs'));//
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
            'percent'=>'required|max:100',
                        
            ]);
          
       $exchange= Kdv::create($request->all());
      return redirect()->route('kdvs.index')
            ->with('flash_message', 'Added-Value Tax name ,
             '. $exchange->name.' created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Kdv  $kdv
     * @return \Illuminate\Http\Response
     */
    public function show(Kdv $kdv)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Kdv  $kdv
     * @return \Illuminate\Http\Response
     */
    public function edit(Kdv $kdv)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Kdv  $kdv
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Kdv $kdv)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Kdv  $kdv
     * @return \Illuminate\Http\Response
     */
    public function destroy(Kdv $kdv)
    {
        //
    }
   public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
               'percent'=>'required|max:100',            
            ]);  
     $id = $request->input('id');
      $kur=Kdv::findOrFail($id);
      $kur->name = $request->input('name');
      $kur->percent = $request->input('percent');
      $kur->save();

        return redirect()->route('kdvs.index', 
             $kur->id)->with('flash_message', 
            'Tax name, '.  $kur->name.' updated');  
    }
}
