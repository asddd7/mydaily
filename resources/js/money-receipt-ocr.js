import { createWorker } from 'tesseract.js';

const extractDate = text => {
    const patterns = [
        /\b(20\d{2})[/.-](0?[1-9]|1[0-2])[/.-](0?[1-9]|[12]\d|3[01])\b/,
        /\b(0?[1-9]|[12]\d|3[01])[/.-](0?[1-9]|1[0-2])[/.-](\d{2,4})\b/,
    ];

    for (const [index, pattern] of patterns.entries()) {
        const match = text.match(pattern);
        if (!match) continue;

        let year;
        let month;
        let day;
        if (index === 0) {
            [, year, month, day] = match;
        } else {
            [, day, month, year] = match;
            if (year.length === 2) year = Number(year) < 70 ? `20${year}` : `19${year}`;
        }

        const date = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day)));
        if (
            date.getUTCFullYear() === Number(year)
            && date.getUTCMonth() === Number(month) - 1
            && date.getUTCDate() === Number(day)
        ) {
            return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        }
    }

    return null;
};

const normalizeAmount = number => {
    const lastSeparator = Math.max(number.lastIndexOf('.'), number.lastIndexOf(','));
    if (lastSeparator === -1) return /^\d+$/.test(number) ? Number(number) : null;

    const hasDecimal = number.length - lastSeparator - 1 === 2;
    let normalized = number.replace(/[.,]/g, '');
    if (hasDecimal) normalized = `${normalized.slice(0, -2)}.${normalized.slice(-2)}`;

    const amount = Number(normalized);
    return Number.isFinite(amount) ? amount : null;
};

const amountsFromLine = line => {
    const matches = [...line.matchAll(/(?:Rp\.?\s*)?((?:\d{1,3}(?:[.,]\d{3})+|\d+)(?:[.,]\d{2})?)/giu)];
    return matches
        .map(match => normalizeAmount(match[1]))
        .filter(amount => amount !== null && amount > 0 && amount <= 9999999999);
};

const extractTotal = lines => {
    const prioritizedAmounts = [];
    const fallbackAmounts = [];

    lines.forEach((line, index) => {
        const amounts = amountsFromLine(line);
        if (!amounts.length) return;
        fallbackAmounts.push(...amounts);

        const lowerLine = line.toLocaleLowerCase('id');
        if (lowerLine.includes('subtotal')) return;

        const score = /grand\s*total|total\s*bayar|amount\s*due|total\s*due/u.test(lowerLine)
            ? 4
            : /total|jumlah|dibayar|bayar|tagihan/u.test(lowerLine) ? 3 : 0;
        if (score) prioritizedAmounts.push({ score, index, amount: amounts.at(-1) });
    });

    if (prioritizedAmounts.length) {
        prioritizedAmounts.sort((left, right) => right.score - left.score || right.index - left.index);
        return prioritizedAmounts[0].amount;
    }

    return fallbackAmounts.length ? Math.max(...fallbackAmounts) : null;
};

export const extractReceiptFields = text => {
    const lines = text.split(/\r?\n/u).map(line => line.trim()).filter(Boolean);
    const ignoredTerms = ['struk', 'receipt', 'invoice', 'nota', 'tanggal', 'date', 'cashier', 'kasir', 'telp', 'phone', 'npwp'];
    const category = lines
        .slice(0, 8)
        .map(line => line.replace(/\s+/gu, ' ').trim())
        .find(line => {
            const lowerLine = line.toLocaleLowerCase('id');
            return line.length >= 3
                && line.length <= 100
                && /\p{L}/u.test(line)
                && !ignoredTerms.some(term => lowerLine.includes(term))
                && !/^\d{1,2}[/.-]\d{1,2}[/.-]\d{2,4}$/u.test(line)
                && !/^(?:rp\.?\s*)?[\d.,\s]+$/iu.test(line);
        }) ?? null;

    const fields = {
        tanggal: extractDate(text),
        category,
        amount: extractTotal(lines),
    };
    const warnings = [];
    if (!fields.tanggal) warnings.push('Tanggal tidak terdeteksi; silakan isi secara manual.');
    if (!fields.category) warnings.push('Nama toko tidak terdeteksi; silakan isi kategori secara manual.');
    if (fields.amount === null) warnings.push('Total transaksi tidak terdeteksi; silakan isi nominal secara manual.');

    return { ...fields, warnings };
};

const initializeReceiptScanner = () => {
    const scanner = document.querySelector('[data-receipt-scan]');
    const dialog = document.querySelector('[data-money-dialog]');
    const transactionForm = document.getElementById('moneyCreateForm');
    if (!scanner || !dialog || !(transactionForm instanceof HTMLFormElement)) return;

    const fileInput = scanner.querySelector('input[type="file"]');
    const scanButton = scanner.querySelector('[data-scan-submit]');
    const scanStatus = scanner.querySelector('[data-scan-status]');
    const ocrTextContainer = scanner.querySelector('[data-ocr-text-container]');
    const ocrText = scanner.querySelector('[data-ocr-text]');
    if (
        !(fileInput instanceof HTMLInputElement)
        || !(scanButton instanceof HTMLButtonElement)
        || !(scanStatus instanceof HTMLElement)
        || !(ocrTextContainer instanceof HTMLDetailsElement)
        || !(ocrText instanceof HTMLElement)
    ) return;

    scanButton.addEventListener('click', async () => {
        const [image] = fileInput.files ?? [];
        ocrTextContainer.hidden = true;
        ocrText.textContent = '';

        if (!image) {
            scanStatus.textContent = 'Pilih foto struk terlebih dahulu.';
            fileInput.focus();
            return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(image.type)) {
            scanStatus.textContent = 'Format foto harus JPG, PNG, atau WebP.';
            return;
        }
        if (image.size > 10 * 1024 * 1024) {
            scanStatus.textContent = 'Ukuran foto maksimal 10 MB.';
            return;
        }

        scanButton.disabled = true;
        scanStatus.textContent = 'Menyiapkan OCR...';
        let worker;

        try {
            worker = await createWorker('ind+eng', 1, {
                logger: message => {
                    if (message.status === 'recognizing text' && Number.isFinite(message.progress)) {
                        scanStatus.textContent = `Membaca struk... ${Math.round(message.progress * 100)}%`;
                    } else if (message.status) {
                        scanStatus.textContent = 'Menyiapkan data OCR untuk pertama kali...';
                    }
                },
            });

            const result = await worker.recognize(image);
            const text = result.data.text.trim();
            if (!text) {
                scanStatus.textContent = 'Teks pada foto tidak terbaca. Coba foto yang lebih jelas.';
                return;
            }

            const fields = extractReceiptFields(text);
            if (fields.tanggal) transactionForm.elements.tanggal.value = fields.tanggal;
            if (fields.category) transactionForm.elements.category.value = fields.category;
            if (fields.amount !== null) transactionForm.elements.amount.value = fields.amount.toFixed(2);
            transactionForm.elements.type.value = 'expense';

            ocrText.textContent = text;
            ocrTextContainer.hidden = false;
            scanStatus.textContent = fields.warnings.length
                ? `Teks berhasil dibaca. ${fields.warnings.join(' ')}`
                : 'Struk terbaca. Periksa kembali saran transaksi sebelum disimpan.';
            transactionForm.elements.category.focus();
        } catch (error) {
            console.error('Browser receipt OCR failed.', error);
            scanStatus.textContent = 'OCR gagal dimuat atau dijalankan. Periksa koneksi internet lalu coba lagi.';
        } finally {
            try {
                if (worker) await worker.terminate();
            } catch (error) {
                console.error('Could not terminate the receipt OCR worker.', error);
            } finally {
                scanButton.disabled = false;
            }
        }
    });
};

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeReceiptScanner, { once: true });
    } else {
        initializeReceiptScanner();
    }
}
