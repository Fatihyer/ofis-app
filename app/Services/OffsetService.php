<?php
namespace App\Services;

use App\Models\Offset;

class OffsetService
{
    public static function create(
        int $a,
        int $b,
        float $amount,
        int $kur,
        string $aciklama,
        string $tarih,
        int $postId = 0,
        ?int $sirketId = null
    ): Offset {
        if ($amount == 0) {
            throw new \Exception('Amount cannot be zero');
        }

        $offsetAciklama = mb_substr($aciklama, 0, 255);

        $offset = Offset::create([
            'sirket_id' => $sirketId,
            'tarih' => $tarih,
            'a_acente_id' => $amount < 0 ? $a : $b,
            'b_acente_id' => $amount < 0 ? $b : $a,
            'aciklama' => $offsetAciklama,
        ]);

        $offset->harekets()->create([
            'sirket_id' => $sirketId,
            'aciklama' => $aciklama,
            'tarih' => $tarih,
            'post_id' => $postId,
            'amount' => abs($amount),
            'ab' => 1,
            'kur_id' => $kur,
            'acente_id' => $amount < 0 ? $a : $b,
            'offset_id' => $offset->id,
        ]);

        $offset->harekets()->create([
            'sirket_id' => $sirketId,
            'aciklama' => $aciklama,
            'tarih' => $tarih,
            'post_id' => $postId,
            'amount' => abs($amount),
            'ab' => 2,
            'kur_id' => $kur,
            'acente_id' => $amount < 0 ? $b : $a,
            'offset_id' => $offset->id,
        ]);

        return $offset;
    }

    public static function createFromBank(
        int $a,
        int $b,
        float $amount,
        int $kur,
        string $aciklama,
        string $tarih,
        int $postId = 0,
        ?int $sirketId = null
    ): Offset {
        if ($amount == 0) {
            throw new \Exception('Amount cannot be zero');
        }

        $offsetAciklama = mb_substr($aciklama, 0, 255);

        $offset = Offset::create([
            'sirket_id' => $sirketId,
            'tarih' => $tarih,
            'a_acente_id' => $amount < 0 ? $a : $b,
            'b_acente_id' => $amount < 0 ? $b : $a,
            'aciklama' => $offsetAciklama,
        ]);

        $offset->harekets()->create([
            'sirket_id' => $sirketId,
            'aciklama' => $aciklama,
            'tarih' => $tarih,
            'post_id' => $postId,
            'amount' => abs($amount),
            'ab' => 2,
            'kur_id' => $kur,
            'acente_id' => $amount < 0 ? $a : $b,
            'offset_id' => $offset->id,
        ]);

        $offset->harekets()->create([
            'sirket_id' => $sirketId,
            'aciklama' => $aciklama,
            'tarih' => $tarih,
            'post_id' => $postId,
            'amount' => abs($amount),
            'ab' => 1,
            'kur_id' => $kur,
            'acente_id' => $amount < 0 ? $b : $a,
            'offset_id' => $offset->id,
        ]);

        return $offset;
    }
}
