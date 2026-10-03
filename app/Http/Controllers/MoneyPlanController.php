<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MoneyPlanController extends Controller
{
    public function index(Request $request): View
    {
        $username = $request->attributes->get('currentUser')->username;
        $month = $request->integer('bulan', (int) now()->format('n'));
        $year = $request->integer('tahun', (int) now()->format('Y'));
        abort_if($month < 1 || $month > 12 || $year < 2000 || $year > 2100, 422);

        $totals = DB::table('money_plan')->where('username', $username)
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense")
            ->selectRaw("SUM(CASE WHEN payment_method = 'cash' AND type = 'income' THEN amount ELSE 0 END) AS cash_income")
            ->selectRaw("SUM(CASE WHEN payment_method = 'cash' AND type = 'expense' THEN amount ELSE 0 END) AS cash_expense")
            ->selectRaw("SUM(CASE WHEN payment_method = 'online' AND type = 'income' THEN amount ELSE 0 END) AS online_income")
            ->selectRaw("SUM(CASE WHEN payment_method = 'online' AND type = 'expense' THEN amount ELSE 0 END) AS online_expense")
            ->first();

        $transactions = DB::table('money_plan')
            ->where('username', $username)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();
        $monthly = DB::table('money_plan')->where('username', $username)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense")
            ->first();

        return view('money.index', compact('month', 'year', 'totals', 'transactions', 'monthly'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tanggal' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,online'],
        ]);
        $data['username'] = $request->attributes->get('currentUser')->username;
        DB::table('money_plan')->insert($data);

        return redirect()->route('money.index')->with('status', 'Transaksi berhasil ditambahkan.');
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'real_cash' => ['required', 'numeric', 'min:0'],
            'real_online' => ['required', 'numeric', 'min:0'],
        ]);
        $username = $request->attributes->get('currentUser')->username;

        DB::transaction(function () use ($data, $username): void {
            $balances = DB::table('money_plan')->where('username', $username)
                ->selectRaw("SUM(CASE WHEN payment_method = 'cash' AND type = 'income' THEN amount WHEN payment_method = 'cash' AND type = 'expense' THEN -amount ELSE 0 END) AS cash")
                ->selectRaw("SUM(CASE WHEN payment_method = 'online' AND type = 'income' THEN amount WHEN payment_method = 'online' AND type = 'expense' THEN -amount ELSE 0 END) AS online")
                ->first();

            foreach ([
                'cash' => (float) ($balances->cash ?? 0),
                'online' => (float) ($balances->online ?? 0),
            ] as $method => $current) {
                $actual = (float) $data['real_'.$method];
                $difference = round($actual - $current, 2);
                if ($difference === 0.0) {
                    continue;
                }

                DB::table('money_plan')->insert([
                    'username' => $username,
                    'type' => $difference > 0 ? 'income' : 'expense',
                    'category' => 'Penyesuaian Saldo',
                    'amount' => abs($difference),
                    'description' => 'Penyesuaian saldo '.$method,
                    'tanggal' => today()->toDateString(),
                    'payment_method' => $method,
                ]);
            }
        });

        return redirect()->route('money.index')->with('status', 'Saldo berhasil disesuaikan.');
    }

    public function destroy(Request $request, int $transaction): RedirectResponse
    {
        $deleted = DB::table('money_plan')
            ->where('id', $transaction)
            ->where('username', $request->attributes->get('currentUser')->username)
            ->delete();
        abort_unless($deleted, 404);

        return redirect()->route('money.index')->with('status', 'Transaksi dihapus.');
    }

    public function compatibilityAction(Request $request): RedirectResponse
    {
        if ($request->has('adjust_balance')) {
            return $this->adjust($request);
        }

        return $this->store($request);
    }
}
