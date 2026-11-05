@php
    $originalName = (string) ($file->file_original ?? '');
    $extension = strtolower(trim((string) ($file->file_type ?? '')));
    $mime = strtolower((string) ($file->file_type ?? ''));

    if ($extension === '' || str_contains($extension, '/')) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    }

    $category = match (true) {
        str_starts_with($mime, 'image/'),
        in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic', 'avif'], true) => 'image',
        str_starts_with($mime, 'video/'),
        in_array($extension, ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v'], true) => 'video',
        str_starts_with($mime, 'audio/'),
        in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'], true) => 'audio',
        $extension === 'pdf' => 'pdf',
        in_array($extension, ['doc', 'docx', 'odt', 'rtf'], true) => 'document',
        in_array($extension, ['xls', 'xlsx', 'csv', 'ods'], true) => 'spreadsheet',
        in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'], true) => 'archive',
        in_array($extension, ['txt', 'md', 'json', 'xml', 'html', 'css', 'js', 'ts', 'php', 'py', 'sql', 'yaml', 'yml'], true) => 'text',
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

    $isPreviewableImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true);
    $displayType = $extension !== '' ? strtoupper($extension) : ($mime !== '' ? $mime : 'FILE');
    $createdTimestamp = $file->created_at ? (strtotime((string) $file->created_at) ?: 0) : 0;
    $createdLabel = $createdTimestamp ? date('d M Y, H:i', $createdTimestamp) : 'Tanggal tidak tersedia';
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
        @if ($category === 'image' && $isPreviewableImage)
            <a
                class="fm-file-preview"
                href="{{ route('files.preview', $file->id) }}"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Lihat gambar {{ $originalName }}"
                title="Klik untuk melihat gambar ukuran penuh"
            >
                <img
                    src="{{ route('files.preview', $file->id) }}"
                    alt="Preview {{ $originalName }}"
                    loading="lazy"
                    decoding="async"
                    width="64"
                    height="64"
                >
            </a>
        @else
            <span class="fm-file-icon fm-file-icon-{{ $category }}">
                <i class="fa-solid {{ $categoryIcons[$category] }}" aria-hidden="true"></i>
            </span>
        @endif

        <span class="fm-file-type">{{ $categoryLabels[$category] }}</span>
    </div>

    <div class="fm-file-information">
        <h3 title="{{ $originalName }}">{{ $originalName }}</h3>
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

    @if ($folders->isNotEmpty())
        <form
            method="post"
            action="{{ route('files.move', $file->id) }}"
            class="fm-file-move-form"
        >
            @csrf
            @method('patch')
            <label for="file-folder-{{ $file->id }}">Grup</label>
            <div>
                <select id="file-folder-{{ $file->id }}" name="folder_id">
                    <option value="">Tanpa grup</option>
                    @foreach ($folders as $folder)
                        <option
                            value="{{ $folder->id }}"
                            @selected((int) ($file->folder_id ?? 0) === (int) $folder->id)
                        >
                            {{ $folder->name }}
                        </option>
                    @endforeach
                </select>
                <button
                    class="fm-icon-button"
                    type="submit"
                    aria-label="Simpan grup untuk {{ $originalName }}"
                    title="Simpan grup"
                >
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    @endif

    <div class="fm-file-actions">
        <a class="fm-button fm-button-download" href="{{ route('files.download', $file->id) }}">
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
                <button type="submit" class="fm-button fm-button-primary">
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
