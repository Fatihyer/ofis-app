<?php

namespace App\Http\Controllers;

use App\Models\Groupinvoice;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Post;
use App\Models\Sirket;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use App\Models\Option;
use App\Services\PennylaneApiService;
use PDF;

class GroupinvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('permission:invoices.view')->only(['index', 'show', 'grppdf']);
        $this->middleware('permission:invoices.create')->only(['create', 'store']);
        $this->middleware('permission:invoices.update')->only(['edit', 'update']);
        $this->middleware('permission:invoices.delete')->only(['destroy']);
        $this->middleware('role:Superadmin')->only(['sendPennylaneDraft']);
    }
    public function index(Request $request)
    {
        $groupinvoices = Groupinvoice::with(['sirket', 'invoices.acente', 'invoices.kur'])
            ->when($request->filled('sirket_id'), fn ($q) => $q->where('sirket_id', $request->input('sirket_id')))
            ->orderby('id', 'desc')->paginate(100)->appends($request->query());
        $sirkets = Sirket::orderBy('name')->pluck('name', 'id');
        $selectedSirketId = $request->input('sirket_id'); //show only 5 items at a time in descending order
         return view('invoice.groupindex', compact('groupinvoices', 'sirkets', 'selectedSirketId'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
      $acente = $request->input('acente');
      $selectedSirketId = $request->input('sirket_id');
      $sirkets = Sirket::orderBy('name')->pluck('name','id');

      $invoices = Invoice::with(['sirket', 'kur'])
          ->where('acente_id', $acente)
          ->when($selectedSirketId, fn ($q) => $q->where('sirket_id', $selectedSirketId))
          ->orderByDesc('id')
          ->get();

      if (!$selectedSirketId) {
          $companyIds = $invoices->pluck('sirket_id')->filter()->unique()->values();
          if ($companyIds->count() === 1) {
              $selectedSirketId = $companyIds->first();
          }
      }

       return view('invoice.newgroup',compact('invoices','sirkets','selectedSirketId','acente'));
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
            'tarih'=>'required',
            'sirket_id'=>'required|integer|exists:sirkets,id',
            'invoicelist' => 'required|array|min:1',       
            ]);
           
       $this->assertInvoicesBelongToSirket($request->input('invoicelist'), (int) $request->input('sirket_id'));
       $groupinvoice=Groupinvoice::create($request->all());
       $groupinvoice->invoices()->sync($request->input('invoicelist')); 
      return redirect()->route('groupinvoices.index')
            ->with('flash_message', 'groupinvoice,
              created');
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\groupinvoice  $groupinvoice
     * @return \Illuminate\Http\Response
     */
    public function show(groupinvoice $groupinvoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\groupinvoice  $groupinvoice
     * @return \Illuminate\Http\Response
     */
    public function edit(groupinvoice $groupinvoice)
    {
      $groupinvoices=Groupinvoice::findOrFail($groupinvoice->id);
      $sirkets = Sirket::orderBy('name')->pluck('name','id');
      $acenteId = optional($groupinvoices->invoices->first())->acente_id;
      $invoices = Invoice::where('acente_id', $acenteId)
          ->where('sirket_id', $groupinvoices->sirket_id)
          ->orderByDesc('id')
          ->pluck('id','id');
       return view('invoice.groupedit',compact('invoices','groupinvoices','sirkets'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\groupinvoice  $groupinvoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, groupinvoice $groupinvoice)
    {
       if ($groupinvoice->pennylane_customer_invoice_id) {
           $request->validate([
               'resmi' => 'nullable|string|max:255',
           ]);

           $groupinvoice->resmi = trim((string) $request->input('resmi'));
           $groupinvoice->save();
           $this->markGroupedPostsAsInvoiced($groupinvoice);

           return Redirect::back()->with('flash_message', 'N° facture Pennylane mis à jour.');
       }

         $this->validate($request, [
            'tarih'=>'required',
            'invoicelist' => 'required|array|min:1',       
            ]);
       $this->assertInvoicesBelongToSirket($request->input('invoicelist'), (int) $request->input('sirket_id'));
       $groupinvoices=Groupinvoice::findOrFail($groupinvoice->id);    
       $groupinvoices->update($request->all());
       $groupinvoices->invoices()->sync($request->input('invoicelist')); 
      return redirect()->route('groupinvoices.index')
            ->with('flash_message', 'groupinvoice,
              created');
      
     
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\groupinvoice  $groupinvoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(groupinvoice $groupinvoice)
    {
         $silinvoice=Groupinvoice::find($groupinvoice->id);
         if ($silinvoice && $silinvoice->pennylane_customer_invoice_id) {
             return Redirect::back()->withErrors('Cette facture groupée est déjà envoyée à Pennylane. Suppression bloquée.');
         }
         $silinvoice->invoices()->sync([]);
         $silinvoice->delete();  
     
    return Redirect::back()->with('flash_message', 'You cant delete');   
    }
    private function assertInvoicesBelongToSirket(array $invoiceIds, int $sirketId): void
    {
        $invalidCount = Invoice::whereIn('id', $invoiceIds)
            ->where('sirket_id', '!=', $sirketId)
            ->count();

        if ($invalidCount > 0) {
            abort(422, 'Les factures sélectionnées doivent appartenir à la même société.');
        }
    }

    public function sendPennylaneDraft(Groupinvoice $groupinvoice, PennylaneApiService $pennylane)
    {
        $groupinvoice->load(['sirket', 'invoices.acente', 'invoices.kur', 'invoices.invoicedetail.kdv']);

        if ($groupinvoice->pennylane_customer_invoice_id) {
            return Redirect::back()->with('success', 'Cette facture groupée est déjà envoyée dans Pennylane brouillon #' . $groupinvoice->pennylane_customer_invoice_id . '.');
        }

        $invoices = $groupinvoice->invoices;
        if ($invoices->isEmpty()) {
            return Redirect::back()->withErrors('Aucune facture sélectionnée dans ce groupe.');
        }

        $syncedInvoices = $invoices->filter(fn ($invoice) => !empty($invoice->pennylane_customer_invoice_id));
        if ($syncedInvoices->isNotEmpty()) {
            return Redirect::back()->withErrors('Certaines factures sont déjà envoyées à Pennylane: #' . $syncedInvoices->pluck('id')->implode(', #'));
        }

        if ($invoices->filter(fn ($invoice) => !empty($invoice->avoir))->isNotEmpty()) {
            return Redirect::back()->withErrors('Les avoirs doivent être envoyés avec un flux Pennylane séparé.');
        }

        $sirketIds = $invoices->pluck('sirket_id')->filter()->unique()->values();
        $acenteIds = $invoices->pluck('acente_id')->filter()->unique()->values();
        $kurIds = $invoices->pluck('kur_id')->filter()->unique()->values();

        if ($sirketIds->count() !== 1 || ($groupinvoice->sirket_id && (int) $sirketIds->first() !== (int) $groupinvoice->sirket_id)) {
            return Redirect::back()->withErrors('Toutes les factures du groupe doivent appartenir à la même société.');
        }
        if ($acenteIds->count() !== 1) {
            return Redirect::back()->withErrors('Toutes les factures du groupe doivent appartenir au même client.');
        }
        if ($kurIds->count() !== 1) {
            return Redirect::back()->withErrors('Toutes les factures du groupe doivent avoir la même devise.');
        }

        $accountKey = $this->pennylaneAccountForSirket((int) ($groupinvoice->sirket_id ?: $sirketIds->first()));
        $customerId = $this->pennylaneCustomerIdForAcente((int) $acenteIds->first(), $accountKey);
        if (!$customerId) {
            return redirect()
                ->route('pennylane.index', ['account' => $accountKey, 'resource' => 'customers', 'limit' => 100])
                ->withErrors('Client non jumelé avec Pennylane. Associez le client puis relancez l’envoi.');
        }

        $productId = $this->pennylaneProductId($pennylane, $accountKey, 'Transferts Multiples');
        if (!$productId) {
            return Redirect::back()->withErrors('Produit Pennylane "Transferts Multiples" introuvable pour ce compte.');
        }

        $payload = $this->pennylaneGroupDraftPayload($groupinvoice, $customerId, $productId);
        if (empty($payload['invoice_lines'])) {
            return Redirect::back()->withErrors('Aucune ligne de facture positive à envoyer à Pennylane.');
        }

        $result = $pennylane->selectAccount($accountKey)->post('/customer_invoices', $payload);
        if (!($result['ok'] ?? false)) {
            return Redirect::back()->withErrors('Erreur Pennylane: ' . ($result['error'] ?? 'réponse inconnue'));
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $created = is_array($data['data'] ?? null) ? $data['data'] : $data;
        $pennylaneId = $created['id'] ?? $created['customer_invoice_id'] ?? null;

        if (!$pennylaneId) {
            return Redirect::back()->withErrors('Pennylane a répondu sans identifiant de facture.');
        }

        $status = (string) ($created['status'] ?? 'draft');
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        DB::transaction(function () use ($groupinvoice, $invoices, $pennylaneId, $status, $encodedPayload) {
            $groupinvoice->pennylane_customer_invoice_id = (string) $pennylaneId;
            $groupinvoice->pennylane_customer_invoice_status = $status;
            $groupinvoice->pennylane_synced_at = now();
            $groupinvoice->pennylane_payload = $encodedPayload;
            $groupinvoice->save();

            foreach ($invoices as $invoice) {
                $invoice->pennylane_customer_invoice_id = (string) $pennylaneId;
                $invoice->pennylane_customer_invoice_status = $status;
                $invoice->pennylane_synced_at = now();
                $invoice->pennylane_payload = $encodedPayload;
                $invoice->save();
            }

            $postIds = $invoices->pluck('post_id')->filter()->unique()->values();
            if ($postIds->isNotEmpty()) {
                Post::whereIn('id', $postIds)
                    ->where(function ($query) {
                        $query->whereNull('billing_status')
                            ->orWhere('billing_status', '!=', 'do_not_invoice');
                    })
                    ->update(['billing_status' => 'invoiced']);
            }
        });

        return Redirect::back()->with('success', 'Brouillon Pennylane créé pour la facture groupée: #' . $pennylaneId . '.');
    }

    private function markGroupedPostsAsInvoiced(Groupinvoice $groupinvoice): void
    {
        $postIds = $groupinvoice->invoices()
            ->whereNotNull('post_id')
            ->pluck('post_id')
            ->filter()
            ->unique()
            ->values();

        if ($postIds->isEmpty()) {
            return;
        }

        Post::whereIn('id', $postIds)
            ->where(function ($query) {
                $query->whereNull('billing_status')
                    ->orWhere('billing_status', '!=', 'do_not_invoice');
            })
            ->update(['billing_status' => 'invoiced']);
    }

    private function pennylaneAccountForSirket(int $sirketId): string
    {
        return [
            2 => 'parisvia',
            3 => 'francevia',
        ][$sirketId] ?? 'parisvia';
    }

    private function pennylaneCustomerIdForAcente(int $acenteId, string $accountKey): ?string
    {
        $mappings = $this->pennylaneTierMappings('customer');
        foreach ($mappings as $mappingKey => $mappedAcenteId) {
            if ((int) $mappedAcenteId !== $acenteId) {
                continue;
            }

            [$mappedAccount, $customerId] = array_pad(explode(':', (string) $mappingKey, 2), 2, null);
            if ($mappedAccount === $accountKey && $customerId) {
                return (string) $customerId;
            }
        }

        return null;
    }

    private function pennylaneTierMappings(string $type): array
    {
        $option = Option::where('name', 'pennylane_' . $type . '_mappings')->first();
        $value = $option ? json_decode((string) $option->value, true) : [];

        return is_array($value) ? $value : [];
    }

    private function pennylaneProductId(PennylaneApiService $pennylane, string $accountKey, string $label): ?int
    {
        $result = $pennylane->selectAccount($accountKey)->get('/products', ['limit' => 100]);
        if (!($result['ok'] ?? false)) {
            return null;
        }

        $items = $result['data']['items'] ?? $result['data']['data'] ?? [];
        foreach ($items as $product) {
            if (!empty($product['archived_at'])) {
                continue;
            }
            if (mb_strtolower(trim((string) ($product['label'] ?? ''))) === mb_strtolower($label)) {
                return (int) $product['id'];
            }
        }

        return null;
    }

    private function pennylaneGroupDraftPayload(Groupinvoice $groupinvoice, string $customerId, int $productId): array
    {
        $date = substr((string) $groupinvoice->tarih, 0, 10) ?: now()->toDateString();
        $deadline = \Carbon\Carbon::parse($date)->addDays(30)->toDateString();

        $lines = $groupinvoice->invoices
            ->sortBy('post_id')
            ->flatMap(function ($invoice) use ($productId) {
                return $invoice->invoicedetail->map(function ($line) use ($productId, $invoice) {
                    $amount = round((float) $line->amount, 2);
                    if ($amount <= 0) {
                        return null;
                    }

                    $description = trim((string) $line->comments);
                    $dossier = $invoice->post_id ? 'Dossier #' . $invoice->post_id . ' - ' : '';

                    return [
                        'product_id' => $productId,
                        'label' => 'Transferts Multiples',
                        'description' => $dossier . ($description ?: 'Facture #' . $invoice->id . ' ligne #' . $line->id),
                        'quantity' => 1,
                        'unit' => 'piece',
                        'raw_currency_unit_price' => number_format($amount, 2, '.', ''),
                        'vat_rate' => 'FR_100',
                    ];
                });
            })
            ->filter()
            ->values()
            ->all();

        return [
            'customer_id' => (int) $customerId,
            'date' => $date,
            'deadline' => $deadline,
            'draft' => true,
            'external_reference' => 'Laravel groupinvoice #' . $groupinvoice->id . ($groupinvoice->resmi ? ' / ' . $groupinvoice->resmi : ''),
            'invoice_lines' => $lines,
        ];
    }

   public function grppdf($id,$type=false)

      {

    $grpinvoices=Groupinvoice::findOrFail($id);
   
     $companydetail=Option::whereIn('name',['company-name','logo','company-info','company-info-2'])->pluck('value','name');
        if ($type=='pdf')
        {
       $pdf = PDF::loadView('invoice.grpdf',compact('grpinvoices','companydetail'));
       //->setPaper('a4', 'portrait');
       return $pdf->download('grpdfview.pdf');
         }
        elseif ($type=='xls')
        {
          return Excel::download(new InvoicesExport($grpinvoices,$companydetail), 'grpinvoicess.xlsx');    
        }
          
  else  { return view ('invoice.grpdf', compact('grpinvoices','companydetail')); }


  }
  
}
