@extends('layouts.app')

@section('title', 'Pengaturan Jam - MyDaily')

@push('styles')
    @vite('resources/css/clock.css')
@endpush

@section('content')
<div class="clock-settings-page">
<section class="card clock-preferences-card" aria-labelledby="clockSettingsTitle">
    <div class="clock-settings-heading">
        <span class="clock-settings-heading-icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
        <div>
            <p class="clock-settings-eyebrow">Preferensi waktu</p>
            <h1 id="clockSettingsTitle">Pengaturan Jam</h1>
            <p class="clock-settings-description">Pilih tampilan dan pengatur waktu yang ingin digunakan.</p>
        </div>
    </div>

    @if (session('status'))
        <p class="clock-status-message" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="clock-status-message is-error" role="alert">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('clock.update') }}" class="clock-settings-form">
        @csrf
        @method('put')

        <fieldset class="clock-mode-fieldset">
            <legend>Pilih mode jam</legend>
            <div class="clock-mode-options">
                @php
                    $clockModes = [
                        'clock' => ['Jam digital', 'Lihat waktu saat ini.', 'fa-clock'],
                        'stopwatch' => ['Stopwatch', 'Ukur durasi aktivitas.', 'fa-stopwatch'],
                        'countdown' => ['Countdown', 'Hitung mundur dengan durasi pilihan.', 'fa-hourglass-half'],
                        'pomodoro' => ['Pomodoro', 'Atur sesi fokus dan istirahat.', 'fa-brain'],
                    ];
                @endphp
                @foreach ($clockModes as $value => [$label, $description, $icon])
                    <label class="clock-mode-option">
                        <input type="radio" name="mode" value="{{ $value }}" @checked(old('mode', $mode) === $value) required>
                        <span class="clock-mode-icon" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
                        <span>
                            <strong>{{ $label }}</strong>
                            <small>{{ $description }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="clock-settings-fields">
            <label class="clock-field">
                <span>Durasi countdown</span>
                <span class="clock-input-wrap">
                    <input type="number" name="minutes" min="1" max="10080" value="{{ old('minutes', $countdownMinutes) }}">
                    <small>menit</small>
                </span>
            </label>
            <label class="clock-field">
                <span>Fokus Pomodoro</span>
                <span class="clock-input-wrap">
                    <input type="number" name="work" min="1" max="180" value="{{ old('work', $workMinutes) }}">
                    <small>menit</small>
                </span>
            </label>
            <label class="clock-field">
                <span>Istirahat Pomodoro</span>
                <span class="clock-input-wrap">
                    <input type="number" name="break" min="1" max="180" value="{{ old('break', $breakMinutes) }}">
                    <small>menit</small>
                </span>
            </label>
        </div>

        <button class="clock-save-button" type="submit">
            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
            Simpan pengaturan
        </button>
    </form>
</section>
<section class="card clock-test-card" aria-labelledby="clockTestTitle">
    <div class="clock-test-heading">
        <span class="clock-test-icon" aria-hidden="true"><i class="fa-solid fa-bell"></i></span>
        <div>
            <h2 id="clockTestTitle">Uji notifikasi dengan stopwatch</h2>
            <p>Mulai stopwatch dan notifikasi tes akan dikirim setelah durasi yang dipilih.</p>
        </div>
    </div>
    <output id="pushTestStopwatch" class="clock-test-time" aria-live="off">00:00</output>
    <div class="clock-test-controls">
        <label for="pushTestDuration">Kirim setelah</label>
        <select id="pushTestDuration">
            <option value="5">5 detik (tes cepat)</option>
            <option value="10">10 detik</option>
            <option value="30">30 detik</option>
            <option value="60">1 menit</option>
            <option value="300">5 menit</option>
        </select>
        <button type="button" id="startPushTest">Mulai tes</button>
        <button type="button" id="cancelPushTest" class="clock-test-cancel" hidden>Batalkan</button>
    </div>
    <p id="pushTestStatus" class="clock-test-status" role="status" aria-live="polite">
        Aktifkan notifikasi perangkat melalui ikon lonceng sebelum memulai tes.
    </p>
</section>

@push('scripts')
<script>
(() => {
    const startButton = document.getElementById('startPushTest');
    const cancelButton = document.getElementById('cancelPushTest');
    const durationSelect = document.getElementById('pushTestDuration');
    const display = document.getElementById('pushTestStopwatch');
    const status = document.getElementById('pushTestStatus');
    if (!startButton || !cancelButton || !durationSelect || !display || !status) return;

    const endpoint = @json(route('notifications.push-test', absolute: false));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let intervalId = null;
    let startAt = 0;
    let targetSeconds = 0;
    const format = seconds => `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    const resetControls = () => {
        startButton.disabled = false;
        durationSelect.disabled = false;
        cancelButton.hidden = true;
    };
    const sendTest = async () => {
        window.clearInterval(intervalId);
        intervalId = null;
        status.textContent = 'Stopwatch selesai. Mengirim notifikasi tes...';
        try {
            const token = localStorage.getItem('mydaily-push-token');
            if (!token) {
                throw new Error('Aktifkan notifikasi pada perangkat ini melalui ikon lonceng terlebih dahulu.');
            }
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ token }),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.success) {
                throw new Error(result.message || `Pengiriman gagal (${response.status}).`);
            }
            status.textContent = 'Firebase menerima permintaan tes. Status ini belum memastikan browser menampilkan notifikasi.';
        } catch (error) {
            status.textContent = error.message;
        } finally {
            resetControls();
        }
    };

    startButton.addEventListener('click', () => {
        targetSeconds = Number(durationSelect.value);
        startAt = Date.now();
        startButton.disabled = true;
        durationSelect.disabled = true;
        cancelButton.hidden = false;
        status.textContent = `Notifikasi akan dikirim setelah ${format(targetSeconds)}.`;
        intervalId = window.setInterval(() => {
            const elapsed = Math.floor((Date.now() - startAt) / 1000);
            display.textContent = format(elapsed);
            if (elapsed >= targetSeconds) sendTest();
        }, 200);
    });
    cancelButton.addEventListener('click', () => {
        window.clearInterval(intervalId);
        intervalId = null;
        display.textContent = '00:00';
        resetControls();
        status.textContent = 'Tes stopwatch dibatalkan.';
    });
})();
</script>
@endpush
</div>
@endsection
