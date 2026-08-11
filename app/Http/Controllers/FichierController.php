<?php

namespace App\Http\Controllers;
use File;

class FichierController extends Controller
{
  public function __construct() {
    //
    $this->middleware('auth');
      $this->middleware(['role:Admin|ofis|transport']);
  }
  public function index()
  {
    $stroreFile = $_SERVER['HTTP_HOST'] == 'ofis.tittravel.com' ? 'stroragetit' : 'storage'; 
    if (file_exists(public_path($stroreFile)))
        {$files =File::allFiles(public_path($stroreFile)); }
     else {$files=array();}
   return view('fichier.index',compact('files','stroreFile'));//
  }
  
  public function create()
    {
         return view('fichier.create');//
    }

  public function FichierUploadPost()

    {

        request()->validate([

            'image' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf,doc,xls|max:2048',

        ]);

          $stroreFile = $_SERVER['HTTP_HOST'] == 'ofis.tittravel.com' ? 'stroragetit' : 'storage'; 
   

        $imageName =request()->image->getClientOriginalName();

        request()->image->move(public_path($stroreFile), $imageName);



        return redirect()->route('fichier.index')->with('flash_message','You have successfully upload image.'.$imageName);

    }
    

}