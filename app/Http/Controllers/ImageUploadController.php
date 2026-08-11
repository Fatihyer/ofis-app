<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;



class ImageUploadController extends Controller

{

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function imageUploadPost()

    {

        request()->validate([

            'image' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf|max:2048',

        ]);


       $stroreFile = $_SERVER['HTTP_HOST'] == 'ofis.tittravel.com' ? 'imagestit' : 'images'; 
  
        $imageName =request()->image->getClientOriginalName().'.'.request()->image->getClientOriginalExtension();

        request()->image->move(public_path($stroreFile.'/'.request()->input('id')), $imageName);



        return redirect()->back()->with('flash_message','You have successfully upload image.'.$imageName);

    }

    public function imageUploadAcente()

    {

        request()->validate([

            'image' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf|max:2048',

        ]);


       $stroreFile = $_SERVER['HTTP_HOST'] == 'ofis.tittravel.com' ? 'imagestit' : 'images'; 
  
        $imageName =request()->image->getClientOriginalName().'.'.request()->image->getClientOriginalExtension();

        request()->image->move(public_path($stroreFile.'/acente/'.request()->input('id')), $imageName);



        return redirect()->back()->with('flash_message','You have successfully upload image.'.$imageName);

    }


}