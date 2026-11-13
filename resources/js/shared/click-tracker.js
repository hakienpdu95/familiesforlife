const config = window.__clickTracking || {};
const events = config.events || {};
const commerce = new Set(config.commerce || []);
const contact = new Set(config.contact || []);
const domainIndex = new Map();

Object.entries(config.platforms || {}).forEach(([platform, domains]) => {
    domains.forEach((domain) => domainIndex.set(domain.toLowerCase(), platform));
});

const MAX_VALUE = 100;
const MAX_EXTRA_PARAMS = 10;

const clip = (value) => {
    if (value === undefined || value === null) return undefined;
    const text = String(value).replace(/\s+/g, ' ').trim();
    return text ? text.slice(0, MAX_VALUE) : undefined;
};

const platformOf = (hostname) => {
    let host = hostname.toLowerCase().replace(/^www\./, '');
    while (host.includes('.')) {
        if (domainIndex.has(host)) return domainIndex.get(host);
        host = host.slice(host.indexOf('.') + 1);
    }
    return null;
};

const readableUrl = (url) => {
    const raw = `${url.origin}${url.pathname}`;
    try {
        return decodeURI(raw);
    } catch {
        return raw;
    }
};

const toSnake = (key) => key.replace(/^[A-Z]/, (c) => c.toLowerCase()).replace(/[A-Z]/g, (c) => `_${c.toLowerCase()}`);

const labelOf = (el) => el.dataset.trackLabel
    || el.getAttribute('title')
    || el.getAttribute('aria-label')
    || el.innerText
    || el.querySelector('img[alt]')?.getAttribute('alt');

const contextOf = (el) => {
    const ctx = {};
    for (let node = el; node && node !== document.documentElement; node = node.parentElement) {
        const data = node.dataset;
        if (!data) continue;
        if (ctx.item_name === undefined && data.trackItem) {
            ctx.item_name = data.trackItem;
            ctx.item_id = data.trackItemId;
            ctx.item_category = data.trackItemCategory;
        }
        if (ctx.placement === undefined && data.trackPlacement) ctx.placement = data.trackPlacement;
        if (ctx.item_name !== undefined && ctx.placement !== undefined) break;
    }
    return ctx;
};

const parseUrl = (el) => {
    const href = el.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return null;
    try {
        return new URL(href, window.location.href);
    } catch {
        return null;
    }
};

const classify = (el, url) => {
    const explicit = el.dataset.track;
    if (explicit === 'off') return null;

    if (url && (url.protocol === 'tel:' || url.protocol === 'mailto:')) {
        return { event: explicit || events.contact, method: url.protocol === 'tel:' ? 'phone' : 'email' };
    }

    const web = url && /^https?:$/.test(url.protocol);
    const external = Boolean(web && url.host !== window.location.host);
    const platform = el.dataset.trackPlatform || (external ? platformOf(url.hostname) : null);

    let event = explicit;
    let method;
    if (!event && platform && contact.has(platform)) {
        event = events.contact;
        method = platform;
    } else if (!event && platform && commerce.has(platform)) {
        event = events.commerce;
    } else if (!event && external) {
        event = events.outbound;
    }

    return event ? { event, method, platform: platform || (external ? 'other' : undefined), external, web } : null;
};

const track = (e) => {
    if (e.type === 'auxclick' && e.button !== 1) return;
    if (typeof window.gtag !== 'function') return;

    const el = e.target instanceof Element ? e.target.closest('a[href], [data-track]') : null;
    if (!el) return;

    const url = el.tagName === 'A' ? parseUrl(el) : null;
    const kind = classify(el, url);
    if (!kind) return;

    const params = {
        ...contextOf(el),
        platform: kind.platform,
        method: kind.method,
        link_text: labelOf(el),
        link_url: kind.web ? readableUrl(url) : undefined,
        link_domain: kind.web ? url.hostname : undefined,
        outbound: kind.external || undefined,
    };

    Object.entries(el.dataset)
        .filter(([key]) => key.startsWith('trackParam') && key.length > 10)
        .slice(0, MAX_EXTRA_PARAMS)
        .forEach(([key, value]) => {
            params[toSnake(key.slice(10))] = value;
        });

    const payload = { transport_type: 'beacon' };
    Object.entries(params).forEach(([key, value]) => {
        if (typeof value === 'boolean') payload[key] = value;
        else if (clip(value) !== undefined) payload[key] = clip(value);
    });

    window.gtag('event', kind.event, payload);
};

document.addEventListener('click', track, { capture: true, passive: true });
document.addEventListener('auxclick', track, { capture: true, passive: true });
