<?php

namespace App\Http\Controllers;

use Webklex\IMAP\Facades\Client;
use Illuminate\Http\Request;
use App\Models\Talep;
use App\Models\TalepDay;
use App\Models\TalepMaili;
use App\Models\TalepMailAttachment;
use App\Models\AcenteEmail;
use App\Models\Acente;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use App\Services\Pricing\TalepPricingService;
use App\Services\Pricing\TalepAiPricingService;
use App\Services\TalepMailPdfParser;



class GmailController extends Controller
{   
    public function __construct() {
        //
          $this->middleware(['role:Admin|ofis|transport']);
      }
   
    // Gelen Mailleri Listele
    public function listMails(Request $request)
    {
        $account = $request->get('account', 'resparis');
    
        $mails = [];
        $mailError = null;
        $refreshed = false;
        $syncedCount = null;
        $days = max(1, min(90, (int) $request->get('days', 7)));
        $limit = max(10, min(500, (int) $request->get('limit', 100)));

        if ($request->boolean('refresh') || ! TalepMaili::where('account', $account)->exists()) {
            try {
                // Keep the interactive request below the web-server timeout.
                // The scheduled command still performs the deeper 100+ message scan.
                $interactiveLimit = $request->boolean('refresh') ? min(10, $limit) : $limit;
                $syncedCount = $this->syncMailbox($account, $days, $interactiveLimit);
                $refreshed = true;
            } catch (\Throwable $e) {
                $mailError = "Connexion IMAP impossible pour cette boîte mail. Vérifiez le mot de passe d'application Gmail / les identifiants IMAP.";
                \Log::warning('IMAP mailbox connection failed', [
                    'account' => $account,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $mails = $this->storedMailsForAccount($account, $limit);
        $lastSync = TalepMaili::where('account', $account)->max('updated_at');
    
        return view('gmail.mails', compact('mails', 'mailError', 'lastSync', 'refreshed', 'syncedCount', 'days', 'limit'));
    }
    
    
    // Seçilen Mailden Yeni Talep Oluştur
    public function createTalep(Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'body' => 'nullable|string',
            'body_html' => 'nullable|string',
            'from' => 'nullable|string|max:255',
            'acente_id' => 'nullable|numeric',
            'message_id' => 'nullable|string',
            'talep_mail_id' => 'nullable|integer|exists:talep_mailleri,id',
            'account' => 'nullable|string|in:contact,resparis,contactfrance,resfrance,paris,sales,sales2',
            'uid' => 'nullable',
            'pdf_text' => 'nullable|string',
        ]);
    
        // Önce kontrol edelim: bu message_id daha önce kaydedilmiş mi?
        if ($request->filled('message_id')) {
            $existingTalep = Talep::where('message_id', $request->input('message_id'))->first();
            if ($existingTalep) {
                $this->syncRequestMailToTalep($request, $existingTalep);

                return response()->json([
                    'success' => 'Une demande existe déjà pour ce mail.',
                    'talep_id' => $existingTalep->id,
                    'talep_url' => route('talepler.show', $existingTalep->id),
                ]);
            }

            $existingMail = TalepMaili::where('message_id', $request->input('message_id'))
                ->whereNotNull('talep_id')
                ->with('talep')
                ->first();

            if ($existingMail?->talep) {
                return response()->json([
                    'success' => 'Une demande existe déjà pour ce mail.',
                    'talep_id' => $existingMail->talep->id,
                    'talep_url' => route('talepler.show', $existingMail->talep->id),
                ]);
            }
        }
    
        $attachmentWarning = null;
        $pricingWarning = null;
        $xml = null;
        $xmlData = [];

        try {
            $mailText = $this->mailTextForXml($request);
            $xml = $this->generateXmlFromMailText($mailText);
            $xmlData = $this->parseTalepXml($xml);

            $talep = DB::transaction(function () use ($request, $xml, $xmlData) {
                $talep = Talep::create($this->talepPayloadFromXml($request, $xml, $xmlData));

                foreach ($xmlData['operations'] as $index => $operation) {
                    if (!$this->operationHasAnyValue($operation)) {
                        continue;
                    }

                    TalepDay::create([
                        'talep_id' => $talep->id,
                        'day_number' => $index + 1,
                        'service_date' => $operation['service_date'] ?? null,
                        'start_time' => $operation['start_time'] ?? null,
                        'end_time' => $operation['end_time'] ?? null,
                        'service_type' => $operation['service_type'] ?? null,
                        'vehicle_type' => $operation['vehicle_type'] ?? null,
                        'pax' => $operation['pax'] ?? null,
                        'pickup_location' => $operation['pickup_location'] ?? null,
                        'dropoff_location' => $operation['dropoff_location'] ?? null,
                        'via_points_json' => !empty($operation['waypoints']) ? array_values(array_filter($operation['waypoints'])) : null,
                        'route_description' => $operation['route_description'] ?? null,
                        'notes' => $operation['notes'] ?? null,
                    ]);
                }

                return $talep;
            });

            try {
                app(TalepPricingService::class)->calculate($talep, true, true);
            } catch (\Throwable $e) {
                $pricingWarning = 'Demande créée, mais le prix système n’a pas pu être calculé.';
                \Log::warning('Gmail talep pricing failed after create', [
                    'talep_id' => $talep->id,
                    'message_id' => $request->input('message_id'),
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                app(TalepAiPricingService::class)->calculate($talep->fresh('days'));
            } catch (\Throwable $e) {
                $pricingWarning = trim(($pricingWarning ?? '') . ' La suggestion de prix IA n’a pas pu être calculée.');
                \Log::warning('Gmail talep AI pricing failed after create', [
                    'talep_id' => $talep->id,
                    'message_id' => $request->input('message_id'),
                    'error' => $e->getMessage(),
                ]);
            }

            $this->syncRequestMailToTalep($request, $talep);
        } catch (\Throwable $e) {
            \Log::error('Gmail create talep failed', [
                'message_id' => $request->input('message_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'La création de la demande a échoué.',
                'error' => $e->getMessage(),
            ], 422);
        }

        try {
            $attachments = $this->mailAttachmentsFromRequest($request);
            foreach ($attachments as $attachment) {
                $this->storeTalepAttachment($talep, $attachment['name'], $attachment['content']);
            }
        } catch (\Throwable $e) {
            $attachmentWarning = 'Demande créée, mais les pièces jointes n’ont pas pu être récupérées.';
            \Log::warning('Gmail talep attachments failed after create', [
                'talep_id' => $talep->id,
                'message_id' => $request->input('message_id'),
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => $attachmentWarning || $pricingWarning ? 'Demande créée avec avertissement.' : 'Demande créée avec succès.',
            'warning' => collect([$attachmentWarning, $pricingWarning])->filter()->implode("\n"),
            'xml' => $xml,
            'talep_id' => $talep->id,
            'talep_url' => route('talepler.show', $talep->id),
        ]);
    }

    public function linkTalep(Request $request)
    {
        $data = $request->validate([
            'talep_mail_id' => 'required|integer|exists:talep_mailleri,id',
            'talep_id' => 'required|integer|exists:talepler,id',
        ]);

        $mail = TalepMaili::findOrFail($data['talep_mail_id']);
        $talep = Talep::findOrFail($data['talep_id']);

        $mail->update([
            'talep_id' => $talep->id,
            'linked_by' => 'manual',
            'sync_status' => 'linked',
        ]);

        if (!$talep->message_id && $mail->message_id) {
            $talep->message_id = $mail->message_id;
            $talep->save();
        }

        return response()->json([
            'success' => 'Mail lié à la demande.',
            'talep_id' => $talep->id,
            'talep_url' => route('talepler.show', $talep->id),
        ]);
    }

    public function syncMailboxForScheduler(string $account, int $days = 7, int $limit = 100): int
    {
        return $this->syncMailbox($account, $days, $limit);
    }

    private function mailTextForXml(Request $request): string
    {
        return Str::limit(implode("\n", [
            'Date de réception de la demande: ' . ($request->input('date') ?: now('Europe/Paris')->format('Y-m-d H:i:s')),
            'Sujet: ' . $request->input('subject'),
            'Expéditeur: ' . $request->input('from'),
            '',
            'Important: la date de réception ci-dessus est date_demande/talep_tarihi. La date demandée dans le texte client est date_operation/service_date.',
            '',
            (string) $request->input('body'),
            '',
            $request->filled('pdf_text') ? "Texte extrait des PDF joints:\n" . (string) $request->input('pdf_text') : '',
        ]), 25000, '');
    }

    private function generateXmlFromMailText(string $mailText): string
    {
        $modelPath = resource_path('prompts/talep_xml_model.xml');
        $xmlModel = file_exists($modelPath) ? file_get_contents($modelPath) : '';

        if (!config('services.openai.key')) {
            throw new \RuntimeException('La configuration OpenAI est manquante.');
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
                        'content' => "Tu transformes des demandes de transport en XML strict. Reponds uniquement avec XML valide, sans markdown. Respecte exactement cette structure et garde les balises meme si une valeur est inconnue. Utilise le francais pour les textes. Important: date_demande/talep_tarihi = date de réception de la demande; date_operation/service_date = date du transport demandé. Ne mets jamais la date de réception comme date_operation sauf si le client demande explicitement un service ce jour-là. Modele XML:\n" . $xmlModel,
                    ],
                    [
                        'role' => 'user',
                        'content' => $mailText,
                    ],
                ],
            ]);

        if (!$response->successful()) {
            $errorCode = data_get($response->json(), 'error.code');
            $message = 'La conversion XML a échoué.';

            if ($response->status() === 429 || $errorCode === 'insufficient_quota') {
                $message = 'Le quota OpenAI est épuisé. Veuillez vérifier la facturation ou la clé API.';
            }

            \Log::error('Gmail XML OpenAI failed', [
                'status' => $response->status(),
                'code' => $errorCode,
                'body' => $response->body(),
            ]);

            throw new \RuntimeException($message);
        }

        $content = trim((string) data_get($response->json(), 'choices.0.message.content'));
        $content = preg_replace('/^```(?:xml)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        if (preg_match('/<demande[\s\S]*<\/demande>/', $content, $matches)) {
            $content = $matches[0];
        }

        if (!$content || !str_contains($content, '<demande')) {
            throw new \RuntimeException('La conversion XML a échoué.');
        }

        return $content;
    }

    private function parseTalepXml(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $root = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$root) {
            throw new \RuntimeException('XML invalide.');
        }

        $operations = [];
        foreach ($root->xpath('operations/operation') ?: [] as $operation) {
            $waypoints = [];
            foreach ($operation->xpath('waypoints/waypoint') ?: [] as $waypoint) {
                $value = $this->cleanXmlValue((string) $waypoint);
                if ($value !== null) {
                    $waypoints[] = $value;
                }
            }

            $operations[] = [
                'service_date' => $this->parseDate($this->xmlText($operation, 'date_operation') ?: $this->xmlText($operation, 'service_date')),
                'start_time' => $this->parseTime($this->xmlText($operation, 'start_time')),
                'end_time' => $this->parseTime($this->xmlText($operation, 'end_time')),
                'pax' => $this->parseInteger($this->xmlText($operation, 'pax')),
                'pickup_location' => $this->xmlText($operation, 'pickup_location'),
                'dropoff_location' => $this->xmlText($operation, 'dropoff_location'),
                'service_type' => $this->xmlText($operation, 'service_type'),
                'vehicle_type' => $this->xmlText($operation, 'vehicle_type'),
                'route_description' => $this->xmlText($operation, 'route_description'),
                'notes' => $this->xmlText($operation, 'notes'),
                'waypoints' => $waypoints,
            ];
        }

        return [
            'date_demande' => $this->xmlText($root, 'date_demande') ?: $this->xmlText($root, 'talep_tarihi'),
            'talep_kanali' => $this->xmlText($root, 'talep_kanali'),
            'country' => $this->xmlText($root, 'country') ?: $this->xmlText($root, 'pays'),
            'customer_name' => $this->xmlText($root, 'customer_name'),
            'customer_phone' => $this->xmlText($root, 'customer_phone'),
            'customer_email' => $this->xmlText($root, 'customer_email'),
            'total_pax' => $this->parseInteger($this->xmlText($root, 'total_pax')),
            'system_total' => $this->parseDecimal($this->xmlText($root, 'prix_propose')),
            'verilen_fiyat' => $this->parseDecimal($this->xmlText($root, 'prix_communique')),
            'currency' => $this->xmlText($root, 'currency') ?: 'EUR',
            'konfirme_durumu' => $this->xmlText($root, 'konfirme_durumu'),
            'uzun_mesaj' => $this->xmlText($root, 'uzun_mesaj'),
            'internal_notes' => $this->xmlText($root, 'internal_notes'),
            'operations' => $operations,
        ];
    }

    private function talepPayloadFromXml(Request $request, string $xml, array $xmlData): array
    {
        return [
            'user_id' => auth()->id(),
            'acente_id' => $request->input('acente_id'),
            'talep_tarihi' => $this->parseDateTime($xmlData['date_demande'] ?? null, $request->input('date')),
            'talep_kanali' => $xmlData['talep_kanali'] ?: 'Mail',
            'customer_name' => $xmlData['customer_name'] ?: null,
            'customer_phone' => $xmlData['customer_phone'] ?: null,
            'customer_email' => filter_var($xmlData['customer_email'] ?? null, FILTER_VALIDATE_EMAIL) ? $xmlData['customer_email'] : null,
            'total_pax' => $xmlData['total_pax'] ?: null,
            'system_total' => $xmlData['system_total'],
            'verilen_fiyat' => $xmlData['verilen_fiyat'],
            'currency' => $xmlData['currency'] ?: 'EUR',
            'relance_yapildi' => 0,
            'konfirme_durumu' => $xmlData['konfirme_durumu'] ?: 'Waiting Price',
            'uzun_mesaj' => Str::limit((string) ($xmlData['uzun_mesaj'] ?: $request->input('body') ?: $request->input('subject')), 60000, ''),
            'internal_notes' => trim(($xmlData['internal_notes'] ?: '') . "\n\nXML généré automatiquement depuis le mail:\n" . $xml),
            'message_id' => $request->input('message_id'),
        ] + (Schema::hasColumn('talepler', 'country') ? [
            'country' => $xmlData['country'] ?: null,
        ] : []);
    }

    private function operationHasAnyValue(array $operation): bool
    {
        return !empty($operation['service_date']) ||
            !empty($operation['pickup_location']) ||
            !empty($operation['dropoff_location']) ||
            !empty($operation['route_description']) ||
            !empty($operation['pax']);
    }

    private function xmlText(\SimpleXMLElement $node, string $name): ?string
    {
        return $this->cleanXmlValue((string) ($node->{$name} ?? ''));
    }

    private function cleanXmlValue(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $lower = mb_strtolower($value);
        if (str_contains($value, 'YYYY-') || str_contains($value, 'HH:MM') || in_array($lower, ['non défini', 'non defini', 'inconnu', 'n/a', '-'], true)) {
            return null;
        }

        return $value;
    }

    private function parseDateTime(?string $value, ?string $fallback = null): Carbon
    {
        foreach ([$value, $fallback] as $candidate) {
            $candidate = $this->cleanXmlValue($candidate);
            if (!$candidate) {
                continue;
            }

            try {
                return Carbon::parse($candidate, 'Europe/Paris');
            } catch (\Throwable $e) {
                \Log::warning('Gmail talep date parse failed', [
                    'date' => $candidate,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return now('Europe/Paris');
    }

    private function parseDate(?string $value): ?string
    {
        $value = $this->cleanXmlValue($value);
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value, 'Europe/Paris')->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseTime(?string $value): ?string
    {
        $value = $this->cleanXmlValue($value);
        if (!$value) {
            return null;
        }

        if (preg_match('/\b([01]?\d|2[0-3])[:hH]([0-5]\d)\b/', $value, $matches)) {
            return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
        }

        return null;
    }

    private function parseInteger(?string $value): ?int
    {
        $value = $this->cleanXmlValue($value);
        if (!$value || !preg_match('/\d+/', $value, $matches)) {
            return null;
        }

        return (int) $matches[0];
    }

    private function parseDecimal(?string $value): ?float
    {
        $value = $this->cleanXmlValue($value);
        if (!$value) {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], $value);
        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function syncMailbox(string $account, int $days = 7, int $limit = 100): int
    {
        $client = Client::account($account);
        $client->connect();

        try {
            $date = Carbon::now()->subDays($days)->format('d-M-Y');
            $synced = 0;

            foreach ($this->mailboxFoldersForSync($client, $account) as $mailboxFolder) {
                // Fetching 100+ complete messages in one IMAP request can exceed the
                // PHP-FPM memory limit, especially when messages contain attachments.
                // Re-open the folder explicitly and fetch a small page at a time.
                $client->openFolder($mailboxFolder['folder_name'], true);
                $chunkSize = min(5, $limit);
                $remaining = $limit;
                $page = 1;

                while ($remaining > 0) {
                    $pageSize = min($chunkSize, $remaining);
                    $query = $mailboxFolder['folder']
                        ->messages()
                        ->since($date)
                        ->limit($pageSize, $page);
                    $query->setFolderPath($mailboxFolder['folder_name']);
                    $messages = $query->get();

                    if ($messages->isEmpty()) {
                        break;
                    }

                    foreach ($messages as $message) {
                        $textBody = (string) $message->getTextBody();
                        $htmlBody = (string) $message->getHTMLBody();
                        if (trim($textBody) === '') {
                            $textBody = trim(strip_tags($htmlBody));
                        }
                        $mailBody = TalepMaili::mailBodyForStorage($htmlBody, $textBody);

                        $attachments = [];
                        foreach ($message->getAttachments() as $attachment) {
                            $name = Str::ascii((string) $attachment->getName());
                            $mimeType = method_exists($attachment, 'getMimeType') ? (string) $attachment->getMimeType() : null;
                            $attachments[] = [
                                'name' => $name,
                                'mime_type' => $mimeType,
                                'content' => $this->isPdfAttachment($name, $mimeType) ? $attachment->getContent() : null,
                            ];
                        }

                        $mail = $this->syncMessageToTalepMail(
                            $message,
                            $account,
                            $mailBody,
                            $attachments,
                            $mailboxFolder['folder_name'],
                            $mailboxFolder['direction']
                        );
                        $this->syncPdfAttachmentsForMail($mail, $attachments);
                        $synced++;
                    }

                    $fetched = $messages->count();
                    unset($messages);
                    gc_collect_cycles();

                    $remaining -= $fetched;
                    if ($fetched < $pageSize) {
                        break;
                    }
                    $page++;
                }
            }

            return $synced;
        } finally {
            $client->disconnect();
        }
    }

    private function mailboxFoldersForSync($client, string $account): array
    {
        $folders = [
            [
                'folder' => $client->getFolder('INBOX'),
                'folder_name' => 'INBOX',
                'direction' => 'in',
            ],
        ];

        foreach ($this->sentFolders($client, $account) as $folder) {
            $folders[] = [
                'folder' => $folder,
                'folder_name' => $this->mailboxFolderName($folder),
                'direction' => 'out',
            ];
        }

        return $folders;
    }

    private function sentFolders($client, string $account): array
    {
        $sentFolders = [];

        foreach ($this->sentFolderCandidates($account) as $candidate) {
            try {
                $folder = $client->getFolder($candidate);
                if (! $folder || ! method_exists($folder, 'messages')) {
                    continue;
                }

                $sentFolders[$this->mailboxFolderName($folder)] = $folder;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return array_values($sentFolders);
    }

    private function sentFolderCandidates(string $account): array
    {
        $byAccount = [
            'contact' => ['[Gmail]/Sent Mail', 'INBOX/[Gmail]/Messages envoyés'],
            'resparis' => ['[Gmail]/Messages envoyés'],
            'contactfrance' => ['[Gmail]/Sent Mail', '[Gmail]/Messages envoyés'],
            'resfrance' => ['[Gmail]/Sent Mail', '[Gmail]/Messages envoyés'],
            'paris' => ['[Gmail]/Sent Mail', '[Gmail]/Messages envoyés'],
            'sales' => ['[Gmail]/Sent Mail', '[Gmail]/Messages envoyés'],
            'sales2' => ['[Gmail]/Sent Mail', '[Gmail]/Messages envoyés'],
        ];
        $fallback = [
            '[Gmail]/Sent Mail',
            '[Gmail]/Messages envoyés',
            'INBOX/[Gmail]/Messages envoyés',
            'Sent Mail',
            'Sent',
        ];

        return array_values(array_unique(array_merge($byAccount[$account] ?? [], $fallback)));
    }

    private function mailboxFolderName($folder): string
    {
        return (string) ($folder->path ?? $folder->name ?? 'INBOX');
    }

    private function storedMailsForAccount(string $account, int $limit = 100): array
    {
        $storedMails = TalepMaili::with(['talep', 'mailAttachments'])
            ->where('account', $account)
            ->orderByRaw('COALESCE(received_at, created_at) DESC')
            ->limit($limit)
            ->get();

        $emailKeys = $storedMails
            ->flatMap(function (TalepMaili $mail) {
                return collect([$mail->from_email])
                    ->merge($mail->to_emails_json ?: [])
                    ->merge($mail->cc_emails_json ?: []);
            })
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        $acenteEmails = AcenteEmail::with('acente')
            ->whereIn(DB::raw('LOWER(TRIM(email))'), $emailKeys)
            ->get()
            ->keyBy(fn ($row) => strtolower(trim($row->email)));

        return $storedMails
            ->map(fn (TalepMaili $mail): array => $this->storedMailPayload($mail, $acenteEmails))
            ->all();
    }

    private function storedMailPayload(TalepMaili $mail, $acenteEmails): array
    {
        $matchEmails = collect($mail->direction === 'out' ? ($mail->to_emails_json ?: []) : [$mail->from_email])
            ->merge($mail->cc_emails_json ?: [])
            ->merge([$mail->from_email])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter();
        $acenteEmail = $matchEmails
            ->map(fn ($email) => $acenteEmails->get($email))
            ->first();
        $storedPdfAttachments = $mail->mailAttachments->keyBy('original_name');
        $attachments = collect($mail->attachment_names_json ?: [])
            ->map(function ($name) use ($storedPdfAttachments) {
                $stored = $storedPdfAttachments->get($name);

                return [
                    'name' => $name,
                    'is_pdf' => (bool) $stored,
                    'parse_status' => $stored?->parse_status,
                    'ocr_used' => (bool) ($stored?->ocr_used),
                    'parsed_preview' => $stored ? Str::limit($stored->parsed_text ?: '', 400, '') : null,
                ];
            })
            ->values()
            ->all();
        $pdfText = Str::limit($mail->mailAttachments
            ->where('parse_status', 'parsed')
            ->pluck('parsed_text')
            ->filter()
            ->implode("\n\n--- PDF ---\n\n"), 20000, '');

        return [
            'subject' => $mail->mail_baslik,
            'from' => $mail->from_email,
            'to' => collect($mail->to_emails_json ?: [])->implode(', '),
            'body' => Str::limit($mail->mail_icerik_text, 12000, ''),
            'body_html' => $mail->mail_icerik_html,
            'date' => optional($mail->received_at ?: $mail->created_at)->format('Y-m-d H:i:s'),
            'account' => $mail->account,
            'folder' => $mail->folder,
            'direction' => $mail->direction,
            'uid' => $mail->uid,
            'attachments' => $attachments,
            'pdf_text' => $pdfText,
            'acente' => optional($acenteEmail?->acente)->name,
            'acente_id' => $acenteEmail?->acente_id,
            'message_id' => $mail->message_id,
            'linked_by' => $mail->linked_by,
            'sync_status' => $mail->sync_status,
            'talep_mail_id' => $mail->id,
            'existing_talep_id' => $mail->talep_id,
            'existing_talep_url' => $mail->talep_id ? route('talepler.show', $mail->talep_id) : null,
        ];
    }

    private function syncMessageToTalepMail($message, string $account, string $mailBody, array $attachments, string $folderName = 'INBOX', string $direction = 'in'): TalepMaili
    {
        $subject = $this->decodeHeaderValue((string) $message->getSubject());
        $from = optional($message->getFrom())->first();
        $fromEmail = strtolower(trim((string) ($from?->mail ?? '')));
        $fromName = trim((string) ($from?->personal ?? $from?->name ?? '')) ?: null;
        $toEmails = $this->addressesToEmails($message->getTo());
        $ccEmails = $this->addressesToEmails($message->getCc());
        $messageId = $this->messageIdFromMessage($message);
        $uid = (string) $message->getUid();
        $receivedAt = optional($message->getDate())->get();
        $subjectNormalized = $this->normalizeMailSubject($subject);
        $threadEmail = $direction === 'out'
            ? $this->outgoingThreadEmail($account, $toEmails, $ccEmails)
            : $fromEmail;
        $threadKey = $this->mailThreadKey($threadEmail, $subjectNormalized);
        $inReplyTo = $this->messageHeaderValue($message, ['in_reply_to', 'in-reply-to']);
        $references = $this->messageHeaderValue($message, ['references']);
        [$talepId, $linkedBy] = $this->matchTalepForMail($messageId, $inReplyTo, $references, $threadKey, $threadEmail, $subjectNormalized);

        $messageHash = $this->mailMessageHash($account, $messageId, $uid, $subject, $receivedAt?->format('Y-m-d H:i:s'), $folderName);
        $mail = TalepMaili::firstOrNew(['message_hash' => $messageHash]);
        $talepId = $talepId ?: $mail->talep_id;

        $mail->fill([
            'talep_id' => $talepId,
            'account' => $account,
            'folder' => $folderName,
            'uid' => $uid,
            'message_id' => $messageId,
            'message_hash' => $messageHash,
            'thread_key' => $threadKey,
            'mail_baslik' => Str::limit($subject ?: '(sans sujet)', 255, ''),
            'subject_normalized' => Str::limit($subjectNormalized, 255, ''),
            'from_email' => $fromEmail ?: null,
            'from_name' => Str::limit($fromName ?: '', 255, '') ?: null,
            'to_emails_json' => $toEmails,
            'cc_emails_json' => $ccEmails,
            'in_reply_to' => Str::limit((string) $inReplyTo, 255, '') ?: null,
            'references_header' => $references,
            'received_at' => $receivedAt,
            'direction' => $direction,
            'attachment_names_json' => collect($attachments)->pluck('name')->filter()->values()->all(),
            'linked_by' => $talepId ? ($linkedBy ?: $mail->linked_by ?: 'auto') : 'none',
            'sync_status' => $talepId ? 'linked' : 'unlinked',
            'mail_icerik' => Str::limit($mailBody, 50000, ''),
        ]);
        $mail->save();
        $mail->load('talep');

        return $mail;
    }

    private function syncRequestMailToTalep(Request $request, Talep $talep): void
    {
        $subject = $this->decodeHeaderValue((string) $request->input('subject'));
        $fromEmail = strtolower(trim((string) $request->input('from')));
        $messageId = trim((string) $request->input('message_id')) ?: null;
        $uid = trim((string) $request->input('uid')) ?: null;
        $account = trim((string) $request->input('account')) ?: null;
        $receivedAt = $this->parseDateTime($request->input('date'), null);
        $subjectNormalized = $this->normalizeMailSubject($subject);
        $threadKey = $this->mailThreadKey($fromEmail, $subjectNormalized);
        $messageHash = $this->mailMessageHash($account ?: 'request', $messageId, $uid, $subject, $receivedAt?->format('Y-m-d H:i:s'), 'INBOX');

        $mail = $request->filled('talep_mail_id') ? TalepMaili::find($request->input('talep_mail_id')) : null;
        $mail = $mail
            ?: TalepMaili::where('message_hash', $messageHash)->first()
            ?: ($messageId ? TalepMaili::where('message_id', $messageId)->first() : null)
            ?: new TalepMaili(['message_hash' => $messageHash]);
        $mailBody = TalepMaili::mailBodyForStorage($request->input('body_html'), $request->input('body'));

        $mail->fill([
            'talep_id' => $talep->id,
            'account' => $account,
            'folder' => 'INBOX',
            'uid' => $uid,
            'message_id' => $messageId,
            'message_hash' => $messageHash,
            'thread_key' => $threadKey,
            'mail_baslik' => Str::limit($subject ?: '(sans sujet)', 255, ''),
            'subject_normalized' => Str::limit($subjectNormalized, 255, ''),
            'from_email' => $fromEmail ?: null,
            'received_at' => $receivedAt,
            'direction' => 'in',
            'attachment_names_json' => collect((array) $request->input('attachments', []))->pluck('name')->filter()->values()->all(),
            'linked_by' => 'created_from_mail',
            'sync_status' => 'linked',
            'mail_icerik' => Str::limit($mailBody, 50000, ''),
        ]);
        $mail->save();
    }

    private function matchTalepForMail(?string $messageId, ?string $inReplyTo, ?string $references, ?string $threadKey, ?string $fromEmail, string $subjectNormalized): array
    {
        if ($messageId) {
            $talep = Talep::where('message_id', $messageId)->first(['id']);
            if ($talep) {
                return [$talep->id, 'message_id'];
            }
        }

        $referenceIds = $this->extractMessageIds(trim((string) $inReplyTo . ' ' . (string) $references));
        if ($referenceIds !== []) {
            $talep = Talep::whereIn('message_id', $referenceIds)->first(['id']);
            if ($talep) {
                return [$talep->id, 'reply_header'];
            }

            $mail = TalepMaili::whereIn('message_id', $referenceIds)
                ->whereNotNull('talep_id')
                ->latest('received_at')
                ->first(['talep_id']);

            if ($mail) {
                return [$mail->talep_id, 'reply_header'];
            }
        }

        if ($threadKey) {
            $mail = TalepMaili::where('thread_key', $threadKey)
                ->whereNotNull('talep_id')
                ->latest('received_at')
                ->first(['talep_id']);

            if ($mail) {
                return [$mail->talep_id, 'thread_key'];
            }
        }

        if ($fromEmail && $subjectNormalized !== '') {
            $mail = TalepMaili::where('from_email', $fromEmail)
                ->where('subject_normalized', $subjectNormalized)
                ->whereNotNull('talep_id')
                ->latest('received_at')
                ->first(['talep_id']);

            if ($mail) {
                return [$mail->talep_id, 'sender_subject'];
            }
        }

        return [null, 'none'];
    }

    private function messageIdFromMessage($message): ?string
    {
        $messageId = $this->messageHeaderValue($message, ['message_id', 'message-id']) ?: (string) $message->getId();

        return trim($messageId) !== '' ? Str::limit(trim($messageId), 255, '') : 'UID-' . $message->getUid();
    }

    private function messageHeaderValue($message, array $names): ?string
    {
        foreach ($names as $name) {
            try {
                $header = $message->getHeader($name);
                if (!$header) {
                    continue;
                }

                $value = is_object($header) && method_exists($header, 'get')
                    ? $header->get('value')
                    : (string) $header;

                if (trim((string) $value) === '' && is_object($header) && method_exists($header, '__toString')) {
                    $value = (string) $header;
                }

                $value = $this->decodeHeaderValue((string) $value);
                if (trim($value) !== '') {
                    return trim($value);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function decodeHeaderValue(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $decoded = @mb_decode_mimeheader($value);

        return trim($decoded ?: $value);
    }

    private function normalizeMailSubject(?string $subject): string
    {
        $subject = $this->decodeHeaderValue($subject);
        $subject = preg_replace('/^\s*((re|fw|fwd|tr|aw)\s*:\s*)+/iu', '', $subject) ?: $subject;
        $subject = preg_replace('/\s+/u', ' ', $subject) ?: $subject;
        $subject = trim($subject);
        $ascii = Str::ascii($subject);
        $normalized = mb_strtolower($ascii ?: $subject);
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?: $normalized;

        return trim(preg_replace('/\s+/', ' ', $normalized) ?: '');
    }

    private function mailThreadKey(?string $fromEmail, string $subjectNormalized): ?string
    {
        $fromEmail = strtolower(trim((string) $fromEmail));
        if ($fromEmail === '' || $subjectNormalized === '') {
            return null;
        }

        return sha1($fromEmail . '|' . $subjectNormalized);
    }

    private function outgoingThreadEmail(string $account, array $toEmails, array $ccEmails): ?string
    {
        $ownEmails = $this->mailAccountEmails($account);

        return collect($toEmails)
            ->merge($ccEmails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->first(fn ($email) => ! in_array($email, $ownEmails, true));
    }

    private function mailAccountEmails(string $account): array
    {
        $emails = collect(config('imap.accounts', []))
            ->pluck('username')
            ->push(config("imap.accounts.{$account}.username"))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $emails;
    }

    private function mailMessageHash(?string $account, ?string $messageId, ?string $uid, ?string $subject, ?string $date, ?string $folder = null): string
    {
        $identity = trim((string) $messageId);
        if ($identity === '') {
            $identity = trim((string) $folder) . '|' . trim((string) $uid) . '|' . trim((string) $subject) . '|' . trim((string) $date);
        }

        return sha1(strtolower(trim((string) $account)) . '|' . $identity);
    }

    private function extractMessageIds(string $value): array
    {
        if ($value === '') {
            return [];
        }

        preg_match_all('/<[^>]+>|[A-Z0-9._%+\-]+@[A-Z0-9.\-]+/i', $value, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($item) => Str::limit(trim($item), 255, ''))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function addressesToEmails($addresses): array
    {
        if (! $addresses) {
            return [];
        }

        if (is_object($addresses) && method_exists($addresses, 'all')) {
            $addresses = $addresses->all();
        } elseif (is_object($addresses) && method_exists($addresses, 'toArray')) {
            $addresses = $addresses->toArray();
        } elseif (is_object($addresses) && method_exists($addresses, 'first')) {
            $addresses = [$addresses->first()];
        } elseif (is_string($addresses)) {
            $addresses = [$addresses];
        }

        return collect($addresses ?: [])
            ->flatMap(fn ($address) => $this->emailsFromAddressValue($address))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function emailsFromAddressValue($address): array
    {
        if (is_array($address)) {
            $mail = $address['mail'] ?? null;
            if (! $mail && !empty($address['mailbox']) && !empty($address['host'])) {
                $mail = $address['mailbox'] . '@' . $address['host'];
            }

            return $mail ? [(string) $mail] : [];
        }

        if (is_object($address)) {
            $mail = $address->mail ?? null;
            if (! $mail && !empty($address->mailbox) && !empty($address->host)) {
                $mail = $address->mailbox . '@' . $address->host;
            }

            if ($mail) {
                return [(string) $mail];
            }
        }

        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', (string) $address, $matches);

        return $matches[0] ?? [];
    }

    private function syncPdfAttachmentsForMail(TalepMaili $mail, array $attachments): void
    {
        if (!Schema::hasTable('talep_mail_attachments')) {
            return;
        }

        foreach ($attachments as $attachment) {
            $name = (string) ($attachment['name'] ?? '');
            $mimeType = $attachment['mime_type'] ?? null;
            $content = $attachment['content'] ?? null;

            if (!$this->isPdfAttachment($name, $mimeType) || $content === null || $content === '') {
                continue;
            }

            $sha256 = hash('sha256', $content);
            $existing = TalepMailAttachment::where('talep_maili_id', $mail->id)
                ->where('sha256', $sha256)
                ->first();

            if ($existing && $existing->parse_status === 'parsed') {
                continue;
            }

            $storedPath = $existing?->stored_path ?: $this->storeMailAttachmentFile($name, $content);
            $record = $existing ?: new TalepMailAttachment([
                'talep_maili_id' => $mail->id,
                'sha256' => $sha256,
            ]);

            $record->fill([
                'acente_id' => $this->acenteIdForMail($mail),
                'original_name' => $name,
                'stored_path' => $storedPath,
                'mime_type' => $mimeType ?: 'application/pdf',
                'size_bytes' => strlen($content),
                'parse_status' => 'pending',
                'parse_error' => null,
            ]);
            $record->save();

            try {
                $parsed = app(TalepMailPdfParser::class)->parse(public_path('storage/' . $storedPath));
                $record->fill([
                    'parsed_text' => Str::limit($parsed['text'] ?? '', 500000, ''),
                    'parsed_json' => $parsed['json'] ?? null,
                    'parse_status' => trim((string) ($parsed['text'] ?? '')) !== '' ? 'parsed' : 'empty',
                    'parse_error' => null,
                    'ocr_used' => (bool) ($parsed['ocr_used'] ?? false),
                ]);
            } catch (\Throwable $e) {
                $record->fill([
                    'parse_status' => 'error',
                    'parse_error' => Str::limit($e->getMessage(), 1000, ''),
                ]);
                \Log::warning('Gmail PDF parse failed', [
                    'talep_maili_id' => $mail->id,
                    'attachment' => $name,
                    'error' => $e->getMessage(),
                ]);
            }

            $record->save();
        }
    }

    private function storeMailAttachmentFile(string $name, string $content): string
    {
        $originalName = str_replace(' ', '_', Str::ascii($name));
        $originalName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $originalName);
        $originalName = $originalName ?: 'piece_jointe.pdf';

        $monthFolder = now()->format('Y-m');
        $relativeFolder = 'talep_mail_attachments/' . $monthFolder;
        $path = public_path('storage/' . $relativeFolder);
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        $fileName = uniqid() . '_' . $originalName;
        file_put_contents($path . '/' . $fileName, $content);

        return $relativeFolder . '/' . $fileName;
    }

    private function isPdfAttachment(?string $name, ?string $mimeType = null): bool
    {
        $name = strtolower(trim((string) $name));
        $mimeType = strtolower(trim((string) $mimeType));

        return Str::endsWith($name, '.pdf') || str_contains($mimeType, 'pdf');
    }

    private function acenteIdForMail(TalepMaili $mail): ?int
    {
        $mail->loadMissing('talep');
        if ($mail->talep?->acente_id) {
            return (int) $mail->talep->acente_id;
        }

        $emails = collect($mail->direction === 'out' ? ($mail->to_emails_json ?: []) : [$mail->from_email])
            ->merge($mail->cc_emails_json ?: [])
            ->merge([$mail->from_email])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return null;
        }

        return AcenteEmail::whereIn(DB::raw('LOWER(TRIM(email))'), $emails)->value('acente_id');
    }

    private function mailAttachmentsFromRequest(Request $request): array
    {
        if ($request->filled('talep_mail_id') && Schema::hasTable('talep_mail_attachments')) {
            $storedAttachments = TalepMailAttachment::where('talep_maili_id', $request->input('talep_mail_id'))
                ->get()
                ->map(function (TalepMailAttachment $attachment) {
                    $path = public_path('storage/' . $attachment->stored_path);

                    return [
                        'name' => $attachment->original_name,
                        'content' => is_file($path) ? file_get_contents($path) : null,
                    ];
                })
                ->filter(fn ($attachment) => !empty($attachment['name']) && $attachment['content'] !== null)
                ->values()
                ->all();

            if ($storedAttachments !== []) {
                return $storedAttachments;
            }
        }

        if ($request->filled('account') && $request->filled('uid')) {
            try {
                $client = Client::account($request->input('account'));
                $client->connect();
                $message = $client->getFolder('INBOX')->messages()->getMessageByUid($request->input('uid'));
                $attachments = [];
                foreach ($message->getAttachments() as $attachment) {
                    $attachments[] = [
                        'name' => Str::ascii($attachment->getName()),
                        'content' => $attachment->getContent(),
                    ];
                }
                $client->disconnect();

                return $attachments;
            } catch (\Throwable $e) {
                \Log::warning('IMAP attachment fetch failed', [
                    'account' => $request->input('account'),
                    'uid' => $request->input('uid'),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return collect((array) $request->input('attachments', []))
            ->filter(fn ($attachment) => !empty($attachment['content']) && !empty($attachment['name']))
            ->map(fn ($attachment) => [
                'name' => $attachment['name'],
                'content' => base64_decode($attachment['content']),
            ])
            ->values()
            ->all();
    }

    private function storeTalepAttachment(Talep $talep, string $name, string $content): void
    {
        $originalName = str_replace(' ', '_', $name);
        $originalName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $originalName);
        $originalName = $originalName ?: 'piece_jointe';

        $monthFolder = now()->format('Y-m');
        $path = public_path('storage/talepler_ekleri/' . $monthFolder);
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        $fileName = uniqid() . '_' . $originalName;
        file_put_contents($path . '/' . $fileName, $content);

        $talep->attachments()->create([
            'dosya_adi' => $monthFolder . '/' . $fileName,
            'orijinal_adi' => $name,
        ]);
    }

}
