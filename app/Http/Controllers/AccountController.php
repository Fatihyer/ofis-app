<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Kur;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('permission:accounts.manage');
      }
    public function index()
    {
       $kurs=Kur::pluck('name', 'id'); 
       $accounts = Account::orderby('id', 'desc')->paginate(100); //show only 5 items at a time in descending order
         return view('accounts.index', compact('accounts','kurs'));//
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
          
       $account= Account::create($request->all());
      return redirect()->route('accounts.index')
            ->with('flash_message', 'Accounts name ,
             '. $account->name.' created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function show(Account $account)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function edit(Account $account)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Account $account)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        //
    }
    public function guncel(Request $request)
    {
       $this->validate($request, [
            'name'=>'required|max:100',
                        
            ]);  
      $id = $request->input('id');
      $account=Account::findOrFail($id);
      $account->name = $request->input('name');
      $account->iban = $request->input('iban');
      $account->swift = $request->input('swift');
      $account->hesapno = $request->input('hesapno');
      $account->kur_id = $request->input('kur_id');
      $account->other = $request->input('other');
      $account->save();

        return redirect()->route('accounts.index', 
             $account->id)->with('flash_message', 
            'Exchange name, '.  $account->name.' updated');  
    }
}
