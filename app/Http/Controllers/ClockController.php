<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClockController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->attributes->get('currentUser');

        return view('clock.edit', [
            'mode' => $user->clock_mode ?: 'clock',
            'countdownMinutes' => max(1, (int) $user->countdown_seconds / 60),
            'workMinutes' => $user->pomodoro_work ?: 25,
            'breakMinutes' => $user->pomodoro_break ?: 5,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:clock,stopwatch,countdown,pomodoro'],
            'minutes' => ['required_if:mode,countdown', 'nullable', 'integer', 'min:1', 'max:10080'],
            'work' => ['required_if:mode,pomodoro', 'nullable', 'integer', 'min:1', 'max:180'],
            'break' => ['required_if:mode,pomodoro', 'nullable', 'integer', 'min:1', 'max:180'],
        ]);

        DB::table('users')->where('id', $request->attributes->get('currentUser')->id)->update([
            'clock_mode' => $data['mode'],
            'countdown_seconds' => $data['mode'] === 'countdown' ? (int) $data['minutes'] * 60 : 0,
            'pomodoro_work' => $data['mode'] === 'pomodoro' ? (int) $data['work'] : 25,
            'pomodoro_break' => $data['mode'] === 'pomodoro' ? (int) $data['break'] : 5,
        ]);

        return redirect()->route('clock.edit')->with('status', 'Pengaturan jam berhasil disimpan.');
    }
}
