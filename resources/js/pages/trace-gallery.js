import { initSwiper, Navigation, Thumbs } from '../modules/swiper.js';
import { A11y, FreeMode, Keyboard } from 'swiper/modules';

import { t } from '../shared/i18n.js';
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function mount(root) {
    const mainEl = root.querySelector('[data-main]');
    const thumbsEl = root.querySelector('[data-thumbs]');
    const current = root.querySelector('[data-current]');
    if (!mainEl || mainEl.swiper) return;

    const thumbs = thumbsEl
        ? initSwiper(thumbsEl, {
            modules: [FreeMode],
            slidesPerView: 'auto',
            spaceBetween: 8,
            slidesOffsetBefore: 16,
            slidesOffsetAfter: 16,
            freeMode: { enabled: true, momentumRatio: 0.5 },
            watchSlidesProgress: true,
            navigation: false,
            pagination: false,
            slideToClickedSlide: true,
        })
        : null;

    initSwiper(mainEl, {
        modules: [Navigation, Thumbs, Keyboard, A11y],
        speed: reduceMotion ? 0 : 420,
        spaceBetween: 0,
        grabCursor: true,
        rewind: true,
        threshold: 6,
        resistanceRatio: 0.6,
        keyboard: { enabled: true, onlyInViewport: true },
        a11y: { prevSlideMessage: t('Ảnh trước'), nextSlideMessage: t('Ảnh sau'), slideLabelMessage: t('Ảnh {{index}}/{{slidesLength}}') },
        navigation: { prevEl: mainEl.querySelector('[data-prev]'), nextEl: mainEl.querySelector('[data-next]') },
        pagination: false,
        thumbs: thumbs ? { swiper: thumbs } : undefined,
        on: {
            slideChange(sw) {
                if (current) current.textContent = String(sw.realIndex + 1);
            },
        },
    });

    root.classList.add('is-ready');
}

const run = () => document.querySelectorAll('[data-tx-gallery]').forEach(mount);
document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run) : run();
