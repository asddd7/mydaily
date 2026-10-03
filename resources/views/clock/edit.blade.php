@extends('layouts.app')

@section('title', 'Pengaturan Jam - MyDaily')

@section('content')
<section class="card">
    <h1>Pengaturan Jam</h1>
    <form method="post" action="{{ route('clock.update') }}">
        @csrf
        @method('put')
        @foreach (['clock' => 'Jam digital', 'stopwatch' => 'Stopwatch', 'countdown' => 'Countdown', 'pomodoro' => 'Pomodoro'] as $value => $label)
            <label style="display:block;margin:.75rem 0">
                <input type="radio" name="mode" value="{{ $value }}" @checked($mode === $value) required>
                {{ $label }}
            </label>
        @endforeach
        <label>Durasi countdown (menit)
            <input type="number" name="minutes" min="1" max="10080" value="{{ $countdownMinutes }}">
        </label>
        <label>Durasi fokus pomodoro (menit)
            <input type="number" name="work" min="1" max="180" value="{{ $workMinutes }}">
        </label>
        <label>Durasi istirahat pomodoro (menit)
            <input type="number" name="break" min="1" max="180" value="{{ $breakMinutes }}">
        </label>
        <button type="submit">Simpan</button>
    </form>
</section>
@endsection
