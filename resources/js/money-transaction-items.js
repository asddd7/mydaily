
const initializeTransactionItems = () => {
    const form = document.getElementById('moneyCreateForm');
    if (!(form instanceof HTMLFormElement)) return;

    const section = form.querySelector('[data-line-items]');
    const itemList = section?.querySelector('[data-item-list]');
    const addButton = section?.querySelector('[data-add-item]');
    const totalInput = form.querySelector('[data-grand-total]');
    const totalHint = form.querySelector('[data-total-hint]');

    if (
        !(section instanceof HTMLElement) ||
        !(itemList instanceof HTMLElement) ||
        !(addButton instanceof HTMLButtonElement) ||
        !(totalInput instanceof HTMLInputElement) ||
        !(totalHint instanceof HTMLElement)
    ) {
        return;
    }

    // Daftar pilihan barang hasil scan OCR.
    // Barang hanya masuk ke transaksi setelah dipilih pada kolom nama barang.
    let scannedItems = [];

    const datalistId = 'money-scanned-item-options';
    let datalist = document.getElementById(datalistId);

    if (!(datalist instanceof HTMLDataListElement)) {
        datalist = document.createElement('datalist');
        datalist.id = datalistId;
        document.body.append(datalist);
    }

    const searchStatus = document.createElement('small');
    searchStatus.className = 'money-item-search-status';
    searchStatus.setAttribute('aria-live', 'polite');

    const addButtonParent = addButton.parentElement;
    if (addButtonParent) {
        addButtonParent.append(searchStatus);
    }

    const getRows = () =>
        [...itemList.querySelectorAll('[data-item-row]')];

    const normalizeName = value =>
        String(value ?? '')
            .trim()
            .toLocaleLowerCase('id')
            .replace(/\s+/gu, ' ');

    const updateSuggestions = items => {
        const uniqueItems = new Map();

        items.forEach(item => {
            if (
                !item ||
                typeof item.name !== 'string' ||
                !item.name.trim() ||
                !Number.isFinite(Number(item.amount)) ||
                Number(item.amount) <= 0
            ) {
                return;
            }

            const name = item.name.trim().slice(0, 150);
            const amount = Math.round(Number(item.amount) * 100) / 100;
            const key = `${normalizeName(name)}|${amount}`;

            if (!uniqueItems.has(key)) {
                uniqueItems.set(key, { name, amount });
            }
        });

        scannedItems = [...uniqueItems.values()];

        datalist.replaceChildren();

        scannedItems.forEach(item => {
            const option = document.createElement('option');
            option.value = item.name;
            option.label = `Rp ${item.amount.toLocaleString('id-ID')}`;
            datalist.append(option);
        });

        searchStatus.textContent = scannedItems.length
            ? `${scannedItems.length} pilihan barang dari hasil scan. Ketik nama barang pada kolom Nama barang.`
            : 'Belum ada saran barang dari hasil scan. Kamu tetap bisa mengetik nama secara manual.';
    };

    const findScannedItem = name => {
        const normalized = normalizeName(name);

        return scannedItems.find(item =>
            normalizeName(item.name) === normalized
        ) ?? null;
    };

    const updateTotal = () => {
        const rows = getRows();
        totalInput.readOnly = rows.length > 0;

        if (rows.length === 0) {
            totalHint.textContent =
                'Isi grand total secara manual, atau tambahkan barang untuk menghitung total otomatis.';
            return;
        }

        const amounts = rows.map(row => {
            const input = row.querySelector('[data-item-amount]');

            return input instanceof HTMLInputElement
                ? input.value.trim()
                : '';
        });

        if (
            amounts.some(value =>
                value === '' ||
                !Number.isFinite(Number(value)) ||
                Number(value) <= 0
            )
        ) {
            totalInput.value = '';
            totalHint.textContent =
                'Lengkapi harga setiap barang untuk menghitung grand total.';
            return;
        }

        const totalCents = amounts.reduce(
            (sum, amount) => sum + Math.round(Number(amount) * 100),
            0
        );

        totalInput.value = (totalCents / 100).toFixed(2);
        totalInput.dispatchEvent(new Event('input', { bubbles: true }));
        totalInput.dispatchEvent(new Event('change', { bubbles: true }));

        totalHint.textContent =
            'Grand total dihitung dari barang yang sudah dimasukkan.';
    };

    const addItemRow = (item = {}) => {
        const existingIndexes = getRows()
            .map(row => {
                const input = row.querySelector('input[name^="items["]');
                return input?.name.match(/^items\[(\d+)\]/u)?.[1];
            })
            .filter(index => index !== undefined)
            .map(Number);

        const index = existingIndexes.length
            ? Math.max(...existingIndexes) + 1
            : 0;

        const row = document.createElement('div');
        row.className = 'money-line-item';
        row.dataset.itemRow = '';

        const nameLabel = document.createElement('label');
        nameLabel.append(document.createTextNode('Nama barang'));

        const nameInput = document.createElement('input');
        nameInput.type = 'search';
        nameInput.name = `items[${index}][name]`;
        nameInput.maxLength = 150;
        nameInput.placeholder = scannedItems.length
            ? 'Cari atau ketik nama barang...'
            : 'Contoh: Nasi goreng';
        nameInput.autocomplete = 'off';
        nameInput.setAttribute('list', datalistId);
        nameInput.required = true;
        nameInput.value = typeof item.name === 'string' ? item.name : '';
        nameLabel.append(nameInput);

        const amountLabel = document.createElement('label');
        amountLabel.append(document.createTextNode('Harga'));

        const amountInput = document.createElement('input');
        amountInput.type = 'number';
        amountInput.name = `items[${index}][amount]`;
        amountInput.min = '0.01';
        amountInput.max = '9999999999';
        amountInput.step = '0.01';
        amountInput.placeholder = '0';
        amountInput.required = true;
        amountInput.dataset.itemAmount = '';
        amountInput.value = item.amount != null ? String(item.amount) : '';
        amountLabel.append(amountInput);

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'money-remove-item-button';
        removeButton.dataset.removeItem = '';
        removeButton.setAttribute('aria-label', 'Hapus barang');
        removeButton.innerHTML =
            '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';

        row.append(nameLabel, amountLabel, removeButton);
        itemList.append(row);

        return row;
    };

    // Tambah satu baris kosong; barang tidak ditambahkan otomatis dari OCR.
    addButton.addEventListener('click', () => {
        const row = addItemRow();
        updateTotal();
        row.querySelector('input')?.focus();
    });

    // Saat nama barang dipilih dari saran OCR, isi harganya.
    itemList.addEventListener('input', event => {
        if (!(event.target instanceof HTMLInputElement)) return;

        if (event.target.matches('input[name$="[name]"]')) {
            const row = event.target.closest('[data-item-row]');
            const amountInput = row?.querySelector('[data-item-amount]');
            const match = findScannedItem(event.target.value);

            if (
                match &&
                amountInput instanceof HTMLInputElement
            ) {
                amountInput.value = String(match.amount);
                updateTotal();
            }

            return;
        }

        if (event.target.matches('[data-item-amount]')) {
            updateTotal();
        }
    });

    itemList.addEventListener('change', event => {
        if (
            !(event.target instanceof HTMLInputElement) ||
            !event.target.matches('input[name$="[name]"]')
        ) {
            return;
        }

        const row = event.target.closest('[data-item-row]');
        const amountInput = row?.querySelector('[data-item-amount]');
        const match = findScannedItem(event.target.value);

        if (
            match &&
            amountInput instanceof HTMLInputElement
        ) {
            amountInput.value = String(match.amount);
            updateTotal();
        }
    });

    itemList.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;

        const removeButton = event.target.closest('[data-remove-item]');
        if (!removeButton) return;

        removeButton.closest('[data-item-row]')?.remove();
        updateTotal();
    });

    // Terima kandidat OCR tanpa memasukkan semuanya ke rincian transaksi.
    form.addEventListener('money:scan-items', event => {
        updateSuggestions(
            Array.isArray(event.detail) ? event.detail : []
        );

        // Perbarui saran pada baris yang sudah ada.
        getRows().forEach(row => {
            const nameInput = row.querySelector('input[name$="[name]"]');

            if (nameInput instanceof HTMLInputElement) {
                nameInput.setAttribute('list', datalistId);
                nameInput.placeholder = scannedItems.length
                    ? 'Cari atau ketik nama barang...'
                    : 'Contoh: Nasi goreng';
            }
        });
    });

    // Dukungan untuk data lama dari validasi Laravel.
    itemList.querySelectorAll('[data-item-row]').forEach(row => {
        const nameInput = row.querySelector('input[name$="[name]"]');
        const amountInput = row.querySelector('input[name$="[amount]"]');

        if (nameInput instanceof HTMLInputElement) {
            nameInput.type = 'search';
            nameInput.setAttribute('list', datalistId);
            nameInput.autocomplete = 'off';
        }

        if (amountInput instanceof HTMLInputElement) {
            amountInput.dataset.itemAmount = '';
            amountInput.type = 'number';
            amountInput.min = '0.01';
            amountInput.max = '9999999999';
            amountInput.step = '0.01';
        }
    });

    updateSuggestions([]);
    updateTotal();
};

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initializeTransactionItems,
            { once: true }
        );
    } else {
        initializeTransactionItems();
    }
}
