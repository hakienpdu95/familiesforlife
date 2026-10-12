export function renderCategoryTreeOption(d, escape) {
    const depth = Number(d.depth) || 0;
    const cls = depth === 0 ? 'font-bold' : depth === 1 ? 'font-medium' : 'text-base-content/70';
    return '<div><span class="flex items-start gap-1.5 ' + cls + '" style="margin-left:' + (depth * 1.25) + 'rem">'
        + (depth > 0 ? '<span class="text-base-content/30 shrink-0">└</span>' : '')
        + '<span>' + escape(d.text) + '</span></span></div>';
}
