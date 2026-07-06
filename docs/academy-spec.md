# Academy Module — Lộ trình học tập & Luyện tập trắc nghiệm (Đặc tả kỹ thuật)

> **Pattern stack:** AVSA + CQRS-lite + Laravel Modules (NWIDART 13) + Laravel Actions (lorisleiva 2.x)
> **Module tham chiếu kiến trúc:** `Modules/OcopRubric` (xem `docs/ocop-rubric-spec.md`) và `Modules/Subscription` (xem `docs/SUBSCRIPTION_SPEC.md`) — 2 module Feature-first mới nhất, dùng làm khuôn cho spec này. **Không** dùng `Modules/KcItem` (layer-first, `Actions/Backend` + `Data/Requests`) làm khuôn vì đã là pattern cũ.
> **Spec version:** 1.0 — 2026-07-05
> **Nguồn tham khảo nghiệp vụ:** 4 ảnh chụp màn hình tại `docs/quizz/` (nền tảng học tập tham khảo bên ngoài — Lộ trình/Chương/Bài học/Luyện tập trắc nghiệm)

---

## 1. Bối cảnh & quyết định đã thống nhất với stakeholder

`docs/quizz/` mô tả một trải nghiệm học tập gồm:
1. **Lộ trình** (vd "AAAAAAAA") chia thành **Chương** (Chương 1–5), mỗi chương có nhiều **Bài học**.
2. Trang chi tiết bài học gồm: tên song ngữ VI/EN, nhóm chủ đề (tag), **Định nghĩa**, **Mục đích sử dụng**, **Các bước áp dụng & ví dụ thực tiễn**, nút **"Đã nắm"**, điều hướng "Bài tiếp".
3. Tab **"Luyện tập"**: quiz trắc nghiệm 1 đáp án đúng/4 lựa chọn A–D, có progress bar "Câu N/Tổng".
4. Tab **"Mốc & thi"** — xuất hiện trên UI nhưng **chưa có đặc tả nội dung** trong ảnh mẫu.

Quyết định đã chốt (hỏi trực tiếp qua `AskUserQuestion`, không suy đoán):

| Câu hỏi | Quyết định | Lý do |
|---|---|---|
| Tái dùng `RoadmapPhase`/`RoadmapMilestone` (đã có trong `Modules/Assessment`) hay xây độc lập? | **Xây lộ trình học tập độc lập mới** | `RoadmapPhase`/`RoadmapMilestone` bị khoá cứng vào `assessment_code`+`maturity_level` (chỉ sinh ra từ kết quả chấm điểm trưởng thành số), không cho phép tạo lộ trình đặt tên tự do như ảnh mẫu |
| Phạm vi đợt này? | **Chỉ "Luyện tập" (quiz trắc nghiệm)** | Tab "Mốc & thi" chưa có đặc tả nghiệp vụ — để tránh xây nửa vời, dời sang phase sau khi có yêu cầu cụ thể |
| Tên module? | **`Academy`** | Ngắn gọn, không đụng độ thuật ngữ "Roadmap" đang dùng trong `Assessment` |

---

## 2. Đặt tên & vị trí module

**Tên module: `Academy`** (`Modules/Academy`).

Lý do không dùng lại module có sẵn:

| Ứng viên | Vì sao không phù hợp |
|---|---|
| `Modules/Assessment` (`RoadmapPhase`/`RoadmapMilestone`) | Cấu trúc Phase→Milestone chỉ tồn tại như *kết quả suy ra* từ 1 bản chấm điểm trưởng thành (`assessment_code`+`maturity_level`+`band_code`), không phải một thực thể "khoá học" độc lập có thể đặt tên, publish, và học tuỳ ý. Ép Academy vào đây sẽ trộn domain "kết quả đánh giá" với domain "nội dung học tập biên tập thủ công". |
| `Modules/KcItem`/`KcCategory` | Là kho tài liệu tổng quát (`type`: document/sop/video/form/faq/case_study/policy), nội dung là 1 cột `content` (longText) không có field riêng Định nghĩa/Mục đích/Bước áp dụng, và không có khái niệm quiz có đáp án đúng. `KcLearningProgress` đã có nhưng gắn với `KcItem`, ngữ nghĩa khác `AcademyLessonProgress` (bài học có cấu trúc Chương/Lộ trình bao quanh). |
| `Modules/Survey` | Engine khảo sát chấm điểm trưởng thành (`survey_fields`, `score_rules`) — xác nhận **không có bất kỳ cột `is_correct`/`correct_option` nào trong toàn bộ repo**. Đây là form-builder tự do phục vụ tính điểm tổng hợp, không có khái niệm "đáp án đúng duy nhất" của 1 câu trắc nghiệm luyện tập. |

→ Module độc lập, thuộc **Platform Core** (ngang hàng `KcItem`/`Assessment` ở Layer 4 theo `docs/PLATFORM_DESIGN.md` §2.1), vì nội dung học tập dùng chung cho mọi tổ chức/vertical, không phải tính năng riêng của 1 vertical.

---

## 3. Phạm vi module (Scope Boundary)

### 3.1 Trong phạm vi (đợt này)

1. Quản trị nội dung học tập theo cây **Lộ trình → Chương → Bài học** (CRUD, publish/draft, sắp xếp thứ tự).
2. Học viên duyệt lộ trình, đọc bài học, đánh dấu **"Đã nắm"** / hoàn tác.
3. Quản trị **ngân hàng câu hỏi trắc nghiệm** gắn theo từng bài học (câu hỏi + 4 lựa chọn, 1 đáp án đúng).
4. **Luyện tập**: học viên bắt đầu 1 lượt luyện tập theo lộ trình (tuỳ chọn giới hạn theo chương), trả lời tuần tự, nộp bài, xem điểm — chấm điểm 100% phía server.

### 3.2 Ngoài phạm vi (cố ý không làm ở đây — chờ đặc tả riêng)

| Nghiệp vụ | Vì sao không làm ở đây |
|---|---|
| Tab **"Mốc & thi"** (milestone/kỳ thi có chứng chỉ) | Ảnh mẫu chỉ hiện tên tab, chưa có đặc tả luồng nghiệp vụ (chấm đỗ/trượt? có time limit? có chứng chỉ?) — làm bây giờ sẽ là đoán mò |
| Gamification (điểm XP, huy hiệu, bảng xếp hạng) | Không có trong ảnh mẫu, không nằm trong yêu cầu |
| Ghi danh (enrollment) tường minh — "user đăng ký lộ trình X" | MVP để mọi user trong org thấy tất cả lộ trình `published` của org mình; tiến độ suy ra từ `academy_lesson_progress`/`academy_quiz_attempts` đã có, không cần bảng ghi danh riêng. Bổ sung sau nếu cần giới hạn theo phòng ban/nhóm |
| Đồng bộ với `RoadmapPhase`/`RoadmapMilestone` của `Assessment` (gợi ý lộ trình theo kết quả đánh giá) | Ranh giới domain — có thể làm ở phase sau bằng cách `RoadmapMilestone` trỏ tới `AcademyPath`/`AcademyLesson` qua 1 bảng liên kết riêng, không sửa Academy |

---

## 4. Nguyên tắc kiến trúc

| Nguyên tắc | Áp dụng trong `Academy` |
|---|---|
| **AVSA + CQRS-lite** | `Features/{Slice}/Actions` (write, `AsAction`) + `Features/{Slice}/Queries` (`*Query` + `*Handler`, implement `QueryInterface`/`QueryHandlerInterface` từ `app/Shared/Contracts`) — không có business logic trong Controller |
| **Toàn bộ dữ liệu Academy là tenant-scoped** | Khác `OcopRubric` (có phần system-level dùng chung), Academy **không có** khái niệm nội dung dùng chung toàn hệ thống — mỗi tổ chức tự biên soạn lộ trình riêng. Mọi bảng "top-level" (`academy_paths`, `academy_chapters`, `academy_lessons`, `academy_tags`, `academy_quiz_questions`, `academy_quiz_attempts`) extend `App\Foundation\Models\TenantAwareModel` |
| **No JSON storage** | Không có cột JSON ở bất kỳ đâu — 4 đáp án trắc nghiệm là bảng quan hệ `academy_quiz_options`, không lưu mảng JSON |
| **Server-side correctness** | `is_correct` không bao giờ được gửi/tin từ client. Khi trả lời 1 câu, server tự tra `academy_quiz_options.is_correct` theo `selected_option_id` để tính đúng/sai — client chỉ gửi `option_id` đã chọn (giống nguyên tắc "server-side point lookup" của `OcopRubric` §18) |
| **Không rò rỉ đáp án đúng trước khi trả lời** | API/Resource trả câu hỏi đang làm **không** bao giờ include field `is_correct` của các option — chỉ trả về sau khi đã có `selected_option_id` cho câu đó (đã trả lời) hoặc sau khi `submit` |
| **Bất biến sau khi nộp bài** | Sau `SubmitQuizAttemptAction`, không có Action nào sửa lại `academy_quiz_attempt_answers`/`academy_quiz_attempts` của lượt đó — chỉ có thể tạo lượt luyện tập mới. Việc này enforce ở tầng Action (không có `UpdateAttempt*`), không cần constraint DB |
| **Soft deletes** | Trên `AcademyPath`, `AcademyChapter`, `AcademyLesson`, `AcademyQuizQuestion`, `AcademyQuizAttempt` (nội dung/lượt làm bài có thể bị admin xoá mềm). **Không** soft-delete trên `academy_quiz_options`, `academy_lesson_tag`, `academy_quiz_attempt_answers` (bảng con, vòng đời gắn chặt với bảng cha, xoá cứng theo cascade) |
| **UUID public** | `academy_paths.uuid`, `academy_chapters.uuid`, `academy_lessons.uuid`, `academy_quiz_questions.uuid`, `academy_quiz_attempts.uuid` — expose qua route (`/academy/paths/{uuid}`...). Bảng con (`options`, `lesson_tag`, `attempt_answers`) không cần uuid vì không được truy cập trực tiếp qua URL |
| **Tên bảng có tiền tố `academy_`** | Theo đúng tiền lệ `kc_*` (KcItem), `ocop_*` (OcopRubric), `roadmap_*` (Assessment) — không phải "business prefix" bị cấm ở `docs/PLATFORM_DESIGN.md` §10.1 (prefix công ty kiểu `thv_`), mà là tiền tố module giúp tránh đụng tên bảng chung chung (`paths`, `lessons`, `tags`...) |
| **Enum** | PHP 8.1 backed enum class cho mỗi cột trạng thái, cast trên Model. Cột DB là `string` (không dùng `$table->enum()` kiểu MySQL) để dễ thêm giá trị về sau — CHECK constraint DB bỏ qua ở môi trường dev SQLite (theo đúng thực tế 2 module tham chiếu, không phải mọi nguyên tắc trong `PLATFORM_DESIGN.md` §10.1 đều được áp cứng ở tầng DB) |

---

## 5. Directory Structure (AVSA Feature-first)

```
Modules/Academy/
├── app/
│   ├── Features/
│   │   ├── ContentAuthoring/        ← Slice: admin biên soạn Lộ trình→Chương→Bài học→Câu hỏi
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePathAction.php / UpdatePathAction.php / ArchivePathAction.php / PublishPathAction.php
│   │   │   │   ├── CreateChapterAction.php / UpdateChapterAction.php / DeleteChapterAction.php / ReorderChaptersAction.php
│   │   │   │   ├── CreateLessonAction.php / UpdateLessonAction.php / PublishLessonAction.php / DeleteLessonAction.php / ReorderLessonsAction.php
│   │   │   │   └── CreateQuizQuestionAction.php / UpdateQuizQuestionAction.php / PublishQuizQuestionAction.php / DeleteQuizQuestionAction.php
│   │   │   ├── Data/           (PathData, ChapterData, LessonData, QuizQuestionData + QuizOptionData[] lồng nhau)
│   │   │   ├── Events/         (LessonPublished, QuizQuestionPublished)
│   │   │   ├── Queries/        (ListPathsForAdminQuery/Handler, GetPathDetailForAdminQuery/Handler, ListQuizQuestionsByLessonQuery/Handler)
│   │   │   └── Http/           (PathAdminController, ChapterAdminController, LessonAdminController, QuizQuestionAdminController)
│   │   │
│   │   ├── LearnerJourney/          ← Slice: học viên duyệt lộ trình, đọc bài, đánh dấu "Đã nắm"
│   │   │   ├── Actions/         (StartLessonAction, MarkLessonMasteredAction, UnmarkLessonMasteredAction)
│   │   │   ├── Queries/         (ListActivePathsQuery/Handler, GetPathOverviewQuery/Handler, GetLessonDetailQuery/Handler)
│   │   │   └── Http/            (LearnerPathController, LearnerLessonController)
│   │   │
│   │   └── QuizPractice/            ← Slice: engine luyện tập trắc nghiệm
│   │       ├── Actions/         (StartQuizAttemptAction, AnswerQuizAttemptQuestionAction, SubmitQuizAttemptAction)
│   │       ├── Events/          (QuizAttemptSubmitted)
│   │       ├── Queries/         (GetAttemptProgressQuery/Handler, GetAttemptResultQuery/Handler)
│   │       └── Http/            (QuizAttemptController)
│   │
│   ├── Models/         (AcademyPath, AcademyChapter, AcademyLesson, AcademyTag, AcademyLessonProgress,
│   │                     AcademyQuizQuestion, AcademyQuizOption, AcademyQuizAttempt, AcademyQuizAttemptAnswer)
│   ├── Enums/           (PublishStatus, LessonProgressStatus, QuizAttemptStatus)
│   ├── Observers/       (nếu cần auto-slug — xem §7)
│   ├── Policies/        (AcademyContentPolicy, AcademyQuizAttemptPolicy)
│   └── Providers/       (AcademyServiceProvider, EventServiceProvider, RouteServiceProvider)
│
├── config/config.php     → ['name' => 'Academy', 'quiz_questions_per_attempt' => 10]
├── database/
│   ├── migrations/       (10 file, xem §7)
│   └── seeders/          (AcademyPermissionSeeder, AcademyDemoContentSeeder, AcademyDatabaseSeeder)
├── resources/views/      (admin/, learner/, quiz/)
├── routes/{web.php, api.php}
├── module.json, composer.json
```

Ghi chú: `Modules/PLATFORM_DESIGN.md` §12.1 còn mô tả cấu trúc layer-first cũ (`Actions/Backend`, `Data/Requests`, `Queries/` phẳng) — Academy **chủ động lệch khỏi §12.1** để đi theo pattern Feature-first mới hơn đã kiểm chứng ở `Subscription`/`OcopRubric`, đúng tinh thần "sửa doc sau, code đi trước" đã thấy ở 2 module đó.

---

## 6. Data Model — ERD tổng quan

```
AcademyPath (1) ──< (n) AcademyChapter (1) ──< (n) AcademyLesson
                                                         │
                                            ┌────────────┼─────────────┐
                                            │            │             │
                                     (n) AcademyTag   (1)│      (n) AcademyQuizQuestion
                                     (qua pivot)          │             │
                                                  AcademyLessonProgress │ (1)
                                                  (per user)            │
                                                                 (n) AcademyQuizOption

AcademyPath (1) ──< (n) AcademyQuizAttempt (1) ──< (n) AcademyQuizAttemptAnswer >── (1) AcademyQuizQuestion
                                                                                >── (0..1) AcademyQuizOption (đã chọn)
User (1) ──< (n) AcademyLessonProgress
User (1) ──< (n) AcademyQuizAttempt
```

---

## 7. Data Model — Chi tiết từng bảng

### 7.1 `academy_paths` — Lộ trình

```php
Schema::create('academy_paths', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->string('title', 200);
    $table->string('slug', 220);
    $table->text('description')->nullable();
    $table->string('status', 20)->default('draft');   // PublishStatus: draft|published|archived
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'slug'], 'uq_academy_path_org_slug');
    $table->index(['organization_id', 'status'], 'idx_academy_path_org_status');
});
```

### 7.2 `academy_chapters` — Chương

```php
Schema::create('academy_chapters', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('path_id')->constrained('academy_paths')->cascadeOnDelete();
    $table->string('title', 200);
    $table->text('description')->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['organization_id', 'path_id'], 'idx_academy_chapter_org_path');
    $table->index(['path_id', 'sort_order'], 'idx_academy_chapter_order');
});
```

### 7.3 `academy_lessons` — Bài học

```php
Schema::create('academy_lessons', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('chapter_id')->constrained('academy_chapters')->cascadeOnDelete();
    $table->string('title', 300);        // tên tiếng Việt, vd "Tăng trưởng qua Marketing kỹ thuật số"
    $table->string('subtitle', 300)->nullable(); // tên tiếng Anh, vd "Digital Marketing Growth"
    $table->string('slug', 320);
    $table->text('definition')->nullable();      // "1. Định nghĩa"
    $table->text('purpose')->nullable();         // "2. Mục đích sử dụng"
    $table->longText('steps_examples')->nullable(); // "3. Các bước áp dụng và ví dụ thực tiễn"
    $table->string('status', 20)->default('draft'); // PublishStatus: draft|published
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'slug'], 'uq_academy_lesson_org_slug');
    $table->index(['organization_id', 'chapter_id'], 'idx_academy_lesson_org_chapter');
    $table->index(['chapter_id', 'sort_order'], 'idx_academy_lesson_order');
    $table->index(['organization_id', 'status'], 'idx_academy_lesson_org_status');
});
```

### 7.4 `academy_tags` — Nhóm chủ đề (vd "Marketing, Bán hàng & CRM")

```php
Schema::create('academy_tags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->string('name', 120);
    $table->string('slug', 140);
    $table->timestamps();

    $table->unique(['organization_id', 'slug'], 'uq_academy_tag_org_slug');
});
```

### 7.5 `academy_lesson_tag` — pivot Bài học ↔ Nhóm chủ đề

```php
Schema::create('academy_lesson_tag', function (Blueprint $table) {
    $table->foreignId('lesson_id')->constrained('academy_lessons')->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained('academy_tags')->cascadeOnDelete();
    $table->primary(['lesson_id', 'tag_id']);
});
```

### 7.6 `academy_lesson_progress` — trạng thái "Đã nắm" theo từng user

```php
Schema::create('academy_lesson_progress', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('lesson_id')->constrained('academy_lessons')->cascadeOnDelete();
    $table->string('status', 20)->default('not_started'); // LessonProgressStatus: not_started|in_progress|mastered
    $table->timestamp('started_at')->nullable();
    $table->timestamp('mastered_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'lesson_id'], 'uq_academy_progress_user_lesson');
    $table->index(['organization_id', 'user_id', 'status'], 'idx_academy_progress_org_user_status');
});
```
Model: `Model` thường + trait `BelongsToOrganization` trực tiếp (**không** `TenantAwareModel`) — đây là 1 dòng trạng thái được cập nhật liên tục (toggle Đã nắm/Hoàn tác), không có khái niệm xoá mềm, và không cần activity log chi tiết cho từng lần bấm nút (tránh rác `activity_log`). Cùng lý do `OcopScoringSession` không dùng `TenantAwareModel` trong `docs/ocop-rubric-spec.md` §6.2 — nhưng ở đây bỏ luôn `LogsActivity` vì tần suất update cao và giá trị audit thấp.

### 7.7 `academy_quiz_questions` — Câu hỏi trắc nghiệm

```php
Schema::create('academy_quiz_questions', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('lesson_id')->constrained('academy_lessons')->cascadeOnDelete();
    $table->text('question_text');
    $table->text('explanation')->nullable();   // hiện sau khi trả lời, giải thích đáp án đúng
    $table->string('status', 20)->default('draft'); // PublishStatus: draft|published
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['organization_id', 'lesson_id'], 'idx_academy_question_org_lesson');
    $table->index(['organization_id', 'status'], 'idx_academy_question_org_status');
});
```

### 7.8 `academy_quiz_options` — Đáp án A–D

```php
Schema::create('academy_quiz_options', function (Blueprint $table) {
    $table->id();
    $table->foreignId('question_id')->constrained('academy_quiz_questions')->cascadeOnDelete();
    $table->text('option_text');
    $table->boolean('is_correct')->default(false);
    $table->unsignedTinyInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index('question_id', 'idx_academy_option_question');
});
```
Không có `organization_id` riêng — luôn truy vấn qua `$question->options`, không bao giờ query độc lập xuyên tổ chức. Validate ở tầng `QuizQuestionData` (Action `CreateQuizQuestionAction`/`UpdateQuizQuestionAction`): tối thiểu 2 lựa chọn, **đúng 1** lựa chọn có `is_correct = true` (trắc nghiệm 1 đáp án đúng, khớp ảnh mẫu).

### 7.9 `academy_quiz_attempts` — Lượt luyện tập

```php
Schema::create('academy_quiz_attempts', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('path_id')->constrained('academy_paths')->restrictOnDelete();
    $table->foreignId('chapter_id')->nullable()->constrained('academy_chapters')->nullOnDelete(); // null = luyện tập toàn bộ lộ trình
    $table->string('status', 20)->default('in_progress'); // QuizAttemptStatus: in_progress|submitted|abandoned
    $table->unsignedTinyInteger('total_questions')->default(0);
    $table->unsignedTinyInteger('correct_count')->default(0);
    $table->unsignedTinyInteger('score_percent')->nullable();
    $table->timestamp('started_at');
    $table->timestamp('submitted_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['organization_id', 'user_id', 'path_id'], 'idx_academy_attempt_org_user_path');
    $table->index(['organization_id', 'status'], 'idx_academy_attempt_org_status');
});
```

### 7.10 `academy_quiz_attempt_answers` — Chi tiết từng câu trong 1 lượt

```php
Schema::create('academy_quiz_attempt_answers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('attempt_id')->constrained('academy_quiz_attempts')->cascadeOnDelete();
    $table->foreignId('question_id')->constrained('academy_quiz_questions')->restrictOnDelete();
    $table->foreignId('selected_option_id')->nullable()->constrained('academy_quiz_options')->nullOnDelete();
    $table->boolean('is_correct')->nullable();  // null = chưa trả lời
    $table->unsignedTinyInteger('sequence');    // thứ tự hiển thị trong lượt (random tại lúc bắt đầu, giữ ổn định khi tải lại trang)
    $table->timestamp('answered_at')->nullable();
    $table->timestamps();

    $table->unique(['attempt_id', 'question_id'], 'uq_academy_attempt_answer');
    $table->index(['attempt_id', 'sequence'], 'idx_academy_attempt_answer_sequence');
});
```

---

## 8. Enums (`Modules/Academy/app/Enums/`)

```php
enum PublishStatus: string {
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';   // chỉ dùng cho AcademyPath
}

enum LessonProgressStatus: string {
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Mastered = 'mastered';
}

enum QuizAttemptStatus: string {
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Abandoned = 'abandoned';
}
```
Theo checklist `docs/PLATFORM_DESIGN.md` §12.6, mỗi enum nên có thêm `label(): string` (nhãn tiếng Việt hiển thị UI) — vd `PublishStatus::Draft->label() === 'Nháp'`.

---

## 9. Permissions (RBAC)

Theo template `Modules/BusinessBlueprint/database/seeders/BusinessBlueprintPermissionSeeder.php` — **không** theo pattern Policy lỗi `hasAnyRole(['CEO','System_Admin'])` của `KcItem` (role Title-Case này không tồn tại trong DB, đã xác nhận qua rà soát `RolePermissionSeeder`).

Thêm vào `app/Enums/PermissionEnum.php`:
```php
// ══ ACADEMY (Lộ trình học tập & Luyện tập trắc nghiệm) ═════════
// Tất cả role = View + Practice | System_Admin = Full quản lý nội dung
case ACADEMY_VIEW     = 'academy.view';      // xem lộ trình/chương/bài học
case ACADEMY_PRACTICE = 'academy.practice';  // làm quiz luyện tập, lưu kết quả
case ACADEMY_MANAGE   = 'academy.manage';    // System Admin — CRUD lộ trình/chương/bài học/câu hỏi
```

`config/permissions.php`: thêm `P::ACADEMY_VIEW`, `P::ACADEMY_PRACTICE` vào **cả 8 role block** (mọi nhân viên đều học/luyện tập được — giống cách `BLUEPRINT_VIEW` được cấp cho tất cả role); thêm `P::ACADEMY_MANAGE` chỉ vào `R::ADMIN`.

`Modules/Academy/database/seeders/AcademyPermissionSeeder.php` (clone chính xác cấu trúc `BusinessBlueprintPermissionSeeder`):
```php
class AcademyPermissionSeeder extends Seeder
{
    private const LEARNER_PERMISSIONS = ['academy.view', 'academy.practice'];
    private const MANAGE_PERMISSIONS  = ['academy.manage'];
    private const ALL_ROLES = ['system_admin','ceo','sales','ops','marketing','hr','ai_operator','viewer'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([...self::LEARNER_PERMISSIONS, ...self::MANAGE_PERMISSIONS] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::ALL_ROLES as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo(self::LEARNER_PERMISSIONS);
        }

        Role::where('name', 'system_admin')->where('guard_name', 'web')->first()
            ?->givePermissionTo(self::MANAGE_PERMISSIONS);

        Role::where('name', 'super-admin')->where('guard_name', 'web')->first()
            ?->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
```
`AcademyDatabaseSeeder` gọi `AcademyPermissionSeeder` + `AcademyDemoContentSeeder` (xem §12), đăng ký vào `database/seeders/SystemDataSeeder.php` (append sau `BusinessBlueprintDatabaseSeeder::class`).

**Policy:**
- `AcademyContentPolicy` (Path/Chapter/Lesson/QuizQuestion): `viewAny/view` → `can('academy.view')`; `create/update/delete/publish` → `can('academy.manage')`.
- `AcademyQuizAttemptPolicy`: `view($user, $attempt)`/`answer($user, $attempt)` → `$attempt->user_id === $user->id && $user->can('academy.practice')` (không cho xem/trả lời lượt luyện tập của người khác).

**Sidebar** (`resources/views/layouts/partials/sidebar.blade.php`): block mới gate bằng `@can(\App\Enums\PermissionEnum::ACADEMY_VIEW->value)`, sub-link "Quản lý nội dung" chỉ hiện khi `@can(\App\Enums\PermissionEnum::ACADEMY_MANAGE->value)`.

---

## 10. Feature Slices — Chi tiết

### 10.1 Slice `ContentAuthoring` (quyền `academy.manage`)

- **Actions**: mỗi Action 1 `handle()`, dùng `AsAction`, nhận DTO tương ứng trong `Data/`.
  - `CreatePathAction`/`UpdatePathAction`/`PublishPathAction`/`ArchivePathAction`
  - `CreateChapterAction`/`UpdateChapterAction`/`DeleteChapterAction`/`ReorderChaptersAction` (nhận mảng `[chapter_id => sort_order]`)
  - `CreateLessonAction`/`UpdateLessonAction`/`PublishLessonAction`/`DeleteLessonAction`/`ReorderLessonsAction`
  - `CreateQuizQuestionAction`/`UpdateQuizQuestionAction` — nhận `QuizQuestionData` chứa `options: QuizOptionData[]`; validate đúng 1 `is_correct = true` **trước khi** `DB::transaction` ghi câu hỏi + options; bắn `QuizQuestionPublished` khi status chuyển `published`
  - `PublishQuizQuestionAction`/`DeleteQuizQuestionAction`
- **Queries**: `ListPathsForAdminQuery/Handler` (mọi status), `GetPathDetailForAdminQuery/Handler` (kèm đếm số chương/bài/câu hỏi), `ListQuizQuestionsByLessonQuery/Handler`.
- **Http**: `PathAdminController`, `ChapterAdminController`, `LessonAdminController`, `QuizQuestionAdminController` — resource controllers mỏng, gọi Action/Query, trả Blade view hoặc redirect+flash (giống pattern `KcItemController`).

### 10.2 Slice `LearnerJourney` (quyền `academy.view`)

- **Queries**:
  - `ListActivePathsQuery/Handler` — các `AcademyPath` `status=published` của org, kèm % bài đã "mastered" của user hiện tại (join `academy_lesson_progress`).
  - `GetPathOverviewQuery/Handler` — chi tiết 1 lộ trình: danh sách chương → bài học kèm `progress_status` của user hiện tại, tổng số bài/số bài đã nắm (map đúng khối "Còn X bài học chưa nắm" trong ảnh mẫu 3).
  - `GetLessonDetailQuery/Handler` — nội dung bài học + id bài trước/sau (cho nút "Bài tiếp") + trạng thái tiến độ hiện tại.
- **Actions**:
  - `StartLessonAction` — `firstOrCreate` dòng `academy_lesson_progress`, set `status=in_progress`, `started_at=now()` nếu lần đầu mở bài.
  - `MarkLessonMasteredAction` — set `status=mastered`, `mastered_at=now()`.
  - `UnmarkLessonMasteredAction` — nút "Hoàn tác" trong ảnh mẫu 3, trả về `status=in_progress`.
- **Http**: `LearnerPathController@index/show`, `LearnerLessonController@show` + 2 route action cho mark/unmark.

### 10.3 Slice `QuizPractice` (quyền `academy.practice`)

- **Actions**:
  - `StartQuizAttemptAction(user, path, ?chapter)`:
    1. Lấy tập `academy_quiz_questions` `status=published` thuộc các lesson trong phạm vi (`chapter_id` nếu có, ngược lại toàn bộ `path`).
    2. Random chọn tối đa `config('academy.quiz_questions_per_attempt', 10)` câu (nếu tổng câu có sẵn ít hơn, lấy hết).
    3. Trong `DB::transaction`: tạo `AcademyQuizAttempt` (`status=in_progress`, `started_at=now()`) + N dòng `AcademyQuizAttemptAnswer` (`selected_option_id=null`, `sequence` = thứ tự đã random).
    4. Nếu phạm vi không có câu hỏi nào `published` → không tạo attempt, trả lỗi nghiệp vụ rõ ràng ("Chương này chưa có câu hỏi luyện tập").
  - `AnswerQuizAttemptQuestionAction(attempt, question, selectedOptionId)`:
    - Guard: `attempt->status === in_progress` và `attempt->user_id === auth id`.
    - Tra `is_correct` thật từ `academy_quiz_options` theo `selectedOptionId` (không nhận `is_correct` từ request).
    - Update đúng 1 dòng `academy_quiz_attempt_answers` (theo `unique(attempt_id, question_id)`), set `answered_at=now()`.
  - `SubmitQuizAttemptAction(attempt)`:
    - Guard trạng thái + quyền sở hữu như trên.
    - Đếm `correct_count`, tính `score_percent = round(correct_count / total_questions * 100)`.
    - Set `status=submitted`, `submitted_at=now()`.
    - Bắn event `QuizAttemptSubmitted`.
- **Queries**:
  - `GetAttemptProgressQuery/Handler` — câu h�ện tại (câu đầu tiên có `selected_option_id IS NULL` theo `sequence`), số câu đã trả lời/tổng (map "Câu 3/7") — **không** trả `is_correct` của các option.
  - `GetAttemptResultQuery/Handler` — sau khi `submitted`: điểm số, danh sách câu kèm đáp án đã chọn/đáp án đúng/giải thích (để review).
- **Http**: `QuizAttemptController` — `store` (start), `show` (câu hiện tại), `update` (answer 1 câu), `submit`, `result`.

---

## 11. Routes (`Modules/Academy/routes/web.php`)

```php
Route::middleware(['auth'])->prefix('dashboard/academy')->name('backend.academy.')->group(function () {

    // ── Quản trị nội dung (academy.manage) ─────────────────────
    Route::resource('paths', PathAdminController::class);
    Route::resource('paths.chapters', ChapterAdminController::class)->shallow();
    Route::resource('chapters.lessons', LessonAdminController::class)->shallow();
    Route::resource('lessons.quiz-questions', QuizQuestionAdminController::class)->shallow();
    Route::post('chapters/reorder', [ChapterAdminController::class, 'reorder'])->name('chapters.reorder');
    Route::post('lessons/reorder', [LessonAdminController::class, 'reorder'])->name('lessons.reorder');

    // ── Học viên (academy.view) ─────────────────────────────────
    Route::get('/', [LearnerPathController::class, 'index'])->name('paths.index');
    Route::get('paths/{path:uuid}', [LearnerPathController::class, 'show'])->name('paths.show');
    Route::get('lessons/{lesson:uuid}', [LearnerLessonController::class, 'show'])->name('lessons.show');
    Route::post('lessons/{lesson:uuid}/mastered', [LearnerLessonController::class, 'markMastered'])->name('lessons.mastered');
    Route::delete('lessons/{lesson:uuid}/mastered', [LearnerLessonController::class, 'unmarkMastered'])->name('lessons.unmastered');

    // ── Luyện tập (academy.practice) ────────────────────────────
    Route::post('paths/{path:uuid}/practice', [QuizAttemptController::class, 'store'])->name('practice.start');
    Route::get('attempts/{attempt:uuid}', [QuizAttemptController::class, 'show'])->name('attempts.show');
    Route::post('attempts/{attempt:uuid}/answer', [QuizAttemptController::class, 'answer'])->name('attempts.answer');
    Route::post('attempts/{attempt:uuid}/submit', [QuizAttemptController::class, 'submit'])->name('attempts.submit');
    Route::get('attempts/{attempt:uuid}/result', [QuizAttemptController::class, 'result'])->name('attempts.result');
});
```
Route path/tên tuân `docs/PLATFORM_DESIGN.md` §12.2 (`backend.{noun}.{action}`, path `/dashboard/{resource}`). Route-model-binding theo `uuid` (không lộ `id` số nguyên tuần tự ra URL).

---

## 12. Seed Data

`Modules/Academy/database/seeders/AcademyDemoContentSeeder.php` — chỉ chạy nếu `academy_paths` rỗng trong org đang seed:
- 1 `AcademyPath` mẫu (status=published)
- 2 `AcademyChapter`
- 4–6 `AcademyLesson` (nội dung song ngữ, có `definition`/`purpose`/`steps_examples` mẫu — có thể lấy cảm hứng từ nội dung trong ảnh 4 "Tăng trưởng qua Marketing kỹ thuật số")
- Mỗi lesson 1 `AcademyQuizQuestion` với 4 `AcademyQuizOption` (đúng 1 `is_correct`)

Mục đích: có dữ liệu thật để test end-to-end qua UI (browse → đọc bài → đánh dấu Đã nắm → luyện tập → nộp bài → xem điểm) mà không cần nhập tay.

---

## 13. Ánh xạ UI ↔ ảnh mẫu

| Ảnh mẫu | Route | Ghi chú |
|---|---|---|
| Ảnh 3 (Học / Mốc & thi / Luyện tập, danh sách chương-bài) | `backend.academy.paths.show` | Chỉ render tab **Học** và **Luyện tập**; **không** render tab "Mốc & thi" (ngoài phạm vi §3.2) |
| Ảnh 4 (trang chi tiết bài học) | `backend.academy.lessons.show` | Nút "Lưu" (bookmark) không có trong data model hiện tại — có thể bỏ qua hoặc thêm bảng `academy_lesson_bookmarks` ở phase sau nếu cần |
| Ảnh 1 (Câu 3/7, progress bar, A–D) | `backend.academy.attempts.show` | Progress bar = `answered_count / total_questions` lấy từ `GetAttemptProgressQuery` |
| Ảnh 2 (hướng dẫn làm quiz) | — | Chỉ là hướng dẫn text tham khảo, không map route |

---

## 14. Key Design Decisions

| Quyết định | Lý do | Đánh đổi |
|---|---|---|
| Xây độc lập, không tái dùng `RoadmapPhase`/`RoadmapMilestone` | Roadmap hiện tại khoá cứng vào assessment; Academy cần lộ trình đặt tên tự do | Trùng lặp khái niệm "Phase/Chapter" 2 nơi trong hệ thống — chấp nhận được vì domain khác nhau rõ ràng, có thể liên kết qua bảng cầu nối ở phase sau |
| 1 đáp án đúng/câu (không multi-select) | Khớp 100% ảnh mẫu (radio A–D, không phải checkbox) | Không hỗ trợ câu hỏi "chọn nhiều đáp án" — nếu cần, thêm `question_type` enum ở phase sau, không phá schema hiện tại |
| Không có bảng ghi danh (`enrollment`) | MVP: mọi user trong org thấy mọi `path` `published` của org | Không giới hạn lộ trình theo phòng ban/nhóm — bổ sung `academy_path_visibility` sau nếu có yêu cầu |
| `academy_lesson_progress`/`academy_quiz_attempt_answers` không log activity chi tiết | Tần suất update cao (mỗi lần đọc bài/trả lời câu), giá trị audit thấp, tránh phình bảng `activity_log` | Không truy vết được lịch sử "ai đổi trạng thái Đã nắm lúc nào" ngoài `mastered_at`/`updated_at` hiện có |
| Random chọn câu hỏi mỗi lượt luyện tập, giới hạn `quiz_questions_per_attempt` (config, default 10) | Khớp trải nghiệm "Câu 1/7" (số lượng biến thiên theo lộ trình có bao nhiêu câu), tránh 1 lượt quá dài nếu ngân hàng câu hỏi lớn | Không đảm bảo học viên gặp đủ 100% câu hỏi trong 1 lượt — làm lại nhiều lượt để phủ hết (chấp nhận được cho mục đích luyện tập, khác thi cử) |
| Không giới hạn số lần luyện tập lại | Đây là "Luyện tập" (practice), không phải "Thi" (exam) — bản chất cho phép làm lại tuỳ ý | — |

---

## 15. Open Questions (cần xác nhận trước khi triển khai Phase 3 trở đi)

1. Câu hỏi trắc nghiệm có cần gắn **độ khó** (difficulty) để tăng dần theo tiến độ học không, hay tất cả câu hỏi ngang hàng?
2. Điểm luyện tập (`score_percent`) có cần hiển thị trên 1 dashboard tổng hợp (vd cho quản lý xem nhân viên nào luyện tập nhiều/ít) không — nếu có, cần thêm 1 Query báo cáo ở slice riêng, không sửa `QuizPractice`.
3. `config('academy.quiz_questions_per_attempt')` nên là cấu hình toàn hệ thống hay cho phép từng `AcademyPath` override? (schema hiện tại chưa có cột này trên `academy_paths` — dễ bổ sung sau nếu cần).
4. Tab "Mốc & thi" khi có đặc tả cụ thể — sẽ là module con của `Academy` (`Features/MilestoneExam/`) hay 1 module riêng liên kết qua `path_id`? Khuyến nghị: slice mới trong `Academy` nếu vẫn dùng chung cây Path/Chapter/Lesson, module riêng nếu có luồng phê duyệt/chứng chỉ phức tạp.
5. Nút "Lưu" (bookmark bài học, thấy ở ảnh 4) có nằm trong phạm vi đợt này không, hay để lại cho phase sau?

---

## 16. Phased Implementation Plan

| Phase | Nội dung | Output kiểm tra được |
|---|---|---|
| **Phase 0 — Scaffold module** | `module.json`, `composer.json`, `AcademyServiceProvider`/`RouteServiceProvider`/`EventServiceProvider`, `config/config.php`, thêm `"Academy": true` vào `modules_statuses.json` | `php artisan module:list` thấy `Academy` enabled |
| **Phase 1 — Data model** | 10 migration (§7), 9 model (`Models/`), 3 enum (§8) | `php artisan migrate` chạy sạch; `php artisan tinker` tạo thử 1 `AcademyPath`→`Chapter`→`Lesson` qua relationship không lỗi |
| **Phase 2 — Permissions** | `PermissionEnum` +3 case, `config/permissions.php` +3 dòng/role, `AcademyPermissionSeeder`, `AcademyDatabaseSeeder`, đăng ký vào `SystemDataSeeder` | `php artisan db:seed` không lỗi; kiểm tra bảng `permissions`/`role_has_permissions` có `academy.*` |
| **Phase 3 — Slice `ContentAuthoring`** | Actions/Data/Queries/Http/Policy + Blade admin views (index/create/edit cho Path/Chapter/Lesson/QuizQuestion) | Đăng nhập `system_admin`, tạo được 1 lộ trình đầy đủ Chương/Bài/Câu hỏi qua UI |
| **Phase 4 — Slice `LearnerJourney`** | Queries/Actions/Http + Blade learner views (path index/show, lesson show) | Đăng nhập user thường, thấy lộ trình `published`, đọc bài, bấm "Đã nắm"/"Hoàn tác" cập nhật đúng `academy_lesson_progress` |
| **Phase 5 — Slice `QuizPractice`** | Actions/Queries/Http + Blade quiz views (question show, result) | Bấm "Luyện tập", trả lời hết N câu (cố tình chọn cả đúng lẫn sai để test), nộp bài, thấy điểm đúng khớp DB |
| **Phase 6 — Seed & Sidebar** | `AcademyDemoContentSeeder`, sidebar entry (`academy.view`/`academy.manage` gate) | Sidebar hiện mục Academy đúng theo role; org mới seed có sẵn 1 lộ trình demo dùng được ngay |
| **Phase 7 — Kiểm thử & nghiệm thu** | Xem §17 | Tất cả kịch bản chấp nhận pass |

---

## 17. Testing & Acceptance Criteria

**`ContentAuthoring`**
- Given `system_admin` tạo `AcademyQuizQuestion` với 4 option nhưng 0 hoặc ≥2 option `is_correct=true` → When submit → Then bị chặn validate, không ghi DB.
- Given user role `viewer` (không có `academy.manage`) gọi route admin CRUD → Then 403.

**`LearnerJourney`**
- Given user chưa từng mở 1 lesson → When gọi `GetLessonDetailQuery` → Then `progress_status = not_started`.
- Given user bấm "Đã nắm" → Then `academy_lesson_progress.status = mastered`, `mastered_at` được set; bấm "Hoàn tác" → `status = in_progress`, `mastered_at = null`.

**`QuizPractice`**
- Given 1 chapter có 3 câu hỏi `published` và `quiz_questions_per_attempt = 10` → When `StartQuizAttemptAction` → Then `total_questions = 3` (không tạo câu ảo).
- Given 1 chapter chưa có câu hỏi `published` nào → When bắt đầu luyện tập → Then trả lỗi nghiệp vụ rõ ràng, không tạo attempt rỗng.
- Given attempt đã `submitted` → When gọi lại `AnswerQuizAttemptQuestionAction` trên attempt đó → Then bị chặn (400/403), không sửa được dữ liệu lịch sử.
- Given user A → When cố `GET` attempt của user B qua route `attempts.show` → Then 403 (theo `AcademyQuizAttemptPolicy::view`).
- Given trả lời đúng 2/3 câu rồi `submit` → Then `correct_count=2`, `score_percent=67`.
- Given response trả về câu hỏi đang làm (`GetAttemptProgressQuery`) → Then payload **không** chứa field `is_correct` của bất kỳ option nào chưa được trả lời.
