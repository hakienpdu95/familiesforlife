<style>
/* ── Tabulator — DaisyUI 5 theme ─────────────────────────────────────────────
   Áp dụng bằng cách thêm class "tabulator-daisy" vào div wrapper của table:
   <div class="... tabulator-daisy"><div id="my-table"></div></div>
   ─────────────────────────────────────────────────────────────────────────── */
.tabulator-daisy .tabulator { border:none; border-radius:0; background:transparent; font-size:.8125rem; }
.tabulator-daisy .tabulator-header { background:oklch(var(--b2)); border-bottom:1px solid oklch(var(--b3)); color:oklch(var(--bc)/.65); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
.tabulator-daisy .tabulator-col { background:transparent; border-right:1px solid oklch(var(--b3)); }
.tabulator-daisy .tabulator-col:last-child { border-right:none; }
.tabulator-daisy .tabulator-col.tabulator-sortable:hover { background:oklch(var(--b3)); }
.tabulator-daisy .tabulator-col-title { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tabulator-daisy .tabulator-row { background:oklch(var(--b1)); border-bottom:1px solid oklch(var(--b2)); transition:background .1s; }
.tabulator-daisy .tabulator-row:hover { background:oklch(var(--b2)/.6); }
.tabulator-daisy .tabulator-row .tabulator-cell { border-right:1px solid oklch(var(--b2)); color:oklch(var(--bc)); padding:.5rem .75rem; line-height:1.4; }
.tabulator-daisy .tabulator-row .tabulator-cell:last-child { border-right:none; }
.tabulator-daisy .tabulator-footer { background:oklch(var(--b2)/.5); border-top:1px solid oklch(var(--b3)); }
.tabulator-daisy .tabulator-paginator { color:oklch(var(--bc)/.7); font-size:.8rem; }
.tabulator-daisy .tabulator-page { background:transparent; border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .5rem; margin:0 1px; font-size:.8rem; cursor:pointer; transition:background .1s; }
.tabulator-daisy .tabulator-page:hover:not([disabled]) { background:oklch(var(--b3)); }
.tabulator-daisy .tabulator-page.active { background:oklch(var(--p)); color:oklch(var(--pc)); border-color:oklch(var(--p)); }
.tabulator-daisy .tabulator-page[disabled] { opacity:.35; cursor:default; }
.tabulator-daisy .tabulator-page-size { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .4rem; font-size:.8rem; }
.tabulator-daisy .tabulator-frozen.tabulator-frozen-right { box-shadow:-2px 0 6px oklch(var(--b3)/.6); }
.tabulator-daisy .tabulator-frozen.tabulator-frozen-left  { box-shadow: 2px 0 6px oklch(var(--b3)/.6); }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar { width:6px; height:6px; }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar-track { background:oklch(var(--b2)); }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar-thumb { background:oklch(var(--b3)); border-radius:3px; }
.tabulator-daisy .tabulator-loader { background:oklch(var(--b1)/.75) !important; }
.tabulator-daisy .tabulator-loader-msg { background:oklch(var(--b2)) !important; border:1px solid oklch(var(--b3)) !important; border-radius:.5rem !important; color:oklch(var(--bc)) !important; font-size:.875rem !important; }

/* ── TomSelect — DaisyUI 5 theme (global — áp dụng mọi TomSelect trên trang) ─ */
.ts-wrapper.single .ts-control { background:oklch(var(--b1)); border-color:oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; min-height:2rem; padding:.25rem .5rem; font-size:.875rem; }
.ts-wrapper.single.focus .ts-control { border-color:oklch(var(--p)); outline:none; box-shadow:0 0 0 2px oklch(var(--p)/.2); }
.ts-dropdown { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); border-radius:.5rem; box-shadow:0 4px 16px rgba(0,0,0,.15); z-index:9999; font-size:.875rem; }
.ts-dropdown .ts-option { color:oklch(var(--bc)); padding:.4rem .75rem; }
.ts-dropdown .ts-option:hover,.ts-dropdown .ts-option.active { background:oklch(var(--b2)); color:oklch(var(--bc)); }
.ts-dropdown .ts-option.selected { background:oklch(var(--p)/.12); color:oklch(var(--p)); }
.ts-wrapper .clear-button { color:oklch(var(--bc)/.4); }
.ts-wrapper .clear-button:hover { color:oklch(var(--bc)); }
.ts-control input { color:oklch(var(--bc)) !important; }
.ts-dropdown .no-results { padding:.75rem; opacity:.5; }
</style>
