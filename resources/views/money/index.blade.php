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
        <article class="money-balance-card money-balance-card-limit">
            <span class="money-balance-icon money-balance-icon-limit" aria-hidden="true">
                <i class="fa-solid fa-chart-pie"></i>
            </span>

            <div class="money-limit-content">
                <p>Limit bulanan · {{ now()->format('m/Y') }}</p>

                @if ($monthlyLimit !== null)
                    <strong>
                        Rp {{ number_format((float) $monthlyLimit, 0, ',', '.') }}
                    </strong>
                @else
                    <strong>Belum diatur</strong>
                @endif

                <small>
                    Terpakai: Rp {{ number_format((float) $monthlySpent, 0, ',', '.') }}
                </small>

                @if ($monthlyLimit !== null)
                    <div
                        class="money-limit-progress"
                        role="progressbar"
                        aria-label="Penggunaan limit bulanan"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="{{ $monthlyLimitProgress }}"
                    >
                        <span style="width: {{ $monthlyLimitProgress }}%"></span>
                    </div>

                    @if ((float) $monthlySpent > (float) $monthlyLimit)
                        <small class="money-limit-warning">
                            Pengeluaran sudah melebihi limit.
                        </small>
                    @else
                        <small>
                            {{ $monthlyLimitProgress }}% dari limit digunakan
                        </small>
                    @endif
                @endif

                <button
                    class="money-limit-manage-button"
                    type="button"
                    data-open-money-limit-dialog
                    aria-haspopup="dialog"
                >
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    {{ $monthlyLimit !== null ? 'Atur limit' : 'Tambah limit' }}
                </button>
            </div>
        </article>
    </section>
    @if (session('status'))
        <p class="money-limit-notice" role="status">
            {{ session('status') }}
        </p>
    @endif

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
        <label for="receiptImage">
            Foto struk (bisa pilih beberapa)
        <input
            id="receiptImage"
            type="file"
            name="receipt_images[]"
            accept="image/jpeg,image/png,image/webp"
            multiple
        >
        </label>
        <small>Pilih beberapa foto struk sekaligus. Maksimal 10 MB per foto.</small>

        <label class="money-receipt-save-option">
            <input
                type="checkbox"
                name="save_receipt_images"
                value="1"
                form="moneyCreateForm"
                data-save-receipt-images
            >

            <span>
                <strong>Simpan gambar struk sebagai bukti pembayaran</strong>
                <small>
                    Jika dicentang, foto struk akan disimpan secara privat
                    dan dapat dilihat dari riwayat transaksi.
                </small>
            </span>
        </label>

        <small class="money-receipt-selected" data-receipt-selected aria-live="polite">Belum ada foto dipilih.</small>

        <button type="button" class="money-clear-receipts" data-clear-receipt-images disabled>
            Hapus semua foto
        </button>

        <button type="button" class="money-scan-button" data-scan-submit>
            <i class="fa-solid fa-camera" aria-hidden="true"></i>
            Baca semua foto
        </button>

        <p
            class="money-scan-status"
            data-scan-status
            role="status"
            aria-live="polite"
        ></p>

        <details class="money-ocr-text" data-ocr-text-container hidden>
            <summary>Teks hasil pembacaan semua foto</summary>
            <pre data-ocr-text></pre>
        </details>
        </div>
        <form
            method="post"
            action="{{ route('money.store') }}"
            class="money-entry-form"
            id="moneyCreateForm"
            enctype="multipart/form-data"
        >
            @csrf
            <input type="hidden" name="_form" value="money_create">
            <label>Tanggal transaksi
                <input type="date" name="tanggal" value="{{ old('tanggal', today()->toDateString()) }}" required>
            </label>
            <label>Jenis transaksi
                <select name="type" required>
                    <option value="income" @selected(old('type', 'income') === 'income')>Pemasukan</option>
                    <option value="expense" @selected(old('type') === 'expense')>Pengeluaran</option>
                </select>
            </label>
            <label>Metode pembayaran
                <select name="payment_method" required>
                    <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                    <option value="online" @selected(old('payment_method') === 'online')>Saldo online</option>
                </select>
            </label>
            <label class="money-field-wide">Transaksi
                <input name="category" value="{{ old('category') }}" maxlength="100" placeholder="Contoh: Beli makan" required>
            </label>
            <label class="money-field-wide">Keterangan tambahan
                <textarea name="description" maxlength="1000" rows="2" placeholder="Catatan transaksi (opsional)">{{ old('description') }}</textarea>
            </label>
            <section class="money-line-items money-field-wide" data-line-items>
                <div class="money-line-items-heading">
                    <div>
                        <h3>Rincian barang <span>(opsional)</span></h3>
                        <p>Tambahkan nama barang dan harganya untuk menghitung grand total.</p>
                    </div>
                    <button type="button" class="money-add-item-button" data-add-item>
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Tambah barang
                    </button>
                </div>
                <div class="money-line-item-list" data-item-list>
                    @foreach (old('items', []) as $index => $item)
                        <div class="money-line-item" data-item-row>
                            <label>Nama barang
                                <input name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" maxlength="150" placeholder="Contoh: Nasi goreng" required>
                            </label>
                            <label>Harga
                                <input name="items[{{ $index }}][amount]" type="number" min="0.01" max="9999999999" step="0.01" value="{{ $item['amount'] ?? '' }}" placeholder="0" required data-item-amount>
                            </label>
                            <button type="button" class="money-remove-item-button" data-remove-item aria-label="Hapus barang">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </section>
            <label class="money-field-wide money-grand-total">Grand total
                <input type="number" name="amount" min="0.01" max="9999999999" step="0.01" value="{{ old('amount') }}" placeholder="Isi total atau tambahkan rincian barang" required data-grand-total>
                <small data-total-hint>Isi grand total secara manual, atau tambahkan rincian barang agar total dihitung otomatis.</small>
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

    <dialog
        class="money-create-dialog money-limit-dialog"
        data-money-limit-dialog
        aria-labelledby="money-limit-dialog-title"
        aria-describedby="money-limit-dialog-description"
        @if ($errors->any() && old('_form') === 'money_limit') data-reopen @endif
    >
        <div class="money-dialog-heading">
            <div>
                <p class="money-eyebrow">Perencanaan keuangan</p>
                <h2 id="money-limit-dialog-title">Tambah/Atur Limit Bulanan</h2>
                <p id="money-limit-dialog-description">
                    Atur batas maksimal pengeluaran untuk bulan berjalan.
                </p>
            </div>

            <button
                class="money-dialog-close"
                type="button"
                data-close-money-limit-dialog
                aria-label="Tutup dialog"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        @if ($errors->any() && old('_form') === 'money_limit')
            <p class="money-dialog-error" role="alert">
                {{ $errors->first() }}
            </p>
        @endif

        <form
            method="post"
            action="{{ route('money.limit.store') }}"
            class="money-entry-form money-limit-form"
        >
            @csrf
            <input type="hidden" name="_form" value="money_limit">

            <label>
                Bulan limit
                <input
                    type="text"
                    value="{{ now()->format('m/Y') }}"
                    readonly
                >
            </label>

            <label>
                Limit pengeluaran (Rp)
                <input
                    type="number"
                    name="monthly_limit"
                    min="0.01"
                    max="9999999999.99"
                    step="0.01"
                    value="{{ old('monthly_limit', $monthlyLimit) }}"
                    placeholder="Contoh: 1500000"
                    required
                    autofocus
                >
                <small>
                    Limit berlaku untuk total transaksi pengeluaran bulan ini.
                </small>
            </label>

            <div class="money-dialog-actions money-field-wide">
                <button
                    class="money-dialog-cancel"
                    type="button"
                    data-close-money-limit-dialog
                >
                    Batal
                </button>

                <button class="money-primary-button" type="submit">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Simpan limit
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
                        <th scope="col">Transaksi</th>
                        <th scope="col">Jumlah</th>
                        <th scope="col">Metode</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr class="money-transaction-row is-collapsed">
                            <td data-label="Tanggal" class="money-history-date">
                                <div class="money-history-date-content">
                                    <button
                                        class="money-history-detail-toggle"
                                        type="button"
                                        aria-expanded="false"
                                        aria-controls="money-transaction-detail-{{ $transaction->id }}"
                                        aria-label="Tampilkan detail transaksi {{ $transaction->category }}"
                                        title="Tampilkan detail transaksi"
                                        data-money-detail-toggle
                                    >
                                        <span>{{ $transaction->tanggal }}</span>
                                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                            <td data-label="Jenis">
                                <span class="money-type-badge {{ $transaction->type === 'income' ? 'money-type-income' : 'money-type-expense' }}">
                                    {{ $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                </span>
                            </td>
                            <td data-label="Transaksi" class="money-history-description">
                                @php($items = $transactionItems->get($transaction->id, collect()))
                                @php($receipts = $transactionReceipts->get($transaction->id, collect()))
                                <div class="money-history-detail-content" id="money-transaction-detail-{{ $transaction->id }}" hidden>
                                    <span class="money-history-category" title="{{ $transaction->category }}">{{ $transaction->category }}</span>
                                    @if ($transaction->description)
                                        <small>{{ $transaction->description }}</small>
                                    @endif
                                    @if ($items->isNotEmpty())
                                        <div class="money-history-items">
                                            <strong>{{ $items->count() }} barang</strong>
                                            <ul>
                                                @foreach ($items as $item)
                                                    <li><span>{{ $item->name }}</span><strong>Rp {{ number_format((float) $item->amount, 0, ',', '.') }}</strong></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    @if ($receipts->isNotEmpty())
                                        <div class="money-history-receipts">
                                            <strong>
                                                Bukti pembayaran ({{ $receipts->count() }} foto)
                                            </strong>

                                            <ul>
                                                @foreach ($receipts as $receipt)
                                                    <li>
                                                        <a
                                                            href="{{ route('money.receipt', [
                                                                'transaction' => $transaction->id,
                                                                'receipt' => $receipt->id,
                                                            ]) }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                        >
                                                            Lihat foto {{ $loop->iteration }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </td>
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
    @vite('resources/js/money-transaction-items.js')
<script>
(() => {
    const detailToggles = document.querySelectorAll('[data-money-detail-toggle]');
    const setDetailExpanded = (button, expanded) => {
        const detailId = button.getAttribute('aria-controls');
        const detail = detailId ? document.getElementById(detailId) : null;
        if (!(detail instanceof HTMLElement)) return;

        button.setAttribute('aria-expanded', String(expanded));
        detail.hidden = !expanded;
        button.closest('.money-transaction-row')?.classList.toggle('is-collapsed', !expanded);
        const category = detail.querySelector('.money-history-category')?.textContent?.trim() ?? '';
        button.title = `${expanded ? 'Minimalkan' : 'Tampilkan'} detail transaksi`;
        button.setAttribute(
            'aria-label',
            `${expanded ? 'Minimalkan' : 'Tampilkan'} detail transaksi ${category}`
        );
    };

    detailToggles.forEach(button => {
        button.addEventListener('click', () => {
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                detailToggles.forEach(toggle => setDetailExpanded(toggle, false));
                return;
            }

            setDetailExpanded(button, true);
        });
    });

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
