<style>
/* ── Tabulator — DaisyUI 5 theme ─────────────────────────────────────────────
   Áp dụng bằng cách thêm class "tabulator-daisy" vào div wrapper của table:
   <div class="... tabulator-daisy"><div id="my-table"></div></div>
   ─────────────────────────────────────────────────────────────────────────── */
.tabulator-daisy .tabulator { border:none; border-radius:0; background:transparent; font-size:.8125rem; }
.tabulator-daisy .tabulator-header { background:var(--color-base-200); border-bottom:1px solid var(--color-base-300); color:color-mix(in oklab, var(--color-base-content) 65%, transparent); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
.tabulator-daisy .tabulator-col { background:transparent; border-right:1px solid var(--color-base-300); }
.tabulator-daisy .tabulator-col:last-child { border-right:none; }
.tabulator-daisy .tabulator-col.tabulator-sortable:hover { background:var(--color-base-300); }
.tabulator-daisy .tabulator-col-title { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tabulator-daisy .tabulator-row { background:var(--color-base-100); border-bottom:1px solid var(--color-base-200); transition:background .1s; }
.tabulator-daisy .tabulator-row:hover { background:color-mix(in oklab, var(--color-base-200) 60%, transparent); }
.tabulator-daisy .tabulator-row .tabulator-cell { border-right:1px solid var(--color-base-200); color:var(--color-base-content); padding:.5rem .75rem; line-height:1.4; }
.tabulator-daisy .tabulator-row .tabulator-cell:last-child { border-right:none; }
.tabulator-daisy .tabulator-footer { background:color-mix(in oklab, var(--color-base-200) 50%, transparent); border-top:1px solid var(--color-base-300); }
.tabulator-daisy .tabulator-paginator { color:color-mix(in oklab, var(--color-base-content) 70%, transparent); font-size:.8rem; }
.tabulator-daisy .tabulator-page { background:transparent; border:1px solid var(--color-base-300); color:var(--color-base-content); border-radius:.375rem; padding:.2rem .5rem; margin:0 1px; font-size:.8rem; cursor:pointer; transition:background .1s; }
.tabulator-daisy .tabulator-page:hover:not([disabled]) { background:var(--color-base-300); }
.tabulator-daisy .tabulator-page.active { background:var(--color-primary); color:var(--color-primary-content); border-color:var(--color-primary); }
.tabulator-daisy .tabulator-page[disabled] { opacity:.35; cursor:default; }
.tabulator-daisy .tabulator-page-size { background:var(--color-base-100); border:1px solid var(--color-base-300); color:var(--color-base-content); border-radius:.375rem; padding:.2rem .4rem; font-size:.8rem; }
.tabulator-daisy .tabulator-frozen.tabulator-frozen-right { box-shadow:-2px 0 6px color-mix(in oklab, var(--color-base-300) 60%, transparent); }
.tabulator-daisy .tabulator-frozen.tabulator-frozen-left  { box-shadow: 2px 0 6px color-mix(in oklab, var(--color-base-300) 60%, transparent); }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar { width:6px; height:6px; }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar-track { background:var(--color-base-200); }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar-thumb { background:var(--color-base-300); border-radius:3px; }
.tabulator-daisy .tabulator-loader { background:color-mix(in oklab, var(--color-base-100) 75%, transparent) !important; }
.tabulator-daisy .tabulator-loader-msg { background:var(--color-base-200) !important; border:1px solid var(--color-base-300) !important; border-radius:.5rem !important; color:var(--color-base-content) !important; font-size:.875rem !important; }

/* ── TomSelect — DaisyUI 5 theme (global — áp dụng mọi TomSelect trên trang) ─ */
.ts-wrapper.single .ts-control { background:var(--color-base-100); border-color:var(--color-base-300); color:var(--color-base-content); border-radius:.375rem; min-height:2rem; padding:.25rem .5rem; font-size:.875rem; }
.ts-wrapper.single.focus .ts-control { border-color:var(--color-primary); outline:none; box-shadow:0 0 0 2px color-mix(in oklab, var(--color-primary) 20%, transparent); }
.ts-dropdown { background:var(--color-base-100); border:1px solid var(--color-base-300); border-radius:.5rem; box-shadow:0 4px 16px rgba(0,0,0,.15); z-index:9999; font-size:.875rem; }
.ts-dropdown .ts-option { color:var(--color-base-content); padding:.4rem .75rem; }
.ts-dropdown .ts-option:hover,.ts-dropdown .ts-option.active { background:var(--color-base-200); color:var(--color-base-content); }
.ts-dropdown .ts-option.selected { background:color-mix(in oklab, var(--color-primary) 12%, transparent); color:var(--color-primary); }
.ts-wrapper .clear-button { color:color-mix(in oklab, var(--color-base-content) 40%, transparent); }
.ts-wrapper .clear-button:hover { color:var(--color-base-content); }
.ts-control input { color:var(--color-base-content) !important; }
.ts-dropdown .no-results { padding:.75rem; opacity:.5; }
.tabulator .tabulator-footer .ts-wrapper.tabulator-page-size { display:inline-block; vertical-align:middle; width:auto !important; min-width:4.75rem; margin:0 5px; border:0; background:transparent; text-align:left; }
.tabulator .tabulator-footer .ts-wrapper.tabulator-page-size .ts-control { min-height:1.75rem; font-size:.8rem !important; }
</style>
