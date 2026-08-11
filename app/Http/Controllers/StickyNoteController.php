<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StickyNote;
use Illuminate\Support\Facades\Auth;

class StickyNoteController extends Controller
{
    public function index()
    {
        if (Auth::check() && Auth::user()->hasRole('Driver')) {
            return redirect('/ev'); // veya route ismi varsa: return redirect()->route('home');
        }
        return view('sticky_notes.index');
    }

    public function fetch()
    {
        return response()->json(StickyNote::orderBy('order')->get());
    }

    public function store(Request $request)
    {
        $note = StickyNote::create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'color' => $request->color,
           'order' => StickyNote::max('order') + 1

        ]);

        return response()->json($note);
    }

    public function updateOrder(Request $request)
    {
        foreach ($request->order as $index => $id) {
            StickyNote::where('id', $id)->update(['order' => $index]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function destroy($id)
    {
        StickyNote::destroy($id);
        return response()->json(['status' => 'deleted']);
    }
}
