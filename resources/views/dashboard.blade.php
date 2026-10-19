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

        @include('calendar.widget')
    </div>
@endsection
