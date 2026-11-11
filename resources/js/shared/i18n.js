export function t(key, replace = {}) {
    const dictionary = (typeof window !== 'undefined' && window.TRANSLATIONS) || {};
    let line = Object.prototype.hasOwnProperty.call(dictionary, key) ? dictionary[key] : key;

    Object.keys(replace)
        .sort((a, b) => b.length - a.length)
        .forEach((name) => {
            const value = String(replace[name] ?? '');
            line = line
                .replaceAll(':' + name.toUpperCase(), value.toUpperCase())
                .replaceAll(':' + name.charAt(0).toUpperCase() + name.slice(1), value.charAt(0).toUpperCase() + value.slice(1))
                .replaceAll(':' + name, value);
        });

    return line;
}

export function locale() {
    return (typeof window !== 'undefined' && window.APP_LOCALE) || document.documentElement.lang || 'vi';
}

if (typeof window !== 'undefined') {
    window.t = t;
}
