<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppLeadAnalyzerInterface;
use App\DTO\LeadAnalysisResult;
use App\Models\WhatsAppGroupMessage;
use Carbon\Carbon;

class KeywordLeadAnalyzer implements WhatsAppLeadAnalyzerInterface
{
    public function analyze(WhatsAppGroupMessage $message): LeadAnalysisResult
    {
        $contextMessages = $this->contextMessages($message);
        $text = trim($contextMessages->pluck('body')->filter()->implode("\n"));
        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return new LeadAnalysisResult(false, 0, language: null, summary: null);
        }

        $score = $this->score($normalized);
        $vehicleType = $this->vehicleType($normalized);
        $passengerCount = $this->passengerCount($normalized);
        $numberOfVehicles = $this->numberOfVehicles($normalized, $vehicleType);
        $serviceDate = $this->serviceDate($text);
        $serviceTime = $this->serviceTime($text);
        $pickupLocation = $this->pickupLocation($text);
        $dropoffLocation = $this->dropoffLocation($text);
        $language = $this->language($normalized);

        if ($serviceDate) {
            $score += 15;
        }
        if ($serviceTime) {
            $score += 10;
        }
        if ($pickupLocation) {
            $score += 10;
        }
        if ($dropoffLocation) {
            $score += 10;
        }

        $score = min(100, $score);

        return new LeadAnalysisResult(
            isLead: $score >= 30,
            score: $score,
            confidence: $score > 0 ? round($score / 100, 2) : null,
            passengerCount: $passengerCount,
            numberOfVehicles: $numberOfVehicles,
            vehicleType: $vehicleType,
            serviceDate: $serviceDate,
            serviceTime: $serviceTime,
            pickupLocation: $pickupLocation,
            dropoffLocation: $dropoffLocation,
            requestType: $vehicleType ? 'transport' : null,
            language: $language,
            summary: $this->summary($message, $text, $vehicleType, $passengerCount, $serviceDate, $serviceTime, $pickupLocation, $dropoffLocation),
            contextMessageIds: $contextMessages->pluck('id')->all(),
        );
    }

    private function contextMessages(WhatsAppGroupMessage $message)
    {
        if (!$message->sender_external_id || !$message->sent_at) {
            return collect([$message]);
        }

        return WhatsAppGroupMessage::query()
            ->where('whatsapp_group_id', $message->whatsapp_group_id)
            ->where('sender_external_id', $message->sender_external_id)
            ->where('direction', WhatsAppGroupMessage::DIRECTION_INCOMING)
            ->whereBetween('sent_at', [$message->sent_at->copy()->subMinutes(5), $message->sent_at])
            ->orderBy('sent_at')
            ->get();
    }

    private function score(string $text): int
    {
        $rules = [
            30 => ['recherche autocar', 'besoin autocar', 'looking for coach', 'need coach', 'looking for bus', 'besoin bus'],
            20 => ['mise a disposition', 'mise à disposition', ' mad '],
            15 => ['autocar', 'coach', 'bus', 'minibus', 'sprinter', 'van', 'devis', 'tarif', 'prix', 'availability', 'disponibilite', 'disponibilité', 'transfer', 'transfert', 'navette', 'transport'],
            10 => ['pax', 'passager', 'passagers', 'personnes', 'places', 'passenger', 'passengers', 'pickup', 'dropoff', 'depart', 'départ', 'arrivee', 'arrivée'],
        ];

        $score = 0;
        foreach ($rules as $points => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $this->normalize($keyword))) {
                    $score += $points;
                }
            }
        }

        if (preg_match('/\b(recherche|cherche|besoin|urgent|need|looking for|ariyorum|lazim|gerekli|acil)\b/u', $text)) {
            $score += 15;
        }

        return min(100, $score);
    }

    private function vehicleType(string $text): ?string
    {
        foreach (['autocar', 'coach', 'bus', 'minibus', 'sprinter', 'van'] as $vehicle) {
            if (str_contains($text, $vehicle)) {
                return $vehicle === 'coach' || $vehicle === 'bus' ? 'autocar' : $vehicle;
            }
        }

        return null;
    }

    private function passengerCount(string $text): ?int
    {
        if (preg_match('/(\d{1,3})\s*(pax|passagers?|personnes|places|passengers?|kisilik|kişi|yolcu)/u', $text, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/(pax|passagers?|personnes|places|passengers?)\s*(\d{1,3})/u', $text, $m)) {
            return (int) $m[2];
        }

        return null;
    }

    private function numberOfVehicles(string $text, ?string $vehicleType): ?int
    {
        if (!$vehicleType) {
            return null;
        }

        if (preg_match('/(\d{1,2})\s*(autocars?|coach|bus|minibus|sprinters?|vans?)/u', $text, $m)) {
            return (int) $m[1];
        }

        return 1;
    }

    private function serviceDate(string $text): ?string
    {
        if (preg_match('/\b(\d{1,2})[\/\.-](\d{1,2})(?:[\/\.-](\d{2,4}))?\b/u', $text, $m)) {
            $year = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) now()->format('Y');
            if ($year < 100) {
                $year += 2000;
            }
            return Carbon::createFromDate($year, (int) $m[2], (int) $m[1])->format('Y-m-d');
        }

        $months = [
            'janvier' => 1, 'fevrier' => 2, 'février' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8, 'août' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12, 'décembre' => 12,
        ];

        foreach ($months as $month => $number) {
            if (preg_match('/\b(\d{1,2})\s+' . preg_quote($month, '/') . '\b/iu', $text, $m)) {
                $year = (int) now()->format('Y');
                $date = Carbon::createFromDate($year, $number, (int) $m[1]);
                if ($date->isPast()) {
                    $date->addYear();
                }
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function serviceTime(string $text): ?string
    {
        if (preg_match('/\b(\d{1,2})[h:](\d{2})\b/u', $text, $m)) {
            return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
        }

        return null;
    }

    private function pickupLocation(string $text): ?string
    {
        return $this->locationAfter($text, ['depart', 'départ', 'pickup', 'from', 'de']);
    }

    private function dropoffLocation(string $text): ?string
    {
        return $this->locationAfter($text, ['destination', 'arrivee', 'arrivée', 'dropoff', 'to', 'vers']);
    }

    private function locationAfter(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            if (preg_match('/\b' . preg_quote($label, '/') . '\b\s*:?\s*([A-ZÀ-ÿ0-9][^\n,.;]{1,60})/iu', $text, $m)) {
                return trim($m[1]);
            }
        }

        if (preg_match('/\b(CDG|ORY|BVA|Paris|Disneyland?|Lille)\b/u', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function language(string $text): ?string
    {
        if (preg_match('/\b(recherche|bonjour|besoin|devis|tarif|départ|arrivée)\b/u', $text)) {
            return 'fr';
        }
        if (preg_match('/\b(looking for|need|pickup|dropoff|quote|passengers)\b/u', $text)) {
            return 'en';
        }
        if (preg_match('/\b(ariyorum|lazim|gerekli|otobus|minibus|sofor|teklif)\b/u', $text)) {
            return 'tr';
        }

        return null;
    }

    private function summary(WhatsAppGroupMessage $message, string $text, ?string $vehicleType, ?int $pax, ?string $date, ?string $time, ?string $pickup, ?string $dropoff): string
    {
        $parts = array_filter([
            $vehicleType ? ucfirst($vehicleType) : null,
            $pax ? $pax . ' pax' : null,
            $date,
            $time ? substr($time, 0, 5) : null,
            $pickup && $dropoff ? $pickup . ' -> ' . $dropoff : null,
        ]);

        return $parts ? implode(' · ', $parts) : mb_substr(trim($text), 0, 240);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $text);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $converted !== false ? $converted : $text;

        return preg_replace('/\s+/', ' ', trim($text));
    }
}
