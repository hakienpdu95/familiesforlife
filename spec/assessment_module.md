# Assessment Module — Đặc tả kỹ thuật v1.0

## Mục lục

1. [Mục tiêu & Phạm vi](#1-mục-tiêu--phạm-vi)
2. [Vị trí trong hệ thống](#2-vị-trí-trong-hệ-thống)
3. [Nguyên tắc thiết kế](#3-nguyên-tắc-thiết-kế)
4. [Database Schema](#4-database-schema)
5. [Models](#5-models)
6. [Contracts — Integration Interface](#6-contracts--integration-interface)
7. [Engine Architecture](#7-engine-architecture)
8. [Actions (Command side)](#8-actions-command-side)
9. [Queries (Query side)](#9-queries-query-side)
10. [Events & Listeners](#10-events--listeners)
11. [Routes & Controllers](#11-routes--controllers)
12. [Views — Admin UI](#12-views--admin-ui)
13. [Jobs](#13-jobs)
14. [Permissions](#14-permissions)
15. [Integration với Survey](#15-integration-với-survey)
16. [Integration với các module tương lai](#16-integration-với-các-module-tương-lai)
17. [Migrations — thứ tự & cấu trúc](#17-migrations--thứ-tự--cấu-trúc)
18. [Thứ tự triển khai](#18-thứ-tự-triển-khai)

---

## 1. Mục tiêu & Phạm vi

### Lý do tách

Module `Survey` hiện đang đảm nhận hai trách nhiệm riêng biệt:
- **Khảo sát** (Survey): Xây dựng form, thu thập phản hồi, quản lý token, webhook
- **Chấm điểm** (Assessment/Scoring): Engine chấm điểm, phân loại maturity, roadmap, recommendations

Việc lẫn lộn vi phạm Single Responsibility Principle và cản trở tái sử dụng engine chấm điểm cho các module khác (Lead scoring theo survey, future HR assessments, v.v.).

### Mục tiêu

- Tách hoàn toàn engine chấm điểm vào module `Assessment` độc lập
- `Survey` chỉ biết: "Survey này có `assessment_code` → dispatch event sau submit → done"
- `Assessment` chỉ biết: "Có một `ScoringSubject` cần chấm điểm → thực hiện pipeline → lưu kết quả"
- Các module khác (`Lead`, `HR`, v.v.) đều có thể implement `ScoringSubjectInterface` để dùng Assessment

### Phạm vi module Assessment

**Bao gồm:**
- Cấu hình Assessment (aggregation model, classification type)
- Domain definitions + weights
- Score rules (boolean / single_choice / multi_choice / numeric_range)
- Score bands + maturity levels
- Persona definitions + matching conditions
- Pain point rules + recommendation rules
- Roadmap phases + milestones
- Config snapshots (versioning)
- Scoring engine pipeline (3 tầng)
- Kết quả chấm điểm (AssessmentResult và các bảng con)
- Admin UI: wizard cấu hình, xem kết quả, reprocess
- API: public result view

**Không bao gồm:**
- Survey builder, fields, options, sections
- Survey responses, answers — Assessment đọc qua Contract
- Webhook dispatch — Survey vẫn tự dispatch
- Lead qualification score — Lead module tự tính

---

## 2. Vị trí trong hệ thống

```
┌─────────────────────────────────────────────────────────────┐
│                     BACKEND MODULES                          │
│                                                              │
│  ┌──────────┐    assessment_code    ┌──────────────────┐    │
│  │  Survey  │ ──────────────────► │   Assessment      │    │
│  │          │                      │                   │    │
│  │ Builder  │  SurveySubmitted     │  ScoringEngine    │    │
│  │ Submit   │ ──────Event────────► │  ResultPersister  │    │
│  │ Token    │                      │  AdminWizard      │    │
│  │ Webhook  │ ◄──AssessmentResult──│                   │    │
│  └──────────┘   (read via join)    └──────────────────┘    │
│                                             │                │
│  ┌──────────┐                              │ fire event      │
│  │   Lead   │                              ▼                │
│  │          │              ┌─────────────────────────┐      │
│  │ LeadScore│              │  WorkflowAutomation     │      │
│  │ (riêng)  │              │  SurveyResultBandTrigger│      │
│  └──────────┘              └─────────────────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

**Dependency flow (một chiều):**
```
Assessment ──reads──► survey_answers, survey_fields (DB tables — không import class Survey)
Survey ──────────────────────────────────────────────────────► Assessment (dispatch job)
Lead ─────────────────────────────────────────────────────────► (không dùng Assessment)
WorkflowAutomation ◄────────────────────────────────────────── Assessment (fire event)
```

---

## 3. Nguyên tắc thiết kế

### AVSA (Actions · Views · Services · APIs)

| Layer | Vai trò trong Assessment |
|-------|--------------------------|
| **Actions** | `RunAssessmentAction`, `SaveAssessmentConfigAction`, `ReprocessAssessmentAction`, `CreateConfigSnapshotAction` |
| **Views** | Wizard cấu hình, trang kết quả, public result page |
| **Services** | `ScoringEngineService`, `ScoringConfigLoader`, các Aggregation/Classification strategies |
| **APIs** | `AssessmentApiController` — JSON config CRUD; `AssessmentResultApiController` — result data |

### CQRS-lite

- **Command (Action):** Thay đổi trạng thái — `RunAssessmentAction`, `SaveAssessmentConfigAction`
- **Query (Handler):** Đọc dữ liệu thuần túy — `GetAssessmentConfigHandler`, `GetAssessmentResultHandler`, `ListAssessmentResultsHandler`
- Không dùng Repository pattern — Query handlers truy cập Model trực tiếp
- Actions sử dụng `lorisleiva/laravel-actions` (`AsAction` trait)

### Laravel Modules (NWIDART)

```
Modules/Assessment/
├── app/
│   ├── Actions/
│   ├── Contracts/
│   ├── Data/
│   ├── Engine/           (thay tên từ Scoring/ để rõ hơn)
│   │   ├── Aggregation/
│   │   ├── Classification/
│   │   └── Contracts/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Models/
│   ├── Providers/
│   └── Queries/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
└── routes/
    ├── web.php
    └── api.php
```

### Không có Cache

Nhất quán với quyết định trong Lead module: không dùng Cache cho đến khi chuyển sang Redis. Mọi query đọc thẳng từ DB.

---

## 4. Database Schema

> Tất cả bảng giữ nguyên tên hiện tại để không phải migrate lại data. Chỉ đổi namespace PHP.

### 4.1 `assessments` — cấu hình tổng quan

```sql
CREATE TABLE assessments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_code     VARCHAR(64) NOT NULL UNIQUE,   -- 'digital_maturity', 'hr_competency'
    name                VARCHAR(255) NOT NULL,
    version             SMALLINT UNSIGNED DEFAULT 1,
    is_active           TINYINT(1) DEFAULT 1,
    has_scoring         TINYINT(1) DEFAULT 1,
    aggregation_model   ENUM('weighted_domain','flat_sum','sectioned') DEFAULT 'weighted_domain',
    classification_type ENUM('score_band','pass_fail','persona_match','none') DEFAULT 'score_band',
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP
);
```

### 4.2 `assessment_domains` — domain & weight

```sql
CREATE TABLE assessment_domains (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL REFERENCES assessments(id),
    domain_code   VARCHAR(64) NOT NULL,
    label         VARCHAR(255) NOT NULL,
    weight        DECIMAL(5,4) DEFAULT 1.0000,  -- tổng weights = 1.0
    sort_order    SMALLINT DEFAULT 0,
    UNIQUE (assessment_id, domain_code)
);
```

### 4.3 `score_rules` — quy tắc chấm từng câu

```sql
CREATE TABLE score_rules (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id  INT UNSIGNED NOT NULL REFERENCES assessments(id),
    field_key      VARCHAR(128) NOT NULL,   -- khớp với survey_fields.field_key
    domain_code    VARCHAR(64),             -- NULL = flat_sum mode
    scoring_type   ENUM('none','boolean','single_choice','multi_choice','numeric_range') DEFAULT 'none',
    max_score      SMALLINT DEFAULT 0,
    weight         DECIMAL(5,4) DEFAULT 1.0,
    UNIQUE (assessment_id, field_key)
);
```

### 4.4 `score_rule_options` — điểm từng lựa chọn

```sql
CREATE TABLE score_rule_options (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_id    INT UNSIGNED NOT NULL REFERENCES score_rules(id) ON DELETE CASCADE,
    option_key VARCHAR(128) NOT NULL,  -- khớp survey_field_options.option_value
    score      SMALLINT NOT NULL DEFAULT 0,
    is_signal  TINYINT(1) DEFAULT 0,
    flag_code  VARCHAR(64)
);
```

### 4.5 `score_rule_numeric_ranges` — điểm dải số

```sql
CREATE TABLE score_rule_numeric_ranges (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_id  INT UNSIGNED NOT NULL REFERENCES score_rules(id) ON DELETE CASCADE,
    min_val  DECIMAL(10,2),
    max_val  DECIMAL(10,2),
    score    SMALLINT NOT NULL DEFAULT 0
);
```

### 4.6 `score_bands` — ngưỡng maturity

```sql
CREATE TABLE score_bands (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id  INT UNSIGNED NOT NULL REFERENCES assessments(id),
    band_code      VARCHAR(64) NOT NULL,  -- 'basic','intermediate','advanced'
    label          VARCHAR(255) NOT NULL,
    min_score      DECIMAL(6,2) NOT NULL,
    max_score      DECIMAL(6,2) NOT NULL,
    description    TEXT,
    color          VARCHAR(16),
    sort_order     SMALLINT DEFAULT 0,
    UNIQUE (assessment_id, band_code)
);
```

### 4.7 `pass_fail_configs` — ngưỡng pass/fail

```sql
CREATE TABLE pass_fail_configs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id    INT UNSIGNED NOT NULL UNIQUE REFERENCES assessments(id),
    pass_threshold   DECIMAL(6,2) NOT NULL,
    pass_label       VARCHAR(128) DEFAULT 'Đạt',
    fail_label       VARCHAR(128) DEFAULT 'Chưa đạt'
);
```

### 4.8 `personas` + `persona_conditions`

```sql
CREATE TABLE personas (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id  INT UNSIGNED NOT NULL REFERENCES assessments(id),
    persona_code   VARCHAR(64) NOT NULL,
    label          VARCHAR(255) NOT NULL,
    description    TEXT,
    sort_order     SMALLINT DEFAULT 0,
    UNIQUE (assessment_id, persona_code)
);

CREATE TABLE persona_conditions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    persona_id    INT UNSIGNED NOT NULL REFERENCES personas(id) ON DELETE CASCADE,
    domain_code   VARCHAR(64) NOT NULL,
    operator      ENUM('gte','lte','eq','between') NOT NULL,
    value_min     DECIMAL(6,2),
    value_max     DECIMAL(6,2)
);
```

### 4.9 `pain_point_rules`

```sql
CREATE TABLE pain_point_rules (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id    INT UNSIGNED NOT NULL REFERENCES assessments(id),
    pain_point_code  VARCHAR(64) NOT NULL,
    label            VARCHAR(255),
    trigger_flag     VARCHAR(64),         -- flag_code từ score_rule_options.flag_code
    trigger_domain   VARCHAR(64),         -- domain_code
    threshold_score  DECIMAL(6,2),        -- domain score < threshold → trigger
    UNIQUE (assessment_id, pain_point_code)
);
```

### 4.10 `recommendation_rules`

```sql
CREATE TABLE recommendation_rules (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id       INT UNSIGNED NOT NULL REFERENCES assessments(id),
    recommendation_code VARCHAR(64) NOT NULL,
    label               VARCHAR(255) NOT NULL,
    description         TEXT,
    priority            SMALLINT DEFAULT 0,
    condition_domain    VARCHAR(64),       -- áp dụng khi domain score thấp
    condition_threshold DECIMAL(6,2),
    UNIQUE (assessment_id, recommendation_code)
);
```

### 4.11 `roadmap_phases` + `roadmap_milestones`

```sql
CREATE TABLE roadmap_phases (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id  INT UNSIGNED NOT NULL REFERENCES assessments(id),
    band_code      VARCHAR(64) NOT NULL,   -- áp dụng cho band nào
    phase_code     VARCHAR(64) NOT NULL,
    title          VARCHAR(255) NOT NULL,
    description    TEXT,
    duration_weeks SMALLINT,
    sort_order     SMALLINT DEFAULT 0,
    UNIQUE (assessment_id, phase_code)
);

CREATE TABLE roadmap_milestones (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phase_id  INT UNSIGNED NOT NULL REFERENCES roadmap_phases(id) ON DELETE CASCADE,
    title     VARCHAR(255) NOT NULL,
    sort_order SMALLINT DEFAULT 0
);
```

### 4.12 `maturity_levels` — legacy fallback

```sql
CREATE TABLE maturity_levels (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id  INT UNSIGNED NOT NULL REFERENCES assessments(id),
    level_code     VARCHAR(64) NOT NULL,
    label          VARCHAR(255) NOT NULL,
    min_score      DECIMAL(6,2),
    UNIQUE (assessment_id, level_code)
);
```

### 4.13 `assessment_results` — kết quả tổng hợp

> Đổi tên từ `survey_results` → `assessment_results` trong migration mới.
> `survey_results` giữ nguyên, alias bằng view nếu cần backward compat.

```sql
CREATE TABLE assessment_results (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_type     VARCHAR(128) NOT NULL,  -- 'SurveyResponse', 'Lead',...
    subject_id       BIGINT UNSIGNED NOT NULL,
    assessment_code  VARCHAR(64) NOT NULL,
    overall_score    DECIMAL(6,2),
    maturity_level   VARCHAR(64),            -- band_code hoặc persona_code (denormalized)
    weight_version   SMALLINT DEFAULT 1,
    calculated_at    TIMESTAMP,
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP,

    UNIQUE (subject_type, subject_id),       -- một subject chỉ có một result (force thì xóa + tạo lại)
    INDEX idx_result_assessment (assessment_code),
    INDEX idx_result_subject (subject_type, subject_id)
);
```

### 4.14 Bảng kết quả con

```sql
-- Domain scores
CREATE TABLE result_domain_scores (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id        BIGINT UNSIGNED NOT NULL REFERENCES assessment_results(id) ON DELETE CASCADE,
    domain_code      VARCHAR(64) NOT NULL,
    raw_score        DECIMAL(8,2),
    normalized_score DECIMAL(6,2),
    INDEX (result_id)
);

-- Signal flags phát hiện trong quá trình chấm
CREATE TABLE result_signal_flags (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id  BIGINT UNSIGNED NOT NULL REFERENCES assessment_results(id) ON DELETE CASCADE,
    flag_code  VARCHAR(64) NOT NULL,
    flag_value TINYINT(1) DEFAULT 1,
    INDEX (result_id)
);

-- Pain points được phát hiện
CREATE TABLE result_pain_points (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id        BIGINT UNSIGNED NOT NULL REFERENCES assessment_results(id) ON DELETE CASCADE,
    pain_point_code  VARCHAR(64) NOT NULL,
    INDEX (result_id)
);

-- Recommendations
CREATE TABLE result_recommendations (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id            BIGINT UNSIGNED NOT NULL REFERENCES assessment_results(id) ON DELETE CASCADE,
    recommendation_code  VARCHAR(64) NOT NULL,
    priority             SMALLINT DEFAULT 0,
    INDEX (result_id)
);

-- Roadmap phases
CREATE TABLE result_roadmap_phases (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id  BIGINT UNSIGNED NOT NULL REFERENCES assessment_results(id) ON DELETE CASCADE,
    phase_id   INT UNSIGNED REFERENCES roadmap_phases(id),
    sort_order SMALLINT DEFAULT 0,
    INDEX (result_id)
);

-- Classification (band / pass-fail / persona)
CREATE TABLE result_classifications (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id           BIGINT UNSIGNED NOT NULL UNIQUE REFERENCES assessment_results(id) ON DELETE CASCADE,
    classification_type VARCHAR(32),
    band_code           VARCHAR(64),
    passed              TINYINT(1),
    persona_code        VARCHAR(64),
    match_score         DECIMAL(6,2),
    label               VARCHAR(255)
);

-- Per-question scores (optional, for debug/audit)
CREATE TABLE result_question_scores (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id        BIGINT UNSIGNED NOT NULL REFERENCES assessment_results(id) ON DELETE CASCADE,
    question_code    VARCHAR(128) NOT NULL,
    feature_code     VARCHAR(128),
    raw_score        DECIMAL(8,2),
    final_score      DECIMAL(8,2),
    selected_option  VARCHAR(128),   -- option_key của lựa chọn được chọn (single choice)
    INDEX (result_id),
    INDEX (result_id, question_code)
);

-- Lựa chọn multi-select: tách ra bảng riêng thay vì lưu chuỗi
CREATE TABLE result_question_selected_options (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_score_id BIGINT UNSIGNED NOT NULL REFERENCES result_question_scores(id) ON DELETE CASCADE,
    option_key        VARCHAR(128) NOT NULL,
    INDEX (question_score_id)
);
```

### 4.15 Snapshot system — versioning cấu hình (relational)

Mỗi lần admin lưu config, hệ thống snapshot toàn bộ trạng thái hiện tại vào các bảng quan hệ. Không dùng JSON — mọi trường đều có kiểu dữ liệu xác định, có thể index và query hiệu quả.

#### `assessment_config_snapshots` — header

```sql
CREATE TABLE assessment_config_snapshots (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_code     VARCHAR(64) NOT NULL,
    snapshot_version    SMALLINT UNSIGNED NOT NULL,
    aggregation_model   ENUM('weighted_domain','flat_sum','sectioned') NOT NULL,
    classification_type ENUM('score_band','pass_fail','persona_match','none') NOT NULL,
    has_scoring         TINYINT(1) NOT NULL DEFAULT 1,
    passing_score       DECIMAL(6,2),        -- dùng cho pass_fail
    label_pass          VARCHAR(128),
    label_fail          VARCHAR(128),
    change_note         VARCHAR(500),
    created_by          BIGINT UNSIGNED,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (assessment_code, snapshot_version),
    INDEX idx_snap_code (assessment_code)
);
```

#### `snapshot_domains`

```sql
CREATE TABLE snapshot_domains (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_id  INT UNSIGNED NOT NULL REFERENCES assessment_config_snapshots(id) ON DELETE CASCADE,
    domain_code  VARCHAR(64) NOT NULL,
    label        VARCHAR(255) NOT NULL,
    weight       DECIMAL(5,4) NOT NULL DEFAULT 1.0000,
    min_score    DECIMAL(6,2),
    max_score    DECIMAL(6,2),
    sort_order   SMALLINT DEFAULT 0,
    INDEX (snapshot_id)
);
```

#### `snapshot_score_rules`

```sql
CREATE TABLE snapshot_score_rules (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_id           INT UNSIGNED NOT NULL REFERENCES assessment_config_snapshots(id) ON DELETE CASCADE,
    field_key             VARCHAR(128) NOT NULL,
    domain_code           VARCHAR(64),
    scoring_type          ENUM('none','boolean','single_choice','multi_choice','numeric_range') NOT NULL,
    max_score             SMALLINT DEFAULT 0,
    weight                DECIMAL(5,4) DEFAULT 1.0000,
    INDEX (snapshot_id),
    INDEX (snapshot_id, field_key)
);
```

#### `snapshot_score_rule_options`

```sql
CREATE TABLE snapshot_score_rule_options (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_rule_id  INT UNSIGNED NOT NULL REFERENCES snapshot_score_rules(id) ON DELETE CASCADE,
    option_key        VARCHAR(128) NOT NULL,
    score             SMALLINT NOT NULL DEFAULT 0,
    is_signal         TINYINT(1) DEFAULT 0,
    flag_code         VARCHAR(64),
    sort_order        SMALLINT DEFAULT 0,
    INDEX (snapshot_rule_id)
);
```

#### `snapshot_score_rule_ranges`

```sql
CREATE TABLE snapshot_score_rule_ranges (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_rule_id  INT UNSIGNED NOT NULL REFERENCES snapshot_score_rules(id) ON DELETE CASCADE,
    min_val           DECIMAL(10,2),
    max_val           DECIMAL(10,2),
    score             SMALLINT NOT NULL DEFAULT 0,
    sort_order        SMALLINT DEFAULT 0,
    INDEX (snapshot_rule_id)
);
```

#### `snapshot_score_bands`

```sql
CREATE TABLE snapshot_score_bands (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_id  INT UNSIGNED NOT NULL REFERENCES assessment_config_snapshots(id) ON DELETE CASCADE,
    band_code    VARCHAR(64) NOT NULL,
    label        VARCHAR(255) NOT NULL,
    min_score    DECIMAL(6,2) NOT NULL,
    max_score    DECIMAL(6,2) NOT NULL,
    description  VARCHAR(1000),
    color        VARCHAR(16),
    sort_order   SMALLINT DEFAULT 0,
    INDEX (snapshot_id)
);
```

#### `snapshot_personas` + `snapshot_persona_conditions`

```sql
CREATE TABLE snapshot_personas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_id  INT UNSIGNED NOT NULL REFERENCES assessment_config_snapshots(id) ON DELETE CASCADE,
    persona_code VARCHAR(64) NOT NULL,
    label        VARCHAR(255) NOT NULL,
    description  VARCHAR(1000),
    sort_order   SMALLINT DEFAULT 0,
    INDEX (snapshot_id)
);

CREATE TABLE snapshot_persona_conditions (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_persona_id  INT UNSIGNED NOT NULL REFERENCES snapshot_personas(id) ON DELETE CASCADE,
    domain_code          VARCHAR(64) NOT NULL,
    operator             ENUM('gte','lte','eq','between') NOT NULL,
    value_min            DECIMAL(6,2),
    value_max            DECIMAL(6,2),
    INDEX (snapshot_persona_id)
);
```

#### `snapshot_pain_point_rules`

```sql
CREATE TABLE snapshot_pain_point_rules (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_id      INT UNSIGNED NOT NULL REFERENCES assessment_config_snapshots(id) ON DELETE CASCADE,
    pain_point_code  VARCHAR(64) NOT NULL,
    label            VARCHAR(255),
    trigger_flag     VARCHAR(64),
    trigger_domain   VARCHAR(64),
    threshold_score  DECIMAL(6,2),
    INDEX (snapshot_id)
);
```

#### `snapshot_recommendation_rules`

```sql
CREATE TABLE snapshot_recommendation_rules (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_id          INT UNSIGNED NOT NULL REFERENCES assessment_config_snapshots(id) ON DELETE CASCADE,
    recommendation_code  VARCHAR(64) NOT NULL,
    label                VARCHAR(255) NOT NULL,
    description          VARCHAR(1000),
    priority             SMALLINT DEFAULT 0,
    condition_domain     VARCHAR(64),
    condition_threshold  DECIMAL(6,2),
    INDEX (snapshot_id)
);
```

#### `snapshot_roadmap_phases` + `snapshot_roadmap_milestones`

```sql
CREATE TABLE snapshot_roadmap_phases (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_id    INT UNSIGNED NOT NULL REFERENCES assessment_config_snapshots(id) ON DELETE CASCADE,
    band_code      VARCHAR(64) NOT NULL,
    phase_code     VARCHAR(64) NOT NULL,
    title          VARCHAR(255) NOT NULL,
    description    VARCHAR(2000),
    duration_weeks SMALLINT,
    sort_order     SMALLINT DEFAULT 0,
    INDEX (snapshot_id)
);

CREATE TABLE snapshot_roadmap_milestones (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snapshot_phase_id INT UNSIGNED NOT NULL REFERENCES snapshot_roadmap_phases(id) ON DELETE CASCADE,
    title            VARCHAR(255) NOT NULL,
    sort_order       SMALLINT DEFAULT 0,
    INDEX (snapshot_phase_id)
);
```

> **Snapshot strategy:** Mỗi snapshot là một bản sao đầy đủ toàn bộ config tại một thời điểm, lưu trong các bảng quan hệ có kiểu dữ liệu xác định. Rollback = copy từ snapshot tables ngược lại live tables trong một transaction. Không dùng JSON vì không thể index, không thể query theo field, không thể validate kiểu dữ liệu ở DB layer.

### 4.16 `scoring_feedback` — admin feedback trên kết quả

```sql
CREATE TABLE scoring_feedback (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id        BIGINT UNSIGNED NOT NULL REFERENCES assessment_results(id),
    actual_band      VARCHAR(64),
    notes            TEXT,
    submitted_by     BIGINT UNSIGNED,
    submitted_at     TIMESTAMP,
    UNIQUE (result_id)
);
```

---

## 5. Models

### 5.1 Core Assessment Models

```
Modules/Assessment/app/Models/
├── Assessment.php
├── AssessmentDomain.php
├── ScoreRule.php
├── ScoreRuleOption.php
├── ScoreRuleNumericRange.php
├── ScoreBand.php
├── PassFailConfig.php
├── Persona.php
├── PersonaCondition.php
├── PainPointRule.php
├── RecommendationRule.php
├── RoadmapPhase.php
├── RoadmapMilestone.php
├── MaturityLevel.php
├── AssessmentConfigSnapshot.php
└── ScoringFeedback.php
```

### 5.2 Result Models

```
Modules/Assessment/app/Models/
├── AssessmentResult.php
├── ResultDomainScore.php
├── ResultSignalFlag.php
├── ResultPainPoint.php
├── ResultRecommendation.php
├── ResultRoadmapPhase.php
├── ResultClassification.php
├── ResultQuestionScore.php
└── ResultQuestionSelectedOption.php
```

### 5.3 Snapshot Models

```
Modules/Assessment/app/Models/Snapshot/
├── AssessmentConfigSnapshot.php       -- header: assessment_config_snapshots
├── SnapshotDomain.php                 -- snapshot_domains
├── SnapshotScoreRule.php              -- snapshot_score_rules
├── SnapshotScoreRuleOption.php        -- snapshot_score_rule_options
├── SnapshotScoreRuleRange.php         -- snapshot_score_rule_ranges
├── SnapshotScoreBand.php              -- snapshot_score_bands
├── SnapshotPersona.php                -- snapshot_personas
├── SnapshotPersonaCondition.php       -- snapshot_persona_conditions
├── SnapshotPainPointRule.php          -- snapshot_pain_point_rules
├── SnapshotRecommendationRule.php     -- snapshot_recommendation_rules
├── SnapshotRoadmapPhase.php           -- snapshot_roadmap_phases
└── SnapshotRoadmapMilestone.php       -- snapshot_roadmap_milestones
```

#### AssessmentConfigSnapshot.php — relationships đầy đủ

```php
class AssessmentConfigSnapshot extends Model
{
    public $timestamps = false;
    protected $table = 'assessment_config_snapshots';

    public function domains(): HasMany
    {
        return $this->hasMany(SnapshotDomain::class, 'snapshot_id');
    }
    public function scoreRules(): HasMany
    {
        return $this->hasMany(SnapshotScoreRule::class, 'snapshot_id')->with(['options','ranges']);
    }
    public function scoreBands(): HasMany
    {
        return $this->hasMany(SnapshotScoreBand::class, 'snapshot_id')->orderBy('min_score');
    }
    public function personas(): HasMany
    {
        return $this->hasMany(SnapshotPersona::class, 'snapshot_id')->with('conditions');
    }
    public function painPointRules(): HasMany
    {
        return $this->hasMany(SnapshotPainPointRule::class, 'snapshot_id');
    }
    public function recommendationRules(): HasMany
    {
        return $this->hasMany(SnapshotRecommendationRule::class, 'snapshot_id')->orderBy('priority');
    }
    public function roadmapPhases(): HasMany
    {
        return $this->hasMany(SnapshotRoadmapPhase::class, 'snapshot_id')
            ->orderBy('sort_order')
            ->with('milestones');
    }
}
```

### 5.3 Assessment.php — relationships

```php
class Assessment extends Model
{
    protected $table = 'assessments';

    public function domains(): HasMany     { return $this->hasMany(AssessmentDomain::class); }
    public function scoreRules(): HasMany  { return $this->hasMany(ScoreRule::class); }
    public function scoreBands(): HasMany  { return $this->hasMany(ScoreBand::class)->orderBy('min_score'); }
    public function personas(): HasMany    { return $this->hasMany(Persona::class); }
    public function painPoints(): HasMany  { return $this->hasMany(PainPointRule::class); }
    public function recommendations(): HasMany { return $this->hasMany(RecommendationRule::class)->orderBy('priority'); }
    public function roadmapPhases(): HasMany   { return $this->hasMany(RoadmapPhase::class)->orderBy('sort_order'); }
    public function snapshots(): HasMany   { return $this->hasMany(AssessmentConfigSnapshot::class, 'assessment_code', 'assessment_code'); }

    public function aggregationModel(): string { return $this->aggregation_model ?? 'weighted_domain'; }
    public function classificationType(): string { return $this->classification_type ?? 'score_band'; }
    public function hasScoring(): bool { return (bool) $this->has_scoring; }

    public static function findByCode(string $code): ?self
    {
        return static::where('assessment_code', $code)->where('is_active', true)->first();
    }
}
```

### 5.4 AssessmentResult.php

```php
class AssessmentResult extends Model
{
    protected $table = 'assessment_results';

    // Polymorphic subject (SurveyResponse, Lead, etc.)
    public function subject(): MorphTo { return $this->morphTo(); }

    public function domainScores(): HasMany     { return $this->hasMany(ResultDomainScore::class, 'result_id'); }
    public function signalFlags(): HasMany      { return $this->hasMany(ResultSignalFlag::class, 'result_id'); }
    public function painPoints(): HasMany       { return $this->hasMany(ResultPainPoint::class, 'result_id'); }
    public function recommendations(): HasMany  { return $this->hasMany(ResultRecommendation::class, 'result_id')->orderBy('priority'); }
    public function roadmapPhases(): HasMany    { return $this->hasMany(ResultRoadmapPhase::class, 'result_id')->orderBy('sort_order'); }
    public function classification(): HasOne    { return $this->hasOne(ResultClassification::class, 'result_id'); }
    public function questionScores(): HasMany   { return $this->hasMany(ResultQuestionScore::class, 'result_id'); }
    public function feedback(): HasOne          { return $this->hasOne(ScoringFeedback::class, 'result_id'); }

    public function scopeForSubject(Builder $q, string $type, int $id): Builder
    {
        return $q->where('subject_type', $type)->where('subject_id', $id);
    }
}
```

---

## 6. Contracts — Integration Interface

Đây là điểm cốt lõi cho phép tái sử dụng Assessment ở nhiều module.

### 6.1 ScoringSubjectInterface

```php
// Modules/Assessment/app/Contracts/ScoringSubjectInterface.php
namespace Modules\Assessment\Contracts;

interface ScoringSubjectInterface
{
    /** Định danh duy nhất của subject (response_id, lead_id, ...) */
    public function getScoringSubjectId(): int;

    /** FQCN dùng cho polymorphic key (vd: SurveyResponse::class) */
    public function getScoringSubjectType(): string;

    /** Assessment code cần chạm điểm */
    public function getAssessmentCode(): string;

    /**
     * Trả về danh sách đáp án theo cấu trúc:
     * [ 'field_key' => ['value' => mixed, 'option_ids' => int[], 'numeric' => float|null] ]
     */
    public function getScoringAnswers(): array;
}
```

### 6.2 Survey implements ScoringSubjectInterface

```php
// Modules/Survey/app/Models/SurveyResponse.php
// Thêm implement, không sửa gì khác

use Modules\Assessment\Contracts\ScoringSubjectInterface;

class SurveyResponse extends Model implements ScoringSubjectInterface
{
    public function getScoringSubjectId(): int   { return $this->id; }
    public function getScoringSubjectType(): string { return static::class; }
    public function getAssessmentCode(): string  { return $this->survey?->assessment_code ?? ''; }

    public function getScoringAnswers(): array
    {
        // AnswerReader logic chuyển vào đây hoặc gọi AnswerReader service
        return app(\Modules\Assessment\Engine\AnswerReader::class)->read($this->id, $this->survey_id);
    }
}
```

---

## 7. Engine Architecture

Pipeline 5 bước, namespace mới `Modules\Assessment\Engine\`:

```
Modules/Assessment/app/Engine/
├── ScoringEngineService.php         -- Orchestrator (inject qua DI)
├── ScoringConfig.php                -- Value object: config loaded từ DB
├── ScoringConfigLoader.php          -- Load ScoringConfig từ Assessment + relations
├── ScoringResult.php                -- readonly: kết quả cuối cùng của pipeline
├── AnswerReader.php                 -- Đọc answers từ subject (survey_answers hoặc source khác)
│
├── FeatureExtractor.php             -- [Tầng 1] Question → raw feature scores + signal flags
│
├── AggregationFactory.php           -- [Tầng 2] Factory: chọn aggregation strategy
├── AggregatedResult.php             -- Value object: domain scores + overall score
├── WeightRepository.php             -- Load weights từ assessment_domains
├── Aggregation/
│   ├── Contracts/AggregationStrategy.php
│   ├── WeightedDomainAggregation.php
│   ├── FlatSumAggregation.php
│   └── SectionedAggregation.php
│
├── ClassificationFactory.php        -- [Tầng 3] Factory: chọn classification strategy
├── ClassificationResult.php         -- Value object: band / pass-fail / persona
├── Classification/
│   ├── Contracts/ClassificationStrategy.php
│   ├── ScoreBandClassification.php
│   ├── PassFailClassification.php
│   ├── PersonaMatchClassification.php
│   └── NoneClassification.php
│
├── PainPointDetector.php            -- [Post] Detect pain points từ signal flags + domain scores
├── RecommendationEngine.php         -- [Post] Generate recommendations từ domain scores
├── RecommendationResult.php
├── RoadmapLoader.php                -- [Post] Load roadmap phases theo band
├── RoadmapPhaseResult.php
├── MaturityDetector.php             -- Legacy support
├── DomainScoreResult.php
├── ScoreNormalizer.php
└── ResultPersister.php              -- [Persist] Transaction: lưu toàn bộ vào DB
```

### 7.1 ScoringEngineService signature (đổi input)

```php
class ScoringEngineService
{
    /**
     * Chấm điểm một subject bất kỳ implement ScoringSubjectInterface.
     *
     * @throws InvalidScoringConfigException
     */
    public function calculate(
        ScoringSubjectInterface $subject,
        bool $force = false,
    ): ScoringResult {
        $assessmentCode = $subject->getAssessmentCode();
        $subjectId      = $subject->getScoringSubjectId();
        $subjectType    = $subject->getScoringSubjectType();

        // Idempotency check
        if (!$force) {
            $existing = AssessmentResult::forSubject($subjectType, $subjectId)->first();
            if ($existing !== null) {
                return $this->buildResultFromModel($existing);
            }
        }

        $config  = $this->configLoader->load($assessmentCode);
        $answers = $subject->getScoringAnswers();

        // ... pipeline giống hiện tại, nhưng persist vào assessment_results
        $this->persister->persist($subjectType, $subjectId, $result);

        return $result;
    }
}
```

### 7.2 AnswerReader — trừu tượng hóa nguồn đáp án

```php
class AnswerReader
{
    /**
     * Đọc answers từ survey_answers cho SurveyResponse.
     * Nếu tương lai có LeadAnswer hoặc HRAnswer → thêm strategy tại đây.
     *
     * Output: ['field_key' => ['value' => mixed, 'option_ids' => int[], 'numeric' => float|null]]
     */
    public function read(int $responseId, int $surveyId): array
    {
        // Query trực tiếp vào survey_answers table (cross-module read — cho phép)
        // Không import class SurveyAnswer, dùng DB::table()
        $rows = DB::table('survey_answers')
            ->where('response_id', $responseId)
            ->get();

        return $this->mapToAnswerArray($rows, $surveyId);
    }
}
```

---

## 8. Actions (Command side)

```
Modules/Assessment/app/Actions/
├── RunAssessmentAction.php          -- Điều phối: nhận Subject → gọi Engine → fire event
├── ForceRerunAssessmentAction.php   -- Force recalculate (xóa result cũ)
├── SaveAssessmentConfigAction.php   -- Lưu toàn bộ config từ wizard (domains + rules + bands)
├── CreateConfigSnapshotAction.php   -- Snapshot config hiện tại vào relational tables
├── RestoreConfigSnapshotAction.php  -- Restore từ snapshot version
└── SubmitScoringFeedbackAction.php  -- Admin xác nhận actual_band
```

### RunAssessmentAction

```php
class RunAssessmentAction
{
    use AsAction;

    public string $jobQueue   = 'assessment';
    public int    $jobTries   = 3;
    public array  $jobBackoff = [10, 30, 60];

    public function __construct(
        private readonly ScoringEngineService $engine,
    ) {}

    public function handle(ScoringSubjectInterface $subject, bool $force = false): AssessmentResult
    {
        $result = $this->engine->calculate($subject, $force);

        // Lấy persisted model
        $model = AssessmentResult::forSubject(
            $subject->getScoringSubjectType(),
            $subject->getScoringSubjectId(),
        )->firstOrFail();

        // Fire domain event — WorkflowAutomation lắng nghe
        event(new AssessmentCompleted($model, $result));

        return $model;
    }

    // Dispatch as queued job
    public function asJob(ScoringSubjectInterface $subject, bool $force = false): void
    {
        $this->handle($subject, $force);
    }
}
```

### CreateConfigSnapshotAction

```php
class CreateConfigSnapshotAction
{
    use AsAction;

    public function handle(Assessment $assessment, string $changeNote = ''): AssessmentConfigSnapshot
    {
        $version = AssessmentConfigSnapshot::where('assessment_code', $assessment->assessment_code)
            ->max('snapshot_version') + 1;

        return DB::transaction(function () use ($assessment, $version, $changeNote) {

            $snap = AssessmentConfigSnapshot::create([
                'assessment_code'     => $assessment->assessment_code,
                'snapshot_version'    => $version,
                'aggregation_model'   => $assessment->aggregation_model,
                'classification_type' => $assessment->classification_type,
                'has_scoring'         => $assessment->has_scoring,
                'change_note'         => $changeNote,
                'created_by'          => Auth::id(),
            ]);

            // Copy domains
            $assessment->domains->each(fn ($d) => SnapshotDomain::create([
                'snapshot_id' => $snap->id,
                'domain_code' => $d->domain_code,
                'label'       => $d->label,
                'weight'      => $d->weight,
                'sort_order'  => $d->sort_order,
            ]));

            // Copy score_rules + options + ranges
            $assessment->scoreRules->each(function ($rule) use ($snap) {
                $snapRule = SnapshotScoreRule::create([
                    'snapshot_id'  => $snap->id,
                    'field_key'    => $rule->field_key,
                    'domain_code'  => $rule->domain_code,
                    'scoring_type' => $rule->scoring_type,
                    'max_score'    => $rule->max_score,
                    'weight'       => $rule->weight,
                ]);
                $rule->options->each(fn ($o) => SnapshotScoreRuleOption::create([
                    'snapshot_rule_id' => $snapRule->id,
                    'option_key'       => $o->option_key,
                    'score'            => $o->score,
                    'is_signal'        => $o->is_signal,
                    'flag_code'        => $o->flag_code,
                    'sort_order'       => $o->sort_order,
                ]));
                $rule->ranges->each(fn ($r) => SnapshotScoreRuleRange::create([
                    'snapshot_rule_id' => $snapRule->id,
                    'min_val'          => $r->min_val,
                    'max_val'          => $r->max_val,
                    'score'            => $r->score,
                ]));
            });

            // Copy score_bands, personas, pain_points, recommendations, roadmap tương tự...
            // (pattern giống hệt ở trên — omitted for brevity)

            return $snap;
        });
    }
}
```

### RestoreConfigSnapshotAction

```php
class RestoreConfigSnapshotAction
{
    use AsAction;

    public function handle(AssessmentConfigSnapshot $snapshot): void
    {
        DB::transaction(function () use ($snapshot) {
            $assessment = Assessment::where('assessment_code', $snapshot->assessment_code)
                ->firstOrFail();

            // Snapshot trạng thái hiện tại trước khi ghi đè
            CreateConfigSnapshotAction::run($assessment, "Auto-snapshot trước khi rollback về v{$snapshot->snapshot_version}");

            // Restore metadata
            $assessment->update([
                'aggregation_model'   => $snapshot->aggregation_model,
                'classification_type' => $snapshot->classification_type,
                'has_scoring'         => $snapshot->has_scoring,
            ]);

            // Xóa config hiện tại và restore từ snapshot
            $assessment->domains()->delete();
            $snapshot->domains->each(fn ($d) => $assessment->domains()->create([
                'domain_code' => $d->domain_code,
                'label'       => $d->label,
                'weight'      => $d->weight,
                'sort_order'  => $d->sort_order,
            ]));

            // Tương tự cho score_rules, bands, personas, pain_points, recommendations, roadmap
        });
    }
}
```

### SaveAssessmentConfigAction

```php
class SaveAssessmentConfigAction
{
    use AsAction;

    public function handle(Assessment $assessment, array $configPayload): void
    {
        DB::transaction(function () use ($assessment, $configPayload) {
            // Snapshot trước khi save (relational — không dùng JSON)
            CreateConfigSnapshotAction::run($assessment, $configPayload['change_note'] ?? '');

            // Upsert domains
            $this->syncDomains($assessment, $configPayload['domains'] ?? []);

            // Upsert score_rules + options + ranges
            $this->syncScoreRules($assessment, $configPayload['score_rules'] ?? []);

            // Upsert score_bands / pass_fail_config / personas
            $this->syncClassification($assessment, $configPayload);

            // Upsert pain points + recommendations + roadmap
            $this->syncPostScoring($assessment, $configPayload);
        });
    }
}
```

---

## 9. Queries (Query side)

```
Modules/Assessment/app/Queries/
├── GetAssessmentConfigQuery.php + GetAssessmentConfigHandler.php
├── GetAssessmentResultQuery.php + GetAssessmentResultHandler.php
├── ListAssessmentResultsQuery.php + ListAssessmentResultsHandler.php
└── ListAssessmentsQuery.php + ListAssessmentsHandler.php
```

### GetAssessmentResultHandler

```php
class GetAssessmentResultHandler
{
    public function handle(GetAssessmentResultQuery $query): ?AssessmentResult
    {
        return AssessmentResult::forSubject($query->subjectType, $query->subjectId)
            ->with([
                'domainScores',
                'signalFlags',
                'painPoints',
                'recommendations',
                'roadmapPhases.phase.milestones',
                'classification',
                'questionScores',
                'feedback',
            ])
            ->first();
    }
}
```

---

## 10. Events & Listeners

### Events

```
Modules/Assessment/app/Events/
├── AssessmentCompleted.php    -- fired sau khi engine hoàn thành + persist
└── AssessmentFailed.php       -- fired khi engine throw exception
```

### AssessmentCompleted

```php
class AssessmentCompleted
{
    public function __construct(
        public readonly AssessmentResult        $result,
        public readonly ScoringResult           $scoringResult,
        public readonly ScoringSubjectInterface $subject,
    ) {}
}
```

### Listeners trong Assessment

```
Modules/Assessment/app/Listeners/
└── LogAssessmentCompleted.php   -- ghi ActivityLog
```

### Listeners trong Survey (lắng nghe Assessment event)

```
Modules/Survey/app/Listeners/
└── DispatchSurveyWebhookOnResult.php  -- Survey lắng nghe AssessmentCompleted, dispatch webhook
```

### Listeners trong WorkflowAutomation

```
Modules/WorkflowAutomation/app/Listeners/
└── FireAssessmentResultTrigger.php    -- lắng nghe AssessmentCompleted, fire workflow triggers
```

---

## 11. Routes & Controllers

### web.php

```php
// prefix: dashboard/assessments — name: assessments.*
Route::middleware(['auth', 'verified'])->prefix('dashboard/assessments')->name('assessments.')->group(function () {

    // ── Assessment CRUD ────────────────────────────────────────────
    Route::get('/',                             [AssessmentController::class, 'index'])->name('index');
    Route::get('/create',                       [AssessmentController::class, 'create'])->name('create');
    Route::post('/',                            [AssessmentController::class, 'store'])->name('store');
    Route::get('/{assessment}',                 [AssessmentController::class, 'show'])->name('show');
    Route::get('/{assessment}/edit',            [AssessmentController::class, 'edit'])->name('edit');
    Route::put('/{assessment}',                 [AssessmentController::class, 'update'])->name('update');
    Route::delete('/{assessment}',              [AssessmentController::class, 'destroy'])->name('destroy');

    // ── Config Wizard (JSON API cho Alpine.js) ──────────────────────
    Route::prefix('/{assessment}/config')->name('config.')->group(function () {
        Route::get('/',            [AssessmentConfigController::class, 'show'])->name('show');
        Route::post('/',           [AssessmentConfigController::class, 'save'])->name('save');
        Route::post('/validate',   [AssessmentConfigController::class, 'validate'])->name('validate');
        Route::get('/snapshots',   [AssessmentConfigController::class, 'snapshots'])->name('snapshots');
        Route::post('/rollback',   [AssessmentConfigController::class, 'rollback'])->name('rollback');
        Route::post('/reprocess',  [AssessmentConfigController::class, 'reprocessAll'])->name('reprocess');
    });

    // ── Results ────────────────────────────────────────────────────
    Route::prefix('/{assessment}/results')->name('results.')->group(function () {
        Route::get('/',                        [AssessmentResultController::class, 'index'])->name('index');
        Route::get('/{result}',                [AssessmentResultController::class, 'show'])->name('show');
        Route::post('/{result}/recalculate',   [AssessmentResultController::class, 'recalculate'])->name('recalculate');
        Route::patch('/{result}/feedback',     [AssessmentResultController::class, 'feedback'])->name('feedback');
    });
});

// Public result page (respondent tự xem)
Route::get('/assessment-result/{token}', [AssessmentPublicResultController::class, 'show'])
    ->name('assessment.result.public')
    ->middleware(\Modules\Assessment\Http\Middleware\ValidateAssessmentResultToken::class);
```

### api.php

```php
Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1/assessment')->name('api.assessment.')->group(function () {
    // Config JSON (dùng cho wizard frontend)
    Route::get('/{code}/config',   [AssessmentApiController::class, 'config'])->name('config');
    Route::post('/{code}/config',  [AssessmentApiController::class, 'saveConfig'])->name('config.save');

    // Results (Tabulator listing)
    Route::get('/{code}/results',  [AssessmentResultApiController::class, 'listing'])->name('results');

    // Trigger manual scoring
    Route::post('/run',            [AssessmentApiController::class, 'run'])->name('run');
});
```

### Controllers

```
Modules/Assessment/app/Http/Controllers/
├── AssessmentController.php          -- CRUD assessment definitions
├── AssessmentConfigController.php    -- Wizard JSON API (getConfig/saveConfig/snapshots/rollback)
├── AssessmentResultController.php    -- List + show + recalculate + feedback
├── AssessmentPublicResultController.php  -- Public result page
├── Api/
│   ├── AssessmentApiController.php
│   └── AssessmentResultApiController.php
```

---

## 12. Views — Admin UI

```
Modules/Assessment/resources/views/
├── assessments/
│   ├── index.blade.php          -- danh sách assessments (Tabulator)
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php           -- overview + quick links to config/results
├── config/
│   └── wizard.blade.php         -- Alpine.js wizard: domains → rules → bands → personas → roadmap
├── results/
│   ├── index.blade.php          -- danh sách results của một assessment (Tabulator)
│   └── show.blade.php           -- chi tiết result: score radar chart, domain bars, roadmap
└── public/
    └── result.blade.php         -- public page cho respondent (no auth)
```

---

## 13. Jobs

```
Modules/Assessment/app/Jobs/
└── RunAssessmentJob.php   -- Queue wrapper, gọi RunAssessmentAction::dispatch()
```

> `RunAssessmentAction` đã có `asJob()` method nên Laravel Actions tự tạo job. `RunAssessmentJob` chỉ cần nếu muốn custom `$queue`, `$tries`, `$timeout` riêng.

---

## 14. Permissions

```php
// Thêm vào App\Enums\PermissionEnum
case ASSESSMENT_VIEW    = 'assessment.view';
case ASSESSMENT_CONFIG  = 'assessment.config';    // Wizard cấu hình
case ASSESSMENT_RESULTS = 'assessment.results';   // Xem kết quả
case ASSESSMENT_REPROCESS = 'assessment.reprocess'; // Force recalculate
```

| Role | Permissions |
|------|-------------|
| System Admin | Tất cả |
| CEO | `assessment.view`, `assessment.results` |
| Ops | `assessment.view`, `assessment.results` |
| AI Operator | `assessment.view`, `assessment.config`, `assessment.results`, `assessment.reprocess` |

---

## 15. Integration với Survey

### Survey sau khi submit

```php
// Modules/Survey/app/Actions/SubmitSurveyAction.php
// Thay CalculateSurveyScoreJob bằng:

use Modules\Assessment\Actions\RunAssessmentAction;

if ($response->survey->hasAssessment()) {
    RunAssessmentAction::dispatch($response);  // $response implements ScoringSubjectInterface
}
```

### Survey lắng nghe kết quả

```php
// Modules/Survey/app/Listeners/DispatchSurveyWebhookOnResult.php

public function handle(AssessmentCompleted $event): void
{
    $subject = $event->subject;

    // Chỉ xử lý khi subject là SurveyResponse
    if (!($subject instanceof SurveyResponse)) {
        return;
    }

    $this->webhooks->dispatch($subject->survey_id, 'result.calculated', [
        'response_id'  => $subject->id,
        'overall_score' => $event->scoringResult->overallScore,
        'band_code'    => $event->scoringResult->classification->bandCode,
    ]);
}
```

### EventServiceProvider của Survey

```php
// Modules/Survey/app/Providers/EventServiceProvider.php
protected $listen = [
    \Modules\Assessment\Events\AssessmentCompleted::class => [
        \Modules\Survey\Listeners\DispatchSurveyWebhookOnResult::class,
    ],
];
```

### WorkflowAutomation trigger

```php
// Modules/WorkflowAutomation/app/Listeners/FireAssessmentResultTrigger.php

public function handle(AssessmentCompleted $event): void
{
    WorkflowDispatcher::fire(TriggerPayload::forAssessmentResult($event->result));
}
```

---

## 16. Integration với các module tương lai

### Ví dụ: HR Competency Assessment

```php
// Modules/HR/app/Models/EmployeeEvaluation.php

use Modules\Assessment\Contracts\ScoringSubjectInterface;

class EmployeeEvaluation extends Model implements ScoringSubjectInterface
{
    public function getScoringSubjectId(): int    { return $this->id; }
    public function getScoringSubjectType(): string { return static::class; }
    public function getAssessmentCode(): string   { return 'hr_competency_v1'; }

    public function getScoringAnswers(): array
    {
        // Map employee evaluation data → answer format
        return $this->answers->mapWithKeys(fn ($a) => [
            $a->competency_code => [
                'value'      => $a->rating,
                'option_ids' => [],
                'numeric'    => (float) $a->rating,
            ]
        ])->all();
    }
}

// Trigger scoring:
RunAssessmentAction::dispatch($evaluation);
```

### Quy tắc mở rộng

1. Implement `ScoringSubjectInterface` trên bất kỳ Eloquent model nào
2. Tạo `assessment_code` phù hợp trong bảng `assessments`
3. Cấu hình domains + rules qua Assessment Wizard
4. Dispatch `RunAssessmentAction::dispatch($subject)`
5. Lắng nghe `AssessmentCompleted` event để xử lý kết quả

**Assessment module không cần sửa gì** khi thêm module mới.

---

## 17. Migrations — thứ tự & cấu trúc

> Chiến lược: Giữ nguyên tên bảng hiện tại (`survey_results`, `score_rules`, v.v.) để không mất data.
> Bảng `assessment_results` là bảng mới thay thế `survey_results` trong long-term.

**Giai đoạn 1 — Di chuyển code (không đổi bảng):**
- Đổi namespace PHP, không chạy migration mới
- Assessment models trỏ vào bảng hiện tại của Survey

**Giai đoạn 2 — Chuẩn hóa bảng (migration riêng, sau này):**
```
001_create_assessment_results_table.php     -- bảng mới thay survey_results
002_migrate_survey_results_data.php         -- copy data cũ, set subject_type = SurveyResponse
003_create_snapshot_tables.php              -- 12 bảng snapshot quan hệ (header + domains + rules + bands + personas + pain_points + recommendations + roadmap)
```

---

## 18. Thứ tự triển khai

| # | Hạng mục | Effort | Ghi chú |
|---|---|---|---|
| 1 | Tạo module `Assessment` với NWIDART scaffold | Thấp | `php artisan module:make Assessment` |
| 2 | Tạo `ScoringSubjectInterface` trong `Assessment/Contracts/` | Thấp | Contract cốt lõi |
| 3 | Copy toàn bộ `Survey/app/Scoring/` → `Assessment/app/Engine/` với namespace mới | Trung | Đổi namespace, không đổi logic |
| 4 | Copy 31 Scoring models → `Assessment/app/Models/` với namespace mới | Trung | Trỏ vào bảng cũ |
| 5 | Copy `ScoringAdminController` → `AssessmentConfigController` | Trung | Đổi route names |
| 6 | Copy `SurveyResultController` → `AssessmentResultController` | Trung | |
| 7 | Tạo `AssessmentController` mới (CRUD assessment definitions) | Thấp | |
| 8 | Tạo `RunAssessmentAction` + `SaveAssessmentConfigAction` | Trung | |
| 9 | Tạo `AssessmentCompleted` event | Thấp | |
| 10 | `SurveyResponse` implement `ScoringSubjectInterface` | Thấp | |
| 11 | Update `SubmitSurveyAction`: gọi `RunAssessmentAction::dispatch()` | Thấp | |
| 12 | Tạo `DispatchSurveyWebhookOnResult` listener trong Survey | Thấp | Lắng nghe `AssessmentCompleted` |
| 13 | Update WorkflowAutomation triggers lắng nghe `AssessmentCompleted` | Thấp | |
| 14 | Tạo `AssessmentServiceProvider`, đăng ký bindings + policies | Thấp | |
| 15 | Copy + adapt views từ Survey sang Assessment/resources/views/ | Trung | |
| 16 | Đăng ký routes + sidebar navigation | Thấp | |
| 17 | Xóa code scoring khỏi Survey module | Thấp | Sau khi verify Assessment hoạt động |
| 18 | Test end-to-end: submit survey → Assessment engine → result page | Trung | |

**Tổng effort ước tính:** 2–3 ngày làm việc. Logic không thay đổi, chủ yếu là đổi namespace và wiring.

---

## Phụ lục — So sánh trước/sau

| | Trước (Survey chứa Scoring) | Sau (Assessment module riêng) |
|---|---|---|
| Namespace engine | `Modules\Survey\Scoring\` | `Modules\Assessment\Engine\` |
| Namespace models | `Modules\Survey\Models\Assessment` | `Modules\Assessment\Models\Assessment` |
| Trigger scoring | `CalculateSurveyScoreJob::dispatch($id)` | `RunAssessmentAction::dispatch($response)` |
| Kết quả | `SurveyResult` (survey-specific) | `AssessmentResult` (polymorphic) |
| Mở rộng module mới | Phải sửa Survey module | Chỉ implement interface |
| Single Responsibility | ❌ Survey làm cả khảo sát + chấm điểm | ✅ Mỗi module một trách nhiệm |
| Reusability | ❌ Chỉ Survey dùng được | ✅ Bất kỳ module nào |
