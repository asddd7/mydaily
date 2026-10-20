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
            <div class="app-push-settings">
                <p id="pushNotificationStatus" role="status">Notifikasi ke HP belum disiapkan.</p>
                <div class="app-push-actions">
                    <button type="button" id="enablePushNotifications">Aktifkan notifikasi HP</button>
                    <button type="button" id="disablePushNotifications" hidden>Nonaktifkan di perangkat ini</button>
                </div>
                <script type="application/json" id="firebaseWebConfig">{!! json_encode(config('firebase.web'), JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                <script type="application/json" id="pushNotificationConfig">{!! json_encode([
                    'vapidKey' => config('firebase.vapid_key'),
                    'serviceWorkerUrl' => route('firebase.messaging-sw', absolute: false),
                    'subscribeUrl' => route('notifications.push-subscriptions.store', absolute: false),
                    'unsubscribeUrl' => route('notifications.push-subscriptions.destroy', absolute: false),
                    'csrfToken' => csrf_token(),
                ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
            </div>
            <a class="app-notification-all" href="{{ route('tasks.index') }}">Lihat semua tugas</a>
        </section>
    </div>
    <button type="button" id="installAppButton" class="app-install-btn" aria-label="Install MyDaily">
        <i class="fa-solid fa-download" aria-hidden="true"></i>
        <span class="app-install-text">Install app</span>
    </button>
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout">Logout</button>
    </form>
</div>
