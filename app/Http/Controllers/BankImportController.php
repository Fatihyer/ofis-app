<?php
namespace App\Http\Controllers;

use App\Models\Acente;
use Illuminate\Http\Request;
use App\Exports\BankImportImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Option;
use App\Models\BankImport;
use App\Models\Sirket;
use App\Services\OffsetService;
use App\Services\PennylaneApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BankImportController extends Controller
{
    public function showImportForm(PennylaneApiService $pennylane)
    {
        $finansId = Option::where('name', 'finansid')->first();

        $acenteler = Acente::whereHas('firmas', function ($query) use ($finansId) {
            $query->where('firmas.id', optional($finansId)->value);
        })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $sirkets = Sirket::orderBy('name')->pluck('name', 'id');
        $selectedSirketId = request('sirket_id', 3);
        $selectedPennylaneAccount = $this->pennylaneAccountForSirket((int) $selectedSirketId);
        $pennylaneBankAccounts = $this->pennylaneBankAccounts($pennylane, $selectedPennylaneAccount);
        $pennylaneMappings = $this->pennylaneMappings();
        $acentes = Acente::orderBy('name')->get();
        $veriler = BankImport::with('acente')
            ->whereNull('offset_id')
            ->when($selectedSirketId, fn ($q) => $q->where('sirket_id', $selectedSirketId))
            ->latest('date')
            ->paginate(50)
            ->appends(request()->query());

        return view('acentes.bankimport', compact(
            'acenteler',
            'veriler',
            'acentes',
            'sirkets',
            'selectedSirketId',
            'selectedPennylaneAccount',
            'pennylaneBankAccounts',
            'pennylaneMappings'
        ));
    }

    public function importFromPennylane(Request $request, PennylaneApiService $pennylane)
    {
        $request->validate([
            'sirket_id' => 'required|integer',
            'acente_id' => 'nullable|integer',
            'pennylane_bank_account_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $accountKey = $this->pennylaneAccountForSirket((int) $request->sirket_id);
        if (!$accountKey) {
            return back()->with('error', 'Aucun compte Pennylane associe a cette societe.');
        }

        $acenteId = (int) ($request->acente_id ?: $this->mappedAcenteId($accountKey, (int) $request->pennylane_bank_account_id));
        if (!$acenteId) {
            return back()->with('error', 'Associez ce compte Pennylane a un compte interne avant de recuperer les mouvements.');
        }

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->endOfDay();
        $bankAccountId = (int) $request->pennylane_bank_account_id;
        $cursor = null;
        $created = 0;
        $skipped = 0;
        $pages = 0;
        $maxPages = 20;

        do {
            $query = ['limit' => 100];
            if ($cursor) {
                $query['cursor'] = $cursor;
            }

            $result = $pennylane->selectAccount($accountKey)->get('/transactions', $query);
            if (!($result['ok'] ?? false)) {
                return back()->with('error', 'Erreur Pennylane: '.($result['error'] ?? 'reponse inconnue'));
            }

            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            $items = $data['items'] ?? $data['data'] ?? [];

            foreach ($items as $transaction) {
                if (!is_array($transaction)) {
                    continue;
                }

                $transactionBankId = (int) data_get($transaction, 'bank_account.id');
                if ($transactionBankId !== $bankAccountId) {
                    continue;
                }

                $date = isset($transaction['date']) ? Carbon::parse($transaction['date'])->startOfDay() : null;
                if (!$date || $date->lt($start) || $date->gt($end)) {
                    continue;
                }

                if (!empty($transaction['archived_at'])) {
                    continue;
                }

                if ($this->bankImportExists($transaction, (int) $request->sirket_id, $acenteId)) {
                    $skipped++;
                    continue;
                }

                $amount = (float) ($transaction['amount'] ?? 0);
                if ($amount == 0.0) {
                    $skipped++;
                    continue;
                }

                BankImport::create([
                    'sirket_id' => (int) $request->sirket_id,
                    'external_source' => 'pennylane',
                    'external_company' => $accountKey,
                    'external_id' => (string) ($transaction['id'] ?? ''),
                    'acente_id' => $acenteId,
                    'date' => $date->toDateString(),
                    'operation' => $transaction['label'] ?? 'Transaction Pennylane',
                    'debit' => $amount < 0 ? $amount : null,
                    'credit' => $amount > 0 ? $amount : null,
                    'currency' => $transaction['currency'] ?? 'EUR',
                    'value_date' => $date->toDateString(),
                    'interbank_label' => 'Pennylane #'.($transaction['id'] ?? '').' - compte '.data_get($transaction, 'bank_account.id'),
                    'offset_id' => null,
                ]);

                $created++;
            }

            $cursor = $data['next_cursor'] ?? null;
            $pages++;
        } while ($cursor && $pages < $maxPages);

        return redirect()
            ->route('bank.import.form', ['sirket_id' => $request->sirket_id])
            ->with('success', 'Import Pennylane termine: '.$created.' ligne(s) ajoutee(s), '.$skipped.' doublon(s)/ligne(s) ignoree(s).');
    }


    public function savePennylaneMappings(Request $request)
    {
        $request->validate([
            'account_key' => 'required|string',
            'mappings' => 'array',
            'mappings.*' => 'nullable|integer',
        ]);

        $accountKey = $request->input('account_key');
        $mappings = $this->pennylaneMappings();

        foreach ((array) $request->input('mappings', []) as $bankAccountId => $acenteId) {
            $key = $this->mappingKey($accountKey, (int) $bankAccountId);
            if ($acenteId) {
                $mappings[$key] = (int) $acenteId;
            } else {
                unset($mappings[$key]);
            }
        }

        Option::updateOrCreate(
            ['name' => 'pennylane_bank_account_mappings'],
            ['value' => json_encode($mappings, JSON_UNESCAPED_UNICODE)]
        );

        return redirect()
            ->route('bank.import.form', ['sirket_id' => $request->input('sirket_id')])
            ->with('success', 'Jumelage des comptes Pennylane enregistre.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/csv',
            'sirket_id' => 'required|integer',
            'acente_id' => 'required|integer',
        ]);

        try {
            Excel::import(new BankImportImport($request->acente_id, $request->sirket_id), $request->file('file'));
            return back()->with('success', 'Releve bancaire importe avec succes.');
        } catch (\Exception $e) {
            return back()->with('error', "Erreur pendant l'import bancaire: " . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        BankImport::destroy($id);
        return redirect()->back()->with('success', 'Ligne bancaire supprimee.');
    }

    public function addoffset(Request $request, $id)
    {
        $request->validate([
            'a_acente_id' => 'required|different:b_acente_id',
            'b_acente_id' => 'required',
            'aciklama' => 'required|string',
            'tarih' => 'required|date',
            'amount' => 'required|numeric|not_in:0',
            'kur_id' => 'required|integer',
        ]);

        $veri = BankImport::findOrFail($id);
        $sirketId = $veri->sirket_id ?: $request->input('sirket_id');

        $offset = OffsetService::createFromBank(
            $request->a_acente_id,
            $request->b_acente_id,
            $request->amount,
            $request->kur_id,
            $request->aciklama,
            $request->tarih,
            0,
            $sirketId ? (int) $sirketId : null
        );

        $veri->sirket_id = $sirketId;
        $veri->offset_id = $offset->id;
        $veri->save();

        return redirect()->back()->with('success', 'Ecriture creee. Offset ID: ' . $offset->id);
    }


    private function pennylaneMappings(): array
    {
        $option = Option::where('name', 'pennylane_bank_account_mappings')->first();
        $value = $option ? json_decode((string) $option->value, true) : [];
        return is_array($value) ? $value : [];
    }

    private function mappedAcenteId(string $accountKey, int $bankAccountId): ?int
    {
        $mappings = $this->pennylaneMappings();
        $value = $mappings[$this->mappingKey($accountKey, $bankAccountId)] ?? null;
        return $value ? (int) $value : null;
    }

    private function mappingKey(string $accountKey, int $bankAccountId): string
    {
        return $accountKey.':'.$bankAccountId;
    }

    private function pennylaneAccountForSirket(?int $sirketId): ?string
    {
        return [
            2 => 'parisvia',
            3 => 'francevia',
        ][$sirketId] ?? null;
    }

    private function pennylaneBankAccounts(PennylaneApiService $pennylane, ?string $accountKey): array
    {
        if (!$accountKey) {
            return [];
        }

        $result = $pennylane->selectAccount($accountKey)->get('/bank_accounts', ['limit' => 100]);
        if (!($result['ok'] ?? false)) {
            return [];
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        return $data['items'] ?? $data['data'] ?? [];
    }

    private function bankImportExists(array $transaction, int $sirketId, int $acenteId): bool
    {
        $amount = (float) ($transaction['amount'] ?? 0);
        $date = $transaction['date'] ?? null;
        $label = (string) ($transaction['label'] ?? '');
        $pennylaneId = $transaction['id'] ?? null;
        $bankReference = $this->extractBankReference($label);

        $query = BankImport::where('sirket_id', $sirketId);

        if ($pennylaneId && (clone $query)
            ->where('external_source', 'pennylane')
            ->where('external_id', (string) $pennylaneId)
            ->exists()) {
            return true;
        }

        if ($bankReference && $this->bankReferenceAlreadyOffset($bankReference, $date, $amount, $acenteId, $label)) {
            return true;
        }

        return $query
            ->where('acente_id', $acenteId)
            ->where(function ($query) use ($pennylaneId, $date, $label, $amount) {
                if ($pennylaneId) {
                    $query->where('interbank_label', 'like', 'Pennylane #'.$pennylaneId.'%');
                }

                $query->orWhere(function ($q) use ($date, $label, $amount) {
                    $q->whereDate('date', $date)
                        ->where('operation', $label)
                        ->when($amount < 0, fn ($qq) => $qq->where('debit', $amount))
                        ->when($amount > 0, fn ($qq) => $qq->where('credit', $amount));
                });
            })
            ->exists();
    }

    private function bankReferenceAlreadyOffset(string $reference, ?string $date, float $amount, int $acenteId, string $label = ''): bool
    {
        if (!$date || $amount == 0.0) {
            return false;
        }

        $bankImportCandidates = BankImport::query()
            ->where('acente_id', $acenteId)
            ->whereNotNull('offset_id')
            ->whereDate('date', $date)
            ->where(function ($query) use ($amount) {
                if ($amount < 0) {
                    $query->where('debit', $amount);
                } elseif ($amount > 0) {
                    $query->where('credit', $amount);
                }
            })
            ->where(function ($query) use ($reference) {
                $query->where('operation', 'like', '%'.$reference.'%')
                    ->orWhere('interbank_label', 'like', '%'.$reference.'%');
            })
            ->get(['operation', 'interbank_label']);

        foreach ($bankImportCandidates as $candidate) {
            $candidateLabel = trim(($candidate->operation ?? '').' '.($candidate->interbank_label ?? ''));
            if ($this->labelsLookSameBankMovement($label, $candidateLabel)) {
                return true;
            }
        }

        $hareketCandidates = DB::table('harekets')
            ->whereNull('deleted_at')
            ->whereNotNull('offset_id')
            ->where('acente_id', $acenteId)
            ->whereDate('tarih', $date)
            ->where('amount', number_format(abs($amount), 2, '.', ''))
            ->where('aciklama', 'like', '%'.$reference.'%')
            ->get(['aciklama']);

        foreach ($hareketCandidates as $candidate) {
            if ($this->labelsLookSameBankMovement($label, (string) $candidate->aciklama)) {
                return true;
            }
        }

        return false;
    }

    private function extractBankReference(string $label): ?string
    {
        if (preg_match('/\b(VIR\s+RECU|VIREMENT|REF(?:ERENCE)?)\s*[:\-]?\s*(\d{9,14})\b/i', $label, $matches)) {
            return $matches[2];
        }

        if (preg_match('/\b(\d{10}S)\b/i', $label, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/ref\s*:\s*([A-Z0-9]+)/i', $label, $matches)) {
            $reference = strtoupper($matches[1]);

            if (preg_match('/^(\d{9,14}|\d{10}S)$/', $reference)) {
                return $reference;
            }
        }

        if (preg_match_all('/\b\d{9,14}\b/', $label, $matches)) {
            foreach ($matches[0] as $reference) {
                if (!preg_match('/^0+$/', $reference)) {
                    return $reference;
                }
            }
        }

        return null;
    }

    private function labelsLookSameBankMovement(string $candidateLabel, string $existingLabel): bool
    {
        $candidateInvoices = $this->extractInvoiceTokens($candidateLabel);
        $existingInvoices = $this->extractInvoiceTokens($existingLabel);

        if ($candidateInvoices && $existingInvoices && empty(array_intersect($candidateInvoices, $existingInvoices))) {
            return false;
        }

        return true;
    }

    private function extractInvoiceTokens(string $label): array
    {
        preg_match_all('/\b(?:F|FAC|FACTURE)\s*20\d{2}[-\s]?\d+\b/i', $label, $matches);

        return array_values(array_unique(array_map(function ($token) {
            return preg_replace('/\s+/', '', strtoupper($token));
        }, $matches[0] ?? [])));
    }
}
