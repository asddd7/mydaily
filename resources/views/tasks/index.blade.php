@extends('layouts.app')

@section('title', 'Tugas - MyDaily')

@push('styles')
    @vite('resources/css/tasks.css')
@endpush

@section('content')
<div class="tasks-page" data-preserve-scroll>
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
            <form method="post" action="{{ route('tasks.import') }}" enctype="multipart/form-data" class="task-import-form" data-loader-action="import">
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
        <form method="post" action="{{ route('tasks.store') }}" class="task-form" enctype="multipart/form-data" data-loader-action="create">
            @csrf
            <input type="hidden" name="_form" value="task_create">
            <label class="task-field task-field-wide">Nama tugas
                <input name="nama_tugas" value="{{ old('nama_tugas') }}" maxlength="100" placeholder="Contoh: Selesaikan laporan" required autofocus>
            </label>
            <label class="task-field" data-deadline-field>Tenggat
                <input type="date" name="deadline" value="{{ old('deadline', today()->toDateString()) }}" required>
            </label>
            <p class="task-recurring-deadline-note task-field-wide" data-deadline-note hidden>Tugas berulang akan dimulai hari ini.</p>
            <div class="task-reminder-field task-field-wide" data-reminder-control>
                <label class="task-reminder-toggle">
                    <input type="hidden" name="reminder_enabled" value="0">
                    <input type="checkbox" name="reminder_enabled" value="1" data-reminder-toggle @checked(old('reminder_enabled', '0') === '1')>
                    <span>Aktifkan notifikasi untuk tugas ini</span>
                </label>
                <label class="task-field" data-reminder-repeat @if (old('reminder_enabled', '0') !== '1') hidden @endif>Template pengingat
                    <select name="reminder_repeat_type" @if (old('reminder_enabled', '0') !== '1') disabled @endif>
                        @foreach (['once' => 'Custom (sekali)', 'every_minute' => 'Setiap menit', 'hourly' => 'Setiap jam', 'daily' => 'Harian', 'weekly' => 'Mingguan'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('reminder_repeat_type', 'once') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="task-field-wide task-reminder-template-note" data-reminder-template-note @if (old('reminder_enabled', '0') !== '1') hidden @endif>Untuk pengingat berulang, pilih waktu mulai pengingat pertama.</p>
                <label class="task-field" data-reminder-time @if (old('reminder_enabled', '0') !== '1') hidden @endif>Waktu mulai / pengingat pertama (WIB)
                    <input type="datetime-local" name="reminder_at" value="{{ old('reminder_at') }}" @if (old('reminder_enabled', '0') === '1') required @else disabled @endif>
                </label>
            </div>
            <label class="task-field">Kategori
                <select name="kategori" required>
                    @foreach (['work' => 'Work', 'casual' => 'Casual', 'game' => 'Game', 'urgent' => 'Urgent'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('kategori', 'work') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="task-field task-field-wide">Pengulangan
                <select name="recurring_type">
                    @foreach (['none' => 'Tidak berulang', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('recurring_type', 'none') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="task-field task-field-wide">Gambar (opsional)
                <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
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

            <div class="task-list-heading-actions">
                <span>
                    {{ $tasks->count() }}
                    {{ ($search !== '' || $category !== '') ? 'hasil' : 'item' }}
                </span>

                @if ($showAll)
                    <a
                        class="task-secondary-button"
                        href="{{ route('tasks.index', request()->except('show_all')) }}"
                    >
                        <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        Sembunyikan tugas selesai
                    </a>
                @else
                    <a
                        class="task-secondary-button"
                        href="{{ route('tasks.index', array_merge(request()->except('show_all'), ['show_all' => 1])) }}"
                    >
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                        Tampilkan semua
                    </a>
                @endif
            </div>
        </div>

        <form method="get" action="{{ route('tasks.index') }}" class="task-search-form" role="search">
            @if ($showAll)
                <input type="hidden" name="show_all" value="1">
            @endif
            @if (request()->query('date'))
                <input type="hidden" name="date" value="{{ request()->query('date') }}">
            @endif
            <label class="task-search-field">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari tugas atau subtugas..." maxlength="100" aria-label="Cari nama tugas atau subtugas">
            </label>
            <label class="task-category-filter">
                <span>Kategori</span>
                <select name="category" aria-label="Filter kategori tugas">
                    <option value="">Semua kategori</option>
                    @foreach ($categoryOptions as $value => $label)
                        <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="task-secondary-button" type="submit">Cari</button>
            @if ($search !== '' || $category !== '')
                <a class="task-search-clear" href="{{ route('tasks.index', request()->query('date') ? ['date' => request()->query('date')] : []) }}">Hapus filter</a>
            @endif
        </form>

        @forelse ($tasks as $task)
            <article class="card task-card" id="task-{{ $task->id }}" tabindex="-1">
                <div class="task-card-main">
                    <form method="post" action="{{ route('tasks.toggle', $task->id) }}" class="task-check-form" data-loader-action="toggle">
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
                        @if ($task->image)
                            <img class="task-image-preview" src="{{ route('tasks.image', $task->id) }}" alt="Gambar untuk tugas {{ $task->nama_tugas }}" loading="lazy">
                        @endif
                        <div class="task-meta">
                            <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $task->deadline }}</span>
                            <span class="task-category">{{ $task->kategori ?? 'daily' }}</span>
                            @if ($task->reminder_at)
                                <span class="task-reminder-badge"><i class="fa-regular fa-bell" aria-hidden="true"></i> {{ \Illuminate\Support\Carbon::parse($task->reminder_at)->format('d M Y, H:i') }}</span>
                            @endif
                            @if ($task->recurring_type && $task->recurring_type !== 'none')
                                <span><i class="fa-solid fa-repeat" aria-hidden="true"></i>
                                    {{ ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'][$task->recurring_type] ?? 'Berulang' }}
                                </span>
                            @endif
                        </div>
                        <a class="task-detail-link" href="{{ route('tasks.show', $task->id) }}">
                            Detail selengkapnya <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="task-actions">
                        <form method="post" action="{{ route('tasks.copy', $task->id) }}" data-loader-action="copy">
                            @csrf
                            <button class="task-icon-button" type="submit" aria-label="Salin tugas {{ $task->nama_tugas }}" title="Salin tugas">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </form>
                        <button
                            class="task-icon-button"
                            type="button"
                            data-open-task-edit
                            data-dialog-target="editTaskDialog{{ $task->id }}"
                            aria-label="Edit tugas {{ $task->nama_tugas }}"
                            title="Edit tugas"
                            aria-haspopup="dialog"
                        >
                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                        </button>
                        <form method="post" action="{{ route('tasks.destroy', $task->id) }}" data-loader-action="delete" onsubmit="return confirm('Hapus tugas dan seluruh subtugas?')">
                            @csrf
                            @method('delete')
                            <button class="task-icon-button task-delete-button" type="submit" aria-label="Hapus tugas {{ $task->nama_tugas }}" title="Hapus tugas">
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </div>

                @php($isEditError = $errors->any() && old('_form') === 'task_edit' && (string) old('_task_id') === (string) $task->id)
                <dialog
                    class="task-edit-dialog"
                    id="editTaskDialog{{ $task->id }}"
                    aria-labelledby="editTaskTitle{{ $task->id }}"
                    aria-describedby="editTaskDescription{{ $task->id }}"
                    @if ($isEditError) data-reopen @endif
                >
                    <div class="task-dialog-heading">
                        <div>
                            <p class="tasks-eyebrow">Edit tugas</p>
                            <h2 id="editTaskTitle{{ $task->id }}">Ubah detail tugas</h2>
                            <p id="editTaskDescription{{ $task->id }}">Perbarui nama, tenggat, kategori, atau pengulangan.</p>
                        </div>
                        <button class="task-icon-button" type="button" data-close-task-dialog aria-label="Tutup dialog">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                    @if ($isEditError)
                        <p class="task-dialog-error" role="alert">{{ $errors->first() }}</p>
                    @endif
                    <form method="post" action="{{ route('tasks.update', $task->id) }}" class="task-form" data-loader-action="update">
                        @csrf
                        @method('put')
                        <input type="hidden" name="_form" value="task_edit">
                        <input type="hidden" name="_task_id" value="{{ $task->id }}">
                        <label class="task-field task-field-wide">Nama tugas
                            <input name="nama_tugas" value="{{ $isEditError ? old('nama_tugas', $task->nama_tugas) : $task->nama_tugas }}" maxlength="100" required>
                        </label>
                        <label class="task-field" data-deadline-field>Tenggat
                            <input type="date" name="deadline" value="{{ $isEditError ? old('deadline', $task->deadline) : $task->deadline }}" required>
                        </label>
                        <p class="task-recurring-deadline-note task-field-wide" data-deadline-note hidden>Tanggal tugas saat ini tetap digunakan sebagai tanggal awal.</p>
                        @php($reminderEnabled = $isEditError ? (bool) old('reminder_enabled', $task->reminder_at ? '1' : '0') : (bool) $task->reminder_at)
                        <div class="task-reminder-field task-field-wide" data-reminder-control>
                            <label class="task-reminder-toggle">
                                <input type="hidden" name="reminder_enabled" value="0">
                                <input type="checkbox" name="reminder_enabled" value="1" data-reminder-toggle @checked($reminderEnabled)>
                                <span>Aktifkan notifikasi untuk tugas ini</span>
                            </label>
                            @php($selectedReminderRepeat = $isEditError ? old('reminder_repeat_type', $task->reminder_repeat_type ?? 'once') : ($task->reminder_repeat_type ?? 'once'))
                            <label class="task-field" data-reminder-repeat @if (!$reminderEnabled) hidden @endif>Template pengingat
                                <select name="reminder_repeat_type" @if (!$reminderEnabled) disabled @endif>
                                    @foreach (['once' => 'Custom (sekali)', 'every_minute' => 'Setiap menit', 'hourly' => 'Setiap jam', 'daily' => 'Harian', 'weekly' => 'Mingguan'] as $value => $label)
                                        <option value="{{ $value }}" @selected($selectedReminderRepeat === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <p class="task-field-wide task-reminder-template-note" data-reminder-template-note @if (!$reminderEnabled) hidden @endif>Untuk pengingat berulang, pilih waktu mulai pengingat pertama.</p>
                            <label class="task-field" data-reminder-time @if (!$reminderEnabled) hidden @endif>Waktu mulai / pengingat pertama (WIB)
                                <input type="datetime-local" name="reminder_at" value="{{ $isEditError ? old('reminder_at', $task->reminder_at ? \Illuminate\Support\Carbon::parse($task->reminder_at)->format('Y-m-d\TH:i') : '') : ($task->reminder_at ? \Illuminate\Support\Carbon::parse($task->reminder_at)->format('Y-m-d\TH:i') : '') }}" @if ($reminderEnabled) required @else disabled @endif>
                            </label>
                        </div>
                        <label class="task-field">Kategori
                            @php($selectedCategory = $isEditError ? old('kategori', $task->kategori ?? 'daily') : ($task->kategori ?? 'daily'))
                            <select name="kategori" required>
                                @foreach (['work' => 'Work', 'casual' => 'Casual', 'game' => 'Game', 'urgent' => 'Urgent'] as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                                @endforeach
                                @if (!in_array($selectedCategory, ['work', 'casual', 'game', 'urgent'], true))
                                    <option value="{{ $selectedCategory }}" selected>{{ ucfirst($selectedCategory) }} (kategori lama)</option>
                                @endif
                            </select>
                        </label>
                        <label class="task-field task-field-wide">Pengulangan
                            <select name="recurring_type">
                                @foreach (['none' => 'Tidak berulang', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($isEditError ? old('recurring_type', $task->recurring_type) : $task->recurring_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="task-dialog-actions task-field-wide">
                            <button class="task-secondary-button" type="button" data-close-task-dialog>Batal</button>
                            <button class="task-primary-button" type="submit">Simpan perubahan</button>
                        </div>
                    </form>
                </dialog>

                <details class="task-subtasks" data-subtask-parent-id="{{ $task->id }}">
                    <summary class="task-subtasks-heading">
                        <span>Subtugas</span>
                        <span class="task-subtask-count">{{ $subtasks->get($task->id, collect())->count() }}</span>
                    </summary>
                    @if ($subtasks->get($task->id, collect())->isNotEmpty())
                        <div class="task-subtask-toolbar">
                            <label class="task-subtask-select-all">
                                <input type="checkbox" data-select-all-subtasks aria-label="Pilih semua subtugas {{ $task->nama_tugas }}">
                                <span>Pilih semua</span>
                            </label>
                            <form
                                method="post"
                                action="{{ route('tasks.subtasks.bulk-destroy', $task->id) }}"
                                id="deleteSubtasksForm{{ $task->id }}"
                                data-loader-action="subtask-delete"
                                onsubmit="return confirm('Hapus subtugas yang dipilih?')"
                            >
                                @csrf
                                @method('delete')
                                <button class="task-subtask-bulk-delete" type="submit" data-delete-selected-subtasks disabled>
                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                    Hapus terpilih (<span data-selected-subtask-count>0</span>)
                                </button>
                            </form>
                        </div>
                        <ul class="task-subtask-list">
                            @foreach ($subtasks->get($task->id, collect()) as $subtask)
                                <li class="task-subtask">
                                    <form method="post" action="{{ route('tasks.subtasks.update', $subtask->id) }}" class="task-subtask-check" data-loader-action="subtask-update">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="nama_tugas" value="{{ $subtask->nama_tugas }}">
                                        <input type="hidden" name="selesai" value="0">
                                        <input
                                            class="task-subtask-select"
                                            type="checkbox"
                                            name="subtask_ids[]"
                                            value="{{ $subtask->id }}"
                                            form="deleteSubtasksForm{{ $task->id }}"
                                            aria-label="Pilih subtugas {{ $subtask->nama_tugas }}"
                                        >
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
                                    <form method="post" action="{{ route('tasks.subtasks.destroy', $subtask->id) }}" data-loader-action="subtask-delete" onsubmit="return confirm('Hapus subtugas ini?')">
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

                    <form method="post" action="{{ route('tasks.subtasks.store', $task->id) }}" class="task-add-subtask" data-loader-action="subtask-create">
                        @csrf
                        <label class="task-field">Tambah subtugas
                            <input name="nama_subtask" maxlength="100" placeholder="Tulis subtugas..." required>
                        </label>
                        <button class="task-secondary-button" type="submit">Tambah</button>
                    </form>
                </details>
            </article>
        @empty
            <div class="card task-empty">
@if ($search !== '' || $category !== '')
                    <span class="task-empty-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <h3>Tugas tidak ditemukan</h3>
                    <p>Tidak ada tugas yang cocok dengan filter yang dipilih.</p>
                @else
                    <span class="task-empty-icon" aria-hidden="true"><i class="fa-regular fa-circle-check"></i></span>
                    <h3>Belum ada tugas</h3>
                    <p>Buat tugas pertama untuk mulai mengatur harimu.</p>
                @endif
            </div>
        @endforelse
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    document.querySelectorAll('.task-form').forEach(form => {
        const reminderToggle = form.querySelector('[data-reminder-toggle]');
        const reminderTime = form.querySelector('[data-reminder-time]');
        const reminderInput = reminderTime?.querySelector('[name="reminder_at"]');
        const reminderRepeat = form.querySelector('[data-reminder-repeat]');
        const reminderRepeatInput = reminderRepeat?.querySelector('[name="reminder_repeat_type"]');
        const reminderTemplateNote = form.querySelector('[data-reminder-template-note]');
        if (reminderToggle instanceof HTMLInputElement && reminderTime instanceof HTMLElement && reminderInput instanceof HTMLInputElement) {
            const updateReminderVisibility = () => {
                reminderTime.hidden = !reminderToggle.checked;
                reminderInput.disabled = !reminderToggle.checked;
                reminderInput.required = reminderToggle.checked;
                if (reminderRepeat instanceof HTMLElement) reminderRepeat.hidden = !reminderToggle.checked;
                if (reminderRepeatInput instanceof HTMLSelectElement) reminderRepeatInput.disabled = !reminderToggle.checked;
                if (reminderTemplateNote instanceof HTMLElement) reminderTemplateNote.hidden = !reminderToggle.checked;
            };
            reminderToggle.addEventListener('change', updateReminderVisibility);
            updateReminderVisibility();
        }

        const recurrence = form.querySelector('[name="recurring_type"]');
        const deadlineField = form.querySelector('[data-deadline-field]');
        const deadlineInput = deadlineField?.querySelector('[name="deadline"]');
        const deadlineNote = form.querySelector('[data-deadline-note]');
        if (!(recurrence instanceof HTMLSelectElement) || !(deadlineField instanceof HTMLElement) || !(deadlineInput instanceof HTMLInputElement)) return;

        const updateDeadlineVisibility = () => {
            const recurring = recurrence.value !== 'none';
            deadlineField.hidden = recurring;
            deadlineInput.required = !recurring;
            if (deadlineNote instanceof HTMLElement) deadlineNote.hidden = !recurring;
        };

        recurrence.addEventListener('change', updateDeadlineVisibility);
        updateDeadlineVisibility();
    });

    const dialog = document.querySelector('[data-task-dialog]');
    const openDialog = (targetDialog) => {
        if (!targetDialog.open) targetDialog.showModal();
        targetDialog.querySelector('[name="nama_tugas"]')?.focus();
    };

    if (dialog instanceof HTMLDialogElement) {
        document.addEventListener('task:open-create', () => openDialog(dialog));
        document.querySelectorAll('[data-open-task-dialog]').forEach(openButton => {
            openButton.addEventListener('click', () => openDialog(dialog));
        });
        if (dialog.querySelector('.task-dialog-error')) openDialog(dialog);
    }

    document.querySelectorAll('[data-open-task-edit]').forEach(button => {
        if (!(button instanceof HTMLButtonElement)) return;

        button.addEventListener('click', () => {
            const targetId = button.dataset.dialogTarget;
            const editDialog = targetId ? document.getElementById(targetId) : null;
            if (editDialog instanceof HTMLDialogElement) openDialog(editDialog);
        });
    });

    document.querySelectorAll('.task-create-dialog, .task-edit-dialog').forEach(taskDialog => {
        if (!(taskDialog instanceof HTMLDialogElement)) return;

        taskDialog.querySelectorAll('[data-close-task-dialog]').forEach(button => {
            button.addEventListener('click', () => taskDialog.close());
        });
        taskDialog.addEventListener('click', event => {
            if (event.target === taskDialog) taskDialog.close();
        });
        if (taskDialog.hasAttribute('data-reopen')) openDialog(taskDialog);
    });

    document.querySelectorAll('.task-subtasks').forEach(details => {
        const selectAll = details.querySelector('[data-select-all-subtasks]');
        const selections = [...details.querySelectorAll('.task-subtask-select')];
        const deleteButton = details.querySelector('[data-delete-selected-subtasks]');
        const selectedCount = details.querySelector('[data-selected-subtask-count]');

        if (!(selectAll instanceof HTMLInputElement) || !(deleteButton instanceof HTMLButtonElement)) return;

        const updateSelection = () => {
            const selected = selections.filter(checkbox => checkbox.checked).length;
            selectAll.checked = selected === selections.length;
            selectAll.indeterminate = selected > 0 && selected < selections.length;
            deleteButton.disabled = selected === 0;
            if (selectedCount) selectedCount.textContent = String(selected);
        };

        selectAll.addEventListener('change', () => {
            selections.forEach(checkbox => { checkbox.checked = selectAll.checked; });
            updateSelection();
        });
        selections.forEach(checkbox => checkbox.addEventListener('change', updateSelection));
    });
})();
</script>
@endpush
