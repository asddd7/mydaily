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
                <tr><th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th></tr>
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

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, {
            cache: 'no-store',
            ...options,
            headers: { Accept: 'application/json', ...(options.headers || {}) }
        });
        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            throw new Error(payload?.message || `Permintaan gagal (${response.status}).`);
        }
        return response.json();
    };

    const addText = (parent, tag, text, className) => {
        const element = document.createElement(tag);
        element.textContent = text;
        if (className) element.className = className;
        parent.appendChild(element);
        return element;
    };

    const addMarkEntry = (mark, holiday = false) => {
        const item = document.createElement('li');
        if (holiday) item.classList.add('holiday-item');
        if (mark.selesai) item.classList.add('done');
        const text = document.createElement('div');
        text.className = 'mark-text';
        const date = document.createElement('strong');
        date.textContent = mark.tanggal;
        text.appendChild(date);
        const label = mark.title || mark.keterangan || '';
        if (mark.url) {
            const link = document.createElement('a');
            link.href = mark.url;
            link.textContent = ` ${label}`;
            text.appendChild(link);
        } else {
            text.appendChild(document.createTextNode(` ${label}`));
        }
        if (mark.description) text.appendChild(document.createTextNode(` — ${mark.description}`));
        item.appendChild(text);

        if (mark.id) {
            const actions = document.createElement('div');
            actions.className = 'mark-actions';
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'btn-done';
            toggle.textContent = mark.selesai ? 'Belum selesai' : 'Selesai';
            toggle.addEventListener('click', async () => {
                try {
                    await requestJson(endpoints.toggle.replace('__id__', mark.id), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ selesai: !mark.selesai })
                    });
                    await render();
                } catch (error) {
                    status.textContent = error.message;
                }
            });
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'delete-mark';
            remove.textContent = 'Hapus';
            remove.addEventListener('click', async () => {
                if (!confirm('Yakin ingin menghapus penanda ini?')) return;
                try {
                    await requestJson(endpoints.remove.replace('__id__', mark.id), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf }
                    });
                    await render();
                } catch (error) {
                    status.textContent = error.message;
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

    const render = async () => {
        const version = ++requestVersion;
        const year = current.getFullYear();
        const month = current.getMonth();
        const monthLabel = current.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        title.textContent = monthLabel;
        status.textContent = 'Memuat kalender…';
        const query = new URLSearchParams({ month: String(month + 1), year: String(year) });

        try {
            const [marks, holidays, tasks] = await Promise.all([
                requestJson(`${endpoints.marks}?${query}`),
                requestJson(`${endpoints.holidays}?year=${year}`),
                requestJson(endpoints.tasks)
            ]);
            if (version !== requestVersion) return;

            const marksByDate = new Map();
            marks.forEach(mark => {
                const entries = marksByDate.get(mark.tanggal) || [];
                entries.push(mark);
                marksByDate.set(mark.tanggal, entries);
            });
            const holidaysByDate = new Map(holidays.map(holiday => [holiday.tanggal, holiday]));
            const tasksByDate = new Map();
            tasks.filter(task => task.tanggal.startsWith(`${year}-${String(month + 1).padStart(2, '0')}`)).forEach(task => {
                const entries = tasksByDate.get(task.tanggal) || [];
                entries.push(task);
                tasksByDate.set(task.tanggal, entries);
            });

            body.replaceChildren();
            list.replaceChildren();
            const firstWeekday = new Date(year, month, 1).getDay();
            const dayCount = new Date(year, month + 1, 0).getDate();
            let row = document.createElement('tr');
            for (let blank = 0; blank < firstWeekday; blank++) row.appendChild(document.createElement('td'));

            for (let day = 1; day <= dayCount; day++) {
                const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const cell = document.createElement('td');
                cell.dataset.date = date;
                addText(cell, 'span', String(day), 'calendar-day-number');
                const holiday = holidaysByDate.get(date);
                const dayMarks = marksByDate.get(date) || [];
                const dayTasks = tasksByDate.get(date) || [];
                const today = new Date();
                const todayDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                if (date === todayDate) cell.classList.add('today');
                if (new Date(year, month, day).getDay() === 0) cell.classList.add('weekend');
                if (holiday) {
                    cell.classList.add('holiday');
                    cell.title = holiday.keterangan;
                    addText(cell, 'small', holiday.keterangan, 'holiday-label');
                }
                if (dayMarks.length || dayTasks.length) {
                    cell.classList.add('marked-date');
                    if (dayMarks.length && dayMarks.every(mark => Number(mark.selesai) === 1)) cell.classList.add('done-date');
                    cell.setAttribute('aria-label', `${date}, ${dayMarks.length} penanda, ${dayTasks.length} tugas`);
                } else {
                    cell.setAttribute('aria-label', date);
                }
                cell.tabIndex = 0;
                cell.setAttribute('role', 'button');
                cell.addEventListener('click', () => openForDate(date));
                cell.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openForDate(date);
                    }
                });
                row.appendChild(cell);

                if ((firstWeekday + day) % 7 === 0 || day === dayCount) {
                    body.appendChild(row);
                    row = document.createElement('tr');
                }
            }

            const monthHolidays = holidays.filter(holiday => holiday.tanggal.startsWith(`${year}-${String(month + 1).padStart(2, '0')}`));
            if (monthHolidays.length) addText(list, 'li', '🎌 Hari Libur Nasional', 'holiday-item');
            monthHolidays.forEach(holiday => addMarkEntry(holiday, true));
            marks.forEach(mark => addMarkEntry(mark));
            tasks.filter(task => task.tanggal.startsWith(`${year}-${String(month + 1).padStart(2, '0')}`)).forEach(task => {
                const entry = addMarkEntry({ ...task, id: null });
                entry.classList.add('calendar-task-item');
            });
            if (!monthHolidays.length && !marks.length && !tasksByDate.size) addText(list, 'li', 'Tidak ada penanda atau tugas bulan ini.');
            status.textContent = '';
        } catch (error) {
            if (version !== requestVersion) return;
            status.textContent = `Kalender gagal dimuat: ${error.message}`;
            console.error(error);
        }
    };

    widget.querySelectorAll('[data-month-shift]').forEach(button => {
        button.addEventListener('click', () => {
            current = new Date(current.getFullYear(), current.getMonth() + Number(button.dataset.monthShift), 1);
            render();
        });
    });
    widget.querySelector('[data-calendar-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(form).entries());
        try {
            await requestJson(endpoints.store, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data)
            });
            form.reset();
            dialog.close();
            status.textContent = 'Penanda berhasil disimpan.';
            await render();
        } catch (error) {
            status.textContent = error.message;
        }
    });
    render();
})();
</script>
@endpush
