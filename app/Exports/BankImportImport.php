<?php

namespace App\Exports;

use App\Models\BankImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BankImportImport implements ToCollection
{
    protected $acenteId;
    protected $sirketId;

    public function __construct($acenteId, $sirketId = null)
    {
        $this->acenteId = $acenteId;
        $this->sirketId = $sirketId;
    }

    public function collection(Collection $rows)
    {
        $currentDate = null;
        $currentOperation = '';
        $currentDebit = null;
        $currentCredit = null;
        $currentCurrency = '';
        $currentValueDate = null;
        $currentInterbankLabel = '';

        $rowCount = 0;
        $started = false;

        foreach ($rows as $row) {
            $rowCount++;

            if ($rowCount <= 5) {
                continue;
            }

            if (!$started) {
                $started = true;
                continue;
            }

            if (!empty($row['0']) && $this->isDate($row['0'])) {
                if ($currentDate) {
                    $this->createRow($currentDate, $currentOperation, $currentDebit, $currentCredit, $currentCurrency, $currentValueDate, $currentInterbankLabel);
                }

                $currentDate = Carbon::parse(str_replace('/', '-', $row['0']))->format('Y-m-d');
                $currentOperation = $row['1'] ?? '';
                $currentDebit = isset($row['2']) ? $this->normalizeDecimal($row['2']) : null;
                $currentCredit = isset($row['3']) ? $this->normalizeDecimal($row['3']) : null;
                $currentCurrency = $row['4'] ?? '';
                $currentValueDate = isset($row['5']) ? Carbon::parse(str_replace('/', '-', $row['5']))->format('Y-m-d') : null;
                $currentInterbankLabel = $row['6'] ?? '';
            } else {
                $currentOperation .= ' ' . ($row['1'] ?? '');
            }
        }

        if ($currentDate) {
            $this->createRow($currentDate, $currentOperation, $currentDebit, $currentCredit, $currentCurrency, $currentValueDate, $currentInterbankLabel);
        }
    }

    private function createRow($date, $operation, $debit, $credit, $currency, $valueDate, $interbankLabel): void
    {
        $operation = $this->normalizeText($operation);
        $interbankLabel = $this->normalizeText($interbankLabel);
        $debit = $this->normalizeAmount($debit);
        $credit = $this->normalizeAmount($credit);

        if ($this->alreadyImported($date, $operation, $debit, $credit, $interbankLabel)) {
            return;
        }

        BankImport::create([
            'sirket_id' => $this->sirketId,
            'acente_id' => $this->acenteId,
            'date' => $date,
            'operation' => $operation,
            'debit' => $debit,
            'credit' => $credit,
            'currency' => $currency,
            'value_date' => $valueDate,
            'interbank_label' => $interbankLabel,
        ]);
    }

    private function alreadyImported($date, $operation, $debit, $credit, $interbankLabel): bool
    {
        $exists = BankImport::query()
            ->where('sirket_id', $this->sirketId)
            ->where('acente_id', $this->acenteId)
            ->whereDate('date', $date)
            ->where(function ($query) use ($operation, $interbankLabel, $debit, $credit) {
                $query->where(function ($q) use ($operation, $debit, $credit) {
                    $q->where('operation', $operation)
                        ->where(function ($amountQuery) use ($debit, $credit) {
                            if ($debit !== null) {
                                $amountQuery->where('debit', $debit);
                            } else {
                                $amountQuery->whereNull('debit');
                            }

                            if ($credit !== null) {
                                $amountQuery->where('credit', $credit);
                            } else {
                                $amountQuery->whereNull('credit');
                            }
                        });
                });

                if ($interbankLabel !== '') {
                    $query->orWhere('interbank_label', $interbankLabel);
                }
            })
            ->exists();

        if ($exists) {
            return true;
        }

        $reference = $this->extractBankReference($operation.' '.$interbankLabel);
        $amount = $credit !== null ? (float) $credit : (float) $debit;

        if (!$reference || $amount == 0.0) {
            return false;
        }

        $candidateLabel = trim($operation.' '.$interbankLabel);
        $candidates = DB::table('harekets')
            ->whereNull('deleted_at')
            ->whereNotNull('offset_id')
            ->where('acente_id', $this->acenteId)
            ->whereDate('tarih', $date)
            ->where('amount', number_format(abs($amount), 2, '.', ''))
            ->where('aciklama', 'like', '%'.$reference.'%')
            ->get(['aciklama']);

        foreach ($candidates as $candidate) {
            if ($this->labelsLookSameBankMovement($candidateLabel, (string) $candidate->aciklama)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText($value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value));
    }

    private function normalizeAmount($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function isDate($value): bool
    {
        try {
            Carbon::parse(str_replace('/', '-', $value));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function normalizeDecimal($value)
    {
        if (empty($value)) {
            return null;
        }

        $value = str_replace(' ', '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? $value : null;
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
