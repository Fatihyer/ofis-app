<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kilometer;

class KilometerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'kilometer' => 'required|integer|min:0',
        ]);

        Kilometer::create($request->all());

        return back()->with('success', 'Kilometer added successfully.');
    }
    public function destroy($id)
{
    $kilometer = Kilometer::findOrFail($id);
    $kilometer->delete();

    return redirect()->back()->with('success', 'Kilometer entry deleted successfully.');
}
}
