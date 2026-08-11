<?php
namespace App\Http\Controllers;

use App\Models\OfficeHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeHourController extends Controller
{
    public function index()
    {
        $officeHours = OfficeHour::where('user_id', Auth::id())->orderBy('date', 'desc')->get();

        // Kullanıcının bugünkü mesai durumunu kontrol et
        $today = now()->toDateString();
        $currentOfficeHour = OfficeHour::where('user_id', Auth::id())->where('date', $today)->first();

        return view('office_hours.index', compact('officeHours', 'currentOfficeHour'));
    }

    public function startWork(Request $request)
    {
        $today = now()->toDateString();

        // Mesaiyi başlat
        $existingRecord = OfficeHour::where('user_id', Auth::id())->where('date', $today)->first();
        if ($existingRecord && $existingRecord->start_time) {
            return redirect()->back()->with('error', 'Mesai zaten başlatılmış.');
        }

        OfficeHour::updateOrCreate(
            ['user_id' => Auth::id(), 'date' => $today],
            ['start_time' => now()->toTimeString()]
        );

        return redirect()->route('office_hours.index')->with('success', 'Mesai başlatıldı.');
    }

    public function endWork(Request $request)
    {
        $today = now()->toDateString();

        // Mesaiyi bitir
        $officeHour = OfficeHour::where('user_id', Auth::id())->where('date', $today)->first();

        if (!$officeHour || !$officeHour->start_time) {
            return redirect()->back()->with('error', 'Mesai başlatılmadan bitirilemez.');
        }

        if ($officeHour->end_time) {
            return redirect()->back()->with('error', 'Mesai zaten bitirilmiş.');
        }

        $officeHour->update(['end_time' => now()->toTimeString()]);

        return redirect()->route('office_hours.index')->with('success', 'Mesai bitirildi.');
    }
    public function edit($id)
{
    // İlgili mesai kaydını bulun
    $officeHour = OfficeHour::findOrFail($id);

    // Görünümü döndür ve mesai kaydını gönder
    return view('office_hours.edit', compact('officeHour'));
}
    public function update(Request $request, $id)
    {
        // Validasyon
        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
        ]);

        $officeHour = OfficeHour::findOrFail($id);

        // Mesai saatlerini güncelle
        $officeHour->update([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->route('office_hours.index')->with('success', 'Mesai saatleri güncellendi.');
    }
    public function destroy($id)
{
    // Mesai kaydını bul ve sil
    $officeHour = OfficeHour::findOrFail($id);
    $officeHour->delete();

    return redirect()->route('office_hours.index')->with('success', 'Mesai kaydı başarıyla silindi.');
}

}
