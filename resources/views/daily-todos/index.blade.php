@extends('layouts.app')

@section('title', 'Daily To-do - MyDaily')

@push('styles')
    @vite('resources/css/daily-todos.css')
@endpush

@section('content')
<div class="daily-todos-page">
    <header class="daily-todos-header">
        <div>
            <p class="daily-todos-eyebrow">Rencana sederhana setiap hari</p>
            <h1>Daily To-do</h1>
            <p>Catat hal-hal yang ingin kamu selesaikan hari ini.</p>
        </div>
        <div class="daily-todos-progress" aria-label="{{ $completedCount }} dari {{ $items->count() }} to-do selesai">
            <strong>{{ $completedCount }}<span>/{{ $items->count() }}</span></strong>
            <span>selesai</span>
        </div>
    </header>

    <section class="daily-todos-card" aria-labelledby="dailyTodoDateTitle">
        <div class="daily-todos-date-heading">
            <div>
                <p class="daily-todos-eyebrow">DAFTAR HARIAN</p>
                <h2 id="dailyTodoDateTitle">{{ $selectedDate->translatedFormat('l, d F Y') }}</h2>
            </div>
            @if ($date !== now()->toDateString())
                <a class="daily-todos-today-link" href="{{ route('daily-todos.index') }}">Kembali ke hari ini</a>
            @endif
        </div>

        <nav class="daily-todos-date-navigation" aria-label="Pilih tanggal">
            <a href="{{ route('daily-todos.index', ['date' => $selectedDate->copy()->subDay()->toDateString()]) }}" aria-label="Hari sebelumnya">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </a>
            <form method="get" action="{{ route('daily-todos.index') }}">
                <label for="dailyTodoDate">Lihat tanggal</label>
                <input id="dailyTodoDate" type="date" name="date" value="{{ $date }}">
                <button type="submit">Buka</button>
            </form>
            <a href="{{ route('daily-todos.index', ['date' => $selectedDate->copy()->addDay()->toDateString()]) }}" aria-label="Hari berikutnya">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
        </nav>

        <form class="daily-todos-add-form" method="post" action="{{ route('daily-todos.store') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <label class="sr-only" for="dailyTodoTitle">To-do baru</label>
            <input
                id="dailyTodoTitle"
                type="text"
                name="title"
                maxlength="255"
                placeholder="Apa yang ingin kamu selesaikan?"
                value="{{ old('title') }}"
                required
            >
            <button type="submit">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah
            </button>
        </form>

        @if ($items->isEmpty())
            <div class="daily-todos-empty">
                <span aria-hidden="true"><i class="fa-regular fa-circle-check"></i></span>
                <h3>Belum ada to-do untuk tanggal ini</h3>
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
</div>
@endsection
