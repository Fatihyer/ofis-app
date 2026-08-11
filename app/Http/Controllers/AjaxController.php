<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Post;
use App\Models\Acente;
use App\Models\Transfer;
use App\Models\Firma;
use App\Models\Account;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AjaxController extends Controller
{ 
    
    
    public function selectAjaxFirma(Request $request)
    {
        // Ensure the request method is POST
        if (!$request->isMethod('post')) {
            return response()->json(['error' => 'Invalid request method'], 405);
        }

        try {
            if ($request->ajax()) {
                $firma = Firma::findOrFail($request->id);

                $firmas = [];
                foreach ($firma->acentes as $acente) {
                    $firmas[$acente->id] = $acente->name;
                }

                return response()->json(['options' => $firmas]);
            }
        } catch (\Exception $e) {
            Log::error('Error in selectAjaxFirma: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }

        return response()->json(['error' => 'This is not an AJAX request'], 400);
    }
  public function selectaccount(Request $request)
  {
     $accounts=Account::pluck('name','id')->toArray(); 
     return response()->json(['options'=>$accounts]);
  }
  
  public function getEvents(Request $request)
{
    $query = Transfer::with(['servicetype', 'driver', 'post.acente'])
        ->orderBy('start_date');

    if ($request->filled('start') && $request->filled('end')) {
        $query->where('start_date', '<', $request->input('end'))
            ->where(function ($q) use ($request) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', $request->input('start'));
            });
    }

    return response()->json($query->get()->map(function ($value) {
        $service = optional($value->servicetype)->name ?: 'Service';
        $driver = optional($value->driver)->name ?: 'Sans chauffeur';
        $agency = optional(optional($value->post)->acente)->name;

        return [
            'title' => '#'.$value->id.' · '.$service.' · '.$driver,
            'start' => $value->start_date,
            'end' => $value->end_date ?: $value->start_date,
            'color' => optional($value->driver)->color ?: '#2563eb',
            'url' => url('transfers/' . $value->id),
            'description' => trim(($agency ? $agency.' · ' : '').($value->from ?: '').' → '.($value->target ?: '')),
        ];
    })->values());
}

public function getFiles(Request $request)
{
    $query = Post::with(['acente'])
        ->orderBy('start_date');

    if ($request->filled('start') && $request->filled('end')) {
        $query->where('start_date', '<', $request->input('end'))
            ->where(function ($q) use ($request) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', $request->input('start'));
            });
    }

    return response()->json($query->get()->map(function ($value) {
        $agency = optional($value->acente)->name ?: 'Agence';
        return [
            'title' => '#'.$value->id.' · '.$agency.' · '.$value->title,
            'start' => $value->start_date,
            'end' => $value->end_date ?: $value->start_date,
            'color' => optional($value->acente)->color ?: '#2563eb',
            'url' => url('posts/' . $value->id),
            'description' => trim(($value->pax ? $value->pax.' pax · ' : '').($value->from ?: '').' → '.($value->target ?: '')),
        ];
    })->values());
}



  
}