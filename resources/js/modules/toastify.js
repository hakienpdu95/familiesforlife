/**
 * resources/js/modules/toastify.js
 * Wrapper Toastify-JS → window.Toast.success/error/warning/info
 *
 * Blade:  @vite(['resources/js/modules/toastify.js'])
 * Use:    Toast.success('Lưu thành công!')
 */
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const BASE = {
    duration: 3500, close: true, gravity: 'top', position: 'right',
    stopOnFocus: true,
    style: { borderRadius: '8px', fontFamily: 'inherit', fontSize: '13px' },
};
const COLORS = {
    success: '#22c55e', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6',
};
function show(message, type, opts = {}) {
    Toastify({ ...BASE, ...opts, text: message, style: { ...BASE.style, background: COLORS[type], ...(opts.style ?? {}) } }).showToast();
}
const Toast = {
    success: (m, o) => show(m, 'success', o),
    error:   (m, o) => show(m, 'error',   o),
    warning: (m, o) => show(m, 'warning', o),
    info:    (m, o) => show(m, 'info',    o),
    custom:  (o)    => Toastify({ ...BASE, ...o }).showToast(),
};
window.Toast = Toast;
export default Toast;
