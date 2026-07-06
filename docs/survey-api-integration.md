# Hướng dẫn tích hợp Survey API — Từ A đến Z

> **Mục tiêu:** Web ngoài (landing page, app riêng...) hiển thị form khảo sát động, người dùng điền và submit — dữ liệu tự động vào CRM Survey.

---

## Mục lục

1. [Tổng quan kiến trúc](#1-tổng-quan-kiến-trúc)
2. [Bước 1 — Chuẩn bị môi trường CRM](#2-bước-1--chuẩn-bị-môi-trường-crm)
3. [Bước 2 — Tạo Survey trong CRM](#3-bước-2--tạo-survey-trong-crm)
4. [Bước 3 — Tạo API Token](#4-bước-3--tạo-api-token)
5. [Bước 4 — Cấu hình CORS](#5-bước-4--cấu-hình-cors)
6. [Bước 5 — Kiểm tra API hoạt động (curl)](#6-bước-5--kiểm-tra-api-hoạt-động-curl)
7. [Bước 6 — Tham chiếu API đầy đủ](#7-bước-6--tham-chiếu-api-đầy-đủ)
8. [Bước 7 — Tích hợp vào web ngoài (HTML + JS)](#8-bước-7--tích-hợp-vào-web-ngoài-html--js)
9. [Bước 8 — Tích hợp với React / Vue](#9-bước-8--tích-hợp-với-react--vue)
10. [Bước 9 — Cloudflare Turnstile (chống bot)](#10-bước-9--cloudflare-turnstile-chống-bot)
11. [Bước 10 — Xem dữ liệu phản hồi trong CRM](#11-bước-10--xem-dữ-liệu-phản-hồi-trong-crm)
12. [Xử lý lỗi thường gặp](#12-xử-lý-lỗi-thường-gặp)
13. [Bảo mật & best practices](#13-bảo-mật--best-practices)
14. [Checklist xuất bản cuối cùng](#14-checklist-xuất-bản-cuối-cùng)

---

## 1. Tổng quan kiến trúc

```
┌─────────────────────────┐        HTTPS         ┌─────────────────────┐
│   Web ngoài (bất kỳ)    │ ──────────────────▶  │   CRM Survey API    │
│  (HTML/React/Vue/...)   │                       │  /api/v1/surveys/   │
│                         │ ◀──────────────────   │                     │
└─────────────────────────┘    JSON response      └──────────┬──────────┘
                                                             │
                                                    ┌────────▼────────┐
                                                    │   Database CRM  │
                                                    │  survey_answers │
                                                    │  survey_resp.   │
                                                    └─────────────────┘
```

**Luồng chuẩn (2 bước):**

```
Bước A — Khi trang web load:
  GET /api/v1/surveys/{slug}/schema
  → Nhận cấu trúc form (sections, fields, options)
  → Render form động theo schema

Bước B — Khi người dùng submit:
  POST /api/v1/surveys/{slug}/submit
  → Gửi { respondent_ref, answers[] }
  → Nhận { response_id: 42 } nếu thành công
```

**Xác thực:** Mọi request đều cần `Authorization: Bearer <token>`.  
**Token** được tạo trong CRM, mỗi token chỉ hợp lệ cho **một survey cụ thể**.

---

## 2. Bước 1 — Chuẩn bị môi trường CRM

### 2.1 Cấu hình `.env` trên server CRM

```bash
# File: /var/www/html/minhan/.env

APP_URL=https://crm.domain.com   # domain thực của CRM, KHÔNG có dấu /
APP_ENV=production
APP_DEBUG=false

# Cache schema survey (cần Redis hoặc database)
CACHE_STORE=redis                # hoặc database nếu chưa có Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Queue (cập nhật last_used_at của token bất đồng bộ)
QUEUE_CONNECTION=database        # hoặc redis

# Turnstile (để tắt trong dev, bật ở production)
TURNSTILE_ENABLED=false          # true khi muốn bật anti-bot
```

### 2.2 Build assets và clear cache

```bash
cd /var/www/html/minhan

php artisan config:clear
php artisan cache:clear
php artisan route:clear
npm run build
php artisan migrate
```

### 2.3 Khởi động queue worker (quan trọng)

Token cập nhật `last_used_at` qua queue. Nếu không có worker, tính năng này bỏ qua — API vẫn hoạt động bình thường.

```bash
# Chạy một lần để test
php artisan queue:work --tries=3

# Production: dùng supervisor
# /etc/supervisor/conf.d/minhan-worker.conf
[program:minhan-worker]
command=php /var/www/html/minhan/artisan queue:work --tries=3 --sleep=3
autostart=true
autorestart=true
user=www-data
```

---

## 3. Bước 2 — Tạo Survey trong CRM

### 3.1 Tạo mới survey

1. Đăng nhập CRM → menu **Survey** → nút **Tạo mới**
2. Điền **tiêu đề** (ví dụ: `Khảo sát độ hài lòng Q2-2025`)
3. Hệ thống tự tạo **slug** từ tiêu đề (ví dụ: `khao-sat-do-hai-long-q2-2025`)
   - Ghi nhớ slug — dùng trong mọi API call
   - Slug có thể edit trước khi Activate

### 3.2 Thiết kế form (Builder)

Vào tab **Builder** của survey, thêm sections và fields:

**Các loại field và cách dùng:**

| Loại field | Mô tả | Khi nào dùng |
|---|---|---|
| `text` | Văn bản ngắn 1 dòng | Họ tên, email, điện thoại |
| `textarea` | Văn bản dài nhiều dòng | Góp ý, nhận xét chi tiết |
| `number` | Chỉ nhận số | Tuổi, số lượng, điểm |
| `rating` | Đánh giá sao (số) | Mức độ hài lòng 1–5 |
| `date` | Chọn ngày | Ngày sinh, ngày hẹn |
| `boolean` | Có / Không | Đồng ý điều khoản |
| `radio` | Chọn 1 trong nhiều | Giới tính, nguồn biết đến |
| `select` | Dropdown chọn 1 | Tỉnh/thành, hạng mục |
| `checkbox` | Tick nhiều lựa chọn | Sở thích, sản phẩm quan tâm |

**Ràng buộc validation có thể đặt:**
- `is_required`: bắt buộc điền
- `rule_min` / `rule_max`: giới hạn số hoặc độ dài text
- `rule_max_select`: số lựa chọn tối đa cho checkbox
- Option `is_other = true`: hiện ô nhập tự do khi chọn "Khác"

### 3.3 Kích hoạt survey

Sau khi thiết kế xong:
1. Nhấn nút **Kích hoạt** (hoặc chuyển trạng thái → **Đang mở**)
2. Survey ở trạng thái **Draft** hoặc **Đã đóng** sẽ trả lỗi `403` khi submit

> **Lưu ý:** Sau khi Activate, không thể thêm/xóa field (schema bị khóa). Chỉ có thể sửa label, placeholder, options.

---

## 4. Bước 3 — Tạo API Token

### 4.1 Tạo token

1. Mở survey → tab **Tokens**
2. Nhấn **Tạo token mới**
3. Điền thông tin:
   - **Tên token**: đặt tên gợi nhớ, ví dụ: `Website Landing Page`, `App Mobile`
   - **Ngày hết hạn**: để trống = không hết hạn; hoặc đặt hạn cụ thể
4. Nhấn **Tạo**

### 4.2 Copy plain token NGAY LẬP TỨC

Sau khi tạo, hệ thống hiển thị token dạng:
```
xH7kP2mN9qR5vL8jF3dW6tY1uA4nB0oC...  (64 ký tự ngẫu nhiên)
```

> **QUAN TRỌNG:** Đây là lần DUY NHẤT hệ thống hiển thị token đầy đủ.  
> DB chỉ lưu SHA-256 hash — không thể phục hồi nếu mất.  
> Mất token → Revoke → Tạo token mới.

### 4.3 Lưu token an toàn

Token phải được lưu ở phía backend web ngoài của bạn, không được để lộ ra client-side:

```bash
# .env của web ngoài
CRM_SURVEY_TOKEN=xH7kP2mN9qR5vL8jF3dW6tY1uA4nB0oC...
CRM_SURVEY_SLUG=khao-sat-do-hai-long-q2-2025
CRM_BASE_URL=https://crm.domain.com
```

### 4.4 Quản lý token

Trong tab **Tokens** của survey, mỗi token hiển thị:
- **Trạng thái**: Active / Revoked / Expired
- **Lần dùng cuối**: thời điểm request gần nhất
- **Ngày hết hạn**

Các thao tác:
- **Xem lại token**: Reveal để hiện lại plain text (tính năng có thể tắt)
- **Thu hồi**: Vô hiệu hóa ngay lập tức, không xóa lịch sử
- **Xóa**: Xóa hoàn toàn khỏi DB

---

## 5. Bước 4 — Cấu hình CORS

CORS kiểm soát domain nào được phép gọi API từ browser.

### 5.1 Kiểm tra cấu hình hiện tại

```bash
php artisan config:show cors
```

**Mặc định** (cho phép mọi origin — OK cho development):
```
allowed_origins: ["*"]
allowed_methods: ["*"]
allowed_headers: ["*"]
```

### 5.2 Giới hạn domain (khuyến nghị cho production)

Publish config nếu chưa có:
```bash
php artisan config:publish cors
# hoặc
php artisan vendor:publish --tag=laravel-cors
```

Sửa `config/cors.php`:
```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    // Chỉ cho phép các domain cụ thể
    'allowed_origins' => [
        'https://website-cua-ban.com',
        'https://form.website-cua-ban.com',
        'https://landing.website-cua-ban.com',
    ],

    // Để trống nếu đã liệt kê allowed_origins
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 3600,  // Cache preflight 1 giờ

    'supports_credentials' => false,
];
```

Sau khi sửa:
```bash
php artisan config:clear
```

### 5.3 Test CORS (từ terminal)

```bash
curl -I -X OPTIONS https://crm.domain.com/api/v1/surveys/test-slug/schema \
  -H "Origin: https://website-cua-ban.com" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: Authorization"

# Kết quả cần thấy:
# Access-Control-Allow-Origin: https://website-cua-ban.com
# Access-Control-Allow-Methods: GET, POST, OPTIONS
# Access-Control-Allow-Headers: Authorization, Content-Type
```

---

## 6. Bước 5 — Kiểm tra API hoạt động (curl)

Thay `<YOUR_TOKEN>` và `<YOUR_SLUG>` bằng giá trị thực trước khi chạy.

### 6.1 Lấy schema survey

```bash
curl -X GET "https://crm.domain.com/api/v1/surveys/<YOUR_SLUG>/schema" \
  -H "Authorization: Bearer <YOUR_TOKEN>" \
  -H "Accept: application/json"
```

**Kết quả mong đợi (200):**
```json
{
  "id": 1,
  "title": "Khảo sát độ hài lòng Q2-2025",
  "slug": "khao-sat-do-hai-long-q2-2025",
  "version": 1,
  "sections": [
    {
      "id": 1,
      "title": "Thông tin cá nhân",
      "icon": null,
      "sort_order": 1,
      "fields": [
        {
          "id": 1,
          "field_key": "ho_ten",
          "label": "Họ và tên",
          "field_type": "text",
          "is_required": true,
          "placeholder": "Nhập họ tên đầy đủ",
          "rule_min": null,
          "rule_max": 100,
          "rule_max_select": null,
          "options": []
        },
        {
          "id": 2,
          "field_key": "muc_hai_long",
          "label": "Mức độ hài lòng",
          "field_type": "radio",
          "is_required": true,
          "placeholder": null,
          "rule_min": null,
          "rule_max": null,
          "rule_max_select": null,
          "options": [
            { "option_value": "rat_hai_long", "label": "Rất hài lòng", "is_other": false },
            { "option_value": "hai_long",     "label": "Hài lòng",      "is_other": false },
            { "option_value": "trung_binh",   "label": "Trung bình",    "is_other": false },
            { "option_value": "khong_hai_long","label": "Không hài lòng","is_other": false },
            { "option_value": "khac",         "label": "Khác",          "is_other": true  }
          ]
        }
      ]
    }
  ]
}
```

### 6.2 Submit câu trả lời

```bash
curl -X POST "https://crm.domain.com/api/v1/surveys/<YOUR_SLUG>/submit" \
  -H "Authorization: Bearer <YOUR_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "respondent_ref": "user-001",
    "answers": [
      {
        "field_key": "ho_ten",
        "value": "Nguyễn Văn A",
        "other_text": null
      },
      {
        "field_key": "muc_hai_long",
        "value": "khac",
        "other_text": "Dịch vụ tốt nhưng cần cải thiện thêm"
      }
    ]
  }'
```

**Kết quả thành công (201):**
```json
{ "response_id": 42 }
```

### 6.3 Các lỗi cần biết khi test

| HTTP | Lý do | Xử lý |
|---|---|---|
| `401` | Không có token / token sai | Kiểm tra header `Authorization: Bearer ...` |
| `401` | Token inactive / hết hạn | Tạo token mới trong CRM |
| `403` | Token không thuộc survey | Token và slug phải khớp nhau |
| `403` | Survey chưa Active | Kích hoạt survey trong CRM |
| `404` | Slug không tồn tại | Kiểm tra chính tả slug |
| `422` | Dữ liệu không hợp lệ | Xem `errors` trong response |
| `429` | Vượt rate limit (60 req/phút) | Giảm tần suất gọi |

---

## 7. Bước 6 — Tham chiếu API đầy đủ

### Base URL

```
https://crm.domain.com/api/v1
```

### Headers chung

```
Authorization: Bearer <plain_token>
Content-Type: application/json
Accept: application/json
```

---

### GET `/surveys/{slug}/schema`

Trả về cấu trúc đầy đủ của survey. Kết quả **cache 30 phút** phía server.

**Path param:**
- `slug` — slug của survey, ví dụ `khao-sat-khach-hang`

**Response 200:**

```
{
  id:        int
  title:     string
  slug:      string
  version:   int
  sections:  Section[]
}

Section {
  id:         int
  title:      string
  icon:       string|null
  sort_order: int
  fields:     Field[]
}

Field {
  id:              int
  parent_field_id: int|null
  field_key:       string        ← dùng khi submit
  label:           string
  field_type:      string        ← xem bảng bên dưới
  value_kind:      string
  is_required:     boolean
  sort_order:      int
  rule_min:        int|null
  rule_max:        int|null
  rule_max_select: int|null
  placeholder:     string|null
  options:         Option[]
}

Option {
  id:           int
  option_value: string    ← dùng khi submit
  label:        string
  sort_order:   int
  is_other:     boolean   ← nếu true, cần gửi kèm other_text
}
```

---

### POST `/surveys/{slug}/submit`

Gửi câu trả lời. Rate limit: **60 requests/phút** per token.

**Request body:**

```json
{
  "respondent_ref": "string|null",
  "answers": [
    {
      "field_key":  "string",
      "value":      "<xem bảng kiểu dữ liệu>",
      "other_text": "string|null"
    }
  ]
}
```

**Kiểu dữ liệu `value` theo `field_type`:**

| `field_type` | Kiểu `value` | Ví dụ | Ghi chú |
|---|---|---|---|
| `text` | `string` | `"Nguyễn Văn A"` | — |
| `textarea` | `string` | `"Nhận xét dài..."` | — |
| `number` | `number` | `25` | Không phải string |
| `rating` | `number` | `4` | Phải trong `[rule_min, rule_max]` |
| `date` | `string` | `"2025-05-22"` | Định dạng `YYYY-MM-DD` bắt buộc |
| `boolean` | `boolean` | `true` hoặc `false` | — |
| `select` | `string` | `"option_value_abc"` | Đúng `option_value` từ schema |
| `radio` | `string` | `"option_value_abc"` | Đúng `option_value` từ schema |
| `checkbox` | `string[]` | `["val1", "val2"]` | Mảng, tối đa `rule_max_select` |

**Quy tắc `other_text`:**
- Chỉ cần khi người dùng chọn option có `is_other: true`
- Nếu field đó `is_required: true` và chọn option `is_other`, thì `other_text` không được rỗng
- Trong mọi trường hợp khác, truyền `null` hoặc bỏ qua field

**Response 201:**
```json
{ "response_id": 42 }
```

**Response 422:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "ho_ten":       ["Trường 'Họ và tên' là bắt buộc."],
    "muc_hai_long": ["Lựa chọn 'abc' không hợp lệ cho trường 'muc_hai_long'."],
    "diem_so":      ["Giá trị tối đa cho trường này là 5."]
  }
}
```

---

### GET `/surveys/{slug}/stats`

Thống kê tổng hợp (số responses, phân phối theo option...).

**Response 200:**
```json
{
  "total_responses": 142,
  "fields": {
    "muc_hai_long": {
      "label": "Mức độ hài lòng",
      "type":  "radio",
      "distribution": {
        "rat_hai_long": 85,
        "hai_long":     40,
        "trung_binh":   12,
        "khac":          5
      }
    }
  }
}
```

---

### GET `/surveys/{slug}/responses`

Danh sách responses, phân trang 50/page.

**Query params:**

| Param | Mô tả | Ví dụ |
|---|---|---|
| `respondent_ref` | Lọc theo ref cụ thể | `?respondent_ref=user-001` |
| `from` | Từ ngày (`YYYY-MM-DD`) | `?from=2025-05-01` |
| `to` | Đến ngày | `?to=2025-05-31` |
| `export=xlsx` | Tải file Excel | `?export=xlsx` |

---

## 8. Bước 7 — Tích hợp vào web ngoài (HTML + JS thuần)

Đây là bản tích hợp đầy đủ, copy-paste và thay 3 biến là chạy được.

### 8.1 File cấu hình (backend/server-side)

```js
// config.js — KHÔNG để file này public, đây là server-side config
const CRM_CONFIG = {
  baseUrl:   'https://crm.domain.com/api/v1',
  token:     process.env.CRM_SURVEY_TOKEN,   // lấy từ env
  slug:      process.env.CRM_SURVEY_SLUG,
};
```

> **Nếu không có backend**, token phải hardcode — chấp nhận rủi ro hoặc dùng Turnstile + giới hạn CORS để giảm thiểu.

### 8.2 HTML + CSS cơ bản

```html
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Khảo sát</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: sans-serif; max-width: 640px; margin: 40px auto; padding: 0 16px; }
    .section { margin-bottom: 32px; }
    .section h2 { border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; color: #111827; }
    .field { margin-bottom: 20px; }
    .field label { display: block; font-weight: 600; margin-bottom: 6px; color: #374151; }
    .field input[type="text"],
    .field input[type="number"],
    .field input[type="date"],
    .field select,
    .field textarea {
      width: 100%; padding: 10px 12px; border: 1px solid #d1d5db;
      border-radius: 6px; font-size: 15px;
    }
    .field textarea { min-height: 100px; resize: vertical; }
    .field .options label { font-weight: normal; display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
    .field .other-input { margin-top: 8px; display: none; }
    .field .other-input.visible { display: block; }
    .error-msg { color: #dc2626; font-size: 13px; margin-top: 4px; }
    .required-star { color: #dc2626; }
    button[type="submit"] {
      background: #2563eb; color: white; padding: 12px 32px;
      border: none; border-radius: 6px; font-size: 16px; cursor: pointer;
    }
    button[type="submit"]:disabled { background: #93c5fd; cursor: not-allowed; }
    .success-box {
      background: #dcfce7; border: 1px solid #86efac;
      padding: 20px; border-radius: 8px; text-align: center;
      color: #166534; font-size: 18px;
    }
    .loading { text-align: center; padding: 40px; color: #6b7280; }
  </style>
</head>
<body>

<div id="survey-container">
  <div class="loading" id="loading-state">Đang tải khảo sát...</div>
  <form id="survey-form" style="display:none" novalidate></form>
  <div id="success-state" style="display:none" class="success-box">
    Cảm ơn bạn đã tham gia khảo sát! 🎉
  </div>
</div>

<script>
// ═══════════════════════════════════════════════
// CẤU HÌNH — thay 3 giá trị này
// ═══════════════════════════════════════════════
const CRM_BASE_URL  = 'https://crm.domain.com/api/v1';
const CRM_TOKEN     = 'YOUR_PLAIN_TOKEN_HERE';
const SURVEY_SLUG   = 'your-survey-slug';
// ═══════════════════════════════════════════════

let surveySchema = null;

// ── Khởi động ─────────────────────────────────
async function init() {
  try {
    surveySchema = await fetchSchema();
    renderForm(surveySchema);
    document.getElementById('loading-state').style.display = 'none';
    document.getElementById('survey-form').style.display   = 'block';
  } catch (err) {
    document.getElementById('loading-state').textContent =
      'Không thể tải khảo sát. Vui lòng thử lại.';
    console.error('Survey load error:', err);
  }
}

// ── Lấy schema ────────────────────────────────
async function fetchSchema() {
  const res = await fetch(`${CRM_BASE_URL}/surveys/${SURVEY_SLUG}/schema`, {
    headers: {
      'Authorization': `Bearer ${CRM_TOKEN}`,
      'Accept':        'application/json',
    },
  });
  if (!res.ok) throw new Error(`Schema fetch failed: ${res.status}`);
  return res.json();
}

// ── Render form ───────────────────────────────
function renderForm(schema) {
  const form = document.getElementById('survey-form');
  form.innerHTML = '';

  // Tiêu đề survey
  const title = document.createElement('h1');
  title.textContent = schema.title;
  form.appendChild(title);

  // Render từng section
  schema.sections.forEach(section => {
    const sectionEl = document.createElement('div');
    sectionEl.className = 'section';

    const heading = document.createElement('h2');
    heading.textContent = section.title;
    sectionEl.appendChild(heading);

    section.fields.forEach(field => {
      sectionEl.appendChild(buildField(field));
    });

    form.appendChild(sectionEl);
  });

  // Nút submit
  const submitBtn = document.createElement('button');
  submitBtn.type = 'submit';
  submitBtn.textContent = 'Gửi khảo sát';
  form.appendChild(submitBtn);

  // Bắt sự kiện submit
  form.addEventListener('submit', handleSubmit);
}

// ── Build từng field ──────────────────────────
function buildField(field) {
  const wrapper = document.createElement('div');
  wrapper.className = 'field';
  wrapper.dataset.fieldKey = field.field_key;

  // Label
  const label = document.createElement('label');
  label.htmlFor = `field_${field.field_key}`;
  label.innerHTML = escapeHtml(field.label) +
    (field.is_required ? ' <span class="required-star">*</span>' : '');
  wrapper.appendChild(label);

  // Input theo field_type
  switch (field.field_type) {
    case 'text':
      wrapper.appendChild(buildTextInput(field));
      break;
    case 'textarea':
      wrapper.appendChild(buildTextarea(field));
      break;
    case 'number':
    case 'rating':
      wrapper.appendChild(buildNumberInput(field));
      break;
    case 'date':
      wrapper.appendChild(buildDateInput(field));
      break;
    case 'boolean':
      wrapper.appendChild(buildBooleanInput(field));
      break;
    case 'radio':
      wrapper.appendChild(buildRadioGroup(field));
      break;
    case 'select':
      wrapper.appendChild(buildSelect(field));
      break;
    case 'checkbox':
      wrapper.appendChild(buildCheckboxGroup(field));
      break;
  }

  // Vùng hiển thị lỗi
  const errDiv = document.createElement('div');
  errDiv.className = 'error-msg';
  errDiv.id = `error_${field.field_key}`;
  wrapper.appendChild(errDiv);

  return wrapper;
}

function buildTextInput(field) {
  const input = document.createElement('input');
  input.type = 'text';
  input.id   = `field_${field.field_key}`;
  input.name = field.field_key;
  if (field.placeholder) input.placeholder = field.placeholder;
  if (field.rule_max)    input.maxLength    = field.rule_max;
  return input;
}

function buildTextarea(field) {
  const ta = document.createElement('textarea');
  ta.id   = `field_${field.field_key}`;
  ta.name = field.field_key;
  if (field.placeholder) ta.placeholder = field.placeholder;
  return ta;
}

function buildNumberInput(field) {
  const input = document.createElement('input');
  input.type = 'number';
  input.id   = `field_${field.field_key}`;
  input.name = field.field_key;
  if (field.rule_min !== null) input.min  = field.rule_min;
  if (field.rule_max !== null) input.max  = field.rule_max;
  if (field.placeholder)      input.placeholder = field.placeholder;
  return input;
}

function buildDateInput(field) {
  const input = document.createElement('input');
  input.type = 'date';
  input.id   = `field_${field.field_key}`;
  input.name = field.field_key;
  return input;
}

function buildBooleanInput(field) {
  const wrap = document.createElement('div');
  wrap.className = 'options';
  ['true', 'false'].forEach(val => {
    const lbl = document.createElement('label');
    const inp = document.createElement('input');
    inp.type  = 'radio';
    inp.name  = field.field_key;
    inp.value = val;
    lbl.appendChild(inp);
    lbl.appendChild(document.createTextNode(val === 'true' ? ' Có' : ' Không'));
    wrap.appendChild(lbl);
  });
  return wrap;
}

function buildRadioGroup(field) {
  const wrap = document.createElement('div');
  wrap.className = 'options';

  field.options.forEach(opt => {
    const lbl = document.createElement('label');
    const inp = document.createElement('input');
    inp.type  = 'radio';
    inp.name  = field.field_key;
    inp.value = opt.option_value;

    if (opt.is_other) {
      inp.dataset.isOther = 'true';
      inp.addEventListener('change', () => {
        const otherBox = wrap.querySelector('.other-input');
        if (otherBox) otherBox.classList.toggle('visible', inp.checked);
      });
    }

    lbl.appendChild(inp);
    lbl.appendChild(document.createTextNode(' ' + opt.label));
    wrap.appendChild(lbl);
  });

  // Ô nhập "Khác" (ẩn mặc định)
  if (field.options.some(o => o.is_other)) {
    const otherInput = document.createElement('input');
    otherInput.type        = 'text';
    otherInput.className   = 'other-input';
    otherInput.placeholder = 'Vui lòng nhập...';
    otherInput.dataset.otherFor = field.field_key;
    wrap.appendChild(otherInput);
  }

  return wrap;
}

function buildSelect(field) {
  const select = document.createElement('select');
  select.id   = `field_${field.field_key}`;
  select.name = field.field_key;

  const defaultOpt = document.createElement('option');
  defaultOpt.value       = '';
  defaultOpt.textContent = '-- Chọn --';
  select.appendChild(defaultOpt);

  field.options.forEach(opt => {
    const option = document.createElement('option');
    option.value       = opt.option_value;
    option.textContent = opt.label;
    select.appendChild(option);
  });

  return select;
}

function buildCheckboxGroup(field) {
  const wrap = document.createElement('div');
  wrap.className = 'options';

  field.options.forEach(opt => {
    const lbl = document.createElement('label');
    const inp = document.createElement('input');
    inp.type  = 'checkbox';
    inp.name  = field.field_key;
    inp.value = opt.option_value;

    if (opt.is_other) {
      inp.dataset.isOther = 'true';
      inp.addEventListener('change', () => {
        const otherBox = wrap.querySelector('.other-input');
        if (otherBox) otherBox.classList.toggle('visible', inp.checked);
      });
    }

    lbl.appendChild(inp);
    lbl.appendChild(document.createTextNode(' ' + opt.label));
    wrap.appendChild(lbl);
  });

  if (field.options.some(o => o.is_other)) {
    const otherInput = document.createElement('input');
    otherInput.type        = 'text';
    otherInput.className   = 'other-input';
    otherInput.placeholder = 'Vui lòng nhập...';
    otherInput.dataset.otherFor = field.field_key;
    wrap.appendChild(otherInput);
  }

  return wrap;
}

// ── Thu thập dữ liệu form → answers[] ─────────
function collectAnswers() {
  const answers = [];

  surveySchema.sections.forEach(section => {
    section.fields.forEach(field => {
      const answer = collectFieldAnswer(field);
      if (answer !== null) answers.push(answer);
    });
  });

  return answers;
}

function collectFieldAnswer(field) {
  const key = field.field_key;

  if (['text', 'textarea', 'number', 'rating', 'date'].includes(field.field_type)) {
    const el = document.querySelector(`[name="${key}"]`);
    const val = el ? el.value.trim() : '';
    if (!val && !field.is_required) return null;
    return {
      field_key:  key,
      value:      field.field_type === 'number' || field.field_type === 'rating'
                    ? (val === '' ? null : Number(val))
                    : val,
      other_text: null,
    };
  }

  if (field.field_type === 'boolean') {
    const checked = document.querySelector(`[name="${key}"]:checked`);
    if (!checked) return null;
    return { field_key: key, value: checked.value === 'true', other_text: null };
  }

  if (field.field_type === 'select') {
    const el = document.querySelector(`[name="${key}"]`);
    if (!el || !el.value) return null;
    return { field_key: key, value: el.value, other_text: null };
  }

  if (field.field_type === 'radio') {
    const checked = document.querySelector(`[name="${key}"]:checked`);
    if (!checked) return null;
    let otherText = null;
    if (checked.dataset.isOther) {
      const otherInput = document.querySelector(`[data-other-for="${key}"]`);
      otherText = otherInput ? otherInput.value.trim() || null : null;
    }
    return { field_key: key, value: checked.value, other_text: otherText };
  }

  if (field.field_type === 'checkbox') {
    const checked = [...document.querySelectorAll(`[name="${key}"]:checked`)];
    if (checked.length === 0) return null;
    const values = checked.map(c => c.value);
    let otherText = null;
    if (checked.some(c => c.dataset.isOther)) {
      const otherInput = document.querySelector(`[data-other-for="${key}"]`);
      otherText = otherInput ? otherInput.value.trim() || null : null;
    }
    return { field_key: key, value: values, other_text: otherText };
  }

  return null;
}

// ── Xử lý submit ──────────────────────────────
async function handleSubmit(e) {
  e.preventDefault();
  clearErrors();

  const submitBtn = e.target.querySelector('[type="submit"]');
  submitBtn.disabled     = true;
  submitBtn.textContent  = 'Đang gửi...';

  const payload = {
    respondent_ref: getUserRef(),   // xem hàm bên dưới
    answers:        collectAnswers(),
  };

  try {
    const res = await fetch(`${CRM_BASE_URL}/surveys/${SURVEY_SLUG}/submit`, {
      method:  'POST',
      headers: {
        'Authorization': `Bearer ${CRM_TOKEN}`,
        'Content-Type':  'application/json',
        'Accept':        'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();

    if (res.ok) {
      // Thành công
      document.getElementById('survey-form').style.display    = 'none';
      document.getElementById('success-state').style.display  = 'block';
      console.log('Survey submitted, response_id:', data.response_id);
      return;
    }

    if (res.status === 422 && data.errors) {
      displayErrors(data.errors);
      return;
    }

    if (res.status === 403) {
      alert('Khảo sát này hiện không nhận phản hồi.');
      return;
    }

    alert('Có lỗi xảy ra, vui lòng thử lại.');

  } catch (err) {
    alert('Lỗi kết nối, vui lòng kiểm tra mạng và thử lại.');
    console.error('Submit error:', err);
  } finally {
    submitBtn.disabled    = false;
    submitBtn.textContent = 'Gửi khảo sát';
  }
}

// ── Helpers ───────────────────────────────────
function getUserRef() {
  // Thay bằng logic thực: lấy từ cookie, localStorage, URL param...
  return localStorage.getItem('userId') || null;
}

function displayErrors(errors) {
  Object.entries(errors).forEach(([key, msgs]) => {
    const errDiv = document.getElementById(`error_${key}`);
    if (errDiv) errDiv.textContent = msgs[0];
  });

  // Scroll đến lỗi đầu tiên
  const firstErr = document.querySelector('.error-msg:not(:empty)');
  if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function clearErrors() {
  document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');
}

function escapeHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Khởi chạy ─────────────────────────────────
init();
</script>
</body>
</html>
```

---

## 9. Bước 8 — Tích hợp với React / Vue

### 9.1 Custom hook React (`useSurvey.js`)

```js
// hooks/useSurvey.js
import { useState, useEffect, useCallback } from 'react';

const CRM_BASE_URL = process.env.REACT_APP_CRM_BASE_URL;
const CRM_TOKEN    = process.env.REACT_APP_CRM_SURVEY_TOKEN;

export function useSurvey(slug) {
  const [schema,      setSchema]      = useState(null);
  const [isLoading,   setIsLoading]   = useState(true);
  const [isSubmitting,setIsSubmitting]= useState(false);
  const [errors,      setErrors]      = useState({});
  const [submitted,   setSubmitted]   = useState(false);
  const [error,       setError]       = useState(null);

  useEffect(() => {
    fetch(`${CRM_BASE_URL}/surveys/${slug}/schema`, {
      headers: { Authorization: `Bearer ${CRM_TOKEN}` },
    })
      .then(r => r.ok ? r.json() : Promise.reject(r.status))
      .then(data => { setSchema(data); setIsLoading(false); })
      .catch(err => { setError('Không tải được khảo sát.'); setIsLoading(false); });
  }, [slug]);

  const submit = useCallback(async (answers, respondentRef = null) => {
    setIsSubmitting(true);
    setErrors({});

    try {
      const res = await fetch(`${CRM_BASE_URL}/surveys/${slug}/submit`, {
        method:  'POST',
        headers: {
          Authorization:  `Bearer ${CRM_TOKEN}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ respondent_ref: respondentRef, answers }),
      });

      const data = await res.json();

      if (res.ok) {
        setSubmitted(true);
        return { success: true, responseId: data.response_id };
      }

      if (res.status === 422) {
        setErrors(data.errors || {});
        return { success: false, errors: data.errors };
      }

      throw new Error(`HTTP ${res.status}`);

    } catch (err) {
      setError('Gửi thất bại, vui lòng thử lại.');
      return { success: false };
    } finally {
      setIsSubmitting(false);
    }
  }, [slug]);

  return { schema, isLoading, isSubmitting, errors, submitted, error, submit };
}
```

**Dùng trong component:**

```jsx
// SurveyPage.jsx
import { useState } from 'react';
import { useSurvey } from './hooks/useSurvey';

export default function SurveyPage() {
  const { schema, isLoading, isSubmitting, errors, submitted, submit } =
    useSurvey('khao-sat-do-hai-long-q2-2025');

  const [formData, setFormData] = useState({});

  if (isLoading)  return <p>Đang tải...</p>;
  if (submitted)  return <p>Cảm ơn bạn đã tham gia khảo sát!</p>;
  if (!schema)    return <p>Không tải được khảo sát.</p>;

  const handleSubmit = async (e) => {
    e.preventDefault();

    const answers = Object.entries(formData).map(([field_key, value]) => ({
      field_key,
      value,
      other_text: formData[`${field_key}__other`] || null,
    }));

    await submit(answers, localStorage.getItem('userId'));
  };

  return (
    <form onSubmit={handleSubmit}>
      <h1>{schema.title}</h1>
      {schema.sections.map(section => (
        <div key={section.id}>
          <h2>{section.title}</h2>
          {section.fields.map(field => (
            <div key={field.id}>
              <label>
                {field.label}{field.is_required && <span style={{color:'red'}}> *</span>}
              </label>

              {field.field_type === 'text' && (
                <input
                  type="text"
                  value={formData[field.field_key] || ''}
                  onChange={e => setFormData(p => ({...p, [field.field_key]: e.target.value}))}
                />
              )}

              {field.field_type === 'radio' && field.options.map(opt => (
                <label key={opt.option_value}>
                  <input
                    type="radio"
                    name={field.field_key}
                    value={opt.option_value}
                    onChange={() => setFormData(p => ({...p, [field.field_key]: opt.option_value}))}
                  />
                  {opt.label}
                  {opt.is_other && formData[field.field_key] === opt.option_value && (
                    <input
                      type="text"
                      placeholder="Nhập nội dung khác..."
                      onChange={e => setFormData(p => ({...p, [`${field.field_key}__other`]: e.target.value}))}
                    />
                  )}
                </label>
              ))}

              {errors[field.field_key] && (
                <p style={{color:'red'}}>{errors[field.field_key][0]}</p>
              )}
            </div>
          ))}
        </div>
      ))}
      <button type="submit" disabled={isSubmitting}>
        {isSubmitting ? 'Đang gửi...' : 'Gửi khảo sát'}
      </button>
    </form>
  );
}
```

### 9.2 Vue 3 Composable (`useSurvey.js`)

```js
// composables/useSurvey.js
import { ref, onMounted } from 'vue';

const CRM_BASE_URL = import.meta.env.VITE_CRM_BASE_URL;
const CRM_TOKEN    = import.meta.env.VITE_CRM_SURVEY_TOKEN;

export function useSurvey(slug) {
  const schema      = ref(null);
  const isLoading   = ref(true);
  const isSubmitting= ref(false);
  const errors      = ref({});
  const submitted   = ref(false);

  onMounted(async () => {
    try {
      const res  = await fetch(`${CRM_BASE_URL}/surveys/${slug}/schema`, {
        headers: { Authorization: `Bearer ${CRM_TOKEN}` },
      });
      schema.value   = await res.json();
    } finally {
      isLoading.value = false;
    }
  });

  async function submit(answers, respondentRef = null) {
    isSubmitting.value = true;
    errors.value       = {};

    const res = await fetch(`${CRM_BASE_URL}/surveys/${slug}/submit`, {
      method:  'POST',
      headers: {
        Authorization:  `Bearer ${CRM_TOKEN}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ respondent_ref: respondentRef, answers }),
    });

    const data = await res.json();
    isSubmitting.value = false;

    if (res.ok)           { submitted.value = true; return true; }
    if (res.status === 422) { errors.value = data.errors || {}; return false; }

    throw new Error(`HTTP ${res.status}`);
  }

  return { schema, isLoading, isSubmitting, errors, submitted, submit };
}
```

---

## 10. Bước 9 — Cloudflare Turnstile (chống bot)

Bật khi form public, không yêu cầu đăng nhập.

### 10.1 Lấy key từ Cloudflare

1. Vào [dash.cloudflare.com](https://dash.cloudflare.com) → **Turnstile**
2. Nhấn **Add site** → chọn widget type **Managed**
3. Copy **Site Key** (public) và **Secret Key** (private)

### 10.2 Cấu hình CRM

```bash
# .env của CRM
TURNSTILE_ENABLED=true
TURNSTILE_SITE_KEY=0x4AAAAAAAxxxxxxxx
TURNSTILE_SECRET_KEY=0x4AAAAAAAxxxxxxxx_SECRET
```

```bash
php artisan config:clear
```

### 10.3 Thêm widget vào form web ngoài

```html
<head>
  <!-- Thêm vào <head> -->
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>

<body>
  <form id="survey-form">
    <!-- ... các field ... -->

    <!-- Thêm widget Turnstile vào trước nút submit -->
    <div class="cf-turnstile"
         data-sitekey="0x4AAAAAAAxxxxxxxx"
         data-callback="onTurnstileSuccess"
         data-expired-callback="onTurnstileExpired">
    </div>

    <button type="submit" id="submit-btn" disabled>Gửi khảo sát</button>
  </form>

  <script>
    let turnstileToken = null;

    function onTurnstileSuccess(token) {
      turnstileToken = token;
      document.getElementById('submit-btn').disabled = false;
    }

    function onTurnstileExpired() {
      turnstileToken = null;
      document.getElementById('submit-btn').disabled = true;
    }

    // Thêm cf-turnstile-response vào payload khi submit
    const payload = {
      respondent_ref: getUserRef(),
      'cf-turnstile-response': turnstileToken,   // ← bắt buộc khi Turnstile bật
      answers: collectAnswers(),
    };
  </script>
</body>
```

---

## 11. Bước 10 — Xem dữ liệu phản hồi trong CRM

### 11.1 Danh sách responses

**Survey → tab Responses** — hiển thị tất cả bài nộp với:
- Thời gian nộp
- `respondent_ref` (ID người dùng bên web ngoài)
- IP ẩn danh
- Trạng thái: Complete / Partial

### 11.2 Chi tiết một response

Click vào response → xem từng câu trả lời với label rõ ràng.

### 11.3 Thống kê tổng hợp

**Survey → tab Thống kê** — biểu đồ phân phối theo từng field.

### 11.4 Export Excel

**Survey → Responses → Xuất Excel** — hoặc gọi API:

```bash
curl "https://crm.domain.com/api/v1/surveys/<SLUG>/responses?export=xlsx&from=2025-05-01&to=2025-05-31" \
  -H "Authorization: Bearer <TOKEN>" \
  --output responses.xlsx
```

### 11.5 Lọc theo user cụ thể

Nếu web ngoài truyền `respondent_ref` = user ID, bạn có thể xem lại lịch sử khảo sát của từng người:

```bash
# API
curl "https://crm.domain.com/api/v1/surveys/<SLUG>/responses?respondent_ref=user-001" \
  -H "Authorization: Bearer <TOKEN>"

# Trong CRM UI: Responses → filter theo respondent_ref
```

---

## 12. Xử lý lỗi thường gặp

### Lỗi khi gọi API

| Tình huống | HTTP | Nguyên nhân | Giải pháp |
|---|---|---|---|
| `API token is required` | 401 | Thiếu header Authorization | Thêm `Authorization: Bearer <token>` |
| `Invalid API token` | 401 | Token sai / bị xóa | Kiểm tra lại plain token, tạo token mới |
| `Token is inactive or has expired` | 401 | Token đã revoke hoặc quá hạn | Vào CRM → Tokens → Tạo token mới |
| `Token is not authorized for this survey` | 403 | Token thuộc survey khác | Slug trong URL phải khớp với survey đã tạo token |
| Survey trả 403 khi submit | 403 | Survey ở trạng thái Draft/Closed | Kích hoạt survey trong CRM |
| CORS error trên browser | — | Domain không được phép | Thêm domain vào `config/cors.php` |
| `field_key 'xxx' không tồn tại` | 422 | field_key sai hoặc field bị tắt | Lấy lại schema mới nhất |
| `Trường 'Họ tên' là bắt buộc` | 422 | Bỏ qua field required | Đảm bảo gửi đủ field is_required |

### Lỗi CORS (debug)

```js
// Thêm vào console để kiểm tra
fetch('https://crm.domain.com/api/v1/surveys/test/schema', {
  headers: { Authorization: 'Bearer invalid' }
}).catch(err => {
  // Nếu lỗi "CORS" → server chưa cho phép domain này
  // Nếu lỗi 401 → CORS đã OK, chỉ cần sửa token
  console.log(err.message);
});
```

### Schema cache không cập nhật

Nếu đã sửa survey nhưng API vẫn trả schema cũ (cache 30 phút):

```bash
# Trên server CRM, xóa cache thủ công:
php artisan cache:clear

# Hoặc chờ tối đa 30 phút để cache tự hết hạn
```

---

## 13. Bảo mật & best practices

### Bảo vệ token

```
✅ Đúng:   Lưu token ở server-side (env var), gọi API từ server, không expose cho browser
✅ Đúng:   Giới hạn CORS chỉ cho domain cụ thể
✅ Đúng:   Đặt ngày hết hạn cho token (ví dụ 90 ngày) và rotate định kỳ
✅ Đúng:   Bật Turnstile nếu form hoàn toàn public

❌ Tránh:  Hardcode token trong file JS public (người dùng có thể xem source)
❌ Tránh:  Dùng 1 token cho nhiều dự án khác nhau
❌ Tránh:  Không bao giờ commit token vào git
```

### Server-side proxy (pattern an toàn nhất)

Nếu có backend (Node.js, PHP, Python...), nên dùng pattern này:

```
Browser ──POST /submit-survey──▶ Backend của bạn ──POST /api/v1/.../submit──▶ CRM
                                  (token lưu ở đây,                           
                                   không bao giờ về browser)
```

```js
// backend-proxy.js (Node.js/Express)
app.post('/submit-survey', async (req, res) => {
  const crmRes = await fetch(
    `${process.env.CRM_BASE_URL}/api/v1/surveys/${process.env.CRM_SURVEY_SLUG}/submit`,
    {
      method:  'POST',
      headers: {
        Authorization:  `Bearer ${process.env.CRM_TOKEN}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        respondent_ref: req.user?.id || null,   // lấy từ session của app bạn
        answers:        req.body.answers,
      }),
    }
  );
  const data = await crmRes.json();
  res.status(crmRes.status).json(data);
});
```

### Rate limiting

API giới hạn **60 requests/phút** per token. Nếu form có nhiều user đồng thời submit, cần xử lý lỗi `429`:

```js
if (res.status === 429) {
  // Hiện thông báo "Hệ thống đang bận, vui lòng thử lại sau ít giây"
  await new Promise(r => setTimeout(r, 2000));
  // retry...
}
```

---

## 14. Checklist xuất bản cuối cùng

### Phía CRM

- [ ] `APP_URL` trỏ đúng domain production (không phải `localhost`)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Survey đã được **Kích hoạt** (status = Active)
- [ ] **Token đã tạo**, plain token đã copy và lưu an toàn
- [ ] CORS đã cấu hình đúng domain web ngoài
- [ ] Queue worker đang chạy (supervisor hoặc cronjob)
- [ ] Redis hoặc database cache hoạt động (cho schema cache)
- [ ] Turnstile bật nếu form public: `TURNSTILE_ENABLED=true`

### Phía web ngoài

- [ ] Token lưu trong **env var**, không hardcode trong JS public
- [ ] Gọi `GET /schema` khi page load để render form động
- [ ] Xử lý tất cả `field_type` (text, radio, checkbox, date, boolean...)
- [ ] Hiển thị lỗi `422` đúng field, cuộn đến lỗi đầu tiên
- [ ] Xử lý `other_text` cho option `is_other: true`
- [ ] `respondent_ref` được map với ID người dùng thực (email, user ID...)
- [ ] Nút submit disable trong lúc đang gửi (tránh double-submit)
- [ ] Hiển thị màn hình cảm ơn sau khi submit thành công
- [ ] Test với curl trước khi deploy lên production
- [ ] Turnstile widget thêm vào form nếu CRM đã bật Turnstile

### Test trước khi go-live

```bash
# 1. Test schema
curl -X GET "https://crm.domain.com/api/v1/surveys/<SLUG>/schema" \
  -H "Authorization: Bearer <TOKEN>"

# 2. Test submit hợp lệ
curl -X POST "https://crm.domain.com/api/v1/surveys/<SLUG>/submit" \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"respondent_ref":"test-001","answers":[{"field_key":"ho_ten","value":"Test User","other_text":null}]}'

# 3. Xác nhận response_id xuất hiện trong CRM → Responses

# 4. Test lỗi validation
curl -X POST "https://crm.domain.com/api/v1/surveys/<SLUG>/submit" \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"answers":[]}'
# Phải nhận 422 với message lỗi

# 5. Test token sai
curl -X GET "https://crm.domain.com/api/v1/surveys/<SLUG>/schema" \
  -H "Authorization: Bearer WRONG_TOKEN"
# Phải nhận 401
```

---

*Tài liệu này phản ánh trạng thái code tại thời điểm `2026-05-22`. Mọi thay đổi schema API cần cập nhật tài liệu tương ứng.*

Cache clear không đủ — bug trong code CRM. Hãy xem trực tiếp:

  Read 1 file (ctrl+o to expand)

● Bug: Cache::store('redis')->remember() đang cache object PHP, khi Redis deserialize lại bị __PHP_Incomplete_Class. Cần flush đúng key này:

Lỗi Redis serialize/deserialize trong CRM. Mỗi lần cache miss thì thành công, nhưng lần sau thì cache bị corrupt. Hãy xem SurveySchemaData:

● Bash(find /var/www/html/minhan/Modules/Survey/app/Data -name "SurveySchemaData.php"

Vấn đề thực sự là 2 tầng:
  
  Tầng 1 — Frontend (devminhan): Đã fix trước đó
  - CSP block Alpine.js → thêm unsafe-eval
  - Inline script block → chuyển vào Vite bundle

  Tầng 2 — CRM backend (minhan): Root cause của "Không tải được form"

  BuildSurveySchemaAction cache Spatie Data object (SurveySchemaData) vào Redis. PHP serialize() lưu class name, nhưng khi unserialize() lại thì class không được autoload kịp →
  __PHP_Incomplete_Class → 500.

  Fix: Cache ->toArray() (plain PHP array) thay vì object. Array luôn deserialize được. Khi cần dùng thì SurveySchemaData::from($cached) để reconstruct.
  
  // Trước: caches PHP object → vỡ sau lần 1
  Cache::remember(..., fn() => SurveySchemaData::fromModel($survey))

  // Sau: caches array → stable
  Cache::remember(..., fn() => SurveySchemaData::fromModel($survey)->toArray())
  // rồi reconstruct: SurveySchemaData::from($cached)