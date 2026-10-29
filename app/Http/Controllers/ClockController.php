<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClockController extends Controller
{
    /**
     * Menampilkan pengaturan jam milik pengguna yang sedang login.
     */
    public function edit(Request $request): View
    {
        $user = $request->attributes->get('currentUser');

        abort_unless($user, 401);

        return view('clock.edit', [
            'mode' => $user->clock_mode ?: 'clock',
            'countdownMinutes' => max(
                1,
                (int) ceil(((int) $user->countdown_seconds) / 60)
            ),
            'workMinutes' => max(1, (int) ($user->pomodoro_work ?: 25)),
            'breakMinutes' => max(1, (int) ($user->pomodoro_break ?: 5)),
        ]);
    }

    /**
     * Memvalidasi dan menyimpan preferensi jam.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->attributes->get('currentUser');

        abort_unless($user, 401);

        $validated = $request->validate([
            'mode' => [
                'required',
                Rule::in(['clock', 'stopwatch', 'countdown', 'pomodoro']),
            ],
            'minutes' => [
                'exclude_unless:mode,countdown',
                'required',
                'integer',
                'min:1',
                'max:10080',
            ],
            'work' => [
                'exclude_unless:mode,pomodoro',
                'required',
                'integer',
                'min:1',
                'max:180',
            ],
            'break' => [
                'exclude_unless:mode,pomodoro',
                'required',
                'integer',
                'min:1',
                'max:180',
            ],
        ], [
            'mode.required' => 'Pilih mode jam terlebih dahulu.',
            'mode.in' => 'Mode jam yang dipilih tidak valid.',
            'minutes.required' => 'Durasi countdown wajib diisi.',
            'minutes.min' => 'Durasi countdown minimal 1 menit.',
            'minutes.max' => 'Durasi countdown maksimal 10.080 menit.',
            'work.required' => 'Durasi fokus wajib diisi.',
            'work.min' => 'Durasi fokus minimal 1 menit.',
            'work.max' => 'Durasi fokus maksimal 180 menit.',
            'break.required' => 'Durasi istirahat wajib diisi.',
            'break.min' => 'Durasi istirahat minimal 1 menit.',
            'break.max' => 'Durasi istirahat maksimal 180 menit.',
        ]);

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'clock_mode' => $validated['mode'],
                'countdown_seconds' => $validated['mode'] === 'countdown'
                    ? (int) $validated['minutes'] * 60
                    : 0,
                'pomodoro_work' => $validated['mode'] === 'pomodoro'
                    ? (int) $validated['work']
                    : 25,
                'pomodoro_break' => $validated['mode'] === 'pomodoro'
                    ? (int) $validated['break']
                    : 5,
            ]);

        return redirect()
            ->route('clock.edit')
            ->with('status', 'Pengaturan jam berhasil disimpan.');
    }
}
