# Hướng dẫn sử dụng — Hệ thống Chấm điểm Survey (Scoring Module)

> **Đối tượng:** Admin cấu hình khảo sát  
> **Phiên bản:** 1.0 — Module Survey, Laravel 13

---

## Mục lục

1. [Tổng quan hệ thống](#1-tổng-quan-hệ-thống)
2. [Khái niệm cốt lõi](#2-khái-niệm-cốt-lõi)
3. [Sơ đồ pipeline chấm điểm](#3-sơ-đồ-pipeline-chấm-điểm)
4. [Các bảng cơ sở dữ liệu](#4-các-bảng-cơ-sở-dữ-liệu)
5. [Truy cập trang cấu hình](#5-truy-cập-trang-cấu-hình)
6. [Tab 1 — Khai báo cơ bản](#6-tab-1--khai-báo-cơ-bản)
7. [Tab 2 — Domains & Trọng số](#7-tab-2--domains--trọng-số)
8. [Tab 3 — Score Rules (Chấm điểm từng câu)](#8-tab-3--score-rules-chấm-điểm-từng-câu)
9. [Tab 4 — Phân loại kết quả](#9-tab-4--phân-loại-kết-quả)
10. [Tab 5 — Pain Points & Recommendations](#10-tab-5--pain-points--recommendations)
11. [Tab 6 — Roadmap](#11-tab-6--roadmap)
12. [Tab 7 — Review & Lưu](#12-tab-7--review--lưu)
13. [Dry-run — Kiểm tra trước khi Go-live](#13-dry-run--kiểm-tra-trước-khi-go-live)
14. [Quy trình chấm điểm tự động sau submit](#14-quy-trình-chấm-điểm-tự-động-sau-submit)
15. [Ví dụ đầy đủ: Khảo sát AI Readiness](#15-ví-dụ-đầy-đủ-khảo-sát-ai-readiness)
16. [Câu hỏi thường gặp](#16-câu-hỏi-thường-gặp)

---

## 1. Tổng quan hệ thống

Scoring Module cho phép admin **cấu hình công thức chấm điểm** cho bất kỳ survey nào. Sau khi người dùng submit khảo sát, hệ thống tự động:

1. Đọc câu trả lời từ database
2. Tính điểm từng câu (Feature Extraction)
3. Gộp điểm theo domain/section (Aggregation)
4. Phân loại kết quả — band, pass/fail, hoặc persona (Classification)
5. Phát hiện điểm yếu (Pain Points)
6. Đề xuất hành động (Recommendations)
7. Gán lộ trình phát triển (Roadmap)
8. Lưu toàn bộ kết quả để hiển thị cho người dùng

**Quan trọng:** Scoring chạy **bất đồng bộ** qua Queue (job `CalculateSurveyScoreJob`), không làm chậm API submit.

---

## 2. Khái niệm cốt lõi

### 2.1 Assessment Code

Mỗi survey được liên kết với một **assessment** qua trường `assessment_code` (chuỗi định danh duy nhất, snake_case).

```
Survey.slug = "khao-sat-ai-readiness"
       ↓ (auto-derive)
assessment_code = "khao_sat_ai_readiness"
```

Khi lần đầu lưu cấu hình scoring, hệ thống tự tạo `assessment_code` từ slug của survey và gán vào `surveys.assessment_code`. Mọi bảng config đều dùng `assessment_code` làm khóa liên kết (không dùng foreign key số).

---

### 2.2 Domain

**Domain** là nhóm năng lực/lĩnh vực mà khảo sát đánh giá. Điểm của từng câu hỏi được gộp vào domain tương ứng.

Ví dụ: Khảo sát AI Readiness có 4 domains:

| domain_code  | label               | weight | min_score | max_score |
|--------------|---------------------|--------|-----------|-----------|
| `data`       | Dữ liệu & Hạ tầng  | 0.30   | 0         | 30        |
| `process`    | Quy trình & Ops     | 0.25   | 0         | 25        |
| `people`     | Con người & Kỹ năng | 0.25   | 0         | 25        |
| `leadership` | Lãnh đạo & Chiến lược | 0.20 | 0         | 20        |

- **weight**: Tỷ lệ đóng góp vào tổng điểm (0–1). Tổng tất cả weights **phải = 1.00**.
- **min_score / max_score**: Khoảng điểm raw tối thiểu/tối đa mà domain có thể đạt được (dùng để normalize).

---

### 2.3 Score Rule

**Score Rule** định nghĩa cách chấm điểm cho một câu hỏi (field) cụ thể.

Mỗi rule thuộc về một domain và có `question_scoring_type`:

| Kiểu              | Áp dụng cho field           | Cách tính                                              |
|-------------------|-----------------------------|--------------------------------------------------------|
| `none`            | Mọi loại                    | Không chấm điểm, bỏ qua câu này                       |
| `boolean`         | Có/Không, Toggle            | `score_if_true` hoặc `score_if_false`                 |
| `single_choice`   | Radio, Select (chọn 1)      | Điểm theo option được chọn                            |
| `multi_choice`    | Checkbox (chọn nhiều)       | Cộng điểm của tất cả option được chọn (có thể cap)    |
| `numeric_range`   | Number, Rating              | Khớp vào khoảng giá trị → điểm tương ứng             |

---

### 2.4 Signal Flag

**Signal Flag** là cờ boolean đặc biệt được bật/tắt khi câu trả lời đáp ứng điều kiện nhất định.

- **Mục đích:** Phát hiện pattern hành vi cụ thể (có dùng AI tool? đã có data lake? leader cam kết?)
- **Định nghĩa:** Khi config rule, điền tên flag vào ô "Signal flag" (vd: `has_ai_tool`, `has_data_lake`)
- **Sử dụng:** Pain Point Rules dùng signal flag để phát hiện điểm yếu

Ví dụ:
```
Field "Bạn có dùng AI tool không?" (boolean)
  → signal_flag = "has_ai_tool"
  → Khi trả lời "Có": has_ai_tool = TRUE
  → Khi trả lời "Không": has_ai_tool = FALSE
```

---

### 2.5 Aggregation Model

Sau khi tính điểm từng câu, hệ thống **gộp điểm** theo một trong 3 mô hình:

#### `weighted_domain` (Khuyến nghị)
```
RawScore(domain) = Σ điểm các câu thuộc domain đó
NormalizedScore(domain) = (Raw - min_score) / (max_score - min_score) × 100
OverallScore = Σ (NormalizedScore(domain) × weight(domain))
```
→ Điểm tổng [0–100], phản ánh đúng tầm quan trọng tương đối giữa các domain.

#### `flat_sum`
```
OverallScore = Σ tất cả điểm các câu (clamp 0–100)
```
→ Đơn giản, dùng khi không cần phân domain hoặc muốn tính điểm thô.

#### `sectioned`
```
Score(section) = Σ điểm câu trong section đó (độc lập)
```
→ Điểm từng section hiển thị riêng biệt, không tính tổng.

---

### 2.6 Classification Type

Sau khi có Overall Score, hệ thống **phân loại** người dùng vào nhóm:

| Kiểu             | Cách hoạt động                                               |
|------------------|--------------------------------------------------------------|
| `score_band`     | So sánh điểm với các dải (band), gán band_code phù hợp     |
| `pass_fail`      | So sánh điểm với ngưỡng → Đạt hoặc Không đạt               |
| `persona_match`  | So sánh tổ hợp điều kiện → khớp persona có match_score cao nhất |
| `none`           | Chỉ tính điểm, không phân loại                              |

---

### 2.7 Score Band

Dải điểm — dùng cho `score_band` classification:

| band_code   | label              | min_score | max_score |
|-------------|--------------------|-----------|-----------|
| `NOVICE`    | Mới bắt đầu        | 0         | 25        |
| `DEVELOPING`| Đang phát triển    | 26        | 50        |
| `ADVANCED`  | Nâng cao           | 51        | 75        |
| `LEADER`    | Dẫn đầu            | 76        | 100       |

**Lưu ý:** Bands phải liền nhau, không được có khoảng trống (gap) hay chồng lấp (overlap). Ví dụ đúng: `0–25`, `26–50`, `51–75`, `76–100`.

---

### 2.8 Persona

Dùng cho `persona_match` — mô tả nhóm người dùng điển hình với các điều kiện kết hợp:

**Ví dụ persona "Leader AI":**
```
Điều kiện 1: overall_score >= 70
Điều kiện 2: domain "leadership" >= 75
Điều kiện 3: signal_flag "has_ai_tool" = true
```
Hệ thống tính `match_score = số điều kiện thỏa / tổng điều kiện`, chọn persona match_score cao nhất.

**Toán tử điều kiện:** `<`, `<=`, `=`, `>=`, `>`  
**Target type:** `overall`, `domain`, `section`, `signal_flag`

---

### 2.9 Pain Point

**Pain Point Rule** phát hiện điểm yếu cụ thể dựa trên tổ hợp signal flags.

**Cú pháp `required_flags`** (comma-separated, AND logic):
```
has_manual_process,!has_automation     # CÓ quy trình thủ công VÀ KHÔNG có automation
!has_ai_tool,!has_data_lake            # KHÔNG có AI tool VÀ KHÔNG có data lake
has_budget,!has_roadmap                # CÓ budget VÀ KHÔNG có roadmap
```

- Không có prefix `!` → flag phải là `TRUE`
- Có prefix `!` → flag phải là `FALSE` (hoặc không tồn tại)

---

### 2.10 Recommendation Rule

**Recommendation Rule** đề xuất hành động khi điểm một domain thấp hơn ngưỡng:

```
trigger_domain: "data"
threshold_score: 50
→ Khi data domain normalized_score < 50 → kích hoạt recommendation này
```

Recommendations được sắp xếp theo `priority` (số nhỏ = ưu tiên cao hơn).

---

### 2.11 Roadmap

Lộ trình phát triển được gán theo band (hoặc persona). Mỗi band có nhiều phases, mỗi phase có milestones.

```
Band: NOVICE
  └─ Phase 1: "Xây dựng nền tảng dữ liệu" (4 tuần)
       └─ Milestone: Kiểm tra chất lượng data hiện tại
       └─ Milestone: Chọn nền tảng lưu trữ
  └─ Phase 2: "Đào tạo nhân sự" (6 tuần)
       └─ Milestone: Training AI basics
```

---

## 3. Sơ đồ pipeline chấm điểm

```
Người dùng Submit Survey
         │
         ▼
[SubmitSurveyAction]
  Validate → Insert survey_answers → Dispatch CalculateSurveyScoreJob
         │
         ▼  (Queue Worker xử lý bất đồng bộ)
[ScoringEngineService.calculate()]
         │
         ├─ [ScoringConfigLoader] ──── Load config từ DB (assessment + domains + rules + bands + ...)
         │
         ├─ [AnswerReader] ─────────── Đọc survey_answers → map field_key → payload
         │
         ├─ [FeatureExtractor] ─────── Tầng 1: Tính điểm từng câu
         │     ├─ boolean:      score_if_true / score_if_false
         │     ├─ single/multi: Σ option scores (+ min/max cap)
         │     └─ numeric_range: Khớp khoảng → score
         │     → Output: rawScores{domain_code → int}, signalFlags{flag → bool}
         │
         ├─ [WeightRepository] ─────── Load domain weights
         │
         ├─ [AggregationFactory] ───── Tầng 2: Gộp điểm
         │     ├─ weighted_domain: normalize + weighted sum
         │     ├─ flat_sum: tổng thô
         │     └─ sectioned: điểm độc lập từng section
         │     → Output: overallScore (0–100), domainScores{code → {raw, normalized}}
         │
         ├─ [ClassificationFactory] ── Tầng 3: Phân loại
         │     ├─ score_band:     so sánh với score_bands
         │     ├─ pass_fail:      so sánh với passing_score
         │     ├─ persona_match:  khớp điều kiện persona (match_score cao nhất)
         │     └─ none:           bỏ qua
         │
         ├─ [PainPointDetector] ────── Kiểm tra required_flags → pain_point_codes[]
         │
         ├─ [RecommendationEngine] ─── domain_score < threshold → recommendations[]
         │
         ├─ [RoadmapLoader] ────────── Gán phases theo band/persona
         │
         └─ [ResultPersister] ─────── Lưu toàn bộ vào 8 bảng result_*
```

---

## 4. Các bảng cơ sở dữ liệu

### 4.1 Bảng cấu hình (Config Tables)

#### `assessments` — Hub cấu hình chính
| Cột                | Kiểu     | Mô tả                                                |
|--------------------|----------|------------------------------------------------------|
| `assessment_code`  | string   | **PK logic** — khóa liên kết tất cả bảng config     |
| `name`             | string   | Tên assessment (= survey title)                      |
| `version`          | int      | Tăng mỗi lần saveConfig (phục vụ cache invalidation) |
| `is_active`        | boolean  | Có đang dùng không                                   |
| `has_scoring`      | boolean  | Bật/tắt chấm điểm                                   |
| `aggregation_model`| string   | `weighted_domain` / `flat_sum` / `sectioned`         |
| `classification_type`| string | `score_band` / `pass_fail` / `persona_match` / `none`|

#### `assessment_domains` — Khai báo domains
| Cột               | Kiểu    | Mô tả                                   |
|-------------------|---------|-----------------------------------------|
| `assessment_code` | string  | FK logic → assessments                  |
| `domain_code`     | string  | Mã domain (`a-z`, `0-9`, `_`)          |
| `label`           | string  | Tên hiển thị                            |
| `weight`          | float   | Trọng số (0–1, tổng = 1.00)            |
| `min_score`       | int     | Điểm raw tối thiểu có thể đạt          |
| `max_score`       | int     | Điểm raw tối đa có thể đạt             |
| `sort_order`      | int     | Thứ tự hiển thị                         |

#### `score_rules` — Quy tắc chấm điểm từng câu
| Cột                    | Kiểu    | Mô tả                                              |
|------------------------|---------|----------------------------------------------------|
| `assessment_code`      | string  | FK logic → assessments                              |
| `field_key`            | string  | Khớp với `survey_fields.field_key`                 |
| `feature_code`         | string  | Tên feature (mặc định = field_key)                 |
| `domain_code`          | string  | Domain câu này đóng góp vào                        |
| `signal_flag`          | string? | Tên flag (chỉ cho boolean rule)                   |
| `question_scoring_type`| string  | `none/boolean/single_choice/multi_choice/numeric_range` |
| `score_if_true`        | int     | Điểm khi trả lời Có (boolean)                     |
| `score_if_false`       | int     | Điểm khi trả lời Không (boolean)                  |
| `min_score_cap`        | int?    | Điểm tối thiểu sau khi cộng (multi_choice)        |
| `max_score_cap`        | int?    | Điểm tối đa sau khi cộng (multi_choice)           |

#### `score_rule_options` — Điểm từng option (choice fields)
| Cột            | Kiểu   | Mô tả                                    |
|----------------|--------|------------------------------------------|
| `rule_id`      | int    | FK → score_rules                         |
| `option_value` | string | Khớp với `survey_field_options.option_value` |
| `option_label` | string | Nhãn hiển thị                           |
| `score`        | int    | Điểm khi chọn option này               |
| `signal_flag`  | string?| Flag được set TRUE khi chọn option này |
| `sort_order`   | int    | Thứ tự                                  |

#### `score_rule_numeric_ranges` — Khoảng giá trị số
| Cột         | Kiểu   | Mô tả                                      |
|-------------|--------|--------------------------------------------|
| `rule_id`   | int    | FK → score_rules                           |
| `min_value` | float? | Giới hạn dưới (null = không giới hạn)     |
| `max_value` | float? | Giới hạn trên (null = không giới hạn)     |
| `score`     | int    | Điểm khi giá trị rơi vào khoảng này       |
| `signal_flag`| string?| Flag được set TRUE khi match khoảng này  |
| `sort_order`| int    | Thứ tự kiểm tra (match first-win)         |

#### `score_bands` — Dải điểm phân loại
| Cột               | Kiểu   | Mô tả                             |
|-------------------|--------|-----------------------------------|
| `assessment_code` | string | FK logic                          |
| `band_code`       | string | Mã band (`A-Z`, `0-9`, `_`)      |
| `label`           | string | Tên hiển thị                      |
| `description`     | string?| Mô tả band                        |
| `min_score`       | float  | Điểm tối thiểu (inclusive)       |
| `max_score`       | float  | Điểm tối đa (inclusive)          |
| `sort_order`      | int    | Thứ tự sắp xếp                    |

#### `pass_fail_configs` — Cấu hình Pass/Fail
| Cột             | Kiểu   | Mô tả                         |
|-----------------|--------|-------------------------------|
| `assessment_code`| string| FK logic                       |
| `passing_score` | float  | Ngưỡng điểm để đạt           |
| `label_pass`    | string | Nhãn khi đạt (vd: "Đạt")    |
| `label_fail`    | string | Nhãn khi không đạt           |

#### `personas` — Nhóm người dùng
| Cột               | Kiểu   | Mô tả             |
|-------------------|--------|-------------------|
| `assessment_code` | string | FK logic          |
| `persona_code`    | string | Mã persona        |
| `label`           | string | Tên persona       |
| `description`     | string?| Mô tả             |
| `sort_order`      | int    | Thứ tự ưu tiên   |

#### `persona_conditions` — Điều kiện xác định persona
| Cột               | Kiểu   | Mô tả                                              |
|-------------------|--------|----------------------------------------------------|
| `persona_id`      | int    | FK → personas                                      |
| `target_type`     | string | `overall` / `domain` / `section` / `signal_flag`  |
| `target_code`     | string?| domain_code / section_code / flag_name            |
| `operator`        | string | `<` / `<=` / `=` / `>=` / `>`                    |
| `threshold_value` | float? | Giá trị so sánh (dùng với overall/domain/section) |
| `flag_value`      | bool?  | Giá trị cờ cần khớp (dùng với signal_flag)        |

#### `pain_point_rules` — Quy tắc phát hiện điểm yếu
| Cột                | Kiểu   | Mô tả                                         |
|--------------------|--------|-----------------------------------------------|
| `assessment_code`  | string | FK logic                                      |
| `pain_point_code`  | string | Mã điểm yếu                                  |
| `label`            | string | Tên hiển thị                                  |
| `required_flags`   | string | `flag1,flag2,!flag3` (AND, `!` = NOT)        |

#### `recommendation_rules` — Quy tắc đề xuất
| Cột                    | Kiểu   | Mô tả                                      |
|------------------------|--------|--------------------------------------------|
| `assessment_code`      | string | FK logic                                   |
| `recommendation_code`  | string | Mã đề xuất                                |
| `label`                | string | Tiêu đề đề xuất                           |
| `description`          | string?| Nội dung chi tiết                         |
| `trigger_domain`       | string | domain_code cần theo dõi                  |
| `threshold_score`      | float  | Kích hoạt khi domain_score < ngưỡng này  |
| `priority`             | int    | Số nhỏ = ưu tiên cao hơn                 |

#### `roadmap_phases` — Các giai đoạn lộ trình
| Cột               | Kiểu   | Mô tả                                        |
|-------------------|--------|----------------------------------------------|
| `assessment_code` | string | FK logic                                     |
| `band_code`       | string?| Band áp dụng (dùng cho score_band)          |
| `maturity_level`  | string?| Fallback tương thích ngược                   |
| `phase_code`      | string | Mã phase                                     |
| `title`           | string | Tiêu đề giai đoạn                           |
| `description`     | string?| Mô tả                                        |
| `duration_weeks`  | int?   | Thời gian thực hiện (tuần)                  |
| `sort_order`      | int    | Thứ tự trong band                            |

#### `roadmap_milestones` — Các cột mốc trong phase
| Cột        | Kiểu   | Mô tả              |
|------------|--------|--------------------|
| `phase_id` | int    | FK → roadmap_phases|
| `title`    | string | Tên milestone      |
| `sort_order`| int   | Thứ tự             |

---

### 4.2 Bảng kết quả (Result Tables)

Sau mỗi lần chấm điểm, kết quả được lưu vào các bảng `result_*`:

| Bảng                       | Lưu gì                                         |
|----------------------------|------------------------------------------------|
| `survey_results`           | Kết quả tổng: overall_score, assessment_code, maturity_level |
| `result_domain_scores`     | Điểm từng domain: raw_score, normalized_score  |
| `result_signal_flags`      | Flag đã bật: flag_code, flag_value             |
| `result_pain_points`       | Điểm yếu phát hiện: pain_point_code           |
| `result_recommendations`   | Đề xuất được kích hoạt: recommendation_code    |
| `result_roadmap_phases`    | Phase trong lộ trình được gán                  |
| `result_classifications`   | Phân loại: band_code, passed, persona_code     |
| `result_question_scores`   | Điểm từng câu: raw, final, selected_options    |

---

## 5. Truy cập trang cấu hình

1. Vào **Dashboard → Khảo sát** → chọn survey muốn cấu hình
2. Trong sidebar bên trái của trang Edit Survey, click **"Cấu hình Scoring"**
3. URL: `/dashboard/surveys/{survey_id}/scoring`

Nếu survey chưa có scoring, trang sẽ mở với trạng thái rỗng. Nếu đã cấu hình, dữ liệu sẽ được tải tự động.

> Lưu ý: Trang sẽ cảnh báo nếu bạn cố thoát mà chưa lưu.

---

## 6. Tab 1 — Khai báo cơ bản

Đây là tab đầu tiên, thiết lập kiến trúc tổng thể.

### Bật chấm điểm
Toggle **"Bật chấm điểm cho survey này"**. Khi tắt, toàn bộ pipeline scoring bị bỏ qua sau submit.

### Mô hình tổng hợp điểm (Aggregation Model)

| Lựa chọn         | Khi nào dùng                                                          |
|------------------|-----------------------------------------------------------------------|
| **Weighted Domain** | Survey đánh giá nhiều lĩnh vực có tầm quan trọng khác nhau (khuyến nghị) |
| **Flat Sum**     | Survey đơn giản, chỉ cộng thẳng điểm, không cần phân domain         |
| **Sectioned**    | Survey có nhiều phần độc lập, cần xem điểm từng phần riêng biệt     |

### Kiểu phân loại kết quả (Classification Type)

| Lựa chọn         | Khi nào dùng                                            |
|------------------|---------------------------------------------------------|
| **Score Band**   | Phân nhóm theo dải điểm (Thấp/Trung bình/Cao/Xuất sắc) |
| **Pass / Fail**  | Chứng chỉ, đánh giá đạt chuẩn                         |
| **Persona Match**| Nhận diện nhóm hành vi/đặc điểm phức tạp               |
| **Không phân loại** | Chỉ muốn biết điểm, không cần gán nhãn             |

---

## 7. Tab 2 — Domains & Trọng số

> Tab này hiển thị với mọi aggregation model. Cột **Weight** chỉ cần điền khi dùng **Weighted Domain**.

### Thêm domain

Click **"Thêm domain"**, điền các trường:

| Trường      | Bắt buộc | Quy tắc                              | Ví dụ          |
|-------------|----------|--------------------------------------|----------------|
| Domain code | ✓        | Chỉ `a-z`, `0-9`, `_`, không space | `data_infra`   |
| Label       |          | Tên hiển thị cho người dùng         | Hạ tầng Dữ liệu|
| Weight      | ✓ (weighted) | Số từ 0 đến 1, tổng = 1.00     | `0.30`         |
| Min score   | ✓        | Điểm thô tối thiểu có thể đạt      | `0`            |
| Max score   | ✓        | Điểm thô tối đa có thể đạt         | `30`           |

### Tính Weight

**Thanh tiến trình tổng weight** hiển thị real-time. Phải đạt đúng 1.00 mới có thể lưu.

**Cách tính weight gợi ý:**
- Xác định % tầm quan trọng của từng domain
- Chuyển sang số thập phân: 30% → 0.30
- Kiểm tra: 0.30 + 0.25 + 0.25 + 0.20 = 1.00 ✅

### Tính Min/Max Score

Đây là điểm **thô** (raw) mà domain có thể đạt, dùng để chuẩn hóa về [0–100]:

```
Nếu domain "data" có 5 câu, mỗi câu tối đa 6 điểm:
  max_score = 5 × 6 = 30
  min_score = 0

Normalized = (Raw - 0) / (30 - 0) × 100
```

> **Quan trọng:** `min_score` phải **nhỏ hơn** `max_score`. Nếu min = max, hệ thống không thể normalize.

### Sắp xếp domain

Dùng nút **▲▼** để sắp xếp thứ tự hiển thị. Thứ tự này ảnh hưởng đến cách kết quả hiển thị cho người dùng.

---

## 8. Tab 3 — Score Rules (Chấm điểm từng câu)

Tab này hiển thị toàn bộ câu hỏi của survey. Mỗi câu có thể được cấu hình một rule riêng.

### Thanh tiến trình

```
Đã cấu hình: 8/15 câu hỏi  [████████░░░░░░] 53%
```

Câu hỏi nào chưa cấu hình (hoặc để `none`) sẽ **không được tính điểm** nhưng vẫn được lưu câu trả lời.

### Mở rộng câu hỏi

Click vào tên câu hỏi để mở form cấu hình. Icon `✓` (xanh) = đã cấu hình, `○` (xám) = chưa cấu hình.

### Các trường chung

| Trường               | Mô tả                                                          |
|----------------------|----------------------------------------------------------------|
| **Kiểu chấm điểm**  | Chọn `none/boolean/single_choice/multi_choice/numeric_range`  |
| **Domain**           | Điểm câu này đóng góp vào domain nào                         |
| **Feature code**     | Định danh nội bộ (tự điền = field_key, thường không cần sửa) |
| **Signal flag**      | Tên flag boolean (chỉ cho boolean rule)                       |

---

### Rule kiểu Boolean

Dùng cho field loại **Có/Không, Toggle, Boolean**.

```
Score nếu CÓ  = [10]    ← điểm khi trả lời "Có"
Score nếu KHÔNG = [0]   ← điểm khi trả lời "Không"
Signal flag = "has_ai_budget"   ← (tùy chọn)
```

**Ví dụ:**
```
Câu: "Doanh nghiệp có ngân sách riêng cho AI không?"
  → Có:    score_if_true  = 10, signal_flag = "has_ai_budget"
  → Không: score_if_false = 0
```

---

### Rule kiểu Single Choice / Multi Choice

Dùng cho **Radio (chọn 1)** hoặc **Checkbox (chọn nhiều)**.

Mỗi option được gán điểm và tùy chọn signal flag:

| option_value     | Score | Signal flag       |
|------------------|-------|-------------------|
| `using_ai_daily` | 10    | `high_ai_usage`   |
| `using_ai_weekly`| 7     |                   |
| `using_ai_rarely`| 3     |                   |
| `not_using_ai`   | 0     | `low_ai_adoption` |

**Đối với Multi Choice:** Điểm được **cộng dồn** (multi_choice). Có thể đặt cap để giới hạn:
```
Min cap: -5   ← Điểm tối thiểu dù chọn nhiều câu âm
Max cap: 15   ← Điểm tối đa dù chọn đủ câu
```

> **Lưu ý:** `option_value` phải khớp chính xác với giá trị trong survey field options.

---

### Rule kiểu Numeric Range

Dùng cho field **Number, Rating**.

Định nghĩa các khoảng giá trị, mỗi khoảng có điểm riêng. Hệ thống kiểm tra theo thứ tự **từ trên xuống, match đầu tiên thắng**:

| Min value | Max value | Score | Signal flag           |
|-----------|-----------|-------|-----------------------|
| `—`       | `10`      | 0     | `low_employee_count`  |
| `11`      | `50`      | 5     |                       |
| `51`      | `200`     | 10    |                       |
| `201`     | `—`       | 15    | `large_enterprise`    |

- Để trống **Min** = không giới hạn dưới (từ âm vô cực)
- Để trống **Max** = không giới hạn trên (đến dương vô cực)

**Ví dụ:**
```
Câu: "Số nhân viên của doanh nghiệp?" (Number field)
  → 1–10:   score = 0,  flag = "micro_business"
  → 11–50:  score = 5
  → 51–200: score = 10
  → 201+:   score = 15, flag = "large_enterprise"
```

---

## 9. Tab 4 — Phân loại kết quả

Nội dung tab thay đổi tùy theo **Classification Type** đã chọn ở Tab 1.

### 9.1 Score Band

Thêm các band theo thứ tự từ thấp đến cao:

| Label          | Band code   | Min | Max |
|----------------|-------------|-----|-----|
| Mới bắt đầu   | `NOVICE`    | 0   | 25  |
| Đang phát triển| `DEVELOPING`| 26  | 50  |
| Nâng cao       | `ADVANCED`  | 51  | 75  |
| Dẫn đầu        | `LEADER`    | 76  | 100 |

**Quy tắc:**
- Bands phải liền nhau (không gap, không overlap)
- Sai: `0–25`, `27–50` ← gap tại 26
- Đúng: `0–25`, `26–50` ← liền nhau

Hệ thống sẽ cảnh báo nếu có gap khi validate.

---

### 9.2 Pass / Fail

Chỉ cần 3 trường:

| Trường          | Mô tả                      | Ví dụ           |
|-----------------|----------------------------|-----------------|
| Điểm đạt        | Ngưỡng overall_score ≥ X   | `65`            |
| Nhãn khi đạt    | Hiển thị cho user          | "Đạt chứng chỉ"|
| Nhãn khi không  | Hiển thị cho user          | "Chưa đủ tiêu chuẩn"|

```
Nếu overall_score >= 65 → "Đạt chứng chỉ"
Nếu overall_score < 65  → "Chưa đủ tiêu chuẩn"
```

---

### 9.3 Persona Match

Thêm từng persona và định nghĩa điều kiện:

**Thêm persona:**
- `persona_code`: mã định danh (snake_case)
- `label`: tên nhóm

**Thêm điều kiện cho persona:**

| Target type  | Target code     | Operator | Threshold / Flag value |
|--------------|-----------------|----------|------------------------|
| `overall`    | *(bỏ trống)*    | `>=`     | `70`                   |
| `domain`     | `leadership`    | `>=`     | `75`                   |
| `signal_flag`| `has_ai_tool`   | `=`      | `true`                 |
| `domain`     | `data`          | `<`      | `40`                   |

**Cách hoạt động:**
```
Persona "AI Leader" có 3 điều kiện:
  overall >= 70        → thỏa ✓
  leadership >= 75     → thỏa ✓
  has_ai_tool = true   → thỏa ✓
  match_score = 3/3 = 100%

Persona "Beginner" có 2 điều kiện:
  overall < 40         → không thỏa ✗
  has_ai_tool = false  → không thỏa ✗
  match_score = 0/2 = 0%

→ Kết quả: Phân loại = "AI Leader" (match_score cao nhất)
```

---

## 10. Tab 5 — Pain Points & Recommendations

### 10.1 Pain Points

Thêm quy tắc phát hiện điểm yếu:

| Trường           | Ví dụ                            | Mô tả                                   |
|------------------|----------------------------------|-----------------------------------------|
| `pain_point_code`| `no_data_infrastructure`         | Mã định danh (snake_case)               |
| `label`          | Thiếu hạ tầng dữ liệu           | Tên điểm yếu                           |
| `required_flags` | `!has_data_lake,!has_etl_pipeline` | Điều kiện kích hoạt (AND logic)        |

**Ví dụ cú pháp required_flags:**

```
# Chỉ có AI tool nhưng không có data
has_ai_tool,!has_clean_data

# Không có cả AI tool lẫn chiến lược
!has_ai_tool,!has_ai_strategy

# Có ngân sách nhưng không có team
has_budget,!has_ai_team

# Chỉ dùng manual (flag âm)
has_manual_process
```

---

### 10.2 Recommendations

Mỗi recommendation kích hoạt khi điểm một domain thấp hơn ngưỡng:

| Trường                 | Ví dụ                              | Mô tả                                        |
|------------------------|------------------------------------|----------------------------------------------|
| `recommendation_code`  | `improve_data_quality`             | Mã định danh                                 |
| `label`                | Nâng cao chất lượng dữ liệu       | Tiêu đề ngắn                                 |
| `description`          | Đầu tư vào data cleaning...       | Hướng dẫn chi tiết                          |
| `trigger_domain`       | `data`                             | Domain cần theo dõi                         |
| `threshold_score`      | `50`                               | Kích hoạt khi domain_score < 50             |

**Sắp xếp ưu tiên:** Dùng ▲▼ để thay đổi thứ tự. Recommendation được sắp xếp theo priority — số nhỏ hiển thị trước.

---

## 11. Tab 6 — Roadmap

Định nghĩa lộ trình phát triển cho từng band (hoặc persona).

### Cấu trúc

```
Band: NOVICE
  └─ Phase 1 (phase_code: "foundation", 4 tuần)
       └─ Milestone: Kiểm tra chất lượng dữ liệu hiện tại
       └─ Milestone: Lập bản đồ luồng dữ liệu
  └─ Phase 2 (phase_code: "tooling", 8 tuần)
       └─ Milestone: Chọn và triển khai nền tảng lưu trữ

Band: DEVELOPING
  └─ Phase 1 (phase_code: "automation", 6 tuần)
       └─ Milestone: Xây dựng data pipeline cơ bản
```

### Thêm phase

1. Chọn band muốn thêm phase (tab con bên trái)
2. Click **"Thêm phase cho [band_code]"**
3. Điền: Tiêu đề, Phase code, Thời gian (tuần), Mô tả
4. Thêm milestones bằng cách click **"Thêm milestone"**

### Quy tắc

- Mỗi band nên có ít nhất 1 phase
- Phase code phải duy nhất trong một band
- Milestones là text đơn giản, không cần mã hóa

---

## 12. Tab 7 — Review & Lưu

Tab cuối kiểm tra tổng thể trước khi lưu.

### Checklist tự động

Hệ thống kiểm tra và hiển thị trạng thái:
- ✅ **OK**: Đủ điều kiện
- ⚠️ **Cảnh báo**: Khuyến nghị nhưng không bắt buộc
- ❌ **Lỗi**: Phải sửa trước khi lưu

**Các mục quan trọng:**
- Tổng weight domains = 1.00 (bắt buộc với weighted_domain)
- Có ít nhất 1 domain
- Có ít nhất 1 score rule được cấu hình
- Score bands không có gap (với score_band classification)

### Thống kê tóm tắt

| Domains | Rules | Bands | Pain Points | Recommendations |
|---------|-------|-------|-------------|-----------------|
| 4       | 12    | 4     | 5           | 8               |

### Lưu & Kích hoạt

Click **"Lưu & Kích hoạt"** để lưu toàn bộ cấu hình. Hệ thống sẽ:
1. Tạo/cập nhật record trong bảng `assessments`
2. Xóa và tạo lại tất cả bảng config liên quan
3. Tăng `version` của assessment (để invalidate cache)
4. Gán `assessment_code` vào `surveys.assessment_code`

> **Lưu ý:** Nút này bị vô hiệu khi còn lỗi trong checklist.

### Xuất JSON Config

Click **"Xuất JSON config"** để tải toàn bộ cấu hình về dạng file `.json`. Hữu ích để:
- Backup cấu hình
- Chia sẻ giữa các survey tương tự
- Debug khi cần xem raw config

---

## 13. Dry-run — Kiểm tra trước khi Go-live

**Dry-run** cho phép bạn chạy thử pipeline scoring với câu trả lời giả mà không ảnh hưởng đến dữ liệu thực.

### Khi nào dùng?

- Sau khi cấu hình xong, **trước khi kích hoạt survey**
- Kiểm tra xem điểm tính có đúng không
- Test các trường hợp biên (edge cases)

### Cách sử dụng

1. Click nút **"Chạy thử"** ở góc trên phải trang scoring
2. Panel slide-in xuất hiện từ phải
3. Nhập câu trả lời mẫu cho từng câu đã có rule
4. Click **"Chạy thử"**

### Kết quả trả về

```
Domain Scores:
  data:       raw: 18  → 60.0%
  process:    raw: 12  → 48.0%
  people:     raw: 20  → 80.0%
  leadership: raw: 16  → 80.0%

Overall Score: 67.5 / 100

Band: ADVANCED

Pain Points:
  no_etl_pipeline, low_ai_adoption

Recommendations:
  improve_data_pipeline, train_staff
```

> **Lưu ý:** Dry-run chỉ hoạt động sau khi đã lưu cấu hình ít nhất một lần.

---

## 14. Quy trình chấm điểm tự động sau submit

Khi người dùng submit survey qua API, hệ thống tự động:

```
1. API submit nhận request
2. Validate 5 lớp (field_key, type, option, required, constraints)
3. Transaction: tạo survey_response + insert survey_answers
4. Purge stats cache
5. Nếu survey.assessment_code != null:
   → Dispatch CalculateSurveyScoreJob vào Queue
6. Trả về response_id cho client

(Background queue worker):
7. Load scoring config từ DB
8. Chạy toàn bộ pipeline (FeatureExtractor → ... → ResultPersister)
9. Lưu kết quả vào các bảng result_*
10. Log kết quả (response_id, overall_score, classification)
```

### Idempotency (Tính bất biến)

Nếu response đã được chấm điểm, job sẽ **bỏ qua** (không tính lại). Để tính lại bắt buộc, admin dùng tính năng **"Force Recalculate"** trong trang quản lý response.

### Xem kết quả

- **Admin xem:** `/dashboard/surveys/{survey}/responses/{response}/result`
- **Job fail:** Tự retry 3 lần, cách 30 giây. Xem log tại `storage/logs/laravel.log`

---

## 15. Ví dụ đầy đủ: Khảo sát AI Readiness

Đây là ví dụ hoàn chỉnh cho một survey đánh giá mức độ sẵn sàng AI của doanh nghiệp.

### Bước 1: Tab 1 — Khai báo

```
has_scoring:        true
aggregation_model:  weighted_domain
classification_type: score_band
```

### Bước 2: Tab 2 — Domains

| domain_code  | label               | weight | min | max |
|--------------|---------------------|--------|-----|-----|
| `data`       | Dữ liệu & Hạ tầng  | 0.30   | 0   | 30  |
| `process`    | Quy trình & Tự động | 0.25  | 0   | 25  |
| `people`     | Con người & Kỹ năng | 0.25  | 0   | 25  |
| `leadership` | Lãnh đạo & Chiến lược | 0.20| 0   | 20  |

### Bước 3: Tab 3 — Score Rules (trích)

**Câu "Doanh nghiệp có data warehouse không?" (boolean)**
```
domain: data
type: boolean
score_if_true:  15
score_if_false: 0
signal_flag:    has_data_warehouse
```

**Câu "Mức độ sử dụng AI tool?" (radio: daily/weekly/rarely/never)**
```
domain: people
type: single_choice
options:
  daily  → score: 10, flag: high_ai_usage
  weekly → score: 7
  rarely → score: 3
  never  → score: 0, flag: no_ai_usage
```

**Câu "Số nhân viên IT?" (number)**
```
domain: people
type: numeric_range
ranges:
  — to 5    → score: 2
  6 to 20   → score: 6, flag: small_it_team
  21 to 100 → score: 10
  101 to —  → score: 15, flag: large_it_team
```

### Bước 4: Tab 4 — Score Bands

| label             | band_code    | min | max |
|-------------------|--------------|-----|-----|
| Khởi đầu          | `NOVICE`     | 0   | 25  |
| Đang phát triển   | `DEVELOPING` | 26  | 50  |
| Sẵn sàng AI       | `READY`      | 51  | 75  |
| Dẫn đầu AI        | `LEADER`     | 76  | 100 |

### Bước 5: Tab 5 — Pain Points

```
Mã: no_data_foundation
Label: Thiếu nền tảng dữ liệu
Flags: !has_data_warehouse,!has_data_lake

Mã: low_ai_adoption
Label: Chưa ứng dụng AI
Flags: no_ai_usage,!high_ai_usage

Mã: leadership_gap
Label: Thiếu cam kết lãnh đạo
Flags: !has_ai_strategy,!has_ai_budget
```

**Recommendations:**

```
Domain "data" < 50  → "Xây dựng hạ tầng dữ liệu cơ bản" (priority: 1)
Domain "people" < 50 → "Đào tạo kỹ năng AI cho nhân viên" (priority: 2)
Domain "leadership" < 60 → "Xây dựng chiến lược AI cấp C-level" (priority: 1)
```

### Bước 6: Tab 6 — Roadmap

**Band NOVICE:**
```
Phase 1: "Đánh giá hiện trạng" (2 tuần)
  - Kiểm kê data sources hiện có
  - Đánh giá chất lượng dữ liệu
Phase 2: "Xây nền tảng" (8 tuần)
  - Chọn nền tảng data warehouse
  - Triển khai ETL pipeline cơ bản
  - Training team data basics
```

**Band DEVELOPING:**
```
Phase 1: "Thí điểm AI" (4 tuần)
  - Chọn 1-2 use case AI phù hợp
  - Triển khai POC (proof of concept)
Phase 2: "Mở rộng" (12 tuần)
  - Scale POC thành sản phẩm
  - Đào tạo người dùng cuối
```

### Kết quả mẫu cho một response

Người dùng trả lời:
- Có data warehouse: **Có** → data +15, flag has_data_warehouse=TRUE
- Mức dùng AI: **weekly** → people +7
- Số nhân viên IT: **45** → people +10

```
Domain raw scores:
  data:       15 (từ 1 câu boolean)
  process:    8
  people:     17 (7 + 10)
  leadership: 10

Normalized:
  data:       (15-0)/(30-0) × 100 = 50.0%
  process:    (8-0)/(25-0) × 100  = 32.0%
  people:     (17-0)/(25-0) × 100 = 68.0%
  leadership: (10-0)/(20-0) × 100 = 50.0%

Overall = 50×0.30 + 32×0.25 + 68×0.25 + 50×0.20
        = 15 + 8 + 17 + 10 = 50.0

Band: DEVELOPING (26–50) ✓

Pain Points: low_ai_adoption (no_ai_usage=FALSE, high_ai_usage=FALSE → ✓)
Recommendations: Đào tạo kỹ năng AI (people_score=68 ≥ 50, không trigger)
                 Xây dựng hạ tầng (data_score=50 ≥ 50, không trigger)
```

---

## 16. Câu hỏi thường gặp

**Q: Tôi thay đổi cấu hình scoring sau khi đã có responses, kết quả cũ có bị ảnh hưởng không?**

A: Không. Kết quả cũ vẫn giữ nguyên. Chỉ các responses **mới submit sau khi lưu** mới dùng cấu hình mới. Để áp dụng config mới cho responses cũ, dùng tính năng **"Force Recalculate"** trong trang quản lý response.

---

**Q: Tại sao tổng weight của tôi là 0.999 nhưng vẫn báo lỗi?**

A: Hệ thống cho phép sai số ±0.01. Nếu tổng = 0.999, đây thuộc khoảng sai số cho phép và sẽ được chấp nhận. Nếu vẫn báo lỗi, kiểm tra lại từng weight xem có nhập nhầm không.

---

**Q: Câu hỏi Text/Textarea có thể chấm điểm không?**

A: Không trực tiếp. Hệ thống chỉ chấm điểm các kiểu: boolean, single_choice, multi_choice, numeric_range. Câu trả lời dạng text được lưu lại nhưng không tham gia tính điểm. Bạn có thể đặt kiểu = `none` cho các câu text.

---

**Q: Signal flag và Pain Point có quan hệ thế nào?**

A: Signal flag là **trung gian**. Rule → set flag → Pain Point rule đọc flag → kết luận điểm yếu.

```
Rule "Có dùng AI tool?" (boolean)
  → signal_flag = "has_ai_tool"
  
Pain Point "Chưa ứng dụng AI":
  required_flags = "!has_ai_tool"
  → Nếu has_ai_tool = FALSE → kích hoạt pain point này
```

---

**Q: Dry-run báo lỗi "Survey này chưa có cấu hình scoring"?**

A: Bạn cần **lưu cấu hình ít nhất một lần** trước khi dry-run. Click **"Lưu & Kích hoạt"** ở Tab 7, sau đó mới dùng dry-run.

---

**Q: Có thể dùng cùng một assessment_code cho nhiều survey không?**

A: Không khuyến nghị. Mỗi survey nên có assessment_code riêng để tránh xung đột cấu hình.

---

**Q: Recommendation có được kích hoạt khi domain_score = threshold không?**

A: Không. Điều kiện kích hoạt là `domain_score < threshold` (nhỏ hơn, không bao gồm bằng). Nếu muốn kích hoạt khi bằng 50, đặt threshold = 51.

---

**Q: Persona match_score 100% có nghĩa là gì?**

A: Tất cả điều kiện của persona đó đều thỏa mãn. Hệ thống chọn persona có match_score **cao nhất** — nếu nhiều persona cùng 100%, persona có `sort_order` nhỏ hơn sẽ được chọn.
