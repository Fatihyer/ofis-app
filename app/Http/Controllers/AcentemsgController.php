<?php

namespace App\Http\Controllers;

use App\Models\Acentemsg;
use Illuminate\Http\Request;

class AcentemsgController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * 
     * 
     */
    public function __construct() {
        //
        $this->middleware('auth');
          $this->middleware(['role:Admin|ofis|transport']);
      }
    public function index()
    {
        //
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
       $acenteid=$request->input('acente_id');
      $messages=Acentemsg::updateOrCreate(['acente_id' => $acenteid]);
      $messages->message=$request->message;
      $messages->save();
      return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Acentemsg  $acentemsg
     * @return \Illuminate\Http\Response
     */
    public function show(Acentemsg $acentemsg)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Acentemsg  $acentemsg
     * @return \Illuminate\Http\Response
     */
    public function edit(Acentemsg $acentemsg)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Acentemsg  $acentemsg
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Acentemsg $acentemsg)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Acentemsg  $acentemsg
     * @return \Illuminate\Http\Response
     */
    public function destroy(Acentemsg $acentemsg)
    {
        //
    }
}
