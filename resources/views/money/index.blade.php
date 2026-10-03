@extends('layouts.app')

@section('title', 'Pengatur Keuangan - MyDaily')

@section('content')
<section class="card">
    <h1>Pengatur Keuangan</h1>
    <div class="form-grid">
        <p>Saldo keseluruhan: <strong>Rp {{ number_format((float) $totals->income - (float) $totals->expense, 0, ',', '.') }}</strong></p>
        <p>Cash: <strong>Rp {{ number_format((float) $totals->cash_income - (float) $totals->cash_expense, 0, ',', '.') }}</strong></p>
        <p>Saldo online: <strong>Rp {{ number_format((float) $totals->online_income - (float) $totals->online_expense, 0, ',', '.') }}</strong></p>
    </div>
</section>

<section class="card">
    <h2>Tambah transaksi</h2>
    <form method="post" action="{{ route('money.store') }}" class="form-grid">
        @csrf
        <label>Jenis
            <select name="type" required><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option></select>
        </label>
        <label>Tanggal <input type="date" name="tanggal" value="{{ today()->toDateString() }}" required></label>
        <label>Kategori <input name="category" maxlength="100" required></label>
        <label>Nominal <input type="number" name="amount" min="0.01" step="0.01" required></label>
        <label>Keterangan <input name="description" maxlength="1000"></label>
        <label>Pembayaran
            <select name="payment_method" required><option value="cash">Cash</option><option value="online">Saldo online</option></select>
        </label>
        <button type="submit">Simpan transaksi</button>
    </form>
</section>

<section class="card">
    <h2>Sesuaikan saldo aktual</h2>
    <form method="post" action="{{ route('money.adjust') }}" class="form-grid">
        @csrf
        <label>Cash saat ini <input type="number" name="real_cash" min="0" step="0.01" required></label>
        <label>Online saat ini <input type="number" name="real_online" min="0" step="0.01" required></label>
        <button type="submit">Sesuaikan saldo</button>
    </form>
</section>

<section class="card">
    <h2>Riwayat transaksi</h2>
    <p>Pemasukan periode ini: <strong>Rp {{ number_format((float) $monthly->income, 0, ',', '.') }}</strong></p>
    <p>Pengeluaran periode ini: <strong>Rp {{ number_format((float) $monthly->expense, 0, ',', '.') }}</strong></p>
    <p>Saldo periode ini: <strong>Rp {{ number_format((float) $monthly->income - (float) $monthly->expense, 0, ',', '.') }}</strong></p>
    <form method="get" action="{{ route('money.index') }}">
        <label>Bulan <input type="number" name="bulan" min="1" max="12" value="{{ $month }}" required></label>
        <label>Tahun <input type="number" name="tahun" min="2000" max="2100" value="{{ $year }}" required></label>
        <button type="submit">Filter</button>
    </form>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Metode</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->tanggal }}</td>
                    <td>{{ $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                    <td>{{ $transaction->category }}</td>
                    <td>Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</td>
                    <td>{{ $transaction->payment_method }}</td>
                    <td>
                        <form method="post" action="{{ route('money.destroy', $transaction->id) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                            @csrf
                            @method('delete')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Tidak ada transaksi untuk periode ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
