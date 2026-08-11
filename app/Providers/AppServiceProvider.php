<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\HermesEngineAlertService;
use App\Models\Option;
use App\Services\TwilioAccountAlertService;
use App\Services\ClientDriverDetailsAlertService;
use App\Services\NotificationAudienceService;
use App\Contracts\WhatsAppGroupProviderInterface;
use App\Contracts\WhatsAppLeadAnalyzerInterface;
use App\Services\WhatsApp\KeywordLeadAnalyzer;
use App\Services\WhatsApp\WppConnectGroupProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WhatsAppGroupProviderInterface::class, WppConnectGroupProvider::class);
        $this->app->bind(WhatsAppLeadAnalyzerInterface::class, KeywordLeadAnalyzer::class);
    }

    public function boot(): void
    {

        View::composer('layouts.app', function ($view) {
            $messages = [];
            $hermesAlerts = [];
            $clientDriverDetailsAlerts = [];
            $canSeeHermesAlerts = false;

            try {
                $user = Auth::user();
                $audience = app(NotificationAudienceService::class);
                $canSeeHermesAlerts = $audience->canSee($user, 'hermes_vehicle_not_started');
                $canSeeClientDriverDetailsAlerts = $audience->canSee($user, 'driver_details_not_sent')
                    || $audience->canSee($user, 'transfer_not_confirmed')
                    || $audience->canSee($user, 'driver_mission_not_confirmed')
                    || $audience->canSee($user, 'enroute_not_defined');

                if (request()->isMethod('GET') && !request()->ajax()) {
                    if ($canSeeHermesAlerts) {
                        $hermesAlerts = app(HermesEngineAlertService::class)->alerts();
                        $messages = array_map(function ($alert) {
                            return is_array($alert) ? ($alert['message'] ?? '') : (string) $alert;
                        }, $hermesAlerts);
                    }
                    if ($canSeeClientDriverDetailsAlerts) {
                        $clientDriverDetailsAlerts = app(ClientDriverDetailsAlertService::class)->alerts(true, $user);
                    }
                }
            } catch (\Throwable $e) {
                $messages = [];
            }

            $refreshMinutes = (int) (Option::where('name', 'hermesToastRefreshMinutes')->value('value') ?? 15);
            if ($refreshMinutes < 1) {
                $refreshMinutes = 15;
            }

            $twilioMessages = [];
            try {
                $user = Auth::user();
                if ($user && app(NotificationAudienceService::class)->canSee($user, 'twilio_balance_low') && request()->isMethod('GET') && !request()->ajax() && request()->is('ev*')) {
                    $twilioMessages = app(TwilioAccountAlertService::class)->messages();
                }
            } catch (\Throwable $e) {
                $twilioMessages = [];
            }

            $view->with('globalHermesToastAlerts', $hermesAlerts);
            $view->with('globalClientDriverDetailsToastAlerts', $clientDriverDetailsAlerts);
            $view->with('globalHermesToastMessages', $messages);
            $view->with('globalHermesToastRefreshMinutes', $refreshMinutes);
            $view->with('globalTwilioToastMessages', $twilioMessages);
        });
        /*
      // Console'da çalışmasın (artisan, cron vs.)
        if (app()->runningInConsole()) {
            return;
        }

        // SADECE ofis2025 domaini
        if (request()->getHost() !== 'ofis2025.parisvia.com') {
            return;
        }

        DB::listen(function ($query) {

            // Sadece yazma sorguları
            if (!preg_match('/\b(insert|update|replace|create|truncate|truncate|replicate)\b/i', $query->sql)) {
                return;
            }

            foreach ((array) $query->bindings as $binding) {

                // String veya DateTime değilse geç
                if (!is_string($binding) && !($binding instanceof \DateTimeInterface)) {
                    continue;
                }

                $value = $binding instanceof \DateTimeInterface
                    ? $binding->format('Y-m-d H:i:s')
                    : trim($binding);

                $ts = strtotime(str_replace('/', '-', $value));
                if ($ts === false) {
                    continue;
                }

                // 🚫 2026 ve sonrası BLOK
                if ($ts >= strtotime('2026-01-01 00:00:00')) {
                    abort(403, 'ofis2025 vous ne pouvez pas ajouter  2026. aller a la page ofis.parisvia.com');
                }
            }
        });*/
    }
}
