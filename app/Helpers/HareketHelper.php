<?php

namespace App\Helpers;

use App\Models\Hareket;
use App\Models\Transfer;

class HareketHelper
{
    /**
     * Tek bir hareket oluşturur
     */
    public static function create($model, array $data)
    {
        return $model->harekets()->create([
            'aciklama'   => $data['aciklama'] ?? null,
            'tarih'      => $data['tarih'],
            'post_id'    => $data['post_id'] ?? 0,
            'amount'     => abs($data['amount']), // ZORUNLU POZİTİF
            'ab'         => $data['ab'],           // 1 = IN, 2 = OUT
            'kur_id'     => $data['kur_id'] ?? 1,
            'acente_id'  => $data['acente_id'] ?? null,
            'urun_id'    => $data['urun_id'] ?? null,
            'adet'       => $data['adet'] ?? null,
        ]);
    }

    /**
     * Offset için çift hareket
     */
    public static function createOffset($offset, array $data)
    {
        $amount = abs($data['amount']);

        // OUT
        $offset->harekets()->create([
            'aciklama'  => $data['aciklama'],
            'tarih'     => $data['tarih'],
            'post_id'   => 0,
            'amount'    => $amount,
            'ab'        => 2,
            'kur_id'    => $data['kur_id'],
            'acente_id' => $data['from'],
        ]);

        // IN
        $offset->harekets()->create([
            'aciklama'  => $data['aciklama'],
            'tarih'     => $data['tarih'],
            'post_id'   => 0,
            'amount'    => $amount,
            'ab'        => 1,
            'kur_id'    => $data['kur_id'],
            'acente_id' => $data['to'],
        ]);
    }

    /**
     * Transfer hareketi yoksa olusturur; varsa aynisini tekrar yaratmaz.
     */
    public static function ensureTransferMovement($transfer, array $data = [])
    {
        if (!$transfer instanceof Transfer) {
            return null;
        }

        $existing = $transfer->harekets()->first();
        if ($existing) {
            return $existing;
        }

        return self::create($transfer, [
            'aciklama'  => $data['aciklama'] ?? 'Transfer created (auto-repaired missing movement)',
            'tarih'     => $data['tarih'] ?? $transfer->start_date ?? now()->toDateString(),
            'post_id'   => $data['post_id'] ?? $transfer->post_id ?? 0,
            'amount'    => $data['amount'] ?? 0,
            'ab'        => $data['ab'] ?? 2,
            'kur_id'    => $data['kur_id'] ?? 1,
            'acente_id' => $data['acente_id'] ?? $transfer->driver_id ?? null,
            'urun_id'   => $data['urun_id'] ?? null,
            'adet'      => $data['adet'] ?? null,
        ]);
    }

    /**
     * Dosyadaki transferlerin eksik hareketlerini tamamlar.
     */
    public static function ensurePostTransferMovements($post): int
    {
        if (!$post || !$post->id) {
            return 0;
        }

        $created = 0;
        $transfers = Transfer::where('post_id', $post->id)
            ->where(function ($query) {
                $query->whereNull('conge')->orWhere('conge', 0);
            })
            ->get();

        foreach ($transfers as $transfer) {
            if ($transfer->harekets()->exists()) {
                continue;
            }

            self::ensureTransferMovement($transfer, [
                'post_id'   => $post->id,
                'tarih'     => $transfer->start_date,
                'acente_id' => $transfer->driver_id,
            ]);
            $created++;
        }

        return $created;
    }
}
