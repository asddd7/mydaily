
@extends('layouts.app')

@section('title', 'Catatan - MyDaily')

@push('styles')
    @vite('resources/css/notes.css')
@endpush

@section('content')
<div class="notes-page">

    {{-- Header --}}
    <header class="notes-header">
        <div class="notes-header-content">
            <span class="notes-eyebrow">
                <i class="fa-regular fa-bookmark" aria-hidden="true"></i>
                RUANG PRIBADI
            </span>
            <h1>Catatan</h1>
            <p>Simpan ide, cerita, dan hal penting dalam satu tempat.</p>
        </div>

        <a href="#noteCreateForm" class="notes-header-button">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            <span>Catatan baru</span>
        </a>
    </header>

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

    {{-- Form tambah catatan --}}
    <section class="notes-create-card" id="noteCreateSection">
        <div class="notes-section-heading">
            <div class="notes-section-icon">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
            </div>
            <div>
                <h2>Tulis catatan baru</h2>
                <p>Tuangkan apa yang sedang kamu pikirkan.</p>
            </div>
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
                        <span class="notes-file-name" id="noteFileName">Belum ada gambar dipilih</span>
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
                <button type="submit" class="notes-primary-button">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Simpan catatan
                </button>
            </div>
        </form>
    </section>

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
    const input = document.getElementById('noteImage');
    const fileName = document.getElementById('noteFileName');

    if (!input || !fileName) return;

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        fileName.textContent = file
            ? file.name
            : 'Belum ada gambar dipilih';
    });
})();
</script>
@endpush
