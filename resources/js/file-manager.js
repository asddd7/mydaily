
(() => {
    'use strict';

    const root = document.querySelector('[data-file-manager]');

    // Tidak menjalankan script jika halaman Berkas tidak sedang dibuka.
    if (!root || root.dataset.initialized === 'true') {
        return;
    }

    root.dataset.initialized = 'true';

    const MAX_FILE_SIZE = 50 * 1024 * 1024;
    const input = root.querySelector('[data-file-input]') ||
        root.querySelector('#fileUploadInput');
    const dropzone = root.querySelector('[data-file-dropzone]');
    const uploadForm = root.querySelector('[data-file-upload-form]');
    const searchInput = root.querySelector('[data-file-search]');
    const filterSelect = root.querySelector('[data-file-filter]');
    const sortSelect = root.querySelector('[data-file-sort]');
    const clearButtons = root.querySelectorAll('[data-file-clear]');
    const grid = root.querySelector('[data-file-grid]');
    const emptyState = root.querySelector('[data-file-filter-empty]');

    const cards = grid
        ? Array.from(
            grid.querySelectorAll('[data-file-card], [data-folder-card]')
        )
        : [];

    const feedback = root.querySelector('[data-file-upload-feedback]');
    const selectedLabel = root.querySelector('[data-file-selected]');
    const uploadDialog = root.querySelector('[data-file-upload-dialog]');
    const nameInput = root.querySelector('[data-file-name-input]');
    const nameGroup = root.querySelector('[data-file-name-group]');
    const extensionLabel = root.querySelector('[data-file-extension]');
    const submitLabel = root.querySelector('[data-file-submit-label]');
    const uploadButton = uploadForm
        ? uploadForm.querySelector('button[type="submit"], button:not([type])')
        : null;

    if (uploadDialog instanceof HTMLDialogElement) {
        root.querySelectorAll('[data-file-upload-open]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!uploadDialog.open) uploadDialog.showModal();
                input?.focus();
            });
        });

        root.querySelectorAll('[data-file-upload-close]').forEach((button) => {
            button.addEventListener('click', () => uploadDialog.close());
        });

        uploadDialog.addEventListener('click', (event) => {
            if (event.target === uploadDialog) uploadDialog.close();
        });

        if (uploadDialog.hasAttribute('data-reopen')) {
            uploadDialog.showModal();
            nameInput?.focus();
        }
    }

    const normalize = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('id')
        .trim();

    const formatSize = (bytes) => {
        const size = Number(bytes);

        if (!Number.isFinite(size) || size < 0) {
            return 'Ukuran tidak diketahui';
        }

        if (size < 1024) return `${size} B`;
        if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;

        return `${(size / (1024 * 1024)).toFixed(2)} MB`;
    };

    const showFeedback = (message, invalid = false) => {
        if (feedback) {
            feedback.textContent = message;
        }

        if (selectedLabel) {
            selectedLabel.classList.toggle('is-invalid', invalid);
        }
    };

    const getFiles = () => Array.from(input?.files || []);

    let suggestedName = '';
    const updateSelectedFile = () => {
        const files = getFiles();
        const file = files.length === 1 ? files[0] : null;

        if (selectedLabel) {
            selectedLabel.hidden = false;
            if (files.length === 0) {
                selectedLabel.textContent = 'Belum ada file dipilih';
                if (submitLabel) submitLabel.textContent = 'Upload file';
            } else if (file) {
                selectedLabel.textContent = `${file.name} · ${formatSize(file.size)}`;
                if (submitLabel) submitLabel.textContent = 'Upload 1 file';
            } else {
                const totalSize = files.reduce((total, selectedFile) => total + selectedFile.size, 0);
                const names = files.slice(0, 3).map((selectedFile) => selectedFile.name);
                const remaining = files.length - names.length;
                selectedLabel.textContent =
                    `${files.length} file dipilih · ${formatSize(totalSize)}: ` +
                    `${names.join(', ')}${remaining > 0 ? `, dan ${remaining} lainnya` : ''}`;
                if (submitLabel) submitLabel.textContent = `Upload ${files.length} file`;
            }
        }

        if (file) {
            const dot = file.name.lastIndexOf('.');
            const baseName = dot > 0 ? file.name.slice(0, dot) : file.name;
            const extension = dot > 0 ? file.name.slice(dot + 1) : '';

            if (nameInput instanceof HTMLInputElement) {
                if (!nameInput.value || nameInput.value === suggestedName) {
                    nameInput.value = baseName;
                }
                nameInput.placeholder = 'Nama file';
            }

            suggestedName = baseName;

            if (extensionLabel) {
                extensionLabel.textContent = extension ? `.${extension}` : 'Tanpa ekstensi';
            }
            if (nameGroup) nameGroup.hidden = false;
            if (nameInput instanceof HTMLInputElement) nameInput.disabled = false;
        } else if (files.length > 1) {
            if (nameInput instanceof HTMLInputElement) {
                nameInput.value = '';
                nameInput.disabled = true;
            }
            if (nameGroup) nameGroup.hidden = true;
            suggestedName = '';
            if (extensionLabel) extensionLabel.textContent = 'Ekstensi';
        } else {
            if (nameInput instanceof HTMLInputElement) {
                if (nameInput.value === suggestedName) nameInput.value = '';
                nameInput.disabled = false;
                nameInput.placeholder = 'Pilih file terlebih dahulu';
            }

            if (nameGroup) nameGroup.hidden = false;
            suggestedName = '';
            if (extensionLabel) extensionLabel.textContent = 'Ekstensi';
        }

        if (files.length === 0) {
            showFeedback('');
            return true;
        }

        const invalidFile = files.find((selectedFile) =>
            selectedFile.size === 0 || selectedFile.size > MAX_FILE_SIZE
        );
        if (invalidFile) {
            showFeedback(
                invalidFile.size === 0
                    ? `${invalidFile.name} kosong dan tidak dapat diunggah.`
                    : `${invalidFile.name} melebihi batas 50 MB.`,
                true
            );
            return false;
        }

        if (submitLabel) {
            submitLabel.textContent = `Upload ${files.length} file`;
        }
        showFeedback('');
        return true;
    };

    // Upload: validasi ukuran file sebelum mengirim ke server.
    input?.addEventListener('change', updateSelectedFile);

    uploadForm?.addEventListener('submit', (event) => {
        const files = getFiles();

        if (files.length === 0) {
            event.preventDefault();
            showFeedback('Pilih minimal satu file terlebih dahulu.', true);
            input?.focus();
            return;
        }

        if (!updateSelectedFile()) {
            event.preventDefault();
            return;
        }

        if (uploadButton) {
            uploadButton.disabled = true;
            uploadButton.setAttribute('aria-busy', 'true');
            uploadButton.dataset.originalText = uploadButton.innerHTML;
            uploadButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Mengunggah...';
        }
    });

    // Drag-and-drop. Input file tetap digunakan sebagai sumber upload.
    if (dropzone && input) {
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.add('is-dragging');
            });
        });

        ['dragleave', 'dragend'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (!dropzone.contains(event.relatedTarget)) {
                    dropzone.classList.remove('is-dragging');
                }
            });
        });

        dropzone.addEventListener('drop', (event) => {
            event.preventDefault();
            event.stopPropagation();
            dropzone.classList.remove('is-dragging');

            const files = event.dataTransfer?.files;

            if (!files?.length) return;

            try {
                const transfer = new DataTransfer();
                Array.from(files).forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } catch {
                showFeedback(
                    'Browser tidak mendukung drop file ini. Gunakan tombol pilih file.',
                    true
                );
            }
        });
    }

    // Pencarian, kategori, dan pengurutan koleksi file.
    const getSortMode = () => normalize(sortSelect?.value || 'newest');

    const compareCards = (a, b, mode) => {
        const nameA = normalize(a.dataset.fileName);
        const nameB = normalize(b.dataset.fileName);
        const sizeA = Number(a.dataset.fileSize) || 0;
        const sizeB = Number(b.dataset.fileSize) || 0;
        const dateA = Number(a.dataset.fileCreated) || 0;
        const dateB = Number(b.dataset.fileCreated) || 0;

        switch (mode) {
            case 'oldest':
            case 'oldest-first':
            case 'date-asc':
                return dateA - dateB;

            case 'name':
            case 'name-asc':
            case 'alphabetical':
                return nameA.localeCompare(nameB, 'id');

            case 'name-desc':
                return nameB.localeCompare(nameA, 'id');

            case 'size-asc':
                return sizeA - sizeB;

            case 'size-desc':
                return sizeB - sizeA;

            case 'newest':
            case 'newest-first':
            case 'date-desc':
            default:
                return dateB - dateA;
        }
    };

    const applyFilters = () => {
        const query = normalize(searchInput?.value);
        const category = normalize(filterSelect?.value || 'all');
        const mode = getSortMode();
        let visibleCount = 0;

        const orderedCards = [...cards].sort((a, b) =>
            compareCards(a, b, mode)
        );

        orderedCards.forEach((card) => {
            const name = normalize(card.dataset.fileName);
            const fileCategory = normalize(card.dataset.fileCategory);
            const searchableText = normalize(card.textContent);
            const matchesSearch =
                !query || name.includes(query) || searchableText.includes(query);
            const isFolder = card.hasAttribute('data-folder-card');

            const matchesCategory =
                !category ||
                category === 'all' ||
                category === 'semua' ||
                (!isFolder && fileCategory === category);

            const visible = matchesSearch && matchesCategory;

            card.hidden = !visible;

            if (visible) visibleCount++;

            // Mengurutkan kartu di dalam grid tanpa memuat ulang halaman.
            grid?.appendChild(card);
        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0 || cards.length === 0;
        }

        const hasFilters = Boolean(query) ||
            (category && category !== 'all' && category !== 'semua');

        clearButtons.forEach((button) => {
            button.hidden = !hasFilters;
        });

        const countLabel = root.querySelector('[data-file-visible-count]');

        if (countLabel) {
            countLabel.textContent = `${visibleCount} file ditampilkan`;
        }
    };

    searchInput?.addEventListener('input', applyFilters);
    filterSelect?.addEventListener('change', applyFilters);
    sortSelect?.addEventListener('change', applyFilters);

    clearButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterSelect) filterSelect.value = 'all';

            applyFilters();
            searchInput?.focus();
        });
    });

    // Konfirmasi penghapusan file.
    root.querySelectorAll('[data-file-delete-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const card = form.closest('[data-file-card]');

            const fileName =
                card?.querySelector('.fm-file-information h3')
                    ?.textContent
                    ?.trim() || 'file ini';

            const confirmed = window.confirm(
                `Yakin ingin menghapus file "${fileName}"?\n\n` +
                'File yang dihapus tidak dapat dipulihkan.'
            );

            if (!confirmed) {
                event.preventDefault();
                return;
            }

            // Cegah tombol ditekan berkali-kali setelah dikonfirmasi.
            const button = form.querySelector('button[type="submit"]');

            if (button) {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Menghapus...';
            }
        });

    });

    root.querySelectorAll('[data-folder-delete-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(
                'Hapus grup ini? File di dalamnya tetap tersimpan di Semua file.'
            )) {
                event.preventDefault();
            }
        });
    });

    // Form ganti nama: cegah nama kosong dan fokuskan input saat dibuka.
    root.querySelectorAll('.fm-rename').forEach((details) => {
        const renameInput = details.querySelector('input[name="file_original"]');
        const renameForm = details.querySelector('form');

        details.addEventListener('toggle', () => {
            if (details.open) {
                renameInput?.focus();
                renameInput?.select();
            }
        });

        renameForm?.addEventListener('submit', (event) => {
            if (!renameInput?.value.trim()) {
                event.preventDefault();
                renameInput?.setCustomValidity('Nama file wajib diisi.');
                renameInput?.reportValidity();
                return;
            }

            renameInput.setCustomValidity('');
        });

        renameInput?.addEventListener('input', () => {
            renameInput.setCustomValidity('');
        });
    });

    // Tutup popup rename dengan Escape atau klik di luar popup.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            root.querySelectorAll('.fm-rename[open]').forEach((details) => {
                details.open = false;
            });
        }
    });

    document.addEventListener('click', (event) => {
        root.querySelectorAll('.fm-rename[open]').forEach((details) => {
            if (!details.contains(event.target)) {
                details.open = false;
            }
        });
    });

    applyFilters();

// Buat grup baru langsung dari popup upload.
const createFolderToggle = root.querySelector('[data-create-folder-toggle]');
const newFolderFields = root.querySelector('[data-new-folder-fields]');
const newFolderInput = root.querySelector('[data-new-folder-input]');
const cancelNewFolder = root.querySelector('[data-cancel-new-folder]');
const uploadFolderSelect = root.querySelector('[data-upload-folder-select]');

const mainCreateFolderButton = root.querySelector(
    '[data-create-folder-toggle-main]'
);

mainCreateFolderButton?.addEventListener('click', () => {
    if (uploadDialog instanceof HTMLDialogElement && !uploadDialog.open) {
        uploadDialog.showModal();
    }

    createFolderToggle?.click();
});

if (
    createFolderToggle &&
    newFolderFields &&
    newFolderInput &&
    cancelNewFolder
) {
    const showNewFolder = () => {
        newFolderFields.hidden = false;
        newFolderInput.required = true;

        if (uploadFolderSelect) {
            uploadFolderSelect.disabled = true;
        }

        createFolderToggle.hidden = true;
        newFolderInput.focus();
    };

    const hideNewFolder = () => {
        newFolderFields.hidden = true;
        newFolderInput.required = false;
        newFolderInput.value = '';

        if (uploadFolderSelect) {
            uploadFolderSelect.disabled = false;
        }

        createFolderToggle.hidden = false;
    };

    createFolderToggle.addEventListener('click', showNewFolder);
    cancelNewFolder.addEventListener('click', hideNewFolder);
}

if (newFolderInput?.value.trim()) {
    newFolderFields.hidden = false;
    newFolderInput.required = true;

    if (uploadFolderSelect) {
        uploadFolderSelect.disabled = true;
    }

    if (createFolderToggle) {
        createFolderToggle.hidden = true;
    }
}
})();
