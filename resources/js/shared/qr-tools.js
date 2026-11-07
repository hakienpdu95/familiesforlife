import qrcode from 'qrcode-generator';

export const QR_EXPORT_SIZE = 2048;

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildQr(text) {
    const qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    return qr;
}

export function qrSvg(text) {
    return buildQr(text)
        .createSvgTag({ cellSize: 2, margin: 0, scalable: true })
        .replace('<svg ', '<svg class="w-full h-full" ');
}

export function downloadQrPng(text, fileName, size = QR_EXPORT_SIZE) {
    const qr = buildQr(text);
    const count = qr.getModuleCount();
    const quiet = 4;
    const cell = Math.floor(size / (count + quiet * 2));
    const offset = Math.floor((size - cell * count) / 2);

    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);
    ctx.fillStyle = '#000000';
    for (let r = 0; r < count; r++) {
        for (let c = 0; c < count; c++) {
            if (qr.isDark(r, c)) ctx.fillRect(offset + c * cell, offset + r * cell, cell, cell);
        }
    }

    canvas.toBlob((blob) => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    }, 'image/png');
}

export function qrFileName(code, label = '', size = QR_EXPORT_SIZE) {
    const slug = String(label || '')
        .normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd')
        .replace(/[^A-Za-z0-9._-]+/g, '-').replace(/^-+|-+$/g, '');
    return 'qr-' + code + (slug ? '-' + slug : '') + '-' + size + '.png';
}

export function qrCellHtml(code, url, label = '') {
    if (!code) return '<span class="text-base-content/30">—</span>';
    return '<div class="inline-flex flex-col items-center gap-1">'
        + '<a href="' + esc(url) + '" target="_blank" rel="noopener" class="block w-12 h-12 p-0.5 bg-white rounded border border-base-200 hover:border-primary" title="Mở trang truy xuất: ' + esc(url) + '">' + qrSvg(url) + '</a>'
        + '<div class="flex items-center gap-0.5">'
        + '<a href="' + esc(url) + '" target="_blank" rel="noopener" class="font-mono text-xs link link-primary">' + esc(code) + '</a>'
        + '<button type="button" class="btn btn-ghost btn-xs btn-square min-h-0 h-5 w-5" title="Tải QR PNG ' + QR_EXPORT_SIZE + '×' + QR_EXPORT_SIZE + ' (2K)"'
        + ' data-qr-download data-url="' + esc(url) + '" data-code="' + esc(code) + '" data-label="' + esc(label) + '">'
        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>'
        + '</button>'
        + '</div></div>';
}

if (!window.__qrDownloadBound) {
    window.__qrDownloadBound = true;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-qr-download]');
        if (!btn) return;
        e.preventDefault();
        downloadQrPng(btn.dataset.url, qrFileName(btn.dataset.code, btn.dataset.label));
    });
}
