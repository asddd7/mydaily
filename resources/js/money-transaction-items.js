import { initializeRupiahInput, setRupiahInputValue } from './money-currency.js';

const initializeTransactionItems = () => {
    const form = document.getElementById('moneyCreateForm');
    const section = form?.querySelector('[data-line-items]');
    const itemList = section?.querySelector('[data-item-list]');
    const addButton = section?.querySelector('[data-add-item]');
    const totalInput = form?.querySelector('[data-grand-total]');
    const totalHint = form?.querySelector('[data-total-hint]');
    const totalValue = totalInput?.parentElement?.querySelector('[data-currency-value]');

    if (
        !(form instanceof HTMLFormElement)
        || !(section instanceof HTMLElement)
        || !(itemList instanceof HTMLElement)
        || !(addButton instanceof HTMLButtonElement)
        || !(totalInput instanceof HTMLInputElement)
        || !(totalValue instanceof HTMLInputElement)
        || !(totalHint instanceof HTMLElement)
    ) return;

    form.querySelectorAll('[data-currency-input]').forEach(input => {
        if (input instanceof HTMLInputElement) initializeRupiahInput(input);
    });

    const existingIndexes = [...itemList.querySelectorAll('[data-item-row] input[name]')]
        .map(input => input.name.match(/^items\[(\d+)\]/u)?.[1])
        .filter(index => index !== undefined)
        .map(Number);
    let nextIndex = existingIndexes.length ? Math.max(...existingIndexes) + 1 : 0;

    const updateTotal = () => {
        const rows = [...itemList.querySelectorAll('[data-item-row]')];
        totalInput.readOnly = rows.length > 0;

        if (rows.length === 0) {
            totalHint.textContent = 'Isi grand total secara manual, atau tambahkan rincian barang agar total dihitung otomatis.';
            return;
        }

        const amounts = rows.map(row => row.querySelector('[data-currency-value]')?.value ?? '');
        if (amounts.some(amount => amount === '')) {
            totalInput.value = '';
            totalValue.value = '';
            totalHint.textContent = 'Lengkapi harga setiap barang untuk menghitung grand total.';
            return;
        }

        const totalCents = amounts.reduce((sum, amount) => sum + Math.round(Number(amount) * 100), 0);
        setRupiahInputValue(totalInput, (totalCents / 100).toFixed(2));
        totalHint.textContent = 'Grand total dihitung otomatis dari seluruh harga barang.';
    };

    addButton.addEventListener('click', () => {
        const index = nextIndex++;
        const row = document.createElement('div');
        row.className = 'money-line-item';
        row.dataset.itemRow = '';
        row.innerHTML = `
            <label>Nama barang
                <input name="items[${index}][name]" maxlength="150" placeholder="Contoh: Nasi goreng" required>
            </label>
            <label>Harga
                <input type="text" inputmode="decimal" placeholder="Rp 0" required data-currency-input data-item-amount>
                <input type="hidden" name="items[${index}][amount]" data-currency-value>
            </label>
            <button type="button" class="money-remove-item-button" data-remove-item aria-label="Hapus barang">
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
            </button>
        `;
        itemList.append(row);
        const itemAmount = row.querySelector('[data-currency-input]');
        if (itemAmount instanceof HTMLInputElement) initializeRupiahInput(itemAmount);
        row.querySelector('input')?.focus();
        updateTotal();
    });

    itemList.addEventListener('input', event => {
        if (event.target instanceof HTMLInputElement && event.target.matches('[data-item-amount]')) {
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

    updateTotal();
};

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTransactionItems, { once: true });
    } else {
        initializeTransactionItems();
    }
}
