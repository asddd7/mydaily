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




const extractReceiptItems = lines => {
    const items = [];

    const ignoredLine =
        /grand\s*total|subtotal|total(?:\s*pembayaran|\s*payment|\s*belanja|\s*tagihan|\s*bayar)?|diskon|discount|hemat|potongan|pajak|ppn|kembalian|uang\s*(?:tunai|diterima)|metode\s*pembayaran|payment\s*method|nominal\s*(?:pembayaran|paid)|dibayar|amount\s*due|amount\s*paid|kode\s*(?:pembayaran|transaksi)|id\s*(?:pembayaran|transaksi)|tanggal|waktu\s*(?:pembayaran|transaksi)|cashier|kasir|alamat|telepon|telp|terima\s*kasih/iu;

    const amountPattern =
        /(?:Rp\.?\s*)?((?:\d{1,3}(?:[.,]\d{3})+|\d+)(?:[.,]\d{1,2})?)\s*([kK])?/giu;

    const normalizedLines = lines.map(line =>
        line.replace(/\s+/gu, ' ').trim()
    );

    const isAmountOnly = line =>
        /^(?:Rp\.?\s*)?(?:\d{1,3}(?:[.,]\d{3})+|\d+)(?:[.,]\d{1,2})?\s*[kK]?$/iu.test(
            line.trim()
        );

    const cleanName = value => value
        .replace(/^\d{1,3}\s*[.)-]\s*/u, '')
        .replace(/\s+\d+\s*(?:pcs?|buah|btl|botol|pack|pak|kg|gram|gr|ml|liter|ltr)\s*$/iu, '')
        .replace(/\s*[x×]\s*\d+\s*$/iu, '')
        .replace(/\s+\d+\s*[x×]\s*$/iu, '')
        .replace(/[\s:|,;=\-]+$/u, '')
        .trim();

    const addItem = (name, rawAmount, suffix = '', options = {}) => {
        const cleanedName = cleanName(name);
        const amount = normalizeAmount(rawAmount, suffix);

        if (
            cleanedName.length < 2 ||
            cleanedName.length > 150 ||
            !/\p{L}/u.test(cleanedName) ||
            ignoredLine.test(cleanedName) ||
            amount === null ||
            amount <= 0 ||
            amount > 9999999999
        ) {
            return;
        }

        // Nominal kecil tanpa penanda mata uang atau pemisah
        // sering merupakan jumlah barang, bukan harga.
        if (
            !options.allowSmallAmount &&
            !options.hasCurrency &&
            !options.hasSeparator &&
            !suffix &&
            amount < 1000
        ) {
            return;
        }

        const normalizedName = cleanedName.toLocaleLowerCase('id');
        const roundedAmount = Math.round(amount * 100) / 100;

        const duplicate = items.some(item =>
            item.name.toLocaleLowerCase('id') === normalizedName &&
            item.amount === roundedAmount
        );

        if (!duplicate) {
            items.push({
                name: cleanedName,
                amount: roundedAmount,
            });
        }
    };

    for (let index = 0; index < normalizedLines.length; index++) {
        const line = normalizedLines[index];

        if (!line || ignoredLine.test(line)) continue;

        // Format: "Nama barang 3.000" atau "2. Nama barang 3.000".
        const matches = [...line.matchAll(amountPattern)];

        if (matches.length > 0) {
            const match = matches[matches.length - 1];
            const name = line.slice(0, match.index).trim();

            if (name.length >= 2 && /\p{L}/u.test(name)) {
                const rawAmount = match[1];
                const suffix = match[2] ?? '';
                const hasCurrency = /Rp\.?\s*/iu.test(
                    match[0].slice(0, match[0].indexOf(rawAmount))
                );
                const hasSeparator = /[.,]/u.test(rawAmount);

                addItem(name, rawAmount, suffix, {
                    hasCurrency,
                    hasSeparator,
                });

                continue;
            }
        }

        // Format: nama barang di satu baris, harga di baris selanjutnya.
        if (isAmountOnly(line)) {
            const previousLine = normalizedLines[index - 1];

            if (
                !previousLine ||
                ignoredLine.test(previousLine) ||
                isAmountOnly(previousLine)
            ) {
                continue;
            }

            const previousMatches = [
                ...previousLine.matchAll(amountPattern),
            ];

            // Jangan memasangkan harga dengan baris yang sudah
            // memiliki angka/harga sendiri.
            if (previousMatches.length > 0) continue;

            const priceMatch = line.match(
                /^(?:Rp\.?\s*)?((?:\d{1,3}(?:[.,]\d{3})+|\d+)(?:[.,]\d{1,2})?)\s*([kK])?$/iu
            );

            if (!priceMatch) continue;

            const rawAmount = priceMatch[1];
            const suffix = priceMatch[2] ?? '';

            addItem(previousLine, rawAmount, suffix, {
                hasCurrency: /^Rp/iu.test(line),
                hasSeparator: /[.,]/u.test(rawAmount),
            });
        }
    }

    return items;
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
        items: extractReceiptItems(lines),
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
    const transactionForm = document.getElementById('moneyCreateForm');

    if (!scanner || !(transactionForm instanceof HTMLFormElement)) {
        return;
    }

    const fileInput = scanner.querySelector('input[type="file"]');
    const scanButton = scanner.querySelector('[data-scan-submit]');
    const scanStatus = scanner.querySelector('[data-scan-status]');
    const selectedFilesDisplay = scanner.querySelector('[data-receipt-selected]');
    const clearFilesButton = scanner.querySelector('[data-clear-receipt-images]');
    const ocrTextContainer = scanner.querySelector('[data-ocr-text-container]');
    const ocrText = scanner.querySelector('[data-ocr-text]');

    if (
        !(fileInput instanceof HTMLInputElement) ||
        !(scanButton instanceof HTMLButtonElement) ||
        !(scanStatus instanceof HTMLElement) ||
        !(selectedFilesDisplay instanceof HTMLElement) ||
        !(clearFilesButton instanceof HTMLButtonElement) ||
        !(ocrTextContainer instanceof HTMLDetailsElement) ||
        !(ocrText instanceof HTMLElement)
    ) {
        return;
    }

    // Some mobile pickers replace the previous selection on each open. Keep
    // selections across opens so users can add receipt photos in batches.
    const selectedImages = new Map();
    const fileKey = file => `${file.name}:${file.size}:${file.lastModified}`;
    const renderSelectedImages = () => {
        const files = [...selectedImages.values()];
        selectedFilesDisplay.textContent = files.length
            ? `${files.length} foto dipilih: ${files.map(file => file.name).join(', ')}`
            : 'Belum ada foto dipilih.';
        clearFilesButton.disabled = files.length === 0;
    };

    fileInput.addEventListener('change', () => {
        Array.from(fileInput.files ?? []).forEach(file => {
            selectedImages.set(fileKey(file), file);
        });

        try {
            const transfer = new DataTransfer();
            selectedImages.forEach(file => transfer.items.add(file));
            fileInput.files = transfer.files;
            renderSelectedImages();
            scanStatus.textContent = `${selectedImages.size} foto siap dipindai.`;
        } catch (error) {
            console.error('Tidak dapat menggabungkan pilihan foto.', error);
            renderSelectedImages();
            scanStatus.textContent =
                'Browser tidak mendukung penggabungan pilihan. Pilih semua foto sekaligus.';
        }
    });

    clearFilesButton.addEventListener('click', () => {
        selectedImages.clear();
        fileInput.value = '';
        renderSelectedImages();
        scanStatus.textContent = 'Pilihan foto dihapus.';
    });

    scanButton.addEventListener('click', async () => {
        const images = Array.from(fileInput.files ?? []);

        ocrTextContainer.hidden = true;
        ocrText.textContent = '';

        if (images.length === 0) {
            scanStatus.textContent = 'Pilih minimal satu foto struk.';
            fileInput.focus();
            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        const invalidType = images.find(image =>
            !allowedTypes.includes(image.type)
        );

        if (invalidType) {
            scanStatus.textContent =
                `Format ${invalidType.name} tidak didukung. ` +
                'Gunakan JPG, PNG, atau WebP.';
            return;
        }

        const oversizedImage = images.find(
            image => image.size > 10 * 1024 * 1024
        );

        if (oversizedImage) {
            scanStatus.textContent =
                `Ukuran ${oversizedImage.name} melebihi batas 10 MB.`;
            return;
        }

        scanButton.disabled = true;
        fileInput.disabled = true;
        scanStatus.textContent =
            `Menyiapkan pemindaian ${images.length} foto...`;

        let worker;
        let currentPhotoNumber = 1;

        try {
            worker = await createWorker('ind+eng', 1, {
                logger: message => {
                    if (
                        message.status === 'recognizing text' &&
                        Number.isFinite(message.progress)
                    ) {
                        scanStatus.textContent =
                            `Membaca foto ${currentPhotoNumber} dari ` +
                            `${images.length}... ` +
                            `${Math.round(message.progress * 100)}%`;
                    } else if (message.status) {
                        scanStatus.textContent =
                            `Menyiapkan OCR untuk foto ` +
                            `${currentPhotoNumber} dari ${images.length}...`;
                    }
                },
            });

            const scannedPhotos = [];

            // Proses foto satu per satu menggunakan worker yang sama.
            for (let index = 0; index < images.length; index++) {
                const image = images[index];
                currentPhotoNumber = index + 1;

                scanStatus.textContent =
                    `Membaca foto ${currentPhotoNumber} dari ${images.length}: ` +
                    image.name;

                try {
                    const result = await worker.recognize(image);
                    const text = result.data.text.trim();

                    scannedPhotos.push({
                        name: image.name,
                        text,
                        fields: text ? extractReceiptFields(text) : null,
                    });
                } catch (error) {
                    console.error(
                        `Gagal membaca foto ${image.name}.`,
                        error
                    );

                    scannedPhotos.push({
                        name: image.name,
                        text: '',
                        fields: null,
                        failed: true,
                    });
                }
            }

            const readablePhotos = scannedPhotos.filter(
                photo => photo.fields !== null
            );

            if (readablePhotos.length === 0) {
                ocrText.textContent = scannedPhotos
                    .map(photo =>
                        `=== ${photo.name} ===\nTeks tidak berhasil dibaca.`
                    )
                    .join('\n\n');

                ocrTextContainer.hidden = false;
                scanStatus.textContent =
                    'Tidak ada foto yang berhasil dibaca. ' +
                    'Coba gunakan foto yang lebih jelas.';
                return;
            }

            // Ambil nilai pertama yang berhasil terdeteksi.
            const firstDetected = fieldName =>
                readablePhotos.find(photo =>
                    photo.fields[fieldName] !== null &&
                    photo.fields[fieldName] !== undefined
                )?.fields[fieldName] ?? null;

            const detectedDate = firstDetected('tanggal');
            const detectedCategory = firstDetected('category');
            const detectedPaymentMethod = firstDetected('payment_method');
            const detectedItems = readablePhotos.flatMap(
                photo => photo.fields.items ?? []
            );

            // Gabungkan nominal dari setiap foto yang mempunyai total.
            // Penjumlahan dalam satuan sen untuk mengurangi masalah desimal.
            const photosWithAmount = readablePhotos.filter(
                photo => photo.fields.amount !== null
            );

            const totalCents = photosWithAmount.reduce(
                (sum, photo) =>
                    sum + Math.round(photo.fields.amount * 100),
                0
            );

            if (detectedDate) {
                transactionForm.elements.tanggal.value = detectedDate;
            }

            if (detectedCategory) {
                transactionForm.elements.category.value = detectedCategory;
            }

            if (detectedPaymentMethod) {
                transactionForm.elements.payment_method.value =
                    detectedPaymentMethod;
            }

            // Semua foto struk dicatat sebagai satu pengeluaran.
            transactionForm.elements.type.value = 'expense';


            // Kirim kandidat hasil OCR sebagai saran pencarian.
            // Tidak ada barang yang otomatis dimasukkan ke transaksi.
            transactionForm.dispatchEvent(
                new CustomEvent('money:scan-items', {
                    detail: detectedItems,
                })
            );


            const amountInput =
                transactionForm.querySelector('[data-grand-total]');


            if (
                transactionForm.querySelectorAll('[data-item-row]').length === 0 &&
                photosWithAmount.length > 0 &&
                amountInput instanceof HTMLInputElement
            ) {
                const totalAmount = totalCents / 100;

                if (amountInput.type === 'number') {
                    amountInput.value = String(totalAmount);
                    amountInput.dispatchEvent(
                        new Event('input', { bubbles: true })
                    );
                    amountInput.dispatchEvent(
                        new Event('change', { bubbles: true })
                    );
                } else {
                    setRupiahInputValue(amountInput, totalAmount);
                }
            }


            // Tampilkan teks OCR dari setiap foto agar bisa diperiksa.
            ocrText.textContent = scannedPhotos
                .map(photo =>
                    `=== ${photo.name} ===\n` +
                    (photo.text || 'Teks tidak berhasil dibaca.')
                )
                .join('\n\n');

            ocrTextContainer.hidden = false;

            const warnings = [];

            const unreadablePhotos = scannedPhotos.filter(
                photo => photo.fields === null
            );

            if (unreadablePhotos.length > 0) {
                warnings.push(
                    `${unreadablePhotos.length} foto tidak berhasil dibaca.`
                );
            }

            const photosWithoutAmount = readablePhotos.filter(
                photo => photo.fields.amount === null
            );

            if (photosWithoutAmount.length > 0) {
                warnings.push(
                    `Nominal tidak terdeteksi pada ` +
                    `${photosWithoutAmount.length} foto. Total hanya ` +
                    `menjumlahkan ${photosWithAmount.length} foto ` +
                    `yang nominalnya terbaca.`
                );
            }

            if (!detectedDate) {
                warnings.push(
                    'Tanggal tidak terdeteksi; isi tanggal secara manual.'
                );
            }

            if (!detectedCategory) {
                warnings.push(
                    'Kategori tidak terdeteksi; isi kategori secara manual.'
                );
            }

            if (photosWithAmount.length > 0) {
                const formattedTotal = (totalCents / 100).toLocaleString(
                    'id-ID',
                    {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2,
                    }
                );

                const amountSummary = photosWithAmount
                    .map(photo => `${photo.name}: Rp ${photo.fields.amount.toLocaleString('id-ID')}`)
                    .join(' | ');

                scanStatus.textContent =
                    `Selesai membaca ${images.length} foto. ${amountSummary}. ` +
                    `Grand total ${photosWithAmount.length} foto: Rp ${formattedTotal}. ` +
                    (warnings.length
                        ? warnings.join(' ')
                        : 'Periksa kembali data sebelum disimpan.');
            } else {
                scanStatus.textContent =
                    'Foto berhasil diproses, tetapi nominal tidak terdeteksi. ' +
                    'Isi nominal transaksi secara manual. ' +
                    warnings.join(' ');
            }

            transactionForm.elements.category.focus();
        } catch (error) {
            console.error('Browser receipt OCR failed.', error);

            scanStatus.textContent =
                'OCR gagal dijalankan. Periksa koneksi internet, ' +
                'lalu coba lagi.';
        } finally {
            try {
                if (worker) {
                    await worker.terminate();
                }
            } catch (error) {
                console.error(
                    'Tidak dapat menghentikan worker OCR.',
                    error
                );
            }

            scanButton.disabled = false;
            fileInput.disabled = false;
        }
    });
};

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initializeReceiptScanner,
            { once: true }
        );
    } else {
        initializeReceiptScanner();
    }
}
