<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->attributes->get('currentUser');
        $today = today()->toDateString();

        return view('dashboard', [
            'user' => $user,
            'today' => $today,
            'expenseToday' => DB::table('money_plan')
                ->where('username', $user->username)
                ->where('type', 'expense')
                ->whereDate('tanggal', $today)
                ->sum('amount'),
            'latestNote' => DB::table('notes')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first(['title', 'created_at']),
            'attendanceToday' => DB::table('absen')
                ->where('user_id', $user->id)
                ->whereDate('tanggal', $today)
                ->first(),
            'attendanceHistory' => DB::table('absen')
                ->where('user_id', $user->id)
                ->orderByDesc('tanggal')
                ->limit(10)
                ->get(),
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $user = $request->attributes->get('currentUser');
        $today = today()->toDateString();

        $exists = DB::table('absen')
            ->where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->exists();

        if (!$exists) {
            DB::table('absen')->insert([
                'user_id' => $user->id,
                'tanggal' => $today,
                'jam_masuk' => now()->format('H:i:s'),
                'created_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);
        }

        return redirect()->route('dashboard')->with(
            $exists ? 'error' : 'status',
            $exists ? 'Anda sudah absen masuk hari ini.' : 'Check in berhasil.'
        );
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $user = $request->attributes->get('currentUser');
        $updated = DB::table('absen')
            ->where('user_id', $user->id)
            ->whereDate('tanggal', today()->toDateString())
            ->whereNull('jam_pulang')
            ->update(['jam_pulang' => now()->format('H:i:s')]);

        return redirect()->route('dashboard')->with(
            $updated ? 'status' : 'error',
            $updated ? 'Check out berhasil.' : 'Anda belum absen masuk atau sudah absen pulang.'
        );
    }

    public function calendarMarks(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);
        $userId = $request->attributes->get('currentUser')->id;

        return response()->json(DB::table('calendar_marks')
            ->where('user_id', $userId)
            ->whereMonth('tanggal', $data['month'])
            ->whereYear('tanggal', $data['year'])
            ->orderBy('tanggal')
            ->get(['id', 'tanggal', 'title', 'description', 'selesai']));
    }
}
