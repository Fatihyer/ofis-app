<?php

namespace App\Http\Controllers;

use App\Models\NotificationAlertType;
use App\Models\NotificationGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Superadmin']);
    }

    public function index()
    {
        $groups = NotificationGroup::with(['users:id,name,email', 'alertTypes:id,name,slug'])->orderBy('name')->get();
        $alertTypes = NotificationAlertType::with('groups:id')->orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('notification_groups.index', compact('groups', 'alertTypes', 'users'));
    }

    public function updateUsers(Request $request, NotificationGroup $group)
    {
        $data = $request->validate([
            'users' => ['nullable', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
        ]);

        $group->users()->sync($data['users'] ?? []);
        Cache::flush();

        return back()->with('success', 'Utilisateurs du groupe mis a jour.');
    }

    public function updateAlertTypes(Request $request)
    {
        $data = $request->validate([
            'alert_groups' => ['nullable', 'array'],
            'alert_groups.*' => ['nullable', 'array'],
            'alert_groups.*.*' => ['integer', 'exists:notification_groups,id'],
        ]);

        foreach (NotificationAlertType::all() as $alertType) {
            $alertType->groups()->sync($data['alert_groups'][$alertType->id] ?? []);
        }

        Cache::flush();
        return back()->with('success', 'Affectation des alertes mise a jour.');
    }
}
