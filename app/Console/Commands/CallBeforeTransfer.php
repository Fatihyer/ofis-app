<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transfer;
use App\Services\TwilioService;
use Carbon\Carbon;
use App\Models\Option;
use App\Services\TransferEnRouteCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendSmsJob;
use App\Jobs\MakeCallJob;


class CallBeforeTransfer extends Command
{
    protected $signature = 'transfer:call';
    protected $description = 'SMS ile onay al, onay gelmezse arama yap';

    public function handle(TwilioService $twilio, TransferEnRouteCalculator $enRouteCalculator)
    {
        // 🔒 Redis Lock – Çakışmayı önler
        if (!Cache::add('call_before_transfer_lock', true, 50)) {
            Log::warning('⏳ transfer:call zaten çalışıyor, yeni tetikleme iptal edildi.');
            return 0;
        }

        try {
            $now = Carbon::now('Europe/Paris');
            $congeIds = explode(',', Option::where('name', 'conge')->value('value'));
            $smsLeadMinutes = (int) (Option::where('name', 'twilio_sms_before_ofis_start_minutes')->value('value') ?? 30);
            $callLeadMinutes = (int) (Option::where('name', 'twilio_call_before_ofis_start_minutes')->value('value') ?? 20);
            $smsLeadMinutes = $smsLeadMinutes > 0 ? $smsLeadMinutes : 30;
            $callLeadMinutes = $callLeadMinutes > 0 ? $callLeadMinutes : 20;

            $this->fillMissingOfisStart($now, $congeIds, $enRouteCalculator);

            $transfers = Transfer::with('driver')
                ->whereNull('called_at')
                ->whereNotNull('ofis_start')
                ->whereNotIn('servicetype_id', $congeIds)
                ->where('call_attempted', 0)
                ->whereBetween('ofis_start', [
                    $now->copy()->subMinutes($callLeadMinutes),
                    $now->copy()->addMinutes($smsLeadMinutes),
                ])
                ->get();

            Log::info("🔍 CallBeforeTransfer başladı...");
            Log::info('🎯 Toplam uygun transfer: ' . $transfers->count() . " | Base: ofis_start | SMS -{$smsLeadMinutes} min | Appel -{$callLeadMinutes} min");

            foreach ($transfers as $transfer) {
                $phone = $transfer->driver?->tel;
                $ofisStart = Carbon::parse($transfer->ofis_start, 'Europe/Paris');
                $smsAt = $ofisStart->copy()->subMinutes($smsLeadMinutes);
                $callAt = $ofisStart->copy()->subMinutes($callLeadMinutes);

                if (!$phone) {
                    $this->warn("⚠️ Pas de numéro pour le transfert ID {$transfer->id}");
                    continue;
                }

                if ($now->lt($smsAt)) {
                    continue;
                }

                // 🔐 Güvenli token üret
                $token = sha1($transfer->id . $phone);
                $confirmLink = url("/driver/confirm/{$transfer->id}?token={$token}");

                // 1️⃣ ofis_start - 30 dk: önce SMS gönder
                if (!$transfer->sms_sent_at) {
                    $heureEnRoute = $ofisStart->format('H:i');
                    $heureService = Carbon::parse($transfer->start_date, 'Europe/Paris')->format('H:i');
                    $smsText = "Paris Via: en route prévu à {$heureEnRoute} pour le transfert à {$heureService}. Confirmez: {$confirmLink}";

                   
                   SendSmsJob::dispatch($phone, $smsText)->onQueue('default');

                    $transfer->sms_sent_at = now('Europe/Paris');
                    $transfer->save();

                    Log::info("📩 SMS envoyé au chauffeur {$phone} pour transfert {$transfer->id} | ofis_start {$ofisStart->format('Y-m-d H:i')}");
                    continue; // Arama yapılmaz, onay beklenir
                }

                // 2️⃣ ofis_start - 20 dk: SMS sonrası onay gelmediyse ara
                if (
                        !$transfer->called_at &&
                        !$transfer->driver_confirmed_at &&
                        !$transfer->driver_app_confirmed_at &&
                        $transfer->sms_sent_at &&
                        $now->gte($callAt) &&
                        Carbon::parse($transfer->sms_sent_at, 'Europe/Paris')
                            ->lessThanOrEqualTo($now->copy()->subMinutes(max(1, $smsLeadMinutes - $callLeadMinutes)))
                    ) {
                      $url = url('/api/twilio-message?id=' . $transfer->id);

                        MakeCallJob::dispatch($phone, $url)
                            ->onQueue('default');



                   $transfer->called_at = now('Europe/Paris');
                    $transfer->call_attempted = 1;
                    $transfer->save();

                    Log::info("📞 Arama yapıldı: {$phone} | Transfer ID: {$transfer->id} | ofis_start {$ofisStart->format('Y-m-d H:i')}");
                }
            }

        } catch (\Throwable $e) {
            Log::error('❌ CallBeforeTransfer hata:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            // 🔓 Lock serbest bırakılır
            Cache::forget('call_before_transfer_lock');
        }

        return 0;
    }

    private function fillMissingOfisStart(Carbon $now, array $congeIds, TransferEnRouteCalculator $enRouteCalculator): void
    {
        $fallbackMinutes = (int) (Option::where('name', 'enroute_minimum_minutes')->value('value') ?? 120);
        $fallbackMinutes = $fallbackMinutes > 0 ? $fallbackMinutes : 120;

        Transfer::with('trajets')
            ->whereNull('ofis_start')
            ->whereNotIn('servicetype_id', $congeIds)
            ->whereBetween('start_date', [
                $now->copy()->subMinutes(30),
                $now->copy()->addHours(12),
            ])
            ->orderBy('start_date')
            ->limit(30)
            ->get()
            ->each(function (Transfer $transfer) use ($enRouteCalculator, $fallbackMinutes) {
                $trajets = $transfer->trajets
                    ->map(function ($trajet) {
                        return [
                            'type' => $trajet->type,
                            'from' => $trajet->from,
                            'google_address' => $trajet->google_address,
                            'datetime' => $trajet->datetime,
                            'order' => $trajet->order,
                        ];
                    })
                    ->filter(fn (array $trajet) => !empty($trajet['datetime']))
                    ->values()
                    ->all();

                if (empty($trajets) && $transfer->start_date) {
                    $trajets[] = [
                        'type' => 'depart',
                        'from' => $transfer->from,
                        'google_address' => null,
                        'datetime' => $transfer->start_date,
                        'order' => 1,
                    ];
                }

                $planned = $enRouteCalculator->calculate(
                    $trajets,
                    (int) $transfer->servicetype_id,
                    (int) $transfer->vehicule_id,
                    (int) $transfer->driver_id,
                    \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'depot_id') ? (int) $transfer->depot_id : null
                );

                if (!$planned && $transfer->start_date) {
                    $planned = Carbon::parse($transfer->start_date, 'Europe/Paris')->subMinutes($fallbackMinutes);
                }

                if ($planned) {
                    $transfer->ofis_start = $planned->toDateTimeString();
                    $transfer->save();

                    Log::info("🕒 ofis_start automatique: transfert {$transfer->id} => {$planned->format('Y-m-d H:i')}");
                }
            });
    }
}
