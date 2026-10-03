<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = DB::table('users')->where('id', $request->attributes->get('currentUser')->id)->first();

        return view('profile', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email,'.$request->attributes->get('currentUser')->id],
            'instagram' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'sosmed_public' => ['sometimes', 'boolean'],
        ]);
        $userId = $request->attributes->get('currentUser')->id;

        DB::table('users')->where('id', $userId)->update([
            'username' => $data['username'],
            'email' => $data['email'],
            'instagram' => $data['instagram'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'twitter' => $data['twitter'] ?? null,
            'tiktok' => $data['tiktok'] ?? null,
            'linkedin' => $data['linkedin'] ?? null,
            'sosmed_public' => (bool) ($data['sosmed_public'] ?? false),
        ]);

        return redirect()->route('profile.show')->with('status', 'Profil berhasil diperbarui.');
    }
}
