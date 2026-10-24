
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
    ) return;


    // Kolom pencarian rincian barang.
    const searchLabel = document.createElement('label');
    searchLabel.className = 'money-item-search';
    searchLabel.textContent = 'Cari nama barang hasil scan';

    const searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.placeholder = 'Contoh: Indomie, susu, beras...';
    searchInput.autocomplete = 'off';
    searchInput.setAttribute(
        'aria-label',
        'Cari nama barang pada rincian transaksi'
    );

    searchLabel.append(searchInput);
    itemList.parentElement.insertBefore(searchLabel, itemList);

    const searchStatus = document.createElement('small');
    searchStatus.setAttribute('aria-live', 'polite');
    searchLabel.append(searchStatus);

    const getRows = () =>
        [...itemList.querySelectorAll('[data-item-row]')];

    const filterItems = () => {
        const keyword = searchInput.value
            .trim()
            .toLocaleLowerCase('id');

        let visibleCount = 0;

        getRows().forEach(row => {
            const nameInput = row.querySelector(
                'input[name$="[name]"]'
            );

            const name = nameInput instanceof HTMLInputElement
                ? nameInput.value.toLocaleLowerCase('id')
                : '';

            const visible = !keyword || name.includes(keyword);

            row.hidden = !visible;

            if (visible) visibleCount++;
        });

        const totalRows = getRows().length;

        searchStatus.textContent = keyword
            ? `${visibleCount} dari ${totalRows} barang ditemukan`
            : `${totalRows} barang`;
    };

    searchInput.addEventListener('input', filterItems);


    const updateTotal = () => {
        const rows = getRows();
        totalInput.readOnly = rows.length > 0;

        if (rows.length === 0) {
            totalHint.textContent =
                'Isi grand total secara manual, atau tambahkan rincian barang agar total dihitung otomatis.';
            return;
        }

        const amounts = rows.map(row => {
            const input = row.querySelector('[data-item-amount]');
            return input instanceof HTMLInputElement ? input.value.trim() : '';
        });

        if (amounts.some(value => value === '' || !Number.isFinite(Number(value)) || Number(value) <= 0)) {
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
            'Grand total dihitung otomatis dari seluruh harga barang.';
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
        nameInput.name = `items[${index}][name]`;
        nameInput.maxLength = 150;
        nameInput.placeholder = 'Contoh: Nasi goreng';
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
        filterItems();

        return row;
    };

    addButton.addEventListener('click', () => {
        const row = addItemRow();
        updateTotal();
        row.querySelector('input')?.focus();
    });

    itemList.addEventListener('input', event => {
        if (
            event.target instanceof HTMLInputElement &&
            event.target.matches('[data-item-amount]')
        ) {
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

    form.addEventListener('money:replace-items', event => {
        const items = Array.isArray(event.detail) ? event.detail : [];

        itemList.replaceChildren();

        items.forEach(item => addItemRow(item));

        filterItems();
        updateTotal();
    });

    // Pastikan baris lama dari validasi Laravel juga dihitung.
    itemList.querySelectorAll('[data-item-row]').forEach(row => {
        row.querySelectorAll('input').forEach(input => {
            if (input.name.endsWith('[amount]')) {
                input.dataset.itemAmount = '';
                input.type = 'number';
                input.min = '0.01';
                input.max = '9999999999';
                input.step = '0.01';
            }
        });
    });

    updateTotal();
};

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTransactionItems, {
            once: true,
        });
    } else {
        initializeTransactionItems();
        filterItems();
    }
}
