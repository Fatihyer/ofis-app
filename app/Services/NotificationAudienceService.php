<?php

namespace App\Services;

use App\Models\NotificationAlertType;
use App\Models\NotificationGroup;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NotificationAudienceService
{
    public function canSee(?User $user, string $alertSlug): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return $this->superadminCanSee($alertSlug);
        }

        $userGroupSlugs = $this->userGroupSlugs($user->id);
        if (empty($userGroupSlugs)) {
            return false;
        }

        $alertGroupSlugs = $this->alertGroupSlugs($alertSlug);
        return (bool) array_intersect($userGroupSlugs, $alertGroupSlugs);
    }

    public function sharesGroupWithUser(?User $viewer, ?int $creatorUserId): bool
    {
        if (!$viewer || !$creatorUserId) {
            return false;
        }

        if ($viewer->hasRole('Superadmin')) {
            return false;
        }

        return (bool) array_intersect(
            $this->userGroupSlugs($viewer->id),
            $this->userGroupSlugs($creatorUserId)
        );
    }

    private function superadminCanSee(string $alertSlug): bool
    {
        return in_array($alertSlug, ['hermes_vehicle_not_started', 'twilio_balance_low'], true);
    }

    public function userGroupSlugs(int $userId): array
    {
        return Cache::remember('notification_user_groups_'.$userId, 300, function () use ($userId) {
            return NotificationGroup::where('active', 1)
                ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                ->pluck('slug')
                ->all();
        });
    }

    private function alertGroupSlugs(string $alertSlug): array
    {
        return Cache::remember('notification_alert_groups_'.$alertSlug, 300, function () use ($alertSlug) {
            $alertType = NotificationAlertType::where('slug', $alertSlug)->where('active', 1)->first();
            if (!$alertType) {
                return [];
            }

            return $alertType->groups()->where('notification_groups.active', 1)->pluck('notification_groups.slug')->all();
        });
    }
}
