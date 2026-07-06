# Đặc tả Hệ thống THUCHOCVN — Lộ trình Hoàn thiện

> **Tài liệu tham chiếu:**
> - *Đề xuất sáng kiến — Hệ thống đánh giá, phát triển và kết nối nguồn nhân lực số* (Lê Thị Hà, THUCHOCVN)
> - *Bộ tiêu chí và phương pháp đánh giá năng lực THUCHOCVN v1.0*
> - *Hệ thống và Phương pháp Xử lý Dữ liệu Đánh giá Năng lực Người dùng* (`docs/scoring/`)
>
> **Phiên bản đặc tả:** v2.0 — so sánh đầy đủ, bổ sung mọi gap so với v1.0

---

## 1. Kiến trúc nghiệp vụ tổng thể

### 1.1 Vòng lặp khép kín 11 bước

Tài liệu gốc mô tả **11 bước**, không phải 9 như đặc tả trước:

```
Bước 1:  Đăng ký tài khoản + xác định vai trò
          ↓
Bước 2:  Xác định vị trí việc làm → gắn Assessment phù hợp
          ↓
Bước 3:  Thực hiện bài đánh giá (multi-source: self/manager/expert/data)
          ↓
Bước 4:  Dữ liệu đánh giá → AI Scoring Engine
          ↓
Bước 5:  Tính điểm năng lực (domain scores + weighted total + maturity level + gap)
          ↓
Bước 6:  Tạo / cập nhật Workforce Digital Twin
          ↓
Bước 7:  Tham gia AI Sandbox (thực hành có kiểm soát)
          ↓
Bước 8:  Đo lường hiệu quả thực hành (Impact Measurement)
          ↓
Bước 9:  Cập nhật Digital Twin từ kết quả Sandbox + Impact
          ↓
Bước 10: Xét và cấp Certification (Human-in-the-Loop)
          ↓
Bước 11: Kết nối việc làm / Career Pathway / Marketplace
          ↑_____________________________________________|
          (theo dõi và phát triển liên tục)
```

### 1.2 Hai khung đánh giá song song

| Khung | Áp dụng cho | Assessment code | Tổng tiêu chí |
|---|---|---|---|
| **TDWCF** — 6 domain cá nhân | Nhân viên, ứng viên, học viên | `TDWCF` | 6 domain × ~10 tiêu chí |
| **5-Pillar Org** — 5 trụ cột tổ chức | Doanh nghiệp, đơn vị | `ORG_5PILLAR` | 5 trụ cột × 25 nhóm × 125 tiêu chí |

### 1.3 Công thức tính điểm nền tảng (từ `docs/scoring/`)

```
Score = Σ (Wi × Fi),  i = 1 → n

Trong đó:
  Wi = Trọng số tiêu chí thứ i (cập nhật động theo Module 170 — Weight Learning)
  Fi = Giá trị đặc trưng từ dữ liệu người dùng (câu trả lời, hành vi, lịch sử)
```

> **Điểm quan trọng từ docs/scoring**: Trọng số Wi ĐƯỢC CẬP NHẬT ĐỘNG qua chu kỳ học dữ liệu (so sánh predicted vs actual). Đây là điểm khác biệt cốt lõi — hệ thống không phải static scoring mà là adaptive scoring.

---

## 2. Gap Analysis — Hiện trạng vs. Yêu cầu

### 2.1 Đã có và đủ dùng

| Module | Hiện trạng | Ghi chú |
|---|---|---|
| `Assessment` Engine | `WeightedDomainAggregation`, `MaturityDetector`, `PainPointDetector`, `RecommendationEngine` | Đủ cơ chế, chỉ cần seed dữ liệu |
| `Survey` | Multi-section, scoring, token, conditions | Cần thêm `source_type` multi-source |
| `WorkflowAutomation` | Engine, trigger, action, condition | Cần thêm triggers năng lực |
| HR backbone | `Employee`, `Department`, `Branch`, `JobTitle`, `Leave`, `OrgChart` | Cần thêm trường năng lực số |
| `KpiGoal` | Tracking, cycle, snapshot, leaderboard | Cần thêm AI impact fields |
| `Marketplace` | Listing, applicant (skills, portfolio, experience) | Cần liên kết workforce profile |
| `PerformanceReview` | Template, criteria, score | Cần liên kết TDWCF domain |
| `KcItem` / `KcCategory` | Knowledge center đầy đủ | Cần liên kết với Recommendation |
| `result_domain_scores` | Lưu điểm từng domain per row (không JSON) | Đúng thiết kế — không cần thay đổi |

### 2.2 Chưa có — cần xây mới

| Hợp phần | Độ ưu tiên | Bảng cần tạo |
|---|---|---|
| Multi-source Assessment weighting | P0 | Mở rộng `survey_responses` + cấu hình trọng số |
| Workforce Digital Twin | P1 | `workforce_profiles`, `workforce_profile_histories` |
| Portfolio cá nhân | P1 | `workforce_portfolios` |
| Certification Framework | P1 | `certification_definitions`, `workforce_certifications` |
| Talent Matching (per job) | P1 | `matching_results`, mở rộng `mkt_listings` |
| AI Impact Measurement | P1 | `ai_impact_snapshots`, mở rộng `kpi_goals` |
| AI Sandbox | P2 | `sandbox_tasks`, `sandbox_submissions`, `sandbox_sessions`, `sandbox_activities` |
| Career Pathway | P2 | `career_pathway_steps` |
| AI Governance | P3 | Mở rộng `audit_logs` |

### 2.3 Sai lệch công thức — cần hiệu chỉnh so với v1.0

| Công thức | Spec v1.0 (sai) | Tài liệu gốc (đúng) |
|---|---|---|
| Matching Score | Năng lực 35% + Chứng nhận 25% + AI Readiness 20% + Kinh nghiệm 20% | Năng lực **40%** + Chứng nhận **20%** + Kinh nghiệm **15%** + AI Readiness **15%** + Career Goal **10%** |
| Composite Score chứng nhận | Assessment 40% + KPI 25% + Sandbox 20% + Portfolio 15% | Assessment **30%** + Sandbox **25%** + Impact **25%** + Portfolio **20%** |
| Sandbox Final Score | Chưa có | Quality **40%** + Productivity **35%** + AI Adoption **25%** |
| Certification threshold | Foundation ≥40, Practitioner ≥60, Professional ≥75, Leader ≥85 | Foundation 40–60, Practitioner 61–75, Professional 76–90, Leader 91–100 |

---

## 3. Phase 0 — Nền tảng cấu hình (không cần module mới)

**Mục tiêu**: Kích hoạt Assessment Engine đang có bằng dữ liệu seed chuẩn + 2 migration mở rộng nhỏ.

### 3.1 Seed TDWCF — Khung năng lực cá nhân

**`TdwcfAssessmentSeeder`** thực hiện tuần tự:

**Bảng `assessments`** — 1 record:
```
assessment_code   : 'TDWCF'
name              : 'Khung năng lực số theo vị trí việc làm (TDWCF v1.0)'
version           : '1.0'
has_scoring       : true
aggregation_model : 'weighted_domain'
classification_type : 'score_band'
is_active         : true
```

**Bảng `assessment_domains`** — 6 records (tổng weight = 1.0000):

| domain_code | label | weight | sort_order | Sub-competencies chính |
|---|---|---|---|---|
| D1_DIGITAL_LITERACY | Digital Literacy — Năng lực số nền tảng | 0.1500 | 1 | D1.1 Thiết bị & phần mềm, D1.2 Cộng tác trực tuyến, D1.3 Bảo mật cá nhân |
| D2_DATA_LITERACY | Data Literacy — Năng lực dữ liệu | 0.1500 | 2 | D2.1 Thu thập & nhập liệu, D2.2 Phân tích & đọc hiểu, D2.3 Trình bày & báo cáo |
| D3_AI_LITERACY | AI Literacy — Năng lực trí tuệ nhân tạo | 0.2000 | 3 | D3.1 Hiểu biết AI, D3.2 Sử dụng công cụ AI, D3.3 Prompt Engineering |
| D4_WORKFLOW | Workflow & Automation — Quy trình và tự động hóa | 0.2000 | 4 | D4.1 Chuẩn hóa & thiết kế quy trình, D4.2 Tự động hóa công việc, D4.3 AI Agent & tích hợp |
| D5_INNOVATION | Innovation & Problem Solving — Đổi mới sáng tạo | 0.1500 | 5 | D5.1 Tư duy phản biện & hệ thống, D5.2 Sáng tạo & đổi mới, D5.3 Giải quyết vấn đề thực tế |
| D6_PERFORMANCE | Work Performance & Impact — Hiệu suất và tác động | 0.1500 | 6 | D6.1 Năng suất lao động, D6.2 Chất lượng công việc, D6.3 Ứng dụng AI, D6.4 Tác động tổ chức |

> **Công thức**: `TDWCF Score = (D1×15%) + (D2×15%) + (D3×20%) + (D4×20%) + (D5×15%) + (D6×15%)`

**Bảng `maturity_levels`** — 5 records:

| level_code | label | min_score | max_score | sort_order |
|---|---|---|---|---|
| DIGITAL_BEGINNER | Digital Beginner — Người mới bắt đầu số | 0 | 20 | 1 |
| DIGITAL_AWARE | Digital Aware — Có nhận thức số | 21 | 40 | 2 |
| DIGITAL_PRACTITIONER | Digital Practitioner — Người thực hành số | 41 | 60 | 3 |
| DIGITAL_PROFESSIONAL | Digital Professional — Chuyên nghiệp số | 61 | 80 | 4 |
| DIGITAL_LEADER | Digital Transformation Leader — Dẫn dắt chuyển đổi số | 81 | 100 | 5 |

**Bảng `score_bands`** — 5 records (dùng cho `classification_type = 'score_band'`):

| band_code | label | min_score | max_score | color_hex |
|---|---|---|---|---|
| BAND_BEGINNER | Sơ cấp | 0 | 20 | #ef4444 |
| BAND_AWARE | Cơ bản | 21 | 40 | #f97316 |
| BAND_PRACTITIONER | Trung cấp | 41 | 60 | #eab308 |
| BAND_PROFESSIONAL | Nâng cao | 61 | 80 | #22c55e |
| BAND_LEADER | Chuyên gia | 81 | 100 | #6366f1 |

---

### 3.2 Seed 5-Pillar Org — Khung đánh giá tổ chức

**`FivePillarAssessmentSeeder`**:

**Bảng `assessments`** — 1 record:
```
assessment_code   : 'ORG_5PILLAR'
name              : 'Khung đánh giá năng lực chuyển đổi số tổ chức (5-Pillar v1.0)'
aggregation_model : 'weighted_domain'
classification_type : 'score_band'
has_scoring       : true
```

**Bảng `assessment_domains`** — 5 records:

| domain_code | label | weight | sort_order | Nhóm tiêu chí (sub-groups) |
|---|---|---|---|---|
| P1_STRATEGY | Chiến lược và Lãnh đạo | 0.2000 | 1 | P1.1 Nhận thức về AI, P1.2 Tầm nhìn & chiến lược, P1.3 Cam kết lãnh đạo, P1.4 Quản trị thay đổi |
| P2_PROCESS | Quy trình và Vận hành | 0.2500 | 2 | P2.1 Chuẩn hóa quy trình, P2.2 Số hóa quy trình, P2.3 Tự động hóa, P2.4 Đo lường & kiểm soát, P2.5 Phối hợp liên phòng, P2.6 Tích hợp hệ thống |
| P3_DATA | Dữ liệu và Quản trị dữ liệu | 0.2000 | 3 | P3.1 Chất lượng dữ liệu (4 tiêu chí), P3.2 Lưu trữ & quản lý, P3.3 Chia sẻ & khai thác, P3.4 Bảo mật dữ liệu (4 tiêu chí), P3.5 Quản trị dữ liệu (4 tiêu chí) |
| P4_PEOPLE | Nguồn Nhân lực | 0.1500 | 4 | P4.1 Năng lực số, P4.2 Năng lực AI (4 tiêu chí), P4.3 Học tập liên tục, P4.4 Khả năng thích ứng, P4.5 Đổi mới sáng tạo, P4.6 Hợp tác & chia sẻ tri thức |
| P5_TECH | Công nghệ và Đổi mới sáng tạo | 0.2000 | 5 | P5.1 Hạ tầng công nghệ, P5.2 Công cụ AI, P5.3 R&D & Pilot, P5.4 Đổi mới sáng tạo, P5.5 Đầu tư công nghệ |

> **Lý do P2 trọng số cao nhất (25%)**: "AI không thể thay thế một quy trình chưa được chuẩn hóa."

**Bảng `maturity_levels`** — 5 records cho ORG_5PILLAR:

| level_code | label | min_score | max_score |
|---|---|---|---|
| ORG_INIT | Khởi đầu — Hoạt động rời rạc, thiếu quy trình | 0 | 20 |
| ORG_FORMING | Hình thành — Có nhận thức, có kế hoạch ban đầu | 21 | 40 |
| ORG_DEVELOPING | Phát triển — Bắt đầu triển khai, có kết quả bước đầu | 41 | 60 |
| ORG_ADVANCED | Nâng cao — Quy trình chuẩn hóa, có cơ chế đo lường | 61 | 80 |
| ORG_LEADING | Dẫn đầu — AI là năng lực cốt lõi, liên tục cải tiến | 81 | 100 |

**Bảng `score_rules`** — Thang điểm 0–5 theo từng tiêu chí (seed mẫu cho P1.1.01 và P2.1.01):

| Ví dụ: P1.1.01 — Mức độ hiểu biết AI của lãnh đạo | Điểm |
|---|---|
| Không biết AI là gì | 0 |
| Biết khái niệm cơ bản | 1 |
| Biết một số công cụ AI | 2 |
| Hiểu cách ứng dụng AI | 3 |
| Hiểu tác động chiến lược | 4 |
| Có khả năng dẫn dắt triển khai AI | 5 |

---

### 3.3 Seed Bộ câu hỏi chuyên biệt — 7 nhóm vị trí

**`SpecializedSurveySetSeeder`** — Seed 7 survey template theo nhóm vị trí (B1–B7):

| Set code | Nhóm đối tượng | Ghi chú |
|---|---|---|
| B1_SALES | Sales / Kinh doanh | Tập trung D3, D4, D6 |
| B2_HR | Nhân sự (HR) | Tập trung D1, D5, D6 |
| B3_FINANCE | Kế toán / Tài chính | Tập trung D2, D4, D6 |
| B4_OPS | Vận hành / Operations | Tập trung D2, D4, D6 |
| B5_IT | IT / Kỹ thuật | Tập trung D3, D4, D5 |
| B6_LEADERSHIP | Lãnh đạo / Quản lý | Tập trung D3, D5, D6 |
| B7_EDUCATION | Giáo dục / Đào tạo | Tập trung D1, D2, D5 |

Migration `add_specialized_set_to_surveys_table`:

```php
$table->string('specialized_set_code', 20)->nullable()->after('assessment_code')
    ->comment('B1_SALES|B2_HR|B3_FINANCE|B4_OPS|B5_IT|B6_LEADERSHIP|B7_EDUCATION');
```

---

### 3.4 Migration mở rộng `survey_responses` — Multi-source support

**Lý do**: Tài liệu yêu cầu 4 nguồn với trọng số riêng:
- Self-Assessment: **25%**
- Manager Assessment: **30%**
- Expert Assessment: **25%**
- Work Performance Data: **20%**

Migration `add_multisource_fields_to_survey_responses_table`:

```php
$table->string('source_type', 20)->nullable()->after('respondent_ref')
    ->comment('self | manager | expert | data');

$table->unsignedBigInteger('subject_user_id')->nullable()->after('source_type')
    ->comment('user_id người được đánh giá — null khi source_type=self');

$table->unsignedBigInteger('evaluator_user_id')->nullable()->after('subject_user_id')
    ->comment('user_id người đánh giá — null khi ẩn danh hoặc source=data');

$table->decimal('source_weight', 4, 2)->nullable()->after('evaluator_user_id')
    ->comment('Trọng số nguồn đánh giá — 0.25|0.30|0.25|0.20 (configurable)');

$table->boolean('requires_human_review')->default(false)->after('source_weight')
    ->comment('True khi độ lệch giữa các nguồn vượt ngưỡng — kích hoạt Human-in-the-Loop');

$table->index(['subject_user_id', 'source_type']);
```

**Logic tổng hợp điểm đa nguồn**:
```
Multi-source Score = Σ(source_score × source_weight)
                   / Σ(source_weight có dữ liệu)

Divergence = MAX(source_score) - MIN(source_score)
Nếu Divergence > 30 điểm → requires_human_review = true
```

---

### 3.5 Migration mở rộng `employees` — Chỉ số năng lực số

```php
$table->decimal('digital_competency_score', 5, 2)->nullable()->after('notes')
    ->comment('Điểm TDWCF tổng hợp — 0.00 đến 100.00');

$table->string('digital_maturity_level', 64)->nullable()->after('digital_competency_score')
    ->comment('level_code: DIGITAL_BEGINNER ... DIGITAL_LEADER');

$table->unsignedBigInteger('latest_assessment_result_id')->nullable()
    ->after('digital_maturity_level')
    ->comment('FK tới assessment_results — kết quả TDWCF mới nhất');

$table->timestamp('last_assessed_at')->nullable()->after('latest_assessment_result_id');

$table->index('digital_maturity_level');
```

**Event listener**: `UpdateEmployeeDigitalCompetencyListener` khi `AssessmentCompleted` với `assessment_code = 'TDWCF'`.

---

## 4. Phase 1 — Workforce Digital Twin, Portfolio & Certification

### 4.1 Bảng `workforce_profiles` — Trái tim Digital Twin

Tổng hợp từ tất cả nguồn thành một hồ sơ sống, cập nhật tự động.

```
workforce_profiles
─────────────────────────────────────────────────────────────────────
id                          bigint unsigned PK
uuid                        char(36) unique
organization_id             bigint unsigned FK → organizations
user_id                     bigint unsigned FK → users
employee_id                 bigint unsigned FK → employees nullable

-- Điểm TDWCF theo domain (snapshot từ kết quả đánh giá mới nhất)
tdwcf_score                 decimal(5,2) nullable    — điểm tổng 0–100
tdwcf_maturity_level        varchar(64) nullable     — level_code
tdwcf_assessed_at           timestamp nullable
score_d1_digital_literacy   decimal(5,2) nullable
score_d2_data_literacy      decimal(5,2) nullable
score_d3_ai_literacy        decimal(5,2) nullable
score_d4_workflow           decimal(5,2) nullable
score_d5_innovation         decimal(5,2) nullable
score_d6_performance        decimal(5,2) nullable

-- Điểm tổng hợp theo mục đích (từ doc digital_twins table)
digital_score               decimal(5,2) nullable    — tổng hợp D1+D2+D3
ai_score                    decimal(5,2) nullable    — = score_d3_ai_literacy
productivity_score          decimal(5,2) nullable    — tổng hợp D4+D6
innovation_score            decimal(5,2) nullable    — = score_d5_innovation
growth_score                decimal(5,2) nullable    — tăng trưởng so với lần đánh giá trước

-- Sandbox & Hoạt động
sandbox_sessions_total      smallint unsigned default 0
sandbox_hours_total         smallint unsigned default 0
sandbox_score_avg           decimal(5,2) nullable    — trung bình điểm sandbox
sandbox_last_completed_at   timestamp nullable

-- Certification
certifications_count        tinyint unsigned default 0
highest_cert_level          varchar(30) nullable     — FOUNDATION|PRACTITIONER|PROFESSIONAL|LEADER
highest_cert_issued_at      timestamp nullable
highest_cert_expires_at     timestamp nullable

-- KPI & Impact
kpi_achievement_avg         decimal(5,2) nullable    — % trung bình hoàn thành KPI
impact_score                decimal(5,2) nullable    — AI Impact Index tổng hợp

-- Career
career_goal                 varchar(200) nullable    — định hướng nghề nghiệp
current_learning_path       varchar(100) nullable    — code lộ trình đang theo

-- Matching
ai_readiness_score          decimal(5,2) nullable    — = (D3 + D4) / 2
workforce_trust_score       decimal(5,2) nullable    — composite uy tín tổng hợp

-- Meta
profile_completeness_pct    tinyint unsigned default 0
created_at, updated_at      timestamp

UNIQUE: (organization_id, user_id)
INDEX: tdwcf_maturity_level
INDEX: ai_readiness_score
INDEX: highest_cert_level
```

**Nguồn dữ liệu và trigger cập nhật**:

| Trường | Nguồn | Trigger |
|---|---|---|
| `tdwcf_score`, `score_d*`, `digital_score`, `ai_score`, `productivity_score`, `innovation_score` | `assessment_results` + `result_domain_scores` | `AssessmentCompleted` |
| `growth_score` | Tính: `tdwcf_score_mới - tdwcf_score_cũ` | `AssessmentCompleted` |
| `kpi_achievement_avg` | `kpi_goals.achievement_pct` | Cuối mỗi chu kỳ KPI |
| `sandbox_*` | `sandbox_sessions` | `SandboxCompleted` |
| `certifications_count`, `highest_cert_level` | `workforce_certifications` | `CertificationIssued` |
| `impact_score` | `ai_impact_snapshots` (AII formula) | `ImpactSnapshotRecorded` |
| `ai_readiness_score` | `(score_d3 + score_d4) / 2` | `AssessmentCompleted` |
| `workforce_trust_score` | Formula tổng hợp xem §4.6 | Mỗi khi có thay đổi |

---

### 4.2 Bảng `workforce_profile_histories`

```
workforce_profile_histories
─────────────────────────────────────────────────────────────────────
id                      bigint unsigned PK
workforce_profile_id    bigint unsigned FK → workforce_profiles
event_type              varchar(30)    — assessment|kpi|sandbox|certification|impact
source_id               bigint unsigned nullable
source_type             varchar(150) nullable    — FQCN model nguồn

tdwcf_score_before      decimal(5,2) nullable
tdwcf_score_after       decimal(5,2) nullable
maturity_level_before   varchar(64) nullable
maturity_level_after    varchar(64) nullable
change_delta            decimal(6,2) nullable    — tdwcf_score_after - tdwcf_score_before

notes                   text nullable
recorded_at             timestamp

INDEX: (workforce_profile_id, event_type)
INDEX: recorded_at
```

---

### 4.3 Bảng `workforce_portfolios` — Hồ sơ minh chứng

Tài liệu yêu cầu Portfolio Score = 20% trong Composite Score chứng nhận.

```
workforce_portfolios
─────────────────────────────────────────────────────────────────────
id                          bigint unsigned PK
uuid                        char(36) unique
organization_id             bigint unsigned FK → organizations
workforce_profile_id        bigint unsigned FK → workforce_profiles
item_type                   varchar(30)
                            — assessment_result|sandbox_result|case_study
                              improvement_report|impact_data|work_sample
title                       varchar(255)
description                 text nullable
evidence_url                varchar(500) nullable    — link file hoặc external
kc_item_id                  bigint unsigned FK → kc_items nullable
                            — liên kết với Knowledge Center nếu đã đăng
approval_status             varchar(20) default 'pending'
                            — pending|approved|rejected
approved_by                 bigint unsigned FK → users nullable
approved_at                 timestamp nullable
rejection_reason            text nullable
sort_order                  smallint unsigned default 0
created_at, updated_at      timestamp

INDEX: (workforce_profile_id, item_type)
INDEX: (workforce_profile_id, approval_status)
```

---

### 4.4 Certification Framework

#### Định nghĩa 4 cấp độ chứng nhận (theo tài liệu)

| level_code | label | Workforce Score | Điều kiện |
|---|---|---|---|
| FOUNDATION | AI Workforce Foundation | 40–60 | Assessment ≥ 40 + hoàn thành Sandbox Foundation |
| PRACTITIONER | AI Workforce Practitioner | 61–75 | Assessment ≥ 61 + KPI ≥ 70% + 1 Case Study |
| PROFESSIONAL | AI Workforce Professional | 76–90 | Assessment ≥ 76 + Sandbox ≥ 20h + Impact score > 0 |
| LEADER | AI Workforce Leader | 91–100 | Assessment ≥ 91 + Portfolio được duyệt |

#### 7 loại chứng nhận theo nhóm vị trí

| cert_type_code | Tên | Vị trí đối tượng |
|---|---|---|
| AI_ADMIN | AI Administrative Officer | Cán bộ hành chính / Văn phòng |
| AI_HR | AI HR Practitioner | Nhân sự |
| AI_SALES | AI Sales Practitioner | Kinh doanh / Sales |
| AI_FINANCE | AI Finance Practitioner | Tài chính / Kế toán |
| AI_DATA | AI Data Operator | Nhập liệu / Xử lý dữ liệu |
| AI_MANAGER | AI Workforce Manager | Quản lý nguồn nhân lực |
| AI_LEADER | AI Transformation Leader | Lãnh đạo chuyển đổi số |

#### Bảng `certification_definitions`

```
certification_definitions
─────────────────────────────────────────────────────────────────────
id                          bigint unsigned PK
uuid                        char(36) unique
organization_id             bigint unsigned FK → organizations nullable (null = global)
cert_code                   varchar(50) unique    — TDWCF_FOUNDATION, AI_SALES_PRACTITIONER, ...
cert_type_code              varchar(30)           — AI_ADMIN|AI_HR|AI_SALES|...
name                        varchar(200)
level_code                  varchar(30)           — FOUNDATION|PRACTITIONER|PROFESSIONAL|LEADER
level_order                 tinyint unsigned      — 1,2,3,4
description                 text nullable
validity_months             tinyint unsigned nullable
                            — Foundation: 24, Practitioner: 24, Professional: 36, Leader: 36

-- Điều kiện cấp (rule-based)
min_workforce_score         decimal(5,2) nullable
min_kpi_achievement_pct     decimal(5,2) nullable
min_sandbox_hours           smallint unsigned nullable
min_sandbox_score           decimal(5,2) nullable
requires_impact_score       boolean default false
requires_portfolio_approval boolean default false

is_active                   boolean default true
created_at, updated_at      timestamp
```

#### Bảng `workforce_certifications`

```
workforce_certifications
─────────────────────────────────────────────────────────────────────
id                          bigint unsigned PK
uuid                        char(36) unique
organization_id             bigint unsigned FK → organizations
workforce_profile_id        bigint unsigned FK → workforce_profiles
cert_definition_id          bigint unsigned FK → certification_definitions

-- Composite Score tại thời điểm cấp (snapshot)
-- Công thức: Assessment×30% + Sandbox×25% + Impact×25% + Portfolio×20%
assessment_score_at_issue   decimal(5,2) nullable    — 30%
sandbox_score_at_issue      decimal(5,2) nullable    — 25%
impact_score_at_issue       decimal(5,2) nullable    — 25%
portfolio_score_at_issue    decimal(5,2) nullable    — 20%
composite_score_at_issue    decimal(5,2) nullable    — điểm tổng hợp cuối cùng

status                      varchar(20) default 'active'    — active|expired|revoked
issued_at                   timestamp
expires_at                  timestamp nullable
revoked_at                  timestamp nullable
revoked_reason              text nullable
certificate_number          varchar(50) unique nullable
qr_code_url                 varchar(500) nullable    — URL để tra cứu chứng nhận công khai
digital_badge_url           varchar(500) nullable
issued_by                   bigint unsigned FK → users nullable
human_reviewer_id           bigint unsigned FK → users nullable
                            — chuyên gia xác nhận (Human-in-the-Loop)
reviewed_at                 timestamp nullable

created_at, updated_at      timestamp

INDEX: (workforce_profile_id, status)
INDEX: expires_at
```

#### Công thức Composite Score (tài liệu gốc, khác với v1.0 spec):

```
Composite Score = (Assessment Score × 30%)
               + (Sandbox Score      × 25%)
               + (Impact Score       × 25%)
               + (Portfolio Score    × 20%)
```

---

### 4.5 Talent Matching — Công thức và bảng dữ liệu

#### Migration mở rộng `mkt_listings`

```php
$table->decimal('required_workforce_score', 5, 2)->nullable()->after('certifications_required')
    ->comment('Điểm TDWCF tối thiểu yêu cầu — 0–100');

$table->string('required_cert_level', 30)->nullable()->after('required_workforce_score')
    ->comment('FOUNDATION|PRACTITIONER|PROFESSIONAL|LEADER');

$table->decimal('required_ai_readiness_score', 5, 2)->nullable()->after('required_cert_level')
    ->comment('AI Readiness Score tối thiểu');

$table->string('required_cert_type_code', 30)->nullable()->after('required_ai_readiness_score')
    ->comment('AI_SALES|AI_HR|AI_FINANCE|... — loại chứng nhận yêu cầu');
```

#### Migration mở rộng `mkt_applicants`

```php
$table->unsignedBigInteger('workforce_profile_id')->nullable()->after('id');
$table->decimal('ai_readiness_score', 5, 2)->nullable()->after('workforce_profile_id');
$table->string('highest_cert_level', 30)->nullable()->after('ai_readiness_score');
$table->string('career_goal', 200)->nullable()->after('highest_cert_level');
$table->index('workforce_profile_id');
```

#### Bảng `matching_results` — Kết quả ghép nối per-job

```
matching_results
─────────────────────────────────────────────────────────────────────
id                          bigint unsigned PK
organization_id             bigint unsigned FK → organizations
workforce_profile_id        bigint unsigned FK → workforce_profiles
mkt_listing_id              bigint unsigned FK → mkt_listings
mkt_applicant_id            bigint unsigned FK → mkt_applicants nullable

-- Điểm thành phần (công thức tài liệu gốc — khác v1.0)
-- Matching Score = Năng lực×40% + Chứng nhận×20% + Kinh nghiệm×15%
--                 + AI Readiness×15% + Career Goal×10%
competency_match            decimal(5,2) nullable    — 40%
certification_match         decimal(5,2) nullable    — 20%
experience_match            decimal(5,2) nullable    — 15%
ai_readiness_match          decimal(5,2) nullable    — 15%
career_goal_match           decimal(5,2) nullable    — 10%
matching_score              decimal(5,2) nullable    — tổng hợp

-- Phân loại mức phù hợp
match_level                 varchar(20) nullable
                            — excellent(90–100)|strong(75–89)|potential(60–74)
                              development(40–59)|not_recommended(<40)

calculated_at               timestamp
status                      varchar(20) default 'pending'    — pending|reviewed|hired|rejected

INDEX: (workforce_profile_id, mkt_listing_id)
INDEX: (matching_score)
```

---

### 4.6 Workforce Trust Score — Công thức tổng hợp

```
Workforce Trust Score = (TDWCF Score   × 30%)
                      + (Certification Score × 25%)    — level: 1→25, 2→50, 3→75, 4→100
                      + (KPI Achievement    × 20%)
                      + (Sandbox Score Avg  × 15%)
                      + (Portfolio Score    × 10%)
```

Cập nhật vào `workforce_profiles.workforce_trust_score` sau mỗi sự kiện thay đổi.

---

## 5. Phase 1b — AI Impact Measurement

**Mục tiêu**: Đo tác động thực tế của AI, tính ROI theo 5 nhóm chỉ số.

### 5.1 Migration mở rộng `kpi_goals`

```php
$table->string('ai_impact_category', 20)->nullable()->after('direction')
    ->comment('learning|productivity|quality|ai_adoption|business');

$table->string('ai_impact_type', 50)->nullable()->after('ai_impact_category')
    ->comment('Loại cụ thể: productivity_gain|error_rate_reduction|time_saving|cost_reduction|...');

$table->decimal('baseline_value', 12, 4)->nullable()->after('ai_impact_type')
    ->comment('Giá trị trước khi áp dụng AI');

$table->decimal('investment_cost', 15, 2)->nullable()->after('baseline_value')
    ->comment('Chi phí đầu tư để đạt KPI');
```

### 5.2 Bảng `ai_impact_snapshots`

5 nhóm chỉ số với trọng số (từ tài liệu gốc):

| impact_category | Tên nhóm | Trọng số trong AII |
|---|---|---|
| learning | Learning Impact — Tác động học tập | 20% |
| productivity | Productivity Impact — Tác động năng suất | 25% |
| quality | Quality Impact — Tác động chất lượng | 20% |
| ai_adoption | AI Adoption Impact — Tác động ứng dụng AI | 15% |
| business | Business Impact — Tác động tổ chức | 20% |

```
ai_impact_snapshots
─────────────────────────────────────────────────────────────────────
id                      bigint unsigned PK
organization_id         bigint unsigned FK → organizations
employee_id             bigint unsigned FK → employees nullable
kpi_goal_id             bigint unsigned FK → kpi_goals nullable

impact_category         varchar(20)      — learning|productivity|quality|ai_adoption|business
impact_type             varchar(50)      — chỉ số cụ thể trong nhóm
baseline_value          decimal(12,4)    — giá trị trước AI
achieved_value          decimal(12,4)    — giá trị đạt được sau AI
improvement_pct         decimal(7,2)     — (achieved-baseline)/baseline × 100

investment_cost         decimal(15,2) default 0
benefit_value           decimal(15,2) default 0
roi_pct                 decimal(7,2) nullable    — (benefit-cost)/cost × 100

period_start            date
period_end              date
notes                   text nullable
created_by              bigint unsigned FK → users nullable
created_at, updated_at  timestamp

INDEX: (organization_id, impact_category)
INDEX: (employee_id, period_start)
```

### 5.3 Công thức AI Impact Index (AII)

```
AII = (Productivity Gain  × 40%)
    + (Quality Improvement × 30%)
    + (Time Saving         × 30%)

Trong đó mỗi thành phần lấy từ ai_impact_snapshots:
  Productivity Gain  = improvement_pct của impact_category='productivity'
  Quality Improvement = improvement_pct của impact_category='quality'
  Time Saving        = improvement_pct của impact_type='time_saving'
```

### 5.4 Công thức Competency Growth Index (CGI)

```
CGI = (tdwcf_score_hiện_tại - tdwcf_score_ban_đầu) / tdwcf_score_ban_đầu × 100%
```

Lưu trong `workforce_profile_histories`: `change_delta` = CGI của từng lần thay đổi.

### 5.5 Công thức Digital Workforce Maturity Index (DWMI)

```
DWMI = TDWCF Score × AI Adoption Rate × Process Digitalization Rate / 10000

Kết quả phân loại:
  0–20  : Khởi đầu
  21–40 : Nhận thức
  41–60 : Thực hành
  61–80 : Chuyên nghiệp
  81–100: Dẫn dắt chuyển đổi
```

---

## 6. Phase 2 — AI Sandbox

**Mục tiêu**: Môi trường thực hành AI có kiểm soát, 5 tier, tự động chấm điểm.

### 6.1 Bảng `sandbox_environments`

```
sandbox_environments
─────────────────────────────────────────────────────────────────────
id                  bigint unsigned PK
uuid                char(36) unique
organization_id     bigint unsigned FK → organizations nullable (null = global)
env_code            varchar(50) unique
name                varchar(200)
type                varchar(30)    — office|data|sales|hr|workflow|leadership
tier                tinyint unsigned default 1    — 1=Foundation → 5=Expert
description         text nullable
is_active           boolean default true
sort_order          tinyint unsigned default 0
created_at, updated_at timestamp
```

> **6 loại môi trường Sandbox**: AI Office, AI Data, AI Sales, AI HR, AI Workflow, AI Leadership

> **5 Tier**: Foundation (Score 40–60) / Practitioner (61–75) / Professional (76–90) / Leader (91–100) / Expert (nghiên cứu/sáng kiến)

### 6.2 Bảng `sandbox_tasks`

Mỗi Sandbox environment có nhiều tasks, mỗi task có scoring rubric riêng.

```
sandbox_tasks
─────────────────────────────────────────────────────────────────────
id                      bigint unsigned PK
uuid                    char(36) unique
sandbox_env_id          bigint unsigned FK → sandbox_environments
target_position_code    varchar(50) nullable    — B1_SALES|B2_HR|... (null = universal)
title                   varchar(255)
instruction             text                    — hướng dẫn chi tiết cho người thực hành
expected_output         text                    — mô tả kết quả mong đợi (không so sánh máy)
scoring_rubric          text                    — tiêu chí chấm điểm (pipe-delimited)
time_limit_minutes      smallint unsigned nullable
ai_tools_allowed        varchar(300) nullable   — pipe-delimited: ChatGPT|Claude|Gemini|...
sort_order              smallint unsigned default 0
is_active               boolean default true
created_at, updated_at  timestamp
```

> **Lưu ý**: `scoring_rubric` và `ai_tools_allowed` dùng pipe-delimited thay vì JSON, nhất quán với quy ước dự án.

### 6.3 Bảng `sandbox_sessions`

```
sandbox_sessions
─────────────────────────────────────────────────────────────────────
id                      bigint unsigned PK
uuid                    char(36) unique
organization_id         bigint unsigned FK → organizations
sandbox_task_id         bigint unsigned FK → sandbox_tasks
workforce_profile_id    bigint unsigned FK → workforce_profiles
user_id                 bigint unsigned FK → users
status                  varchar(20) default 'in_progress'
                        — in_progress|submitted|evaluating|completed|abandoned
started_at              timestamp
submitted_at            timestamp nullable
completed_at            timestamp nullable
duration_minutes        smallint unsigned nullable

-- Kết quả chấm điểm (công thức: Quality×40% + Productivity×35% + AI Adoption×25%)
quality_score           decimal(5,2) nullable    — 40% — chấm theo scoring_rubric
productivity_score      decimal(5,2) nullable    — 35% — tính từ duration vs time_limit
ai_adoption_score       decimal(5,2) nullable    — 25% — phân tích mức độ dùng AI
final_score             decimal(5,2) nullable    — tổng hợp theo công thức
passed                  boolean nullable

evaluator_user_id       bigint unsigned FK → users nullable
evaluated_at            timestamp nullable
feedback                text nullable
created_at, updated_at  timestamp

INDEX: (workforce_profile_id, status)
INDEX: (sandbox_task_id, status)
```

### 6.4 Bảng `sandbox_submissions`

Kết quả nộp bài của người thực hành:

```
sandbox_submissions
─────────────────────────────────────────────────────────────────────
id                      bigint unsigned PK
sandbox_session_id      bigint unsigned FK → sandbox_sessions unique
submitted_content       text            — output thực tế của người dùng (text)
ai_tools_used           varchar(300) nullable    — pipe-delimited
submitted_at            timestamp
```

### 6.5 Bảng `sandbox_activities`

Log chi tiết hành động trong session (dùng để tính ai_adoption_score tự động):

```
sandbox_activities
─────────────────────────────────────────────────────────────────────
id                      bigint unsigned PK
sandbox_session_id      bigint unsigned FK → sandbox_sessions
activity_type           varchar(50)
                        — prompt_used|tool_called|output_generated|error_occurred|iteration
activity_description    varchar(500)    — mô tả ngắn hành động
ai_tool_used            varchar(100) nullable
quality_note            tinyint unsigned nullable    — 0–10 (cho evaluator ghi)
occurred_at             timestamp

INDEX: (sandbox_session_id, activity_type)
```

### 6.6 Công thức chấm điểm Sandbox

```
Final Score = (Quality Score     × 40%)
            + (Productivity Score × 35%)
            + (AI Adoption Score  × 25%)

Productivity Score = MIN(100, (time_limit / duration_minutes) × 100)
                   — 0 nếu duration_minutes > time_limit × 1.5 (quá chậm)

AI Adoption Score = (Số activities có ai_tool_used / Tổng activities) × 100
                    × Hệ số chất lượng AI usage
```

### 6.7 Quy trình vận hành Sandbox

```
1. User chọn sandbox_task phù hợp tier + vị trí
   → Tạo sandbox_session (status=in_progress)

2. User thực hành → ghi sandbox_activities (ai_tool_used, activity_type)

3. User nộp bài → tạo sandbox_submissions, session.status=submitted

4. Chấm điểm tự động:
   - productivity_score: tính từ duration_minutes vs time_limit
   - ai_adoption_score: đếm activities có ai_tool_used
   - quality_score: evaluator chấm hoặc AI Scoring Engine nếu có rubric
   → status=completed, final_score = formula

5. Event SandboxCompleted:
   → Listener cập nhật workforce_profiles.sandbox_* fields
   → Ghi workforce_profile_histories (event_type='sandbox')
   → Kiểm tra điều kiện cấp Certification

6. Nếu đủ điều kiện cert + requires_human_review=false → IssueCertificationAction
   Nếu requires_human_review=true → notification tới chuyên gia xét duyệt
```

---

## 7. Phase 2b — Career Pathway Engine

### 7.1 Bảng `career_pathway_steps`

Lộ trình tự động từ: Foundation → Practitioner → Professional → Leader → Digital Transformation Leader

```
career_pathway_steps
─────────────────────────────────────────────────────────────────────
id                          bigint unsigned PK
organization_id             bigint unsigned FK → organizations nullable
from_level                  varchar(64) nullable    — DIGITAL_BEGINNER|DIGITAL_AWARE|...
to_level                    varchar(64)             — level cần đạt
step_order                  tinyint unsigned
title                       varchar(200)
description                 text nullable
required_cert_code          varchar(50) nullable    — chứng nhận cần đạt để lên level này
recommended_kc_tag          varchar(100) nullable   — tag KcItem được gợi ý học
recommended_sandbox_env_code varchar(50) nullable   — sandbox nên thực hành
estimated_weeks             smallint unsigned nullable
is_active                   boolean default true
created_at, updated_at      timestamp
```

---

## 8. Phase 3 — Nâng cao, Tích hợp & AI Governance

### 8.1 Workflow triggers từ sự kiện năng lực

| Event | Trigger code | Payload | Hành động gợi ý |
|---|---|---|---|
| `AssessmentCompleted` | `assessment.completed` | `assessment_code`, `user_id`, `score` | Gửi kết quả, đề xuất sandbox tier tiếp theo |
| `MaturityLevelChanged` | `employee.maturity_level_changed` | `old_level`, `new_level`, `employee_id` | Notify quản lý, cập nhật lộ trình |
| `CertificationIssued` | `certification.issued` | `cert_level`, `user_id` | Email chúc mừng, cập nhật Marketplace profile |
| `CertificationExpired` | `certification.expired` | `cert_code`, `user_id` | Nhắc gia hạn, mời đánh giá lại |
| `SandboxCompleted` | `sandbox.completed` | `session_id`, `final_score` | Cộng điểm, kiểm tra điều kiện chứng nhận |
| `LowKpiAlert` | `kpi.achievement_below_threshold` | `goal_id`, `achievement_pct` | Đề xuất tập huấn, notify quản lý |
| `HighDivergenceDetected` | `assessment.human_review_required` | `response_id`, `divergence_score` | Alert chuyên gia cần xem xét |

### 8.2 Liên kết Recommendation Engine ↔ Knowledge Center

Migration `add_kc_link_to_recommendation_rules_table`:

```php
$table->unsignedBigInteger('kc_category_id')->nullable()->after('recommendation_text')
    ->comment('FK tới kc_categories — danh mục tài nguyên học tập');

$table->string('kc_item_tag', 100)->nullable()->after('kc_category_id')
    ->comment('Tag KcItem: ai_literacy|prompt_engineering|workflow_design|...');

$table->string('career_pathway_step_code', 50)->nullable()->after('kc_item_tag')
    ->comment('Bước lộ trình nghề nghiệp được đề xuất');
```

### 8.3 Survey — liên kết vị trí việc làm

Migration `add_role_targeting_to_surveys_table`:

```php
$table->string('specialized_set_code', 20)->nullable()->after('assessment_code')
    ->comment('B1_SALES|B2_HR|B3_FINANCE|B4_OPS|B5_IT|B6_LEADERSHIP|B7_EDUCATION');

$table->string('target_role_code', 100)->nullable()->after('specialized_set_code')
    ->comment('job_title.code — khảo sát dành riêng cho vị trí này');

$table->string('target_role_level', 30)->nullable()->after('target_role_code')
    ->comment('junior|senior|lead|manager');
```

### 8.4 Performance Review — liên kết TDWCF domain

Migration `add_tdwcf_domain_to_review_criteria_table`:

```php
$table->string('tdwcf_domain_code', 50)->nullable()->after('name')
    ->comment('D1_DIGITAL_LITERACY...D6_PERFORMANCE — ánh xạ tiêu chí về TDWCF domain');
```

Listener `SyncWorkforceProfileOnPerformanceReviewFinalized`: khi finalize review, tổng hợp điểm theo domain vào `workforce_profiles.score_d*`.

### 8.5 AI Governance — mở rộng `audit_logs` (hoặc bảng riêng)

Migration `add_ai_fields_to_audit_logs_table`:

```php
$table->string('ai_action_type', 30)->nullable()->after('action')
    ->comment('scoring|recommendation|certification_check|matching|null nếu không phải AI');

$table->string('ai_model_used', 50)->nullable()->after('ai_action_type')
    ->comment('GPT-4o|Claude-3.5|Gemini-Pro|Scoring-Engine-v1...');

$table->string('ai_risk_level', 10)->nullable()->after('ai_model_used')
    ->comment('low|medium|high — phân loại rủi ro AI output');

$table->boolean('human_reviewed')->default(false)->after('ai_risk_level')
    ->comment('true nếu đã có người kiểm tra kết quả AI');

$table->unsignedBigInteger('human_reviewer_id')->nullable()->after('human_reviewed');
```

**Nguyên tắc AI Governance (5 nguyên tắc từ tài liệu)**:
1. **Human-Centric**: Mọi quyết định tuyển dụng, chứng nhận, khen thưởng đều cần người phê duyệt
2. **Transparency**: Ghi rõ kết quả nào do AI, kết quả nào do người xác nhận
3. **Accountability**: Truy vết được: ai quyết định / khi nào / model AI nào
4. **Fairness**: Không phân biệt theo giới tính / độ tuổi / địa phương
5. **Privacy**: Dữ liệu mức 3–4 (hồ sơ nhân sự, CCCD, tài khoản) không đưa vào AI công cộng

**Phân loại AI Risk**:
- `low` (soạn email, viết nội dung): Dùng trực tiếp
- `medium` (báo cáo phân tích, gợi ý đào tạo): Kiểm tra ngẫu nhiên
- `high` (đánh giá năng lực, xét chứng nhận, gợi ý tuyển dụng): **Bắt buộc human review**

---

## 9. Tổng hợp DB Schema

### 9.1 Bảng tạo mới

| Bảng | Phase | Mục đích |
|---|---|---|
| `workforce_profiles` | 1 | Digital Twin — hồ sơ năng lực động |
| `workforce_profile_histories` | 1 | Lịch sử thay đổi điểm theo sự kiện |
| `workforce_portfolios` | 1 | Hồ sơ minh chứng cho chứng nhận |
| `certification_definitions` | 1 | Định nghĩa 4 cấp × 7 loại chứng nhận |
| `workforce_certifications` | 1 | Chứng nhận đã cấp (có QR, digital badge) |
| `matching_results` | 1 | Kết quả ghép nối ứng viên ↔ job listing |
| `ai_impact_snapshots` | 1b | Đo lường tác động AI theo 5 nhóm chỉ số |
| `sandbox_environments` | 2 | 6 loại môi trường Sandbox |
| `sandbox_tasks` | 2 | Tasks với scoring rubric và expected output |
| `sandbox_sessions` | 2 | Phiên thực hành, chứa điểm thành phần |
| `sandbox_submissions` | 2 | Bài nộp của người thực hành |
| `sandbox_activities` | 2 | Log hành động trong session |
| `career_pathway_steps` | 2b | Lộ trình thăng tiến từng bước |

### 9.2 Migration sửa đổi bảng hiện có

| Bảng | Phase | Thêm cột |
|---|---|---|
| `survey_responses` | 0 | `source_type`, `subject_user_id`, `evaluator_user_id`, `source_weight`, `requires_human_review` |
| `surveys` | 0 | `specialized_set_code` |
| `employees` | 0 | `digital_competency_score`, `digital_maturity_level`, `latest_assessment_result_id`, `last_assessed_at` |
| `kpi_goals` | 1b | `ai_impact_category`, `ai_impact_type`, `baseline_value`, `investment_cost` |
| `mkt_listings` | 1 | `required_workforce_score`, `required_cert_level`, `required_ai_readiness_score`, `required_cert_type_code` |
| `mkt_applicants` | 1 | `workforce_profile_id`, `ai_readiness_score`, `highest_cert_level`, `career_goal` |
| `recommendation_rules` | 3 | `kc_category_id`, `kc_item_tag`, `career_pathway_step_code` |
| `surveys` | 3 | `target_role_code`, `target_role_level` |
| `review_criteria` | 3 | `tdwcf_domain_code` |
| `audit_logs` | 3 | `ai_action_type`, `ai_model_used`, `ai_risk_level`, `human_reviewed`, `human_reviewer_id` |

### 9.3 Seeder cần tạo

| Seeder | Phase | Nội dung |
|---|---|---|
| `TdwcfAssessmentSeeder` | 0 | Assessment + 6 domains + 5 maturity levels + 5 score bands |
| `FivePillarAssessmentSeeder` | 0 | Assessment ORG_5PILLAR + 5 domains + 5 maturity levels + score rules mẫu (P1.1.01, P2.1.01) |
| `SpecializedSurveySetSeeder` | 0 | 7 survey template B1–B7 với `specialized_set_code` |
| `CertificationDefinitionSeeder` | 1 | 4 cấp × 7 loại = 28 certification definitions (với validity_months) |
| `SandboxEnvironmentSeeder` | 2 | 6 môi trường × 5 tier |
| `SandboxTaskSeeder` | 2 | Tasks mẫu bao gồm CheckVN Pilot (AI OCR, AI Product Description, AI Data Validation, AI Customer Support) |
| `CareerPathwaySeeder` | 2b | Lộ trình Foundation → Leader → DT Leader |

---

## 10. Sơ đồ quan hệ tổng thể (ERD)

```
organizations
  └── users ────────────────────────────────────────────────────────┐
        │                                                            │
        ├── employees                                                │
        │     ├── digital_competency_score (snapshot)               │
        │     └── digital_maturity_level (snapshot)                 │
        │                                                            │
        ├── survey_responses                                         │
        │     ├── source_type (self|manager|expert|data)            │
        │     ├── subject_user_id ──────────────────────────────────┤
        │     ├── evaluator_user_id ────────────────────────────────┤
        │     └── source_weight                                     │
        │                                                            │
        ├── assessment_results                                       │
        │     └── result_domain_scores (D1–D6, per row)             │
        │           └── triggers ───► workforce_profiles            │
        │                                                            │
        └── workforce_profiles (1:1 per org) ◄──────────────────────┘
              ├── workforce_profile_histories
              ├── workforce_portfolios
              ├── workforce_certifications
              │     └── certification_definitions
              ├── sandbox_sessions
              │     ├── sandbox_submissions
              │     └── sandbox_activities
              │     └── FK → sandbox_tasks → sandbox_environments
              └── FK → matching_results → mkt_listings

kpi_goals
  ├── ai_impact_category
  └── ai_impact_snapshots
        └── triggers ───► workforce_profiles.impact_score

mkt_applicants ────────────────► workforce_profiles (link)
mkt_listings
  ├── required_workforce_score
  ├── required_cert_level
  └── matching_results (per applicant per listing)

career_pathway_steps ──────────► workforce_profiles.current_learning_path
recommendation_rules ──────────► kc_categories / kc_items
```

---

## 11. Dashboard & Báo cáo — Spec hiển thị

### 11.1 Dashboard cá nhân (Employee / Learner)

| Widget | Dữ liệu từ | Công thức |
|---|---|---|
| Workforce Score | `workforce_profiles.tdwcf_score` | — |
| Radar Chart 6 Domain | `score_d1` ... `score_d6` | — |
| Maturity Level badge | `tdwcf_maturity_level` | — |
| Competency Growth Index | `workforce_profile_histories` | `(current - initial) / initial × 100%` |
| AI Impact Index | `ai_impact_snapshots` | `AII = Productivity×40% + Quality×30% + Time×30%` |
| Sandbox Progress | `sandbox_sessions` | Hours + avg score |
| Certification Timeline | `workforce_certifications` | Status + expiry |
| Career Pathway | `career_pathway_steps` | % hoàn thành từng bước |

### 11.2 Dashboard tổ chức (Admin)

| Widget | Dữ liệu từ |
|---|---|
| Phân bố Maturity Level | `workforce_profiles` GROUP BY `tdwcf_maturity_level` |
| Gap Analysis theo phòng ban | `result_domain_scores` JOIN `employees.department_id` |
| AI Adoption Rate | `ai_impact_snapshots` WHERE `impact_category='ai_adoption'` |
| ROI Summary | `ai_impact_snapshots.roi_pct` AVG per org |
| Top performers | `workforce_profiles` ORDER BY `tdwcf_score` |
| DWMI — Org Maturity | `TDWCF_avg × AI_Adoption × Process_Digital / 10000` |

### 11.3 Dashboard nhà tuyển dụng (Employer)

| Widget | Dữ liệu từ |
|---|---|
| Matching Results per Listing | `matching_results` by `mkt_listing_id` |
| Talent Pool Distribution | `workforce_profiles` |
| Candidate Score Comparison | `matching_results.competency_match`, `certification_match` |

---

## 12. Multi-tenancy — Scoping theo nhân viên / tổ chức

Toàn bộ dữ liệu mới đều được scope đúng theo hệ thống multi-tenant hiện tại.

### 12.1 Nguyên tắc chung

| Bảng | `organization_id` | `employee_id` | Ghi chú |
|---|---|---|---|
| `workforce_profiles` | Bắt buộc | FK nullable | UNIQUE(org_id, user_id) — 1 hồ sơ/user/org |
| `workforce_profile_histories` | — | — | Qua `workforce_profile_id` |
| `workforce_portfolios` | Bắt buộc | — | Qua `workforce_profile_id` |
| `workforce_certifications` | Bắt buộc | — | Qua `workforce_profile_id` |
| `matching_results` | Bắt buộc | — | Scope cả listing lẫn applicant |
| `ai_impact_snapshots` | Bắt buộc | FK nullable | Có thể gắn cấp org hoặc per-employee |
| `sandbox_sessions` | Bắt buộc | — | Qua `workforce_profile_id` |
| `sandbox_activities` | — | — | Qua `sandbox_session_id` |

**User thuộc nhiều tổ chức** → có nhiều `workforce_profiles` riêng biệt, mỗi profile độc lập và không chia sẻ dữ liệu giữa các tổ chức.

### 12.2 Global templates — Trường hợp `organization_id nullable`

Hai bảng có `organization_id nullable` để hỗ trợ **global template dùng chung**:

| Bảng | `organization_id = null` nghĩa là |
|---|---|
| `certification_definitions` | Định nghĩa chứng nhận toàn cầu (dùng cho mọi org) |
| `sandbox_environments` | Môi trường Sandbox mẫu do THUCHOCVN cung cấp |

**Vấn đề cần xử lý**: `TenantAwareModel` mặc định có global scope lọc theo `organization_id = TenantContext::getOrganizationId()`. Nếu hai model này extend `TenantAwareModel`, query sẽ bỏ sót các record global (`organization_id IS NULL`).

**Giải pháp**: Hai model này **không extend `TenantAwareModel`**, thay vào đó implement scope thủ công:

```php
// CertificationDefinition::class
public function scopeAvailableForOrg(Builder $query, int $orgId): Builder
{
    return $query->where(function ($q) use ($orgId) {
        $q->whereNull('organization_id')
          ->orWhere('organization_id', $orgId);
    });
}

// Dùng khi query:
CertificationDefinition::availableForOrg($orgId)->get();
```

Tương tự áp dụng cho `SandboxEnvironment`. Các bảng còn lại (`workforce_profiles`, `workforce_certifications`, `sandbox_sessions`...) extend `TenantAwareModel` bình thường.

### 12.3 Employee nullable trong workforce_profiles

`employee_id` nullable cho phép 2 loại user có workforce profile:

| Loại user | `employee_id` | Use case |
|---|---|---|
| Nhân viên nội bộ | Có — FK tới `employees` | Đánh giá năng lực nội bộ tổ chức |
| Ứng viên / học viên bên ngoài | NULL | Dùng Marketplace, tìm việc, học tập |

---

## 13. Quy ước lập trình

| Vấn đề | Quy ước |
|---|---|
| Logic nghiệp vụ | Lorisleiva Actions |
| Event-driven | Laravel Events + Listeners |
| Tenant isolation | `organization_id` + `TenantAwareModel` (trừ global templates — xem §12.2) |
| Query | CQRS-lite: `*Query` + `*Handler` |
| Không dùng JSON | Pipe-delimited cho multi-value text; PHP arrays trong Seeder |
| Migrations | `database/migrations/generated/` với số thứ tự tiếp theo |
| render_migration_file.json | Cập nhật đồng bộ mỗi khi thêm bảng mới |
| AI Risk | Mọi action AI risk=high phải có `requires_human_review=true` |
| Adaptive scoring | `WeightRepository` hỗ trợ `weight_version` để cập nhật động Wi |
| Global template query | Dùng `scopeAvailableForOrg()` thay vì `TenantAwareModel` scope — tránh mất record null org |

---

## 13. Checklist tiến độ

### Phase 0 — Nền tảng cấu hình
- [ ] `TdwcfAssessmentSeeder`
- [ ] `FivePillarAssessmentSeeder` (với score_rules mẫu 0–5 cho P1.1.01, P2.1.01)
- [ ] `SpecializedSurveySetSeeder` (B1–B7)
- [ ] Migration `add_multisource_fields_to_survey_responses_table`
- [ ] Migration `add_specialized_set_to_surveys_table`
- [ ] Migration `add_digital_competency_to_employees_table`
- [ ] Listener `UpdateEmployeeDigitalCompetencyListener`

### Phase 1 — Digital Twin, Portfolio & Certification
- [ ] Migration + Model `workforce_profiles`
- [ ] Migration + Model `workforce_profile_histories`
- [ ] Migration + Model `workforce_portfolios`
- [ ] Migration + Model `certification_definitions`
- [ ] Migration + Model `workforce_certifications`
- [ ] Migration + Model `matching_results`
- [ ] Migration `add_workforce_fields_to_mkt_applicants_table`
- [ ] Migration `add_competency_requirements_to_mkt_listings_table`
- [ ] `CertificationDefinitionSeeder` (28 definitions: 4 cấp × 7 loại, với validity_months)
- [ ] Action `CreateWorkforceProfileAction`
- [ ] Action `RecalculateWorkforceProfileAction`
- [ ] Action `IssueCertificationAction` (kiểm tra điều kiện, Human-in-the-Loop flag)
- [ ] Action `CalculateMatchingScoreAction` (5 thành phần: Năng lực 40% + ...)
- [ ] Listener `SyncWorkforceProfileOnAssessmentCompleted`
- [ ] Listener `SyncWorkforceProfileOnKpiCycleClosed`
- [ ] Service `WorkforceTrustScoreService`

### Phase 1b — AI Impact Measurement
- [ ] Migration `add_ai_impact_fields_to_kpi_goals_table`
- [ ] Migration + Model `ai_impact_snapshots`
- [ ] Action `RecordAiImpactSnapshotAction`
- [ ] Service `AiImpactIndexCalculator` (AII formula)
- [ ] Service `CompetencyGrowthIndexCalculator` (CGI formula)
- [ ] Query `GetRoiSummaryQuery`

### Phase 2 — AI Sandbox
- [ ] Migration + Model `sandbox_environments`
- [ ] Migration + Model `sandbox_tasks`
- [ ] Migration + Model `sandbox_sessions`
- [ ] Migration + Model `sandbox_submissions`
- [ ] Migration + Model `sandbox_activities`
- [ ] `SandboxEnvironmentSeeder` (6 loại × 5 tier)
- [ ] `SandboxTaskSeeder` (tasks mẫu + CheckVN Pilot tasks)
- [ ] Action `StartSandboxSessionAction`
- [ ] Action `SubmitSandboxAction`
- [ ] Action `CompleteSandboxSessionAction` (tính 3 thành phần điểm)
- [ ] Listener `UpdateWorkforceProfileOnSandboxCompleted`

### Phase 2b — Career Pathway
- [ ] Migration + Model `career_pathway_steps`
- [ ] `CareerPathwaySeeder`
- [ ] Service `CareerPathwayEngine` (gợi ý bước tiếp theo)

### Phase 3 — Nâng cao
- [ ] 7 Workflow triggers từ sự kiện năng lực
- [ ] Migration `add_kc_link_to_recommendation_rules_table`
- [ ] Migration `add_role_targeting_to_surveys_table`
- [ ] Migration `add_tdwcf_domain_to_review_criteria_table`
- [ ] Migration `add_ai_governance_fields_to_audit_logs_table`
- [ ] Listener `SyncWorkforceProfileOnPerformanceReviewFinalized`
