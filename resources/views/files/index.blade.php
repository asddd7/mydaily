@extends('layouts.app')

@section('title', 'Berkas - MyDaily')

@push('styles')
    @vite('resources/css/files.css')
@endpush

@section('content')
@php
    $fileCollection = collect($files ?? []);

    $formatFileSize = static function ($bytes): string {
        $bytes = max(0, (float) $bytes);

        if ($bytes < 1024) {
            return number_format($bytes, 0, ',', '.') . ' B';
        }

        if ($bytes < 1024 ** 2) {
            return number_format($bytes / 1024, 2, ',', '.') . ' KB';
        }

        if ($bytes < 1024 ** 3) {
            return number_format($bytes / (1024 ** 2), 2, ',', '.') . ' MB';
        }

        return number_format($bytes / (1024 ** 3), 2, ',', '.') . ' GB';
    };
@endphp

<div class="fm-page" data-file-manager>
    {{-- Header --}}
    <header class="fm-header">
        <div class="fm-header-copy">
            <p class="fm-eyebrow">
                <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                Ruang penyimpanan
            </p>

            <h1>Berkas Saya</h1>

            <p class="fm-subtitle">
                Simpan, cari, dan kelola semua file kamu di satu tempat.
            </p>
        </div>
        <button class="fm-header-button" type="button" data-file-upload-open>
            <i class="fa-solid fa-upload" aria-hidden="true"></i>
            Upload file
        </button>
    </header>

    {{-- Flash messages --}}
    @if (session('status'))
        <div class="fm-alert fm-alert-success" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="fm-alert fm-alert-error" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>

            <div>
                <strong>Periksa kembali data kamu.</strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Summary --}}
    <section class="fm-stats" aria-label="Ringkasan penyimpanan">
        <article class="fm-stat-card">
            <span class="fm-stat-icon fm-stat-icon-blue">
                <i class="fa-solid fa-folder" aria-hidden="true"></i>
            </span>

            <div class="fm-stat-content">
                <span>Total file</span>
                <strong>{{ $totalFiles }}</strong>
                <small>File tersimpan</small>
            </div>
        </article>

        <article class="fm-stat-card">
            <span class="fm-stat-icon fm-stat-icon-purple">
                <i class="fa-solid fa-hard-drive" aria-hidden="true"></i>
            </span>

            <div class="fm-stat-content">
                <span>Total ukuran</span>
                <strong>{{ $formatFileSize($totalSize) }}</strong>
                <small>Akumulasi ukuran file</small>
            </div>
        </article>

        <article class="fm-stat-card">
            <span class="fm-stat-icon fm-stat-icon-green">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            </span>

            <div class="fm-stat-content">
                <span>Batas upload</span>
                <strong>50 MB</strong>
                <small>Maksimum per file</small>
            </div>
        </article>
    </section>

    @include('files.partials.folder-navigation', [
        'activeFolder' => $activeFolder,
        'folders' => $folders,
        'totalFiles' => $totalFiles,
    ])

    @include('files.partials.upload-dialog', [
        'activeFolder' => $activeFolder,
        'folders' => $folders,
    ])

    {{-- Files library --}}
    <section class="fm-panel fm-library-panel">
        <div class="fm-library-heading">
            <div class="fm-section-heading fm-section-heading-compact">
                <div>
                    <p class="fm-section-kicker">Pustaka file</p>
                    <h2>{{ $activeFolder ? $activeFolder->name : 'File tersimpan' }}</h2>
                    <p>{{ $activeFolder ? 'File yang tersimpan di grup ini.' : 'Kelola nama, grup, dan unduh file yang kamu butuhkan.' }}</p>
                </div>
            </div>

            <span class="fm-total-badge">
                {{ $fileCollection->count() }} file
            </span>
        </div>

        @if ($fileCollection->isNotEmpty())
            <div class="fm-toolbar">
                <label class="fm-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>

                    <input
                        type="search"
                        placeholder="Cari nama file..."
                        aria-label="Cari nama file"
                        data-file-search
                    >

                    <kbd>⌕</kbd>
                </label>

                <div class="fm-toolbar-filters">
                    <label class="fm-filter-control">
                        <span>Jenis</span>

                        <select data-file-filter aria-label="Filter jenis file">
                            <option value="all">Semua jenis</option>
                            <option value="image">Gambar</option>
                            <option value="video">Video</option>
                            <option value="audio">Audio</option>
                            <option value="pdf">PDF</option>
                            <option value="document">Dokumen</option>
                            <option value="spreadsheet">Spreadsheet</option>
                            <option value="archive">Arsip</option>
                            <option value="text">Teks / kode</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </label>

                    <label class="fm-filter-control">
                        <span>Urutkan</span>

                        <select data-file-sort aria-label="Urutkan file">
                            <option value="newest">Terbaru</option>
                            <option value="oldest">Terlama</option>
                            <option value="name-asc">Nama A–Z</option>
                            <option value="name-desc">Nama Z–A</option>
                            <option value="size-desc">Ukuran terbesar</option>
                            <option value="size-asc">Ukuran terkecil</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="fm-library-meta">
                <span data-file-visible-count>
                    Menampilkan {{ $fileCollection->count() }} file
                </span>

                <button type="button" class="fm-clear-filter" data-file-clear>
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    Reset filter
                </button>
            </div>

            <div class="fm-file-grid" data-file-grid>
                @foreach ($fileCollection as $file)
                    @include('files.partials.file-card', [
                        'file' => $file,
                        'folders' => $folders,
                        'formatFileSize' => $formatFileSize,
                    ])
                @endforeach
            </div>

            <div class="fm-filter-empty" data-file-filter-empty hidden>
                <span class="fm-empty-icon">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>

                <h3>File tidak ditemukan</h3>
                <p>Coba gunakan kata kunci atau filter yang berbeda.</p>

                <button
                    type="button"
                    class="fm-button fm-button-secondary"
                    data-file-clear
                >
                    Reset pencarian
                </button>
            </div>
        @else
            <div class="fm-empty-state">
                <span class="fm-empty-icon">
                    <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                </span>

                <h3>{{ $activeFolder ? 'Grup ini masih kosong' : 'Belum ada file tersimpan' }}</h3>

                <p>
                    {{ $activeFolder ? 'Unggah file dan pilih grup ini sebagai tujuan penyimpanan.' : 'Mulai dengan mengunggah dokumen, gambar, atau file lainnya.' }}
                </p>

                <button class="fm-button fm-button-primary" type="button" data-file-upload-open>
                    <i class="fa-solid fa-upload" aria-hidden="true"></i>
                    Upload file
                </button>
            </div>
        @endif
    </section>

    <p class="fm-footer-note">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        Simpan file penting dengan nama yang mudah dikenali agar lebih mudah ditemukan.
    </p>
</div>
@endsection

@push('scripts')
    @vite('resources/js/file-manager.js')
@endpush
