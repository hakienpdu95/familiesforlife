# Bổ sung 28 danh mục còn thiếu vào CategoryFoundationSeeder

## Context

Route `dashboard/core-idea-extractor/category-foundations` hiển thị **44 danh mục** (những `PostCategory` được menu header thật tham chiếu — xác nhận qua `ListCategoryFoundationsAction::pruneToMenuReferenced()` và đối chiếu trực tiếp DB). File `Modules/CoreIdeaExtractor/database/seeders/CategoryFoundationSeeder.php` hiện mới có **16/44** định nghĩa (nhóm "Mang thai" 5/5, "Trẻ sơ sinh 0-3 tháng" 6/6, "Trẻ nhỏ 3-12 tháng" 4/5, "Trẻ mầm non 3-6 tuổi" 1/5) — chính tác giả seeder đã để lại TODO ở dòng 586-587 xác nhận còn thiếu. Bảng `cie_category_foundations` hiện **rỗng hoàn toàn** trên DB dev (seeder chưa từng chạy).

Nghiên cứu cho thấy 13 danh mục thuộc 3 nhóm "Gia đình", "Chọn trường cho con", "Sản phẩm & Dịch vụ" **hoàn toàn chưa có định hướng biên tập nào** trong code/spec/docs (chỉ có `name`, `description` = NULL) — trừ 2 ngoại lệ có bài demo thật (Du lịch gia đình, Tài chính gia đình, mỗi category 5 bài) dùng để neo đúng hướng nội dung thực tế đã triển khai. Người dùng đã xác nhận hướng cho 3 danh mục nhạy cảm nhất:
- **Đánh giá sản phẩm**: so sánh trung lập, KHÔNG khuyến nghị mua cụ thể (dù có hạ tầng Product CTA Box/affiliate thật).
- **Video**: category cắt ngang theo ĐỊNH DẠNG (ý tưởng video hóa mọi chủ đề nuôi dạy con), không phải 1 chủ đề hẹp.
- **Giải thưởng nổi bật**: bài "Top/Best-of" do biên tập tự thực hiện, không phải giải thưởng chính thức do site trao.

Mục tiêu: viết đủ 28 định nghĩa còn thiếu, cùng cấu trúc 8 field (`core_focus`, `unique_angle`, `content_goals`, `pain_points`, `rejected_ideas`, `audience`, `constraints`, `style_sample`) và cùng độ sâu/tông giọng như 16 định nghĩa mẫu đã có (viết cho cha mẹ Việt cụ thể, có tình huống thật, có ranh giới chống chồng lấn giữa các danh mục, không thuyết giảng/hù dọa).

## Đối tượng mục tiêu & trọng tâm nội dung từng danh mục

### Nhóm 1 — Trẻ nhỏ (3-12 tháng): bổ sung 1 danh mục còn thiếu

| Danh mục (slug, parent) | Đối tượng mục tiêu | Trọng tâm & ranh giới |
|---|---|---|
| Bệnh thường gặp (`benh-thuong-gap-2`, cha: `tre-nho-3-12-thang`) | Cha mẹ có con 3-12 tháng, giai đoạn con bắt đầu ăn dặm + hết miễn dịch từ mẹ nên ốm vặt tăng mạnh, đi nhà trẻ sớm gặp lây chéo. | Sốt mọc răng, tiêu chảy/táo bón khi đổi sang ăn dặm, viêm hô hấp/tay chân miệng khi bắt đầu đi trẻ, phân biệt ốm do mọc răng vs bệnh thật, tiêm chủng mốc 6-12 tháng. Ranh giới: KHÔNG lấn "Bệnh thường gặp" ở nhóm sơ sinh (bệnh sơ sinh đặc thù như vàng da) hay ở nhóm tập đi (chấn thương do biết đi, ngộ độc do bốc ăn).

### Nhóm 2 — Trẻ tập đi (1-3 tuổi): 5 danh mục (1 pillar + 4 con), TOÀN BỘ giai đoạn còn trống

| Danh mục | Đối tượng mục tiêu | Trọng tâm & ranh giới |
|---|---|---|
| Trẻ tập đi 1-3 tuổi — pillar (`tre-tap-di-1-3-tuoi`, gốc) | Cha mẹ có con vừa biết đi, chuẩn bị tâm thế bước vào giai đoạn "khủng hoảng tuổi lên 2" nổi tiếng khó. | Bài tổng quan giai đoạn: cột mốc lớn (biết đi, biết nói câu ngắn, cai bỉm/bỉm ban ngày, cai ti/bú bình), thay đổi tâm sinh lý chính (bùng nổ ý thức "cái tôi", ăn vạ đỉnh điểm), lộ trình 24 tháng làm bản đồ dẫn sang 4 danh mục con. |
| Chăm sóc & nuôi dạy (`cham-soc-nuoi-day`, cha: `tre-tap-di-1-3-tuoi`) | Cha mẹ 1 con đầu 1-3 tuổi, đi làm, đối mặt "tantrum" lần đầu chưa có kinh nghiệm xử lý. | Ăn vạ/khủng hoảng tuổi lên 2, cai bỉm (toilet training), cai ti giả/ti mẹ, tập nói không cắn/đánh bạn, đi nhà trẻ lần đầu (xa cách lo âu), an toàn trong nhà khi con biết trèo/mở được mọi thứ. Ranh giới: KHÔNG lấn hành vi 3-6 tuổi phức tạp hơn (nói dối, ghen tị) đã thuộc "Chăm sóc & nuôi dạy" mầm non; không lấn cột mốc phát triển (thuộc Phát triển của trẻ). |
| Phát triển của trẻ (`phat-trien-cua-tre-3`, cha: `tre-tap-di-1-3-tuoi`) | Cha mẹ theo dõi mốc phát triển, lo lắng khi con "chậm" so với bảng chuẩn hay con hàng xóm. | Mốc vận động (đi vững, chạy, leo cầu thang), ngôn ngữ (bùng nổ vốn từ 18-24 tháng, ghép câu 2-3 từ), nhận thức/chơi tưởng tượng, dấu hiệu chậm phát triển cần tầm soát tự kỷ sớm (giai đoạn vàng 18-24 tháng). Ranh giới: không viết hành vi/kỷ luật (thuộc Chăm sóc & nuôi dạy), không viết bệnh lý (thuộc Bệnh thường gặp). |
| Dinh dưỡng cho trẻ (`dinh-duong-cho-tre`, cha: `tre-tap-di-1-3-tuoi`) | Cha mẹ vật lộn với "biếng ăn sinh lý" tuổi lên 2 — giai đoạn biếng ăn phổ biến nhất đời trẻ. | Biếng ăn sinh lý sau ăn dặm, chuyển từ cháo/bột sang cơm nát - ăn thô, cai sữa hoàn toàn, thực đơn gia đình cho trẻ ăn cùng mâm cơm, xử lý kén ăn/chọn món. Ranh giới: không viết lại kỹ thuật ăn dặm khởi đầu (thuộc nhóm "Ăn dặm & dinh dưỡng" của Trẻ nhỏ 3-12 tháng), chỉ viết giai đoạn SAU ăn dặm — ăn thô & ăn cùng gia đình. |
| Bệnh thường gặp (`benh-thuong-gap-3`, cha: `tre-tap-di-1-3-tuoi`) | Cha mẹ có con mới đi nhà trẻ, tiếp xúc nhiều trẻ khác nên bệnh lây lan, đồng thời con hiếu động dễ tai nạn. | Tay chân miệng/sốt virus theo mùa dịch nhà trẻ, viêm tai giữa, dị ứng thực phẩm mới phát hiện khi ăn thô đa dạng, xử trí hóc/ngộ độc/té ngã tại nhà (an toàn số 1 giai đoạn biết đi), lịch tiêm nhắc 18-24 tháng. Ranh giới: không trùng bệnh sơ sinh đặc thù, không trùng vấn đề ăn uống (thuộc Dinh dưỡng cho trẻ). |

### Nhóm 3 — Trẻ mầm non (3-6 tuổi): 4 danh mục còn thiếu (đã có sẵn "Chăm sóc & nuôi dạy")

| Danh mục | Đối tượng mục tiêu | Trọng tâm & ranh giới |
|---|---|---|
| Trẻ mầm non 3-6 tuổi — pillar (`tre-mam-non-3-6-tuoi`, gốc) | Cha mẹ có con vừa qua giai đoạn tập đi, chuẩn bị 3 năm mầm non và tiền lớp 1. | Bài tổng quan: đặc điểm tâm sinh lý 3-6 tuổi, các cột mốc lớn theo năm (3 tuổi mẫu giáo bé → 6 tuổi tiền lớp 1), bản đồ dẫn sang 4 danh mục con + sang "Chọn trường cho con". |
| Phát triển của trẻ (`phat-trien-cua-tre-4`, cha: `tre-mam-non-3-6-tuoi`) | Cha mẹ theo dõi phát triển nhận thức/vận động/ngôn ngữ chuẩn bị vào lớp 1. | Vận động tinh (cầm bút, cắt kéo), ngôn ngữ (nói rõ, kể chuyện mạch lạc), nhận thức (đếm số, nhận mặt chữ), kỹ năng xã hội (chơi nhóm, chia sẻ), sàng lọc chậm nói/tăng động giảm chú ý ở tuổi này. |
| Dinh dưỡng cho trẻ (`dinh-duong-cho-tre-2`, cha: `tre-mam-non-3-6-tuoi`) | Cha mẹ có con ăn bán trú ở trường, không kiểm soát trực tiếp bữa ăn như trước. | Cân đối dinh dưỡng khi ăn bán trú (không biết con ăn gì ở lớp), suy dinh dưỡng thấp còi/thừa cân tuổi mầm non, xây thói quen ăn uống lành mạnh lâu dài (ít đồ ngọt/nước ngọt), dạy con tự xúc ăn văn minh. |
| Bệnh thường gặp (`benh-thuong-gap-4`, cha: `tre-mam-non-3-6-tuoi`) | Cha mẹ có con đi học bán trú cả ngày, khó theo dõi sát khi con ốm ở trường. | Bệnh hô hấp/tay chân miệng lây trong lớp đông, sâu răng (giai đoạn răng sữa), cận thị học đường sớm, dị ứng/hen theo mùa, khi nào nên cho nghỉ học vì bệnh truyền nhiễm. |

### Nhóm 4 — Trẻ tiểu học (6-12 tuổi): 5 danh mục, TOÀN BỘ giai đoạn còn trống

| Danh mục | Đối tượng mục tiêu | Trọng tâm & ranh giới |
|---|---|---|
| Trẻ tiểu học 6-12 tuổi — pillar (`tre-tieu-hoc-6-12-tuoi`, gốc) | Cha mẹ có con vừa vào lớp 1, chuyển từ "chăm sóc" sang "đồng hành học tập". | Bài tổng quan: đặc điểm 6-12 tuổi, hành trình 5 năm tiểu học, bản đồ dẫn sang 4 danh mục con. |
| Chăm sóc & nuôi dạy (`cham-soc-nuoi-day-3`, cha: `tre-tieu-hoc-6-12-tuoi`) | Cha mẹ đối mặt áp lực học tập, quản lý thời gian, mạng xã hội/thiết bị ở tuổi con bắt đầu có bạn bè, ý kiến riêng. | Rèn tự học/tự giác làm bài tập, quản lý thời gian màn hình - mạng xã hội/game, dạy quản lý tiền tiêu vặt, xử lý bắt nạt học đường, đồng hành thi cử mà không tạo áp lực, nói chuyện giới tính tuổi dậy thì sớm. Ranh giới: không lấn hành vi tuổi nhỏ hơn (ăn vạ), không lấn chọn trường (thuộc nhóm Chọn trường). |
| Phát triển của trẻ (`phat-trien-cua-tre-5`, cha: `tre-tieu-hoc-6-12-tuoi`) | Cha mẹ theo dõi phát triển học tập, cảm xúc - xã hội, dấu hiệu dậy thì sớm. | Phát triển nhận thức/tư duy logic theo lớp, kỹ năng đọc-viết-tính toán, phát triển cảm xúc - xã hội (tự tin, đồng cảm), dấu hiệu dậy thì sớm (8-9 tuổi ở bé gái), tăng động giảm chú ý/khó khăn học tập cần can thiệp. |
| Dinh dưỡng cho trẻ (`dinh-duong-cho-tre-3`, cha: `tre-tieu-hoc-6-12-tuoi`) | Cha mẹ lo con ăn ở trường/căng tin, đồng thời lo thừa cân - béo phì học đường đang tăng. | Bữa sáng trước giờ học, suất ăn bán trú, kiểm soát đồ ăn vặt - nước ngọt ở cổng trường, phòng ngừa thừa cân/thiếu vi chất tuổi dậy thì sớm. |
| Bệnh thường gặp (`benh-thuong-gap-5`, cha: `tre-tieu-hoc-6-12-tuoi`) | Cha mẹ có con tự lập hơn ở trường, khó giám sát sát sao khi ốm. | Cận thị học đường (tăng mạnh giai đoạn này), cong vẹo cột sống do cặp sách/ngồi sai tư thế, béo phì, sức khỏe tâm lý (lo âu học tập, stress thi cử) — điểm khác biệt: đây là danh mục ĐẦU TIÊN đưa sức khỏe tâm lý trẻ vào "Bệnh thường gặp" vì tuổi này bắt đầu có áp lực học tập thật. |

### Nhóm 5 — Gia đình: 6 danh mục, TOÀN BỘ còn trống (2/6 đã có bài demo thật để neo hướng)

| Danh mục | Đối tượng mục tiêu | Trọng tâm & ranh giới |
|---|---|---|
| Sức khỏe cha mẹ (`suc-khoe-cha-me`, gốc) | Cha mẹ 28-45 tuổi quen "hy sinh sức khỏe bản thân vì con", chỉ để ý khi đã kiệt sức/burn-out. | Sức khỏe thể chất người lớn thường bị bỏ quên khi có con nhỏ (đau lưng bế con, thiếu ngủ mãn tính), sức khỏe tinh thần cha mẹ (burn-out, trầm cảm sau sinh ở CẢ bố lẫn mẹ, áp lực làm cha mẹ hoàn hảo), khám sức khỏe định kỳ hay bị trì hoãn. Ranh giới: không trùng Sức khỏe mẹ bầu (chỉ thai kỳ); đây là sức khỏe cha mẹ SAU khi đã có con, gồm cả người bố. |
| Hôn nhân (`hon-nhan`, gốc) | Vợ chồng có con nhỏ, mối quan hệ bị xao nhãng vì dồn hết thời gian/năng lượng cho con. | Duy trì kết nối vợ chồng sau khi có con ("date night" thực tế với ngân sách/thời gian eo hẹp), bất đồng quan điểm nuôi dạy con giữa hai vợ chồng, chia sẻ việc nhà - chăm con công bằng, đời sống vợ chồng thay đổi sau sinh. Ranh giới: không viết mâu thuẫn với ông bà (thuộc các bài nuôi dạy con theo tuổi), tập trung đúng mối quan hệ hai vợ chồng. |
| Du lịch gia đình (`du-lich-gia-dinh`, gốc — ĐÃ có 5 bài demo thật) | Gia đình có con nhỏ muốn đi du lịch nhưng ngại vì con quấy/vướng đồ đạc. Neo theo 5 bài đã có: du lịch biển cùng trẻ nhỏ, đồ dùng cần mang, điểm đến gần Hà Nội, mẹo trên máy bay, ngân sách chuyến đi. | Chọn điểm đến/thời điểm phù hợp theo độ tuổi con, chuẩn bị đồ đi kèm, mẹo giữ con không quấy khi di chuyển dài (máy bay/ô tô), ngân sách du lịch gia đình. Giữ đúng tông đã thiết lập bởi bài demo — thực dụng, có checklist. |
| Nhà cửa & đời sống (`nha-cua-doi-song`, gốc) | Gia đình trẻ có con nhỏ, cần tổ chức không gian sống an toàn - gọn gàng trong điều kiện nhà chung cư/nhà phố chật. | An toàn trong nhà theo độ tuổi con (chống trẻ leo trèo, khóa tủ thuốc/ổ điện), tổ chức góc chơi/học cho con trong nhà nhỏ, việc nhà hiệu quả khi có con mọn, chọn đồ nội thất/thiết bị gia đình phù hợp có con nhỏ. |
| Tài chính gia đình (`tai-chinh-gia-dinh`, gốc — ĐÃ có 5 bài demo thật) | Vợ chồng trẻ mới có con, chi phí tăng vọt, cần hoạch định tài chính dài hạn. Neo theo 5 bài đã có: quỹ dự phòng, dạy con quản lý tiền, ngân sách chi tiêu gia đình 4 người, bảo hiểm nhân thọ, tiết kiệm học đại học cho con. | Ngân sách chi tiêu khi có con, quỹ dự phòng - bảo hiểm, tiết kiệm dài hạn cho việc học của con, dạy con về tiền từ nhỏ. Giữ đúng tông đã có — thực dụng, có con số/mốc cụ thể, không tư vấn đầu tư rủi ro cao. |
| Quyền lợi & pháp lý (`quyen-loi-phap-ly`, gốc) | Cha mẹ đi làm cần biết quyền lợi luật định (thai sản, nghỉ ốm con) nhưng ngại hỏi công ty vì sợ ảnh hưởng công việc. | Chế độ thai sản - nghỉ chăm con ốm theo Luật BHXH/Lao động, đăng ký khai sinh - giấy tờ cho con, quyền nuôi con khi ly hôn, chính sách hỗ trợ trẻ em của nhà nước. Đây là danh mục DUY NHẤT viết về luật — phải dẫn đúng điều luật, không tư vấn pháp lý cá nhân hóa (chỉ phổ biến quy định chung, khuyên gặp luật sư/BHXH khi cần cụ thể). |

### Nhóm 6 — Chọn trường cho con: 4 danh mục, TOÀN BỘ còn trống

| Danh mục | Đối tượng mục tiêu | Trọng tâm & ranh giới |
|---|---|---|
| Trường mầm non & tiểu học (`truong-mam-non-tieu-hoc`, gốc) | Cha mẹ đứng trước quyết định chọn trường lớn nhất trong 6 năm đầu đời con — công lập/tư thục/quốc tế, học phí chênh lệch hàng chục lần. | Tiêu chí chọn trường theo túi tiền - vị trí - triết lý giáo dục, so sánh công lập vs tư thục vs quốc tế (ưu/nhược thật, không PR trường nào), quy trình tuyển sinh - hồ sơ - thời điểm nộp, dấu hiệu trường tốt khi đi tham quan thực tế. Ranh giới: không đánh giá 1 trường cụ thể theo tên (tránh PR/bôi nhọ), chỉ viết khung tiêu chí + quy trình chung. |
| Trường năng khiếu & kỹ năng (`truong-nang-khieu-ky-nang`, gốc) | Cha mẹ muốn cho con học thêm ngoài giờ (nghệ thuật, thể thao, ngoại ngữ) nhưng phân vân chọn đúng năng khiếu thật của con hay chạy theo phong trào. | Nhận biết năng khiếu thật của con ở từng độ tuổi, tiêu chí chọn lớp năng khiếu (bơi, đàn, vẽ, võ, tiếng Anh...) tránh "học cho có phong trào", cân bằng giữa học năng khiếu và thời gian nghỉ ngơi/chơi tự do của trẻ. |
| Trung tâm học tập (`trung-tam-hoc-tap`, gốc) | Cha mẹ có con tiểu học/THCS cần học thêm (Toán, tiếng Anh, luyện chữ) do không đủ thời gian kèm ở nhà. | Tiêu chí chọn trung tâm/gia sư uy tín, khi nào con thật sự cần học thêm vs áp lực thành tích ảo, cân đối lịch học thêm không quá tải, chi phí học thêm hợp lý theo thu nhập gia đình. |
| Giáo dục tại nhà (`giao-duc-tai-nha`, gốc) | Nhóm nhỏ, đặc thù: cha mẹ cân nhắc/đang homeschool tại Việt Nam — nơi homeschool chưa phổ biến và có vướng mắc pháp lý về học bạ/thi cử. | Homeschool là gì, có hợp pháp ở Việt Nam không (điều kiện thực tế, vướng mắc bằng cấp/thi), ai phù hợp - ai không, cách xây chương trình học tại nhà, cộng đồng hỗ trợ homeschool VN. Constraint riêng: phải trung thực về rào cản pháp lý/xã hội thực tế tại VN, không cổ vũ một chiều. |

### Nhóm 7 — Sản phẩm & Dịch vụ: 3 danh mục, theo 3 quyết định đã chốt với người dùng

| Danh mục | Đối tượng mục tiêu | Trọng tâm & ranh giới |
|---|---|---|
| Đánh giá sản phẩm (`danh-gia-san-pham`, gốc) | Cha mẹ đang phân vân giữa nhiều lựa chọn sản phẩm mẹ & bé (bỉm, sữa, xe đẩy, đồ chơi giáo dục...) trước khi mua. | So sánh KHÁCH QUAN theo tiêu chí thực dụng (giá/công năng/độ an toàn/đối tượng phù hợp), trình bày ưu-nhược trung lập, để người đọc tự quyết — KHÔNG chốt "nên mua sản phẩm X". Có thể tận dụng Product CTA Box (đã có hạ tầng catalog giá/ảnh/link) để hiển thị lựa chọn, nhưng phần lời văn giữ vai trò tư vấn trung lập, không quảng cáo. |
| Video (`video`, gốc) | Không phải 1 nhóm độc giả theo tuổi con — mà là biên tập viên/đội content cần Ý TƯỞNG kịch bản ngắn, cắt ngang mọi chủ đề khác. | core_focus tập trung vào ĐỊNH DẠNG: loại nội dung nào hợp video ngắn (hướng dẫn thao tác nhanh: quấn khăn, sơ cứu hóc dị vật; "một ngày của mẹ bỉm"; review sản phẩm dạng video), khác biệt với bài viết dài. Ranh giới: không cạnh tranh nội dung với các danh mục chủ đề khác — chỉ đề xuất GÓC VIDEO HÓA của các chủ đề đó, không tự tạo chủ đề mới. |
| Giải thưởng nổi bật (`giai-thuong-noi-bat`, gốc) | Độc giả tìm "gợi ý nhanh, đã được chọn lọc sẵn" thay vì tự so sánh dài dòng — thường ở giai đoạn quyết định mua/chọn gấp. | Bài dạng "Top 5/10..." do ban biên tập tự tổng hợp/xếp hạng (sản phẩm, trường học, dịch vụ theo mùa/dịp), minh bạch tiêu chí xếp hạng ngay đầu bài, ghi rõ đây là gợi ý biên tập chứ không phải giải thưởng chính thức có hội đồng chấm. Dẫn traffic về Đánh giá sản phẩm / Trường mầm non & tiểu học. |

## Cách triển khai

1. **Viết nội dung**: Dùng 7 agent con (fork, kế thừa toàn bộ ngữ cảnh/nghiên cứu/style mẫu đã đọc) chạy song song theo đúng 7 nhóm trên — mỗi agent viết đủ 8 field (`core_focus, unique_angle, content_goals, pain_points, rejected_ideas, audience, constraints, style_sample`) dạng heredoc PHP, bám sát độ dài/tông giọng/cấu trúc "core_focus nêu phạm vi + câu KHÔNG lấn sân cuối" như 16 mẫu đã có, dựa trên đối tượng/trọng tâm đã chốt ở bảng trên.
2. **Lắp ráp & rà soát**: Tôi tổng hợp 28 phần tử vào `CategoryFoundationSeeder::DEFINITIONS` (thay thế comment TODO dòng 586-587), kiểm tra chéo ranh giới chồng lấn giữa các danh mục liền kề (đặc biệt 3 bộ "Chăm sóc & nuôi dạy / Phát triển của trẻ / Dinh dưỡng cho trẻ / Bệnh thường gặp" lặp lại ở 3 giai đoạn tuổi phải phân biệt rõ theo tuổi), sửa văn phong nếu có đoạn nào lệch tông so với 16 mẫu gốc.
3. **Validate cú pháp**: chạy `php -l` trên file sau khi sửa.
4. **Seed thử trên DB dev**: chạy seeder (`php artisan db:seed --class="Modules\CoreIdeaExtractor\Database\Seeders\CategoryFoundationSeeder"` hoặc qua `module:seed`) để xác nhận cả 44 category đều khớp `(slug, parent_slug)` thật trong DB — không bị cảnh báo "category not found" — rồi kiểm tra nhanh bằng tinker rằng `cie_category_foundations` có đủ bản ghi tương ứng.

## Không nằm trong phạm vi

4 category "mồ côi" thuộc taxonomy cũ trước khi đổi cấu trúc header (`giao-duc`, `hon-nhan-gia-dinh`, `dinh-duong`, `ky-nang-song`) — không còn được menu header tham chiếu, nên không thuộc "danh mục menu còn thiếu" mà yêu cầu đề cập.
