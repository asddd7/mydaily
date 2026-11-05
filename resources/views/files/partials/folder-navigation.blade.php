<section class="fm-panel fm-folder-panel" aria-labelledby="fm-folder-title">
    <div class="fm-folder-heading">
        <div>
            <p class="fm-section-kicker">Pengelompokan</p>
            <h2 id="fm-folder-title">Grup file</h2>
            <p>Buat grup untuk merapikan dan mengelompokkan file.</p>
        </div>
    </div>

    <nav class="fm-folder-list" aria-label="Pilih grup file">
        <a
            class="fm-folder-link {{ $activeFolder ? '' : 'is-active' }}"
            href="{{ route('files.index') }}"
            @if (! $activeFolder) aria-current="page" @endif
        >
            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
            <span>Semua file</span>
            <small>{{ $totalFiles }}</small>
        </a>

        @foreach ($folders as $folder)
            @php($isActiveFolder = (int) ($activeFolder->id ?? 0) === (int) $folder->id)

            <div class="fm-folder-item">
                <a
                    class="fm-folder-link {{ $isActiveFolder ? 'is-active' : '' }}"
                    href="{{ route('files.index', ['folder' => $folder->id]) }}"
                    @if ($isActiveFolder) aria-current="page" @endif
                >
                    <i class="fa-solid fa-folder" aria-hidden="true"></i>
                    <span>{{ $folder->name }}</span>
                    <small>{{ $folder->files_count }}</small>
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

                            <button class="fm-button fm-button-secondary" type="submit">
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

                            <button class="fm-folder-delete-button" type="submit">
                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                Hapus grup
                            </button>
                            <small>File di dalamnya tetap tersimpan di Semua file.</small>
                        </form>
                    </div>
                </details>
            </div>
        @endforeach
    </nav>
</section>
