# SCORING ADMIN — ĐẶC TẢ UI/UX
**Module**: Survey > Scoring Configuration
**Dùng cho**: Claude Code / Dev implement giao diện admin cấu hình scoring
**Version**: 1.2
**Stack**: Laravel 13 · Blade · DaisyUI 5 · Alpine.js 3 · native `fetch()`

---

## 1. Mục tiêu

Xây dựng giao diện admin cho phép **cấu hình toàn bộ scoring engine** của một survey
trực tiếp trên browser — không cần viết seeder, không cần vào DB thủ công.

Người dùng: **Admin / BA** (không phải developer).

Đầu ra: ghi config vào các bảng DB sau (theo thứ tự phụ thuộc):

| Bảng | Mục đích |
|---|---|
| `assessments` | Hub config: aggregation_model, classification_type, has_scoring |
| `assessment_domains` | Domains + weight + min/max score |
| `score_rules` | Rule chấm điểm từng câu hỏi (field_key → feature_code) |
| `score_rule_options` | Các lựa chọn + điểm cho single/multi choice |
| `score_rule_numeric_ranges` | Các khoảng giá trị cho numeric_range |
| `score_bands` | Dải điểm → nhãn phân loại (score_band) |
| `pass_fail_configs` | Ngưỡng đạt/trượt (pass_fail) |
| `personas` | Danh sách personas (persona_match) |
| `persona_conditions` | Điều kiện khớp cho từng persona |
| `pain_point_rules` | Điều kiện phát hiện vấn đề (flag-based) |
| `recommendation_rules` | Điều kiện trigger gợi ý (domain score-based) |
| `roadmap_phases` | Lộ trình hành động gắn với từng band_code |
| `roadmap_milestones` | Các mốc công việc trong từng phase |

> **Phase 2 (chưa active)**: `feature_weights`, `feature_weight_history` —
> bảng đã tạo sẵn nhưng engine chưa kích hoạt. UI hiển thị section này
> nhưng đánh dấu "Phase 2 — Coming soon", không ghi vào DB.

---

## 2. Vị trí trong hệ thống

```
Surveys
└── [Tên survey] (edit page)
    ├── Thông tin chung      (đã có — cột trái)
    ├── Builder câu hỏi      (đã có — cột phải)
    └── ⭐ Scoring Config     → link sang trang riêng
```

**Route web (middleware `auth`)**:
```
GET  /dashboard/surveys/{survey}/scoring
POST /dashboard/surveys/{survey}/scoring
```

`{survey}` = Laravel route model binding theo `Survey::id` (integer),
giống với tất cả admin routes hiện có (`/dashboard/surveys/{survey}/stats`,
`/dashboard/surveys/{survey}/responses`, v.v.).

**JSON API admin (cùng middleware `auth`, trả `application/json`)**:
```
GET  /dashboard/surveys/{survey}/scoring/config     → load config hiện tại
PUT  /dashboard/surveys/{survey}/scoring/config     → save config (DB transaction)
GET  /dashboard/surveys/{survey}/scoring/fields     → danh sách survey_fields (để map rule)
GET  /dashboard/surveys/{survey}/scoring/flags      → signal flags đã khai báo trong score_rules
POST /dashboard/surveys/{survey}/scoring/validate   → validate, trả danh sách lỗi
POST /dashboard/surveys/{survey}/scoring/dry-run    → chạy thử với answers mẫu
```

> ⚠ **Public API** (`/api/v1/surveys/{slug}/...`) **không thay đổi** —
> đó là API submit/schema dùng Bearer token, hoàn toàn tách biệt.

> **assessment_code**: không lưu `survey_id` trong `assessments`.
> Code được sinh từ slug survey khi tạo mới
> (vd: slug `ai-readiness` → code `ai_readiness`).
> Toàn bộ bảng config dùng `assessment_code` làm khóa liên kết.

---

## 3. Kỹ thuật & Conventions

Bám sát 100% patterns của Survey module hiện tại (xem `builder/index.blade.php`):

| Aspect | Convention |
|---|---|
| Template | `@extends('layouts.backend')` + `@section('content')` |
| JavaScript | Alpine.js function pattern: `x-data="scoringConfig(@js($survey->id), @js($config), @js(csrf_token()))"` |
| Script location | `@push('scripts')` — inline trong blade, KHÔNG tách file JS riêng |
| HTTP calls | Native `fetch()` với header `X-CSRF-TOKEN` + `Accept: application/json` — không dùng jQuery/axios |
| Helper API | Hàm `api(url, method, body)` giống builder: set `saving = true`, parse JSON, hiện lỗi nếu `!response.ok` |
| Flash message | `flash: { text, type }` reactive, `setTimeout` tự xóa sau 3s (success) / 5s (error) |
| Destructive confirm | Native `confirm()` browser — giống `deleteSection()`, `deleteField()` trong builder |
| Ordering | Nút ▲ ▼ để đổi thứ tự (không dùng drag-and-drop lib) — giống builder |
| Modals | DaisyUI `<dialog class="modal">` + `:class="{ 'modal-open': modal.open }"` |
| Breadcrumb | `@section('breadcrumb')` với nav standard |
| State | Alpine.js reactive object — **không** lưu localStorage |

---

## 4. Layout tổng thể

Trang scoring config là **trang riêng** (không nhúng vào edit page). Layout:

```
@extends('layouts.backend')

┌──────────────────────────────────────────────────────────────┐
│  BREADCRUMB: Khảo sát › [Tên survey] › Scoring Config        │
├──────────────────────────────────────────────────────────────┤
│  HEADER: "Cấu hình Scoring — [Tên survey]"     [badge status]│
│  Flash message (x-show, auto-dismiss)                        │
├──────────────────────────────────────────────────────────────┤
│  DaisyUI TABS                                                │
│  ┌──────┬─────────┬────────┬───────┬─────────┬────────┬────┐ │
│  │①Cơ bản│②Domains│③Rules │④Bands │⑤Outputs │⑥Roadmap│⑦OK│ │
│  └──────┴─────────┴────────┴───────┴─────────┴────────┴────┘ │
│                                                              │
│  CONTENT PANEL (tab đang chọn)                               │
│                                                              │
├──────────────────────────────────────────────────────────────┤
│  FOOTER (sticky bottom): [← Trước]  [Lưu nháp]  [Tiếp →]   │
│                          (hoặc [Lưu & Kích hoạt] ở tab cuối) │
└──────────────────────────────────────────────────────────────┘
```

**Tab navigation**:
- Dùng DaisyUI `tabs` + `tab-lifted` hoặc `tab-bordered`.
- Mỗi tab có indicator trạng thái: `✓` (valid, màu success), `!` (error, màu error),
  không có indicator = chưa chạm vào.
- Có thể nhảy tự do giữa tabs, không buộc tuần tự.
- Tab bị disable (ví dụ: Domains khi chọn flat_sum) vẫn hiển thị nhưng `pointer-events-none opacity-40`.

**State management trong Alpine.js**:
```js
x-data="scoringConfig(surveyId, existingConfig, csrfToken)"

// Cấu trúc state chính:
{
  saving: false,
  activeTab: 1,           // 1–7
  flash: { text: '', type: 'success' },

  cfg: {
    hasScoring: true,
    aggregationModel: 'weighted_domain',
    classificationType: 'score_band',
    domains: [],
    rules: [],            // indexed by field_key
    bands: [],
    passFailConfig: {},
    personas: [],
    painPoints: [],
    recommendations: [],
    roadmapPhases: {},    // { [band_code]: [phases] }
  },
}
```

---

## 5. Chi tiết từng tab

---

### TAB 1 — Khai báo cơ bản

**Bảng ghi**: `assessments`

#### Fields

| Field | UI | Ghi chú |
|---|---|---|
| `has_scoring` | DaisyUI toggle (`<input type="checkbox" class="toggle toggle-primary">`) | Tắt → ẩn tab 2–7 + hiện alert-info "Survey này không chấm điểm" |
| `aggregation_model` | 3 card radio (xem bên dưới) | |
| `classification_type` | 4 card radio (xem bên dưới) | |

#### Radio cards `aggregation_model`

3 `<label>` card ngang nhau dùng `<input type="radio" class="hidden">` bên trong.
Card được chọn: `ring-2 ring-primary bg-primary/5`.

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│ ⚖ weighted_domain│  │ ∑ flat_sum       │  │ ▦ sectioned      │
│ Gộp theo domain  │  │ Cộng thẳng tất   │  │ Tính điểm độc    │
│ + trọng số       │  │ cả câu hỏi       │  │ lập từng section │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

#### Radio cards `classification_type`

4 card tương tự:
- `score_band` — Dải điểm (0–30 / 31–60 / 61–100…)
- `pass_fail` — Chỉ Đạt / Không đạt
- `persona_match` — Khớp nhóm persona theo điều kiện
- `none` — Không phân loại, chỉ tính điểm

#### Reactive behavior (Alpine.js `x-show` / `$watch`)

- `aggregation_model = flat_sum` → tab 2 (Domains) `disabled = true`, tooltip giải thích.
- `aggregation_model = sectioned` → tab 2 label đổi thành "Sections".
- `classification_type = none` → tab 4 (Bands) `disabled = true`.
- `classification_type = pass_fail` → tab 4 chỉ render form `pass_fail_configs`.
- `classification_type = persona_match` → tab 4 render Persona Builder.

---

### TAB 2 — Domains & Trọng số

**Hiện khi**: `aggregation_model = weighted_domain`
**Đổi thành "Sections"**: `aggregation_model = sectioned`

**Bảng ghi**: `assessment_domains`

#### Layout bảng domains

```
┌──────────────────────┬────────┬───────────┬───────────┬───┐
│ Domain code          │ Weight │ Min score │ Max score │   │
├──────────────────────┼────────┼───────────┼───────────┼───┤
│ [workflow          ] │ [0.25] │ [-67    ] │ [+58    ] │ 🗑 │
│ [sales             ] │ [0.20] │ [-55    ] │ [+60    ] │ 🗑 │
│ [hr                ] │ [0.15] │ [-43    ] │ [+55    ] │ 🗑 │
│ [data              ] │ [0.20] │ [-50    ] │ [+60    ] │ 🗑 │
│ [ai                ] │ [0.20] │ [-57    ] │ [+60    ] │ 🗑 │
├──────────────────────┴────────┴───────────┴───────────┴───┤
│ + Thêm domain                                             │
├───────────────────────────────────────────────────────────┤
│ Tổng weight: ████████████████████ 1.00  ✅ Hợp lệ        │
└───────────────────────────────────────────────────────────┘
```

Weight nhập dưới dạng decimal (0.25), hiển thị % trong progress bar (25%).

#### Validation inline (Alpine.js computed)

- Progress bar `style="width: X%"` tính từ `sum(domains.map(d => d.weight)) * 100`.
- Màu xanh khi tổng = 1.00 (±0.01), đỏ khi lệch.
- Text: "Còn thiếu 5%" / "Vượt 5% — cần giảm".
- `min_score` phải < `max_score` — validate `@blur` trên input.
- Không cho xóa domain nếu chỉ còn 1.
- Nếu domain đang có `score_rules` gắn vào → `confirm()` trước khi xóa:
  "X rules đang dùng domain này. Tiếp tục xóa?"

#### Gợi ý tự động min/max

Khi domain đã có `score_rules` → nút **"Tính tự động"** xuất hiện cuối row.
Click → gọi `GET /dashboard/surveys/{survey}/scoring/fields?domain=workflow`
→ tổng hợp score_if_true/false + option scores → điền sẵn min/max.

#### Chế độ Sectioned

Khi `aggregation_model = sectioned`, tab này hiển thị danh sách `survey_sections`
đã có (từ builder) với 2 input bổ sung: `min_score`, `max_score` cho từng section.
Không cần tạo mới — chỉ bổ sung config scoring.

---

### TAB 3 — Score Rules

**Bảng ghi**: `score_rules`, `score_rule_options`, `score_rule_numeric_ranges`

#### Khái niệm cần hiểu

- **`field_key`**: key của `survey_fields` — dùng để đọc câu trả lời khi submit.
- **`feature_code`**: mã trong scoring engine — mặc định = `field_key`,
  thay đổi nếu nhiều câu hỏi đóng góp vào cùng 1 feature.
- **`question_scoring_type`**: trường mới (none/boolean/single_choice/multi_choice/
  numeric_range). Trường `condition_type` cũ vẫn được ghi song song cho backward compat.

#### Layout danh sách

Lấy từ `GET /dashboard/surveys/{survey}/scoring/fields`.
Mỗi câu hỏi = 1 accordion row (giống builder):

```
┌──────────────────────────────────────────────────────────┐
│ ✓  [boolean]  Q1: Doanh nghiệp có SOP không?   [workflow]│
├──────────────────────────────────────────────────────────┤
│ ✓  [multi  ]  Q2: Công cụ đang sử dụng?        [sales   ]│
├──────────────────────────────────────────────────────────┤
│ ✓  [numeric]  Q3: Tỷ lệ chuyển đổi lead (%)    [sales   ]│
├──────────────────────────────────────────────────────────┤
│ ○  [none   ]  Q4: Tên doanh nghiệp              —không chấm│
└──────────────────────────────────────────────────────────┘
```

○ = chưa cấu hình (opacity thấp). ✓ = đã có rule.

**Thanh tiến trình trên cùng**:
```
Đã cấu hình: 8/12 câu hỏi   ██████████░░░░░  67%
```

#### Form bên trong accordion — Trường dùng chung

| Field | UI |
|---|---|
| `question_scoring_type` | `<select class="select select-sm">` |
| `domain_code` | `<select class="select select-sm">` — options lấy từ tab 2; ẩn khi `flat_sum` |
| `feature_code` | `<input class="input input-sm font-mono">` — placeholder = field_key |
| `signal_flag` | `<input class="input input-sm">` — chỉ hiện với boolean |

---

**`boolean`**:
```
Score nếu CÓ: [input +15]    Score nếu KHÔNG: [input -15]
Signal flag (khi true):       [input has_sop]
```

---

**`single_choice` / `multi_choice`**:

Bảng options, thêm/xóa/sắp xếp bằng nút ▲ ▼ (giống builder):

```
┌──────────────────────────┬────────┬──────────────────┬─────┐
│ Option value (key)       │ Score  │ Signal flag      │     │
├──────────────────────────┼────────┼──────────────────┼─────┤
│ ▲▼ [crm              ]  │ [+15 ] │ [has_crm       ] │  🗑  │
│ ▲▼ [excel            ]  │ [+5  ] │ [              ] │  🗑  │
│ ▲▼ [manual           ]  │ [-10 ] │ [manual_process] │  🗑  │
│ ▲▼ [none             ]  │ [-15 ] │ [no_tool       ] │  🗑  │
├──────────────────────────┴────────┴──────────────────┴─────┤
│ + Thêm option                                              │
└────────────────────────────────────────────────────────────┘
```

Score input: `class="input input-sm w-20"`, màu text xanh (>0) / đỏ (<0) / xám (=0)
qua Alpine.js `:class`.

Nếu `multi_choice`, thêm 2 field phía dưới bảng:
```
Min score cap: [input -20]    Max score cap: [input +30]
```
Alert-warning: "Bắt buộc với multi_choice — engine clamp tổng điểm vào [cap_min, cap_max]"

---

**`numeric_range`** (bảng `score_rule_numeric_ranges`):

```
┌────────────┬────────────┬────────┬──────────────────┬─────┐
│ Min value  │ Max value  │ Score  │ Signal flag      │     │
├────────────┼────────────┼────────┼──────────────────┼─────┤
│ ▲▼ [—    ] │ [5       ] │ [-10 ] │ [              ] │  🗑  │
│ ▲▼ [5    ] │ [15      ] │ [+5  ] │ [              ] │  🗑  │
│ ▲▼ [15   ] │ [—       ] │ [+20 ] │ [high_conv     ] │  🗑  │
├────────────┴────────────┴────────┴──────────────────┴─────┤
│ + Thêm khoảng                                             │
└───────────────────────────────────────────────────────────┘
— = NULL (không giới hạn)
```

Validation: các khoảng không được overlap — inline error khi nhập.

---

### TAB 4 — Phân loại

**Render theo `classification_type` đã chọn ở tab 1.**

---

**`score_band`** — Bảng `score_bands`:

```
┌───────────────────────────┬──────────────────────┬──────┬──────┬───┐
│ label                     │ band_code            │ Min  │ Max  │   │
├───────────────────────────┼──────────────────────┼──────┼──────┼───┤
│ ▲▼ [Vận hành thủ công   ] │ [MANUAL_OPERATION  ] │ [0 ] │ [30] │ 🗑 │
│ ▲▼ [Nền tảng số cơ bản  ] │ [DIGITAL_FOUNDATION] │ [31] │ [60] │ 🗑 │
│ ▲▼ [Sẵn sàng triển khai ] │ [AI_READY          ] │ [61] │ [80] │ 🗑 │
│ ▲▼ [Chuyển đổi AI       ] │ [AI_TRANSFORMATION ] │ [81] │[100] │ 🗑 │
├───────────────────────────┴──────────────────────┴──────┴──────┴───┤
│ + Thêm band                                                        │
└────────────────────────────────────────────────────────────────────┘
```

Visual indicator bên dưới bảng — thước 0–100 tô màu theo từng band:
```
0         30   31         60   61      80   81       100
[───── 🔴 ─────][───── 🟡 ─────][── 🟢 ──][──── 🟣 ────]
```
Hiển thị cảnh báo nếu có gap hoặc overlap giữa các band.

> `band_code` được dùng ở tab 6 (Roadmap) để gắn roadmap phases.

---

**`pass_fail`** — Bảng `pass_fail_configs`:

```
Ngưỡng đạt (passing_score): [input 70]   (score >= 70 → Pass)
Nhãn Pass (label_pass):     [input Đạt yêu cầu      ]
Nhãn Fail (label_fail):     [input Chưa đạt yêu cầu ]
```

---

**`persona_match`** — Bảng `personas` + `persona_conditions`:

Danh sách personas (accordion giống builder):

```
┌────────────────────────────────────────────────────────────┐
│ ▶  Startup tăng trưởng           [3 điều kiện]  ✎  🗑     │
├────────────────────────────────────────────────────────────┤
│ ▶  Enterprise cần AI             [4 điều kiện]  ✎  🗑     │
├────────────────────────────────────────────────────────────┤
│ + Thêm persona                                             │
└────────────────────────────────────────────────────────────┘
```

Khi expand, bảng `persona_conditions`:

```
┌──────────────┬─────────────┬─────────────┬────────────────┬───┐
│ target_type  │ target_code │ operator    │ value          │   │
├──────────────┼─────────────┼─────────────┼────────────────┼───┤
│ [domain    ] │ [sales    ] │ [>=       ] │ threshold: [60]│ 🗑 │
│ [signal_flag]│ [has_crm  ] │ [=        ] │ flag: [true  ] │ 🗑 │
│ [overall   ] │ —           │ [<        ] │ threshold: [80]│ 🗑 │
└──────────────┴─────────────┴─────────────┴────────────────┴───┘
+ Thêm điều kiện
```

**`target_type`**: `domain` / `signal_flag` / `overall`  
**`operator`**: `>=` / `<=` / `>` / `<` / `=`  
**value**: nếu `signal_flag` → `flag_value` (boolean true/false);
          nếu domain/overall → `threshold_value` (float).

Điều kiện trong cùng persona = AND. Để OR → thêm persona riêng.

---

### TAB 5 — Outputs

**Bảng ghi**: `pain_point_rules`, `recommendation_rules`

#### Section A — Pain Point Rules

Signal flags gợi ý tự động từ `GET /dashboard/surveys/{survey}/scoring/flags`.

```
┌──────────────────────────────┬────────────────────────────────────┐
│ required_flags (chips)       │ pain_point_code + mô tả            │
├──────────────────────────────┼────────────────────────────────────┤
│ [!has_crm ×] [lead_loss ×]  │ [sales_leakage ] [Rò rỉ khách...  ]│
│ [data_fragmented ×]          │ [fragmented_data] [Dữ liệu phân...] │
│ [!has_sop ×]                 │ [manual_workflow] [Thủ công...     ]│
├──────────────────────────────┴────────────────────────────────────┤
│ + Thêm pain point rule                                            │
└───────────────────────────────────────────────────────────────────┘
```

Flag chips: `badge badge-warning` nếu positive, `badge badge-error` nếu NOT (`!`).
Click chip để toggle `!` prefix. `×` để xóa chip.
Dropdown autocomplete từ danh sách flags đã khai báo ở tab 3.

#### Section B — Recommendation Rules

Điều kiện trigger theo normalized domain score (0–100):

```
┌──────────────────────────────┬────────────────────────────────────┐
│ Điều kiện (domain + operator + threshold) │ Code + mô tả          │
├──────────────────────────────────────────┴────────────────────────┤
│ domain [sales  ] [<] [50] → [crm_setup    ] [Thiết lập CRM...   ] │
│ domain [workflow] [<] [40] → [workflow_f..] [Nền tảng quy trình] │
│ domain [ai     ] [<] [30] → [ai_training ] [Đào tạo AI...      ] │
├───────────────────────────────────────────────────────────────────┤
│ + Thêm recommendation rule                                        │
└───────────────────────────────────────────────────────────────────┘
```

Dropdown domain lấy từ tab 2. Có thêm `priority` (1–5) để sắp xếp bằng ▲ ▼.

---

### TAB 6 — Roadmap

**Bảng ghi**: `roadmap_phases`, `roadmap_milestones`

Lộ trình hành động gắn với từng `band_code` (hoặc `persona_code` nếu persona_match).

#### Layout

Tabs con theo từng band (DaisyUI `tabs tabs-sm`):

```
[ 🔴 MANUAL_OPERATION ] [ 🟡 DIGITAL_FOUNDATION ] [ 🟢 AI_READY ] [ 🟣 AI_TRANSFORMATION ]
```

Trong mỗi band tab, danh sách phases với ▲ ▼:

```
┌────────────────────────────────────────────────────────────┐
│ ▲▼  Phase 1: Nền tảng vận hành   [phase_code: foundation]  │
│     Mô tả: [textarea...]          Duration: [4] tuần       │
│     Milestones:                                            │
│       ▲▼ [Lập SOP           ] 🗑                           │
│       ▲▼ [Cấu hình CRM      ] 🗑                           │
│       + Thêm milestone                                     │
├────────────────────────────────────────────────────────────┤
│ ▲▼  Phase 2: Số hoá bán hàng     [phase_code: sales_digit] │
│     ...                                                    │
├────────────────────────────────────────────────────────────┤
│ + Thêm phase                                               │
└────────────────────────────────────────────────────────────┘
```

> **Backward compat**: khi lưu, ghi đồng thời `band_code` = band_code
> và `maturity_level` = band_code vào `roadmap_phases`.

---

### TAB 7 — Xem lại & Lưu

Checklist validation + tóm tắt + nút Lưu chính thức.

#### Checklist (Alpine.js computed)

```
┌──────────────────────────────────────────────────────────┐
│  CHECKLIST                                               │
│                                                          │
│  ✅  has_scoring = true                                  │
│  ✅  aggregation_model = weighted_domain                 │
│  ✅  classification_type = score_band                    │
│  ✅  Tổng weight domains = 1.00                          │
│  ✅  8/8 câu hỏi đã cấu hình                            │
│  ✅  4 score bands liên tục 0–100                        │
│  ✅  2 pain point rules                                  │
│  ✅  5 recommendation rules                              │
│  ✅  Roadmap: 4 bands × 2 phases = 8 phases              │
│  ⚠   Dynamic weights (Phase 2): chưa kích hoạt          │
│                                                          │
│  ❌  domain 'hr': min_score >= max_score                 │
└──────────────────────────────────────────────────────────┘
```

#### Tóm tắt config

```
┌─────────────────────────────────────────────────────────┐
│  5 domains │ 12 rules │ 4 bands │ 5 recs │ 8 phases     │
└─────────────────────────────────────────────────────────┘
```

#### Nút action

```
[ Xuất JSON config ]     [ Lưu & Kích hoạt ]
```

- **"Lưu & Kích hoạt"**: `:disabled="hasErrors || saving"`.
  Click → `PUT /dashboard/surveys/{survey}/scoring/config`.
  Thành công → flash success "Scoring đã được kích hoạt", `assessments.is_active = true`.
- **"Xuất JSON config"**: download JSON toàn bộ state hiện tại từ frontend
  (không cần API thêm).
- Hiển thị lịch sử version nếu đã từng lưu:
  "Version 3 — lưu lúc 14:32 ngày 24/05/2026 bởi admin@..."

---

## 6. Dry-run Panel

Nút **"Chạy thử"** xuất hiện khi đã có ít nhất 1 rule. Mở overlay panel
(Alpine.js `x-show` + transition):

```
┌────────────────────────────────────────┐
│  🧪 DRY-RUN — Kiểm tra config         │
├────────────────────────────────────────┤
│  Nhập câu trả lời mẫu:                │
│                                        │
│  Q1 Có SOP?         [toggle true/false]│
│  Q2 Công cụ?        [multi checkbox]   │
│  Q3 Tỷ lệ CĐ?      [number input]     │
│  ...                                   │
├────────────────────────────────────────┤
│  KẾT QUẢ:                             │
│                                        │
│  workflow: 69.6   sales: 55.0         │
│  hr: 60.0   data: 72.0   ai: 40.0    │
│  Overall: 59.8                        │
│  Band: DIGITAL_FOUNDATION 🟡          │
│  Pain points: sales_leakage           │
│  Recommendations: crm_setup           │
└────────────────────────────────────────┘
```

Gọi `POST /dashboard/surveys/{survey}/scoring/dry-run`.
Payload = `{ answers: { [field_key]: value } }`.
Response format = `ScoringResult::toArray()` (đầu ra thực tế của engine).

---

## 7. Edge Cases

| Tình huống | Xử lý |
|---|---|
| Survey chưa có field nào | Tab 3 empty state + link đến `/dashboard/surveys/{survey}/edit` |
| Xóa domain đang có rules | `confirm()` chuẩn: "X rules dùng domain này. Xóa?" |
| Đổi `aggregation_model` sau khi đã có config | `confirm()`: "Thay đổi sẽ xóa toàn bộ domain config. Tiếp tục?" |
| Tổng weight ≠ 1.00 khi nhấn Lưu | Chặn + highlight tab 2 với dấu `!` trong tab nav |
| Score bands có gap | Cảnh báo inline: "Điểm X không thuộc band nào" |
| Survey đã có `survey_responses` | Banner `alert-warning`: "Survey đã có dữ liệu. Config mới áp dụng cho submission kế tiếp." |
| `assessments` chưa tồn tại cho survey này | Controller tự tạo record với `is_active = false` khi GET lần đầu |
| Lưu thất bại (DB error) | Flash error, không thay đổi state frontend |
