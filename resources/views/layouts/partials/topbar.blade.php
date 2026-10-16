<div class="app-topbar">
    <button type="button" id="toggleSidebar" class="toggle-btn" aria-label="Buka atau tutup menu">☰</button>
    <a href="{{ route('dashboard') }}" class="app-topbar-logo">MYDAILY</a>
    <span class="app-topbar-welcome">Welcome, <strong>{{ request()->attributes->get('currentUser')->username }}</strong></span>
    <a href="{{ route('clock.edit') }}" id="floatingClockLink" class="floating-clock" title="Pengaturan jam">
        <span id="floatingClock" aria-live="off"></span>
    </a>
    <div class="app-notification">
        <button type="button" id="notificationToggle" class="app-topbar-notifications" aria-label="Buka notifikasi tugas" aria-expanded="false" aria-controls="notificationDropdown">
            <i class="fa-solid fa-bell" aria-hidden="true"></i>
            <span id="notificationCount" aria-live="polite"></span>
        </button>
        <section id="notificationDropdown" class="app-notification-dropdown" aria-labelledby="notificationDropdownTitle" hidden>
            <div class="app-notification-heading">
                <h2 id="notificationDropdownTitle">Tugas belum selesai</h2>
                <span id="notificationSummary" aria-live="polite"></span>
            </div>
            <div id="notificationList" class="app-notification-list" aria-live="polite">
                <p class="app-notification-empty">Memuat notifikasi...</p>
            </div>
            <a class="app-notification-all" href="{{ route('tasks.index') }}">Lihat semua tugas</a>
        </section>
    </div>
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout">Logout</button>
    </form>
</div>
