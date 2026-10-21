import { createWorker } from 'tesseract.js';
import { setRupiahInputValue } from './money-currency.js';

const monthNumbers = new Map([
    ['jan', '01'], ['januari', '01'], ['january', '01'],
    ['feb', '02'], ['februari', '02'], ['february', '02'],
    ['mar', '03'], ['maret', '03'], ['march', '03'],
    ['apr', '04'], ['april', '04'],
    ['mei', '05'], ['may', '05'],
    ['jun', '06'], ['juni', '06'], ['june', '06'],
    ['jul', '07'], ['juli', '07'], ['july', '07'],
    ['agu', '08'], ['agt', '08'], ['agustus', '08'], ['aug', '08'], ['august', '08'],
    ['sep', '09'], ['sept', '09'], ['september', '09'],
    ['okt', '10'], ['oktober', '10'], ['oct', '10'], ['october', '10'],
    ['nov', '11'], ['november', '11'],
    ['des', '12'], ['desember', '12'], ['dec', '12'], ['december', '12'],
]);

const makeDate = (yearValue, monthValue, dayValue) => {
    const year = String(yearValue).length === 2
        ? (Number(yearValue) < 70 ? `20${yearValue}` : `19${yearValue}`)
        : String(yearValue);
    const month = String(monthValue).padStart(2, '0');
    const day = String(dayValue).padStart(2, '0');
    const date = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day)));

    return date.getUTCFullYear() === Number(year)
        && date.getUTCMonth() === Number(month) - 1
        && date.getUTCDate() === Number(day)
        ? `${year}-${month}-${day}`
        : null;
};

const extractDate = text => {
    const numeric = text.match(/\b(20\d{2})[/.-](0?[1-9]|1[0-2])[/.-](0?[1-9]|[12]\d|3[01])\b|\b(0?[1-9]|[12]\d|3[01])[/.-](0?[1-9]|1[0-2])[/.-](\d{2,4})\b/u);
    if (numeric) {
        return numeric[1]
            ? makeDate(numeric[1], numeric[2], numeric[3])
            : makeDate(numeric[6], numeric[5], numeric[4]);
    }

    const monthNames = [...monthNumbers.keys()].sort((a, b) => b.length - a.length).join('|');
    const named = text.match(new RegExp(`\\b(\\d{1,2})\\s+(${monthNames})\\.?\\s*,?\\s+(\\d{2,4})\\b`, 'iu'));
    if (!named) return null;

    return makeDate(named[3], monthNumbers.get(named[2].toLocaleLowerCase('id')), named[1]);
};

const normalizeAmount = (number, suffix = '') => {
    const lastSeparator = Math.max(number.lastIndexOf('.'), number.lastIndexOf(','));
    if (lastSeparator === -1) {
        const amount = /^\d+$/u.test(number) ? Number(number) : null;
        if (amount === null) return null;
        return suffix.toLocaleLowerCase('id') === 'k' ? amount * 1000 : amount;
    }

    const decimalDigits = number.length - lastSeparator - 1;
    const hasDecimal = suffix.toLocaleLowerCase('id') === 'k'
        ? decimalDigits > 0 && decimalDigits <= 2
        : decimalDigits === 2;
    let normalized = number.replace(/[.,]/g, '');
    if (hasDecimal) {
        normalized = `${normalized.slice(0, -decimalDigits)}.${normalized.slice(-decimalDigits)}`;
    }

    const amount = Number(normalized);
    if (!Number.isFinite(amount)) return null;

    return suffix.toLocaleLowerCase('id') === 'k' ? amount * 1000 : amount;
};

const amountsFromLine = line => {
    const matches = [...line.matchAll(/(?:Rp\.?\s*)?((?:\d{1,3}(?:[.,]\d{3})+|\d+)(?:[.,]\d{1,2})?)\s*([kK])?/giu)];
    return matches
        .map(match => normalizeAmount(match[1], match[2]))
        .filter(amount => amount !== null && amount > 0 && amount <= 9999999999);
};

const ignoredAmountLine = /diskon|discount|hemat|potongan|subtotal|pajak|ppn|kode\s*(?:pembayaran|transaksi)|id\s*(?:pembayaran|transaksi)|(?:waktu|tanggal)\s*(?:pembayaran|transaksi)|payment\s*id|transaction\s*id/u;

const totalScore = line => {
    if (ignoredAmountLine.test(line)) return 0;
    if (/grand\s*total|total\s*(?:pembayaran|payment|belanja|tagihan|bayar)|amount\s*due|total\s*due/u.test(line)) return 12;
    if (/jumlah\s*(?:pembayaran|dibayar)|(?:nominal|amount)\s*(?:pembayaran|paid)|tagihan|dibayar|bayar/u.test(line)) return 8;
    if (/\btotal\b/u.test(line)) return 6;
    return 0;
};

const extractTotal = lines => {
    const candidates = [];
    const fallback = [];

    lines.forEach((line, index) => {
        const normalizedLine = line.toLocaleLowerCase('id').replace(/\s+/gu, ' ');
        if (ignoredAmountLine.test(normalizedLine)) return;

        const score = totalScore(normalizedLine);
        const amounts = amountsFromLine(line);
        if (score && amounts.length) candidates.push({ score, index, amount: amounts.at(-1) });

        // Some OCR layouts put the label and amount on adjacent lines.
        if (score && !amounts.length) {
            for (const nextLine of lines.slice(index + 1, index + 3)) {
                const nextNormalized = nextLine.toLocaleLowerCase('id');
                if (ignoredAmountLine.test(nextNormalized)) break;
                const nextAmounts = amountsFromLine(nextLine);
                if (nextAmounts.length) {
                    candidates.push({ score: score - 1, index, amount: nextAmounts.at(-1) });
                    break;
                }
            }
        }

        // For unlabeled receipts, only trust currency-marked lines, never arbitrary IDs/times.
        if (/rp\.?\s*/iu.test(line) && amounts.length) fallback.push(...amounts);
    });

    if (candidates.length) {
        candidates.sort((left, right) => right.score - left.score || right.index - left.index);
        return candidates[0].amount;
    }

    return fallback.length ? Math.max(...fallback) : null;
};

const isDigitalPaymentReceipt = text => /total\s*pembayaran|metode\s*pembayaran|id\s*pembayaran|waktu\s*pembayaran|payment\s*id/iu.test(text);

export const extractReceiptFields = text => {
    const lines = text.split(/\r?\n/u).map(line => line.trim()).filter(Boolean);
    const digitalPayment = isDigitalPaymentReceipt(text);
    const ignoredTerms = [
        'struk', 'receipt', 'invoice', 'nota', 'tanggal', 'date', 'cashier', 'kasir', 'telp', 'phone', 'npwp',
        'berhasil', 'rincian pembayaran', 'total pembayaran', 'jumlah diskon', 'metode pembayaran',
        'waktu pembayaran', 'id pembayaran', 'bayar instan', 'lihat pinjaman', 'sea bank', 'seabank',
    ];
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
        category: digitalPayment ? 'Pembayaran digital' : category,
        amount: extractTotal(lines),
        payment_method: digitalPayment ? 'online' : null,
    };
    const warnings = [];
    if (!fields.tanggal) warnings.push('Tanggal tidak terdeteksi; silakan isi secara manual.');
    if (digitalPayment) warnings.push('Bukti ini tidak menyebutkan barang atau tujuan pembayaran; sesuaikan kategori transaksi.');
    else if (!fields.category) warnings.push('Nama toko tidak terdeteksi; silakan isi kategori secara manual.');
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
            if (fields.payment_method) transactionForm.elements.payment_method.value = fields.payment_method;
            const amountInput = transactionForm.querySelector('[data-grand-total]');
            if (fields.amount !== null && amountInput instanceof HTMLInputElement) {
                setRupiahInputValue(amountInput, fields.amount);
            }
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
