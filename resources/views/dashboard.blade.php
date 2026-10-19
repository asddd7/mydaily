@extends('layouts.app')

@section('title', 'Dashboard - MyDaily')

@section('content')
@auth
    <section class="card dashboard-card">
        <h2>Notifikasi MyDaily</h2>

        <p>
            Aktifkan notifikasi untuk menerima pembaruan
            dari aplikasi.
        </p>

        <button type="button" id="enable-push">
            Aktifkan Notifikasi
        </button>

        <p id="push-status" role="status" aria-live="polite">
            Notifikasi belum diaktifkan.
        </p>
    </section>
@endauth
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

        @include('calendar.widget')
    </div>
@endsection
