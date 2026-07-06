/**
 * resources/js/modules/swiper.js
 * Swiper v12 wrapper
 *
 * Blade:  @vite(['resources/js/modules/swiper.js'])
 * Use:    initSwiper('#mySwiper', { ...options })
 */
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, Thumbs } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const DEFAULTS = {
    modules:   [Navigation, Pagination, Autoplay],
    loop:      false,
    spaceBetween: 16,
    slidesPerView: 1,
    navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    pagination: { el: '.swiper-pagination', clickable: true },
};

const SwiperInstances = new Map();
window.SwiperInstances = SwiperInstances;

function initSwiper(selector, options = {}) {
    const el = typeof selector === 'string'
        ? document.querySelector(selector)
        : selector;
    if (!el) { console.warn('[Swiper] Element not found:', selector); return null; }

    const sw = new Swiper(el, { ...DEFAULTS, ...options });
    SwiperInstances.set(typeof selector === 'string' ? selector : el.id, sw);
    return sw;
}

window.initSwiper = initSwiper;
window.Swiper     = Swiper;
export { initSwiper, SwiperInstances, Navigation, Pagination, Autoplay, Thumbs };
export default Swiper;
