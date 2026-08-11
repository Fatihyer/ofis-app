<?php

namespace App\Http\Controllers;

use App\Models\Option;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('permission:options.view')->only(['index', 'show']);
        $this->middleware('permission:options.update')->except(['index', 'show']);
    }
    public function index()
    {
        $options = Option::orderby('id', 'desc')->paginate(100); //show only 5 items at a time in descending order
         return view('options.index', compact('options'));//
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
          
       $options= Option::create($request->all());
      return redirect()->route('options.index')
            ->with('flash_message', 'Options ,
             '. $options->name.' created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Option  $option
     * @return \Illuminate\Http\Response
     */
    public function show(Option $option)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Option  $option
     * @return \Illuminate\Http\Response
     */
    public function edit(Option $option)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Option  $option
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Option $option)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Option  $option
     * @return \Illuminate\Http\Response
     */
    public function destroy(Option $option)
    {
        //
    }
   public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
               'value'=>'required',            
            ]);  
      $id = $request->input('id');
      $options=Option::findOrFail($id);
      $options->name = $request->input('name');
      $options->value = $request->input('value');
      $options->save();

        return redirect()->route('options.index', 
             $options->id)->with('flash_message', 
            'Tax name, '.  $options->name.' updated');  
    }
}
