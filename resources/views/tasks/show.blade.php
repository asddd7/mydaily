@extends('layouts.app')

@section('title', $task->nama_tugas.' - Detail Tugas')

@push('styles')
    @vite('resources/css/tasks.css')
@endpush

@section('content')
<div class="tasks-page task-detail-page">
    <a class="task-detail-back" href="{{ route('tasks.index') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali ke daftar tugas
    </a>

    <article class="task-detail-card">
        <div class="task-detail-header">
            <div>
                <p class="tasks-eyebrow">Detail tugas</p>
                <h1 class="task-detail-title {{ $task->selesai ? 'is-complete' : '' }}">{{ $task->nama_tugas }}</h1>
            </div>
            <span class="task-detail-status {{ $task->selesai ? 'is-complete' : '' }}">
                <i class="fa-solid {{ $task->selesai ? 'fa-circle-check' : 'fa-clock' }}" aria-hidden="true"></i>
                {{ $task->selesai ? 'Selesai' : 'Belum selesai' }}
            </span>
        </div>

        <div class="task-detail-meta">
            <div>
                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                <span><small>Tenggat</small><strong>{{ $task->deadline }}</strong></span>
            </div>
            <div>
                <i class="fa-solid fa-tag" aria-hidden="true"></i>
                <span><small>Kategori</small><strong>{{ $task->kategori ?? 'daily' }}</strong></span>
            </div>
            <div>
                <i class="fa-solid fa-repeat" aria-hidden="true"></i>
                <span>
                    <small>Pengulangan</small>
                    <strong>{{ ['none' => 'Tidak berulang', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'][$task->recurring_type ?? 'none'] ?? 'Tidak berulang' }}</strong>
                </span>
            </div>
        </div>

        @if ($task->image)
            <section class="task-detail-image-section">
                <h2>Gambar tugas</h2>
                <a href="{{ route('tasks.image', $task->id) }}" target="_blank" rel="noopener">
                    <img class="task-detail-image" src="{{ route('tasks.image', $task->id) }}" alt="Gambar untuk tugas {{ $task->nama_tugas }}">
                </a>
            </section>
        @endif

        <section class="task-detail-subtasks" aria-labelledby="task-detail-subtasks-title">
            <div class="task-detail-section-heading">
                <div>
                    <p class="tasks-eyebrow">Langkah pekerjaan</p>
                    <h2 id="task-detail-subtasks-title">Subtugas</h2>
                </div>
                <span>{{ $subtasks->where('selesai', true)->count() }} / {{ $subtasks->count() }} selesai</span>
            </div>

            @if ($subtasks->isNotEmpty())
                <ul>
                    @foreach ($subtasks as $subtask)
                        <li class="{{ $subtask->selesai ? 'is-complete' : '' }}">
                            <form method="post" action="{{ route('tasks.subtasks.update', $subtask->id) }}" class="task-detail-subtask-check" data-loader-action="subtask-update">
                                @csrf
                                @method('put')
                                <input type="hidden" name="nama_tugas" value="{{ $subtask->nama_tugas }}">
                                <input type="hidden" name="selesai" value="0">
                                <input type="hidden" name="return_to_detail" value="1">
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
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="task-detail-empty">Belum ada subtugas untuk tugas ini.</p>
            @endif
        </section>

        <div class="task-detail-actions">
            <form method="post" action="{{ route('tasks.toggle', $task->id) }}" data-loader-action="toggle">
                @csrf
                <input type="hidden" name="selesai" value="{{ $task->selesai ? 0 : 1 }}">
                <button class="task-primary-button" type="submit">
                    <i class="fa-solid {{ $task->selesai ? 'fa-rotate-left' : 'fa-check' }}" aria-hidden="true"></i>
                    {{ $task->selesai ? 'Tandai belum selesai' : 'Tandai selesai' }}
                </button>
            </form>
        </div>
    </article>

    <dialog class="task-create-dialog task-subtask-dialog" data-subtask-dialog aria-labelledby="subtask-dialog-title" @if ($errors->any() && old('_form') === 'subtask_create') data-reopen @endif>
        <div class="task-dialog-heading">
            <div>
                <p class="tasks-eyebrow">Tugas: {{ $task->nama_tugas }}</p>
                <h2 id="subtask-dialog-title">Tambah subtugas</h2>
                <p>Tambahkan langkah kecil untuk menyelesaikan tugas ini.</p>
            </div>
            <button class="task-icon-button" type="button" data-close-subtask-dialog aria-label="Tutup dialog">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        @if ($errors->any() && old('_form') === 'subtask_create')
            <p class="task-dialog-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <form method="post" action="{{ route('tasks.subtasks.store', $task->id) }}" class="task-form" data-loader-action="subtask-create">
            @csrf
            <input type="hidden" name="_form" value="subtask_create">
            <input type="hidden" name="return_to_detail" value="1">
            <label class="task-field task-field-wide">Nama subtugas
                <input name="nama_subtask" value="{{ old('nama_subtask') }}" maxlength="100" placeholder="Contoh: Siapkan bahan" required>
            </label>
            <div class="task-dialog-actions task-field-wide">
                <button class="task-secondary-button" type="button" data-close-subtask-dialog>Batal</button>
                <button class="task-primary-button" type="submit">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Tambah subtugas
                </button>
            </div>
        </form>
    </dialog>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const dialog = document.querySelector('[data-subtask-dialog]');
    if (!(dialog instanceof HTMLDialogElement)) return;

    const openDialog = () => {
        if (!dialog.open) dialog.showModal();
        dialog.querySelector('[name="nama_subtask"]')?.focus();
    };

    document.addEventListener('task:open-subtask', openDialog);
    dialog.querySelectorAll('[data-close-subtask-dialog]').forEach(button => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });
    if (dialog.hasAttribute('data-reopen')) openDialog();
})();
</script>
@endpush
