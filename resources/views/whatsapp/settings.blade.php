@extends('layouts.app')

@section('title', 'WhatsApp Bot - MyDaily')

@push('styles')
    @vite('resources/css/whatsapp.css')
@endpush

@section('content')
<section class="whatsapp-settings-page">
    <header class="whatsapp-heading">
        <span class="whatsapp-heading-icon" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span>
        <div>
            <p class="whatsapp-eyebrow">Integrasi pesan</p>
            <h1>WhatsApp Bot</h1>
            <p>Tautkan nomor WhatsApp untuk mengelola task dan to-do melalui chat pribadi.</p>
        </div>
    </header>

    @unless ($botConfigured)
        <div class="whatsapp-setup-notice" role="status">
            Integrasi belum dikonfigurasi. Administrator perlu mengisi pengaturan WhatsApp di environment server
            dan mendaftarkan webhook sebelum akun dapat ditautkan.
        </div>
    @endunless

    <article class="whatsapp-card">
        @if ($account?->phone_number)
            <div class="whatsapp-connection-status">
                <span class="whatsapp-status-dot" aria-hidden="true"></span>
                <div>
                    <strong>Nomor terhubung</strong>
                    <p>+{{ $account->phone_number }}</p>
                </div>
            </div>
            <form method="post" action="{{ route('whatsapp.unlink') }}"
                onsubmit="return confirm('Putuskan nomor WhatsApp dari akun ini?')">
                @csrf
                @method('delete')
                <button class="whatsapp-button whatsapp-button-danger" type="submit">Putuskan tautan</button>
            </form>
        @else
            <h2>Hubungkan akun</h2>
            <p>Buat kode sekali pakai, lalu kirim ke nomor resmi bot dengan format <code>TAUTKAN 12345678</code>.
                Kode berlaku selama 10 menit.</p>

            @if (session('link_code'))
                <div class="whatsapp-link-code" role="status">
                    <span>Kode tautan Anda</span>
                    <strong>{{ session('link_code') }}</strong>
                    <small>Jangan bagikan kode ini kepada orang lain.</small>
                </div>
            @endif

            @if ($botConfigured)
                <form method="post" action="{{ route('whatsapp.link-code') }}">
                    @csrf
                    <button class="whatsapp-button" type="submit">Buat kode tautan</button>
                </form>
            @endif
        @endif
    </article>

    <article class="whatsapp-card whatsapp-commands">
        <h2>Perintah yang tersedia</h2>
        <ul>
            <li><code>DAFTAR</code> — lihat to-do hari ini dan task aktif.</li>
            <li><code>TODO TAMBAH &lt;judul&gt;</code>, <code>TODO UBAH &lt;id&gt; &lt;judul&gt;</code>, <code>TODO SELESAI &lt;id&gt;</code>.</li>
            <li><code>TODO HAPUS &lt;id&gt; KONFIRMASI</code>.</li>
            <li><code>TASK TAMBAH &lt;judul&gt;</code> — tenggat otomatis hari ini.</li>
            <li><code>TASK UBAH &lt;id&gt; &lt;judul&gt;</code>.</li>
            <li><code>TASK SELESAI &lt;id&gt;</code>.</li>
            <li><code>TASK HAPUS &lt;id&gt; KONFIRMASI</code> — juga menghapus subtugas.</li>
            <li><code>BANTUAN</code> — tampilkan bantuan bot.</li>
        </ul>
        <p>Penghapusan memerlukan kata <code>KONFIRMASI</code>. Bot hanya menerima pesan teks masuk.</p>
    </article>
</section>
@endsection
