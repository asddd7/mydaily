@extends('layouts.app')

@section('title', 'Tugas - MyDaily')

@push('styles')
    <link rel="stylesheet" href="{{ asset('tasks.css') }}">
@endpush

@section('content')
<div class="tasks-page">
    <div class="tasks-heading">
        <div>
            <p class="tasks-eyebrow">Perencanaan harian</p>
            <h1>Tugas</h1>
            <p class="tasks-intro">Atur prioritas, tenggat, dan subtugasmu di satu tempat.</p>
        </div>
        <div class="tasks-total" aria-label="{{ $tasks->count() }} tugas">
            <strong>{{ $tasks->count() }}</strong>
            <span>tugas</span>
        </div>
    </div>

    <div class="tasks-tools">
        <section class="card task-tool-card">
            <div class="task-section-heading">
                <span class="task-section-icon" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                <div>
                    <h2>Buat tugas baru</h2>
                    <p>Catat hal yang ingin kamu selesaikan.</p>
                </div>
            </div>
            <button class="task-primary-button" type="button" data-open-task-dialog aria-haspopup="dialog">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Tambah tugas
            </button>
        </section>

        <section class="card task-tool-card task-import-card">
            <div class="task-section-heading">
                <span class="task-section-icon task-import-icon" aria-hidden="true"><i class="fa-solid fa-file-arrow-up"></i></span>
                <div>
                    <h2>Import dari Excel</h2>
                    <p>Tambahkan banyak tugas sekaligus.</p>
                </div>
            </div>
            <div class="task-import-help">
                <p><strong>Format kolom:</strong></p>
                <ul>
                    <li>A — Nama tugas</li>
                    <li>B — Tenggat</li>
                    <li>C — Nama tugas induk (opsional)</li>
                </ul>
            </div>
            <a class="task-template-link" href="{{ asset('template_import.xlsx') }}">
                <i class="fa-solid fa-download" aria-hidden="true"></i> Download template Excel
            </a>
            <form method="post" action="{{ route('tasks.import') }}" enctype="multipart/form-data" class="task-import-form">
                @csrf
                <label class="task-field">Pilih file Excel
                    <input type="file" name="file_excel" accept=".xlsx,.xls" required>
                </label>
                <button class="task-secondary-button" type="submit">Import tugas</button>
            </form>
        </section>
    </div>

    <dialog class="task-create-dialog" data-task-dialog aria-labelledby="task-dialog-title" aria-describedby="task-dialog-description">
        <div class="task-dialog-heading">
            <div>
                <p class="tasks-eyebrow">Tugas baru</p>
                <h2 id="task-dialog-title">Tambah tugas</h2>
                <p id="task-dialog-description">Isi detail tugas yang ingin kamu selesaikan.</p>
            </div>
            <button class="task-icon-button" type="button" data-close-task-dialog aria-label="Tutup dialog">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        @if ($errors->any() && old('_form') === 'task_create')
            <p class="task-dialog-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <form method="post" action="{{ route('tasks.store') }}" class="task-form">
            @csrf
            <input type="hidden" name="_form" value="task_create">
            <label class="task-field task-field-wide">Nama tugas
                <input name="nama_tugas" value="{{ old('nama_tugas') }}" maxlength="100" placeholder="Contoh: Selesaikan laporan" required autofocus>
            </label>
            <label class="task-field">Tenggat
                <input type="date" name="deadline" value="{{ old('deadline', today()->toDateString()) }}" required>
            </label>
            <label class="task-field">Kategori
                <input name="kategori" value="{{ old('kategori', 'daily') }}" maxlength="100" placeholder="Contoh: kerja">
            </label>
            <label class="task-field task-field-wide">Pengulangan
                <select name="recurring_type">
                    @foreach (['none' => 'Tidak berulang', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('recurring_type', 'none') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="task-dialog-actions task-field-wide">
                <button class="task-secondary-button" type="button" data-close-task-dialog>Batal</button>
                <button class="task-primary-button" type="submit">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Tambah tugas
                </button>
            </div>
        </form>
    </dialog>

    <section class="task-list-section" aria-labelledby="task-list-title">
        <div class="task-list-heading">
            <div>
                <p class="tasks-eyebrow">Daftar pekerjaan</p>
                <h2 id="task-list-title">Tugas kamu</h2>
            </div>
            <span>{{ $tasks->count() }} item</span>
        </div>

        @forelse ($tasks as $task)
            <article class="card task-card">
                <div class="task-card-main">
                    <form method="post" action="{{ route('tasks.toggle', $task->id) }}" class="task-check-form">
                        @csrf
                        <input type="hidden" name="selesai" value="0">
                        <input
                            class="task-checkbox"
                            type="checkbox"
                            name="selesai"
                            value="1"
                            @checked($task->selesai)
                            onchange="this.form.requestSubmit()"
                            aria-label="Tandai {{ $task->nama_tugas }} sebagai {{ $task->selesai ? 'belum selesai' : 'selesai' }}"
                        >
                    </form>
                    <div class="task-card-info">
                        <h3 class="task-name {{ $task->selesai ? 'is-complete' : '' }}">{{ $task->nama_tugas }}</h3>
                        <div class="task-meta">
                            <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $task->deadline }}</span>
                            <span class="task-category">{{ $task->kategori ?? 'daily' }}</span>
                            @if ($task->recurring_type && $task->recurring_type !== 'none')
                                <span><i class="fa-solid fa-repeat" aria-hidden="true"></i>
                                    {{ ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'][$task->recurring_type] ?? 'Berulang' }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="task-actions">
                        <form method="post" action="{{ route('tasks.copy', $task->id) }}">
                            @csrf
                            <button class="task-icon-button" type="submit" aria-label="Salin tugas {{ $task->nama_tugas }}" title="Salin tugas">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </form>
                        <form method="post" action="{{ route('tasks.destroy', $task->id) }}" onsubmit="return confirm('Hapus tugas dan seluruh subtugas?')">
                            @csrf
                            @method('delete')
                            <button class="task-icon-button task-delete-button" type="submit" aria-label="Hapus tugas {{ $task->nama_tugas }}" title="Hapus tugas">
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <details class="task-edit">
                    <summary><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i> Edit detail tugas</summary>
                    <form method="post" action="{{ route('tasks.update', $task->id) }}" class="task-form task-edit-form">
                        @csrf
                        @method('put')
                        <label class="task-field task-field-wide">Nama tugas
                            <input name="nama_tugas" value="{{ $task->nama_tugas }}" maxlength="100" required>
                        </label>
                        <label class="task-field">Tenggat
                            <input type="date" name="deadline" value="{{ $task->deadline }}" required>
                        </label>
                        <label class="task-field">Kategori
                            <input name="kategori" value="{{ $task->kategori }}" maxlength="100">
                        </label>
                        <label class="task-field task-field-wide">Pengulangan
                            <select name="recurring_type">
                                @foreach (['none' => 'Tidak berulang', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $value => $label)
                                    <option value="{{ $value }}" @selected($task->recurring_type === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="task-primary-button task-field-wide" type="submit">Simpan perubahan</button>
                    </form>
                </details>

                <div class="task-subtasks">
                    <h4>Subtugas <span>{{ $subtasks->get($task->id, collect())->count() }}</span></h4>
                    @if ($subtasks->get($task->id, collect())->isNotEmpty())
                        <ul class="task-subtask-list">
                            @foreach ($subtasks->get($task->id, collect()) as $subtask)
                                <li class="task-subtask">
                                    <form method="post" action="{{ route('tasks.subtasks.update', $subtask->id) }}" class="task-subtask-check">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="nama_tugas" value="{{ $subtask->nama_tugas }}">
                                        <input type="hidden" name="selesai" value="0">
                                        <input
                                            class="task-checkbox"
                                            type="checkbox"
                                            name="selesai"
                                            value="1"
                                            @checked($subtask->selesai)
                                            onchange="this.form.requestSubmit()"
                                            aria-label="Tandai subtugas {{ $subtask->nama_tugas }} sebagai {{ $subtask->selesai ? 'belum selesai' : 'selesai' }}"
                                        >
                                        <span class="{{ $subtask->selesai ? 'is-complete' : '' }}">{{ $subtask->nama_tugas }}</span>
                                    </form>
                                    <form method="post" action="{{ route('tasks.subtasks.destroy', $subtask->id) }}" onsubmit="return confirm('Hapus subtugas ini?')">
                                        @csrf
                                        @method('delete')
                                        <button class="task-subtask-delete" type="submit" aria-label="Hapus subtugas {{ $subtask->nama_tugas }}" title="Hapus subtugas">
                                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="task-no-subtasks">Belum ada subtugas.</p>
                    @endif

                    <form method="post" action="{{ route('tasks.subtasks.store', $task->id) }}" class="task-add-subtask">
                        @csrf
                        <label class="task-field">Tambah subtugas
                            <input name="nama_subtask" maxlength="100" placeholder="Tulis subtugas..." required>
                        </label>
                        <button class="task-secondary-button" type="submit">Tambah</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card task-empty">
                <span class="task-empty-icon" aria-hidden="true"><i class="fa-regular fa-circle-check"></i></span>
                <h3>Belum ada tugas</h3>
                <p>Buat tugas pertama untuk mulai mengatur harimu.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const dialog = document.querySelector('[data-task-dialog]');
    const openButton = document.querySelector('[data-open-task-dialog]');
    if (!(dialog instanceof HTMLDialogElement) || !(openButton instanceof HTMLButtonElement)) return;

    const closeButtons = dialog.querySelectorAll('[data-close-task-dialog]');
    const taskName = dialog.querySelector('[name="nama_tugas"]');

    const openDialog = () => {
        if (!dialog.open) dialog.showModal();
        taskName?.focus();
    };

    openButton.addEventListener('click', openDialog);
    closeButtons.forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });

    if (dialog.querySelector('.task-dialog-error')) openDialog();
})();
</script>
@endpush
