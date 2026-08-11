<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/ev';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Giriş bilgileri hatalı.']);
        }

        $stored = (string) $user->password;
        $plain  = (string) $request->password;

        $isHashed = str_starts_with($stored, '$2y$')
            || str_starts_with($stored, '$2a$')
            || str_starts_with($stored, '$argon2');

        $ok = $isHashed ? Hash::check($plain, $stored) : (md5($plain) === $stored);

        if (!$ok) {
            return back()->withErrors(['email' => 'Giriş bilgileri hatalı.']);
        }

        // MD5 ise girişte otomatik bcrypt/argon2'ye yükselt
        if (!$isHashed) {
            $user->password = Hash::make($plain);
            $user->save();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('url.intended');
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect('/ev');
    }
}
