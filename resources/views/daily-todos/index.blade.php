@extends('layouts.app')

@section('title', 'Daily To-do - MyDaily')

@push('styles')
    @vite('resources/css/daily-todos.css')
@endpush

@section('content')
<div class="daily-todos-page">
    <header class="daily-todos-header">
        <div>
            <p class="daily-todos-eyebrow">DAFTAR HARIAN</p>
            <h1>Daily To-do</h1>
            <p>Catat hal-hal yang ingin kamu selesaikan hari ini.</p>
        </div>
        <button class="daily-todos-add-trigger" type="button" data-open-daily-todo-dialog>
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Tambah to-do
        </button>
    </header>

    <section class="daily-todos-card" aria-label="Daftar to-do hari ini">
        @if ($items->isEmpty())
            <div class="daily-todos-empty">
                <span aria-hidden="true"><i class="fa-regular fa-circle-check"></i></span>
                <h2>Belum ada to-do</h2>
                <p>Tambahkan satu hal kecil untuk memulai harimu.</p>
            </div>
        @else
            <ul class="daily-todos-list">
                @foreach ($items as $item)
                    <li @class(['daily-todos-item', 'is-completed' => $item->is_completed])>
                        <form method="post" action="{{ route('daily-todos.toggle', $item->id) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button
                                class="daily-todos-check"
                                type="submit"
                                aria-label="{{ $item->is_completed ? 'Tandai belum selesai' : 'Tandai selesai' }}: {{ $item->title }}"
                            >
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                            </button>
                        </form>
                        <span class="daily-todos-item-title">{{ $item->title }}</span>
                        <form method="post" action="{{ route('daily-todos.destroy', $item->id) }}">
                            @csrf
                            @method('delete')
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button class="daily-todos-delete" type="submit" aria-label="Hapus to-do: {{ $item->title }}">
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <dialog
        class="daily-todos-dialog"
        data-daily-todo-dialog
        aria-labelledby="dailyTodoDialogTitle"
        @if ($errors->any()) data-reopen @endif
    >
        <form method="post" action="{{ route('daily-todos.store') }}" class="daily-todos-dialog-form">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <div class="daily-todos-dialog-heading">
                <div>
                    <p class="daily-todos-eyebrow">SATU LANGKAH KECIL</p>
                    <h2 id="dailyTodoDialogTitle">Tambah to-do</h2>
                </div>
                <button class="daily-todos-dialog-close" type="button" data-close-daily-todo-dialog aria-label="Tutup dialog">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <label for="dailyTodoTitle">Apa yang ingin kamu selesaikan?</label>
            <input
                id="dailyTodoTitle"
                type="text"
                name="title"
                maxlength="255"
                placeholder="Contoh: Membaca 10 halaman"
                value="{{ old('title') }}"
                required
            >
            <div class="daily-todos-dialog-actions">
                <button class="daily-todos-cancel" type="button" data-close-daily-todo-dialog>Batal</button>
                <button class="daily-todos-submit" type="submit">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Tambahkan
                </button>
            </div>
        </form>
    </dialog>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const dialog = document.querySelector('[data-daily-todo-dialog]');
    if (!(dialog instanceof HTMLDialogElement)) return;

    const openDialog = () => {
        if (!dialog.open) dialog.showModal();
        dialog.querySelector('[name="title"]')?.focus();
    };

    document.querySelector('[data-open-daily-todo-dialog]')?.addEventListener('click', openDialog);
    dialog.querySelectorAll('[data-close-daily-todo-dialog]').forEach(button => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });
    if (dialog.hasAttribute('data-reopen')) openDialog();
})();
</script>
@endpush
