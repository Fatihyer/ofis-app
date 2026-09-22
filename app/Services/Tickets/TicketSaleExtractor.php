<?php

namespace App\Services\Tickets;

use App\Models\WhatsAppGroupMessage;

/**
 * Bilet gruplarindaki serbest metin mesajlarindan satis kaydi cikarir.
 *
 * Yakalanan kalip: "<adet> <urun> > <musteri>"
 *   42 bateaux mouches > ege rehber/shine
 *   25 adult 3 child parisiens > arda adonis
 *   33 mouche - yannis
 *   49 bilet yasin akca / Simurg
 */
class TicketSaleExtractor
{
    private const PRODUCT = '/(bateaux\s+parisiens?|bateaux\s+mouches?|billets?\s+mouches?|mouches?|parisiens?)/iu';

    private const NUMBERS = '/(\d{1,3})\s*(adults?|adultes?|yetişkin|yetiskin|child|chld|çocuk|cocuk)?/iu';

    private const CUSTOMER = '/(?:>|:|--+>|-\s)\s*(?P<who>[^>:\n]{2,60})\s*$/u';

    /** Satis olmayan, stok/fiyat/iptal bildiren satirlar */
    private const SKIP = [
        'alindi', 'alındı', 'aliniyor', 'alınıyor', 'aliyoruz', 'alıyoruz', 'alacagiz', 'alacağız',
        'stok', 'stock', 'kullanilmadi', 'kullanılmadı', 'kullanilmayan', 'kullanılmayan',
        'iptal', 'fiyat', 'sifre', 'şifre', 'lazim', 'lazım', 'teklif',
    ];

    /** Varsayilan satis fiyatlari (gruptaki bilgilendirme mesajlarindan) */
    private const PRICES = [
        'mouches' => ['adult' => 10.0, 'child' => 10.0],
        'parisiens' => ['adult' => 11.0, 'child' => 8.0],
    ];

    /** Musteriye ozel anlasilmis fiyatlar */
    private const CUSTOMER_PRICES = [
        'selcuk buyukkok' => ['parisiens' => ['adult' => 9.0, 'child' => 8.0]],
        'mehmet genc' => ['mouches' => ['adult' => 8.0, 'child' => 8.0]],
    ];

    /** Ayni musterinin farkli yazimlari */
    private const ALIASES = [
        'merve safi ozmen' => 'merve safi',
        'merve ozmen' => 'merve safi',
        'ismail mammadov' => 'ismail mamadov',
        'mamadov' => 'ismail mamadov',
        'mamadou' => 'ismail mamadov',
        'mamadow' => 'ismail mamadov',
        'nts travel' => 'nts',
        'atlas internationa' => 'atlas international',
        'glob dmc' => 'glob dmc',
        'globdmc' => 'glob dmc',
        'gaby' => 'gabriel',
        'gabriel kyrgias' => 'gabriel',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extract(WhatsAppGroupMessage $message): array
    {
        $rows = [];

        foreach (preg_split('/\r?\n/', (string) $message->body) as $line) {
            $line = trim($line);

            if ($line === '' || $this->shouldSkip($line)) {
                continue;
            }

            if (!preg_match(self::PRODUCT, $line, $productMatch, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            if (!preg_match(self::CUSTOMER, $line, $customerMatch)) {
                continue;
            }

            $product = stripos($productMatch[1][0], 'parisien') !== false ? 'parisiens' : 'mouches';
            $head = substr($line, 0, $productMatch[1][1]); // urun kelimesinden onceki kisim

            [$adult, $child] = $this->quantities($head);

            if ($adult + $child === 0) {
                continue;
            }

            $customerRaw = trim($customerMatch['who']);
            $customerKey = $this->customerKey($customerRaw);
            $prices = $this->prices($product, $customerKey);

            $rows[] = [
                'whatsapp_group_id' => $message->whatsapp_group_id,
                'whatsapp_group_message_id' => $message->id,
                'sale_date' => ($message->sent_at ?? $message->created_at)->toDateString(),
                'product' => $product,
                'qty_adult' => $adult,
                'qty_child' => $child,
                'unit_price_adult' => $prices['adult'],
                'unit_price_child' => $prices['child'],
                'amount' => round($adult * $prices['adult'] + $child * $prices['child'], 2),
                'customer_raw' => mb_substr($customerRaw, 0, 190),
                'customer_key' => $customerKey,
                'source_line' => mb_substr($line, 0, 500),
                'source_hash' => sha1($line),
            ];
        }

        return $rows;
    }

    public function customerKey(string $value): string
    {
        // Turkce buyuk harfler once sadelestirilir: mb_strtolower('İ') birlesik nokta uretir
        $key = strtr(trim($value), ['İ' => 'I', 'I' => 'I', 'Ş' => 'S', 'Ğ' => 'G', 'Ü' => 'U', 'Ö' => 'O', 'Ç' => 'C']);
        $key = mb_strtolower($key, 'UTF-8');
        $key = strtr($key, ['ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c', 'â' => 'a', 'î' => 'i', 'é' => 'e', 'è' => 'e']);
        $key = preg_replace('/\b(rehber|bey|bay|hanim|mr|mme|sn|le\s+\d+\s+\w+)\b/u', ' ', $key);
        $key = preg_replace('/[^a-z0-9 ]/u', ' ', $key);
        $key = trim(preg_replace('/\s+/', ' ', $key));

        foreach (self::ALIASES as $needle => $canonical) {
            // kelime sinirinda eslesme: "moments travel" icindeki "nts travel" sayilmaz
            if ($key !== '' && preg_match('/\\b' . preg_quote($needle, '/') . '\\b/u', $key)) {
                return $canonical;
            }
        }

        return $key !== '' ? $key : 'bilinmiyor';
    }

    private function quantities(string $head): array
    {
        $adult = 0;
        $child = 0;

        if (preg_match_all(self::NUMBERS, $head, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (($match[1] ?? '') === '') {
                    continue;
                }

                $tag = mb_strtolower($match[2] ?? '', 'UTF-8');
                $isChild = $tag !== '' && (str_starts_with($tag, 'child') || str_starts_with($tag, 'chld') || str_starts_with($tag, 'ço') || str_starts_with($tag, 'co'));

                if ($isChild) {
                    $child += (int) $match[1];
                } else {
                    $adult += (int) $match[1];
                }
            }
        }

        return [$adult, $child];
    }

    private function prices(string $product, string $customerKey): array
    {
        foreach (self::CUSTOMER_PRICES as $needle => $productPrices) {
            if (str_contains($customerKey, $needle) && isset($productPrices[$product])) {
                return $productPrices[$product];
            }
        }

        return self::PRICES[$product];
    }

    private function shouldSkip(string $line): bool
    {
        $low = mb_strtolower($line, 'UTF-8');

        foreach (self::SKIP as $needle) {
            if (str_contains($low, $needle)) {
                return true;
            }
        }

        return false;
    }
}
