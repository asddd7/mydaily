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
        <button class="money-primary-button" type="button" data-open-money-dialog aria-haspopup="dialog">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Tambah transaksi
        </button>
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
        <section class="money-panel money-adjust-panel">
            <div class="money-panel-heading">
                <span class="money-panel-icon money-panel-icon-adjust" aria-hidden="true"><i class="fa-solid fa-scale-balanced"></i></span>
                <div>
                    <h2>Sesuaikan saldo aktual</h2>
                    <p>Samakan catatan dengan saldo yang tersedia.</p>
                </div>
            </div>
            <button class="money-secondary-button" type="button" data-open-money-adjust-dialog aria-haspopup="dialog">
                <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Sesuaikan saldo
            </button>
        </section>
    </div>

    <dialog class="money-create-dialog" data-money-dialog aria-labelledby="money-dialog-title" aria-describedby="money-dialog-description" @if ($errors->any() && old('_form') === 'money_create') data-reopen @endif>
        <div class="money-dialog-heading">
            <div>
                <p class="money-eyebrow">Transaksi baru</p>
                <h2 id="money-dialog-title">Tambah transaksi</h2>
                <p id="money-dialog-description">Catat pemasukan atau pengeluaran baru.</p>
            </div>
            <button class="money-dialog-close" type="button" data-close-money-dialog aria-label="Tutup dialog">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        @if ($errors->any() && old('_form') === 'money_create')
            <p class="money-dialog-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <div class="money-receipt-scan" data-receipt-scan>
            <label for="receiptImage">Foto struk
                <input id="receiptImage" type="file" accept="image/jpeg,image/png,image/webp" capture="environment">
            </label>
            <button type="button" class="money-scan-button" data-scan-submit>
                <i class="fa-solid fa-camera" aria-hidden="true"></i> Baca struk
            </button>
            <p class="money-scan-status" data-scan-status role="status" aria-live="polite"></p>
            <details class="money-ocr-text" data-ocr-text-container hidden>
                <summary>Teks hasil pembacaan</summary>
                <pre data-ocr-text></pre>
            </details>
        </div>
        <form method="post" action="{{ route('money.store') }}" class="money-entry-form" id="moneyCreateForm">
            @csrf
            <input type="hidden" name="_form" value="money_create">
            <label>Jenis
                <select name="type" required>
                    <option value="income" @selected(old('type', 'income') === 'income')>Pemasukan</option>
                    <option value="expense" @selected(old('type') === 'expense')>Pengeluaran</option>
                </select>
            </label>
            <label>Tanggal
                <input type="date" name="tanggal" value="{{ old('tanggal', today()->toDateString()) }}" required>
            </label>
            <label>Kategori
                <input name="category" value="{{ old('category') }}" maxlength="100" required>
            </label>
            <label>Nominal
                <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" placeholder="0" required>
            </label>
            <label class="money-field-wide">Keterangan
                <input name="description" value="{{ old('description') }}" maxlength="1000" placeholder="Opsional">
            </label>
            <label class="money-field-wide">Metode pembayaran
                <select name="payment_method" required>
                    <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                    <option value="online" @selected(old('payment_method') === 'online')>Saldo online</option>
                </select>
            </label>
            <div class="money-dialog-actions money-field-wide">
                <button class="money-dialog-cancel" type="button" data-close-money-dialog>Batal</button>
                <button class="money-primary-button" type="submit">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Simpan transaksi
                </button>
            </div>
        </form>
    </dialog>

    <dialog class="money-create-dialog money-adjust-dialog" data-money-adjust-dialog aria-labelledby="money-adjust-dialog-title" aria-describedby="money-adjust-dialog-description" @if ($errors->any() && old('_form') === 'money_adjust') data-reopen @endif>
        <div class="money-dialog-heading">
            <div>
                <p class="money-eyebrow">Saldo aktual</p>
                <h2 id="money-adjust-dialog-title">Sesuaikan saldo</h2>
                <p id="money-adjust-dialog-description">Masukkan saldo yang tersedia saat ini.</p>
            </div>
            <button class="money-dialog-close" type="button" data-close-money-adjust-dialog aria-label="Tutup dialog">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        @if ($errors->any() && old('_form') === 'money_adjust')
            <p class="money-dialog-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <form method="post" action="{{ route('money.adjust') }}" class="money-adjust-form">
            @csrf
            <input type="hidden" name="_form" value="money_adjust">
            <label>Cash saat ini
                <input type="number" name="real_cash" min="0" step="0.01" value="{{ old('real_cash') }}" placeholder="0" required>
            </label>
            <label>Saldo online saat ini
                <input type="number" name="real_online" min="0" step="0.01" value="{{ old('real_online') }}" placeholder="0" required>
            </label>
            <div class="money-dialog-actions money-field-wide">
                <button class="money-dialog-cancel" type="button" data-close-money-adjust-dialog>Batal</button>
                <button class="money-primary-button" type="submit">
                    <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Simpan saldo
                </button>
            </div>
        </form>
    </dialog>

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

@push('scripts')
    @vite('resources/js/money-receipt-ocr.js')
<script>
(() => {
    const dialog = document.querySelector('[data-money-dialog]');
    if (dialog instanceof HTMLDialogElement) {
        const openDialog = () => {
            if (!dialog.open) dialog.showModal();
            dialog.querySelector('[name="type"]')?.focus();
        };

        document.querySelectorAll('[data-open-money-dialog]').forEach(button => {
            button.addEventListener('click', openDialog);
        });
        dialog.querySelectorAll('[data-close-money-dialog]').forEach(button => {
            button.addEventListener('click', () => dialog.close());
        });
        dialog.addEventListener('click', event => {
            if (event.target === dialog) dialog.close();
        });

        if (dialog.hasAttribute('data-reopen')) openDialog();
    }

    const adjustDialog = document.querySelector('[data-money-adjust-dialog]');
    if (!(adjustDialog instanceof HTMLDialogElement)) return;

    const openAdjustDialog = () => {
        if (!adjustDialog.open) adjustDialog.showModal();
        adjustDialog.querySelector('[name="real_cash"]')?.focus();
    };

    document.querySelectorAll('[data-open-money-adjust-dialog]').forEach(button => {
        button.addEventListener('click', openAdjustDialog);
    });
    adjustDialog.querySelectorAll('[data-close-money-adjust-dialog]').forEach(button => {
        button.addEventListener('click', () => adjustDialog.close());
    });
    adjustDialog.addEventListener('click', event => {
        if (event.target === adjustDialog) adjustDialog.close();
    });

    if (adjustDialog.hasAttribute('data-reopen')) openAdjustDialog();
})();
</script>
@endpush
