
@extends('layouts.app')

@section('title', 'Catatan - MyDaily')

@push('styles')
    @vite('resources/css/notes.css')
@endpush

@section('content')
<div class="notes-page">

    {{-- Header --}}
    <section class="notes-header" aria-labelledby="notesPageTitle">
        <div class="notes-header-content">
            <span class="notes-eyebrow">
                <i class="fa-regular fa-bookmark" aria-hidden="true"></i>
                RUANG PRIBADI
            </span>
            <h1 id="notesPageTitle">Catatan</h1>
            <p>Simpan ide, cerita, dan hal penting dalam satu tempat.</p>
        </div>
    </section>

    {{-- Pesan status --}}
    @if (session('status'))
        <div class="notes-alert notes-alert-success" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="notes-alert notes-alert-error" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <div>
                <strong>Catatan belum berhasil disimpan.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Modal tambah catatan --}}
    <dialog class="notes-dialog" id="noteCreateDialog" aria-labelledby="noteModalTitle">
        <section class="notes-create-card" id="noteCreateSection">
            <div class="notes-section-heading">
                <div class="notes-section-icon">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                </div>

                <div class="notes-section-heading-text">
                    <h2 id="noteModalTitle">Tulis catatan baru</h2>
                    <p>Tuangkan apa yang sedang kamu pikirkan.</p>
                </div>

                <button
                    type="button"
                    class="notes-modal-close"
                    data-close-note-modal
                    aria-label="Tutup modal"
                    title="Tutup"
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            <form
                method="post"
                action="{{ route('notes.store') }}"
                enctype="multipart/form-data"
                id="noteCreateForm"
                class="notes-form"
            >
                @csrf

                <div class="notes-form-field">
                    <label for="noteTitle">Judul catatan</label>
                    <input
                        id="noteTitle"
                        type="text"
                        name="title"
                        maxlength="255"
                        value="{{ old('title') }}"
                        placeholder="Contoh: Ide proyek berikutnya"
                        required
                    >
                </div>

                <div class="notes-form-field">
                    <label for="noteContent">Isi catatan</label>
                    <textarea
                        id="noteContent"
                        name="content"
                        rows="6"
                        placeholder="Mulai tulis catatanmu di sini..."
                        required
                    >{{ old('content') }}</textarea>
                </div>

                <div class="notes-form-field">
                    <label for="noteImage">Gambar <span>(opsional)</span></label>

                    <div class="notes-upload-area">
                        <div class="notes-upload-icon">
                            <i class="fa-regular fa-image" aria-hidden="true"></i>
                        </div>

                        <div class="notes-upload-info">
                            <strong>Pilih gambar</strong>
                            <span>JPG, PNG, WEBP, atau format gambar yang didukung · Maks. 5 MB</span>
                            <span class="notes-file-name" id="noteFileName">
                                Belum ada gambar dipilih
                            </span>
                        </div>

                        <label for="noteImage" class="notes-upload-button">
                            Pilih file
                        </label>

                        <input
                            id="noteImage"
                            type="file"
                            name="image"
                            accept="image/*"
                        >
                    </div>
                </div>

                <div class="notes-form-footer">
                    <p>
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        Catatan tersimpan pada akunmu.
                    </p>

                    <div class="notes-modal-actions">
                        <button
                            type="button"
                            class="notes-cancel-button"
                            data-close-note-modal
                        >
                            Batal
                        </button>

                        <button type="submit" class="notes-primary-button">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            Simpan catatan
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </dialog>
    {{-- Daftar catatan --}}
    <section class="notes-list-section">
        <div class="notes-list-heading">
            <div>
                <span class="notes-eyebrow">KOLEKSI PRIBADI</span>
                <h2>Semua catatan</h2>
            </div>
            <span class="notes-count">
                <i class="fa-regular fa-note-sticky" aria-hidden="true"></i>
                {{ $notes->count() }} catatan
            </span>
        </div>

        @forelse ($notes as $note)
            <article class="notes-card">
                @if ($note->image)
                    <a
                        href="{{ route('notes.show', $note->id) }}"
                        class="notes-card-image"
                        aria-label="Buka catatan {{ $note->title }}"
                    >
                        <img
                            src="{{ route('notes.image', $note->id) }}"
                            alt="Gambar catatan {{ $note->title }}"
                            loading="lazy"
                        >
                    </a>
                @else
                    <div class="notes-card-image notes-card-image-empty" aria-hidden="true">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>
                @endif

                <div class="notes-card-body">
                    <div class="notes-card-meta">
                        <span>
                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                            {{ \Illuminate\Support\Carbon::parse($note->created_at)->translatedFormat('d M Y') }}
                        </span>
                        @if ($note->updated_at !== $note->created_at)
                            <span class="notes-updated-label">Catatan</span>
                        @endif
                    </div>

                    <h3>
                        <a href="{{ route('notes.show', $note->id) }}">
                            {{ $note->title }}
                        </a>
                    </h3>

                    <p class="notes-card-excerpt">
                        {{ \Illuminate\Support\Str::limit($note->content, 180) }}
                    </p>

                    <div class="notes-card-footer">
                        <a
                            href="{{ route('notes.show', $note->id) }}"
                            class="notes-open-link"
                        >
                            Buka catatan
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>

                        <form
                            method="post"
                            action="{{ route('notes.destroy', $note->id) }}"
                            onsubmit="return confirm('Yakin ingin menghapus catatan ini?')"
                        >
                            @csrf
                            @method('delete')
                            <button
                                type="submit"
                                class="notes-delete-button"
                                aria-label="Hapus catatan {{ $note->title }}"
                                title="Hapus catatan"
                            >
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="notes-empty-state">
                <div class="notes-empty-icon">
                    <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                </div>
                <h3>Belum ada catatan</h3>
                <p>Catatan pertamamu bisa dimulai dari ide kecil hari ini.</p>
                <a href="#noteCreateForm" class="notes-primary-button">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Buat catatan pertama
                </a>
            </div>
        @endforelse
    </section>
</div>
@endsection


@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('noteCreateDialog');
    const form = document.getElementById('noteCreateForm');
    const input = document.getElementById('noteImage');
    const fileName = document.getElementById('noteFileName');

    if (!dialog || !form) return;

    const openModal = () => {
        if (!dialog.open) {
            dialog.showModal();
        }

        requestAnimationFrame(() => {
            dialog.querySelector('input:not([type="file"]), textarea')
                ?.focus({ preventScroll: true });
        });
    };

    const closeModal = () => {
        if (dialog.open) {
            dialog.close();
        }
    };

    document.querySelectorAll('[data-open-note-modal]').forEach(button => {
        button.addEventListener('click', openModal);
    });

    document.querySelectorAll('[data-close-note-modal]').forEach(button => {
        button.addEventListener('click', closeModal);
    });

    // Integrasi dengan floating-actions.blade.php
    document.addEventListener('note:open-create', openModal);

    // Klik backdrop menutup modal, tetapi klik isi modal tidak.
    dialog.addEventListener('click', event => {
        if (event.target === dialog) {
            closeModal();
        }
    });

    // Perbarui nama file yang dipilih.
    if (input && fileName) {
        input.addEventListener('change', () => {
            fileName.textContent = input.files?.[0]?.name
                || 'Belum ada gambar dipilih';
        });
    }

    // Buka kembali modal jika validasi server gagal.
    @if ($errors->any())
        openModal();
    @endif
})();
</script>
@endpush

