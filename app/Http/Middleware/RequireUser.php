<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class RequireUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->route()?->getName(), [
            'login',
            'login.store',
            'register',
            'register.store',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'firebase.messaging-sw',
            'pwa.manifest',
            'pwa.manifest-json',
            'notifications.push.cron',
        ], true) || in_array($request->path(), [
            'koneksi/login.php',
            'register.php',
            'koneksi/register.php',
            'koneksi/forgot_password.php',
            'forgot_password.php',
            'koneksi/reset_password.php',
            'reset_password.php',
        ], true)) {
            return $next($request);
        }

        $userId = $request->session()->get('user_id');
        $rememberToken = $request->cookie('remember_token');
        $rememberTokenHash = $rememberToken ? hash('sha256', $rememberToken) : null;

        if (!$userId && $rememberTokenHash) {
            $rememberedUser = DB::table('remember_tokens')
                ->join('users', 'users.id', '=', 'remember_tokens.user_id')
                ->where('remember_tokens.token_hash', $rememberTokenHash)
                ->where('remember_tokens.expires_at', '>', now())
                ->select('users.id', 'remember_tokens.last_used_at', 'remember_tokens.created_at')
                ->first();

            if ($rememberedUser) {
                $lastActivity = $rememberedUser->last_used_at ?? $rememberedUser->created_at;
                if (Carbon::parse($lastActivity)->lte(now()->subMinutes(config('session.lifetime')))) {
                    DB::table('remember_tokens')->where('token_hash', $rememberTokenHash)->delete();

                    return redirect()->route('login')->withCookie(Cookie::forget('remember_token'));
                }

                $request->session()->put('user_id', $rememberedUser->id);
                $userId = $rememberedUser->id;
            }
        }

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            $request->session()->forget('user_id');

            return redirect()->route('login');
        }

        $request->attributes->set('currentUser', $user);
        if ($rememberTokenHash) {
            DB::table('remember_tokens')
                ->where('token_hash', $rememberTokenHash)
                ->where('user_id', $userId)
                ->update(['last_used_at' => now()]);
        }

        return $next($request);
    }
}
