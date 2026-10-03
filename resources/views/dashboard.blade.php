@extends('layouts.app')

@section('title', 'Dashboard - MyDaily')

@section('content')
        <h1>Dashboard</h1>
        <section class="card">
            <h2>Ringkasan Harian - {{ \Illuminate\Support\Carbon::parse($today)->translatedFormat('d F Y') }}</h2>
            <p>Total pengeluaran hari ini: <strong>Rp {{ number_format((float) $expenseToday, 0, ',', '.') }}</strong></p>
            @if ($latestNote)
                <p>Catatan terbaru: <strong>{{ $latestNote->title }}</strong></p>
            @else
                <p>Belum ada catatan.</p>
            @endif
        </section>

        <section class="card">
            <h2>Absen Hari Ini</h2>
            @if (!$attendanceToday)
                <form method="post" action="{{ route('attendance.check-in') }}">
                    @csrf
                    <button type="submit">Mulai</button>
                </form>
            @elseif (!$attendanceToday->jam_pulang)
                <p>Mulai: {{ $attendanceToday->jam_masuk }}</p>
                <form method="post" action="{{ route('attendance.check-out') }}">
                    @csrf
                    <button type="submit">Selesai</button>
                </form>
            @else
                <p>Mulai: {{ $attendanceToday->jam_masuk }} | Selesai: {{ $attendanceToday->jam_pulang }}</p>
            @endif
        </section>

        @include('calendar.widget')

        <section class="card">
            <h2>Riwayat Absen</h2>
            @forelse ($attendanceHistory as $attendance)
                <p>{{ $attendance->tanggal }} — {{ $attendance->jam_masuk ?? '-' }} sampai {{ $attendance->jam_pulang ?? '-' }}</p>
            @empty
                <p>Belum ada riwayat absen.</p>
            @endforelse
        </section>
@endsection
