<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use App\Models\WhatsappTransferDraft;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappTransferDraftService
{
    public function createOrUpdateDraft(WhatsappMessage $message): WhatsappTransferDraft
    {
        $parsed = $this->parseMessage($message->body ?? '');

        $draft = WhatsappTransferDraft::updateOrCreate(
            ['whatsapp_message_id' => $message->id],
            [
                'draft_type' => $parsed['draft_type'] ?? 'transfer_request',
                'parsed_json' => $parsed,
                'parsed_xml' => $parsed['xml'] ?? null,
                'confidence' => $this->normalizeConfidence($parsed['confidence'] ?? null),
                'status' => ($parsed['status'] ?? null) === 'error' ? 'error' : 'pending',
            ]
        );

        $message->update(['status' => $draft->status === 'error' ? 'parse_error' : 'drafted']);

        return $draft;
    }

    private function parseMessage(string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return [
                'status' => 'error',
                'error' => 'Message vide.',
                'confidence' => 0,
            ];
        }

        if (!config('services.openai.key')) {
            return $this->fallbackParse($body, 'Configuration OpenAI manquante.');
        }

        $xmlModelPath = resource_path('prompts/talep_xml_model.xml');
        $xmlModel = file_exists($xmlModelPath) ? file_get_contents($xmlModelPath) : '';
        $today = now('Europe/Paris')->toDateString();

        try {
            $response = Http::withToken(config('services.openai.key'))
                ->acceptJson()
                ->timeout(45)
                ->post(rtrim(config('services.openai.base_url'), '/') . '/chat/completions', [
                    'model' => trim(config('services.openai.model')),
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu lis des messages WhatsApp de transport touristique pour ParisVia et tu prepares un brouillon AVANT creation/modification. Reponds uniquement en JSON valide. Date du jour: {$today}. Langue interface: francais. Ne jamais inventer les informations inconnues: mets null.\n\nRegles importantes:\n- Si le message demande un tarif, un devis, un prix TTC, des conditions ou photos, draft_type doit etre demande_devis et action create_demande, pas unknown.\n- Le prix manquant n'est PAS une erreur quand le client demande un tarif: mets prix null et ajoute demande_prix=true.\n- Si le message contient un aller + un retour, ou plusieurs horaires de retour/navette, remplis operations avec plusieurs objets. Ne mets pas les retours comme simples etapes sauf si ce sont de vrais arrets intermediaires.\n- Pour une navette en roulement, mets type_service Navette, nombre_navettes si indique, horaires_retour comme tableau, et une note claire.\n- confidence doit toujours etre numerique entre 0 et 100.\n\nStructure attendue: {draft_type, action, confidence, needs_review, missing_fields, demande_prix, dossier, transfer, operations, possible_existing_transfer_id, question_before_edit, xml}. action vaut create_demande, create_dossier, create_transfer, update_transfer ou unknown. dossier contient client, agence, titre, date_debut, date_fin, pax, notes. transfer contient date, heure_depart, heure_fin, lieu_depart, lieu_arrivee, etapes, pax, chauffeur, vehicule, type_service, prix, notes. operations est un tableau de services distincts avec sens aller/retour/navette, date, heure_depart, heure_fin, lieu_depart, lieu_arrivee, pax, type_service, nombre_navettes, horaires_retour, notes. xml doit respecter si possible ce modele:\n" . $xmlModel,
                        ],
                        [
                            'role' => 'user',
                            'content' => $body,
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('WhatsApp OpenAI parse failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackParse($body, 'La conversion OpenAI a echoue.');
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content'));
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $decoded = json_decode($content, true);

            if (!is_array($decoded)) {
                return $this->fallbackParse($body, 'La reponse OpenAI nest pas un JSON valide.');
            }

            $decoded['raw_message'] = $body;
            $decoded['parsed_at'] = now('Europe/Paris')->toDateTimeString();

            return $decoded;
        } catch (\Throwable $e) {
            Log::error('WhatsApp OpenAI parse exception', ['error' => $e->getMessage()]);
            return $this->fallbackParse($body, 'Erreur OpenAI: ' . $e->getMessage());
        }
    }

    private function normalizeConfidence($confidence): ?float
    {
        if ($confidence === null || $confidence === '') {
            return null;
        }

        if (!is_numeric($confidence)) {
            return null;
        }

        $value = (float) $confidence;
        return $value <= 1 ? round($value * 100, 2) : round($value, 2);
    }

    private function fallbackParse(string $body, string $error): array
    {
        return [
            'status' => 'error',
            'draft_type' => 'transfer_request',
            'action' => 'unknown',
            'confidence' => 0,
            'needs_review' => true,
            'missing_fields' => ['analyse automatique'],
            'error' => $error,
            'raw_message' => $body,
            'dossier' => [],
            'transfer' => [],
            'xml' => '<demande><message>' . htmlspecialchars($body, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</message></demande>',
        ];
    }
}
