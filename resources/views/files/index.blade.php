
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

    $isMainPage = ! $activeFolder;
    $hasFolders = $isMainPage && $folders->isNotEmpty();
    $hasFiles = $fileCollection->isNotEmpty();
    $hasItems = $hasFolders || $hasFiles;
    $totalItems = ($hasFolders ? $folders->count() : 0) + $fileCollection->count();
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

    {{-- Upload dialog --}}
    @include('files.partials.upload-dialog', [
        'activeFolder' => $activeFolder,
        'folders' => $folders,
    ])

    {{-- Unified library --}}
    <section class="fm-panel fm-library-panel">

        <div class="fm-library-heading">
            <div class="fm-section-heading fm-section-heading-compact">
                <div>
                    <p class="fm-section-kicker">
                        {{ $activeFolder ? 'Isi grup' : 'Pustaka penyimpanan' }}
                    </p>

                    <h2>
                        @if ($activeFolder)
                            <a href="{{ route('files.index') }}" class="fm-breadcrumb-link">
                                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                                Semua File
                            </a>
                            <span class="fm-breadcrumb-separator">/</span>
                            {{ $activeFolder->name }}
                        @else
                            Semua File
                        @endif
                    </h2>

                    <p>
                        @if ($activeFolder)
                            File yang tersimpan di grup ini.
                        @else
                            Folder dan file ditampilkan dalam satu tempat.
                        @endif
                    </p>
                </div>
            </div>

            <div class="fm-library-heading-actions">
                @if ($isMainPage)
                    <button
                        type="button"
                        class="fm-button fm-button-secondary"
                        data-create-folder-toggle-main
                    >
                        <i class="fa-solid fa-folder-plus" aria-hidden="true"></i>
                        Buat Grup
                    </button>
                @endif

                <button
                    type="button"
                    class="fm-total-badge"
                    data-file-total
                >
                    {{ $totalItems }} item
                </button>
            </div>
        </div>

        @if ($hasItems)

            {{-- Toolbar --}}
            <div class="fm-toolbar">
                <label class="fm-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>

                    <input
                        type="search"
                        placeholder="Cari nama file atau grup..."
                        aria-label="Cari file atau grup"
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

                        <select data-file-sort aria-label="Urutkan item">
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
                    Menampilkan {{ $totalItems }} item
                </span>

                <button type="button" class="fm-clear-filter" data-file-clear>
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    Reset filter
                </button>
            </div>

            {{-- Folder dan file dalam satu grid --}}
            <div class="fm-file-grid" data-file-grid>

                {{-- Folder hanya ditampilkan di halaman utama --}}
                @if ($hasFolders)
                    @foreach ($folders as $folder)
                        @php
                            $folderTimestamp = $folder->updated_at
                                ? (strtotime((string) $folder->updated_at) ?: 0)
                                : 0;
                        @endphp

                        <article
                            class="fm-folder-card"
                            data-folder-card
                            data-file-name="{{ mb_strtolower($folder->name, 'UTF-8') }}"
                            data-file-category="folder"
                            data-file-size="0"
                            data-file-created="{{ $folderTimestamp }}"
                        >
                            <a
                                class="fm-folder-card-main"
                                href="{{ route('files.index', ['folder' => $folder->id]) }}"
                                aria-label="Buka grup {{ $folder->name }}"
                            >
                                <span class="fm-folder-card-icon">
                                    <i class="fa-solid fa-folder" aria-hidden="true"></i>
                                </span>

                                <span class="fm-folder-card-info">
                                    <strong title="{{ $folder->name }}">
                                        {{ $folder->name }}
                                    </strong>
                                    <small>{{ $folder->files_count }} file</small>
                                </span>
                            </a>

                            <details class="fm-folder-actions">
                                <summary
                                    class="fm-icon-button"
                                    aria-label="Kelola grup {{ $folder->name }}"
                                    title="Kelola grup"
                                >
                                    <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
                                </summary>

                                <div class="fm-folder-action-menu">
                                    <form
                                        method="post"
                                        action="{{ route('files.folders.update', $folder->id) }}"
                                        class="fm-folder-rename-form"
                                    >
                                        @csrf
                                        @method('put')

                                        <label>
                                            Ubah nama grup
                                            <input
                                                type="text"
                                                name="name"
                                                value="{{ $folder->name }}"
                                                maxlength="100"
                                                required
                                            >
                                        </label>

                                        <button
                                            class="fm-button fm-button-secondary"
                                            type="submit"
                                        >
                                            Simpan nama
                                        </button>
                                    </form>

                                    <form
                                        method="post"
                                        action="{{ route('files.folders.destroy', $folder->id) }}"
                                        data-folder-delete-form
                                    >
                                        @csrf
                                        @method('delete')

                                        <button
                                            class="fm-folder-delete-button"
                                            type="submit"
                                        >
                                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                            Hapus grup
                                        </button>

                                        <small>
                                            File di dalamnya tetap tersimpan.
                                        </small>
                                    </form>
                                </div>
                            </details>
                        </article>
                    @endforeach
                @endif

                {{-- File --}}
                @foreach ($fileCollection as $file)
                    @include('files.partials.file-card', [
                        'file' => $file,
                        'folders' => $folders,
                        'formatFileSize' => $formatFileSize,
                    ])
                @endforeach

            </div>

            {{-- Filter empty --}}
            <div class="fm-filter-empty" data-file-filter-empty hidden>
                <span class="fm-empty-icon">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>

                <h3>Item tidak ditemukan</h3>
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

                <h3>
                    {{ $activeFolder ? 'Grup ini masih kosong' : 'Belum ada file atau grup' }}
                </h3>

                <p>
                    {{ $activeFolder
                        ? 'Unggah file dan pilih grup ini sebagai tujuan penyimpanan.'
                        : 'Mulai dengan membuat grup atau mengunggah file.' }}
                </p>

                <button
                    class="fm-button fm-button-primary"
                    type="button"
                    data-file-upload-open
                >
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
