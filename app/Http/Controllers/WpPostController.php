<?php

namespace App\Http\Controllers;

use App\Models\WpCansuRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WpPostController extends Controller
{
    // isteğe bağlı koruma
    // public function __construct() { $this->middleware('auth'); }

    public function index(Request $request)
    {
        $q = WpCansuRequest::query();

        // basit filtreler
        if ($request->filled('from')) {
            $q->where('created_at', '>=', $request->date('from')->startOfDay());
        }
        if ($request->filled('to')) {
            $q->where('created_at', '<=', $request->date('to')->endOfDay());
        }
        if ($request->filled('lang')) {
            $q->where('lang', $request->get('lang'));
        }
        if ($request->filled('email_status')) {
            $q->where('email_status', $request->get('email_status'));
        }
        if ($request->filled('search')) {
            $s = '%' . trim($request->get('search')) . '%';
            $q->where(function ($w) use ($s) {
                $w->where('client_name', 'like', $s)
                  ->orWhere('client_email', 'like', $s)
                  ->orWhere('client_phone', 'like', $s)
                  ->orWhere('cansu_start', 'like', $s)
                  ->orWhere('cansu_end', 'like', $s);
            });
        }

        $data = $q->orderByDesc('created_at')->paginate(20);

        // JSON istenirse (örn. ?format=json)
        if ($request->wantsJson() || $request->get('format') === 'json') {
            return response()->json($data);
        }

        return view('cansu.index', compact('data'));
    }

    public function show($id)
    {
        $row = WpCansuRequest::findOrFail($id);
        return view('cansu.show', compact('row'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $q = WpCansuRequest::query();

        if ($request->filled('from')) {
            $q->where('created_at', '>=', $request->date('from')->startOfDay());
        }
        if ($request->filled('to')) {
            $q->where('created_at', '<=', $request->date('to')->endOfDay());
        }
        if ($request->filled('email_status')) {
            $q->where('email_status', $request->get('email_status'));
        }
        if ($request->filled('lang')) {
            $q->where('lang', $request->get('lang'));
        }

        $filename = 'cansu_requests_' . now()->format('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = [
            'id','created_at','lang','cansu_start','cansu_end','cansu_time',
            'cansu_passengers','retour','retour_datetime','car_sur_place',
            'client_name','client_phone','client_email','client_notes','email_status'
        ];

        return response()->stream(function () use ($q, $columns) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM (Excel uyumu)
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $columns);

            $q->orderByDesc('created_at')->chunk(500, function ($rows) use ($out, $columns) {
                foreach ($rows as $r) {
                    $line = [];
                    foreach ($columns as $c) {
                        $line[] = (string)($r->{$c} ?? '');
                    }
                    fputcsv($out, $line);
                }
            });

            fclose($out);
        }, 200, $headers);
    }
}
