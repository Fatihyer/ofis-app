<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

//use App\Ulke;
use Auth;
use Session;
use App\Models\Acente;
use App\Models\AcenteEmail;
use App\Models\Ulke;
use App\Models\Post;
use App\Models\Firma;
use App\Models\Invoice;
use App\Models\Transfer;
use App\Models\Hareket;
use App\Models\Offset;
use App\Models\Stock;
use App\Models\Acentemsg;
use App\Models\User;
use DB;
use File;
use Illuminate\Support\Facades\Schema;
///excel
use App\Exports\AcentesExport;
use App\Exports\AcenteDetailledExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\HermesApiService;
use App\Services\PennylaneApiService;

class AcenteController extends Controller
{
    protected $start_date;
    protected $end_date;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   public function __construct() {
    $this->middleware('auth');
    $this->middleware('permission:acentes.view')->only(['index', 'show', 'balanceprovider', 'excellist', 'detailledExport', 'getHermesDrivingHours']);
    $this->middleware('permission:acentes.create')->only(['create', 'store']);
    $this->middleware('permission:acentes.update')->only(['edit', 'update', 'guncel']);
    $this->middleware('permission:acentes.delete')->only(['destroy']);


      $request = app('request');
      $this->start_date = date('Y-m-d', strtotime('-1 years'));
      $this->end_date = date('Y-m-d', strtotime('+2 years'));

      try {
          if ($request->filled('start_date') && $request->filled('end_date')) {
              $this->start_date = Carbon::parse($request->input('start_date'))->toDateString();
              $this->end_date = Carbon::parse($request->input('end_date'))->toDateString();
          } elseif ($request->filled('daterange')) {
              $tarihayir = preg_split('/\s*-\s*/', $request->input('daterange'));
              if (count($tarihayir) >= 2) {
                  $this->start_date = Carbon::createFromFormat('d/m/Y', trim($tarihayir[0]))->toDateString();
                  $this->end_date = Carbon::createFromFormat('d/m/Y', trim($tarihayir[1]))->toDateString();
              }
          }
      } catch (\Throwable $e) {
          $this->start_date = date('Y-m-d', strtotime('-1 years'));
          $this->end_date = date('Y-m-d', strtotime('+2 years'));
      }
   
    }

    public function index()
    {
      $firma=Firma::pluck('name','id')->toArray(); 
         if (app('request')->input('firma')>0) 
         {
         $acentes = Acente::sortable()->search(app('request')->input('s'))->firma(app('request')->input('firma'))->orderby('name')->paginate(50);
         }
        else
        {
         $acentes = Acente::sortable()->search(app('request')->input('s'))->orderby('name')->paginate(50);
         }
          return view('acentes.index', compact('acentes','firma'));
      
    }
  
    public function show($id)
    {

    
        $acente = Acente::with(['emails', 'firmas', 'ulke', 'responsable'])->findOrFail($id); //Find post of id = $id
        $start_date = $this->start_date;
        $end_date = $this->end_date;
        $startAt = Carbon::parse($start_date)->startOfDay();
        $endAt = Carbon::parse($end_date)->endOfDay();
       
        $storeFile = request()->getHost() == 'ofis.tittravel.com' ? 'imagestit' : 'images'; 
        if (file_exists(public_path($storeFile.'/acente/'.$id))) {
            $files = File::allFiles(public_path($storeFile.'/acente/'.$id));
        } else {
            $files = [];
        }
    
        $posts = Post::sortable()
            ->with([
                'status.color',
                'transfer.servicetype',
                'transfer.vehicule',
                'transfer.driver',
                'transfer.harekets.kur',
                'invoice.groupinvoice',
            ])
            ->where('acente_id', $id)
            ->whereBetween('start_date', [$startAt, $endAt])
            ->orderby('start_date', 'desc')->paginate(200); //show only 5 items at a time in descending order
        $invoices = Invoice::where('acente_id', $id)
            ->whereBetween('tarih', [$startAt, $endAt])
            ->orderby('id', 'desc')->paginate(200); //show only 5 items at a time in descending order
    
        $transfers = Transfer::where(function ($query) use ($id) {
                $query->where('driver_id', $id);
                if (Schema::hasColumn('transfers', 'second_driver_id')) {
                    $query->orWhere('second_driver_id', $id);
                }
            })
            ->whereBetween('start_date', [$startAt, $endAt])
            ->orderby('start_date', 'desc')->paginate(200);
    
        $harekets = Hareket::with(['post', 'payment', 'kur', 'hareketable', 'files'])
            ->where('acente_id', $id)
            ->whereBetween('tarih', [$startAt, $endAt])
            ->orderby('tarih', 'asc')->paginate(200);
        $harekets->getCollection()->loadMorph('hareketable', [
            Offset::class => ['alacakli', 'borclu'],
        ]);

        $balanceSummary = Hareket::with('kur')
            ->selectRaw("
                kur_id,
                SUM(CASE WHEN ab = 1 THEN amount ELSE 0 END) as debit_total,
                SUM(CASE WHEN ab = 2 THEN amount ELSE 0 END) as credit_total,
                SUM(CASE WHEN ab = 2 THEN amount WHEN ab = 1 THEN -amount ELSE 0 END) as net_total
            ")
            ->where('acente_id', $id)
            ->whereBetween('tarih', [$startAt, $endAt])
            ->groupBy('kur_id')
            ->get();

        $balanceTypeSummary = Hareket::selectRaw("
                COALESCE(hareketable_type, 'Autre') as type_label,
                COUNT(*) as movement_count,
                SUM(CASE WHEN ab = 1 THEN amount ELSE 0 END) as debit_total,
                SUM(CASE WHEN ab = 2 THEN amount ELSE 0 END) as credit_total
            ")
            ->where('acente_id', $id)
            ->whereBetween('tarih', [$startAt, $endAt])
            ->groupBy('hareketable_type')
            ->get();
    
        $offsets = Offset::where(function ($query) use ($id) {
                $query->where('a_acente_id', '=', $id)
                    ->orWhere('b_acente_id', '=', $id);
            })->whereBetween('tarih', [$startAt, $endAt])
            ->orderby('tarih', 'asc')->paginate(200);
    
        $stocks = Stock::where(function ($query) use ($id) {
                $query->where('a_acente_id', '=', $id)
                    ->orWhere('b_acente_id', '=', $id)
                    ->orWhere('urun_id', '=', $id);
            })->whereBetween('tarih', [$startAt, $endAt])
            ->orderby('tarih', 'asc')->paginate(200);
    
        $sum = Hareket::selectRaw("
                kur_id,
                SUM(
                    CASE 
                        WHEN ab = 2 THEN amount   -- ALACAK (+)
                        WHEN ab = 1 THEN -amount  -- BORÇ (-)
                        ELSE 0
                    END
                ) as tpl
            ")
            ->where('acente_id', $id)
            ->groupBy('kur_id')
            ->get();
        $pennylaneInvoiceMatches = [];
        if (request('src') === 'invoice') {
            $pennylaneInvoiceMatches = $this->pennylaneInvoiceMatches($invoices->getCollection(), $start_date, $end_date);
        }

        $bakiye = $harekets->sum('amount');;
        $cash = 0; 
        $casts = 0; 
    
        return view('acentes.show', compact('acente', 'posts', 'invoices', 'transfers', 'harekets', 'offsets', 'stocks', 'sum', 'balanceSummary', 'balanceTypeSummary', 'bakiye', 'cash', 'casts', 'storeFile', 'files', 'start_date', 'end_date', 'pennylaneInvoiceMatches'));
    }

    private function pennylaneInvoiceMatches($invoices, string $startDate, string $endDate): array
    {
        $matches = [];
        $invoicesBySirket = $invoices
            ->filter(fn ($invoice) => trim((string) $invoice->resmi) !== '')
            ->groupBy(fn ($invoice) => (int) ($invoice->sirket_id ?: 2));

        if ($invoicesBySirket->isEmpty()) {
            return $matches;
        }

        $pennylane = app(PennylaneApiService::class);

        foreach ($invoicesBySirket as $sirketId => $companyInvoices) {
            $accountKey = $this->pennylaneAccountForSirket((int) $sirketId);
            $pennylaneInvoices = $this->fetchPennylaneCustomerInvoices($pennylane, $accountKey, $startDate, $endDate);
            $pennylaneByNumber = [];

            foreach ($pennylaneInvoices as $pennyInvoice) {
                foreach ($this->invoiceNumberKeys($pennyInvoice['invoice_number'] ?? '', $pennyInvoice['date'] ?? null) as $numberKey) {
                    if ($numberKey !== '' && !isset($pennylaneByNumber[$numberKey])) {
                        $pennylaneByNumber[$numberKey] = $pennyInvoice;
                    }
                }
            }

            foreach ($companyInvoices as $invoice) {
                $penny = null;
                foreach ($this->invoiceNumberKeys($invoice->resmi, substr((string) $invoice->tarih, 0, 10)) as $numberKey) {
                    if (isset($pennylaneByNumber[$numberKey])) {
                        $penny = $pennylaneByNumber[$numberKey];
                        break;
                    }
                }

                if (!$penny) {
                    $matches[$invoice->id] = [
                        'status' => 'missing',
                        'account' => $accountKey,
                        'pennylane' => null,
                        'amount_diff' => null,
                        'date_diff' => false,
                    ];
                    continue;
                }

                $amountDiff = round((float) $invoice->amount - (float) ($penny['amount'] ?? 0), 2);
                $dateDiff = substr((string) $invoice->tarih, 0, 10) !== ($penny['date'] ?? null);
                $status = abs($amountDiff) > 0.01 ? 'amount_mismatch' : ($dateDiff ? 'date_mismatch' : 'ok');

                $matches[$invoice->id] = [
                    'status' => $status,
                    'account' => $accountKey,
                    'pennylane' => $penny,
                    'amount_diff' => $amountDiff,
                    'date_diff' => $dateDiff,
                ];
            }
        }

        return $matches;
    }

    private function fetchPennylaneCustomerInvoices(PennylaneApiService $pennylane, string $accountKey, string $startDate, string $endDate): array
    {
        $cursor = null;
        $items = [];
        $pages = 0;

        do {
            $query = ['limit' => 100];
            if ($cursor) {
                $query['cursor'] = $cursor;
            }

            $result = $pennylane->selectAccount($accountKey)->get('/customer_invoices', $query);
            if (!($result['ok'] ?? false)) {
                break;
            }

            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            foreach (($data['items'] ?? $data['data'] ?? []) as $invoice) {
                $date = $invoice['date'] ?? null;
                if (!$date || $date < $startDate || $date > $endDate) {
                    continue;
                }
                if (!empty($invoice['archived_at'])) {
                    continue;
                }
                $items[] = $invoice;
            }

            $cursor = $data['next_cursor'] ?? null;
            $pages++;
        } while ($cursor && $pages < 30);

        return $items;
    }

    private function pennylaneAccountForSirket(int $sirketId): string
    {
        return [
            2 => 'parisvia',
            3 => 'francevia',
        ][$sirketId] ?? 'parisvia';
    }

    private function normalizeInvoiceNumber($value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', '', $value);
        $value = rtrim($value, '/');
        return strtoupper($value);
    }

    private function invoiceNumberKeys($value, ?string $date = null): array
    {
        $normalized = $this->normalizeInvoiceNumber($value);
        if ($normalized === '') {
            return [];
        }

        $keys = [];
        $add = function ($candidate) use (&$keys) {
            $candidate = $this->normalizeInvoiceNumber($candidate);
            if ($candidate === '') {
                return;
            }
            $keys[] = $candidate;
            if (str_starts_with($candidate, 'F')) {
                $keys[] = substr($candidate, 1);
            } else {
                $keys[] = 'F' . $candidate;
            }
        };
        $add($normalized);

        $year = $date ? substr($date, 0, 4) : null;
        $month = $date ? substr($date, 5, 2) : null;

        $addSequentialVariants = function ($candidate) use ($add) {
            if (preg_match('/^(F?\d{4}-\d{2}-)(\d{1,4})$/', $candidate, $matches)) {
                $add($matches[1] . str_pad($matches[2], 3, '0', STR_PAD_LEFT));
                $add($matches[1] . ltrim($matches[2], '0'));
            }
        };
        $addSequentialVariants($normalized);

        $fixedDuplicatedYearDigit = preg_replace('/^F?(20\d)2(\d-\d{2}-\d+)$/', 'F$1$2', $normalized);
        if ($fixedDuplicatedYearDigit && $fixedDuplicatedYearDigit !== $normalized) {
            $add($fixedDuplicatedYearDigit);
            $addSequentialVariants($fixedDuplicatedYearDigit);
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{1,4})$/', $normalized, $matches)) {
            $add('F' . $matches[1]);
            $addSequentialVariants('F' . $matches[1]);
        }

        if ($year && $month && preg_match('/^(F?\d{4})-(\d{1,4})$/', $normalized, $matches)) {
            $prefix = ltrim($matches[1], 'F');
            if ($prefix === $year) {
                $withMonth = 'F' . $year . '-' . $month . '-' . $matches[2];
                $add($withMonth);
                $addSequentialVariants($withMonth);
            }
        }

        foreach (array_values($keys) as $candidate) {
            $withoutSeparators = preg_replace('/[^A-Z0-9]/', '', $candidate);
            if ($withoutSeparators && $withoutSeparators !== $candidate) {
                $keys[] = $withoutSeparators;
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }
    

  
   public function create()
    {
       $firma=Firma::pluck('name','id');
       $ulke =Ulke::pluck('country_name', 'id');
       return view ('acentes.create', compact('ulke','firma'));
      
    }
    public function store(Request $request)
    {
      $validated = $request->validate([
        'firma' => 'required|array',
        'firma.*' => 'integer',
        'name' => 'required|string|max:255',
        'tittle' => 'required|string|max:255',
        'emails' => 'nullable|array',
        'emails.*' => 'nullable|email|max:255',
        'web' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'ulke_id' => 'nullable|integer',
        'tel' => 'nullable|string|max:255',
        'address' => 'nullable|string',
        'postal' => 'nullable|string|max:255',
        'vd' => 'nullable|string|max:255',
        'vdno' => 'nullable|string|max:255',
        'color' => 'nullable|string|max:255',
        'suivi' => 'nullable|boolean',
        'hermescle'=> 'nullable|string',
    ]);

    $emails = $this->normalizedAcenteEmails($request);

    if ($response = $this->acenteEmailConflictResponse($emails)) {
        return $response;
    }
    
    $acente = DB::transaction(function () use ($request, $emails) {
       // Acente (Provider) oluştur
    $acente = new Acente();
    $acente->name = $request->name;
    $acente->tittle = $request->tittle;
   // $acente->web = $request->web;
    $acente->city = $request->city;
    $acente->ulke_id = $request->ulke_id;
    $acente->tel = $request->tel;
    $acente->address = $request->address;
    $acente->postal = $request->postal;
    $acente->vd = $request->vd;
    $acente->vdno = $request->vdno;
    $acente->color = $request->color;
    $acente->suivi = $request->suivi;
    $acente->hermescle=$request->hermescle;
    $acente->save();
    
        // Firma ilişkisini bağlayalım
        $acente->firmas()->sync($request->input('firma'));

        $this->syncAcenteEmails($acente, $emails);

        return $acente;
    });
    
        return redirect()->route('acentes.index')
            ->with('flash_message', 'Acente '. $acente->name.' başarıyla oluşturuldu.');
    }
    
   public function edit($id)
    {
         $firma=Firma::pluck('name','id');
          $acente = Acente::with('emails')->findOrFail($id);
          $ulke =Ulke::pluck('country_name', 'id');
           $users = User::orderBy('name')->pluck('name', 'id')->toArray();
         return view('acentes.edit', compact('ulke','acente','firma','users'));
     
     
    }
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|max:100',
            'firma' => 'required|array',
            'firma.*' => 'integer',
            'emails' => 'nullable|array',
            'emails.*' => 'nullable|email|max:255',
        ]);

        $acente = Acente::findOrFail($id);

        $emails = $this->normalizedAcenteEmails($request);

        if ($response = $this->acenteEmailConflictResponse($emails, $acente->id)) {
            return $response;
        }
    
        DB::transaction(function () use ($request, $acente, $emails) {
            $acente->fill($this->acentePayload($request));
            $acente->save();
            $acente->firmas()->sync($request->input('firma'));
            $this->syncAcenteEmails($acente, $emails);
        });
    
        return redirect()->route('acentes.show', $id)
            ->with('flash_message', 'Acente ' . $acente->name . ' düzenlendi.');
    }

    private function acentePayload(Request $request): array
    {
        return $request->only([
            'name',
            'tittle',
            'city',
            'ulke_id',
            'tel',
            'address',
            'postal',
            'vd',
            'vdno',
            'color',
            'suivi',
            'whatsapp',
            'airportshuttle',
            'responsable_id',
            'hermescle',
        ]);
    }

    private function normalizedAcenteEmails(Request $request): array
    {
        return collect((array) $request->input('emails', []))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function acenteEmailConflictResponse(array $emails, ?int $ignoreAcenteId = null)
    {
        if ($emails === []) {
            return null;
        }

        $query = AcenteEmail::with('acente:id,name')
            ->whereIn(DB::raw('LOWER(TRIM(email))'), $emails);

        if ($ignoreAcenteId) {
            $query->where('acente_id', '!=', $ignoreAcenteId);
        }

        $conflicts = $query->get();
        if ($conflicts->isEmpty()) {
            return null;
        }

        $message = 'Bu e-mail adresi başka acentede kayıtlı: ' . $conflicts
            ->map(fn ($row) => $row->email . ' (' . optional($row->acente)->name . ')')
            ->unique()
            ->implode(', ');

        return redirect()->back()->withErrors(['emails' => $message])->withInput();
    }

    private function syncAcenteEmails(Acente $acente, array $emails): void
    {
        $acente->emails()->delete();

        foreach ($emails as $email) {
            $acente->emails()->create(['email' => $email]);
        }
    }
    

  public function destroy(Request $request,$id)
    {
        
       $siacente=Acente::findOrFail($id);
   if  ($siacente->post()->exists())
      {
      return Redirect::back()->with('flash_message', 'Acente have File');   
      }
    elseif ($siacente->hareket()->exists()) 
      {
      return Redirect::back()->with('flash_message', 'Acente have Hareket');   
      }
    elseif ($siacente->invoice()->exists()) 
      {
      return Redirect::back()->with('flash_message', 'Acente have İnvoice');   
      }
    else
    {$siacente->delete();
    
    return Redirect::back()->with('flash_message', 'delete');   
    }
    
    }
 
  public function excellist($id,$start_date=null,$end_date=null)
  {
  //echo  $this->start_date;
  
//  DB::enableQueryLog();
  return Excel::download(new AcentesExport($id,$this->start_date,$this->end_date), 'harekets.xlsx');
 
  }

  public function detailledExport($id)
  {
      $acente = Acente::findOrFail($id);
      $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $acente->name) ?: $acente->name);
      $safeName = trim($safeName, '-') ?: 'client';
      $fileName = 'dossiers-detailles-' . $safeName . '-' . $this->start_date . '-' . $this->end_date . '.xlsx';

      return Excel::download(new AcenteDetailledExport($id, $this->start_date, $this->end_date), $fileName);
  }

  public function balanceprovider()
{
    if (
        Auth::user()->hasAnyRole(['Superadmin', 'Admin', 'ofis']) ||
        Auth::user()->hasAnyPermission(['balances.view', 'ofis'])
    ) {

        $query = Hareket::query();

        $selectedFirma = request('firma');

        // Firma filtresi varsa
        if (request()->filled('firma') && request('firma') !== '0') {
            $firmaId = request('firma');

            $query->whereHas('acente.firmas', function ($q) use ($firmaId) {
                $q->where('firmas.id', $firmaId);
            });
        }

        // 🔥 GERÇEK BAKİYE HESABI
        $sums = $query
            ->selectRaw("
                acente_id,
                SUM(
                    CASE
                        WHEN ab = 2 THEN amount
                        WHEN ab = 1 THEN -amount
                        ELSE 0
                    END
                ) as sum
            ")
            ->groupBy('acente_id')
            ->pluck('sum', 'acente_id')
            ->toArray();
        $sums = array_filter($sums, function ($value) {
            return (float)$value != 0.0;
        });
        uasort($sums, fn ($a, $b) => abs((float) $b) <=> abs((float) $a));

        $total = array_sum($sums);
        $totalCredit = array_sum(array_filter($sums, fn ($value) => (float) $value >= 0));
        $totalDebit = array_sum(array_filter($sums, fn ($value) => (float) $value < 0));

        $acente = Acente::withTrashed()->pluck('name', 'id')->toArray();
        $msg    = Acentemsg::pluck('message', 'acente_id')->toArray();
        $firma  = Firma::pluck('name', 'id')->toArray();
        

        return view('acentes.balance', compact(
            'sums',
            'total',
            'totalCredit',
            'totalDebit',
            'acente',
            'firma',
            'selectedFirma',
            'msg'
        ));
    }
}

    

public function getHermesDrivingHours($id, HermesApiService $hermes)
{
  

$acente = Acente::findOrFail($id);
    $hermescle = $acente->hermescle;
    if (!$hermescle) {
        return response()->json(['error' => 'Hermes kodu atanmadı.'], 400);
    }

    $start = $this->start_date ? Carbon::parse($this->start_date) : now()->startOfMonth();
    $end   = $this->end_date ? Carbon::parse($this->end_date) : now()->endOfMonth();
    try {
        $units = $hermes->get('/units/active');

        $relatedUnits = collect($units)->filter(function ($unit) use ($hermescle) {
    return isset($unit['agency']) && (
        $unit['agency']['code'] === $hermescle || $unit['agency']['uid'] === $hermescle
    );
});

        $totalSeconds = 0;

        foreach ($relatedUnits as $unit) {
            $trackInfos = $hermes->get("/units/{$unit['uid']}/track-info");

            $isDriving = false;
            $previousDate = null;

            foreach ($trackInfos as $info) {
                $status = strtolower($info['status']['label']);
                $date = Carbon::parse($info['date']);

                if ($date->lt($start) || $date->gt($end)) continue;

                if ($status === 'conduite') {
                    $isDriving = true;
                    $previousDate = $date;
                } elseif ($isDriving) {
                    $isDriving = false;
                    if ($previousDate) {
                        $duration = $date->diffInSeconds($previousDate);
                        $totalSeconds += $duration;
                    }
                }
            }
        }
   


        return response()->json([
            'acente' => $acente->name,
            'total_hours' => round($totalSeconds / 3600, 2),
            'period' => $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y'),
        ]);
    } catch (\Throwable $e) {
    
        
    \Log::error('Hermes API hatası');
    \Log::error($e);
    return response()->json(['error' => 'Sunucu hatası'], 500);
    }
   
}

public function getHermesDrivingHoursDetail($id, HermesApiService $hermes)
{
    $acente = Acente::findOrFail($id);

    if (!$acente->hermescle) {
        abort(404, 'Aucun identifiant Hermes pour ce chauffeur');
    }

    // Hermes track-info (SON 7 GÜN)
    $trackInfo = $hermes->get("/resources/{$acente->hermescle}/track-info");

    if (!is_array($trackInfo)) {
        $trackInfo = [];
    }

    $days = [];
    $totalSeconds = 0;

    foreach ($trackInfo as $day) {
        if (!isset($day['day'])) {
            continue;
        }

        $duration = (int) ($day['duration'] ?? 0); // saniye
        $totalSeconds += $duration;

        $days[] = [
            'day'        => $day['day'],
            'duration_s' => $duration,
            'duration_h' => round($duration / 3600, 2),
            'distance'   => $day['distance'] ?? null,
            'max_speed'  => $day['maxSpeed'] ?? null,
        ];
    }

    return view('hermes.hermes_driving_hours', [
        'acente'        => $acente,
        'days'          => $days,
        'total_hours'   => round($totalSeconds / 3600, 2),
        'retentionDays' => 7,
    ]);
}
}
