<?php

namespace App\Http\Controllers;
use Auth;
use App\Models\Status;
use Illuminate\Http\Request;
use App\Models\Color;

class StatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function __construct() {
      //  $this->middleware(['auth', 'clearance'])->except('index', 'show');
      $this->middleware(['role:Admin|ofis']);
        
    }
    public function index()
    {
       $colors=Color::pluck('name', 'id'); 
       $statuss = Status::orderby('id', 'desc')->paginate(100); //show only 5 items at a time in descending order
         return view('status.index', compact('statuss','colors'));
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
          
       $status= Status::create($request->all());
      return redirect()->route('statuss.index')
            ->with('flash_message', 'Statue,
             '. $status->name.' created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Status  $status
     * @return \Illuminate\Http\Response
     */
    public function show(Status $status)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Status  $status
     * @return \Illuminate\Http\Response
     */
    public function edit(Status $status)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Status  $status
     * @return \Illuminate\Http\Response
     */
    public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
                        
            ]);  
     $id = $request->input('id');
      $state=Status::findOrFail($id);
      $state->name = $request->input('name');
      $state->color_id = $request->input('color_id');
        $state->save();

        return redirect()->route('statuss.index', 
             $state->id)->with('flash_message', 
            'State, '.  $state->name.' updated');  
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Status  $status
     * @return \Illuminate\Http\Response
     */
    public function destroy(Status $status)
    {
        //
    }
}
