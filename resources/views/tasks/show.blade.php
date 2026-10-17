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
                            <i class="fa-{{ $subtask->selesai ? 'solid fa-circle-check' : 'regular fa-circle' }}" aria-hidden="true"></i>
                            <span>{{ $subtask->nama_tugas }}</span>
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
</div>
@endsection
