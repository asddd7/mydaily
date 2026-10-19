@extends('layouts.app')

@section('title', 'Dashboard - MyDaily')

@section('content')
    <div class="dashboard-page">
        <h1 class="dashboard-title">Dashboard</h1>
        <section class="card dashboard-card dashboard-summary">
            <h2>Ringkasan Harian - {{ \Illuminate\Support\Carbon::parse($today)->translatedFormat('d F Y') }}</h2>
            <p>Total pengeluaran hari ini: <strong>Rp {{ number_format((float) $expenseToday, 0, ',', '.') }}</strong></p>
            @if ($latestNote)
                <p>Catatan terbaru: <strong>{{ $latestNote->title }}</strong></p>
            @else
                <p>Belum ada catatan.</p>
            @endif
        </section>
        <section class="card dashboard-card dashboard-push-card">
            <div class="dashboard-push-icon" aria-hidden="true"><i class="fa-solid fa-bell"></i></div>
            <div class="dashboard-push-copy">
                <h2>Notifikasi MyDaily</h2>
                <p>Aktifkan notifikasi dari ikon lonceng di bagian atas. Anda bisa menguji pengirimannya lewat stopwatch.</p>
            </div>
            <a class="dashboard-push-link" href="{{ route('clock.edit') }}">
                <i class="fa-solid fa-stopwatch" aria-hidden="true"></i> Buka stopwatch
            </a>
        </section>
        @include('calendar.widget')
    </div>
@endsection
