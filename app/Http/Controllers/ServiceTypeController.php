<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Servicetype;
use App\Models\Color;
use App\Models\Firma;
use App\Models\Transfer;
use Config;
class ServiceTypeController extends Controller
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
     // $service=array('0'=>'Transfer', '1'=>'Dispo', '2'=>'Guide', '3'=>'Other');  
     $service=Config::get('fp.service_type');
     $firmas=Firma::pluck('name', 'id'); 
     $colors=Color::pluck('name', 'id'); 
     $servicetypes = Servicetype::sortable()->orderby('name', 'asc')->paginate(300); //show only 5 items at a time in descending order
    return view('servicetype.index', compact('servicetypes','colors','service','firmas'));
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
             'firma_id'=>'required',           
            ]);
      // print_r($request->input());   
       $transtertype= ServiceType::create($request->all());
      return redirect()->route('servicetype.index')
          ->with('flash_message', 'Transfer Type,
             '. $transtertype->name.' created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ServiceType  $servicetype
     * @return \Illuminate\Http\Response
     */
    public function show(ServiceType $servicetype)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ServiceType  $servicetype
     * @return \Illuminate\Http\Response
     */
    public function edit(ServiceType $servicetype)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ServiceType  $servicetype
     * @return \Illuminate\Http\Response
     */
     public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
                        
            ]);  
     $id = $request->input('id');
      $state=ServiceType::findOrFail($id);
      $state->name = $request->input('name');
      $state->hizmet = $request->input('hizmet');
       $state->firma_id = $request->input('firma_id');
      $state->color_id = $request->input('color_id');
        $state->save();

        return redirect()->route('servicetype.index', 
             $state->id)->with('flash_message', 
            'State, '.  $state->name.' updated');  
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ServiceType  $servicetype
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
       
      $varmi=Transfer::where('servicetype_id',$id)->pluck('id','post_id');
      $say=count($varmi);
      if ($say>0)
      {
        foreach ($varmi as $key=>$var)
        {
          echo "Transfer id:",$key, "File","<a href=\"",route('posts.show',$var),"\">", $var,"</a><br/>";
        }
      }
      else
      {
         $service=ServiceType::findOrFail($id);
        $service->delete();
  
      return Redirect::back()->with('flash_message', 'delete');   
      } 
        
    }
}
