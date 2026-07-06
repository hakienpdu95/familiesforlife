/**
 * resources/js/modules/qrcode.js
 * QR Code Generator wrapper
 *
 * Blade:  @vite(['resources/js/modules/qrcode.js'])
 * Use:    generateQR('https://...', '#container', { size: 200 })
 *         generateQRDataURL('text') → Promise<string dataURL>
 */
import qrcode from 'qrcode-generator';

/**
 * Render QR code vào container
 * @param {string} text       Nội dung QR
 * @param {string|HTMLElement} container  Selector hoặc element
 * @param {object} options    { size, typeNumber, errorLevel }
 */
function generateQR(text, container, options = {}) {
    const { size = 200, typeNumber = 0, errorLevel = 'M' } = options;
    const el = typeof container === 'string'
        ? document.querySelector(container)
        : container;
    if (!el) { console.warn('[QR] Container not found:', container); return; }

    const qr = qrcode(typeNumber, errorLevel);
    qr.addData(text);
    qr.make();

    const cellSize = Math.floor(size / qr.getModuleCount());
    el.innerHTML = qr.createSvgTag(cellSize, 0);
    const svg = el.querySelector('svg');
    if (svg) { svg.style.width = `${size}px`; svg.style.height = `${size}px`; }
}

/**
 * Trả về data URL của QR (dùng để download)
 */
function generateQRDataURL(text, options = {}) {
    const { typeNumber = 0, errorLevel = 'M', cellSize = 4 } = options;
    const qr = qrcode(typeNumber, errorLevel);
    qr.addData(text);
    qr.make();
    return qr.createDataURL(cellSize);
}

window.generateQR        = generateQR;
window.generateQRDataURL = generateQRDataURL;
export { generateQR, generateQRDataURL };
export default qrcode;
