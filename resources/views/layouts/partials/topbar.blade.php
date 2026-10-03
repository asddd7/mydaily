<div class="app-topbar">
    <button type="button" id="toggleSidebar" class="toggle-btn" aria-label="Buka atau tutup menu">☰</button>
    <a href="{{ route('dashboard') }}" class="app-topbar-logo">MYDAILY</a>
    <span class="app-topbar-welcome">Welcome, <strong>{{ request()->attributes->get('currentUser')->username }}</strong></span>
    <a href="{{ route('clock.edit') }}" id="floatingClockLink" class="floating-clock" title="Pengaturan jam">
        <span id="floatingClock" aria-live="off"></span>
    </a>
    <a href="{{ route('tasks.index') }}" class="app-topbar-notifications" aria-label="Tugas yang belum selesai">
        <i class="fa-solid fa-bell"></i>
        <span id="notificationCount" aria-live="polite"></span>
    </a>
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout">Logout</button>
    </form>
</div>
