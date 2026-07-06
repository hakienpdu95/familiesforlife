**Đường dẫn dự án:** `/var/www/html/minhan`
**Module**: `Modules/Survey`

## NHÓM 4 — Performance & Large Dataset (500k rows)

**Mục tiêu chính**: Đảm bảo module Survey có thể chịu tải thực tế **≥ 500.000 rows** (`survey_responses` + `survey_answers`) mà vẫn mượt mà, response time thấp, không lag, không trễ.

**Đọc trước toàn bộ**:
- `Actions/BuildSurveySchemaAction.php`
- `Services/SurveyStatsService.php`
- `Services/ResponseViewerService.php`
- `Actions/ExportSurveyResponsesAction.php`
- `Http/Controllers/ResponseController.php`
- `Http/Controllers/SurveyController.php`

Claude phải đọc file thực tế trước khi đánh giá bất kỳ task nào.

### Task 4.1 — List Table Response (Response Index / Danh sách bảng)
- Phải implement **Cursor Pagination** (không dùng `page` thông thường) cho danh sách response.
- Query phải select tối thiểu cột cần thiết, tránh `select *`.
- Hỗ trợ filter theo `status`, `completed_at`, `respondent_ref`.
- Eager load `answers` chỉ khi cần xem chi tiết (không eager load mặc định trong list).
- Thời gian load danh sách **10.000 rows** phải < 800ms (sau cache).

### Task 4.2 — Optimize Survey Stats cho 500k rows
`SurveyStatsService`:
- Tất cả query count/group by/avg phải sử dụng index hiệu quả.
- Thêm caching Redis cho toàn bộ stats:
  - Key: `survey:stats:{survey_id}`
  - TTL: 300 giây (5 phút)
- Purge cache tự động khi có submit response mới (dùng Event + Listener hoặc Queue).
- Chạy `EXPLAIN` trên 3 query chính (count option, avg number/rating, count boolean) và báo cáo.

### Task 4.3 — Export large dataset
`ExportSurveyResponsesAction`:
- Phải dùng **Queue job** + **chunking** (chunk size 2000–5000 rows).
- Sử dụng `LazyCollection` hoặc `DB::cursor()` để không load hết data vào memory.
- Multi-choice field (`checkbox`) phải gộp thành một ô Excel (dùng `implode(', ', $values)`).
- Không được để export chạy đồng bộ (sync) khi > 10.000 rows.

### Task 4.4 — Cache Survey Schema
`BuildSurveySchemaAction`:
- Phải cache kết quả schema vào Redis:
  - Key: `survey:schema:{slug}`
  - TTL: 1800 giây (30 phút)
- Purge cache tự động khi:
  - Update/Create/Delete field
  - Update/Create/Delete option
  - Update section order
  - Activate/Deactivate survey

### Task 4.5 — Query Log & N+1 Audit
- Bật `DB::enableQueryLog()` trên các action quan trọng (BuildSchema, Response list, Stats, Export).
- Báo cáo số query thực tế và kiểm tra có N+1 không.
- Đề xuất index cần thiết (nếu query chậm) mà không được sửa migration.

**→ DỪNG sau khi hoàn thành Nhóm 4.**