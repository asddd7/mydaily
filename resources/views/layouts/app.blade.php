<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MyDaily')</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link rel="stylesheet" href="{{ asset('app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">
    @include('layouts.partials.sidebar')
    <main class="content">
        @include('layouts.partials.topbar')

        @if (session('status'))
            <div class="success-msg" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="error-msg" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="error-msg" role="alert">{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </main>
</div>
@include('layouts.partials.floating-actions')
@stack('scripts')
<script>
(() => {
    const badge = document.getElementById('notificationCount');
    const updateNotifications = () => fetch(@json(route('notifications.count')), { cache: 'no-store' })
        .then(response => {
            if (!response.ok) throw new Error(`Notification request failed: ${response.status}`);
            return response.json();
        })
        .then(data => { badge.textContent = data.count ? ` ${data.count}` : ''; })
        .catch(error => console.error(error));
    updateNotifications();
    window.setInterval(updateNotifications, 60000);

    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    document.getElementById('toggleSidebar').addEventListener('click', () => {
        if (window.matchMedia('(max-width: 768px)').matches) {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('active');
            return;
        }
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar', sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
    });
    backdrop.addEventListener('click', () => {
        sidebar.classList.remove('show');
        backdrop.classList.remove('active');
    });
    document.querySelectorAll('.sidebar a').forEach(link => link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 768px)').matches) {
            sidebar.classList.remove('show');
            backdrop.classList.remove('active');
        }
    }));
    const setSidebarState = () => {
        if (window.matchMedia('(max-width: 768px)').matches) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('show');
            backdrop.classList.remove('active');
            return;
        }
        sidebar.classList.toggle('collapsed', localStorage.getItem('sidebar') === 'collapsed');
        sidebar.classList.remove('show');
        backdrop.classList.remove('active');
    };
    setSidebarState();
    window.addEventListener('resize', setSidebarState);
    window.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            sidebar.classList.remove('show');
            backdrop.classList.remove('active');
        }
    });
})();
</script>
</body>
</html>
