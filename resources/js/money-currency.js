const currencyFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

export const normalizeRupiahAmount = value => {
    let amount = String(value ?? '').trim().replace(/[^0-9.,]/gu, '');
    if (!amount) return '';

    const usesThousandsSuffix = /k$/iu.test(String(value ?? '').trim());
    if (amount.includes(',')) {
        amount = amount.replace(/\./gu, '').replace(',', '.');
    } else if (/^\d{1,3}(?:\.\d{3})+$/u.test(amount)) {
        amount = amount.replace(/\./gu, '');
    }

    const parsed = Number(amount);
    if (!Number.isFinite(parsed)) return '';

    return (usesThousandsSuffix ? parsed * 1000 : parsed).toFixed(2);
};

const formatRupiah = value => {
    const amount = Number(value);
    return Number.isFinite(amount) ? currencyFormatter.format(amount) : '';
};

export const setRupiahInputValue = (displayInput, amount) => {
    const valueInput = displayInput.parentElement?.querySelector('[data-currency-value]');
    if (!(valueInput instanceof HTMLInputElement)) return;

    const normalized = normalizeRupiahAmount(amount);
    valueInput.value = normalized;
    displayInput.value = normalized ? formatRupiah(normalized) : '';
};

export const initializeRupiahInput = displayInput => {
    if (displayInput.dataset.currencyReady === 'true') return;

    const valueInput = displayInput.parentElement?.querySelector('[data-currency-value]');
    if (!(valueInput instanceof HTMLInputElement)) return;

    displayInput.dataset.currencyReady = 'true';
    if (!valueInput.value && displayInput.value) {
        valueInput.value = normalizeRupiahAmount(displayInput.value);
    }
    displayInput.value = valueInput.value ? formatRupiah(valueInput.value) : '';

    displayInput.addEventListener('focus', () => {
        displayInput.value = valueInput.value;
        displayInput.select();
    });

    displayInput.addEventListener('input', () => {
        valueInput.value = normalizeRupiahAmount(displayInput.value);
    });

    displayInput.addEventListener('blur', () => {
        displayInput.value = valueInput.value ? formatRupiah(valueInput.value) : '';
    });
};

export const initializeRupiahInputs = (root = document) => {
    root.querySelectorAll('[data-currency-input]').forEach(input => {
        if (input instanceof HTMLInputElement) initializeRupiahInput(input);
    });
};

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initializeRupiahInputs(), { once: true });
    } else {
        initializeRupiahInputs();
    }
}
