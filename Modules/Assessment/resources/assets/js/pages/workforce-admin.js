import { TabulatorFull as Tabulator } from 'tabulator-tables'

const MATURITY_LABELS = {
    DIGITAL_BEGINNER:     'Khởi đầu số',
    DIGITAL_AWARE:        'Nhận thức số',
    DIGITAL_PRACTITIONER: 'Thực hành số',
    DIGITAL_PROFESSIONAL: 'Chuyên nghiệp số',
    DIGITAL_LEADER:       'Dẫn dắt số',
}
const MATURITY_BADGE = {
    DIGITAL_BEGINNER:     'badge-ghost',
    DIGITAL_AWARE:        'badge-info',
    DIGITAL_PRACTITIONER: 'badge-warning',
    DIGITAL_PROFESSIONAL: 'badge-success',
    DIGITAL_LEADER:       'badge-accent',
}

function maturityBadge(value) {
    if (!value) return '<span class="text-base-content/30 text-xs">—</span>'
    const label = MATURITY_LABELS[value] ?? value
    const cls   = MATURITY_BADGE[value] ?? 'badge-ghost'
    return `<span class="badge ${cls} badge-xs">${label}</span>`
}

function scorePill(value) {
    if (value === null || value === undefined) return '<span class="text-base-content/30 text-xs">—</span>'
    const n    = parseFloat(value)
    const cls  = n >= 70 ? 'text-success' : n >= 40 ? 'text-warning' : 'text-error'
    return `<span class="font-semibold text-sm ${cls}">${n.toFixed(1)}</span>`
}

export function workforceAdminPage({ apiUrl }) {
    return {
        filters: {
            search:         '',
            maturity_level: '',
        },
        table: null,

        init() {
            this.$nextTick(() => this.mountTable())

            this.$watch('filters', () => {
                this.table?.setData(this.buildUrl())
            })
        },

        buildUrl() {
            const p = new URLSearchParams()
            if (this.filters.search)         p.set('search', this.filters.search)
            if (this.filters.maturity_level) p.set('maturity_level', this.filters.maturity_level)
            return `${apiUrl}?${p.toString()}`
        },

        mountTable() {
            this.table = new Tabulator('#workforce-table', {
                ajaxURL:           this.buildUrl(),
                ajaxResponse:      (_url, _params, res) => res,
                pagination:        true,
                paginationMode:    'remote',
                sortMode:          'remote',
                paginationSize:    25,
                layout:            'fitColumns',
                responsiveLayout:  'collapse',
                height:            'calc(100vh - 340px)',
                locale:            'vi-VN',
                langs: {
                    'vi-VN': {
                        pagination: {
                            first: '«', last: '»', prev: '‹', next: '›',
                            first_title: 'Đầu', last_title: 'Cuối', prev_title: 'Trước', next_title: 'Sau',
                            page_size: 'Dòng/trang',
                            page_title: 'Trang',
                            all: 'Tất cả',
                        },
                    },
                },
                columns: [
                    {
                        title:     'Nhân viên',
                        field:     'employee_name',
                        minWidth:  180,
                        formatter: (cell) => {
                            const row      = cell.getRow().getData()
                            const initials = (row.employee_name ?? 'U').charAt(0).toUpperCase()
                            const dept     = row.department ? `<span class="text-xs text-base-content/40">${row.department}</span>` : ''
                            return `<div class="flex items-center gap-2.5 py-0.5">
                                <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-xs font-semibold text-primary shrink-0">${initials}</div>
                                <div class="leading-tight">
                                    <p class="font-medium text-sm">${row.employee_name ?? '—'}</p>
                                    ${dept}
                                </div>
                            </div>`
                        },
                    },
                    {
                        title:     'Cấp độ',
                        field:     'tdwcf_maturity_level',
                        width:     140,
                        formatter: (cell) => maturityBadge(cell.getValue()),
                        hozAlign:  'center',
                    },
                    {
                        title:     'TDWCF',
                        field:     'tdwcf_score',
                        width:     90,
                        formatter: (cell) => scorePill(cell.getValue()),
                        hozAlign:  'center',
                    },
                    {
                        title:     'Trust',
                        field:     'workforce_trust_score',
                        width:     90,
                        formatter: (cell) => scorePill(cell.getValue()),
                        hozAlign:  'center',
                    },
                    {
                        title:     'AI Ready',
                        field:     'ai_readiness_score',

                        width:     90,
                        formatter: (cell) => scorePill(cell.getValue()),
                        hozAlign:  'center',
                    },
                    {
                        title:     'Sandbox',
                        field:     'sandbox_sessions_total',
                        width:     80,
                        formatter: (cell) => {
                            const v = cell.getValue()
                            return v ? `<span class="text-sm">${v}</span>` : '<span class="text-base-content/30">—</span>'
                        },
                        hozAlign: 'center',
                    },
                    {
                        title:    'Cập nhật',
                        field:    'last_assessed_at',
                        width:    110,
                        formatter: (cell) => {
                            const v = cell.getValue()
                            return v ? `<span class="text-xs text-base-content/50">${v}</span>` : '<span class="text-base-content/20 text-xs">—</span>'
                        },
                    },
                    {
                        title:     '',
                        field:     'id',
                        width:     60,
                        hozAlign:  'center',
                        formatter: (cell) => {
                            const id = cell.getValue()
                            return `<a href="/dashboard/workforce/${id}" class="btn btn-ghost btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>`
                        },
                        headerSort: false,
                    },
                ],
            })
        },

        resetFilters() {
            this.filters.search         = ''
            this.filters.maturity_level = ''
        },
    }
}
