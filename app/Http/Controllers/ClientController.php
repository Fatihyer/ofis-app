<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
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
     $count=count($request->name);
     $name=  $request->name;
     $title=  $request->title;
     $surname=  $request->surname;
     $post_id=  $request->post_id;
     $tel=  $request->tel;
     $email=  $request->email;
     $comments=  $request->comments;
      
     for($i = 0; $i < $count; $i++){
     $clients = new Client;
     $clients->name=$name[$i];
     $clients->title=$title[$i];
     $clients->surname=$surname[$i];
     $clients->post_id=$post_id[$i];
     $clients->tel=$tel[$i];
     $clients->email=$email[$i];
     $clients->comments=$comments[$i];
     $clients->save();
     }
    
     return back()->with('flash_message',$count.' client name added');      
      
    
     
      
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function show(Client $client)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {
              $clients = Client::findOrFail($client->id);
      return view('clients.clientsedit',compact('clients'));
      
      
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Client $client)
    {
       $this->validate($request, [
            'name'=>'required',
         ]);

        $client = Client::findOrFail($client->id);
        $client->update($request->all());
        
        return redirect()->route('posts.show', 
            $client->post->id)->with('flash_message', 
            'Name,updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        $clients = Client::findOrFail($client->id);
        $clients->delete();
        return redirect()->back()->with('flash_message','Client Deleted');
        
    }
}
