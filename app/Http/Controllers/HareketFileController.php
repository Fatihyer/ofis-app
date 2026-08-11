<?php

namespace App\Http\Controllers;

use App\Models\Hareket;
use App\Models\HareketFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HareketFileController extends Controller
{
    public function store(Request $request, $hareketId)
    {
        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $hareket = Hareket::findOrFail($hareketId);

        $file = $request->file('file');
        $path = $file->store("hareket-files/{$hareket->id}", 'public');

        HareketFile::create([
            'hareket_id'    => $hareket->id,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return back()->with('success', 'Fatura dosyası harekete eklendi.');
    }

    public function destroy($id)
    {
        $row = HareketFile::findOrFail($id);

        if ($row->path && Storage::disk('public')->exists($row->path)) {
            Storage::disk('public')->delete($row->path);
        }

        $row->delete();

        return back()->with('success', 'Dosya silindi.');
    }
}
