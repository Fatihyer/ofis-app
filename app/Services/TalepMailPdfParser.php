<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class TalepMailPdfParser
{
    public function parse(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('PDF introuvable.');
        }

        $text = $this->pdftotext($path);
        $ocrUsed = false;

        if (mb_strlen(trim($text)) < 80) {
            $text = $this->ocrPdf($path);
            $ocrUsed = true;
        }

        $text = $this->normalizeText($text);

        return [
            'text' => $text,
            'ocr_used' => $ocrUsed,
            'json' => $this->parseBilletCollectif($text),
        ];
    }

    private function pdftotext(string $path): string
    {
        [$code, $output] = $this->runCommand([
            'pdftotext',
            '-layout',
            '-enc',
            'UTF-8',
            $path,
            '-',
        ]);

        return $code === 0 ? $output : '';
    }

    private function ocrPdf(string $path): string
    {
        $pages = max(1, min(12, $this->pdfPageCount($path)));
        $tempDir = sys_get_temp_dir() . '/talep_pdf_ocr_' . uniqid('', true);
        if (!mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Dossier OCR temporaire impossible.');
        }

        try {
            [$renderCode, $renderOutput] = $this->runCommand([
                'pdftoppm',
                '-png',
                '-r',
                '200',
                '-f',
                '1',
                '-l',
                (string) $pages,
                $path,
                $tempDir . '/page',
            ], 120);

            if ($renderCode !== 0) {
                throw new RuntimeException('Conversion PDF image impossible: ' . Str::limit($renderOutput, 300, ''));
            }

            $textParts = [];
            foreach (glob($tempDir . '/page-*.png') ?: [] as $imagePath) {
                [$ocrCode, $ocrOutput] = $this->runCommand([
                    'tesseract',
                    $imagePath,
                    'stdout',
                    '-l',
                    'fra+eng',
                    '--psm',
                    '6',
                ], 120);

                if ($ocrCode === 0 && trim($ocrOutput) !== '') {
                    $textParts[] = $ocrOutput;
                }
            }

            return implode("\n\n", $textParts);
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    private function pdfPageCount(string $path): int
    {
        [$code, $output] = $this->runCommand(['pdfinfo', $path]);
        if ($code !== 0 || !preg_match('/^Pages:\s*(\d+)/mi', $output, $matches)) {
            return 1;
        }

        return (int) $matches[1];
    }

    private function parseBilletCollectif(string $text): array
    {
        $operations = [];
        $chunks = preg_split('/(?=Dossier\s*n[°o]\s*\d+)/iu', $text) ?: [];

        foreach ($chunks as $chunk) {
            if (!preg_match('/Dossier\s*n[°o]\s*(\d+)/iu', $chunk, $dossierMatch)) {
                continue;
            }

            $dateEdition = $this->firstMatch('/Date d[’\']édition\s*:\s*([^\n]+)/iu', $chunk);
            $conducteur = $this->firstMatch('/Conducteur\s*1\s*:?\s*([^\n]+?)(?:\s+Véhicule|\n)/iu', $chunk);
            $destination = $this->firstMatch('/Destination et itinéraire\s*:?\s*([^\n]+)/iu', $chunk);

            if (!$dateEdition && !$conducteur && !$destination) {
                continue;
            }

            $operations[] = [
                'document_type' => Str::contains(Str::lower($chunk), 'billet collectif') ? 'billet_collectif' : 'pdf',
                'dossier_no' => $dossierMatch[1],
                'date_edition' => $dateEdition,
                'conducteur' => $conducteur,
                'vehicule' => $this->firstMatch('/Véhicule\s*:?\s*([^\n]+)/iu', $chunk),
                'pax' => $this->firstMatch('/Nb Passagers prévus\s*:?\s*(\d+)/iu', $chunk),
                'client' => $this->firstMatch('/Client\s*:\s*\n?\s*([^\n]+)/iu', $chunk),
                'destination' => $destination,
                'presentation_time' => $this->firstMatch('/Heure de présentation\s*:?\s*\|?\s*(\d{1,2}[:h]\d{2})/iu', $chunk),
                'start_time' => $this->firstMatch('/Heure de départ\s*:?\s*\|?\s*(\d{1,2}[:h]\d{2})/iu', $chunk),
                'return_time' => $this->firstMatch('/Heure de retour\s*:?\s*\|?\s*(\d{1,2}[:h]\d{2})/iu', $chunk),
                'raw_preview' => Str::limit(trim($chunk), 1500, ''),
            ];
        }

        return [
            'parser' => 'billet_collectif_v1',
            'operations' => $operations,
        ];
    }

    private function firstMatch(string $pattern, string $text): ?string
    {
        if (!preg_match($pattern, $text, $matches)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', $matches[1]) ?: $matches[1]);

        return $value !== '' ? $value : null;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace("\r", "\n", $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?: $text;
        $text = preg_replace("/\n{4,}/", "\n\n\n", $text) ?: $text;

        return trim($text);
    }

    private function runCommand(array $command, int $timeoutSeconds = 60): array
    {
        $cmd = 'timeout ' . escapeshellarg($timeoutSeconds . 's') . ' ' . implode(' ', array_map('escapeshellarg', $command)) . ' 2>&1';
        $lines = [];
        exec($cmd, $lines, $code);

        return [$code, trim(implode("\n", $lines))];
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . '/*') ?: [] as $file) {
            is_dir($file) ? $this->removeDirectory($file) : @unlink($file);
        }

        @rmdir($path);
    }
}
