<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('users')->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('user_id', $user->id);

        $response = redirect()->intended(route('dashboard'));
        DB::table('remember_tokens')->where('user_id', $user->id)->delete();

        if ($request->boolean('remember')) {
            $token = Str::random(128);
            $expiresAt = now()->addDays(30);

            DB::table('remember_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]);

            $response->withCookie(cookie(
                'remember_token',
                $token,
                60 * 24 * 30,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            ));
            $response->withCookie(cookie('remember_email', $user->email, 60 * 24 * 30));
        } else {
            $response->withCookie(Cookie::forget('remember_email'));
        }

        return $response;
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function storeRegistration(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::table('users')->insert([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
        ]);

        return redirect()->route('login')->with('status', 'Registrasi berhasil. Silakan masuk.');
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = DB::table('users')->where('email', $data['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email tidak ditemukan.'])->onlyInput('email');
        }

        $token = Str::random(64);
        DB::table('users')->where('id', $user->id)->update([
            'reset_token' => $token,
            'reset_expire' => now()->addHour(),
        ]);

        return back()->with('reset_link', route('password.reset', ['token' => $token]));
    }

    public function resetPassword(string $token): View|RedirectResponse
    {
        $user = DB::table('users')
            ->where('reset_token', $token)
            ->where('reset_expire', '>', now())
            ->first();

        if (!$user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Tautan reset sudah kedaluwarsa atau tidak valid.']);
        }

        return view('auth.reset-password', ['token' => $token]);
    }

    public function updatePassword(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $updated = DB::table('users')
            ->where('reset_token', $token)
            ->where('reset_expire', '>', now())
            ->update([
                'password' => Hash::make($data['password']),
                'reset_token' => null,
                'reset_expire' => null,
            ]);

        if (!$updated) {
            return back()->withErrors(['password' => 'Tautan reset sudah kedaluwarsa atau tidak valid.']);
        }

        return redirect()->route('login')->with('status', 'Password berhasil diperbarui. Silakan masuk.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->cookie('remember_token');

        if ($token) {
            DB::table('remember_tokens')
                ->where('token_hash', hash('sha256', $token))
                ->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withCookie(Cookie::forget('remember_token'))
            ->withCookie(Cookie::forget('remember_email'));
    }
}
