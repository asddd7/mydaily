@extends('layouts.app')

@section('title', 'Tugas - MyDaily')

@section('content')
<section class="card">
    <h1>Tugas</h1>
    <form method="post" action="{{ route('tasks.store') }}" class="form-grid">
        @csrf
        <label>Nama tugas <input name="nama_tugas" maxlength="100" required></label>
        <label>Tenggat <input type="date" name="deadline" value="{{ today()->toDateString() }}" required></label>
        <label>Kategori <input name="kategori" value="daily" maxlength="100"></label>
        <label>Pengulangan
            <select name="recurring_type">
                <option value="none">Tidak berulang</option>
                <option value="daily">Harian</option>
                <option value="weekly">Mingguan</option>
                <option value="monthly">Bulanan</option>
                <option value="yearly">Tahunan</option>
            </select>
        </label>
        <button type="submit">Tambah tugas</button>
    </form>
</section>

<section class="card">
    <h2>Import dari Excel</h2>
    <p>Kolom A: nama tugas, kolom B: tenggat, kolom C: nama tugas induk (opsional).</p>
    <p><a href="{{ asset('template_import.xlsx') }}">Download template Excel</a></p>
    <form method="post" action="{{ route('tasks.import') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file_excel" accept=".xlsx,.xls" required>
        <button type="submit">Import</button>
    </form>
</section>

@forelse ($tasks as $task)
    <section class="card">
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <form method="post" action="{{ route('tasks.toggle', $task->id) }}">
                @csrf
                <input type="hidden" name="selesai" value="0">
                <input type="checkbox" name="selesai" value="1" @checked($task->selesai) onchange="this.form.submit()">
            </form>
            <strong @if ($task->selesai) style="text-decoration:line-through" @endif>{{ $task->nama_tugas }}</strong>
            <span>{{ $task->deadline }} · {{ $task->kategori ?? 'daily' }}</span>
            <form method="post" action="{{ route('tasks.copy', $task->id) }}">
                @csrf
                <button type="submit">Salin</button>
            </form>
            <form method="post" action="{{ route('tasks.destroy', $task->id) }}" onsubmit="return confirm('Hapus tugas dan seluruh subtugas?')">
                @csrf
                @method('delete')
                <button type="submit">Hapus</button>
            </form>
        </div>

        <details>
            <summary>Edit tugas</summary>
            <form method="post" action="{{ route('tasks.update', $task->id) }}" class="form-grid">
                @csrf
                @method('put')
                <label>Nama <input name="nama_tugas" value="{{ $task->nama_tugas }}" required></label>
                <label>Tenggat <input type="date" name="deadline" value="{{ $task->deadline }}" required></label>
                <label>Kategori <input name="kategori" value="{{ $task->kategori }}"></label>
                <label>Pengulangan
                    <select name="recurring_type">
                        @foreach (['none' => 'Tidak berulang', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $value => $label)
                            <option value="{{ $value }}" @selected($task->recurring_type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit">Simpan</button>
            </form>
        </details>

        <ul>
            @foreach ($subtasks->get($task->id, collect()) as $subtask)
                <li style="margin:.5rem 0">
                    <form method="post" action="{{ route('tasks.subtasks.update', $subtask->id) }}" style="display:inline-flex;gap:.5rem;align-items:center">
                        @csrf
                        @method('put')
                        <input type="hidden" name="nama_tugas" value="{{ $subtask->nama_tugas }}">
                        <input type="hidden" name="selesai" value="0">
                        <input type="checkbox" name="selesai" value="1" @checked($subtask->selesai) onchange="this.form.submit()">
                        <span @if ($subtask->selesai) style="text-decoration:line-through" @endif>{{ $subtask->nama_tugas }}</span>
                    </form>
                    <form method="post" action="{{ route('tasks.subtasks.destroy', $subtask->id) }}" style="display:inline">
                        @csrf
                        @method('delete')
                        <button type="submit" aria-label="Hapus subtugas">Hapus</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <form method="post" action="{{ route('tasks.subtasks.store', $task->id) }}">
            @csrf
            <label>Tambah subtugas <input name="nama_subtask" maxlength="100" required></label>
            <button type="submit">Tambah</button>
        </form>
    </section>
@empty
    <section class="card"><p>Belum ada tugas.</p></section>
@endforelse
@endsection
