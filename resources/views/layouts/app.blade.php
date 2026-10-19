<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="fcm-device-url" content="{{ route('fcm.devices.store') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MyDaily')</title>
    @vite(['resources/css/style.css', 'resources/css/app.css', 'resources/js/page-loader.js', 'resources/js/push-notifications.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @stack('styles')
</head>
<body>
@include('layouts.partials.page-loader')
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
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    const notificationSummary = document.getElementById('notificationSummary');
    const updateNotifications = () => fetch(@json(route('notifications.count')), { cache: 'no-store' })
        .then(response => {
            if (!response.ok) throw new Error(`Notification request failed: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (!Number.isFinite(Number(data.count)) || !Array.isArray(data.notifications)) {
                throw new Error('Notification response has an invalid format.');
            }
            const count = Number(data.count) || 0;
            badge.textContent = count ? String(count > 99 ? '99+' : count) : '';
            notificationToggle.setAttribute('aria-label', count ? `Tugas belum selesai: ${count}` : 'Tidak ada tugas belum selesai');
            notificationSummary.textContent = count ? `${count} tugas` : 'Semua tugas selesai';
            notificationList.replaceChildren();

            if (!data.notifications.length) {
                const emptyMessage = document.createElement('p');
                emptyMessage.className = 'app-notification-empty';
                emptyMessage.textContent = 'Tidak ada tugas yang belum selesai.';
                notificationList.append(emptyMessage);
                return;
            }

            data.notifications.forEach(task => {
                const link = document.createElement('a');
                link.className = 'app-notification-item';
                link.href = `${@json(route('tasks.index'))}#task-${encodeURIComponent(task.id)}`;

                const name = document.createElement('span');
                name.className = 'app-notification-item-name';
                name.textContent = task.nama_tugas;

                const deadline = document.createElement('time');
                deadline.className = 'app-notification-item-deadline';
                deadline.dateTime = task.deadline;
                deadline.textContent = task.deadline;

                link.append(name, deadline);
                notificationList.append(link);
            });

            if (data.count > data.notifications.length) {
                const more = document.createElement('p');
                more.className = 'app-notification-more';
                more.textContent = `Dan ${data.count - data.notifications.length} tugas lainnya`;
                notificationList.append(more);
            }
        })
        .catch(error => console.error(error));
    updateNotifications();
    window.setInterval(updateNotifications, 60000);
    window.addEventListener('focus', updateNotifications);

    notificationToggle.addEventListener('click', () => {
        const isExpanded = notificationToggle.getAttribute('aria-expanded') === 'true';
        notificationToggle.setAttribute('aria-expanded', String(!isExpanded));
        notificationDropdown.hidden = isExpanded;
        if (!isExpanded) updateNotifications();
    });
    document.addEventListener('click', event => {
        if (
            !notificationDropdown.hidden
            && event.target instanceof Element
            && !event.target.closest('.app-notification')
        ) {
            notificationDropdown.hidden = true;
            notificationToggle.setAttribute('aria-expanded', 'false');
        }
    });
    window.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !notificationDropdown.hidden) {
            notificationDropdown.hidden = true;
            notificationToggle.setAttribute('aria-expanded', 'false');
            notificationToggle.focus();
        }
    });

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
<script
    type="module"
    src="{{ asset('js/firebase-messaging.js') }}?v=4"
></script>
</body>
</html>
