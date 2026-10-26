@extends('layouts.app')

@section('title', 'Berkas - MyDaily')

@push('styles')
    @vite('resources/css/files.css')
@endpush

@section('content')
@php
    $fileCollection = collect($files ?? []);

    $totalFileBytes = $fileCollection->sum(
        fn ($file) => (int) ($file->file_size ?? 0)
    );

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
                <strong>{{ $fileCollection->count() }}</strong>
                <small>File tersimpan</small>
            </div>
        </article>

        <article class="fm-stat-card">
            <span class="fm-stat-icon fm-stat-icon-purple">
                <i class="fa-solid fa-hard-drive" aria-hidden="true"></i>
            </span>

            <div class="fm-stat-content">
                <span>Total ukuran</span>
                <strong>{{ $formatFileSize($totalFileBytes) }}</strong>
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

    {{-- Upload popup --}}
    <dialog class="fm-upload-dialog" data-file-upload-dialog aria-labelledby="fm-upload-title" @if ($errors->any() && old('_form') === 'files_upload') data-reopen @endif>
        <div class="fm-upload-dialog-heading">
            <div>
                <p class="fm-section-kicker">Tambahkan berkas</p>
                <h2 id="fm-upload-title">Upload file baru</h2>
                <p>Pilih file dan sesuaikan nama yang akan ditampilkan.</p>
            </div>
            <button type="button" class="fm-icon-button" data-file-upload-close aria-label="Tutup popup upload">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        @if ($errors->any() && old('_form') === 'files_upload')
            <div class="fm-alert fm-alert-error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="post"
            action="{{ route('files.store') }}"
            enctype="multipart/form-data"
            class="fm-upload-form"
            data-file-upload-form
        >
            @csrf
            <input type="hidden" name="_form" value="files_upload">

            <label class="fm-upload-zone" data-file-dropzone>
                <input
                    type="file"
                    name="file"
                    id="fileUploadInput"
                    class="fm-file-input"
                    required
                    data-file-input
                    aria-describedby="fm-upload-help fm-upload-feedback"
                >

                <span class="fm-upload-icon" aria-hidden="true">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                </span>

                <span class="fm-upload-title">
                    Pilih file atau tarik ke sini
                </span>

                <span class="fm-upload-description">
                    Gambar, video, dokumen, arsip, dan file teks
                </span>

                <span class="fm-upload-selected" data-file-selected>
                    Belum ada file dipilih
                </span>
            </label>

            <label class="fm-upload-name-label" for="fileDisplayName">
                Nama file
                <div class="fm-upload-name-field">
                    <input
                        type="text"
                        id="fileDisplayName"
                        name="file_display_name"
                        maxlength="255"
                        value="{{ old('file_display_name') }}"
                        placeholder="Pilih file terlebih dahulu"
                        required
                        data-file-name-input
                    >
                    <span data-file-extension>Ekstensi</span>
                </div>
                <small>Ekstensi file akan dipertahankan otomatis.</small>
            </label>

            <div class="fm-upload-footer">
                <div>
                    <small id="fm-upload-help">
                        Maksimal 50 MB untuk setiap file.
                    </small>

                    <small
                        class="fm-upload-feedback"
                        id="fm-upload-feedback"
                        data-file-upload-feedback
                        role="status"
                        aria-live="polite"
                    ></small>
                </div>

                <button class="fm-button fm-button-primary" type="submit">
                    <i class="fa-solid fa-upload" aria-hidden="true"></i>
                    Simpan file
                </button>
            </div>
        </form>
    </dialog>

    {{-- Files library --}}
    <section class="fm-panel fm-library-panel">
        <div class="fm-library-heading">
            <div class="fm-section-heading fm-section-heading-compact">
                <div>
                    <p class="fm-section-kicker">Pustaka file</p>
                    <h2>File tersimpan</h2>
                    <p>Kelola nama dan unduh file yang kamu butuhkan.</p>
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
                    @php
                        $originalName = (string) ($file->file_original ?? '');
                        $extension = strtolower(
                            pathinfo((string) ($file->file_type ?? ''), PATHINFO_EXTENSION)
                        );

                        if ($extension === '') {
                            $extension = strtolower(
                                pathinfo($originalName, PATHINFO_EXTENSION)
                            );
                        }
                        $mime = strtolower((string) ($file->file_type ?? ''));

                        $category = match (true) {
                            str_starts_with($mime, 'image/'),
                            in_array($extension, [
                                'jpg', 'jpeg', 'png', 'gif', 'webp',
                                'bmp', 'svg', 'heic', 'avif'
                            ], true) => 'image',

                            str_starts_with($mime, 'video/'),
                            in_array($extension, [
                                'mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v'
                            ], true) => 'video',

                            str_starts_with($mime, 'audio/'),
                            in_array($extension, [
                                'mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'
                            ], true) => 'audio',

                            $extension === 'pdf' => 'pdf',

                            in_array($extension, [
                                'doc', 'docx', 'odt', 'rtf'
                            ], true) => 'document',

                            in_array($extension, [
                                'xls', 'xlsx', 'csv', 'ods'
                            ], true) => 'spreadsheet',

                            in_array($extension, [
                                'zip', 'rar', '7z', 'tar', 'gz'
                            ], true) => 'archive',

                            in_array($extension, [
                                'txt', 'md', 'json', 'xml', 'html', 'css',
                                'js', 'ts', 'php', 'py', 'sql', 'yaml', 'yml'
                            ], true) => 'text',

                            default => 'other',
                        };

                        $categoryLabels = [
                            'image' => 'Gambar',
                            'video' => 'Video',
                            'audio' => 'Audio',
                            'pdf' => 'PDF',
                            'document' => 'Dokumen',
                            'spreadsheet' => 'Spreadsheet',
                            'archive' => 'Arsip',
                            'text' => 'Teks / kode',
                            'other' => 'File',
                        ];

                        $categoryIcons = [
                            'image' => 'fa-file-image',
                            'video' => 'fa-file-video',
                            'audio' => 'fa-file-audio',
                            'pdf' => 'fa-file-pdf',
                            'document' => 'fa-file-word',
                            'spreadsheet' => 'fa-file-excel',
                            'archive' => 'fa-file-zipper',
                            'text' => 'fa-file-lines',
                            'other' => 'fa-file',
                        ];

                        $displayType = $extension !== ''
                            ? strtoupper($extension)
                            : ($mime !== '' ? $mime : 'FILE');

                        $createdTimestamp = $file->created_at
                            ? (strtotime((string) $file->created_at) ?: 0)
                            : 0;

                        $createdLabel = $createdTimestamp
                            ? date('d M Y, H:i', $createdTimestamp)
                            : 'Tanggal tidak tersedia';
                    @endphp

                    <article
                        class="fm-file-card"
                        data-file-card
                        data-file-name="{{ mb_strtolower($originalName, 'UTF-8') }}"
                        data-file-category="{{ $category }}"
                        data-file-size="{{ (int) ($file->file_size ?? 0) }}"
                        data-file-created="{{ $createdTimestamp }}"
                    >
                        <div class="fm-file-card-top">
                            <span class="fm-file-icon fm-file-icon-{{ $category }}">
                                <i
                                    class="fa-solid {{ $categoryIcons[$category] }}"
                                    aria-hidden="true"
                                ></i>
                            </span>

                            <span class="fm-file-type">
                                {{ $categoryLabels[$category] }}
                            </span>
                        </div>

                        <div class="fm-file-information">
                            <h3 title="{{ $originalName }}">
                                {{ $originalName }}
                            </h3>

                            <p class="fm-file-size">
                                {{ $formatFileSize($file->file_size ?? 0) }}
                                <span aria-hidden="true">·</span>
                                {{ $displayType }}
                            </p>

                            <p class="fm-file-date">
                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                {{ $createdLabel }}
                            </p>
                        </div>

                        <div class="fm-file-actions">
                            <a
                                class="fm-button fm-button-download"
                                href="{{ route('files.download', $file->id) }}"
                            >
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                Download
                            </a>

                            <details class="fm-rename">
                                <summary
                                    class="fm-icon-button"
                                    aria-label="Ubah nama {{ $originalName }}"
                                    title="Ubah nama"
                                >
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </summary>

                                <form
                                    method="post"
                                    action="{{ route('files.update', $file->id) }}"
                                    class="fm-rename-form"
                                >
                                    @csrf
                                    @method('put')

                                    <label>
                                        Nama file baru

                                        <input
                                            type="text"
                                            name="file_original"
                                            value="{{ $originalName }}"
                                            maxlength="255"
                                            required
                                        >
                                    </label>

                                    <button
                                        type="submit"
                                        class="fm-button fm-button-primary"
                                    >
                                        Simpan nama
                                    </button>
                                </form>
                            </details>

                            <form
                                method="post"
                                action="{{ route('files.destroy', $file->id) }}"
                                class="fm-delete-form"
                                data-file-delete-form
                            >
                                @csrf
                                @method('delete')

                                <button
                                    type="submit"
                                    class="fm-icon-button fm-icon-button-danger"
                                    aria-label="Hapus {{ $originalName }}"
                                    title="Hapus file"
                                >
                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    </article>
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

                <h3>Belum ada file tersimpan</h3>

                <p>
                    Mulai dengan mengunggah dokumen, gambar, atau file lainnya.
                </p>

                <p>Gunakan tombol upload di pojok kanan bawah untuk menambahkan file.</p>
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
