@extends('layouts.app')

@section('title', 'Dashboard - MyDaily')

@section('content')
    <div class="dashboard-page">
        <header class="dashboard-hero">
            <div>
                <p class="dashboard-eyebrow">Ruang kerja harian</p>
                <h1 class="dashboard-title">Dashboard</h1>
                <p class="dashboard-subtitle">Ringkasan aktivitas dan rencana harianmu.</p>
            </div>
            <div class="dashboard-date-badge">
                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                <time datetime="{{ \Illuminate\Support\Carbon::parse($today)->toDateString() }}">
                    {{ \Illuminate\Support\Carbon::parse($today)->translatedFormat('l, d F Y') }}
                </time>
            </div>
        </header>

        <section class="card dashboard-card dashboard-overview" aria-labelledby="dashboardOverviewTitle">
            <div class="dashboard-section-heading">
                <div>
                    <p class="dashboard-card-eyebrow">Hari ini</p>
                    <h2 id="dashboardOverviewTitle">Ringkasan aktivitas</h2>
                </div>
                <span class="dashboard-overview-icon" aria-hidden="true"><i class="fa-solid fa-chart-simple"></i></span>
            </div>
            <div class="dashboard-summary-grid">
                <article class="dashboard-stat dashboard-stat-expense">
                    <span class="dashboard-stat-icon" aria-hidden="true"><i class="fa-solid fa-arrow-trend-down"></i></span>
                    <div>
                        <p>Pengeluaran hari ini</p>
                        <strong>Rp {{ number_format((float) $expenseToday, 0, ',', '.') }}</strong>
                    </div>
                </article>
                <article class="dashboard-stat dashboard-stat-note">
                    <span class="dashboard-stat-icon" aria-hidden="true"><i class="fa-regular fa-note-sticky"></i></span>
                    <div>
                        <p>Catatan terbaru</p>
                        @if ($latestNote)
                            <strong title="{{ $latestNote->title }}">{{ $latestNote->title }}</strong>
                        @else
                            <strong class="dashboard-stat-empty">Belum ada catatan</strong>
                        @endif
                    </div>
                </article>
            </div>
        </section>

        <section class="card dashboard-card dashboard-push-card" aria-labelledby="dashboardPushTitle">
            <div class="dashboard-push-icon" aria-hidden="true"><i class="fa-solid fa-bell"></i></div>
            <div class="dashboard-push-copy">
                <p class="dashboard-card-eyebrow">Tetap terhubung</p>
                <h2 id="dashboardPushTitle">Notifikasi MyDaily</h2>
                <p>Aktifkan notifikasi dari ikon lonceng di bagian atas. Anda bisa menguji pengirimannya lewat stopwatch.</p>
            </div>
            <a class="dashboard-push-link" href="{{ route('clock.edit') }}">
                <i class="fa-solid fa-stopwatch" aria-hidden="true"></i> Tes notifikasi
            </a>
        </section>
        @include('calendar.widget')
    </div>
@endsection
