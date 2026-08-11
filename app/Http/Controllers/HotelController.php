<?php
namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Acente;
use Illuminate\Http\Request;
use App\Models\Status;
use App\Models\Servicetype;
use App\Models\Option;

class HotelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Your index logic here
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $hotelids = Option::where('name', 'hotelid')->first();
        
        if ($hotelids) {
            $hoteloption = $hotelids->value;

            $otels = Acente::whereHas('firmas', function ($query) use ($hoteloption) {
                $query->where('id', $hoteloption);
            })->orderBy('name')->pluck('name', 'id');

            $status = Status::pluck('name', 'id');
            $servicetype = Servicetype::where('firma_id', $hoteloption)->pluck('name', 'id');

            return view('hotel.create', compact('otels', 'status', 'servicetype'));
        } else {
            return redirect()->back()->with('flash_message', 'PLEASE ADD ON OPTION hotelid');
        }
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
            'acente_id' => 'required',
            'from' => 'required|date',
            'to' => 'required|date|after:from',
            'post_id' => 'required',
        ]);

        $hotel = Hotel::create($request->all());

        $hotel->harekets()->create([
            'aciklama' => 'hotel',
            'tarih' => $request->from,
            'post_id' => $request->post_id,
            'amount' => '0',
            'ab' => '2',
            'kur_id' => '1',
            'acente_id' => $hotel->acente_id,
        ]);

    //   \LogActivity::addToLog('Hotel reservation added, res no: ' . $hotel->id, $hotel->post_id);

        return redirect()->route('posts.show', $hotel->post_id)
            ->with('flash_message', 'Hotel reservation added, res no: ' . $hotel->id . ' added');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function show(Hotel $hotel)
    {
        // Your show logic here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $hotel = Hotel::findOrFail($id);

        $hotelids = Option::where('name', 'hotelid')->first();
        
        if ($hotelids) {
            $hoteloption = $hotelids->value;

            $otels = Acente::whereHas('firmas', function ($query) use ($hoteloption) {
                $query->where('id', $hoteloption);
                
            })
            ->orderBy('name')
            ->pluck('name', 'id');

            $status = Status::pluck('name', 'id');
            $servicetype = Servicetype::where('firma_id', $hoteloption)->orderBy('name')->pluck('name', 'id');

            return view('hotel.edit', compact('otels', 'status', 'hotel', 'servicetype'));
        } else {
            return redirect()->back()->with('flash_message', 'PLEASE ADD ON OPTION hotelid');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'acente_id' => 'required',
            'from' => 'required|date',
            'to' => 'required|date|after:from',
            'post_id' => 'required',
        ]);

        $hotel = Hotel::findOrFail($id);
        $hotel->update($request->all());

        $hotel->harekets()->update([
            'tarih' => $request->from,
            'acente_id' => $request->acente_id,
        ]);

       // \LogActivity::addToLog('Hotel reservation updated, res no: ' . $hotel->id, $hotel->post_id);

        return redirect()->route('posts.show', $hotel->post_id)
            ->with('flash_message', 'Hotel reservation updated, res no: ' . $hotel->id . ' updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Hotel  $hotel
     * @return \Illuminate\Http\Response
     */
    public function destroy(Hotel $hotel)
    {
        $hareket = Hotel::find($hotel->id);

        if ($hareket->harekets()->first()->amount) {
            return redirect()->back()->with('flash_message', 'THE HOTEL HAS AMOUNT, PLEASE DELETE BEFORE DELETE');
        } else {
            $hareket->harekets()->delete();
            \LogActivity::addToLog('Hotel deleted: (' . $hareket->start_date . ')', $hareket->post_id, 'Hotel ID: ' . $hareket->id);
            $hareket->delete();

            return redirect()->back()->with('flash_message', 'THE HOTEL WAS DELETED');
        }
    }
}
