<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use App\Models\Acente;
use Spatie\Permission\Models\Role;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('isAdmin')->except(['online']);
    }

    private function canSeeOnlineUsers($user = null): bool
    {
        $user = $user ?: Auth::user();

        return $user && $user->hasAnyRole(['Superadmin', 'Admin', 'ofis']);
    }

    private function canFullyManageUsers($user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->hasPermissionTo('Administer roles & permissions');
    }

    private function availableRoles()
    {
        if ($this->canFullyManageUsers()) {
            return Role::get();
        }

        return Role::where('name', 'Driver')->get();
    }

    private function driverRole(): Role
    {
        return Role::where('name', 'Driver')->firstOrFail();
    }

    private function requestedRoles(Request $request): array
    {
        if ($this->canFullyManageUsers()) {
            return (array) $request->input('roles', []);
        }

        return [$this->driverRole()->id];
    }

    private function abortUnlessEditableByCurrentUser(User $user): void
    {
        if (!$this->canFullyManageUsers() && !$user->hasRole('Driver')) {
            abort(403);
        }
    }

    public function index()
    {
        $users = $this->canFullyManageUsers()
            ? User::with(['roles', 'acentes'])->get()
            : User::role('Driver')->with(['roles', 'acentes'])->get();

        return view('users.index')->with('users', $users);
    }

    public function online()
    {
        abort_unless($this->canSeeOnlineUsers(), 403);

        Auth::user()?->forceFill(['last_seen_at' => now()])->save();

        $now = now();
        $users = User::query()
            ->select(['id', 'name', 'email', 'last_seen_at'])
            ->whereNotNull('last_seen_at')
            ->orderByDesc('last_seen_at')
            ->get();

        $activityByUser = collect();

        if (Schema::hasTable('user_activity_summaries') && $users->isNotEmpty()) {
            $today = $now->toDateString();
            $weekStart = $now->copy()->startOfWeek()->toDateString();
            $weekEnd = $now->copy()->endOfWeek()->toDateString();
            $monthStart = $now->copy()->startOfMonth()->toDateString();
            $monthEnd = $now->copy()->endOfMonth()->toDateString();

            $activityByUser = DB::table('user_activity_summaries')
                ->select('user_id')
                ->selectRaw('SUM(CASE WHEN activity_date = ? THEN active_seconds ELSE 0 END) as today_seconds', [$today])
                ->selectRaw('SUM(CASE WHEN activity_date BETWEEN ? AND ? THEN active_seconds ELSE 0 END) as week_seconds', [$weekStart, $weekEnd])
                ->selectRaw('SUM(CASE WHEN activity_date BETWEEN ? AND ? THEN active_seconds ELSE 0 END) as month_seconds', [$monthStart, $monthEnd])
                ->whereIn('user_id', $users->pluck('id')->all())
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');
        }

        $users = $users->map(function ($user) use ($activityByUser, $now) {
            $activity = $activityByUser->get($user->id);
            $lastSeen = $user->last_seen_at ? \Carbon\Carbon::parse($user->last_seen_at) : null;
            $minutesSinceSeen = $lastSeen ? $lastSeen->diffInMinutes($now) : null;

            $user->today_active_seconds = (int) ($activity->today_seconds ?? 0);
            $user->week_active_seconds = (int) ($activity->week_seconds ?? 0);
            $user->month_active_seconds = (int) ($activity->month_seconds ?? 0);
            $user->online_status_order = $minutesSinceSeen !== null && $minutesSinceSeen <= 5
                ? 0
                : ($minutesSinceSeen !== null && $minutesSinceSeen <= 30 ? 1 : 2);

            return $user;
        })->sortBy([
            ['online_status_order', 'asc'],
            ['last_seen_at', 'desc'],
        ])->values();

        return view('users.online', compact('users'));
    }

    public function create()
    {
        $roles = $this->availableRoles();
        $acentes = Acente::orderBy('name')->pluck('name', 'id')->toArray();

        return view('users.create', ['roles' => $roles, 'acentes' => $acentes]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:120',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create($request->only('email', 'name', 'password', 'phone'));
        $user->acentes()->sync($request->input('acente_id', []));

        foreach ($this->requestedRoles($request) as $role) {
            $role_r = Role::where('id', '=', $role)->firstOrFail();
            $user->assignRole($role_r);
        }

        return redirect()->route('users.index')
            ->with('flash_message', 'User successfully added.');
    }

    public function show($id)
    {
        return redirect('users');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->abortUnlessEditableByCurrentUser($user);

        $roles = $this->availableRoles();
        $acentes = Acente::orderBy('name')->pluck('name', 'id')->toArray();

        return view('users.edit', compact('user', 'roles', 'acentes'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->abortUnlessEditableByCurrentUser($user);

        $this->validate($request, [
            'name' => 'required|max:120',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6|confirmed',
        ]);

        $input = $request->only(['name', 'email', 'phone']);
        if ($request->filled('password')) {
            $input['password'] = $request->input('password');
        }

        $user->fill($input)->save();

        if ($request->filled('password')) {
            $user->revokeTokens();
        }

        $user->acentes()->sync($request->input('acente_id', []));

        $roles = $this->requestedRoles($request);
        if (!empty($roles)) {
            $user->roles()->sync($roles);
        } elseif ($this->canFullyManageUsers()) {
            $user->roles()->detach();
        }

        return redirect()->route('users.index')
            ->with('flash_message', 'User successfully edited.');
    }

    public function destroy($id)
    {
        if (!$this->canFullyManageUsers()) {
            abort(403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')
            ->with('flash_message', 'User successfully deleted.');
    }

    public function changepass($id)
    {
        $user = User::findOrFail($id);
        $this->abortUnlessEditableByCurrentUser($user);

        return view('users.pass', compact('user'));
    }

    public function passwordupdate(Request $request)
    {
        $user = User::findOrFail($request->id);
        $this->abortUnlessEditableByCurrentUser($user);

        $this->validate($request, [
            'password' => 'required|min:6|confirmed',
        ]);

        $input = $request->only(['password']);
        $user->fill($input)->save();
        $user->revokeTokens();

        return redirect()->route('users.index')
            ->with('flash_message', 'Password updated.');
    }
}
