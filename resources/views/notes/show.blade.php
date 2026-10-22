
@extends('layouts.app')

@section('title', 'Edit Catatan - MyDaily')

@push('styles')
    @vite('resources/css/notes.css')
@endpush

@section('content')
<div class="notes-edit-page">

    {{-- Header halaman --}}
    <section class="notes-edit-header" aria-labelledby="notesEditTitle">
        <a href="{{ route('notes.index') }}" class="notes-edit-back">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Kembali ke catatan</span>
        </a>

        <div class="notes-edit-heading">
            <span class="notes-eyebrow">
                <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                RUANG PRIBADI
            </span>

            <h1 id="notesEditTitle">Edit catatan</h1>
            <p>Perbarui ide, cerita, atau informasi yang ingin kamu simpan.</p>
        </div>
    </section>

    <div class="notes-edit-layout">

        {{-- Form edit --}}
        <section class="notes-edit-card">
            <div class="notes-edit-card-heading">
                <div class="notes-section-icon">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                </div>

                <div>
                    <h2>Detail catatan</h2>
                    <p>Ubah informasi sesuai kebutuhanmu.</p>
                </div>
            </div>

            <form
                method="post"
                action="{{ route('notes.update', $note->id) }}"
                enctype="multipart/form-data"
                class="notes-edit-form"
                id="noteEditForm"
            >
                @csrf
                @method('put')

                <div class="notes-form-field">
                    <label for="editNoteTitle">Judul catatan</label>

                    <input
                        id="editNoteTitle"
                        type="text"
                        name="title"
                        value="{{ old('title', $note->title) }}"
                        maxlength="255"
                        placeholder="Masukkan judul catatan"
                        required
                    >

                    @error('title')
                        <small class="notes-edit-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="notes-form-field">
                    <label for="editNoteContent">Isi catatan</label>

                    <textarea
                        id="editNoteContent"
                        name="content"
                        rows="12"
                        placeholder="Tulis isi catatanmu di sini..."
                        required
                    >{{ old('content', $note->content) }}</textarea>

                    @error('content')
                        <small class="notes-edit-field-error">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Gambar --}}
                <div class="notes-form-field">
                    <label for="editNoteImage">
                        Gambar catatan
                        <span>(opsional)</span>
                    </label>

                    <div class="notes-edit-image-panel">

                        <div
                            class="notes-edit-image-preview{{ $note->image ? '' : ' is-empty' }}"
                            id="editNoteImagePreview"
                        >
                            @if ($note->image)
                                <img
                                    src="{{ route('notes.image', $note->id) }}"
                                    alt="Gambar saat ini untuk {{ $note->title }}"
                                    id="editNoteImagePreviewImg"
                                >
                            @else
                                <div class="notes-edit-no-image" id="editNoteNoImage">
                                    <i class="fa-regular fa-image" aria-hidden="true"></i>
                                    <span>Belum ada gambar</span>
                                </div>

                                <img
                                    src=""
                                    alt="Pratinjau gambar baru"
                                    id="editNoteImagePreviewImg"
                                    hidden
                                >
                            @endif
                        </div>

                        <div class="notes-edit-image-info">
                            <strong>
                                {{ $note->image ? 'Gambar saat ini' : 'Tambahkan gambar' }}
                            </strong>

                            <p>
                                Pilih gambar baru jika ingin mengganti gambar yang tersimpan.
                                Maksimal 5 MB.
                            </p>

                            <label
                                for="editNoteImage"
                                class="notes-upload-button notes-edit-select-image"
                            >
                                <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                                Pilih gambar
                            </label>

                            <input
                                id="editNoteImage"
                                type="file"
                                name="image"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                            >

                            <span class="notes-file-name" id="editNoteFileName">
                                Belum ada gambar baru dipilih
                            </span>

                            @error('image')
                                <small class="notes-edit-field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <p class="notes-edit-image-hint">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        Biarkan kosong jika tidak ingin mengganti gambar.
                    </p>
                </div>

                {{-- Informasi catatan --}}
                <div class="notes-edit-metadata">
                    <div>
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                        <span>Dibuat</span>
                        <strong>
                            {{ \Illuminate\Support\Carbon::parse($note->created_at)->translatedFormat('d M Y, H:i') }}
                        </strong>
                    </div>

                    <div>
                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        <span>Diperbarui</span>
                        <strong>
                            {{ \Illuminate\Support\Carbon::parse($note->updated_at)->translatedFormat('d M Y, H:i') }}
                        </strong>
                    </div>
                </div>

                {{-- Tombol aksi --}}
                <div class="notes-edit-actions">
                    <a
                        href="{{ route('notes.index') }}"
                        class="notes-edit-cancel"
                    >
                        Batal
                    </a>

                    <button type="submit" class="notes-primary-button">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Simpan perubahan
                    </button>
                </div>
            </form>
        </section>

    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('editNoteImage');
    const fileName = document.getElementById('editNoteFileName');
    const preview = document.getElementById('editNoteImagePreview');
    const previewImg = document.getElementById('editNoteImagePreviewImg');

    if (!input || !fileName || !preview || !previewImg) return;

    let previewUrl = null;

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file) {
            fileName.textContent = 'Belum ada gambar baru dipilih';

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            return;
        }

        fileName.textContent = file.name;

        if (!file.type.startsWith('image/')) {
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);
        previewImg.src = previewUrl;
        previewImg.hidden = false;

        const noImage = document.getElementById('editNoteNoImage');
        if (noImage) noImage.hidden = true;

        preview.classList.remove('is-empty');
    });

    window.addEventListener('beforeunload', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
})();
</script>
@endpush
