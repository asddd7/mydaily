<section class="card calendar-widget" data-calendar-widget>
    <div class="calendar-header">
        <button type="button" data-month-shift="-1" aria-label="Bulan sebelumnya">❮</button>
        <h2 data-month-label aria-live="polite"></h2>
        <button type="button" data-month-shift="1" aria-label="Bulan berikutnya">❯</button>
    </div>

    <div data-calendar-status class="calendar-status" role="status" aria-live="polite"></div>

    <div class="table-responsive">
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th>
                    <th>Kam</th><th>Jum</th><th>Sab</th>
                </tr>
            </thead>
            <tbody data-calendar-days></tbody>
        </table>
    </div>

    <div class="calendar-marks">
        <h3>📌 Penanda dan tugas bulan ini</h3>
        <ul data-calendar-list></ul>
    </div>

    <dialog data-calendar-dialog class="calendar-dialog">
        <form data-calendar-form>
            <h3>Tambah penanda</h3>

            <label class="form-label" for="calendar-mark-date">Tanggal</label>
            <input type="date" name="tanggal" id="calendar-mark-date" required>

            <label class="form-label" for="calendar-mark-title">Judul</label>
            <input type="text" name="title" id="calendar-mark-title" maxlength="255" required>

            <label class="form-label" for="calendar-mark-description">Catatan</label>
            <textarea name="description" id="calendar-mark-description" rows="3"></textarea>

            <div class="calendar-dialog-actions">
                <button type="submit" class="download-btn">Simpan</button>
                <button type="button" data-calendar-close class="close">Batal</button>
            </div>
        </form>
    </dialog>
</section>

@push('scripts')
<script>
(() => {
    const widget = document.querySelector('[data-calendar-widget]');
    if (!widget) return;

    const body = widget.querySelector('[data-calendar-days]');
    const title = widget.querySelector('[data-month-label]');
    const list = widget.querySelector('[data-calendar-list]');
    const status = widget.querySelector('[data-calendar-status]');
    const dialog = widget.querySelector('[data-calendar-dialog]');
    const form = widget.querySelector('[data-calendar-form]');
    const csrf = @json(csrf_token());

    const endpoints = {
        marks: @json(route('calendar.marks')),
        holidays: @json(route('calendar.holidays')),
        tasks: @json(route('calendar.task-marks')),
        store: @json(route('calendar.store')),
        toggle: @json(route('calendar.toggle', ['mark' => '__id__'])),
        remove: @json(route('calendar.destroy', ['mark' => '__id__']))
    };

    let current = new Date();
    current = new Date(current.getFullYear(), current.getMonth(), 1);
    let requestVersion = 0;

    // Cache selama halaman dashboard masih terbuka.
    // Ini membuat navigasi antar-bulan tidak mengulang request yang sama.
    const cache = {
        marks: new Map(),
        holidays: new Map(),
        tasks: null
    };

    const monthKey = (year, month) =>
        `${year}-${String(month + 1).padStart(2, '0')}`;

    const toDateString = date =>
        `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, {
            cache: 'no-store',
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {})
            }
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            throw new Error(payload?.message || `Permintaan gagal (${response.status}).`);
        }

        return response.json();
    };

    const getMarks = async (year, month) => {
        const key = monthKey(year, month);
        if (!cache.marks.has(key)) {
            cache.marks.set(
                key,
                requestJson(`${endpoints.marks}?month=${month + 1}&year=${year}`)
            );
        }
        return cache.marks.get(key);
    };

    const getHolidays = async year => {
        if (!cache.holidays.has(year)) {
            cache.holidays.set(
                year,
                requestJson(`${endpoints.holidays}?year=${year}`)
            );
        }
        return cache.holidays.get(year);
    };

    const getTasks = async () => {
        if (!cache.tasks) {
            cache.tasks = requestJson(endpoints.tasks);
        }
        return cache.tasks;
    };

    const addText = (parent, tag, text, className) => {
        const element = document.createElement(tag);
        element.textContent = text;
        if (className) element.className = className;
        parent.appendChild(element);
        return element;
    };

    const createGrid = (year, month) => {
        body.replaceChildren();

        const fragment = document.createDocumentFragment();
        const firstWeekday = new Date(year, month, 1).getDay();
        const dayCount = new Date(year, month + 1, 0).getDate();
        const today = toDateString(new Date());
        let row = document.createElement('tr');

        for (let blank = 0; blank < firstWeekday; blank++) {
            row.appendChild(document.createElement('td'));
        }

        for (let day = 1; day <= dayCount; day++) {
            const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const cell = document.createElement('td');
            cell.dataset.date = date;
            cell.tabIndex = 0;
            cell.setAttribute('role', 'button');
            cell.setAttribute('aria-label', date);

            addText(cell, 'span', String(day), 'calendar-day-number');

            if (date === today) {
                cell.classList.add('today');
            }

            if (new Date(year, month, day).getDay() === 0) {
                cell.classList.add('weekend');
            }

            const open = () => openForDate(date);
            cell.addEventListener('click', open);
            cell.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    open();
                }
            });

            row.appendChild(cell);

            if ((firstWeekday + day) % 7 === 0 || day === dayCount) {
                fragment.appendChild(row);
                row = document.createElement('tr');
            }
        }

        body.appendChild(fragment);
    };

    const groupByDate = items => {
        const map = new Map();
        items.forEach(item => {
            if (!item?.tanggal) return;
            const entries = map.get(item.tanggal) || [];
            entries.push(item);
            map.set(item.tanggal, entries);
        });
        return map;
    };

    const addMarkEntry = (mark, holiday = false) => {
        const item = document.createElement('li');

        if (holiday) item.classList.add('holiday-item');
        if (mark.selesai) item.classList.add('done');

        const text = document.createElement('div');
        text.className = 'mark-text';

        const date = document.createElement('strong');
        date.textContent = mark.tanggal || '';
        text.appendChild(date);

        const label = mark.title || mark.keterangan || '';

        if (mark.url) {
            const link = document.createElement('a');
            link.href = mark.url;
            link.textContent = ` ${label}`;
            link.target = '_self';
            text.appendChild(link);
        } else {
            text.appendChild(document.createTextNode(` ${label}`));
        }

        if (mark.description) {
            text.appendChild(document.createTextNode(` — ${mark.description}`));
        }

        item.appendChild(text);

        if (mark.id) {
            const actions = document.createElement('div');
            actions.className = 'mark-actions';

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'btn-done';
            toggle.textContent = mark.selesai ? 'Belum selesai' : 'Selesai';
            toggle.addEventListener('click', async () => {
                toggle.disabled = true;
                try {
                    await requestJson(endpoints.toggle.replace('__id__', mark.id), {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({ selesai: !mark.selesai })
                    });
                    cache.marks.delete(monthKey(current.getFullYear(), current.getMonth()));
                    await render();
                } catch (error) {
                    status.textContent = error.message;
                    toggle.disabled = false;
                }
            });

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'delete-mark';
            remove.textContent = 'Hapus';
            remove.addEventListener('click', async () => {
                if (!confirm('Yakin ingin menghapus penanda ini?')) return;

                remove.disabled = true;
                try {
                    await requestJson(endpoints.remove.replace('__id__', mark.id), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf }
                    });
                    cache.marks.delete(monthKey(current.getFullYear(), current.getMonth()));
                    await render();
                } catch (error) {
                    status.textContent = error.message;
                    remove.disabled = false;
                }
            });

            actions.append(toggle, remove);
            item.appendChild(actions);
        }

        list.appendChild(item);
        return item;
    };

    const openForDate = date => {
        form.elements.tanggal.value = date;
        dialog.showModal();
        form.elements.title.focus();
    };

    const applyCalendarData = (year, month, marks = [], holidays = [], tasks = []) => {
        const marksByDate = groupByDate(marks);
        const holidaysByDate = new Map(
            holidays
                .filter(item => item?.tanggal)
                .map(item => [item.tanggal, item])
        );

        const currentMonthPrefix = monthKey(year, month);
        const currentMonthTasks = tasks.filter(task =>
            task?.tanggal?.startsWith(currentMonthPrefix)
        );
        const tasksByDate = groupByDate(currentMonthTasks);

        body.querySelectorAll('td[data-date]').forEach(cell => {
            const date = cell.dataset.date;
            const holiday = holidaysByDate.get(date);
            const dayMarks = marksByDate.get(date) || [];
            const dayTasks = tasksByDate.get(date) || [];

            cell.querySelectorAll('.holiday-label').forEach(node => node.remove());
            cell.classList.remove('holiday', 'marked-date', 'done-date');
            cell.setAttribute(
                'aria-label',
                dayMarks.length || dayTasks.length
                    ? `${date}, ${dayMarks.length} penanda, ${dayTasks.length} tugas`
                    : date
            );

            if (holiday) {
                cell.classList.add('holiday');
                cell.title = holiday.keterangan || '';
                addText(cell, 'small', holiday.keterangan || '', 'holiday-label');
            } else {
                cell.removeAttribute('title');
            }

            if (dayMarks.length || dayTasks.length) {
                cell.classList.add('marked-date');

                if (dayMarks.length && dayMarks.every(mark => Number(mark.selesai) === 1)) {
                    cell.classList.add('done-date');
                }
            }
        });

        list.replaceChildren();

        const monthHolidays = holidays.filter(holiday =>
            holiday?.tanggal?.startsWith(currentMonthPrefix)
        );

        if (monthHolidays.length) {
            addText(list, 'li', '🎌 Hari Libur Nasional', 'holiday-item');
        }

        monthHolidays.forEach(holiday => addMarkEntry(holiday, true));
        marks.forEach(mark => addMarkEntry(mark));

        currentMonthTasks.forEach(task => {
            const entry = addMarkEntry({ ...task, id: null });
            entry.classList.add('calendar-task-item');
        });

        if (!monthHolidays.length && !marks.length && !currentMonthTasks.length) {
            addText(list, 'li', 'Tidak ada penanda atau tugas bulan ini.');
        }
    };

    const render = async () => {
        const version = ++requestVersion;
        const year = current.getFullYear();
        const month = current.getMonth();

        title.textContent = current.toLocaleDateString('id-ID', {
            month: 'long',
            year: 'numeric'
        });

        // Render angka tanggal terlebih dahulu. User tidak perlu menunggu API.
        createGrid(year, month);
        list.replaceChildren();
        addText(list, 'li', 'Memuat penanda dan tugas…', 'calendar-loading-item');
        status.textContent = 'Memuat data…';
        widget.setAttribute('aria-busy', 'true');

        try {
            const results = await Promise.allSettled([
                getMarks(year, month),
                getHolidays(year),
                getTasks()
            ]);

            if (version !== requestVersion) return;

            const marksResult = results[0];
            const holidaysResult = results[1];
            const tasksResult = results[2];

            const marks = marksResult.status === 'fulfilled' ? marksResult.value : [];
            const holidays = holidaysResult.status === 'fulfilled' ? holidaysResult.value : [];
            const tasks = tasksResult.status === 'fulfilled' ? tasksResult.value : [];

            applyCalendarData(year, month, marks, holidays, tasks);

            const failed = results.filter(result => result.status === 'rejected');
            status.textContent = failed.length
                ? 'Sebagian data kalender gagal dimuat.'
                : '';

            failed.forEach(result => console.error('Calendar request failed:', result.reason));
        } catch (error) {
            if (version !== requestVersion) return;
            status.textContent = `Kalender gagal dimuat: ${error.message}`;
            console.error(error);
        } finally {
            if (version === requestVersion) {
                widget.removeAttribute('aria-busy');
            }
        }
    };

    widget.querySelectorAll('[data-month-shift]').forEach(button => {
        button.addEventListener('click', () => {
            current = new Date(
                current.getFullYear(),
                current.getMonth() + Number(button.dataset.monthShift),
                1
            );
            render();
        });
    });

    widget.querySelector('[data-calendar-close]').addEventListener('click', () => {
        dialog.close();
    });

    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const submitButton = form.querySelector('[type="submit"]');
        const data = Object.fromEntries(new FormData(form).entries());

        submitButton.disabled = true;

        try {
            await requestJson(endpoints.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(data)
            });

            cache.marks.delete(monthKey(current.getFullYear(), current.getMonth()));
            form.reset();
            dialog.close();
            status.textContent = 'Penanda berhasil disimpan.';
            await render();
        } catch (error) {
            status.textContent = error.message;
        } finally {
            submitButton.disabled = false;
        }
    });

    render();
})();
</script>
@endpush
