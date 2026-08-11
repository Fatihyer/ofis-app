<?php

namespace App\Http\Controllers;

use App\Helpers\LogActivity;
use App\Helpers\HareketHelper;
use App\Models\Acente;
use App\Models\Client;
use App\Models\Post;
use App\Models\Servicetype;
use App\Models\Trajet;
use App\Models\Transfer;
use App\Models\Vehicule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiBotController extends Controller
{
    public function index()
    {
        $this->authorizeAiBot();

        return view('ai_bot.index');
    }

    public function quickTransfer()
    {
        $this->authorizeAiBot();

        $acentes = Acente::orderBy('name')->pluck('name', 'id');

        return view('ai_bot.quick_transfer', compact('acentes'));
    }

    public function ask(Request $request): JsonResponse
    {
        $this->authorizeAiBot();

        $data = $request->validate([
            'question' => 'required|string|min:2|max:1000',
        ]);

        $question = trim($data['question']);
        $context = $this->buildTransferContext($question);

        if (!config('services.openai.key')) {
            return response()->json([
                'success' => false,
                'answer' => 'La configuration OpenAI est manquante.',
            ], 422);
        }

        $response = Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->timeout(45)
            ->post(rtrim(config('services.openai.base_url'), '/') . '/chat/completions', [
                'model' => trim(config('services.openai.model')),
                'temperature' => 0,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "Tu es l'assistant operationnel interne de ParisVia. Reponds en francais, clairement et brievement. Utilise uniquement le CONTEXTE fourni. Si l'information n'est pas dans le contexte, dis que tu ne la vois pas dans les donnees chargees. Quand tu listes des transferts, indique #id, heure, depart, arrivee, chauffeur, vehicule, statut. N'invente jamais.",
                    ],
                    [
                        'role' => 'user',
                        'content' => "QUESTION:\n{$question}\n\nCONTEXTE:\n" . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'answer' => 'La reponse AI a echoue. Veuillez verifier OpenAI.',
                'debug' => data_get($response->json(), 'error.message'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'answer' => trim((string) data_get($response->json(), 'choices.0.message.content')),
            'context_summary' => [
                'period' => $context['period_label'],
                'count' => count($context['transfers']),
            ],
        ]);
    }

    public function quickTransferXml(Request $request): JsonResponse
    {
        $this->authorizeAiBot();

        $data = $request->validate([
            'message' => 'required|string|min:5|max:12000',
        ]);

        if (!config('services.openai.key')) {
            return response()->json([
                'success' => false,
                'message' => 'La configuration OpenAI est manquante.',
            ], 422);
        }

        $today = now('Europe/Paris')->toDateString();
        $model = $this->quickTransferXmlModel();

        $response = Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->timeout(45)
            ->post(rtrim(config('services.openai.base_url'), '/') . '/chat/completions', [
                'model' => trim(config('services.openai.model')),
                'temperature' => 0,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "Tu transformes des bons de commande, emails ou messages WhatsApp en XML strict pour creer un dossier et un ou plusieurs transferts dans ParisVia. Reponds uniquement avec XML valide, sans markdown. Date du jour: {$today}. N'invente pas les informations manquantes: laisse les balises vides et ajoute une note dans <points_a_verifier>. Si le texte dit autocar, coach, sprinter ou van comme passager, considere que c'est le vehicule demande, pas le nom du passager. Utilise les dates ISO YYYY-MM-DD et heures HH:MM. Modele obligatoire:\n{$model}",
                    ],
                    [
                        'role' => 'user',
                        'content' => $data['message'],
                    ],
                ],
            ]);

        if (!$response->successful()) {
            \Log::error('Quick transfer XML OpenAI failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'La conversion XML a échoué.',
                'debug' => data_get($response->json(), 'error.message'),
            ], 422);
        }

        $xml = trim((string) data_get($response->json(), 'choices.0.message.content'));
        $xml = preg_replace('/^```(?:xml)?\s*/i', '', $xml);
        $xml = preg_replace('/\s*```$/', '', $xml);

        if (preg_match('/<demande[\s\S]*<\/demande>/', $xml, $matches)) {
            $xml = $matches[0];
        }

        return response()->json([
            'success' => true,
            'xml' => $xml,
            'preview' => $this->previewQuickTransferXml($xml),
        ]);
    }

    public function quickTransferStore(Request $request): JsonResponse
    {
        $this->authorizeAiBot();

        $data = $request->validate([
            'xml' => 'required|string|min:10|max:30000',
            'acente_id' => 'nullable|exists:acentes,id',
        ]);

        $parsed = $this->parseQuickTransferXml($data['xml']);

        if (!empty($data['acente_id'])) {
            $parsed['acente_id'] = (int) $data['acente_id'];
            $parsed['acente_name'] = Acente::whereKey($data['acente_id'])->value('name');
        }

        $this->validateQuickTransferPayload($parsed);

        $post = DB::transaction(function () use ($parsed, $data) {
            $firstStart = collect($parsed['operations'])->min('start_at');
            $lastEnd = collect($parsed['operations'])->max('end_at');

            $post = Post::create([
                'title' => mb_substr($parsed['title'], 0, 100),
                'body' => $this->limitTextForColumn($this->buildQuickTransferPostBody($parsed, $data['xml']), 900),
                'start_date' => $firstStart,
                'end_date' => $lastEnd,
                'acente_id' => $parsed['acente_id'],
                'pax' => $parsed['total_pax'] ?: collect($parsed['operations'])->sum('pax') ?: 1,
                'child' => 0,
                'status_id' => 3,
                'user_id' => auth()->id(),
                'billing_status' => 'to_invoice',
                'payment_status' => 'not_received',
            ]);

            $this->createClientFromQuickTransfer($post, $parsed);

            foreach ($parsed['operations'] as $index => $operation) {
                $transfer = new Transfer();
                $transfer->post_id = $post->id;
                $transfer->ofis_start = Carbon::parse($operation['start_at'])->subHour()->toDateTimeString();
                $transfer->start_date = $operation['start_at'];
                $transfer->end_date = $operation['end_at'];
                $transfer->servicetype_id = $operation['service_type_id'];
                $transfer->from = $operation['pickup_location'];
                $transfer->target = $operation['dropoff_location'];
                $transfer->pax = $operation['pax'];
                $transfer->comments = trim(implode("\n", array_filter([
                    'Créé depuis AI création rapide.',
                    $operation['route_description'],
                    $operation['notes'],
                    $parsed['points_a_verifier'] ? 'À vérifier: ' . $parsed['points_a_verifier'] : null,
                ])));
                $transfer->vehicule_id = $operation['vehicule_id'];
                $transfer->driver_id = $this->defaultDriverId();
                $transfer->km = 0;
                $transfer->mission = 1;
                $transfer->accueil = 0;
                $transfer->status_id = 2;
                $transfer->save();

                $this->createQuickTransferTrajets($transfer, $operation);
                HareketHelper::create($transfer, [
                    'aciklama' => 'Transfer créé depuis AI création rapide.',
                    'tarih' => $operation['start_at'],
                    'post_id' => $post->id,
                    'amount' => 0,
                    'ab' => 2,
                    'kur_id' => 1,
                    'acente_id' => $transfer->driver_id,
                ]);
            }

            LogActivity::addToLog('Dossier créé par AI création rapide.', $post->id, 'AI Quick Transfer');

            return $post;
        });

        return response()->json([
            'success' => true,
            'message' => 'Le dossier #' . $post->id . ' a été créé.',
            'post_id' => $post->id,
            'post_url' => route('posts.show', $post->id),
        ]);
    }

    private function authorizeAiBot(): void
    {
        abort_unless(auth()->user()?->hasRole('Superadmin'), 403, 'Acces reserve aux super administrateurs.');
    }

    private function quickTransferXmlModel(): string
    {
        return <<<'XML'
<demande>
  <source>mail|whatsapp|texte</source>
  <agence>Nom de l'agence si connu</agence>
  <reference>Reference client ou bon de commande</reference>
  <date_demande>YYYY-MM-DD</date_demande>
  <customer_name>Nom du passager ou client final</customer_name>
  <customer_phone></customer_phone>
  <customer_email></customer_email>
  <total_pax>1</total_pax>
  <vehicle_type>COACH|SPRINTER|VAN|Classe V|Autre</vehicle_type>
  <operations>
    <operation>
      <date_operation>YYYY-MM-DD</date_operation>
      <start_time>HH:MM</start_time>
      <end_time>HH:MM</end_time>
      <service_type>Transfert|Mise à dispo|Navette|Autre</service_type>
      <pax>1</pax>
      <pickup_location>Adresse de depart</pickup_location>
      <waypoints>
        <waypoint>Adresse etape optionnelle</waypoint>
      </waypoints>
      <dropoff_location>Adresse d'arrivee</dropoff_location>
      <route_description>Description courte</route_description>
      <notes>Instructions operationnelles</notes>
    </operation>
  </operations>
  <points_a_verifier>Elements ambigus ou manquants</points_a_verifier>
  <message_original>Message original nettoye</message_original>
</demande>
XML;
    }

    private function parseQuickTransferXml(string $xml): array
    {
        $xml = trim($xml);
        if (preg_match('/<demande[\s\S]*<\/demande>/', $xml, $matches)) {
            $xml = $matches[0];
        }

        libxml_use_internal_errors(true);
        $root = simplexml_load_string($xml);
        if (!$root) {
            throw ValidationException::withMessages([
                'xml' => 'XML invalide. Corrigez le format avant de créer le dossier.',
            ]);
        }

        $text = fn ($node, $key) => trim((string) ($node->{$key} ?? ''));
        $agencyName = $text($root, 'agence') ?: $text($root, 'acente');
        $vehicleType = $text($root, 'vehicle_type') ?: $text($root, 'vehicule_demande');
        $totalPax = (int) ($text($root, 'total_pax') ?: 1);
        $customerName = $text($root, 'customer_name') ?: $text($root, 'passager');

        if ($this->looksLikeVehicleType($customerName) && !$vehicleType) {
            $vehicleType = $customerName;
            $customerName = '';
        }

        $operations = [];
        foreach (($root->operations->operation ?? []) as $operationNode) {
            $date = $text($operationNode, 'date_operation') ?: $text($operationNode, 'service_date');
            $startTime = substr($text($operationNode, 'start_time') ?: $text($operationNode, 'heure_depart'), 0, 5);
            $endTime = substr($text($operationNode, 'end_time') ?: $text($operationNode, 'heure_fin'), 0, 5);
            $pickup = $text($operationNode, 'pickup_location') ?: $text($operationNode, 'lieu_depart');
            $dropoff = $text($operationNode, 'dropoff_location') ?: $text($operationNode, 'lieu_arrivee');
            $operationVehicleType = $text($operationNode, 'vehicle_type') ?: $vehicleType;
            $operationPax = (int) ($text($operationNode, 'pax') ?: $totalPax ?: 1);

            $waypoints = [];
            foreach (($operationNode->waypoints->waypoint ?? []) as $waypoint) {
                $value = trim((string) $waypoint);
                if ($value !== '') {
                    $waypoints[] = $value;
                }
            }

            if (!$date && !$pickup && !$dropoff) {
                continue;
            }

            $startAt = $this->buildDateTime($date, $startTime);
            $endAt = $this->buildDateTime($date, $endTime, $startAt);

            $operations[] = [
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'service_type' => $text($operationNode, 'service_type'),
                'service_type_id' => $this->resolveServiceTypeId($text($operationNode, 'service_type')),
                'vehicle_type' => $operationVehicleType,
                'vehicule_id' => $this->resolveVehiculeId($operationVehicleType),
                'pax' => $operationPax,
                'pickup_location' => $pickup,
                'dropoff_location' => $dropoff,
                'waypoints' => $waypoints,
                'route_description' => $text($operationNode, 'route_description'),
                'notes' => $text($operationNode, 'notes'),
            ];
        }

        $acenteId = $this->resolveAcenteId($agencyName);
        $titleParts = array_filter([
            'AI',
            $agencyName ?: null,
            $customerName ?: null,
            $vehicleType ?: null,
        ]);

        return [
            'source' => $text($root, 'source') ?: 'texte',
            'reference' => $text($root, 'reference'),
            'acente_name' => $agencyName,
            'acente_id' => $acenteId,
            'customer_name' => $customerName,
            'customer_phone' => $text($root, 'customer_phone'),
            'customer_email' => $text($root, 'customer_email'),
            'total_pax' => $totalPax,
            'vehicle_type' => $vehicleType,
            'points_a_verifier' => $text($root, 'points_a_verifier'),
            'message_original' => $text($root, 'message_original'),
            'title' => implode(' - ', $titleParts) ?: 'AI - Création rapide',
            'operations' => $operations,
        ];
    }

    private function previewQuickTransferXml(string $xml): array
    {
        try {
            $parsed = $this->parseQuickTransferXml($xml);
            return [
                'agence' => $parsed['acente_name'],
                'agence_trouvee' => (bool) $parsed['acente_id'],
                'client' => $parsed['customer_name'],
                'vehicule' => $parsed['vehicle_type'],
                'operations' => collect($parsed['operations'])->map(fn ($operation) => [
                    'date' => $operation['date'],
                    'heure' => $operation['start_time'],
                    'depart' => $operation['pickup_location'],
                    'arrivee' => $operation['dropoff_location'],
                    'service' => $operation['service_type'],
                    'vehicule_id' => $operation['vehicule_id'],
                ])->values()->all(),
                'points_a_verifier' => $parsed['points_a_verifier'],
            ];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    private function validateQuickTransferPayload(array $parsed): void
    {
        $errors = [];

        if (!$parsed['acente_id']) {
            $errors['acente_id'] = 'Sélectionnez une agence avant de créer le dossier.';
        }

        if (empty($parsed['operations'])) {
            $errors['xml'] = 'Ajoutez au moins une opération dans le XML.';
        }

        foreach ($parsed['operations'] as $index => $operation) {
            $label = 'Opération ' . ($index + 1) . ': ';
            if (!$operation['date']) {
                $errors['xml'] = $label . 'date manquante.';
                break;
            }
            if (!$operation['start_time']) {
                $errors['xml'] = $label . 'heure de départ manquante.';
                break;
            }
            if (!$operation['pickup_location']) {
                $errors['xml'] = $label . 'lieu de départ manquant.';
                break;
            }
            if (!$operation['dropoff_location']) {
                $errors['xml'] = $label . 'lieu d’arrivée manquant.';
                break;
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function buildDateTime(?string $date, ?string $time, ?string $fallbackStart = null): string
    {
        if (!$date) {
            throw ValidationException::withMessages(['xml' => 'Date opération manquante.']);
        }

        $time = $time ?: ($fallbackStart ? Carbon::parse($fallbackStart)->addHour()->format('H:i') : '00:00');
        $dateTime = Carbon::parse($date . ' ' . $time);

        if ($fallbackStart && $dateTime->lessThanOrEqualTo(Carbon::parse($fallbackStart))) {
            $dateTime->addDay();
        }

        return $dateTime->toDateTimeString();
    }

    private function createQuickTransferTrajets(Transfer $transfer, array $operation): void
    {
        $start = Carbon::parse($operation['start_at']);
        $end = Carbon::parse($operation['end_at']);
        $waypoints = $operation['waypoints'];
        $segments = count($waypoints) + 1;
        $totalSeconds = max(60, $end->diffInSeconds($start));

        $trajets = [[
            'type' => 'depart',
            'from' => $operation['pickup_location'],
            'google_address' => $operation['pickup_location'],
            'datetime' => $start->toDateTimeString(),
            'order' => 1,
        ]];

        foreach ($waypoints as $index => $waypoint) {
            $trajets[] = [
                'type' => 'etape',
                'from' => $waypoint,
                'google_address' => $waypoint,
                'datetime' => $start->copy()->addSeconds((int) round($totalSeconds * (($index + 1) / $segments)))->toDateTimeString(),
                'order' => $index + 2,
            ];
        }

        $trajets[] = [
            'type' => 'arrivee',
            'from' => $operation['dropoff_location'],
            'google_address' => $operation['dropoff_location'],
            'datetime' => $end->toDateTimeString(),
            'order' => count($trajets) + 1,
        ];

        foreach ($trajets as $trajetData) {
            Trajet::create(['transfer_id' => $transfer->id] + $trajetData);
        }
    }

    private function createClientFromQuickTransfer(Post $post, array $parsed): void
    {
        if (!$parsed['customer_name'] && !$parsed['customer_phone'] && !$parsed['customer_email']) {
            return;
        }

        Client::create([
            'title' => '',
            'name' => $parsed['customer_name'] ?: 'Client',
            'surname' => '',
            'email' => $parsed['customer_email'],
            'tel' => $parsed['customer_phone'],
            'post_id' => $post->id,
            'comments' => 'Créé depuis AI création rapide.',
        ]);
    }

    private function buildQuickTransferPostBody(array $parsed, string $xml): string
    {
        return trim(implode("\n", array_filter([
            'Création rapide AI',
            $parsed['reference'] ? 'Référence: ' . $parsed['reference'] : null,
            $parsed['source'] ? 'Source: ' . $parsed['source'] : null,
            $parsed['vehicle_type'] ? 'Véhicule demandé: ' . $parsed['vehicle_type'] : null,
            $parsed['points_a_verifier'] ? 'À vérifier: ' . $parsed['points_a_verifier'] : null,
            $parsed['message_original'] ? 'Message: ' . Str::limit($parsed['message_original'], 350) : null,
            'XML conservé dans la création rapide AI; résumé volontairement court pour le dossier.',
        ])));
    }

    private function limitTextForColumn(?string $value, int $bytes = 900): ?string
    {
        if ($value === null) {
            return null;
        }

        return strlen($value) > $bytes ? mb_strcut($value, 0, $bytes, 'UTF-8') : $value;
    }

    private function resolveAcenteId(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $normalized = Str::lower(Str::ascii($name));

        return Acente::query()
            ->select('id', 'name')
            ->get()
            ->first(function ($acente) use ($normalized) {
                $candidate = Str::lower(Str::ascii(trim((string) $acente->name)));
                return $candidate === $normalized || str_contains($candidate, $normalized) || str_contains($normalized, $candidate);
            })?->id;
    }

    private function resolveServiceTypeId(?string $label): int
    {
        $label = trim((string) $label);
        $normalized = Str::lower(Str::ascii($label));

        $wanted = 'Transfert';
        if (str_contains($normalized, 'dispo')) {
            $wanted = 'Dispo';
        } elseif (str_contains($normalized, 'navette')) {
            $wanted = 'Navette';
        }

        return (int) (Servicetype::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($label, 'UTF-8')])->value('id')
            ?: Servicetype::where('name', 'like', '%' . $wanted . '%')->orderBy('id')->value('id')
            ?: Servicetype::where('name', 'like', '%Transfert%')->orderBy('id')->value('id')
            ?: Servicetype::orderBy('id')->value('id'));
    }

    private function resolveVehiculeId(?string $vehicleType): int
    {
        $vehicleType = trim((string) $vehicleType);
        $normalized = Str::lower(Str::ascii($vehicleType));

        if (str_contains($normalized, 'autocar') || str_contains($normalized, 'coach')) {
            $generic = 'COACH';
        } elseif (str_contains($normalized, 'sprinter') || str_contains($normalized, 'minibus')) {
            $generic = 'SPRINTER';
        } elseif (str_contains($normalized, 'van') || str_contains($normalized, 'vito') || str_contains($normalized, 'classe v')) {
            $generic = 'VAN';
        } else {
            $generic = null;
        }

        return (int) (($generic ? Vehicule::whereRaw('UPPER(TRIM(name)) = ?', [$generic])->value('id') : null)
            ?: Vehicule::whereRaw('TRIM(name) = ?', ['-'])->value('id')
            ?: Vehicule::orderBy('id')->value('id'));
    }

    private function defaultDriverId(): int
    {
        return (int) (Acente::whereRaw('TRIM(name) = ?', ['---'])->value('id')
            ?: Acente::whereRaw('TRIM(name) = ?', ['----'])->value('id')
            ?: Acente::orderBy('id')->value('id'));
    }

    private function looksLikeVehicleType(?string $value): bool
    {
        $normalized = Str::lower(Str::ascii(trim((string) $value)));

        return $normalized !== '' && preg_match('/\b(autocar|coach|sprinter|minibus|van|vito)\b|classe\s*v/', $normalized);
    }

    private function buildTransferContext(string $question): array
    {
        $tz = 'Europe/Paris';
        $lower = Str::lower(Str::ascii($question));
        $now = Carbon::now($tz);
        [$start, $end, $label] = $this->periodFromQuestion($lower, $now);

        $ids = $this->transferIdsFromQuestion($question);

        $query = Transfer::with(['post.acente', 'driver', 'vehicule', 'servicetype', 'status'])
            ->where(function ($q) {
                $q->whereNull('conge')->orWhere('conge', 0);
            });

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
            $label = 'transfert #' . implode(', #', $ids);
        } else {
            $query->whereBetween('start_date', [$start, $end]);
        }

        $transfers = $query
            ->orderBy('start_date')
            ->limit(80)
            ->get()
            ->map(function ($transfer) use ($tz) {
                $start = $transfer->start_date ? Carbon::parse($transfer->start_date, $tz) : null;
                $end = $transfer->end_date ? Carbon::parse($transfer->end_date, $tz) : null;
                return [
                    'id' => $transfer->id,
                    'dossier_id' => $transfer->post_id,
                    'dossier' => optional($transfer->post)->title,
                    'agence' => optional(optional($transfer->post)->acente)->name,
                    'date' => $start?->format('d/m/Y'),
                    'heure_depart' => $start?->format('H:i'),
                    'heure_arrivee_ou_fin' => $end?->format('H:i'),
                    'en_route_prevu' => $transfer->ofis_start ? Carbon::parse($transfer->ofis_start, $tz)->format('H:i') : null,
                    'depart' => $transfer->from,
                    'arrivee' => $transfer->target,
                    'pax' => $transfer->pax,
                    'chauffeur' => optional($transfer->driver)->name,
                    'chauffeur_tel' => optional($transfer->driver)->tel,
                    'vehicule' => trim((optional($transfer->vehicule)->plaka ? optional($transfer->vehicule)->plaka . ' - ' : '') . (optional($transfer->vehicule)->name ?? '')) ?: null,
                    'service' => optional($transfer->servicetype)->name,
                    'statut' => optional($transfer->status)->name,
                    'km' => $transfer->km,
                    'commentaires' => Str::limit((string) $transfer->comments, 280),
                ];
            })
            ->values()
            ->all();

        return [
            'generated_at' => $now->format('d/m/Y H:i'),
            'period_label' => $label,
            'period_start' => $start->format('Y-m-d H:i:s'),
            'period_end' => $end->format('Y-m-d H:i:s'),
            'question' => $question,
            'transfers' => $transfers,
        ];
    }

    private function periodFromQuestion(string $lower, Carbon $now): array
    {
        if (preg_match('/\b(week|semaine|hafta)\b/', $lower)) {
            return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'cette semaine'];
        }

        if (preg_match('/\b(after tomorrow|apres demain|apres-demain|obur gun|oburgun)\b/', $lower)) {
            $day = $now->copy()->addDays(2);
            return [$day->copy()->startOfDay(), $day->copy()->endOfDay(), 'apres-demain'];
        }

        if (preg_match('/\b(tomorrow|demain|yarin|yarinki)\b/', $lower)) {
            $day = $now->copy()->addDay();
            return [$day->copy()->startOfDay(), $day->copy()->endOfDay(), 'demain'];
        }

        if (preg_match('/\b(yesterday|hier|dun|dunku)\b/', $lower)) {
            $day = $now->copy()->subDay();
            return [$day->copy()->startOfDay(), $day->copy()->endOfDay(), 'hier'];
        }

        if (preg_match('/(\d{1,2})[\.\/\-](\d{1,2})(?:[\.\/\-](\d{2,4}))?/', $lower, $m)) {
            $year = isset($m[3]) ? (int) $m[3] : (int) $now->year;
            if ($year < 100) {
                $year += 2000;
            }
            $day = Carbon::create($year, (int) $m[2], (int) $m[1], 0, 0, 0, $now->timezone);
            return [$day->copy()->startOfDay(), $day->copy()->endOfDay(), $day->format('d/m/Y')];
        }

        return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), "aujourd'hui"];
    }

    private function transferIdsFromQuestion(string $question): array
    {
        preg_match_all('/#\s*(\d{3,})|transfer(?:t)?\s*(\d{3,})|transfert\s*(\d{3,})/iu', $question, $matches);
        $ids = [];
        foreach ($matches as $group) {
            foreach ($group as $value) {
                if (is_numeric($value)) {
                    $ids[] = (int) $value;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
