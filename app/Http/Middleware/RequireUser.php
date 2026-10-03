<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if (!$userId && $request->hasCookie('remember_token')) {
            $tokenHash = hash('sha256', $request->cookie('remember_token'));
            $rememberedUser = DB::table('remember_tokens')
                ->join('users', 'users.id', '=', 'remember_tokens.user_id')
                ->where('remember_tokens.token_hash', $tokenHash)
                ->where('remember_tokens.expires_at', '>', now())
                ->select('users.id')
                ->first();

            if ($rememberedUser) {
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

        return $next($request);
    }
}
