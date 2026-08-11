<?php

namespace App\Http\Controllers;

use App\Models\Acente;
use App\Models\Option;
use App\Services\PennylaneApiService;
use Illuminate\Http\Request;

class PennylaneController extends Controller
{
    private array $resources = [
        'me' => ['label' => 'Compte Pennylane', 'endpoint' => '/me', 'list' => false],
        'customers' => ['label' => 'Clients', 'endpoint' => '/customers', 'list' => true],
        'products' => ['label' => 'Produits', 'endpoint' => '/products', 'list' => true],
        'customer_invoices' => ['label' => 'Factures clients', 'endpoint' => '/customer_invoices', 'list' => true],
        'supplier_invoices' => ['label' => 'Factures fournisseurs', 'endpoint' => '/supplier_invoices', 'list' => true],
        'suppliers' => ['label' => 'Fournisseurs', 'endpoint' => '/suppliers', 'list' => true],
        'transactions' => ['label' => 'Transactions', 'endpoint' => '/transactions', 'list' => true],
        'bank_accounts' => ['label' => 'Comptes bancaires', 'endpoint' => '/bank_accounts', 'list' => true],
        'categories' => ['label' => 'Catégories', 'endpoint' => '/categories', 'list' => true],
        'accounting_accounts' => ['label' => 'Comptes comptables', 'endpoint' => '/accounting_accounts', 'list' => true],
    ];

    public function index(Request $request, PennylaneApiService $pennylane)
    {
        $accountKey = $request->query('account', 'francevia');
        $pennylane->selectAccount($accountKey);

        $resourceKey = $request->query('resource', 'me');
        $resource = $this->resources[$resourceKey] ?? $this->resources['me'];
        $query = [];

        if ($resource['list']) {
            $query['limit'] = min(max((int) $request->query('limit', 25), 1), 100);
            $query['cursor'] = $request->query('cursor');
        }

        $result = $pennylane->get($resource['endpoint'], $query);
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $items = $this->itemsFrom($data, $resource['list']);
        $tierMappings = [];
        $acentes = collect();
        $tierSuggestions = [];
        $mappingType = null;
        $mappingLabel = null;

        if (in_array($resourceKey, ['customers', 'suppliers'], true)) {
            $mappingType = $resourceKey === 'suppliers' ? 'supplier' : 'customer';
            $mappingLabel = $mappingType === 'supplier' ? 'fournisseur' : 'client';
            $tierMappings = $this->tierMappings($mappingType);
            $acenteRows = Acente::orderBy('name')->get(['id', 'name', 'vd', 'vdno', 'email', 'tel']);
            $acentes = $acenteRows->pluck('name', 'id');
            $tierSuggestions = $this->tierSuggestions($items, $acenteRows);
        }

        return view('pennylane.index', [
            'resources' => $this->resources,
            'resourceKey' => $resourceKey,
            'resource' => $resource,
            'result' => $result,
            'data' => $data,
            'items' => $items,
            'configured' => $pennylane->configured(),
            'limit' => $query['limit'] ?? null,
            'accounts' => $pennylane->accounts(),
            'accountKey' => $pennylane->accountKey(),
            'accountLabel' => $pennylane->accountLabel(),
            'customerMappings' => $tierMappings,
            'tierMappings' => $tierMappings,
            'acentes' => $acentes,
            'customerSuggestions' => $tierSuggestions,
            'tierSuggestions' => $tierSuggestions,
            'mappingType' => $mappingType,
            'mappingLabel' => $mappingLabel,
        ]);
    }

    public function saveCustomerMappings(Request $request)
    {
        $validated = $request->validate([
            'account' => ['required', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'mappings' => ['nullable', 'array'],
            'mappings.*' => ['nullable', 'integer', 'exists:acentes,id'],
        ]);

        $accountKey = $validated['account'];
        $stored = $this->tierMappings('customer');

        foreach (($validated['mappings'] ?? []) as $pennylaneId => $acenteId) {
            $key = $this->mappingKey($accountKey, $pennylaneId);

            if ($acenteId) {
                $stored[$key] = (int) $acenteId;
                continue;
            }

            unset($stored[$key]);
        }

        $this->saveTierMappings('customer', $stored);

        return redirect()
            ->route('pennylane.index', [
                'account' => $accountKey,
                'resource' => 'customers',
                'limit' => $validated['limit'] ?? 25,
            ])
            ->with('success', 'Jumelage des clients Pennylane enregistre.');
    }

    public function saveSupplierMappings(Request $request)
    {
        $validated = $request->validate([
            'account' => ['required', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'mappings' => ['nullable', 'array'],
            'mappings.*' => ['nullable', 'integer', 'exists:acentes,id'],
        ]);

        $accountKey = $validated['account'];
        $stored = $this->tierMappings('supplier');

        foreach (($validated['mappings'] ?? []) as $pennylaneId => $acenteId) {
            $key = $this->mappingKey($accountKey, $pennylaneId);

            if ($acenteId) {
                $stored[$key] = (int) $acenteId;
                continue;
            }

            unset($stored[$key]);
        }

        $this->saveTierMappings('supplier', $stored);

        return redirect()
            ->route('pennylane.index', [
                'account' => $accountKey,
                'resource' => 'suppliers',
                'limit' => $validated['limit'] ?? 25,
            ])
            ->with('success', 'Jumelage des fournisseurs Pennylane enregistre.');
    }

    public function importSupplier(Request $request, PennylaneApiService $pennylane)
    {
        $validated = $request->validate([
            'account' => ['required', 'string'],
            'supplier_id' => ['required'],
        ]);

        $accountKey = $validated['account'];
        $supplierId = (string) $validated['supplier_id'];
        $mappingKey = $this->mappingKey($accountKey, $supplierId);
        $stored = $this->tierMappings('supplier');

        if (!empty($stored[$mappingKey])) {
            $acente = Acente::find($stored[$mappingKey]);
            if ($acente) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Fournisseur déjà jumelé.',
                    'acente_id' => $acente->id,
                    'acente_name' => $acente->name,
                    'url' => route('acentes.show', $acente->id),
                ]);
            }
        }

        $result = $pennylane->selectAccount($accountKey)->get('/suppliers/' . $supplierId);
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $result['error'] ?? 'Lecture Pennylane impossible.',
            ], 422);
        }

        $supplier = is_array($result['data'] ?? null) ? $result['data'] : [];
        if (isset($supplier['data']) && is_array($supplier['data'])) {
            $supplier = $supplier['data'];
        }

        $name = trim((string) ($supplier['name'] ?? ''));
        if ($name === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Nom fournisseur Pennylane manquant.',
            ], 422);
        }

        $vat = trim((string) ($supplier['vat_number'] ?? ''));
        $regNo = trim((string) ($supplier['reg_no'] ?? ''));
        $address = is_array($supplier['postal_address'] ?? null) ? $supplier['postal_address'] : [];
        $email = $this->firstEmail($supplier['emails'] ?? []);

        $acente = null;
        if ($regNo !== '') {
            $acente = Acente::where('vdno', $regNo)->first();
        }
        if (!$acente && $vat !== '') {
            $acente = Acente::where('vd', $vat)->first();
        }
        if (!$acente) {
            $acente = Acente::where('name', $name)->first();
        }

        if (!$acente) {
            $acente = new Acente();
            $acente->name = $name;
            $acente->tittle = 'Fournisseur';
            $acente->address = trim((string) ($address['address'] ?? ''));
            $acente->postal = trim((string) ($address['postal_code'] ?? ''));
            $acente->city = trim((string) ($address['city'] ?? ''));
            $acente->email = $email;
            $acente->vd = $vat ?: null;
            $acente->vdno = $regNo ?: null;
            $acente->suivi = 0;
            $acente->save();
        }

        $stored[$mappingKey] = (int) $acente->id;
        $this->saveTierMappings('supplier', $stored);

        return response()->json([
            'ok' => true,
            'message' => 'Fournisseur ajouté et jumelé.',
            'acente_id' => $acente->id,
            'acente_name' => $acente->name,
            'url' => route('acentes.show', $acente->id),
        ]);
    }

    private function itemsFrom(array $data, bool $list): array
    {
        if (!$list) {
            return [$data];
        }

        if (isset($data['items']) && is_array($data['items'])) {
            return $data['items'];
        }

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        if (array_is_list($data)) {
            return $data;
        }

        return [];
    }

    private function customerMappings(): array
    {
        return $this->tierMappings('customer');
    }

    private function tierMappings(string $type): array
    {
        $option = Option::where('name', 'pennylane_' . $type . '_mappings')->first();
        $value = $option ? json_decode((string) $option->value, true) : [];

        return is_array($value) ? $value : [];
    }

    private function saveTierMappings(string $type, array $stored): void
    {
        Option::updateOrCreate(
            ['name' => 'pennylane_' . $type . '_mappings'],
            ['value' => json_encode($stored, JSON_UNESCAPED_UNICODE)]
        );
    }

    private function mappingKey(string $accountKey, string|int $externalId): string
    {
        return $accountKey . ':' . $externalId;
    }

    private function firstEmail($emails): ?string
    {
        if (!is_array($emails)) {
            return null;
        }

        foreach ($emails as $email) {
            if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
            if (is_array($email)) {
                foreach (['email', 'value', 'address'] as $key) {
                    if (!empty($email[$key]) && filter_var($email[$key], FILTER_VALIDATE_EMAIL)) {
                        return $email[$key];
                    }
                }
            }
        }

        return null;
    }

    private function customerSuggestions(array $items, $acentes): array
    {
        return $this->tierSuggestions($items, $acentes);
    }

    private function tierSuggestions(array $items, $acentes): array
    {
        $normalizedAcentes = [];

        foreach ($acentes as $key => $acente) {
            $id = is_object($acente) ? $acente->id : $key;
            $fields = is_object($acente)
                ? [$acente->name, $acente->vd, $acente->vdno, $acente->email, $acente->tel]
                : [$acente];

            foreach ($fields as $field) {
                $normalized = $this->normalizeName($field);

                if ($normalized !== '') {
                    $normalizedAcentes[$normalized] = (int) $id;
                }
            }
        }

        $suggestions = [];

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $parts = [
                $item['company_name'] ?? null,
                $item['name'] ?? null,
                $item['vat_number'] ?? null,
                $item['reg_no'] ?? null,
                $item['email'] ?? null,
                trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')),
            ];

            foreach ($parts as $part) {
                $normalized = $this->normalizeName($part);

                if ($normalized !== '' && isset($normalizedAcentes[$normalized])) {
                    $suggestions[$item['id']] = $normalizedAcentes[$normalized];
                    break;
                }
            }
        }

        return $suggestions;
    }

    private function normalizeName($value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '', $value);

        return $value ?: '';
    }
}
