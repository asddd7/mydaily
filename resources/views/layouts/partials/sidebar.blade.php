<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <span class="logo-icon"><i class="fa-solid fa-calendar-check"></i></span>
        <span class="logo-full">MYDAILY</span>
    </div>
    <div class="sidebar-section">MENU UTAMA</div>
    <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>
        <i class="fa-solid fa-house"></i><span>Dashboard</span>
    </a>
    <a href="{{ route('tasks.index') }}" @class(['active' => request()->routeIs('tasks.*')])>
        <i class="fa-solid fa-list-check"></i><span>Tugas</span>
    </a>
    <a href="{{ route('money.index') }}" @class(['active' => request()->routeIs('money.*')])>
        <i class="fa-solid fa-wallet"></i><span>Pengatur Keuangan</span>
    </a>
    <a href="{{ route('notes.index') }}" @class(['active' => request()->routeIs('notes.*')])>
        <i class="fa-solid fa-note-sticky"></i><span>Catatan</span>
    </a>
    <a href="{{ route('profile.show') }}" @class(['active' => request()->routeIs('profile.*')])>
        <i class="fa-solid fa-user"></i><span>Profil</span>
    </a>
    <a href="{{ route('files.index') }}" @class(['active' => request()->routeIs('files.*')])>
        <i class="fa-solid fa-folder"></i><span>File Manager</span>
    </a>
    <a href="{{ route('calendar.index') }}" @class(['active' => request()->routeIs('calendar.*')])>
        <i class="fa-solid fa-calendar-days"></i><span>Kalender</span>
    </a>
    <div class="sidebar-section">PENGATURAN</div>
    <a href="{{ route('clock.edit') }}" @class(['active' => request()->routeIs('clock.*')])>
        <i class="fa-solid fa-clock"></i><span>Pengaturan Jam</span>
    </a>
    @if (request()->attributes->get('currentUser')->role === 'admin')
        <a href="{{ route('admin.database-structure') }}" @class(['active' => request()->routeIs('admin.*')])>
            <i class="fa-solid fa-table"></i><span>Struktur DB</span>
        </a>
    @endif
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
