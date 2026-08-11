<?php

namespace App\Services;

use App\Helpers\HareketHelper;
use App\Models\Hareket;
use App\Models\Transfer;
use App\Models\TransferDriverHareket;
use Illuminate\Support\Facades\Schema;

class TransferDriverAssignmentService
{
    public function secondDriverColumnExists(): bool
    {
        return Schema::hasColumn('transfers', 'second_driver_id');
    }

    public function linkTableExists(): bool
    {
        return Schema::hasTable('transfer_driver_harekets');
    }

    public function syncSecondDriver(Transfer $transfer, ?int $secondDriverId, ?int $userId = null, $date = null): void
    {
        if (!$this->secondDriverColumnExists()) {
            return;
        }

        $oldSecondDriverId = (int) ($transfer->second_driver_id ?? 0);
        $newSecondDriverId = (int) ($secondDriverId ?: 0);

        $transfer->second_driver_id = $newSecondDriverId ?: null;
        if ($oldSecondDriverId !== $newSecondDriverId) {
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_at')) {
                $transfer->second_driver_app_confirmed_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_confirmed_by')) {
                $transfer->second_driver_app_confirmed_by = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_refused_at')) {
                $transfer->second_driver_app_refused_at = null;
            }
            if (Schema::hasColumn('transfers', 'second_driver_app_refused_by')) {
                $transfer->second_driver_app_refused_by = null;
            }
        }
        $transfer->save();

        if (!$this->linkTableExists()) {
            return;
        }

        if ($newSecondDriverId) {
            $this->upsertSecondDriverHareket($transfer, $newSecondDriverId, $userId, $date ?: $transfer->start_date);
            return;
        }

        $this->removeSecondDriverHareketIfEmpty($transfer, $userId);
    }

    private function upsertSecondDriverHareket(Transfer $transfer, int $driverId, ?int $userId, $date): void
    {
        $link = TransferDriverHareket::where('transfer_id', $transfer->id)
            ->where('driver_role', 'second_driver')
            ->first();

        $payload = [
            'tarih' => $date ?: $transfer->start_date,
            'post_id' => $transfer->post_id,
            'acente_id' => $driverId,
        ];

        $hareket = $link && $link->hareket_id ? Hareket::find($link->hareket_id) : null;

        if ($hareket) {
            $hareket->update($payload);
        } else {
            $hareket = HareketHelper::create($transfer, [
                'aciklama' => 'Double équipage - Transfert #' . $transfer->id,
                'tarih' => $payload['tarih'],
                'post_id' => $payload['post_id'],
                'amount' => 0,
                'ab' => 2,
                'kur_id' => 1,
                'acente_id' => $driverId,
            ]);
        }

        TransferDriverHareket::updateOrCreate(
            ['transfer_id' => $transfer->id, 'driver_role' => 'second_driver'],
            [
                'driver_id' => $driverId,
                'hareket_id' => $hareket->id,
                'created_by' => $link->created_by ?? $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function removeSecondDriverHareketIfEmpty(Transfer $transfer, ?int $userId): void
    {
        $link = TransferDriverHareket::where('transfer_id', $transfer->id)
            ->where('driver_role', 'second_driver')
            ->first();

        if (!$link) {
            return;
        }

        $hareket = $link->hareket_id ? Hareket::find($link->hareket_id) : null;
        if ($hareket && (float) $hareket->amount == 0.0 && !$hareket->payment_id && !$hareket->offset_id) {
            $hareket->delete();
            $link->delete();
            return;
        }

        $link->update(['driver_id' => null, 'updated_by' => $userId]);
    }
}
