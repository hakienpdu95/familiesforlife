Để kiến trúc một hệ thống phần mềm quản lý nội bộ bằng framework Laravel đáp ứng tuyệt đối các hành lang pháp lý phức tạp của ngành hàng mẹ và bé, hệ thống cần được thiết kế với tư duy "lưu vết toàn diện" (Full Traceability).

Dưới đây là roadmap chi tiết các module và phân hệ kỹ thuật, được "đo ni đóng giày" để tuân thủ 5 văn bản pháp luật cốt lõi mà bạn đã tổng hợp:

### Module 1: Quản trị Dữ liệu lõi & Hồ sơ Pháp lý (Master Data & Compliance)

Module này là nền tảng database để phân loại và lưu trữ minh chứng pháp lý, đảm bảo hàng hóa đủ điều kiện lưu thông.

* **Phân hệ Quản lý Nhà cung cấp (Vendor Management):**
* Lưu trữ thông tin định danh: Tên, địa chỉ, mã số thuế, đầu mối liên hệ.
* Lưu trữ minh chứng: File scan Giấy chứng nhận cơ sở đủ điều kiện an toàn thực phẩm.
* *Mục đích:* Thực thi nguyên tắc truy xuất "một bước trước" theo Thông tư 25/2019/TT-BYT.


* **Phân hệ Quản lý Danh mục (Product/SKU Master):** Thiết kế database linh hoạt để phân nhánh theo từng nhóm ngành hàng:
* *Nhóm Thực phẩm (Nghị định 15/2018/NĐ-CP):* Các trường upload Bản tự công bố hoặc Giấy tiếp nhận đăng ký bản công bố sản phẩm.


* *Nhóm Mỹ phẩm (Thông tư 06/2011/TT-BYT):* Các trường lưu Số tiếp nhận Phiếu công bố sản phẩm mỹ phẩm (có giá trị 5 năm), và khu vực đính kèm Hồ sơ thông tin sản phẩm (PIF).


* *Nhóm Thiết bị y tế (NĐ 98 & 07):* Dropdown phân loại trang thiết bị (A, B, C, D) và trường text lưu Số lưu hành y tế.


* **Phân hệ Cảnh báo Pháp lý (Compliance Cronjobs):**
* Lập trình các task chạy ngầm (Schedule Command trong Laravel) để kiểm tra hạn sử dụng của các giấy tờ công bố. Tự động gửi cảnh báo (qua hệ thống notification nội bộ hoặc email) trước 30-60 ngày đối với các Phiếu công bố mỹ phẩm hoặc Giấy chứng nhận ATTP sắp hết hạn.





### Module 2: Quản trị Kho & Định dạng Mã Truy vết (Smart Inventory & Trace Coding)

Đây là "trái tim" của hệ thống truy xuất, quản lý dòng chảy vật lý của hàng hóa gắn với luồng thông tin.

* **Phân hệ Khởi tạo Mã Truy vết (Traceability Code Generator):**
* *Nghiệp vụ:* Tuân thủ Thông tư 02/2024/TT-BKHCN và TCVN 13274:2020.
* *Kỹ thuật:* Lập trình logic sinh mã truy vết vật phẩm gồm 14 chữ số, bao gồm số định danh ứng dụng (AI 01), tiền tố mã doanh nghiệp, số tham chiếu vật phẩm và thuật toán tự động tính chữ số kiểm tra ở vị trí thứ 14. Hệ thống tự động sinh mã QR tương ứng để dán lên bao bì (đối với hàng chia nhỏ hoặc tự đóng gói).




* **Phân hệ Quản lý Lô & Hạn sử dụng (Batch & Expiry Management):**
* Khi làm phiếu nhập kho (Inbound), hệ thống bắt buộc (required validation) phải nhập `batch_number` (Số lô) và `expiry_date` (Hạn dùng) bên cạnh `sku_id` và số lượng.
* Tồn kho không được gộp chung theo SKU mà phải tách biệt theo từng dòng lô (Batch lines) để tính toán giá trị tồn và hạn sử dụng chính xác.


* **Phân hệ Điều phối Xuất kho (Outbound & FEFO):**
* Áp dụng thuật toán FEFO (First Expired, First Out) để hệ thống tự động đề xuất xuất bán hoặc điều chuyển các lô hàng có hạn sử dụng gần nhất.



### Module 3: Bán lẻ & Lưu vết Khách hàng (POS & CRM)

Module này thực thi nguyên tắc "một bước sau" trong chuỗi truy xuất.

* **Phân hệ Bán lẻ (Point of Sale):**
* Giao diện thu ngân hỗ trợ kết nối máy quét mã vạch.
* *Logic quan trọng:* Khi quét mã bán hàng, hệ thống phải trừ lùi tồn kho đúng vào "Số lô" của sản phẩm đang được giao dịch. Nếu cùng 1 SKU nhưng có 2 lô khác nhau trên kệ, thu ngân có thể chọn đúng số lô thực tế khách lấy.


* **Phân hệ Hồ sơ Khách hàng (Customer Profile):**
* Lưu trữ thông tin khách hàng (SĐT, Tên) gắn liền với lịch sử mua sắm chi tiết đến từng số lô sản phẩm. Điều này là bắt buộc để khoanh vùng chính xác người tiêu dùng khi có sự cố.



### Module 4: Truy vết ngược & Quản trị Sự cố (Traceback & Recall)

Module thể hiện sức mạnh của toàn bộ hệ thống lưu trữ bên trên khi có yêu cầu từ cơ quan quản lý.

* **Phân hệ Truy vết 2 chiều (Two-way Traceability):**
* *Truy vết xuôi (Từ nhà sản xuất -> Khách hàng):* Khi Bộ Y tế hoặc nhà cung cấp yêu cầu thu hồi Lô X của Sản phẩm Y, hệ thống xuất báo cáo: Đã nhập bao nhiêu, đang tồn bao nhiêu trên kệ, đã bán cho những khách hàng nào (kèm SĐT để liên hệ thu hồi).


* *Truy vết ngược (Từ Khách hàng -> Nguồn gốc):* Khách hàng mang sản phẩm lỗi đến, hệ thống nhập mã vạch/mã lô và truy xuất ra ngay: Nhập ngày nào, thuộc chứng từ nhập kho nào, từ nhà cung cấp nào, minh chứng an toàn thực phẩm/phiếu công bố của lô đó là gì.


* **Phân hệ Ghi nhận Tác dụng phụ (Adverse Event Report):**
* Đáp ứng quy định của Thông tư 06/2011/TT-BYT về mỹ phẩm. Nơi nhân viên ghi nhận các khiếu nại về tác dụng phụ (dị ứng, mẩn ngứa do kem/sữa tắm).
* Cung cấp template tự động trích xuất dữ liệu thành báo cáo để gửi Cục Quản lý dược trong vòng 07 ngày đối với các tác dụng phụ trầm trọng.


Với các bảng dữ liệu (Database Schema) trong Laravel, bạn định sử dụng kiến trúc thiết kế nào để liên kết các bảng Sản Phẩm (Products), Lô Hàng (Batches), và Lịch sử Giao dịch (Transactions) nhằm tối ưu tốc độ truy vấn khi dữ liệu phình to?

---------------------------------------------------------------------------

Chào bạn, việc xây dựng phân hệ Quản trị Phân quyền (RBAC) vững chắc trên nền tảng Laravel là yếu tố sống còn để bảo vệ tính toàn vẹn của dữ liệu truy xuất nguồn gốc. Đặc biệt với các mặt hàng nhạy cảm của mẹ và bé, hệ thống phải trả lời được câu hỏi: "Ai đã duyệt hồ sơ này?" hoặc "Ai đã xuất lô hàng kia?".

Dưới đây là đặc tả kỹ thuật và thiết kế Database bám sát core hệ thống:

### Cấu trúc Cơ sở dữ liệu (Database Schema)

Hệ thống sẽ sử dụng mô hình RBAC tiêu chuẩn kết hợp với bảng lưu vết (Audit Log) để đảm bảo tính minh bạch tuyệt đối:

* Bảng `users`: Quản lý thông tin tài khoản nội bộ (`id`, `name`, `email`, `password`, `is_active`).
* Bảng `roles` và `permissions`: Định nghĩa vai trò và các đặc quyền cụ thể (Ví dụ: `approve_compliance`, `initiate_recall`, `scan_pos`).
* Bảng `model_has_roles` và `role_has_permissions`: Các bảng trung gian (Pivot tables) gắn kết người dùng với quyền hạn.
* Bảng `audit_logs`: Lưu vết mọi thao tác thay đổi dữ liệu quan trọng (`user_id`, `action`, `model_type`, `model_id`, `old_values`, `new_values`).

### Ma trận Phân quyền cốt lõi (Role Matrix)

* **Quản trị viên (Admin):** Nắm toàn quyền hệ thống, phê duyệt các minh chứng pháp lý, và ra quyết định kích hoạt chiến dịch thu hồi mỹ phẩm lỗi.


* **Quản lý Kho (Warehouse):** Quyền thao tác tạo lô hàng (Batches), khởi tạo Mã truy vết vật phẩm theo TCVN 13274:2020 và xử lý luồng xuất nhập.


* **Nhân viên Thu ngân (POS):** Giới hạn quyền ở mức tạo hóa đơn, quét mã QR trừ tồn kho và ghi nhận tác dụng phụ từ khách hàng.


* **Kinh doanh B2B (B2B Sales):** Quyền xem tồn kho, tạo đơn xuất buôn để thực thi lưu vết "một bước sau" tới các đại lý.



### Logic Lưu vết Kiểm toán (Audit Trail Logic)

* Sử dụng cơ chế Eloquent Observers trong Laravel để tự động bắt các sự kiện `created`, `updated`, `deleted` trên các bảng nhạy cảm như `batches` hay `product_compliances`.
* Nếu một nhân viên kho cố tình sửa đổi hạn sử dụng (`exp_date`) của một lô hàng đã nhập, hệ thống sẽ lưu lại giá trị cũ và mới vào `audit_logs` cùng thời gian thực hiện.

### Kiểm soát Truy cập (Access Control)

* Sử dụng Route Middleware để bảo vệ các API endpoint. Ví dụ: Kênh API cập nhật Hồ sơ thông tin sản phẩm (PIF) chỉ cho phép tài khoản có quyền `approve_compliance` truy cập.


* Giao diện frontend tự động ẩn/hiện các nút bấm (như nút "Đình chỉ lưu hành") dựa trên danh sách quyền trả về từ backend.



Bạn dự định sử dụng package mã nguồn mở nào của Laravel (như Spatie Permission) để tăng tốc độ phát triển phân hệ này, hay muốn tự viết toàn bộ logic từ đầu?

---------------------------------------------------------------------------

Để thiết kế cơ sở dữ liệu (Database Schema) cho Phân hệ Quản lý Nhà cung cấp (Vendor Management) đáp ứng nghiêm ngặt các quy định pháp lý về truy xuất nguồn gốc, hệ thống cần bám sát nguyên tắc "một bước trước" – tức là phải nhận diện được chính xác tổ chức/cá nhân phân phối hàng hóa và tình trạng pháp lý của họ tại thời điểm nhập hàng.

Với nền tảng framework Laravel, thay vì dồn tất cả vào một bảng, bạn nên tách thành **02 bảng riêng biệt** (quan hệ One-to-Many). Thiết kế này giúp bạn lưu vết được lịch sử thay đổi giấy tờ theo thời gian mà không làm mất dữ liệu cũ.

### 1. Bảng `vendors` (Thông tin định danh cốt lõi)

Bảng này lưu trữ các thông tin định danh cố định của nhà cung cấp, phục vụ cho việc đối chiếu thông tin tổ chức, cá nhân chịu trách nhiệm đưa sản phẩm ra thị trường.

| Tên trường (Column) | Kiểu dữ liệu (Type) | Mục đích & Căn cứ pháp lý |
| --- | --- | --- |
| `id` | BigIncrements | Khóa chính (Primary Key). |
| `vendor_code` | String(50), Unique | Mã quản lý nội bộ hoặc lưu "Tiền tố mã doanh nghiệp" để đồng bộ với định dạng mã truy vết vận chuyển/địa điểm.

 |
| `name` | String(255) | Tên tổ chức, cá nhân (Ghi đầy đủ theo Giấy chứng nhận đăng ký kinh doanh).

 |
| `tax_code` | String(50), Unique | Mã số doanh nghiệp/Mã số thuế. Căn cứ đối chiếu duy nhất.

 |
| `address` | String(500) | Địa chỉ trụ sở chính.

 |
| `phone_number` | String(20) | Điện thoại liên hệ.

 |
| `email` | String(100) | Email để hệ thống tự động bắn link Signed URL yêu cầu upload giấy tờ.

 |
| `representative_name` | String(100) | Họ và tên người đại diện theo pháp luật.

 |
| `status` | Boolean / Enum | Trạng thái giao dịch (Đang hợp tác / Ngừng hợp tác). |
| `deleted_at` | Timestamp | **Bắt buộc dùng SoftDeletes.** Không bao giờ xóa cứng (hard delete) nhà cung cấp để giữ nguyên vẹn lịch sử truy xuất nguồn gốc.

 |

### 2. Bảng `vendor_certificates` (Quản lý hồ sơ minh chứng theo thời gian)

Đây là bảng cực kỳ quan trọng để quản lý tính hợp pháp của nhà cung cấp theo trục thời gian. Một nhà cung cấp có thể có nhiều loại giấy phép, và mỗi loại sẽ hết hạn và được cấp mới nhiều lần.

| Tên trường (Column) | Kiểu dữ liệu (Type) | Mục đích & Căn cứ pháp lý |
| --- | --- | --- |
| `id` | BigIncrements | Khóa chính. |
| `vendor_id` | UnsignedBigInt | Khóa ngoại (Foreign Key) liên kết với bảng `vendors`. |
| `certificate_type` | Enum / String | Phân loại: Đăng ký kinh doanh, Giấy chứng nhận cơ sở đủ điều kiện ATTP, Thực hành sản xuất tốt (GMP)....

 |
| `certificate_number` | String(100) | Số giấy chứng nhận / Số giấy phép.

 |
| `issue_date` | Date | Ngày cấp.

 |
| `expiry_date` | Date, Nullable | Ngày hết hạn. Cơ sở dữ liệu cốt lõi để lập lịch cảnh báo. |
| `issued_by` | String(255) | Nơi cấp (VD: Sở Kế hoạch Đầu tư, Cục An toàn thực phẩm).

 |
| `file_url` | String(500) | Đường dẫn file PDF/Scan bản sao chứng thực. (File do NCC upload qua link Guest).

 |
| `is_active` | Boolean | Cờ (flag) xác định giấy tờ này có đang là bản mới nhất và còn hiệu lực hay không. |

| `renewal_deadline` | Date | Cột Date tính trước 6 tháng để kích hoạt Cronjob nhắc nhở gia hạn GMP |

### 3. Cách thức quản lý dữ liệu theo thời gian (Temporal Management)

Để hệ thống vận hành tự động và tuân thủ pháp luật, dữ liệu trong hai bảng trên sẽ được khai thác thông qua các luồng logic sau:

* **Không ghi đè dữ liệu minh chứng cũ:** Khi Giấy chứng nhận ATTP của nhà cung cấp hết hạn và họ gửi bản mới, bạn **không được** update (sửa) trực tiếp vào bản ghi cũ trong bảng `vendor_certificates`. Thay vào đó, bạn tạo một bản ghi (insert) mới với `expiry_date` mới, đồng thời cập nhật `is_active = false` cho bản ghi cũ. Điều này đảm bảo nếu thanh tra kiểm tra lại một lô hàng nhập từ 2 năm trước, hệ thống vẫn trích xuất đúng tờ giấy phép có hiệu lực tại đúng thời điểm nhập lô hàng đó.


* **Chặn luồng nhập kho (Inbound Block):** Bằng cách sử dụng Middleware hoặc FormRequest, khi tạo Phiếu nhập kho (PO), hệ thống sẽ query kiểm tra bảng `vendor_certificates`. Nếu `expiry_date` của Giấy chứng nhận ATTP đã qua thời hạn hiện tại, hệ thống lập tức khóa thao tác nhập hàng và báo lỗi: *"Nhà cung cấp đã hết hạn chứng nhận ATTP, không thể nhập lô mới"*.
* **Cronjob Cảnh báo tự động:** Thiết lập Laravel Scheduler chạy lệnh `php artisan schedule:run` mỗi ngày một lần lúc nửa đêm. Lệnh này sẽ quét cột `expiry_date`. Nếu phát hiện giấy tờ nào còn <= 30 ngày là hết hạn, hệ thống tự động sinh ra một Signed URL và gửi email/Zalo cho nhà cung cấp yêu cầu: *"Giấy phép của quý đối tác sắp hết hạn, vui lòng truy cập đường dẫn sau để upload bản gia hạn"*.

Với thiết kế 2 bảng có quan hệ One-to-Many này, bạn có dự định viết các hàm Observer trong model `VendorCertificate` để tự động xử lý logic chuyển trạng thái `is_active` khi có giấy phép mới được thêm vào không?

---------------------------------------------------------------------------

Dưới đây là thiết kế chi tiết các bảng (Tables) cốt lõi, ý nghĩa các trường dữ liệu (Columns) và cách thức quản lý theo thời gian, bám sát Nghị định 15/2018/NĐ-CP và Thông tư 06/2011/TT-BYT.

### 2. Bảng `products` (Danh mục Sản phẩm - SKU Master)

Bảng này lưu trữ các thông tin chung nhất về mặt vật lý của một sản phẩm, không chứa thông tin pháp lý (vì hồ sơ pháp lý có thể được cập nhật/gia hạn theo thời gian).

* `id`: Primary Key.
* `sku`: Mã nội bộ của sản phẩm.
* `barcode`: Mã vạch in trên bao bì.
* `name`: Tên sản phẩm.
* `category_type`: Cột rẽ nhánh (Enum: `food`, `cosmetic`, `medical`, `others`). Dựa vào trường này, giao diện Laravel (Blade/Vue/React) sẽ hiển thị form yêu cầu upload các loại giấy tờ khác nhau.
* `brand`: Thương hiệu.
* `unit`: Đơn vị tính (Hộp, Chai, Tuýp).

### 3. Bảng `product_compliances` (Quản lý Hồ sơ Pháp lý theo thời gian)

Để người dùng (hoặc nhà cung cấp) thao tác nhanh chóng và không phải nhập thủ công tên các loại giấy tờ phức tạp, hệ thống bắt buộc phải **định nghĩa sẵn (seed sẵn) danh mục các loại giấy tờ pháp lý** dựa trên các văn bản quy phạm pháp luật đã quy định.

Người dùng chỉ cần thao tác rất đơn giản: **Chọn loại giấy tờ từ danh mục thả xuống (Dropdown) -> Nhập số hiệu, ngày cấp, ngày hết hạn -> Kéo thả file PDF/Scan để tải lên**.

Dưới đây là danh mục các loại giấy tờ pháp lý chuẩn hóa được định nghĩa sẵn trong hệ thống:

### Danh mục Giấy tờ Pháp lý Định nghĩa sẵn (Pre-defined Document Types)

#### 1. Nhóm Giấy tờ chung cho Doanh nghiệp / Nhà cung cấp (Vendor Level)

* **Giấy chứng nhận đăng ký kinh doanh** (Hoặc Giấy phép đầu tư).
* **Giấy chứng nhận cơ sở đủ điều kiện an toàn thực phẩm** (Quy định tại Nghị định 15/2018/NĐ-CP cho các cơ sở sản xuất, kinh doanh thực phẩm).


* **Giấy chứng nhận Thực hành sản xuất tốt (GMP)** (Áp dụng cho cơ sở sản xuất thực phẩm bảo vệ sức khỏe hoặc mỹ phẩm đạt chuẩn CGMP-ASEAN).



#### 2. Nhóm Giấy tờ pháp lý cho Thực phẩm (Nghị định 15/2018/NĐ-CP)

* **Bản tự công bố sản phẩm** (Theo Mẫu số 01 Phụ lục I - Áp dụng cho thực phẩm đã qua chế biến bao gói sẵn, phụ gia, dụng cụ chứa đựng thực phẩm).


* **Giấy tiếp nhận đăng ký bản công bố sản phẩm** (Theo Mẫu số 03 Phụ lục I - Áp dụng bắt buộc cho thực phẩm bảo vệ sức khỏe, thực phẩm dinh dưỡng y học, sản phẩm dinh dưỡng cho trẻ đến 36 tháng tuổi).


* **Phiếu kết quả kiểm nghiệm an toàn thực phẩm** (Cấp bởi phòng kiểm nghiệm được chỉ định hoặc đạt chuẩn ISO 17025, có thời hạn trong vòng 12 tháng).


* **Bằng chứng khoa học chứng minh công dụng** (Bắt buộc đối với thực phẩm bảo vệ sức khỏe).



#### 3. Nhóm Giấy tờ pháp lý cho Mỹ phẩm (Thông tư 06/2011/TT-BYT)

* **Số tiếp nhận Phiếu công bố sản phẩm mỹ phẩm** (Cấp bởi Cục Quản lý dược hoặc Sở Y tế, có thời hạn hiệu lực 05 năm).


* **Hồ sơ thông tin sản phẩm (PIF - Product Information File)** (Gồm 4 phần: Tài liệu hành chính, chất lượng nguyên liệu, chất lượng thành phẩm, an toàn & hiệu quả).


* **Giấy ủy quyền (Letter of Authorization)** (Từ nhà sản xuất hoặc chủ sở hữu sản phẩm cho đơn vị phân phối tại Việt Nam, đã được hợp pháp hóa lãnh sự).


* **Giấy chứng nhận lưu hành tự do (CFS - Certificate of Free Sale)** (Áp dụng cho mỹ phẩm nhập khẩu, được hợp pháp hóa lãnh sự).



#### 4. Nhóm Giấy tờ pháp lý cho Trang thiết bị Y tế (Nghị định 98/2021/NĐ-CP & 07/2023/NĐ-CP)

* **Bản phân loại trang thiết bị y tế** (Phân loại A, B, C, D).
* **Số lưu hành trang thiết bị y tế** (Giấy chứng nhận đăng ký lưu hành hoặc Giấy phép nhập khẩu).

---

### Cách thức Quản lý trong Cơ sở dữ liệu và Giao diện (UI/UX)

1. **Seed sẵn trong Database:**
Khi khởi tạo hệ thống, một bảng `document_master_types` sẽ được tạo sẵn các dòng dữ liệu mã hóa (VD: `code: cosmetic_notification`, `name: Phiếu công bố sản phẩm mỹ phẩm`, `applicable_category: cosmetic`, `is_required_expiry: true`, `default_validity_years: 5`).
2. **Giao diện Động (Dynamic Form):**
* Khi người dùng chọn một sản phẩm thuộc nhóm *Mỹ phẩm*, giao diện sẽ tự động lọc ra các loại giấy tờ thuộc nhóm Mỹ phẩm (Phiếu công bố, PIF, Giấy ủy quyền, CFS).


* Nếu chọn loại giấy tờ có thời hạn (như Phiếu công bố mỹ phẩm có giá trị 5 năm), hệ thống tự động bật ô chọn "Ngày cấp" và tự động tính toán "Ngày hết hạn" để đưa vào hàng đợi giám sát của Cronjob.




3. **Lưu trữ an toàn:**
File tải lên sẽ được lưu trữ vào hệ thống storage bảo mật của Laravel, gắn chặt với `product_id` hoặc `vendor_id` tương ứng, sẵn sàng phục vụ cho việc kiểm tra xuất trình khi cơ quan chức năng yêu cầu.


Bảng này liên kết 1-N (One-to-Many) với bảng `products`. Việc tách riêng giúp bạn lưu lại *lịch sử* gia hạn giấy tờ (ví dụ: công bố cũ hết hạn, nhà cung cấp gửi bản công bố mới, bạn sẽ tạo một record mới thay vì ghi đè, giúp đảm bảo tính toàn vẹn khi truy xuất dữ liệu trong quá khứ).

* `id`: Primary Key.
* `product_id`: Khóa ngoại trỏ về bảng `products`.
* `document_type`: Loại giấy tờ (Enum: `self_declaration` - Bản tự công bố, `registered_declaration` - Bản đăng ký công bố, `cosmetic_notification` - Phiếu công bố mỹ phẩm).


* `document_number`: Số tiếp nhận / Số công bố (Ví dụ: 135/11/CBMP-HN).


* `issue_date`: Ngày cấp.
* `expiration_date`: Ngày hết hạn.
* *Cách quản lý:* Với mỹ phẩm, hệ thống tự động tính `expiration_date` = `issue_date` + 5 năm (Vì Phiếu công bố mỹ phẩm có giá trị 05 năm kể từ ngày cấp).




* `document_file_path`: Đường dẫn file PDF của Bản công bố/Phiếu công bố.
* `pif_file_path`: Đường dẫn folder/file lưu trữ Hồ sơ thông tin sản phẩm (PIF) - Chỉ bắt buộc (required) nếu `category_type` của product là `cosmetic`.
* `pif_retention_date`: Cột Date để hệ thống chặn lệnh xóa file PIF trước thời hạn 3 năm.

* `status`: Trạng thái (Active, Expiring, Expired).

### 4. Bảng `batches` (Quản lý Lô hàng - Khớp nối Sản phẩm và Nguồn cung)

Đây là bảng giải quyết triệt để bài toán: "Sản phẩm này, thuộc lô này, được nhập từ ai và khi nào?". Bảng này thực thi nguyên tắc truy xuất "một bước trước".

* `id`: Primary Key.
* `product_id`: Khóa ngoại trỏ về `products` (Biết là sản phẩm gì).
* `vendor_id`: Khóa ngoại trỏ về `vendors` (Biết nhập từ Nhà cung cấp nào).
* `batch_number`: Số lô in trên bao bì (Bắt buộc nhập).


* `mfg_date`: Ngày sản xuất.


* `exp_date`: Hạn sử dụng của lô hàng.


* *Cách quản lý:* Trường này là cơ sở để hệ thống chạy thuật toán FEFO (Hết hạn trước - Xuất trước) khi bán hàng tại POS.


* `quantity`: Số lượng tồn kho thực tế của lô này.
* `internal_trace_code`: Mã truy vết nội bộ do hệ thống của bạn tự sinh ra khi nhập kho.
* `coa_file_path`: (Tùy chọn) Đường dẫn file Giấy chứng nhận phân tích (COA) của riêng lô hàng đó do nhà cung cấp gửi kèm.

---

### Logic Quản lý Thời gian & Vận hành (Cronjobs trong Laravel)

Với thiết kế DB như trên, bạn sẽ thiết lập Laravel Task Scheduling (Cronjobs) để quản lý 2 luồng thời gian song song:

1. **Quản lý Hạn sử dụng Pháp lý (Bảng `product_compliances`):**
* Hàng ngày, hệ thống quét trường `expiration_date`. Nếu giấy phép mỹ phẩm chỉ còn 60 ngày là đến mốc 5 năm, hệ thống báo đỏ: *"Sản phẩm [Tên SP] sắp hết hạn công bố, yêu cầu [Nhà cung cấp] gửi bản gia hạn"*.




2. **Quản lý Hạn sử dụng Vật lý (Bảng `batches`):**
* Hệ thống quét trường `exp_date`. Lô hàng nào còn 90 ngày là hết hạn sử dụng cho bé, tự động đẩy vào danh sách "Hàng cận date" để bạn làm chương trình khuyến mãi hoặc trả lại nhà cung cấp.



Với cấu trúc bảng này, trong Laravel (sử dụng Eloquent ORM), bạn dự định sẽ dùng quan hệ `HasMany` thông thường hay `MorphMany` (Đa hình) để gắn các loại giấy tờ pháp lý vào sản phẩm?

---------------------------------------------------------------------------
Xây dựng một phân hệ cảnh báo chạy ngầm (Cronjob) trong Laravel là một chốt chặn an toàn tuyệt vời, giúp cửa hàng mẹ và bé của bạn chủ động kiểm soát rủi ro pháp lý thay vì phản ứng thụ động.

### 1. Mục đích và Nguyên tắc vận hành

* **Lưu vết trạng thái:** Bảng này đóng vai trò như một hộp thư đến (Inbox) cho hệ thống quản trị, giúp bạn biết cảnh báo nào mới phát sinh, cảnh báo nào đang xử lý và cảnh báo nào đã được giải quyết.
* **Quản lý đa đối tượng:** Hệ thống cần thiết kế linh hoạt (Polymorphic) để cảnh báo cho nhiều loại đối tượng khác nhau (Hồ sơ mỹ phẩm, Giấy chứng nhận của nhà cung cấp, hoặc Lô hàng sắp hết date).

### 2. Thiết kế bảng `compliance_warnings`

Bảng này sẽ ghi nhận mọi bất thường do hệ thống Cronjob quét được hàng ngày:

* `id`: Khóa chính.
* `warnable_type` và `warnable_id`: Cặp khóa ngoại đa hình (Polymorphic relations trong Laravel) trỏ đến bảng `product_compliances` hoặc bảng `batches`.
* `alert_type`: Phân loại cảnh báo (Ví dụ: `document_expiring`, `batch_expiring`).
* `warning_level`: Mức độ nghiêm trọng (Vàng - Báo trước 60 ngày, Đỏ - Báo trước 15 ngày hoặc đã quá hạn).
* `message`: Nội dung thông báo tự động (Ví dụ: "Phiếu công bố của Sữa tắm X sẽ hết hạn vào ngày Y").
* `target_date`: Ngày thực tế xảy ra sự kiện hết hạn.
* `status`: Trạng thái xử lý (Enum: `pending` - Chờ xử lý, `acknowledged` - Đã tiếp nhận, `resolved` - Đã giải quyết).
* `resolved_at`: Thời gian người quản trị xác nhận đã nạp chứng từ mới.

### 3. Kịch bản chạy ngầm (Cronjob Logic) theo thời gian

Bạn sẽ cấu hình file `app/Console/Kernel.php` để chạy lệnh quét DB vào lúc 00:00 mỗi ngày. Hệ thống sẽ áp dụng các mốc thời gian pháp lý như sau:

* **Đối với Mỹ phẩm:** Quét bảng `product_compliances`, tìm các Phiếu công bố mỹ phẩm có giá trị 05 năm sắp đến hạn. Nếu `target_date` còn đúng 60 ngày, hệ thống tạo một bản ghi vào bảng `compliance_warnings` với trạng thái `pending`.


* **Đối với Thực phẩm bảo vệ sức khỏe:** Quét kiểm tra Giấy chứng nhận cơ sở đủ điều kiện an toàn thực phẩm đạt yêu cầu Thực hành sản xuất tốt (GMP). Giấy này có giá trị 03 năm kể từ ngày cấp, và doanh nghiệp phải nộp hồ sơ đăng ký cấp lại trước khi hết hạn 06 tháng. Hệ thống sẽ tự động nhắc nhở bạn yêu cầu nhà cung cấp gửi bản gia hạn trước mốc 06 tháng này.


* **Đóng vòng lặp xử lý:** Khi bạn tải lên một file PDF chứng từ mới hoặc gia hạn thành công, Controller của Laravel sẽ tự động tìm các `compliance_warnings` liên quan và chuyển `status` sang `resolved`.

---------------------------------------------------------------------------

### Cú pháp và Định dạng Mã Truy vết theo TCVN 13274:2020

Để phục vụ mô hình phân phối và bán lẻ, hệ thống cần bộ sinh mã (Code Generator) hỗ trợ 3 loại mã cốt lõi. Nếu chủ sở hữu nhãn hàng chưa chỉ định mã truy vết, nhà bán lẻ hoàn toàn có thể tự chỉ định mã nội bộ cho các mặt hàng được sử dụng trong cửa hàng của chính mình.

* **Mã truy vết vật phẩm (Product Tracing Code):** Dùng để định danh thương phẩm tại cửa hàng. Cú pháp gồm 14 chữ số bao gồm: Số định danh ứng dụng AI (01) + Số chỉ thị (1 số) + Tiền tố mã doanh nghiệp (7-10 số) + Số tham chiếu vật phẩm (2-5 số) + Số kiểm tra (1 số).


* **Mã truy vết địa điểm (Location Tracing Code):** Dùng để định danh các vị trí vật lý như kệ hàng, kho bãi hoặc cửa hàng. Cú pháp gồm 13 chữ số bao gồm: Số định danh ứng dụng AI (414) + Tiền tố mã doanh nghiệp (7-10 số) + Số tham chiếu địa điểm (2-5 số) + Số kiểm tra (1 số).


* **Mã truy vết vận chuyển (Shipment Tracing Code):** Dùng để định danh các thùng hàng (đơn vị logistic) khi luân chuyển nội bộ. Cú pháp gồm 18 chữ số bao gồm: Số định danh ứng dụng AI (00) + Số mở rộng (1 số) + Tiền tố mã doanh nghiệp (7-10 số) + Số tham chiếu theo xê-ri (6-9 số) + Số kiểm tra (1 số).



---

### Thiết kế Cơ sở dữ liệu (Database Schema)

Để hệ thống sinh mã chuẩn xác và có khả năng tracking (theo dõi), audit (kiểm toán) đến từng hộp sản phẩm bán lẻ, cấu trúc cơ sở dữ liệu cần được chia thành các bảng độc lập.

**1. Bảng `company_prefixes` (Tiền tố mã doanh nghiệp)**
Bảng này lưu trữ dải số định danh do cơ quan quản lý nhà nước cấp cho doanh nghiệp của bạn.

* `id`: Khóa chính.
* `prefix_value`: Chuỗi 7-10 chữ số.


* `is_active`: Trạng thái sử dụng.

**2. Bảng `tracing_codes` (Lưu trữ Mã truy vết gốc)**
Bảng này lưu trữ các cấu trúc mã 13, 14 hoặc 18 số đã được sinh ra. Không được thay đổi mã truy vết đã cấp trong suốt thời gian tồn tại của vật phẩm hoặc đơn vị logistic.

* `id`: Khóa chính.
* `application_identifier`: Số định danh ứng dụng (01, 414, hoặc 00).


* `company_prefix_id`: Liên kết đến bảng `company_prefixes`.
* `reference_number`: Số tham chiếu vật phẩm, địa điểm hoặc vận chuyển.


* `check_digit`: Chữ số cuối cùng được tính toán tự động thông qua thuật toán nhân các vị trí với 3 hoặc 1, cộng dồn và lấy bội của 10 gần nhất trừ đi tổng.


* `full_code`: Chuỗi mã hoàn chỉnh hợp lệ.
* `code_type`: Cột phân loại (`product`, `location`, `shipment`).

**3. Bảng `retail_item_tags` (Tem truy vết dán lên từng sản phẩm)**
Đây là bảng giải quyết bài toán tracking và audit cho mỗi hộp sản phẩm vật lý được tách ra từ lô nhập.

* `id`: Khóa chính.
* `batch_id`: Liên kết đến bảng quản lý lô nhập.
* `tracing_code_id`: Liên kết đến bảng `tracing_codes` để biết đây là mã vật phẩm nào.
* `serial_number`: Số chuỗi nhảy tự động cho từng sản phẩm trong cùng một lô (Ví dụ: 001 đến 100).
* `qr_content`: Nội dung chuỗi dữ liệu nhúng vào mã QR in ra giấy (Bao gồm Full Code 14 số + Số lô + Serial).
* `current_location_id`: Liên kết đến Mã truy vết địa điểm hiện tại của sản phẩm.


* `status`: Trạng thái vật lý (`in_warehouse`, `on_shelf`, `sold`, `recalled`).

---

### Quy trình Vận hành Sinh mã và Tracking

* **Bước 1: Khởi tạo mã gốc:** Khi nhập một sản phẩm mới chưa có mã vạch từ nhà sản xuất, hệ thống tự động trích xuất tiền tố doanh nghiệp và sinh ra Mã truy vết vật phẩm (14 số).


* **Bước 2: In tem theo lô (Serialization):** Nếu lô hàng nhập về có 50 hộp kem dưỡng ẩm, hệ thống sẽ kết hợp Mã truy vết vật phẩm (14 số), Mã lô và sinh ra 50 mã QR duy nhất chứa Serial từ 01 đến 50. Nhân viên kho tiến hành dán tem lên từng hộp.
* **Bước 3: Định danh địa điểm lưu trữ:** Các kệ hàng trong cửa hàng được gắn Mã truy vết địa điểm (AI 414). Khi nhân viên xếp hàng lên kệ, dùng máy quét mã kệ và quét mã QR trên hộp sản phẩm để hệ thống ghi nhận vị trí thực tế.


* **Bước 4: Tracking & Audit:** Khi thanh toán tại quầy POS, thu ngân quét mã QR trên hộp. Hệ thống tự động chuyển trạng thái `status` thành `sold` và lưu vết thời gian bán. Nếu cần kiểm toán, quản lý chỉ cần quét mã QR trên một hộp bất kỳ, hệ thống sẽ truy xuất ngay lập tức hộp này thuộc lô nào, đã từng nằm ở kho nào và ai là người nhập hàng.

---------------------------------------------------------------------------
Đối với các mặt hàng có vòng đời ngắn và yêu cầu khắt khe về chất lượng như đồ dùng, thực phẩm hay mỹ phẩm, Phân hệ Quản lý Lô & Hạn sử dụng chính là "trái tim" của hệ thống kiểm soát. Phân hệ này vừa phải đáp ứng việc lưu trữ thông tin nhà cung cấp, ngày tháng, số lượng, số lô phục vụ truy xuất nguồn gốc, vừa phải liên kết chặt chẽ với các tem truy vết (QR code) sinh ra từ phân hệ trước để tối ưu hóa việc lấy hàng tại kệ.

Dưới đây là cấu trúc cơ sở dữ liệu (Database Schema) và logic vận hành được thiết kế chuyên biệt cho mô hình phân phối và bán lẻ:

### 1. Cấu trúc Cơ sở dữ liệu (Database Schema)

Để hệ thống hoạt động mượt mà và không bị sai lệch số liệu khi bán hàng, luồng dữ liệu cần được bóc tách thành 3 bảng chính:

**Bảng `inbound_receipts` (Quản lý Phiếu nhập kho)**
Bảng này đóng vai trò xác định rõ thời điểm và nguồn gốc đầu vào của hàng hóa nhằm thực thi nguyên tắc lưu vết nhà cung cấp.

* `id`: Khóa chính.
* `vendor_id`: Khóa ngoại trỏ đến nhà cung cấp.
* `receipt_code`: Mã phiếu nhập nội bộ.
* `receipt_date`: Ngày nhập kho thực tế.
* `status`: Trạng thái phiếu nhập (`draft`, `completed`, `cancelled`).

**Bảng `batches` (Quản lý Lô hàng & Hạn sử dụng)**
Đây là bảng cốt lõi. Mỗi khi nhập hàng, bạn không cộng dồn số lượng vào sản phẩm chung mà sẽ tạo một "dòng lô" mới.

* `id`: Khóa chính.
* `inbound_receipt_id`: Khóa ngoại trỏ đến phiếu nhập kho (Biết lô này về từ chuyến hàng nào).
* `product_id`: Khóa ngoại trỏ đến sản phẩm.
* `batch_number`: Số lô sản xuất ghi trên bao bì sản phẩm. Đây là trường bắt buộc lưu trữ.


* `mfg_date`: Ngày sản xuất.


* `exp_date`: Ngày hết hạn hoặc hạn dùng. (Đặc biệt với các loại mỹ phẩm cho bé có độ ổn định dưới 30 tháng, bắt buộc phải ghi ngày hết hạn).


* `initial_qty`: Số lượng nhập vào ban đầu của lô.


* `current_qty`: Số lượng tồn kho hiện tại của lô (Trường này sẽ bị trừ lùi khi có giao dịch bán lẻ).
* `status`: Trạng thái của lô hàng (`available` - đang bán, `quarantined` - đang cách ly chờ kiểm tra, `recalled` - bị thu hồi sản phẩm, `out_of_stock` - hết hàng).



**Bảng `batch_locations` (Quản lý Vị trí tồn kho của Lô)**
Đối với bán lẻ, một lô sữa có thể vừa nằm trong kho, vừa nằm trên kệ trưng bày. Bảng này liên kết với cấu trúc Mã truy vết địa điểm đã thiết lập ở phân hệ trước để định danh vị trí vật lý.

* `id`: Khóa chính.
* `batch_id`: Khóa ngoại trỏ đến bảng `batches`.
* `location_tracing_code`: Mã truy vết địa điểm (Ví dụ: Mã kệ A1, kho B).


* `quantity`: Số lượng của lô đó đang nằm tại vị trí này.

---

### 2. Sự liên kết với Phân hệ Khởi tạo Mã Truy vết (Traceability Code Generator)

Hệ thống sẽ kết nối trực tiếp bảng `batches` với bảng `retail_item_tags` (tem QR dán trên từng sản phẩm) từ module trước:

* Khi bạn duyệt hoàn thành `inbound_receipts` cho một lô sữa tắm gồm 50 chai, hệ thống tự động chèn 1 bản ghi vào bảng `batches` với `initial_qty` là 50.
* Ngay lập tức, hệ thống tự động sinh ra 50 bản ghi trong bảng `retail_item_tags`, mỗi bản ghi chứa một mã QR duy nhất được kế thừa thông tin `batch_id`.
* Khi dán 50 mã QR này lên chai sữa tắm, mỗi chai đã trở thành một thực thể độc lập nhưng vẫn truy ngược được về đúng thông tin số lô, hạn dùng và nhà cung cấp.

---

### 3. Logic Vận hành và Quản lý Dữ liệu

* **Logic Xuất hàng (FEFO - First Expired, First Out):**
Khi nhân viên kho cần soạn hàng để châm lên kệ, phần mềm sẽ truy vấn bảng `batches` và tự động sắp xếp theo `exp_date` tăng dần. Hệ thống sẽ chỉ định nhân viên lấy các sản phẩm thuộc lô có hạn sử dụng gần nhất, đi kèm với Mã truy vết địa điểm cụ thể để nhân viên biết chính xác phải lấy hàng ở đâu.


* **Logic Cảnh báo Hạn sử dụng (Expiry Alerts):**
Sử dụng Cronjob của Laravel quét trường `exp_date` trong bảng `batches` hàng ngày. Các lô hàng có hạn sử dụng sắp đến (ví dụ: còn 90 ngày) sẽ được hệ thống gom lại và gửi cảnh báo sang bảng `compliance_warnings` để chủ shop có phương án đẩy doanh số hoặc đổi trả.
* **Logic Thu hồi Sản phẩm (Recall):**
Nếu cơ quan chức năng hoặc nhà sản xuất yêu cầu đình chỉ lưu hành và thu hồi một lô mỹ phẩm vi phạm, bạn chỉ cần gõ `batch_number` vào hệ thống. Phần mềm lập tức khóa (`status` = `recalled`) lô hàng này trên toàn hệ thống POS, không cho phép thu ngân quét mã tính tiền, đồng thời hiển thị vị trí vật lý (`location_tracing_code`) để nhân viên đến kệ rút hàng xuống.



Bạn có dự định tích hợp thêm module quản lý hạn mức tồn kho an toàn (Min/Max Inventory) để hệ thống tự động gợi ý danh sách cần đặt hàng khi số lượng của các lô hiện tại giảm xuống mức thấp không?

---------------------------------------------------------------------------

Chào bạn, Phân hệ Điều phối Xuất kho (Outbound & FEFO) chính là chốt chặn cuối cùng để thực thi nguyên tắc truy xuất "một bước sau" – tức là phải biết chính xác sản phẩm thuộc lô nào đã được bán cho ai hoặc chuyển đi đâu. Đối với doanh nghiệp bán lẻ mẹ và bé, phân hệ này còn phải tự động hóa việc xuất hàng theo nguyên tắc FEFO (Hết hạn trước - Xuất trước) để giảm thiểu tối đa rủi ro tồn kho hàng cận date.

Dựa trên sự liên kết với phân hệ Quản lý Lô và Mã Truy vết liền kề, dưới đây là thiết kế Database Schema và logic vận hành chi tiết bằng Laravel:

### 1. Cấu trúc Cơ sở dữ liệu (Database Schema)

Để đáp ứng cả hai nghiệp vụ là bán lẻ tại quầy (POS) và xuất buôn/xuất trả nhà cung cấp, dữ liệu xuất kho cần được quản lý qua 3 bảng có tính liên kết chặt chẽ:

**Bảng `outbound_orders` (Quản lý Thông tin Đơn xuất kho)**
Bảng này lưu trữ thông tin chung của một lần xuất hàng, giúp xác định "bước sau" của luồng hàng hóa là đi về đâu.

* `id`: Khóa chính.
* `order_code`: Mã đơn xuất (Ví dụ: POS-2608-001, WH-TRANS-002).
* `type`: Loại phiếu xuất (`retail` - Bán lẻ POS, `wholesale` - Bán buôn, `transfer` - Chuyển kho nội bộ, `return_to_vendor` - Trả hàng nhà cung cấp).
* `destination_type` & `destination_id`: Cặp khóa ngoại đa hình (Polymorphic).
* Nếu là `retail` -> Trỏ đến bảng Khách hàng (Customer) để lưu vết người mua.


* Nếu là `transfer` -> Trỏ đến bảng Mã truy vết địa điểm (Ví dụ: Mã kệ hàng, Cửa hàng chi nhánh).




* `status`: Trạng thái (`pending` - Chờ xuất, `completed` - Đã xuất/Đã thanh toán, `cancelled` - Hủy).
* `created_at`: Thời gian xuất hàng.

**Bảng `outbound_order_items` (Chi tiết Yêu cầu Xuất)**
Bảng này lưu trữ danh sách các mặt hàng (SKU) mà khách hàng muốn mua hoặc kho cần xuất.

* `id`: Khóa chính.
* `outbound_order_id`: Khóa ngoại trỏ đến `outbound_orders`.
* `product_id`: Khóa ngoại trỏ đến bảng Sản phẩm (`products`).
* `requested_qty`: Số lượng yêu cầu xuất.
* `fulfilled_qty`: Số lượng thực tế đã quét mã xuất kho.

**Bảng `outbound_picked_batches` (Chi tiết Lấy hàng theo Lô & Tem truy vết)**
Đây là bảng cốt lõi thực thi FEFO và truy xuất nguồn gốc sâu đến từng đơn vị sản phẩm.

* `id`: Khóa chính.
* `outbound_order_item_id`: Khóa ngoại trỏ đến `outbound_order_items`.
* `batch_id`: Khóa ngoại trỏ đến bảng `batches` (Ghi nhận chính xác lô hàng nào bị trừ).
* `retail_item_tag_id`: (Nullable) Khóa ngoại trỏ đến bảng `retail_item_tags` sinh ra từ module trước. Nếu bán lẻ quét mã QR trên từng hộp, trường này sẽ lưu ID của hộp đó để tracking 1-1.
* `picked_qty`: Số lượng thực tế lấy từ lô này.

---

### 2. Logic Vận hành (Business Logic) trong Laravel

**A. Thuật toán Đề xuất FEFO (First Expired, First Out)**
Khi có một yêu cầu xuất kho, thay vì để nhân viên tự nhặt hàng, Laravel Controller sẽ tự động query để tìm các lô hàng phù hợp nhất:

* *Query:* Tìm trong bảng `batches` các lô có `product_id` tương ứng, điều kiện `current_qty > 0`, và `status` = `available` (bỏ qua các lô có trạng thái `recalled` - bị thu hồi).


* *Order:* Sắp xếp theo `exp_date` ASC (Tăng dần - Lô nào cận date nhất sẽ lên đầu).
* *Action:* Hệ thống hiển thị lên màn hình máy POS/PDA yêu cầu nhân viên lấy hàng từ số lô này, tại Mã truy vết địa điểm cụ thể.



**B. Xử lý tại quầy thu ngân (POS Scanning)**

* Thu ngân dùng súng quét mã QR (chứa Mã truy vết vật phẩm 14 số + Serial) dán trên hộp sản phẩm.


* Hệ thống nhận diện được `retail_item_tag_id`, từ đó query ngược ra `batch_id`.
* *Kiểm tra FEFO:* Nếu hộp sản phẩm thu ngân đang quét KHÔNG thuộc lô được hệ thống đề xuất (nghĩa là khách tự nhặt một hộp lô mới hơn trên kệ), hệ thống có thể hiện một cảnh báo nhỏ (Soft Warning) trên màn hình POS: *"Sản phẩm này không thuộc lô cận date nhất. Vẫn tiếp tục bán?"*.
* Khi thanh toán thành công, hệ thống trừ `current_qty` trong bảng `batches`, update `status` của hộp đó thành `sold` trong bảng `retail_item_tags`, và lưu trữ thông tin khách hàng vào `outbound_orders`.



**C. Đóng vòng lặp Truy vết (Traceability & Recall)**
Thiết kế này đáp ứng hoàn hảo yêu cầu quản lý sự cố:

* Nếu có thông báo thu hồi một lô sữa tắm từ nhà cung cấp, bạn truy vấn bảng `outbound_picked_batches` theo `batch_id` của lô đó.


* Join với bảng `outbound_orders` (kiểu `retail`), bạn sẽ lập tức có được danh sách toàn bộ Tên và Số điện thoại của khách hàng đã mua lô sữa tắm bị lỗi này để tiến hành thu hồi.



Bạn có dự định tích hợp tính năng gửi tin nhắn (SMS/Zalo ZNS) tự động từ hệ thống đến khách hàng trong trường hợp khẩn cấp phải thu hồi sản phẩm lỗi không?

---------------------------------------------------------------------------

Chào bạn, Phân hệ Bán lẻ (Point of Sale - POS) chính là "nút thắt" quyết định sự thành bại của toàn bộ hệ thống truy xuất nguồn gốc. Theo quy định của pháp luật, đây là khâu thực thi nguyên tắc "một bước sau", yêu cầu bạn phải biết rõ sản phẩm đó đã được bán cho ai để phục vụ việc thu hồi khi có sự cố.

Bài toán **"Cùng 1 SKU nhưng có 2 lô khác nhau trên kệ"** là bài toán kinh điển trong bán lẻ. Để giải quyết triệt để và tracking đúng lô khi quét mã, hệ thống của bạn không thể chỉ dựa vào bảng hóa đơn thông thường, mà phải thiết kế cơ sở dữ liệu (Database Schema) kết nối chặt chẽ với các tem truy vết vật phẩm (TCVN 13274:2020) đã sinh ra ở module trước.

Dưới đây là thiết kế chi tiết cho Phân hệ Bán lẻ (POS):

### 1. Giải pháp xử lý bài toán "1 SKU - 2 Lô khác nhau" tại quầy POS

Thay vì để thu ngân tự nhớ, hệ thống POS của bạn sẽ được lập trình để xử lý qua 2 kịch bản quét mã:

* **Kịch bản 1 (Chính xác tuyệt đối 100%): Quét mã QR Truy vết.**
Trên mỗi hộp sản phẩm đã được dán một mã QR chứa Mã truy vết vật phẩm định danh duy nhất (sinh ra từ bảng `retail_item_tags`). Khi thu ngân quét mã QR này, hệ thống sẽ bỏ qua bước tìm SKU, mà trực tiếp map thẳng vào `retail_item_tag_id`, từ đó truy ra chính xác `batch_id` (Số lô) mà không cần hỏi lại thu ngân.
* **Kịch bản 2 (Dự phòng): Quét mã vạch truyền thống (Barcode của SKU).**
Thu ngân quét mã vạch gốc in sẵn trên vỏ hộp (Mã này chỉ mang thông tin SKU). Hệ thống sẽ truy vấn bảng `batches`. Nếu phát hiện SKU này đang có 2 lô (Lô A và Lô B) cùng có trạng thái `on_shelf` (trên kệ), màn hình POS sẽ **bật một Popup bắt buộc (Required Dialog)**. Popup này hiển thị danh sách các lô đang có trên kệ, sắp xếp theo thuật toán FEFO (Lô cận date xếp trên cùng), và yêu cầu thu ngân cầm hộp sữa lên, nhìn số lô và click chọn đúng lô thực tế đang giao cho khách.

### 2. Cấu trúc Cơ sở dữ liệu (Database Schema) cho POS

Để lưu vết các giao dịch này, DB của module POS sẽ bao gồm 3 bảng liên kết với nhau:

**Bảng `pos_invoices` (Hóa đơn Bán lẻ)**
Bảng này lưu trữ thông tin tổng quan của giao dịch và đặc biệt quan trọng trong việc gắn kết với thông tin khách hàng để phục vụ truy xuất ngược.

* `id`: Khóa chính.
* `invoice_code`: Mã hóa đơn (Ví dụ: INV-26082026-001).
* `customer_id`: (Nullable) Khóa ngoại trỏ đến bảng Khách hàng. Việc khuyến khích khách hàng đọc số điện thoại để tích điểm chính là cách bạn lấy data để phục vụ việc thu hồi sản phẩm lỗi sau này.


* `cashier_id`: Nhân viên thu ngân thực hiện giao dịch.
* `total_amount`: Tổng tiền thanh toán.
* `transaction_time`: Thời gian giao dịch chính xác.
* `status`: Trạng thái hóa đơn (`completed`, `refunded`).

**Bảng `pos_invoice_items` (Chi tiết SKU trong Hóa đơn)**
Bảng này ghi nhận khách hàng mua những mặt hàng gì, số lượng tổng là bao nhiêu.

* `id`: Khóa chính.
* `pos_invoice_id`: Khóa ngoại trỏ đến bảng `pos_invoices`.
* `product_id`: Khóa ngoại trỏ đến bảng `products` (SKU).
* `quantity`: Tổng số lượng SKU khách mua.
* `price`: Đơn giá tại thời điểm bán.

**Bảng `pos_invoice_batches` (Lưu vết Lô & Tem Truy vết - Cốt lõi của Tracking)**
Bảng này là cầu nối (Pivot Table) giữa dòng hóa đơn và dữ liệu tồn kho theo lô. Đây là nơi giải quyết bài toán khách mua 2 hộp sữa cùng SKU nhưng thuộc 2 lô khác nhau.

* `id`: Khóa chính.
* `pos_invoice_item_id`: Khóa ngoại trỏ đến bảng `pos_invoice_items`.
* `batch_id`: Khóa ngoại trỏ đến bảng `batches`. Cột này ghi nhận chính xác "Số lô" (Batch Number) của hộp sản phẩm đã được bán ra.
* `retail_item_tag_id`: (Nullable) Khóa ngoại trỏ đến bảng `retail_item_tags`. Nếu thu ngân dùng súng quét mã QR tự in (Kịch bản 1), ID của mã tem truy vết đó sẽ được lưu vào đây để hệ thống biết chính xác hộp vật lý nào đã rời khỏi cửa hàng.
* `quantity`: Số lượng bán ra thuộc lô này. *(Ví dụ: Khách mua 2 hộp sữa Meiji, nhưng kho chỉ còn 1 hộp Lô A và lấy thêm 1 hộp Lô B. Hệ thống sẽ lưu thành 2 dòng trong bảng này: Dòng 1 ghi Lô A - SL 1; Dòng 2 ghi Lô B - SL 1).*

### 3. Logic Vận hành Quản lý Dữ liệu (Backend Logic)

Khi giao dịch bán lẻ hoàn tất (Checkout), hệ thống Laravel của bạn cần thực hiện 1 Database Transaction bao gồm các lệnh sau để đảm bảo tính toàn vẹn:

1. **Lưu Hóa đơn:** `INSERT` dữ liệu vào `pos_invoices`, `pos_invoice_items`, và `pos_invoice_batches`.
2. **Trừ tồn kho Lô:** Lấy `batch_id` và `quantity` từ giao dịch, chạy lệnh `UPDATE batches SET current_qty = current_qty - quantity WHERE id = batch_id`. Điều này đảm bảo tồn kho của lô A và lô B luôn chính xác.
3. **Cập nhật trạng thái Tem:** Nếu có `retail_item_tag_id`, chạy lệnh `UPDATE retail_item_tags SET status = 'sold' WHERE id = retail_item_tag_id`.
4. **Sẵn sàng Truy xuất & Thu hồi:** Nếu sau này cơ quan quản lý y tế hoặc nhà sản xuất phát đi thông báo một lô kem hăm tã gây tác dụng phụ trầm trọng, bạn chỉ cần truy vấn (Join) từ `batch_id` của lô lỗi trong bảng `pos_invoice_batches` -> `pos_invoices` -> `customers`. Ngay lập tức, bạn có danh sách số điện thoại để gọi điện thu hồi sản phẩm.



Việc thiết kế chặt chẽ như thế này sẽ giúp bạn vận hành chuỗi bán lẻ hoàn toàn tuân thủ các quy định khắt khe nhất. Bạn có muốn đi vào chi tiết cấu trúc JSON/API dùng để truyền dữ liệu từ màn hình Frontend POS (React/Vue) xuống Backend Laravel khi thực hiện quét mã không?

---------------------------------------------------------------------------

Thiết kế Phân hệ Hồ sơ Khách hàng (Customer Profile) kết hợp với Cổng truy xuất nguồn gốc (Traceability Portal) là bước đi đột phá để bạn hoàn thiện chuỗi quản lý khép kín. Phân hệ này không chỉ giúp bạn tuân thủ nguyên tắc lưu vết "một bước sau" để phục vụ việc thu hồi sản phẩm khi có sự cố, mà còn là công cụ đắc lực để tương tác trực tiếp với người tiêu dùng.

Đối với một hệ thống được xây dựng bằng Laravel, việc quản lý khách hàng và lưu vết hành vi quét mã QR tại nhà đòi hỏi một thiết kế cơ sở dữ liệu (Database Schema) vừa chặt chẽ để chống giả mạo, vừa linh hoạt để xử lý trường hợp khách quét lại nhiều lần.

### 1. Cấu trúc Cơ sở dữ liệu (Database Schema)

Chúng ta sẽ thiết lập 2 bảng dữ liệu chính để bóc tách thông tin định danh khách hàng và lịch sử tương tác mã truy vết:

**Bảng `customers` (Hồ sơ Khách hàng gốc)**
Bảng này lưu trữ thông tin định danh cốt lõi, thường được thu thập ngay tại quầy thu ngân (POS) hoặc khi khách hàng chủ động nhập thông tin trên Cổng truy xuất.

* `id`: Khóa chính.
* `phone`: Số điện thoại (Đóng vai trò là chuỗi định danh duy nhất - Unique Index).
* `full_name`: Họ và tên khách hàng.
* `loyalty_points`: Điểm tích lũy (Có thể dùng làm "mồi nhử" để khuyến khích khách hàng nhập SĐT khi quét mã ở nhà).
* `created_at` / `updated_at`: Thời gian tạo và cập nhật hồ sơ.

**Bảng `traceability_scan_logs` (Lưu vết hành vi quét mã QR)**
Đây là bảng "Append-only" (chỉ thêm mới, không ghi đè), giúp bạn kiểm toán (audit) toàn bộ hành trình quét mã của từng hộp sản phẩm vật lý.

* `id`: Khóa chính.
* `retail_item_tag_id`: Khóa ngoại trỏ đến bảng `retail_item_tags` (Định danh chính xác hộp sản phẩm mà khách đang cầm trên tay).
* `batch_id`: Khóa ngoại trỏ đến bảng `batches` (Tối ưu tốc độ truy vấn để biết ngay lô nào đang được quét nhiều nhất).
* `customer_id`: (Nullable) Khóa ngoại trỏ đến bảng `customers`. Có thể rỗng nếu khách chỉ quét để xem thông tin mà từ chối nhập SĐT.
* `scanned_at`: Thời gian thực hiện hành vi quét mã (Timestamp).
* `ip_address` & `user_agent`: Lưu trữ địa chỉ IP và thông tin thiết bị (iPhone, Android) để phân tích hành vi và phát hiện gian lận.

### 2. Logic Vận hành & Xử lý "Quét nhiều lần" (Backend Logic)

Khi khách hàng mang hộp sữa hoặc tuýp kem dưỡng ẩm về nhà, dùng Zalo/Camera quét mã QR truy vết (được sinh ra theo chuẩn định dạng từ module trước), hệ thống sẽ xử lý qua các luồng sau:

**A. Thu thập thông tin khéo léo (Lead Capture)**

* Thay vì ép buộc khách hàng nhập thông tin ngay lập tức, màn hình (Landing Page) hiện ra sẽ hiển thị các thông tin truy xuất cơ bản: Tên sản phẩm, Tên nhà cung cấp, Ngày sản xuất, Hạn sử dụng, và Bản tự công bố an toàn thực phẩm.


* Để lấy được `customer_id`, bạn có thể thiết kế một nút: *"Nhập Số điện thoại để xác nhận hàng chính hãng & Tích điểm"*. Khi khách nhập SĐT, Laravel Controller sẽ kiểm tra (update hoặc create mới vào bảng `customers`), sau đó chèn một bản ghi vào bảng `traceability_scan_logs`.

**B. Xử lý kịch bản khách quét nhiều lần (Scan Frequency Control)**
Do bảng `traceability_scan_logs` là bảng lưu vết tuần tự, mỗi lần khách quét mã, hệ thống đều `INSERT` một dòng mới. Để xử lý thông minh, bạn thiết lập logic:

* *Lần quét đầu tiên:* Hệ thống hiển thị thông báo xanh: *"Chúc mừng! Đây là lần đầu tiên sản phẩm này được quét mã xác thực. Sản phẩm chính hãng."*
* *Lần quét thứ 2 đến thứ 5 (Cùng một SĐT hoặc IP):* Khách hàng quét lại để xem hướng dẫn sử dụng. Hệ thống hiển thị: *"Sản phẩm này đã được xác thực trước đó vào ngày [scanned_at]."*
* *Cảnh báo bất thường (Cùng 1 mã QR nhưng bị quét hàng trăm lần ở nhiều IP khác nhau):* Đây là dấu hiệu của việc mã QR bị copy và in lên hàng giả. Hệ thống sẽ bật cảnh báo đỏ trên màn hình người dùng: *"Cảnh báo! Mã QR này đã được quét vượt quá số lần quy định. Vui lòng liên hệ cửa hàng để kiểm tra."* Đồng thời, hệ thống tạo một thông báo gửi về màn hình quản trị của bạn.

**C. Sẵn sàng cho kịch bản Thu hồi khẩn cấp (Recall Action)**
Nếu Cục Quản lý Dược yêu cầu thu hồi một lô mỹ phẩm do tác dụng phụ, bạn có thể truy xuất bảng `traceability_scan_logs` kết hợp với bảng `pos_invoices`. Nếu khách hàng chưa mua (hoặc được tặng) nhưng đã quét mã tại nhà và để lại SĐT, bạn vẫn có thể trích xuất được data để liên hệ và thu hồi sản phẩm lỗi, đáp ứng hoàn hảo nghĩa vụ pháp lý.

Với luồng xử lý quét mã QR này, bạn muốn hệ thống chỉ hiển thị thông tin sản phẩm đơn thuần trên màn hình điện thoại của khách, hay muốn tích hợp thêm mục hướng dẫn sử dụng chuyên sâu (ví dụ: cách pha sữa công thức đúng chuẩn) để tăng trải nghiệm người dùng?

---------------------------------------------------------------------------

Chào bạn, Phân hệ Truy vết 2 chiều (Two-way Traceability) chính là linh hồn của toàn bộ hệ thống, nơi phát huy tối đa sức mạnh của tất cả các bảng dữ liệu chúng ta đã thiết kế ở các bước trước.

Theo đúng nguyên tắc "một bước trước - một bước sau" của Thông tư 25/2019/TT-BYT đối với thực phẩm, và yêu cầu kiểm soát chất lượng, thu hồi sản phẩm của Thông tư 06/2011/TT-BYT đối với mỹ phẩm, **việc truy vết thực chất không phải là tạo ra một bảng dữ liệu mới để copy lại thông tin, mà là nghệ thuật thiết kế các truy vấn (Queries) liên kết các bảng đã có**, kết hợp với việc xây dựng thêm các **Bảng Quản lý Sự cố (Incident Management)** để lưu vết hành động xử lý.

Dưới đây là thiết kế chuẩn mực cho phân hệ này, tối ưu hóa cho framework Laravel:

### 1. Cấu trúc Bảng dữ liệu bổ sung (Database Schema)

Để hệ thống không chỉ "tìm ra lỗi" mà còn "quản lý quá trình xử lý lỗi", chúng ta cần bổ sung 2 bảng chuyên dụng để đáp ứng yêu cầu pháp lý:

**Bảng `product_recalls` (Quản lý Chiến dịch Thu hồi)**
Đáp ứng quy định về việc đình chỉ lưu hành và thu hồi sản phẩm khi có yêu cầu từ cơ quan quản lý hoặc nhà sản xuất.

* `id`: Khóa chính.
* `recall_code`: Mã chiến dịch thu hồi (Ví dụ: RECALL-2026-08).
* `batch_id`: Khóa ngoại trỏ đến bảng `batches` (Xác định chính xác lô hàng bị thu hồi).
* `reason`: Lý do thu hồi (Ví dụ: Chứa thành phần vượt mức cho phép, Lỗi bao bì, v.v.).
* `decision_date`: Ngày ra quyết định thu hồi.
* `status`: Trạng thái chiến dịch (`initiating` - Đang khởi tạo, `in_progress` - Đang thu hồi, `completed` - Đã hoàn tất).
* `total_recalled_qty`: Số lượng sản phẩm thực tế đã thu hồi lại được từ khách hàng hoặc kệ hàng.
* `refunded_amount`: Cột Decimal ghi nhận tổng chi phí hoàn trả cho khách hàng khi thu hồi.

**Bảng `adverse_event_reports` (Báo cáo Tác dụng bất lợi)**
Bảng này cực kỳ quan trọng đối với mảng mỹ phẩm cho mẹ và bé. Nó đáp ứng trực tiếp quy định phải báo cáo tác dụng phụ trầm trọng về Cục Quản lý dược trong vòng 07 ngày kể từ khi nhận thông tin đầu tiên.

* `id`: Khóa chính.
* `customer_id`: Khóa ngoại trỏ đến `customers` (Khách hàng gặp sự cố).
* `retail_item_tag_id`: Khóa ngoại trỏ đến `retail_item_tags` (Biết chính xác khách đã dùng hộp sản phẩm vật lý nào).
* `incident_date`: Ngày khách hàng báo cáo sự cố.
* `symptoms`: Mô tả chi tiết triệu chứng (Ví dụ: Bé bị mẩn đỏ, dị ứng ngứa sau khi bôi kem).
* `severity_level`: Mức độ nghiêm trọng (`mild` - Nhẹ, `serious` - Trầm trọng, `life_threatening` - Đe dọa tính mạng).
* `is_reported_to_agency`: Cờ (Boolean) xác nhận đã gửi báo cáo cho cơ quan y tế chưa.
* `status`: Trạng thái xử lý (`investigating` - Đang điều tra, `resolved` - Đã giải quyết/Bồi thường).

---

### 2. Logic Vận hành: Truy vết Xuôi (Trace-forward)

**Tình huống:** Nhà cung cấp báo tin lô sữa tắm số hiệu "L2024A" bị lỗi màng seal, yêu cầu thu hồi khẩn cấp.

**Luồng xử lý trên Laravel (Controller & Eloquent):**

1. **Xác định nguồn:** Tìm kiếm trong bảng `batches` với `batch_number = 'L2024A'`. Hệ thống lập tức khóa lô này lại (`status` = `recalled`).
2. **Khóa hàng trên kệ:** Truy vấn bảng `retail_item_tags` (Tem truy vết) thuộc `batch_id` này mà `status` đang là `on_shelf` hoặc `in_warehouse`. Đổi toàn bộ trạng thái thành `recalled` để thu ngân không thể quét mã bán ra được nữa. Hệ thống trích xuất `location_tracing_code` (Mã địa điểm) để báo nhân viên ra đúng kệ A1 rút hàng xuống.
3. **Truy tìm "Bước sau":** Join bảng `pos_invoice_batches` -> `pos_invoices` -> `customers`. Hệ thống xuất ra ngay danh sách gồm: Tên, Số điện thoại của tất cả các mẹ đã mua hộp sữa tắm thuộc lô "L2024A", thời gian mua, và mã hóa đơn.
4. **Thực thi:** Tạo 1 bản ghi vào bảng `product_recalls`. CSKH sẽ gọi điện theo danh sách để mời khách mang sản phẩm ra đổi trả.

---

### 3. Logic Vận hành: Truy vết Ngược (Trace-back)

**Tình huống:** Một khách hàng mang hộp kem hăm tã đến cửa hàng phàn nàn rằng bé bôi bị dị ứng mẩn đỏ.

**Luồng xử lý trên Laravel:**

1. **Bắt đầu từ "Bước sau":** Nhân viên dùng app nội bộ quét mã QR (TCVN 13274:2020) dán trên vỏ hộp kem. Hệ thống nhận diện `retail_item_tag_id`.
2. **Truy vết "Bước trước":** Từ `retail_item_tag_id`, hệ thống query ra `batch_id` (Số lô) -> `inbound_receipts` (Phiếu nhập kho) -> `vendors` (Nhà cung cấp).
3. **Kiểm tra tính pháp lý:** Đồng thời query sang bảng `products` -> `product_compliances`. Hệ thống hiển thị ngay lên màn hình:
* Hộp kem này nhập ngày 01/08/2026.
* Từ Nhà phân phối Công ty X.
* Số tiếp nhận Phiếu công bố mỹ phẩm là 135/11/CBMP-HN (Vẫn đang còn hiệu lực).
* File COA (Giấy chứng nhận phân tích) của riêng lô hàng đó (để chứng minh shop bán hàng chuẩn, có giấy tờ đàng hoàng).


4. **Thực thi:** Tạo 1 bản ghi vào bảng `adverse_event_reports`. Nếu chọn `severity_level` là `serious`, Cronjob của hệ thống sẽ bật cảnh báo đỏ liên tục trên Dashboard để nhắc bạn hoàn thiện mẫu Báo cáo tác dụng bất lợi gửi Cục Quản lý dược đúng hạn 07 ngày.

Với cách tổ chức Database phân mảnh (Normalization) kết hợp các khóa ngoại (Foreign Keys) vững chắc như thế này, hệ thống của bạn hoàn toàn đủ khả năng kiểm toán dữ liệu sâu đến từng hộp sản phẩm vật lý.

Để hoàn thiện kiến trúc, bạn có muốn chúng ta thiết kế tiếp một module nhỏ (Dashboard & Reports) để trực quan hóa các dữ liệu truy vết này thành biểu đồ, giúp bạn nắm bắt tổng quan tình hình kinh doanh mỗi ngày không?

---------------------------------------------------------------------------
Chào bạn, việc thiết kế chi tiết Phân hệ Ghi nhận Tác dụng phụ (Adverse Event Report) cho thấy bạn đang xây dựng phần mềm với tư duy tuân thủ pháp luật rất nghiêm túc. Trong mảng bán lẻ đồ dùng và mỹ phẩm cho mẹ và bé (như kem hăm tã, sữa tắm gội), đây là chốt chặn cực kỳ quan trọng để bảo vệ uy tín cửa hàng.

Theo quy định tại **Khoản 3 Điều 48 và Phụ lục số 18-MP của Thông tư 06/2011/TT-BYT**, khi phát hiện tác dụng phụ trầm trọng ảnh hưởng đến tính mạng, bạn bắt buộc phải báo cáo cho Cục Quản lý dược trong vòng **07 ngày** kể từ khi nhận thông tin đầu tiên, và nộp báo cáo chi tiết trong **08 ngày** tiếp theo.

Để xuất ra được đúng "Mẫu thông báo tác dụng bất lợi đối với mỹ phẩm" (Phụ lục số 18-MP), hệ thống cơ sở dữ liệu (Database Schema) bằng Laravel của bạn cần được thiết kế như sau:

### 1. Cấu trúc Bảng `adverse_event_reports` (Ghi nhận Tác dụng bất lợi)

Bảng này sẽ mapping (ánh xạ) trực tiếp 1-1 với các trường dữ liệu yêu cầu trong Phụ lục 18-MP, đồng thời liên kết chặt chẽ với các module đã thiết kế trước đó:

**Thông tin liên kết hệ thống (System Tracking)**

* `id`: Khóa chính.
* `report_code`: Mã báo cáo nội bộ (Ví dụ: AER-202608-001).
* `retail_item_tag_id`: Khóa ngoại trỏ đến bảng tem truy vết `retail_item_tags`. (Chỉ cần ID này, hệ thống dùng Eloquent của Laravel sẽ query ngược ra ngay: Tên sản phẩm, Nhà sản xuất, Ngày sản xuất, Hạn dùng, Số lô).


* `customer_id`: Khóa ngoại trỏ đến người mua hàng trong bảng `customers`.

**Thông tin chi tiết người sử dụng (Victim Details)**
Lưu ý: Khách mua là mẹ, nhưng người sử dụng có thể là em bé, nên cần tách bạch theo đúng Phụ lục 18-MP.

* `victim_name`: Tên người sử dụng.


* `victim_identity_number`: Số CMND/CCCD hoặc Hộ chiếu (Nếu là người lớn).


* `victim_age`: Tuổi (Có thể lưu theo tháng tuổi nếu là trẻ sơ sinh).


* `victim_gender`: Giới tính.



**Thông tin chi tiết về tác dụng bất lợi (Adverse Event Details)**

* `onset_datetime`: Thời gian xuất hiện tác dụng bất lợi.


* `event_description`: Mô tả chi tiết tác dụng bất lợi (Dạng Text).


* `time_gap_after_last_use`: Thời gian giữa lần dùng sản phẩm cuối cùng và thời điểm xuất hiện (Lưu theo phút, giờ, hoặc ngày).


* `usage_description`: Sản phẩm đã được sử dụng như thế nào (Ví dụ: Thoa một lớp mỏng sau khi tắm).



**Thông tin Y tế và Kết quả (Medical & Outcome)**

* `required_hospitalization`: Boolean - Người sử dụng có phải nhập viện không?


* `required_medical_treatment`: Boolean - Người sử dụng có phải điều trị y tế không?


* `outcome_status`: Kết quả hiện tại (Enum: `recovered` - Đã hồi phục, `not_recovered` - Vẫn chưa hồi phục, `unknown` - Không biết, `death` - Tử vong).


* `outcome_date`: Ngày ghi nhận kết quả (Ngày hồi phục hoặc ngày tử vong).


* `report_source`: Nguồn cung cấp báo cáo (Enum: `healthcare_professional` - Chuyên gia y tế, `customer` - Khách hàng, `other` - Nguồn khác).



**Thông tin Quản lý & Pháp lý nội bộ (Compliance Tracking)**

* `severity_level`: Mức độ nghiêm trọng (`mild` - Nhẹ, `serious` - Trầm trọng, đe dọa tính mạng).
* `initial_report_deadline`: Hạn chót phải gửi báo cáo đầu tiên (Tự động tính = `created_at` + 7 ngày).


* `initial_report_sent_at`: (Nullable) Timestamp ghi nhận thời điểm bạn đã nộp báo cáo cho Cục Quản lý dược.
* `detailed_report_deadline`: Hạn chót gửi báo cáo chi tiết (Tự động tính = `initial_report_sent_at` + 8 ngày).


* `detailed_report_sent_at`: (Nullable) Timestamp ghi nhận thời điểm hoàn tất nộp báo cáo chi tiết.
* `status`: Trạng thái nội bộ (`draft`, `investigating` - Đang điều tra, `reported_to_agency` - Đã báo cáo cơ quan, `closed` - Đóng hồ sơ).

---

### 2. Logic Vận hành và Quản lý Dữ liệu (Backend Logic)

**A. Khởi tạo Báo cáo nhanh chóng từ POS/Portal:**

* Khi có khách hàng gọi điện phản ánh, nhân viên CSKH mở giao diện, gõ Số điện thoại khách hàng hoặc Mã QR của sản phẩm.
* Hệ thống tự động điền (Auto-fill) toàn bộ thông tin về Sản phẩm, Số lô, Ngày sản xuất, Hạn dùng, Nhà cung cấp (Lấy từ bảng `batches` và `inbound_receipts`). Nhân viên chỉ việc điền các thông tin về triệu chứng của bé.

**B. Cảnh báo Pháp lý Chữ đỏ (Red Alert Cronjob):**

* Bạn cấu hình một Command trong Laravel chạy mỗi giờ (Hourly Cronjob).
* Nếu `severity_level` là `serious` VÀ `initial_report_sent_at` đang rỗng (Null):
* Hệ thống sẽ lấy `initial_report_deadline` trừ đi thời gian hiện tại.
* Khi chỉ còn 48 tiếng là đến hạn 07 ngày theo quy định, hệ thống sẽ đẩy thông báo khẩn cấp ra màn hình Dashboard hoặc gửi tin nhắn Telegram cho bạn: *"Cảnh báo: Báo cáo sự cố AER-202608-001 sắp quá hạn nộp Cục Quản lý dược!"*.





**C. Trích xuất Biểu mẫu Tự động (Export to PDF/Word):**

* Bạn sử dụng package xuất file (như `dompdf` hoặc `phpword`) trong Laravel.
* Chỉ với một nút bấm "Xuất Phụ lục 18-MP", hệ thống sẽ lấy dữ liệu từ bảng `adverse_event_reports` đổ chính xác vào Form chuẩn mẫu của Bộ Y tế. Bạn chỉ việc in ra, ký tên, đóng dấu và nộp.



Với thiết kế này, cửa hàng bán lẻ của bạn không chỉ kiểm soát rủi ro chất lượng một cách chuyên nghiệp mà còn sẵn sàng vượt qua bất kỳ cuộc thanh tra hậu mại nào.

Bạn đã hình dung đủ bức tranh toàn cảnh để bắt tay vào thiết kế Models và Migrations trong Laravel chưa, hay muốn làm rõ thêm về luồng giao diện (UI/UX) cho nhân viên khi tiếp nhận thông tin sự cố này?

---------------------------------------------------------------------------

Dưới đây là bức tranh tổng thể về luồng dữ liệu của hệ thống:

### Khởi tạo và Nhập hàng (Inbound & Master Data)

* Quản trị viên thiết lập hồ sơ nhà cung cấp và nạp các minh chứng pháp lý bắt buộc (Bản công bố, Hồ sơ PIF) lên hệ thống.


* Khi hàng về, Quản lý kho tạo phiếu nhập, hệ thống bắt buộc ghi nhận số lô sản xuất và hạn dùng thực tế.


* Phần mềm tự động sinh Mã truy vết vật phẩm (14 số) kết hợp số serial, in thành tem QR để dán lên từng hộp sữa hoặc tuýp kem.



### Lưu trữ và Kiểm soát (Inventory & Monitoring)

* Hàng hóa sau khi dán tem sẽ được gán Mã truy vết địa điểm vật lý (vị trí kho, kệ hàng) để dễ dàng kiểm đếm và lấy hàng.


* Các tác vụ chạy ngầm (Cronjobs) liên tục giám sát hạn sử dụng của từng lô vật lý cũng như thời hạn của giấy phép (ví dụ: cảnh báo tự động khi Phiếu công bố mỹ phẩm 5 năm sắp hết hạn).



### Phân phối và Bán lẻ (Outbound & POS)

* Nếu xuất buôn cho các đại lý con, hệ thống gộp các hộp sản phẩm vào thùng, sinh Mã truy vết vận chuyển và tạo đơn hàng B2B để lưu vết.


* Tại quầy POS bán lẻ, Thu ngân dùng súng quét mã QR trên vỏ hộp; hệ thống tự động trừ đúng tồn kho của lô đó và lưu thông tin người mua.


* Thuật toán FEFO luôn can thiệp vào các lệnh xuất kho nhằm cảnh báo và ưu tiên đẩy các lô hàng cận date ra trước.

### Truy vết và Xử lý sự cố (Traceback & Recall)

* Người tiêu dùng quét mã QR tại nhà để xác thực hàng chính hãng, đồng thời hệ thống ghi nhận lịch sử và tọa độ quét nhằm theo dõi luồng phân phối.


* Nếu có phản ánh dị ứng sản phẩm, hệ thống truy xuất ngược từ mã QR ra lô nhập, nhà cung cấp, và tự động trích xuất Báo cáo tác dụng bất lợi để nộp Cục Quản lý dược đúng hạn 07 ngày.


* Khi có lệnh đình chỉ lưu hành, hệ thống truy vết xuôi để tìm toàn bộ đại lý và khách lẻ đang giữ lô lỗi, lập tức khóa mã trên toàn hệ thống không cho phép bán tiếp.



Với luồng logic vận hành khép kín này, bạn dự định sẽ triển khai cơ sở hạ tầng máy chủ (Cloud/VPS) như thế nào để đảm bảo tốc độ truy vấn luôn mượt mà khi dữ liệu tem QR sinh ra ngày càng lớn?