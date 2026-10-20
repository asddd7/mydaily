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
            status.textContent = 'Notifikasi tes berhasil dikirim ke perangkat ini.';
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
@endsection
