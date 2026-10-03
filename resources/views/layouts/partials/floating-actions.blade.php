<div class="fab-container">
    <button class="fab-main" id="fabToggle" type="button" aria-label="Buka akses cepat">
        <i class="fa-solid fa-plus"></i>
    </button>
    <nav class="fab-menu" id="fabMenu" aria-label="Akses cepat">
        <a href="{{ route('tasks.index') }}" class="fab-item"><i class="fa-solid fa-list-check"></i><span>Tugas</span></a>
        <a href="{{ route('money.index') }}" class="fab-item"><i class="fa-solid fa-wallet"></i><span>Keuangan</span></a>
        <a href="{{ route('notes.index') }}" class="fab-item"><i class="fa-solid fa-note-sticky"></i><span>Catatan</span></a>
        <a href="{{ route('files.index') }}" class="fab-item"><i class="fa-solid fa-folder"></i><span>File</span></a>
    </nav>
</div>

<a href="{{ route('clock.edit') }}" id="floatingClockLink" class="floating-clock" title="Pengaturan jam">
    <span id="floatingClock" aria-live="off"></span>
</a>

<script>
(() => {
    const toggle = document.getElementById('fabToggle');
    const menu = document.getElementById('fabMenu');
    toggle.addEventListener('click', event => {
        event.stopPropagation();
        menu.classList.toggle('show');
    });
    document.addEventListener('click', () => menu.classList.remove('show'));

    const clock = document.getElementById('floatingClock');
    const mode = @json(request()->attributes->get('currentUser')->clock_mode ?: 'clock');
    const countdownSeconds = Number(@json((int) request()->attributes->get('currentUser')->countdown_seconds));
    const workSeconds = Number(@json((int) (request()->attributes->get('currentUser')->pomodoro_work ?: 25) * 60));
    const breakSeconds = Number(@json((int) (request()->attributes->get('currentUser')->pomodoro_break ?: 5) * 60));
    const pad = value => String(value).padStart(2, '0');
    let startTime = Number(localStorage.getItem('mydaily.stopwatchStart')) || Date.now();
    let countdownEnd = Number(localStorage.getItem('mydaily.countdownEnd'));
    let pomodoroType = localStorage.getItem('mydaily.pomodoroType') || 'work';
    let pomodoroEnd = Number(localStorage.getItem('mydaily.pomodoroEnd'));
    if (mode === 'countdown' && !countdownEnd) {
        countdownEnd = Date.now() + countdownSeconds * 1000;
        localStorage.setItem('mydaily.countdownEnd', String(countdownEnd));
    }
    if (mode === 'pomodoro' && !pomodoroEnd) {
        pomodoroEnd = Date.now() + workSeconds * 1000;
        localStorage.setItem('mydaily.pomodoroEnd', String(pomodoroEnd));
    }
    const format = seconds => {
        seconds = Math.max(0, seconds);
        return `${pad(Math.floor(seconds / 3600))}:${pad(Math.floor((seconds % 3600) / 60))}:${pad(seconds % 60)}`;
    };
    const updateClock = () => {
        const now = Date.now();
        if (mode === 'clock') {
            clock.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
        } else if (mode === 'stopwatch') {
            clock.textContent = format(Math.floor((now - startTime) / 1000));
            localStorage.setItem('mydaily.stopwatchStart', String(startTime));
        } else if (mode === 'countdown') {
            const remaining = Math.ceil((countdownEnd - now) / 1000);
            clock.textContent = format(remaining);
            if (remaining <= 0) {
                localStorage.removeItem('mydaily.countdownEnd');
                countdownEnd = 0;
            }
        } else if (mode === 'pomodoro') {
            let remaining = Math.ceil((pomodoroEnd - now) / 1000);
            if (remaining <= 0) {
                pomodoroType = pomodoroType === 'work' ? 'break' : 'work';
                pomodoroEnd = now + (pomodoroType === 'work' ? workSeconds : breakSeconds) * 1000;
                localStorage.setItem('mydaily.pomodoroType', pomodoroType);
                localStorage.setItem('mydaily.pomodoroEnd', String(pomodoroEnd));
                remaining = Math.ceil((pomodoroEnd - now) / 1000);
            }
            clock.textContent = `${pomodoroType === 'work' ? '🍅' : '☕'} ${format(remaining)}`;
        }
    };
    updateClock();
    window.setInterval(updateClock, 1000);
})();
</script>
