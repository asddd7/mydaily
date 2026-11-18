<section class="card calendar-widget" data-calendar-widget>
    <div class="calendar-header">
        <button type="button" data-month-shift="-1" aria-label="Bulan sebelumnya">❮</button>
        <h2 data-month-label aria-live="polite"></h2>
        <button type="button" data-month-shift="1" aria-label="Bulan berikutnya">❯</button>
    </div>


    <section class="calendar-holiday-panel" aria-labelledby="calendarHolidayTitle">
        <div class="calendar-holiday-heading">
            <span class="calendar-holiday-heading-icon" aria-hidden="true"><i class="fa-regular fa-calendar-check"></i></span>
            <div>
                <h3 id="calendarHolidayTitle">Kelola hari libur</h3>
                <p>Hari libur yang ditambahkan akan ditampilkan di kalender.</p>
            </div>
        </div>
        <button type="button" class="calendar-holiday-button" data-holiday-open aria-haspopup="dialog">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Tambah hari libur
        </button>
        <p class="calendar-holiday-feedback" data-holiday-list-feedback role="status" aria-live="polite"></p>
        <div class="calendar-holiday-list-section">
            <h4>Hari libur <span data-calendar-holiday-year></span></h4>
            <ul class="calendar-holiday-list" data-calendar-holiday-list>
                <li>Memuat hari libur…</li>
            </ul>
        </div>
    </section>

    <dialog data-calendar-holiday-dialog class="calendar-dialog" aria-labelledby="calendarHolidayDialogTitle">
        <form class="calendar-holiday-form" data-calendar-holiday-form>
            <h3 id="calendarHolidayDialogTitle" data-holiday-dialog-title>Tambah hari libur</h3>
            <input type="hidden" name="original_tanggal">
            <label>
                <span>Tanggal</span>
                <input type="date" name="tanggal" min="2000-01-01" max="2100-12-31" required>
            </label>
            <label>
                <span>Keterangan</span>
                <input type="text" name="keterangan" maxlength="255" placeholder="Contoh: Hari Kemerdekaan" required>
            </label>
            <p class="calendar-holiday-feedback" data-holiday-feedback role="status" aria-live="polite"></p>
            <div class="calendar-dialog-actions calendar-holiday-form-actions">
                <button type="submit" class="calendar-holiday-button calendar-holiday-submit" data-holiday-submit>
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span data-holiday-submit-label>Tambah hari libur</span>
                </button>
                <button type="button" class="calendar-holiday-button close" data-holiday-close>
                    Batal
                </button>
            </div>
        </form>
    </dialog>


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
        <h3>Agenda bulan ini</h3>
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
        marks: @json(route('calendar.marks', absolute: false)),
        holidays: @json(route('calendar.holidays', absolute: false)),
        tasks: @json(route('calendar.task-marks', absolute: false)),
        taskToggle: @json(route('tasks.toggle', ['task' => '__id__'], absolute: false)),
        store: @json(route('calendar.store', absolute: false)),
        toggle: @json(route('calendar.toggle', ['mark' => '__id__'], absolute: false)),
        remove: @json(route('calendar.destroy', ['mark' => '__id__'], absolute: false)),
        holidayStore: @json(route('calendar.holidays.store', absolute: false)),
        holidayRemove: @json(route('calendar.holidays.destroy', ['date' => '__date__'], absolute: false))
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

    const holidayForm = widget.querySelector('[data-calendar-holiday-form]');
    const holidayDialog = widget.querySelector('[data-calendar-holiday-dialog]');
    const holidayDialogTitle = widget.querySelector('[data-holiday-dialog-title]');
    const holidayOpen = widget.querySelector('[data-holiday-open]');
    const holidayList = widget.querySelector('[data-calendar-holiday-list]');
    const holidayYear = widget.querySelector('[data-calendar-holiday-year]');
    const holidayFeedback = widget.querySelector('[data-holiday-feedback]');
    const holidayListFeedback = widget.querySelector('[data-holiday-list-feedback]');
    const holidaySubmitLabel = widget.querySelector('[data-holiday-submit-label]');
    const holidayClose = widget.querySelector('[data-holiday-close]');

    const resetHolidayForm = () => {
        holidayForm.reset();
        holidayForm.elements.original_tanggal.value = '';
        holidayDialogTitle.textContent = 'Tambah hari libur';
        holidaySubmitLabel.textContent = 'Tambah hari libur';
        holidayFeedback.textContent = '';
    };

    holidayOpen.addEventListener('click', () => {
        resetHolidayForm();
        holidayListFeedback.textContent = '';
        holidayDialog.showModal();
        holidayForm.elements.tanggal.focus();
    });

    holidayClose.addEventListener('click', () => holidayDialog.close());
    holidayDialog.addEventListener('close', resetHolidayForm);
    holidayDialog.addEventListener('click', event => {
        if (event.target === holidayDialog) holidayDialog.close();
    });

    const renderHolidayList = (holidays, year) => {
        holidayYear.textContent = String(year);
        holidayList.replaceChildren();

        if (!holidays.length) {
            addText(holidayList, 'li', 'Belum ada hari libur untuk tahun ini.', 'calendar-holiday-empty');
            return;
        }

        holidays
            .filter(holiday => holiday?.tanggal && holiday?.keterangan)
            .sort((a, b) => a.tanggal.localeCompare(b.tanggal))
            .forEach(holiday => {
                const item = document.createElement('li');
                item.className = 'calendar-holiday-entry';

                const details = document.createElement('div');
                const date = document.createElement('time');
                date.dateTime = holiday.tanggal;
                date.textContent = new Date(`${holiday.tanggal}T00:00:00`).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long'
                });
                details.append(date);
                addText(details, 'span', holiday.keterangan);

                const actions = document.createElement('div');
                actions.className = 'calendar-holiday-entry-actions';
                const edit = addText(actions, 'button', 'Ubah');
                edit.type = 'button';
                edit.setAttribute('aria-label', `Ubah ${holiday.keterangan}`);
                edit.addEventListener('click', () => {
                    resetHolidayForm();
                    holidayForm.elements.tanggal.value = holiday.tanggal;
                    holidayForm.elements.keterangan.value = holiday.keterangan;
                    holidayForm.elements.original_tanggal.value = holiday.tanggal;
                    holidayDialogTitle.textContent = 'Ubah hari libur';
                    holidaySubmitLabel.textContent = 'Simpan perubahan';
                    holidayListFeedback.textContent = '';
                    holidayDialog.showModal();
                    holidayForm.elements.keterangan.focus();
                });

                const remove = addText(actions, 'button', 'Hapus');
                remove.type = 'button';
                remove.className = 'is-delete';
                remove.setAttribute('aria-label', `Hapus ${holiday.keterangan}`);
                remove.addEventListener('click', async () => {
                    if (!window.confirm(`Hapus hari libur ${holiday.keterangan}?`)) return;
                    remove.disabled = true;
                    holidayListFeedback.textContent = '';
                    try {
                        await requestJson(endpoints.holidayRemove.replace('__date__', encodeURIComponent(holiday.tanggal)), {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrf }
                        });
                        cache.holidays.delete(year);
                        holidayListFeedback.textContent = 'Hari libur berhasil dihapus.';
                        await render();
                    } catch (error) {
                        holidayListFeedback.textContent = error.message;
                        remove.disabled = false;
                    }
                });

                item.append(details, actions);
                holidayList.appendChild(item);
            });
    };

    holidayForm.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = holidayForm.querySelector('[data-holiday-submit]');
        submit.disabled = true;
        holidayFeedback.textContent = '';
        holidayListFeedback.textContent = '';
        const data = Object.fromEntries(new FormData(holidayForm).entries());
        const newDate = data.tanggal;
        const oldDate = data.original_tanggal;

        try {
            const result = await requestJson(endpoints.holidayStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(data)
            });
            if (oldDate) cache.holidays.delete(Number(oldDate.slice(0, 4)));
            cache.holidays.delete(Number(newDate.slice(0, 4)));
            current = new Date(Number(newDate.slice(0, 4)), Number(newDate.slice(5, 7)) - 1, 1);
            if (holidayDialog.open) holidayDialog.close();
            holidayListFeedback.textContent = result.message;
            await render();
        } catch (error) {
            holidayFeedback.textContent = error.message;
        } finally {
            submit.disabled = false;
        }
    });

    const isDone = value => value === true || Number(value) === 1;

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

    const addMarkEntry = (mark, targetList, holiday = false) => {
        const item = document.createElement('li');

        if (holiday) item.classList.add('holiday-item');
        const done = isDone(mark.selesai);
        if (done) item.classList.add('done');

        const text = document.createElement('div');
        text.className = 'mark-text';

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

        if (mark.agendaType === 'task' || mark.task) {
            const actions = document.createElement('div');
            actions.className = 'mark-actions calendar-task-actions';

            const badge = addText(
                actions,
                'span',
                done ? 'Selesai' : 'Belum selesai',
                `calendar-task-status${done ? ' is-done' : ''}`
            );
            badge.setAttribute('aria-label', `Status tugas: ${done ? 'selesai' : 'belum selesai'}`);

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'calendar-task-toggle';
            toggle.textContent = done ? 'Buka kembali' : 'Selesaikan';
            toggle.setAttribute('aria-pressed', String(done));
            toggle.setAttribute('aria-label', `${done ? 'Buka kembali' : 'Tandai selesai'}: ${label}`);
            toggle.addEventListener('click', async () => {
                toggle.disabled = true;
                try {
                    await requestJson(endpoints.taskToggle.replace('__id__', mark.id), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({ selesai: !done })
                    });
                    cache.tasks = null;
                    await render();
                } catch (error) {
                    status.textContent = error.message;
                    toggle.disabled = false;
                }
            });

            actions.appendChild(toggle);
            item.appendChild(actions);
        } else if (mark.id) {
            const actions = document.createElement('div');
            actions.className = 'mark-actions';

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'btn-done';
            toggle.textContent = done ? 'Belum selesai' : 'Selesai';
            toggle.addEventListener('click', async () => {
                toggle.disabled = true;
                try {
                    await requestJson(endpoints.toggle.replace('__id__', mark.id), {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({ selesai: !done })
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

        targetList.appendChild(item);
        return item;
    };

    const openForDate = date => {
        form.elements.tanggal.value = date;
        dialog.showModal();
        form.elements.title.focus();
    };
    document.addEventListener('calendar:open-create', () => openForDate(toDateString(new Date())));

    const applyCalendarData = (year, month, marks = [], holidays = [], tasks = []) => {
        renderHolidayList(holidays, year);
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
            const completedTasks = dayTasks.filter(task => isDone(task.selesai)).length;

            cell.querySelectorAll('.holiday-label').forEach(node => node.remove());
            cell.querySelectorAll('.calendar-task-progress').forEach(node => node.remove());
            cell.classList.remove('holiday', 'marked-date', 'done-date', 'has-tasks');

            if (holiday) {
                cell.classList.add('holiday');
                cell.title = holiday.keterangan || '';
                addText(cell, 'small', holiday.keterangan || '', 'holiday-label');
            } else {
                cell.removeAttribute('title');
            }

            const description = [date];
            if (holiday?.keterangan) description.push(`hari libur: ${holiday.keterangan}`);
            if (dayMarks.length) description.push(`${dayMarks.length} penanda`);
            if (dayTasks.length) {
                description.push(`${completedTasks} dari ${dayTasks.length} tugas selesai`);
                cell.classList.add('has-tasks');
                addText(cell, 'span', `${completedTasks}/${dayTasks.length}`, 'calendar-task-progress');
            }
            cell.setAttribute('aria-label', description.join(', '));

            const trackedItems = [...dayMarks, ...dayTasks];
            if (trackedItems.length) {
                cell.classList.add('marked-date');
                if (trackedItems.every(item => isDone(item.selesai))) {
                    cell.classList.add('done-date');
                }
            }
        });

        list.replaceChildren();

        const monthHolidays = holidays.filter(holiday =>
            holiday?.tanggal?.startsWith(currentMonthPrefix)
        );
        const agendaByDate = new Map();
        const addToAgenda = (item, type) => {
            if (!item?.tanggal) return;
            const entries = agendaByDate.get(item.tanggal) || [];
            entries.push({ ...item, agendaType: type });
            agendaByDate.set(item.tanggal, entries);
        };

        monthHolidays.forEach(holiday => addToAgenda(holiday, 'holiday'));
        marks.forEach(mark => addToAgenda(mark, 'mark'));
        currentMonthTasks.forEach(task => addToAgenda(task, 'task'));

        if (!agendaByDate.size) {
            addText(list, 'li', 'Tidak ada penanda atau tugas bulan ini.');
            return;
        }

        [...agendaByDate.entries()]
            .sort(([dateA], [dateB]) => dateA.localeCompare(dateB))
            .forEach(([date, entries]) => {
                const group = document.createElement('li');
                group.className = 'calendar-date-group';

                const header = document.createElement('div');
                header.className = 'calendar-date-heading';

                const dateLabel = document.createElement('time');
                dateLabel.dateTime = date;
                dateLabel.textContent = new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
                header.appendChild(dateLabel);

                const taskCount = entries.filter(entry => entry.agendaType === 'task').length;
                const markCount = entries.filter(entry => entry.agendaType === 'mark').length;
                const holidayCount = entries.filter(entry => entry.agendaType === 'holiday').length;
                const summary = [
                    taskCount ? `${taskCount} tugas` : '',
                    markCount ? `${markCount} penanda` : '',
                    holidayCount ? `${holidayCount} hari libur` : ''
                ].filter(Boolean).join(' · ');
                addText(header, 'span', summary, 'calendar-date-summary');
                group.appendChild(header);

                const entriesList = document.createElement('ul');
                entriesList.className = 'calendar-date-items';
                entries.forEach(entry => {
                    const item = addMarkEntry(entry, entriesList, entry.agendaType === 'holiday');
                    if (entry.agendaType === 'task') item.classList.add('calendar-task-item');
                });
                group.appendChild(entriesList);
                list.appendChild(group);
            });
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


    // Tampilkan nama file JSON yang dipilih.
    const holidayFileInput = widget.querySelector('[data-holiday-file-input]');
    const holidayFileLabel = widget.querySelector('[data-holiday-file-label]');
    const holidayImportForm = widget.querySelector('[data-holiday-import-form]');

    holidayFileInput?.addEventListener('change', () => {
        const file = holidayFileInput.files?.[0];

        if (holidayFileLabel) {
            holidayFileLabel.textContent = file
                ? file.name
                : 'Pilih file JSON';
        }
    });

    // Konfirmasi karena import akan mengganti file hari libur aktif.
    holidayImportForm?.addEventListener('submit', event => {
        const file = holidayFileInput?.files?.[0];

        if (!file) {
            event.preventDefault();
            alert('Pilih file JSON terlebih dahulu.');
            return;
        }

        if (!file.name.toLowerCase().endsWith('.json')) {
            event.preventDefault();
            alert('File harus berformat .json.');
            return;
        }

        const confirmed = window.confirm(
            'Import file "' + file.name + '"?\n\n' +
            'Data tanggal yang sama di holidays.json akan diperbarui, ' +
            'sedangkan tanggal lainnya tetap dipertahankan.'
        );

        if (!confirmed) {
            event.preventDefault();
        }
    });
    render();
})();
</script>
@endpush
