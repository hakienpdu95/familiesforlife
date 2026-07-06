# Survey Module — Feature Inventory

> Tài liệu liệt kê đầy đủ tất cả tính năng, chức năng đã implement trong `Modules/Survey/`.
> Cập nhật: 2026-05-25

---

## Mục lục

1. [Routes](#1-routes)
2. [Controllers](#2-controllers)
3. [Models & Database](#3-models--database)
4. [Scoring Pipeline](#4-scoring-pipeline)
5. [Views](#5-views)
6. [Jobs](#6-jobs)
7. [Middlewares](#7-middlewares)
8. [Actions](#8-actions)
9. [Seeders](#9-seeders)
10. [Migrations — Bảng DB](#10-migrations--bảng-db)

---

## 1. Routes

### 1.1 Web Routes — Admin Backend

Prefix: `/dashboard` | Middleware: `auth` | Name prefix: `backend.`

#### Survey CRUD — `backend.surveys.*`

| Method | URL | Controller@Method | Name |
|--------|-----|-------------------|------|
| GET | `/dashboard/surveys` | `SurveyController@index` | `surveys.index` |
| GET | `/dashboard/surveys/create` | `SurveyController@create` | `surveys.create` |
| POST | `/dashboard/surveys` | `SurveyController@store` | `surveys.store` |
| GET | `/dashboard/surveys/{survey}/edit` | `SurveyController@edit` | `surveys.edit` |
| PUT | `/dashboard/surveys/{survey}` | `SurveyController@update` | `surveys.update` |
| DELETE | `/dashboard/surveys/{survey}` | `SurveyController@destroy` | `surveys.destroy` |
| POST | `/dashboard/surveys/{survey}/activate` | `SurveyController@activate` | `surveys.activate` |

#### Token Management — `backend.surveys.tokens.*`

| Method | URL | Controller@Method | Name |
|--------|-----|-------------------|------|
| GET | `/dashboard/surveys/{survey}/tokens` | `TokenController@index` | `tokens.index` |
| POST | `/dashboard/surveys/{survey}/tokens` | `TokenController@store` | `tokens.store` |
| GET | `/dashboard/surveys/{survey}/tokens/{token}/reveal` | `TokenController@reveal` | `tokens.reveal` |
| PATCH | `/dashboard/surveys/{survey}/tokens/{token}/revoke` | `TokenController@revoke` | `tokens.revoke` |
| DELETE | `/dashboard/surveys/{survey}/tokens/{token}` | `TokenController@destroy` | `tokens.destroy` |

#### Stats Dashboard — `backend.surveys.stats.*`

| Method | URL | Controller@Method | Name |
|--------|-----|-------------------|------|
| GET | `/dashboard/surveys/{survey}/stats` | `StatsController@index` | `surveys.stats.index` |

#### Scoring Config Wizard — `backend.surveys.scoring.*`

| Method | URL | Controller@Method | Name |
|--------|-----|-------------------|------|
| GET | `/dashboard/surveys/{survey}/scoring` | `ScoringAdminController@index` | `scoring.index` |
| GET | `/dashboard/surveys/{survey}/scoring/config` | `ScoringAdminController@getConfig` | `scoring.config` |
| PUT | `/dashboard/surveys/{survey}/scoring/config` | `ScoringAdminController@saveConfig` | `scoring.config.save` |
| GET | `/dashboard/surveys/{survey}/scoring/fields` | `ScoringAdminController@getFields` | `scoring.fields` |
| GET | `/dashboard/surveys/{survey}/scoring/flags` | `ScoringAdminController@getFlags` | `scoring.flags` |
| POST | `/dashboard/surveys/{survey}/scoring/validate` | `ScoringAdminController@validateConfig` | `scoring.validate` |
| POST | `/dashboard/surveys/{survey}/scoring/dry-run` | `ScoringAdminController@dryRun` | `scoring.dry-run` |

#### Results — `backend.surveys.results.*`

| Method | URL | Controller@Method | Name |
|--------|-----|-------------------|------|
| GET | `/dashboard/surveys/{survey}/results/summary` | `SurveyResultController@summary` | `results.summary` |

#### Response Management — `backend.surveys.responses.*`

| Method | URL | Controller@Method | Name |
|--------|-----|-------------------|------|
| GET | `/dashboard/surveys/{survey}/responses` | `ResponseController@index` | `responses.index` |
| GET | `/dashboard/surveys/{survey}/responses/export` | `ResponseController@export` | `responses.export` |
| GET | `/dashboard/surveys/{survey}/responses/export/download/{key}` | `ResponseController@downloadExport` | `responses.export.download` |
| GET | `/dashboard/surveys/{survey}/responses/{response}` | `ResponseController@show` | `responses.show` |
| DELETE | `/dashboard/surveys/{survey}/responses/{response}` | `ResponseController@destroy` | `responses.destroy` |
| GET | `/dashboard/surveys/{survey}/responses/{response}/result` | `SurveyResultController@show` | `responses.result.show` |
| POST | `/dashboard/surveys/{survey}/responses/{response}/recalculate` | `SurveyResultController@recalculate` | `responses.result.recalculate` |
| PATCH | `/dashboard/surveys/{survey}/responses/{response}/feedback` | `SurveyResultController@submitFeedback` | `responses.result.feedback` |

#### Section / Field / Option Builder (JSON) — `backend.surveys.sections/fields/options.*`

| Method | URL | Controller@Method | Name |
|--------|-----|-------------------|------|
| POST | `/{survey}/sections` | `SectionController@store` | `sections.store` |
| PUT | `/{survey}/sections/{section}` | `SectionController@update` | `sections.update` |
| DELETE | `/{survey}/sections/{section}` | `SectionController@destroy` | `sections.destroy` |
| PATCH | `/{survey}/sections/reorder` | `SectionController@reorder` | `sections.reorder` |
| POST | `/{survey}/fields` | `FieldController@store` | `fields.store` |
| PUT | `/{survey}/fields/{field}` | `FieldController@update` | `fields.update` |
| DELETE | `/{survey}/fields/{field}` | `FieldController@destroy` | `fields.destroy` |
| PATCH | `/{survey}/fields/{field}/toggle` | `FieldController@toggleActive` | `fields.toggle` |
| PATCH | `/{survey}/fields/reorder` | `FieldController@reorder` | `fields.reorder` |
| POST | `/{survey}/fields/{field}/options` | `OptionController@store` | `options.store` |
| PUT | `/{survey}/fields/{field}/options/{option}` | `OptionController@update` | `options.update` |
| DELETE | `/{survey}/fields/{field}/options/{option}` | `OptionController@destroy` | `options.destroy` |
| PATCH | `/{survey}/fields/{field}/options/reorder` | `OptionController@reorder` | `options.reorder` |

### 1.2 Web Routes — Public

| Method | URL | Middleware | Name |
|--------|-----|------------|------|
| GET | `/surveys/{slug}/result` | `ValidateSurveyWebToken` | `surveys.result.public` |

### 1.3 API Routes — Public

Prefix: `v1` | Middleware: `ValidateSurveyToken`

| Method | URL | Controller@Method | Notes |
|--------|-----|-------------------|-------|
| GET | `/v1/surveys/{slug}/schema` | `SurveyApiController@schema` | Lấy schema survey |
| POST | `/v1/surveys/{slug}/submit` | `SurveyApiController@submit` | Throttle: 60/min + Turnstile |
| GET | `/v1/surveys/{slug}/stats` | `SurveyApiController@stats` | Lấy submission stats |
| GET | `/v1/surveys/{slug}/responses` | `SurveyApiController@responses` | Paginated list / export |
| GET | `/v1/surveys/{slug}/result` | `SurveyApiController@result` | Respondent lấy kết quả |
| POST | `/v1/surveys/{slug}/behavior` | `SurveyApiController@behavior` | Log behavior events |

### 1.4 Backend JSON API — Tabulator

Prefix: `backend/api` | Middleware: `auth` | Name prefix: `backend.api.`

| Method | URL | Controller@Method | Name |
|--------|-----|-------------------|------|
| GET | `/backend/api/surveys` | `SurveyBackendApiController@index` | `backend.api.surveys` |
| GET | `/backend/api/surveys/{survey}/responses` | `SurveyBackendApiController@responses` | `backend.api.surveys.responses` |

---

## 2. Controllers

### 2.1 SurveyController

Quản lý lifecycle surveys (CRUD, activate/deactivate).

| Method | Input | Output | Auth |
|--------|-------|--------|------|
| `index()` | — | View `surveys.index` với statuses enum | `survey.view` |
| `create()` | — | View `surveys.create` | `survey.create` |
| `store(SurveyRequest, CreateSurveyAction)` | title, slug, assessment_code | Redirect → `surveys.edit` | `survey.create` |
| `edit(Survey)` | Survey | View `surveys.edit` với sections/fields/options tree + fieldTypes | `survey.update` |
| `update(SurveyRequest, Survey, UpdateSurveyAction)` | title, slug, ... | Redirect → `surveys.edit` | `survey.update` |
| `activate(Survey, ActivateSurveyAction)` | Survey | Redirect → `surveys.edit` | `survey.update` |
| `destroy(Survey)` | Survey | Redirect → `surveys.index` (chỉ khi chưa active) | `survey.delete` |

### 2.2 ScoringAdminController

Wizard cấu hình scoring: domains, rules, bands, personas, pain points, recommendations, roadmap.

| Method | Input | Output | Auth |
|--------|-------|--------|------|
| `index(Survey)` | Survey | View `scoring.index` | `survey.update` |
| `getConfig(Survey)` | Survey | JSON: `{assessment, domains, rules, bands, pass_fail, personas, pain_points, recommendations, roadmap}` | `survey.update` |
| `saveConfig(Request, Survey)` | Full config JSON | JSON: `{success, message}` (transaction) | `survey.update` |
| `getFields(Survey)` | Survey | JSON: `{fields: [{field_key, label, is_choice, field_options[], rule?}]}` | `survey.update` |
| `getFlags(Survey)` | Survey | JSON: `{flags: [flag_code, ...]}` — tất cả signal flags đang dùng | `survey.update` |
| `validateConfig(Request, Survey)` | Full config JSON | JSON: `{valid: bool, errors: string[]}` (strict check) | `survey.update` |
| `dryRun(Request, Survey)` | `{answers: {field_key: value}}` | JSON: kết quả scoring đầy đủ (domain_scores, classification, pain_points, ...) | `survey.update` |

**saveConfig() transaction bao gồm**: Assessment → Domains → Score Rules (+ options + numeric ranges) → Score Bands → Pass-fail config → Personas (+ conditions) → Pain point rules → Recommendation rules → Roadmap phases (+ milestones).

**validateConfig() kiểm tra**:
- Tổng weight domains = 1.0 (weighted_domain)
- min_score < max_score mỗi domain
- Score bands coverage và không overlap
- multi_choice: bắt buộc max_score_cap, min < max cap
- multi_choice/single_choice: ≥ 2 options
- numeric_range: không overlap giữa các ranges
- Persona conditions hợp lệ

### 2.3 SurveyResultController

Xem và quản lý kết quả chấm điểm.

| Method | Input | Output | Auth |
|--------|-------|--------|------|
| `publicResult(Request, slug)` | `?ref=`, `?token=` | View `results.public` (kết quả public cho respondent) | Token middleware |
| `show(Survey, SurveyResponse)` | Survey + Response | View `results.show` với result + feedback form | `survey.view_responses` |
| `summary(Survey)` | Survey | View `results.summary` với phân bố maturity, avg domain scores | `survey.view_responses` |
| `recalculate(Survey, SurveyResponse, CalculateSurveyScoreAction)` | force=true | JSON: `{success, message}` | `survey.update` |
| `submitFeedback(Survey, SurveyResponse, Request)` | `{actual_band}` | JSON: `{success, message}` — update scoring_feedback | `survey.update` |

### 2.4 ResponseController

Xem và xuất survey responses.

| Method | Input | Output | Auth |
|--------|-------|--------|------|
| `index(Survey)` | Survey | View `responses.index` + counts (total, complete) | `survey.view_responses` |
| `show(Survey, SurveyResponse, ResponseViewerService)` | Response | View `responses.show` với answers grouped by sections | `survey.view_responses` |
| `export(Request, Survey, ExportSurveyResponsesAction)` | respondent_ref?, from?, to? | StreamedResponse (≤10k) hoặc queue job (>10k) | `survey.export` |
| `downloadExport(Survey, key)` | Export key (UUID) | Download file từ Redis cache | `survey.export` |
| `destroy(Survey, SurveyResponse)` | Response | Redirect | `survey.delete` |

### 2.5 StatsController

| Method | Input | Output | Auth |
|--------|-------|--------|------|
| `index(Survey, SurveyStatsService)` | Survey | View `stats.index` với: submission count, timeline by day, scoring distribution, avg domain scores | `survey.view_responses` |

### 2.6 TokenController

| Method | Input | Output | Auth |
|--------|-------|--------|------|
| `index(Survey)` | Survey | View `tokens.index` với token list | `survey.manage_tokens` |
| `store(Survey, GenerateSurveyTokenAction)` | name, expires_at | JSON: `{plain (1 lần), token payload}` | `survey.manage_tokens` |
| `reveal(Survey, SurveyToken)` | Token | JSON: `{plain}` | `survey.manage_tokens` |
| `revoke(Survey, SurveyToken)` | Token | JSON: success | `survey.manage_tokens` |
| `destroy(Survey, SurveyToken)` | Token | JSON: success | `survey.manage_tokens` |

### 2.7 SectionController / FieldController / OptionController

JSON API cho survey builder (Alpine.js fetch).

**SectionController**: store, update, destroy, reorder (với cache purge)  
**FieldController**: store, update, toggleActive, destroy, reorder  
**OptionController**: store, update, destroy, reorder

### 2.8 SurveyApiController (Public API)

| Method | Input | Output |
|--------|-------|--------|
| `schema(slug)` | slug | JSON: `{sections[], fields[], options[]}` — schema đầy đủ, cached 30 min |
| `submit(slug, SubmitSurveyRequest)` | answers[], respondent_ref?, cf-turnstile-response | JSON: `{response_id}` (201) |
| `stats(slug)` | slug | JSON: stats object |
| `responses(slug, Request)` | page, size, respondent_ref, from, to | JSON: paginated responses |
| `behavior(slug, Request)` | response_id, events[] | JSON: `{stored: count}` (201) |
| `result(slug, Request)` | ref, token (Bearer) | JSON: full scoring result |

### 2.9 SurveyBackendApiController (Tabulator API)

| Method | Filter params | Sort params |
|--------|--------------|-------------|
| `index()` | search, status, date_from, date_to | field, dir |
| `responses(Survey)` | respondent_ref, status, from, to | field, dir |

---

## 3. Models & Database

### 3.1 Survey

**Bảng**: `surveys`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| title | varchar | |
| slug | varchar (unique) | URL slug |
| assessment_code | varchar (nullable) | FK logic → assessments |
| status | tinyint | 0=draft, 1=active, 2=closed |
| version | integer | |

**Relations**: `sections()`, `fields()`, `responses()`, `tokens()`  
**Key methods**: `hasScoring(): bool`

---

### 3.2 SurveySection

**Bảng**: `survey_sections`

| Column | Type |
|--------|------|
| survey_id | FK |
| title | varchar |
| icon | varchar (nullable) |
| sort_order | integer |

**Relations**: `fields()` HasMany SurveyField (ordered)

---

### 3.3 SurveyField

**Bảng**: `survey_fields`

| Column | Type | Notes |
|--------|------|-------|
| survey_id, section_id | FK | |
| parent_field_id | FK (nullable) | Self-ref for conditional fields |
| field_key | varchar (unique/survey) | Machine key |
| label | varchar | |
| field_type | enum | text, email, number, radio, checkbox, select, textarea, date, file, scale, yes_no, ... |
| value_kind | enum | Loại value lưu |
| is_required | bool | |
| is_active | bool | |
| rule_min, rule_max | integer (nullable) | Min/max value or length |
| rule_max_select | integer (nullable) | Max items multi-choice |
| placeholder | varchar (nullable) | |

**Relations**: `section()`, `options()`, `children()`, `answers()`

---

### 3.4 SurveyFieldOption

**Bảng**: `survey_field_options`

| Column | Type |
|--------|------|
| field_id | FK |
| option_value | varchar |
| label | varchar |
| sort_order | integer |
| is_other | bool |

---

### 3.5 SurveyToken

**Bảng**: `survey_tokens`

| Column | Type | Notes |
|--------|------|-------|
| survey_id | FK | |
| name | varchar | Display name |
| token | varchar | SHA-256 hash |
| token_encrypted | varchar (nullable) | Encrypted plain |
| is_active | bool | |
| last_used_at | timestamp (nullable) | |
| expires_at | timestamp (nullable) | |

**Methods**: `isExpired()`, `isValid()`

---

### 3.6 SurveyResponse

**Bảng**: `survey_responses`

| Column | Type | Notes |
|--------|------|-------|
| survey_id | FK | |
| respondent_ref | varchar (nullable) | Email/phone/ID |
| respondent_ip | BINARY(16) | inet_pton encoded |
| status | tinyint | 0=pending, 1=complete |
| submitted_at | timestamp | |

**Relations**: `survey()`, `answers()`, `result()` HasOne SurveyResult

---

### 3.7 SurveyAnswer

**Bảng**: `survey_answers`

| Column | Type |
|--------|------|
| response_id | FK |
| field_id | FK |
| answer_text | text (nullable) |
| answer_numeric | integer (nullable) |
| answer_choice_id | FK nullable → survey_field_options |

---

### 3.8 Assessment

**Bảng**: `assessments`

| Column | Type | Values |
|--------|------|--------|
| assessment_code | varchar (unique) | e.g. `ai_workflow_v1` |
| name | varchar | |
| version | varchar | |
| has_scoring | bool | |
| aggregation_model | varchar | `flat_sum`, `weighted_domain`, `sectioned` |
| classification_type | varchar | `none`, `score_band`, `pass_fail`, `persona_match` |

---

### 3.9 AssessmentDomain

**Bảng**: `assessment_domains`

| Column | Type |
|--------|------|
| assessment_code | FK logic |
| domain_code | varchar |
| label | varchar |
| weight | decimal(5,4) — 0.0–1.0 |
| min_score, max_score | integer — raw score range |
| sort_order | integer |
| is_active | bool |

**Scopes**: `forAssessment(code)`, `ordered()`

---

### 3.10 ScoreRule

**Bảng**: `score_rules`

| Column | Type | Notes |
|--------|------|-------|
| assessment_code | FK logic | |
| field_key | varchar | Khớp `survey_fields.field_key` |
| feature_code | varchar | Alias (default = field_key) |
| domain_code | varchar (nullable) | NULL cho flat_sum |
| question_scoring_type | varchar | `none`, `boolean`, `single_choice`, `multi_choice`, `numeric_range` |
| signal_flag | varchar (nullable) | Flag emitted khi match |
| score_if_true, score_if_false | integer | Boolean rule scores |
| min_score_cap, max_score_cap | integer (nullable) | Cap cho multi_choice |
| is_active | bool | |

**Relations**: `options()` HasMany ScoreRuleOption, `numericRanges()` HasMany ScoreRuleNumericRange

---

### 3.11 ScoreRuleOption

**Bảng**: `score_rule_options`

| Column | Type |
|--------|------|
| rule_id | FK |
| option_value | varchar — khớp với SurveyFieldOption.option_value |
| option_label | varchar (nullable) |
| score | integer |
| signal_flag | varchar (nullable) |
| sort_order | integer |

---

### 3.12 ScoreRuleNumericRange

**Bảng**: `score_rule_numeric_ranges`

| Column | Type |
|--------|------|
| rule_id | FK |
| min_value, max_value | float (nullable) — inclusive range |
| score | integer |
| signal_flag | varchar (nullable) |
| sort_order | integer |

---

### 3.13 ScoreBand

**Bảng**: `score_bands`

| Column | Type | Notes |
|--------|------|-------|
| assessment_code | FK logic | |
| band_code | varchar | e.g. `MANUAL_OPERATION`, `AI_READY` |
| label | varchar | |
| description | text (nullable) | |
| min_score, max_score | decimal(5,2) | Band score range |
| default_min, default_max | decimal(5,2) | Original range (để reset) |
| is_dynamic | bool | |
| lead_temperature | varchar | `hot`, `warm`, `cold` |
| sort_order | integer | |

**Scopes**: `forAssessment(code)`, `ordered()`

---

### 3.14 MaturityLevel (Legacy)

**Bảng**: `maturity_levels` — thay thế bởi ScoreBand trong các assessment mới

| Column | Type |
|--------|------|
| assessment_code | FK logic |
| level_code, label, description | varchar/text |
| min_score, max_score | decimal(5,2) |
| sort_order | integer |

---

### 3.15 Persona + PersonaCondition

**Bảng**: `personas`

| Column | Type |
|--------|------|
| assessment_code | FK logic |
| persona_code, label | varchar |
| description | text (nullable) |
| sort_order | integer |

**Bảng**: `persona_conditions`

| Column | Type | Notes |
|--------|------|-------|
| persona_id | FK | |
| target_type | varchar | `domain`, `section`, `overall`, `signal_flag` |
| target_code | varchar (nullable) | domain_code / flag_code / "overall" |
| operator | varchar | `<`, `<=`, `=`, `>=`, `>` |
| threshold_value | decimal(5,2) (nullable) | Score threshold |
| flag_value | bool (nullable) | Expected flag value |

---

### 3.16 PainPointRule

**Bảng**: `pain_point_rules`

| Column | Type | Notes |
|--------|------|-------|
| assessment_code | FK logic | |
| pain_point_code | varchar | |
| label | varchar (nullable) | |
| required_flags | varchar | CSV: `flag1,flag2,!flag3` (! = NOT) |
| is_active | bool | |

---

### 3.17 RecommendationRule

**Bảng**: `recommendation_rules`

| Column | Type | Notes |
|--------|------|-------|
| assessment_code | FK logic | |
| recommendation_code, label | varchar | |
| description | text (nullable) | |
| trigger_domain | varchar | Domain code trigger |
| threshold_score | decimal(5,2) | Trigger khi normalized_score < threshold |
| priority | integer | |
| is_active | bool | |

---

### 3.18 RoadmapPhase + RoadmapMilestone

**Bảng**: `roadmap_phases`

| Column | Type |
|--------|------|
| assessment_code | FK logic |
| band_code | varchar (nullable) — modern |
| maturity_level | varchar (nullable) — legacy |
| phase_code, title | varchar |
| description | text (nullable) |
| duration_weeks | integer (nullable) |
| sort_order | integer |

**Bảng**: `roadmap_milestones`

| Column | Type |
|--------|------|
| phase_id | FK |
| title | varchar |
| description | text (nullable) |
| sort_order | integer |

---

### 3.19 SurveyResult + Result children

**Bảng**: `survey_results`

| Column | Type |
|--------|------|
| response_id | FK (unique) |
| overall_score | decimal(5,2) |
| maturity_level | varchar — band/level code |
| assessment_code | FK logic |
| weight_version | integer (nullable) |
| calculated_at | timestamp |

**Child tables** (FK → result_id):

| Bảng | Columns |
|------|---------|
| `result_domain_scores` | domain_code, raw_score, normalized_score |
| `result_signal_flags` | flag_code, flag_value (bool) |
| `result_pain_points` | pain_point_code |
| `result_recommendations` | recommendation_code, priority |
| `result_roadmap_phases` | phase_id (FK), sort_order |
| `result_question_scores` | question_code, feature_code, raw_score, final_score, selected_options |
| `result_classifications` | classification_type, band_code, passed, persona_code, match_score |

---

### 3.20 ScoringFeedback

**Bảng**: `scoring_feedback`

| Column | Type | Notes |
|--------|------|-------|
| result_id | FK | |
| assessment_code | FK logic | |
| predicted_band | varchar (nullable) | Auto-filled sau mỗi lần chấm |
| actual_band | varchar (nullable) | Admin fill via T6 UI |
| predicted_score, actual_score | decimal(5,2) (nullable) | |
| feedback_source | varchar (nullable) | `system` |
| is_processed | bool | Đã dùng để improve model chưa |

---

### 3.21 PassFailConfig

**Bảng**: `pass_fail_configs`

| Column | Type |
|--------|------|
| assessment_code | FK logic |
| passing_score | decimal(5,2) |
| label_pass, label_fail | varchar |

---

### 3.22 SubmissionBehaviorLog

**Bảng**: `submission_behavior_logs`

| Column | Type | Notes |
|--------|------|-------|
| response_id | FK | |
| question_code | varchar (nullable) | field_key |
| event_type | varchar | `question_focus`, `question_blur`, `answer_changed`, `answer_cleared`, `section_entered`, `time_spent` |
| event_value | varchar (nullable) | |
| sequence_no | integer | |
| occurred_at | timestamp | |

---

## 4. Scoring Pipeline

Pipeline chạy trong `ScoringEngineService::calculate()`. Tất cả trong 1 request (sync) hoặc queued job (async sau submit).

```
AnswerReader → FeatureExtractor → WeightRepository
     ↓                ↓                 ↓
SurveyAnswer[]   RawScores[]     DomainWeights[]
                 SignalFlags[]
                      ↓
              AggregationFactory
                (flat_sum / weighted_domain / sectioned)
                      ↓
               AggregatedResult
               (overallScore, domainScores[])
                      ↓
            ClassificationFactory
            (score_band / pass_fail / persona_match / none)
                      ↓
             ClassificationResult
                      ↓
       ┌──────────────┼──────────────┐
       ↓              ↓              ↓
PainPointDetector  RecommendationEngine  RoadmapLoader
       ↓              ↓              ↓
  PainPoints[]   Recommendations[]  Roadmap[]
                      ↓
               ResultPersister (transaction)
                      ↓
               SurveyResult (DB)
```

### 4.1 AnswerReader

**Input**: responseId, surveyId  
**Output**: `array<field_key, {type, value?, values?}>`
- `type: boolean` → `value: bool`
- `type: choice` → `values: string[]`
- `type: number` → `value: float`

---

### 4.2 FeatureExtractor (Tầng 1)

Áp dụng `score_rules` lên answers → domain scores + signal flags.

**Scoring types**:

| Type | Logic |
|------|-------|
| `boolean` | `score_if_true` hoặc `score_if_false` |
| `single_choice` | Tìm option match → lấy `ScoreRuleOption.score` |
| `multi_choice` | Sum option scores → clamp bởi `min_score_cap`/`max_score_cap` |
| `numeric_range` | Tìm range match → lấy `ScoreRuleNumericRange.score` |

**Signal flag**: mỗi rule hoặc option có thể emit `signal_flag = true/false`.

---

### 4.3 WeightRepository

Đọc weights từ `assessment_domains.weight` (static, v1).  
**Output**: `{weights: {domain_code: float}, version: 1}`

---

### 4.4 AggregationFactory + Strategies (Tầng 2)

| Strategy | Logic |
|----------|-------|
| `FlatSumAggregation` | `overall = Σ raw_scores` (không phân domain) |
| `WeightedDomainAggregation` | `normalized_domain = (raw - min) / (max - min) * 100`, `overall = Σ(normalized * weight)` |
| `SectionedAggregation` | Aggregate per section, normalize per section |

**Output**: `AggregatedResult {overallScore, domainScores[], sectionScores[]}`

---

### 4.5 ClassificationFactory + Strategies (Tầng 3)

| Strategy | Logic |
|----------|-------|
| `ScoreBandClassification` | Find band mà `min_score ≤ overall ≤ max_score` |
| `PassFailClassification` | `passed = overall >= PassFailConfig.passing_score` |
| `PersonaMatchClassification` | Soft match: `match_score = met_conditions / total_conditions`, pick best persona (tie → sort_order thấp hơn) |
| `NoneClassification` | Không phân loại, trả null |

---

### 4.6 PainPointDetector

**Logic**: Iterate `pain_point_rules`, parse `required_flags` CSV:
- `flag_code` → flag phải = true
- `!flag_code` → flag phải = false hoặc absent
- AND logic: tất cả conditions phải pass

**Output**: `string[]` danh sách pain_point_code được kích hoạt

---

### 4.7 RecommendationEngine

**Logic**: Nếu `domain_normalized_score < recommendation_rule.threshold_score` → recommend.  
**Output**: `RecommendationResult[] {code, label, description, priority}` sorted by priority

---

### 4.8 RoadmapLoader

**Logic**: Load `RoadmapPhase` theo `band_code` (hoặc fallback `maturity_level`) kèm milestones.  
**Output**: `RoadmapPhaseResult[] {phaseCode, title, description, durationWeeks, milestones[]}`

---

### 4.9 ResultPersister (Transaction)

Xóa result cũ (nếu force) → Insert:
1. `survey_results`
2. `result_domain_scores` (batch)
3. `result_signal_flags` (batch)
4. `result_pain_points` (batch)
5. `result_recommendations` (batch)
6. `result_question_scores` (batch)
7. `result_roadmap_phases` (batch)
8. `result_classifications`
9. `scoring_feedback` (predicted_band = classification result)

---

### 4.10 ScoreNormalizer

```
normalized = ((raw - min) / (max - min)) * 100
```
Clamp: `[0, 100]`

---

### 4.11 Data Transfer Objects (DTOs)

| Class | Fields |
|-------|--------|
| `ScoringResult` | overallScore, assessmentCode, weightVersion, classification, domainScores[], sectionScores[], signalFlags{}, painPoints[], recommendations[], roadmap[], questionScores{} |
| `DomainScoreResult` | domainCode, rawScore, normalizedScore |
| `ClassificationResult` | classificationType, bandCode, passed, personaCode, matchScore, label |
| `RecommendationResult` | code, label, description, priority |
| `RoadmapPhaseResult` | phaseCode, title, description, durationWeeks, milestones[] |
| `ScoringConfig` | assessment, domains, scoreRules, painPointRules, recommendations |
| `AggregatedResult` | overallScore, domainScores[], sectionScores[] |

---

## 5. Views

| File | Purpose | Data |
|------|---------|------|
| `surveys/index.blade.php` | Danh sách surveys — Tabulator table | statuses |
| `surveys/create.blade.php` | Form tạo survey mới | — |
| `surveys/edit.blade.php` | Schema builder: sections, fields, options (Alpine.js) | survey, sectionsData, fieldTypes, isLocked |
| `tokens/index.blade.php` | Token list + create/revoke/delete | survey, tokens |
| `responses/index.blade.php` | Danh sách responses — Tabulator table | survey, totalAll, totalComplete |
| `responses/show.blade.php` | Chi tiết response + answers grouped by section | survey, response, sections |
| `stats/index.blade.php` | Stats dashboard: submission timeline, scoring distribution, avg domain scores | survey, stats, byDay, scoringData |
| `scoring/index.blade.php` | Scoring wizard — 7 tabs (Alpine.js SPA): ① Khai báo → ② Domains → ③ Rules → ④ Bands → ⑤ Outputs → ⑥ Roadmap → ⑦ Review | survey, assessmentCode |
| `results/public.blade.php` | Trang kết quả public cho respondent | survey, response, result, recLabels, painLabels, domainLabels, maturityInfo |
| `results/show.blade.php` | Admin xem chi tiết result + admin feedback panel | survey, response, result, recLabels, domainLabels, feedback, bands |
| `results/summary.blade.php` | Tổng hợp scoring: maturity distribution, avg domain scores, overall avg | survey, maturityDistribution, avgDomainScores, avgOverall, totalScored, maturityLevels, domains |

### Scoring Wizard Tabs

| Tab | Nội dung |
|-----|---------|
| ① Khai báo | has_scoring toggle, aggregation_model, classification_type |
| ② Domains | domain_code, label, weight, min/max_score (với validation tổng weight = 1.0) |
| ③ Rules | Per-field scoring config: scoring_type, options/ranges, signal_flags, domain assignment |
| ④ Bands | Score bands (score_band type) hoặc Pass-fail config hoặc Personas + conditions |
| ⑤ Outputs | Pain point rules + Recommendation rules |
| ⑥ Roadmap | Roadmap phases + milestones per band |
| ⑦ Review | Checklist validate + dry-run testing + save |

---

## 6. Jobs

### 6.1 CalculateSurveyScoreJob

| Property | Value |
|----------|-------|
| Queue | default |
| Tries | 3 |
| Backoff | 30s |
| Purpose | Async calculate scoring sau submit |
| Handle | `CalculateSurveyScoreAction::handle(responseId, force=false)` |

### 6.2 ExportSurveyResponsesJob

| Property | Value |
|----------|-------|
| Queue | default |
| Timeout | 600s |
| Purpose | Xuất responses > 10k rows ra Excel |
| Process | LazyCollection cursor → chunk 2000 → XLSX → storage/app/exports/{key}.xlsx → key lưu Redis (TTL 1h) |

### 6.3 UpdateTokenLastUsedJob

| Property | Value |
|----------|-------|
| Queue | default |
| Tries | 3 |
| Purpose | Async update `survey_tokens.last_used_at = now()` |

---

## 7. Middlewares

### 7.1 ValidateSurveyToken (API)

**Dùng cho**: Tất cả public API endpoints  
**Logic**:
1. Extract `Authorization: Bearer {token}`
2. Hash SHA-256
3. Find SurveyToken by hash
4. Kiểm tra `isValid()` (active + not expired)
5. Kiểm tra `token.survey.slug == route slug`
6. Dispatch `UpdateTokenLastUsedJob` (async)
7. Set `request.attributes: surveyToken, survey`

**Errors**: 401 (invalid/missing), 403 (wrong survey)

---

### 7.2 ValidateSurveyWebToken (Web)

**Dùng cho**: Public result page (`/surveys/{slug}/result`)  
**Logic**:
1. Extract `?token=` query param
2. Hash + validate (same logic as above)
3. Set `request.attributes: survey`

**Errors**: 403 (invalid/expired/unauthorized)

---

### 7.3 ValidateSurveyTurnstile

**Dùng cho**: `POST /v1/surveys/{slug}/submit`  
**Skip khi**: `app()->isLocal()`, `app()->runningUnitTests()`, `TURNSTILE_ENABLED=false`, key chưa cấu hình  
**Validate**: `cf-turnstile-response` field qua Cloudflare Turnstile API

---

## 8. Actions

| Action | Purpose | Input | Output |
|--------|---------|-------|--------|
| `CreateSurveyAction` | Tạo survey mới | validated data | Survey |
| `UpdateSurveyAction` | Cập nhật survey | Survey + data | Survey |
| `ActivateSurveyAction` | Đổi status → active | Survey | void |
| `CreateSectionAction` | Tạo section | Survey + data | SurveySection |
| `UpdateSectionAction` | Cập nhật section | Section + data | SurveySection |
| `DestroySectionAction` | Xóa section | Section | void |
| `CreateFieldAction` | Tạo field | Survey + data | SurveyField |
| `UpdateFieldAction` | Cập nhật field | Field + data | SurveyField |
| `DeactivateFieldAction` | Toggle is_active | Field | SurveyField |
| `DestroyFieldAction` | Xóa field | Field | void |
| `CreateOptionAction` | Tạo option | Field + data | SurveyFieldOption |
| `UpdateOptionAction` | Cập nhật option | Option + data | SurveyFieldOption |
| `DestroyOptionAction` | Xóa option | Option | void |
| `ReorderAction` | Reorder items | items[] + model | void |
| `GenerateSurveyTokenAction` | Tạo token mới | Survey + data | `{token, plain}` |
| `RevokeSurveyTokenAction` | Thu hồi token | Token | void |
| `DeleteSurveyTokenAction` | Xóa token | Token | void |
| `SubmitSurveyAction` | Process submission | Request + survey | SurveyResponse (dispatch scoring job) |
| `BuildSurveySchemaAction` | Build schema (cached) | Survey | SurveySchemaData |
| `CalculateSurveyScoreAction` | Run scoring pipeline | responseId, force | ScoringResult |
| `ExportSurveyResponsesAction` | Export responses | Survey + filters | StreamedResponse hoặc dispatch job |

---

## 9. Seeders

### SurveyDatabaseSeeder
Root seeder → gọi các seeders con.

### AiReadinessSurveySeeder
Seed survey mẫu AI Readiness với:
- Survey record (slug: `ai-readiness`)
- Sections + fields đầy đủ (multiple choice, yes/no, scale, text, ...)
- Survey token mặc định

### ScoringConfigSeeder
Seed complete scoring config cho `ai_workflow_v1`:

| Thành phần | Data |
|-----------|------|
| Assessment | `ai_workflow_v1`, weighted_domain, score_band |
| Domains | workflow (25%), sales (20%), hr (15%), data (20%), ai (20%) |
| Score Bands | MANUAL_OPERATION (0–30), DIGITAL_FOUNDATION (31–60), AI_READY (61–80), AI_TRANSFORMATION (81–100) |
| ScoreRules | Nhiều rules với options và numeric ranges |
| PainPointRules | Các pain points dựa trên signal flags |
| RecommendationRules | Recommendations theo domain score |
| Roadmap | Phases + milestones per band |

---

## 10. Migrations — Bảng DB

| File | Bảng | Mục đích |
|------|------|---------|
| `000004_create_surveys_table` | `surveys` | Survey records |
| `000005_create_survey_responses_table` | `survey_responses` | Submission records |
| `000006_create_survey_sections_table` | `survey_sections` | Survey sections |
| `000008_create_survey_tokens_table` | `survey_tokens` | API tokens |
| `000010_create_survey_fields_table` | `survey_fields` | Field definitions |
| `000011_create_survey_field_options_table` | `survey_field_options` | Choice options |
| `000012_create_survey_answers_table` | `survey_answers` | Submitted answers |
| `000014_create_assessment_domains_table` | `assessment_domains` | Scoring domains |
| `000015_create_maturity_levels_table` | `maturity_levels` | Legacy maturity classification |
| `000016_create_score_rules_table` | `score_rules` | Scoring rules per field |
| `000017_create_score_rule_options_table` | `score_rule_options` | Option scores |
| `000019_create_recommendation_rules_table` | `recommendation_rules` | Recommendation logic |
| `000020_create_roadmap_phases_table` | `roadmap_phases` | Roadmap phase definitions |
| `000021_create_roadmap_milestones_table` | `roadmap_milestones` | Roadmap milestones |
| `000022_create_survey_results_table` | `survey_results` | Scoring result records |
| `000023_create_result_domain_scores_table` | `result_domain_scores` | Per-domain scores |
| `000024_create_result_signal_flags_table` | `result_signal_flags` | Emitted signal flags |
| `000025_create_result_pain_points_table` | `result_pain_points` | Detected pain points |
| `000026_create_result_recommendations_table` | `result_recommendations` | Triggered recommendations |
| `000027_create_result_roadmap_phases_table` | `result_roadmap_phases` | Assigned roadmap |
| `000029_create_assessments_table` | `assessments` | Assessment config |
| `000031_create_score_rule_numeric_ranges_table` | `score_rule_numeric_ranges` | Numeric range rules |
| `000036_create_score_bands_table` | `score_bands` | Score band definitions |
| `000038_create_personas_tables` | `personas`, `persona_conditions` | Persona definitions |
| `000039_create_result_classifications_table` | `result_classifications` | Classification results |
| `000040_add_weight_version_to_survey_results` | `survey_results` | + weight_version column |
| `000041_create_result_question_scores_table` | `result_question_scores` | Per-question scores |
| `000043_create_scoring_feedback_table` | `scoring_feedback` | Admin feedback loop |
| `000044_create_submission_behavior_logs_table` | `submission_behavior_logs` | User behavior events |
| `000050_drop_unused_scoring_infrastructure` | — | Drop behavior/feature weight tables |
| `000051_make_score_rules_domain_code_nullable` | `score_rules` | domain_code nullable |
| `000052_make_pain_rec_label_nullable` | `pain_point_rules`, `recommendation_rules` | label nullable |
| `000053_drop_job_position_tables` | — | Drop job_positions, result_job_positions |

---

## Tổng kết

Module Survey là một **hệ thống hoàn chỉnh** gồm 4 lớp chức năng:

| Lớp | Chức năng |
|-----|----------|
| **Survey Builder** | CRUD surveys, sections, fields, options; token management; schema builder UI |
| **Submission Engine** | Public API submit, bot detection (Turnstile), behavior logging, response management |
| **Scoring Engine** | 4-tầng pipeline: Feature Extraction → Aggregation → Classification → Enrichment |
| **Result & Reporting** | Public result page, admin result review, admin feedback (actual_band), summary stats, export |

**Kỹ thuật nổi bật**:
- Schema cache Redis (TTL 30 phút)
- Token auth SHA-256 hash
- Async jobs: scoring, export, token tracking
- Multi-strategy aggregation (flat_sum / weighted_domain / sectioned)
- Multi-strategy classification (score_band / pass_fail / persona_match / none)
- Soft-match persona algorithm
- Behavior event logging
- Streaming export ≤10k rows, queue export >10k rows
- Admin feedback loop (`scoring_feedback`) để track predicted vs actual band
