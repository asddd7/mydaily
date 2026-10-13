@extends('layouts.app')

@section('title', 'Pengatur Keuangan - MyDaily')

@push('styles')
    @vite('resources/css/money.css')
@endpush

@section('content')
<div class="money-page">
    <div class="money-heading">
        <div>
            <p class="money-eyebrow">Ringkasan keuangan</p>
            <h1>Pengatur Keuangan</h1>
            <p>Pantau saldo dan riwayat transaksi dalam satu tempat.</p>
        </div>
    </div>

    <section class="money-balance-grid" aria-label="Ringkasan saldo">
        <article class="money-balance-card money-balance-card-primary">
            <span class="money-balance-icon" aria-hidden="true"><i class="fa-solid fa-wallet"></i></span>
            <div>
                <p>Saldo keseluruhan</p>
                <strong>Rp {{ number_format((float) $totals->income - (float) $totals->expense, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="money-balance-card">
            <span class="money-balance-icon money-balance-icon-cash" aria-hidden="true"><i class="fa-solid fa-money-bill-wave"></i></span>
            <div>
                <p>Saldo cash</p>
                <strong>Rp {{ number_format((float) $totals->cash_income - (float) $totals->cash_expense, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="money-balance-card">
            <span class="money-balance-icon money-balance-icon-online" aria-hidden="true"><i class="fa-solid fa-building-columns"></i></span>
            <div>
                <p>Saldo online</p>
                <strong>Rp {{ number_format((float) $totals->online_income - (float) $totals->online_expense, 0, ',', '.') }}</strong>
            </div>
        </article>
    </section>

    <div class="money-form-grid">
        <section class="money-panel">
            <div class="money-panel-heading">
                <span class="money-panel-icon" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                <div>
                    <h2>Tambah transaksi</h2>
                    <p>Catat pemasukan atau pengeluaran baru.</p>
                </div>
            </div>
            <form method="post" action="{{ route('money.store') }}" class="money-entry-form" id="moneyCreateForm">
                @csrf
                <label>Jenis
                    <select name="type" required>
                        <option value="income">Pemasukan</option>
                        <option value="expense">Pengeluaran</option>
                    </select>
                </label>
                <label>Tanggal
                    <input type="date" name="tanggal" value="{{ today()->toDateString() }}" required>
                </label>
                <label>Kategori
                    <input name="category" maxlength="100" required>
                </label>
                <label>Nominal
                    <input type="number" name="amount" min="0.01" step="0.01" placeholder="0" required>
                </label>
                <label class="money-field-wide">Keterangan
                    <input name="description" maxlength="1000" placeholder="Opsional">
                </label>
                <label class="money-field-wide">Metode pembayaran
                    <select name="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="online">Saldo online</option>
                    </select>
                </label>
                <button class="money-primary-button money-field-wide" type="submit">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Simpan transaksi
                </button>
            </form>
        </section>

        <section class="money-panel money-adjust-panel">
            <div class="money-panel-heading">
                <span class="money-panel-icon money-panel-icon-adjust" aria-hidden="true"><i class="fa-solid fa-scale-balanced"></i></span>
                <div>
                    <h2>Sesuaikan saldo aktual</h2>
                    <p>Samakan catatan dengan saldo yang tersedia.</p>
                </div>
            </div>
            <form method="post" action="{{ route('money.adjust') }}" class="money-adjust-form">
                @csrf
                <label>Cash saat ini
                    <input type="number" name="real_cash" min="0" step="0.01" placeholder="0" required>
                </label>
                <label>Saldo online saat ini
                    <input type="number" name="real_online" min="0" step="0.01" placeholder="0" required>
                </label>
                <button class="money-secondary-button" type="submit">Sesuaikan saldo</button>
            </form>
        </section>
    </div>

    <section class="money-panel money-history-panel">
        <div class="money-panel-heading money-history-heading">
            <div>
                <p class="money-eyebrow">Aktivitas</p>
                <h2>Riwayat transaksi</h2>
            </div>
            <form method="get" action="{{ route('money.index') }}" class="money-filter-form">
                <label>Bulan
                    <input type="number" name="bulan" min="1" max="12" value="{{ $month }}" required>
                </label>
                <label>Tahun
                    <input type="number" name="tahun" min="2000" max="2100" value="{{ $year }}" required>
                </label>
                <button type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
            </form>
        </div>

        <div class="money-period-summary">
            <div>
                <span>Pemasukan periode ini</span>
                <strong class="money-income">Rp {{ number_format((float) $monthly->income, 0, ',', '.') }}</strong>
            </div>
            <div>
                <span>Pengeluaran periode ini</span>
                <strong class="money-expense">Rp {{ number_format((float) $monthly->expense, 0, ',', '.') }}</strong>
            </div>
            <div>
                <span>Saldo periode ini</span>
                <strong>Rp {{ number_format((float) $monthly->income - (float) $monthly->expense, 0, ',', '.') }}</strong>
            </div>
        </div>

        <div class="money-table-wrap">
            <table class="money-table">
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Jumlah</th>
                        <th scope="col">Metode</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td data-label="Tanggal">{{ $transaction->tanggal }}</td>
                            <td data-label="Jenis">
                                <span class="money-type-badge {{ $transaction->type === 'income' ? 'money-type-income' : 'money-type-expense' }}">
                                    {{ $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                </span>
                            </td>
                            <td data-label="Kategori">{{ $transaction->category }}</td>
                            <td data-label="Jumlah" class="money-amount">Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</td>
                            <td data-label="Metode">{{ $transaction->payment_method === 'cash' ? 'Cash' : 'Online' }}</td>
                            <td data-label="Aksi">
                                <form method="post" action="{{ route('money.destroy', $transaction->id) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                                    @csrf
                                    @method('delete')
                                    <button class="money-delete-button" type="submit" aria-label="Hapus transaksi {{ $transaction->category }}">
                                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="money-empty-row">
                            <td colspan="6">
                                <span class="money-empty-icon" aria-hidden="true"><i class="fa-regular fa-folder-open"></i></span>
                                <strong>Tidak ada transaksi</strong>
                                <span>Belum ada transaksi pada periode ini.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
