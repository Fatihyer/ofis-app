<?php

namespace App\Http\Controllers;

use App\Services\ClientDriverDetailsAlertService;
use App\Services\NotificationAudienceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class GlobalAlertController extends Controller
{
    public function clientDriverDetails(ClientDriverDetailsAlertService $alerts, NotificationAudienceService $audience): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !(
            $audience->canSee($user, 'driver_details_not_sent')
            || $audience->canSee($user, 'transfer_not_confirmed')
            || $audience->canSee($user, 'driver_mission_not_confirmed')
            || $audience->canSee($user, 'enroute_not_defined')
        )) {
            return response()->json(['alerts' => [], 'messages' => []]);
        }

        $clientAlerts = $alerts->alerts(true, $user);

        return response()->json([
            'alerts' => $clientAlerts,
            'messages' => array_map(function ($alert) {
                return is_array($alert) ? ($alert['message'] ?? '') : (string) $alert;
            }, $clientAlerts),
        ]);
    }

    public function userNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['alerts' => []]);
        }

        $alerts = $user->unreadNotifications()
            ->whereIn('type', [
                \App\Notifications\TalepAdminPriceUpdated::class,
                \App\Notifications\TaskAssignedNotification::class,
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?: [];
                if ($notification->type === \App\Notifications\TaskAssignedNotification::class) {
                    return [
                        'id' => $notification->id,
                        'title' => $data['title'] ?? 'Nouvelle tâche',
                        'message' => $data['message'] ?? '',
                        'task_id' => $data['task_id'] ?? null,
                        'task_title' => $data['task_title'] ?? null,
                        'updated_by' => $data['assigned_by'] ?? null,
                        'url' => $data['url'] ?? null,
                        'created_at' => optional($notification->created_at)->format('d/m/Y H:i'),
                    ];
                }

                $currency = $data['currency'] ?? 'EUR';
                $newPrice = $data['new_price'] ?? null;
                $oldPrice = $data['old_price'] ?? null;
                $priceText = $newPrice !== null ? number_format((float) $newPrice, 2, ',', ' ').' '.$currency : '-';
                $oldText = $oldPrice !== null ? number_format((float) $oldPrice, 2, ',', ' ').' '.$currency : '-';

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? 'Notification',
                    'message' => $data['message'] ?? '',
                    'talep_id' => $data['talep_id'] ?? null,
                    'request_no' => $data['request_no'] ?? null,
                    'customer_name' => $data['customer_name'] ?? null,
                    'old_price_text' => $oldText,
                    'new_price_text' => $priceText,
                    'updated_by' => $data['updated_by'] ?? null,
                    'url' => $data['url'] ?? null,
                    'created_at' => optional($notification->created_at)->format('d/m/Y H:i'),
                ];
            })
            ->values()
            ->all();

        return response()->json(['alerts' => $alerts]);
    }

    public function markUserNotificationRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        if ((int) $notification->notifiable_id !== (int) $request->user()->id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    private function canSeeOperationAlerts(): bool
    {
        $user = Auth::user();

        return $user && (
            $user->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport'])
            || $user->hasAnyPermission(['hermes.alerts', 'hermes.view', 'ofis', 'transport'])
        );
    }
}
