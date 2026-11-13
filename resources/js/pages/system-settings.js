import '@/scss/tom-select-frontend.scss';
import { createTs } from '@shared/tom-select-factory.js';

const form = document.querySelector('[data-settings-form]');

if (form) {
    const timezoneEl = form.querySelector('[data-settings-timezone]');
    const dateEl = form.querySelector('#date_format');
    const timeEl = form.querySelector('#time_format');
    const dirtyBadge = document.querySelector('[data-settings-dirty]');
    const resetBtn = document.querySelector('[data-settings-reset]');
    const preview = {
        time: document.querySelector('[data-preview-time]'),
        date: document.querySelector('[data-preview-date]'),
        timezone: document.querySelector('[data-preview-timezone]'),
        datetime: document.querySelector('[data-preview-datetime]'),
    };

    const timezoneTs = createTs(timezoneEl, {
        plugins: [],
        maxOptions: null,
        searchField: ['text', 'city', 'utc'],
        lockOptgroupOrder: true,
        placeholder: timezoneEl.dataset.tsPlaceholder,
        render: {
            optgroup_header: (data, escape) =>
                `<div class="optgroup-header" style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;opacity:.55">${escape(data.label)}</div>`,
            option: (data, escape) =>
                `<div style="display:flex;justify-content:space-between;gap:.75rem;align-items:center">
                    <span>${escape(data.city || data.text)}</span>
                    <span style="font-family:ui-monospace,monospace;font-size:.7rem;opacity:.55">${escape(data.utc || '')}</span>
                 </div>`,
            item: (data, escape) =>
                `<div>${escape(data.text)}<span style="margin-left:.5rem;font-family:ui-monospace,monospace;font-size:.7rem;opacity:.55">${escape(data.utc || '')}</span></div>`,
        },
    });

    const formatOptions = {
        plugins: [],
        controlInput: null,
        render: {
            option: (data, escape) =>
                `<div style="display:flex;justify-content:space-between;gap:.75rem;align-items:center">
                    <span class="tabular-nums">${escape(data.text)}</span>
                    <code style="font-size:.7rem;opacity:.55">${escape(data.pattern || '')}</code>
                 </div>`,
            item: (data, escape) =>
                `<div><span class="tabular-nums">${escape(data.text)}</span><code style="margin-left:.5rem;font-size:.7rem;opacity:.55">${escape(data.pattern || '')}</code></div>`,
        },
    };
    const dateTs = createTs(dateEl, formatOptions);
    const timeTs = createTs(timeEl, formatOptions);

    const values = () => Object.fromEntries(new FormData(form));
    const initial = values();

    const partsIn = (timeZone) => {
        let parts;
        try {
            parts = new Intl.DateTimeFormat('en-US', {
                timeZone, year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
            }).formatToParts(new Date());
        } catch {
            parts = new Intl.DateTimeFormat('en-US', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
            }).formatToParts(new Date());
        }
        return Object.fromEntries(parts.map(p => [p.type, p.value]));
    };

    const formatPhp = (pattern, p) => {
        const hour = Number(p.hour);
        const tokens = {
            d: p.day, m: p.month, Y: p.year, y: p.year.slice(-2),
            H: String(hour).padStart(2, '0'), h: String(hour % 12 || 12).padStart(2, '0'),
            i: p.minute, s: p.second, A: hour < 12 ? 'AM' : 'PM', a: hour < 12 ? 'am' : 'pm',
        };
        return pattern.replace(/[dmYyHhisAa]/g, c => tokens[c]);
    };

    const render = () => {
        const v = values();
        const p = partsIn(v.timezone);
        const date = formatPhp(v.date_format, p);
        const time = formatPhp(v.time_format, p);
        preview.time.textContent = time;
        preview.date.textContent = date;
        preview.datetime.textContent = `${date} ${time}`;
        const utc = timezoneTs?.options[v.timezone]?.utc;
        preview.timezone.textContent = utc ? `${v.timezone} (${utc})` : v.timezone;

        const dirty = Object.keys(initial).some(k => initial[k] !== v[k]);
        dirtyBadge?.classList.toggle('hidden', !dirty);
        if (resetBtn) resetBtn.disabled = !dirty;
    };

    form.addEventListener('change', render);
    form.addEventListener('input', render);
    form.addEventListener('reset', () => {
        setTimeout(() => {
            timezoneTs?.setValue(initial.timezone, true);
            dateTs?.setValue(initial.date_format, true);
            timeTs?.setValue(initial.time_format, true);
            render();
        });
    });
    form.addEventListener('submit', () => {
        const btn = document.querySelector('button[type="submit"][form="system-settings-form"]');
        if (btn) {
            btn.disabled = true;
            btn.insertAdjacentHTML('afterbegin', '<span class="loading loading-spinner loading-xs"></span>');
        }
    });

    render();
    setInterval(render, 15000);
}
