<dialog
    class="fm-upload-dialog"
    data-file-upload-dialog
    aria-labelledby="fm-upload-title"
    @if ($errors->any() && old('_form') === 'files_upload') data-reopen @endif
>
    <div class="fm-upload-dialog-heading">
        <div>
            <p class="fm-section-kicker">Tambahkan berkas</p>
            <h2 id="fm-upload-title">Upload file</h2>
            <p>Pilih satu atau beberapa file, lalu tentukan grup tujuan.</p>
        </div>

        <button
            type="button"
            class="fm-icon-button"
            data-file-upload-close
            aria-label="Tutup popup upload"
        >
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
                name="files[]"
                id="fileUploadInput"
                class="fm-file-input"
                multiple
                required
                data-file-input
                aria-describedby="fm-upload-help fm-upload-feedback"
            >

            <span class="fm-upload-icon" aria-hidden="true">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
            </span>
            <span class="fm-upload-title">Pilih file atau tarik beberapa file ke sini</span>
            <span class="fm-upload-description">
                Gambar, video, dokumen, arsip, dan file teks
            </span>
            <span class="fm-upload-selected" data-file-selected>
                Belum ada file dipilih
            </span>
        </label>

        <label class="fm-upload-name-label" for="fileDisplayName" data-file-name-group>
            Nama file
            <div class="fm-upload-name-field">
                <input
                    type="text"
                    id="fileDisplayName"
                    name="file_display_name"
                    maxlength="255"
                    value="{{ old('file_display_name') }}"
                    placeholder="Opsional untuk satu file"
                    data-file-name-input
                >
                <span data-file-extension>Ekstensi</span>
            </div>
            <small>
                Untuk satu file saja. Jika memilih beberapa file, nama asli tiap file dipakai.
            </small>
        </label>

        <div class="fm-upload-folder-section">
            <label class="fm-upload-folder-label" for="fm-upload-folder">
                Simpan ke grup
                <select
                    name="folder_id"
                    id="fm-upload-folder"
                    aria-label="Grup tujuan upload"
                    data-upload-folder-select
                >
                    <option value="">Semua file (tanpa grup)</option>
                    @foreach ($folders as $folder)
                        <option
                            value="{{ $folder->id }}"
                            @selected((string) old('folder_id', $activeFolder->id ?? '') === (string) $folder->id)
                        >
                            {{ $folder->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <button
                type="button"
                class="fm-button fm-button-secondary fm-create-folder-toggle"
                data-create-folder-toggle
            >
                <i class="fa-solid fa-folder-plus" aria-hidden="true"></i>
                Buat grup baru
            </button>

            <div class="fm-new-folder-fields" data-new-folder-fields hidden>
                <label for="fm-new-folder-name">Nama grup baru</label>
                <input
                    type="text"
                    name="new_folder_name"
                    id="fm-new-folder-name"
                    maxlength="100"
                    placeholder="Contoh: Dokumen, Foto, Pekerjaan"
                    value="{{ old('new_folder_name') }}"
                    data-new-folder-input
                >
                <small>Grup akan dibuat ketika file berhasil diunggah.</small>
                <button type="button" class="fm-folder-cancel" data-cancel-new-folder>
                    Batal
                </button>
            </div>
        </div>

        <div class="fm-upload-footer">
            <div>
                <small id="fm-upload-help">
                    Maksimal 50 MB per file. Beberapa file dapat diunggah sekaligus.
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
                <span data-file-submit-label>Upload file</span>
            </button>
        </div>
    </form>
</dialog>
