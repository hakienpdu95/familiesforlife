<?php

namespace Modules\AIVideoStudioTemplate\Features\FormulaAdvisor\Support;

use Illuminate\Support\Str;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §13 (v1.23) — dữ liệu tra cứu tĩnh chuyển
 * thể từ spec/ma-tran-cong-thuc-kich-ban.md (Phần 3-8), ngách Mẹ & Bé. Mỗi entry map thẳng sang
 * `AiVideoStudioProject::VIDEO_FORMULAS` đã có (KHÔNG định nghĩa formula mới) — công thức PSC/BAB/
 * HVC/ABCD/Testimonial/Onboarding của nguồn khớp 1-1 với `psa`/`bab`/`hook_value_cta`/`abcd`/
 * `testimonial_5part`/`onboarding_5part` đã tồn tại từ v1.16/v1.20.
 *
 * Cấp độ nhân dạng khán giả (`personas`, mã P1-P13) giữ NGUYÊN dạng mã thô theo nguồn — tài liệu
 * "Bộ Chân dung Người Review" mô tả đầy đủ từng mã KHÔNG có trong repo này, nên module chỉ hiển thị
 * mã kèm chú thích "cần tài liệu chân dung đầy đủ để diễn giải", KHÔNG tự suy diễn nội dung persona.
 *
 * Đây là dữ liệu THAM KHẢO/GỢI Ý — biên tập viên vẫn có toàn quyền chọn công thức khác khi tạo
 * Project (§2.1), catalog này không khoá cứng lựa chọn nào.
 */
class ProductFormulaCatalog
{
    /**
     * Mỗi entry: tier (S/A/B/C/D), name, primary (khoá VIDEO_FORMULAS hoặc null nếu đa phiên bản),
     * secondary (mảng khoá), personas (chuỗi mã thô), note (Logic chọn/Ghi chú then chốt),
     * warning (chuỗi ⚠️/🔴 rút ra riêng nếu có), sample_script (nullable, 4 kịch bản mẫu Phần 7).
     */
    public const PRODUCTS = [
        // ── TIER S (spec/ma-tran-cong-thuc-kich-ban.md Phần 3) ──────────────────────────────
        'nuoc_giat_xa_em_be' => [
            'tier' => 'S',
            'name' => 'Nước giặt & xả em bé',
            'primary' => 'bab',
            'secondary' => ['hook_value_cta'],
            'personas' => 'P5 → P6',
            'note' => 'Vết ố sữa/ố phân su trên đồ trắng là before/after hoàn hảo — trên VẢI, không trên da. Bản HVC dành cho P6 giải thích SLS/MIT/CMIT.',
            'warning' => null,
        ],
        'nuoc_rua_binh_sua' => [
            'tier' => 'S',
            'name' => 'Nước rửa bình sữa',
            'primary' => 'psa',
            'secondary' => ['onboarding_5part'],
            'personas' => 'P5 → P8',
            'note' => 'Vấn đề đã rõ: cặn sữa bám đáy, mùi hôi ám. Bản Onboarding dạy quy trình rửa–tráng–tiệt trùng đúng.',
            'warning' => null,
        ],
        'bim_ta' => [
            'tier' => 'S',
            'name' => 'Bỉm / tã',
            'primary' => 'psa',
            'secondary' => ['bab', 'testimonial_5part'],
            'personas' => 'P2 → P5 → P6',
            'note' => 'PSC cho hăm & tràn đêm. BAB cho test thấm hút (đổ nước có màu). Testimonial dành riêng cho bỉm cao cấp >400k.',
            'warning' => null,
            'sample_script' => [
                'title' => 'PSC · Bỉm sơ sinh · Chân dung P2 · 30 giây',
                'body' => <<<'MD'
**[PROBLEM — 0:00–0:08]** *(Quay thật, 3h sáng, ánh đèn ngủ)*
"3 giờ sáng, lần thứ hai thay ga giường. Bỉm tràn từ lưng xuống. Đây là đêm thứ tư liên tiếp."

**[SOLUTION — 0:08–0:22]**
"Mình đổi sang size lớn hơn một cỡ và chọn loại có chun lưng cao. Không phải bỉm dở — là mình chọn sai size. Bé 4,2kg mà mình vẫn dùng NB." *(Cận cảnh chun lưng, đo vòng đùi)* "Ba đêm nay không tràn lần nào."

**[CTA — 0:22–0:30]**
"Mình để bảng cân nặng ↔ size ở dưới. Các mẹ check lại size trước khi đổi hãng nhé — có khi không cần đổi hãng đâu."

*Vì sao hiệu quả: vấn đề có thật và cụ thể, giải pháp KHÔNG PHẢI là mua đắt hơn mà là chọn đúng — tạo tin cậy và ngược đời so với video bán hàng thông thường. CTA hướng vào kiến thức trước, link sau.*
MD,
            ],
        ],
        'gac_ro_luoi' => [
            'tier' => 'S',
            'name' => 'Gạc rơ lưỡi',
            'primary' => 'onboarding_5part',
            'secondary' => ['psa'],
            'personas' => 'P2 + P9',
            'note' => 'Rơ sai gây nôn trớ và tổn thương niêm mạc → phần Mistakes là lý do tồn tại của video này.',
            'warning' => null,
            'sample_script' => [
                'title' => 'Onboarding · Gạc rơ lưỡi · Chân dung P9 · 120 giây',
                'body' => <<<'MD'
**[WELCOME — 0:00–0:12]** "Mình là điều dưỡng nhi. Video này không bán gạc rơ lưỡi — nó nói về cách rơ đúng, vì mình gặp khá nhiều ca bé bị trớ và xước lợi do rơ sai."

**[FIRST VALUE — 0:12–0:30]** "Nguyên tắc quan trọng nhất: **rơ trước cữ bú, không rơ sau.** Rơ khi bụng đầy là lý do phổ biến nhất khiến bé nôn trớ."

**[STEPS — 0:30–1:15]** *(Quay tay thật, từng bước)* "1. Rửa tay, đeo gạc vào ngón út. 2. Thấm nước muối sinh lý, không thấm sũng. 3. Lau mặt trong má trước. 4. Lau lợi. 5. Lưỡi lau SAU CÙNG, từ trong ra ngoài, một chiều."

**[MISTAKES — 1:15–1:50]** ⭐ *phần giá trị nhất* "Bốn lỗi mình gặp nhiều nhất: đưa ngón tay quá sâu gây nôn khan · chà mạnh làm xước lợi · dùng mật ong (TUYỆT ĐỐI không cho bé dưới 1 tuổi) · rơ ngay sau khi bú. Nếu mảng trắng dày, bám chặt — có thể là nấm miệng, cần đi khám, không tự xử lý tại nhà."

**[SUPPORT CTA — 1:50–2:00]** "Loại gạc mình đang dùng ở dưới. Mẹ nào cần giải thích thêm thì để lại bình luận."

*Vì sao hiệu quả: mở đầu "video này không bán" hạ hoàn toàn hàng rào phòng thủ. Phần Mistakes vừa an toàn thật vừa dễ lưu/chia sẻ. Câu về nấm miệng thể hiện ranh giới nghề nghiệp — tăng tin cậy, không mất đơn.*
MD,
            ],
        ],
        'bong_tam_2_dau' => [
            'tier' => 'S',
            'name' => 'Bông tăm 2 đầu',
            'primary' => 'hook_value_cta',
            'secondary' => [],
            'personas' => 'P9 → P2',
            'note' => 'Khán giả CHƯA Ý THỨC rằng không được ngoáy sâu vào tai bé. Unaware điển hình → PSC sẽ vô hiệu.',
            'warning' => null,
        ],
        'khan_kho_bong_y_te' => [
            'tier' => 'S',
            'name' => 'Khăn khô / bông y tế',
            'primary' => 'bab',
            'secondary' => ['hook_value_cta'],
            'personas' => 'P5',
            'note' => 'BAB so sánh trực quan độ dai & chi phí. HVC cho insight "bông y tế thay khăn khô" — rẻ hơn nhiều lần.',
            'warning' => null,
        ],

        // ── TIER A (Phần 4) ──────────────────────────────────────────────────────────────────
        'khan_sua_soi_tre' => [
            'tier' => 'A',
            'name' => 'Khăn sữa sợi tre',
            'primary' => 'hook_value_cta',
            'secondary' => ['bab'],
            'personas' => 'P1 → P6',
            'note' => '"Vì sao đừng mua khăn xô và khăn họa tiết" — kiến thức, không phải nỗi đau.',
            'warning' => null,
        ],
        'sua_tam_goi_2in1' => [
            'tier' => 'A',
            'name' => 'Sữa tắm gội 2in1 ⭐',
            'primary' => null,
            'secondary' => ['psa', 'hook_value_cta', 'testimonial_5part'],
            'personas' => 'P5 / P6 / P7',
            'note' => 'Món DUY NHẤT nên tách 3 phiên bản: P5 = PSC (rát da, giá rẻ) · P6 = HVC (bảng thành phần) · P7 = Testimonial.',
            'warning' => '⚠️ Testimonial (P7): phần Result chỉ nói trải nghiệm cá nhân, không cam kết hết hẳn tình trạng da.',
            'multi_version' => true,
        ],
        'kem_duong_am' => [
            'tier' => 'A',
            'name' => 'Kem dưỡng ẩm',
            'primary' => 'testimonial_5part',
            'secondary' => ['hook_value_cta'],
            'personas' => 'P7',
            'note' => 'Phần Result chỉ nói trải nghiệm cá nhân + khuyến nghị khám da liễu nhi.',
            'warning' => '🔴 Cấm tuyệt đối ảnh da bé before/after — KHÔNG dùng BAB cho sản phẩm này.',
            'forbid_formulas' => ['bab'],
        ],
        'khan_uot' => [
            'tier' => 'A',
            'name' => 'Khăn ướt',
            'primary' => 'bab',
            'secondary' => ['psa'],
            'personas' => 'P2 → P5',
            'note' => 'Test kéo giãn, test độ ẩm, test bục — trực quan nhất trong cả danh mục.',
            'warning' => null,
        ],
        'xit_khang_khuan' => [
            'tier' => 'A',
            'name' => 'Xịt kháng khuẩn',
            'primary' => 'hook_value_cta',
            'secondary' => ['psa'],
            'personas' => 'P9',
            'note' => 'Cần giải thích KHI NÀO cần dùng. Đưa nhược điểm (mùi hắc) vào phần Value để tăng độ tin.',
            'warning' => null,
        ],
        'tui_binh_tru_sua' => [
            'tier' => 'A',
            'name' => 'Túi & bình trữ sữa',
            'primary' => 'onboarding_5part',
            'secondary' => ['psa'],
            'personas' => 'P8',
            'note' => 'Mistakes cực giá trị: rã đông bằng nước nóng, trữ quá hạn, không ghi ngày. PSC phụ cho "túi rẻ bị bục trong tủ đông".',
            'warning' => null,
        ],
        'co_rua_binh_2_dau' => [
            'tier' => 'A',
            'name' => 'Cọ rửa bình 2 đầu',
            'primary' => 'psa',
            'secondary' => [],
            'personas' => 'P5',
            'note' => 'Ngắn 15s: không rửa được đáy bình và cổ núm.',
            'warning' => null,
        ],
        'kem_ngua_tri_ham' => [
            'tier' => 'A',
            'name' => 'Kem ngừa & trị hăm',
            'primary' => 'hook_value_cta',
            'secondary' => ['psa'],
            'personas' => 'P2 + P7',
            'note' => 'Đa số KHÔNG phân biệt được ngừa hăm vs trị hăm — đó là unaware.',
            'warning' => '⚠️ Không hình ảnh vùng hăm của bé.',
        ],
        'nuoc_muoi_sinh_ly' => [
            'tier' => 'A',
            'name' => 'Nước muối sinh lý',
            'primary' => 'onboarding_5part',
            'secondary' => ['hook_value_cta'],
            'personas' => 'P9',
            'note' => 'Nhỏ mắt/mũi sai tư thế gây sặc. Mistakes = an toàn.',
            'warning' => null,
        ],
        'khan_ham_khang_khuan' => [
            'tier' => 'A',
            'name' => 'Khăn hăm / khăn lau kháng khuẩn',
            'primary' => 'psa',
            'secondary' => [],
            'personas' => 'P2',
            'note' => 'Vấn đề rõ, giá thấp, quyết định nhanh.',
            'warning' => null,
        ],
        'may_dun_tiet_trung_3in1' => [
            'tier' => 'A',
            'name' => 'Máy đun tiệt trùng 3in1',
            'primary' => 'onboarding_5part',
            'secondary' => ['testimonial_5part', 'abcd'],
            'personas' => 'P3 → P8 → P10',
            'note' => 'Onboarding phá rào "mua về không biết dùng". Testimonial (P3, đã dùng 8 tháng) cho độ bền. ABCD nếu chạy ads.',
            'warning' => null,
        ],

        // ── TIER B/C/D theo nhóm (Phần 5) ───────────────────────────────────────────────────
        'may_hut_sua' => [
            'tier' => 'B',
            'name' => 'Máy hút sữa',
            'primary' => 'testimonial_5part',
            'secondary' => ['onboarding_5part', 'abcd'],
            'personas' => 'P8',
            'note' => 'Rào cản 1,9tr chỉ vượt được bằng câu chuyện. Onboarding cho lắp–vệ sinh–lịch hút.',
            'warning' => null,
            'sample_script' => [
                'title' => 'Testimonial 5 phần · Máy hút sữa · Chân dung P8 · 150 giây',
                'body' => <<<'MD'
**[BEFORE — 0:00–0:20]** "Tháng thứ hai sau sinh, mình quay lại công ty. Con không chịu ti trực tiếp. Mình dùng máy hút tay 300k."

**[CHALLENGE — 0:20–0:50]** "Mỗi cữ hút 40 phút. Ngày 6 cữ. Tay mình tê, đầu ti nứt hai bên. Có hôm mình ngồi trong phòng vệ sinh công ty hút, hết giờ nghỉ trưa vẫn chưa xong. Mình đã định cai sữa."

**[SOLUTION — 0:50–1:20]** "Mình đổi sang máy đôi. Giá 1,9 triệu — đúng là đắt, mình cân nhắc hai tuần. Lý do quyết định: hút đôi rút thời gian mỗi cữ xuống còn khoảng một nửa, lực hút ổn định hơn nhiều so với bơm tay."

**[RESULT — 1:20–1:50]** "Với mình, mỗi cữ giờ khoảng 20 phút. Sáu tháng rồi mình vẫn duy trì được. Đầu ti hết nứt sau khoảng hai tuần — nhưng phần này mình nghĩ do mình chỉnh phễu đúng cỡ nữa, không chỉ do máy."

**[RECOMMENDATION — 1:50–2:30]** "Mình khuyên mua nếu bạn hút từ 4 cữ/ngày trở lên. Còn nếu chỉ hút 1–2 cữ bổ sung thì KHÔNG CẦN — máy đơn rẻ hơn nhiều là đủ. Đừng mua vì thấy người ta khen."

*Vì sao hiệu quả: phần Result TỰ GIỚI HẠN (thừa nhận sản phẩm không phải nguyên nhân duy nhất). Phần Recommendation CHỦ ĐỘNG LOẠI BỚT khách. Nghịch lý là 2 điểm này làm tăng tỷ lệ chốt vì người xem thấy bạn không cố bán bằng mọi giá.*
MD,
            ],
        ],
        'nhong_chun' => [
            'tier' => 'B',
            'name' => 'Nhộng chũn',
            'primary' => 'onboarding_5part',
            'secondary' => ['testimonial_5part'],
            'personas' => 'P2',
            'note' => 'Quấn sai liên quan an toàn giấc ngủ. Mistakes là phần bắt buộc, không phải tùy chọn.',
            'warning' => '🔴 Không bao giờ rút gọn thành PSC ngắn cho món này.',
            'forbid_formulas' => ['psa'],
        ],
        'ghe_ngoi_o_to' => [
            'tier' => 'B',
            'name' => 'Ghế ngồi ô tô',
            'primary' => 'onboarding_5part',
            'secondary' => ['abcd'],
            'personas' => 'P10',
            'note' => 'Lắp sai gây nguy hiểm tính mạng.',
            'warning' => '🔴 Không bao giờ làm PSC ngắn cho món này.',
            'forbid_formulas' => ['psa'],
        ],
        'diu' => [
            'tier' => 'B',
            'name' => 'Địu',
            'primary' => 'onboarding_5part',
            'secondary' => [],
            'personas' => 'P10',
            'note' => 'Tư thế sai ảnh hưởng hông và đường thở của bé.',
            'warning' => null,
        ],
        'dung_cu_hut_mui' => [
            'tier' => 'B',
            'name' => 'Dụng cụ hút mũi',
            'primary' => 'onboarding_5part',
            'secondary' => ['psa'],
            'personas' => 'P9',
            'note' => 'Lực quá mạnh tổn thương niêm mạc.',
            'warning' => null,
        ],
        'nhiet_ke_dien_tu' => [
            'tier' => 'B',
            'name' => 'Nhiệt kế điện tử',
            'primary' => 'psa',
            'secondary' => ['onboarding_5part'],
            'personas' => 'P9',
            'note' => 'Hook có sẵn: "8 giây vs 30 giây con giãy".',
            'warning' => null,
        ],
        'nhiet_am_ke_phong' => [
            'tier' => 'B',
            'name' => 'Nhiệt ẩm kế phòng',
            'primary' => 'hook_value_cta',
            'secondary' => [],
            'personas' => 'P9',
            'note' => 'Unaware điển hình: hầu hết không biết nhiệt độ phòng bao nhiêu là phù hợp.',
            'warning' => null,
        ],
        'khan_tam_chau_tam' => [
            'tier' => 'C',
            'name' => 'Khăn tắm · Chậu tắm',
            'primary' => 'hook_value_cta',
            'secondary' => ['bab'],
            'personas' => 'P3 → P1',
            'note' => '"2/4/6 lớp mua loại nào, mấy cái là đủ".',
            'warning' => null,
        ],
        'mieng_lot_ga_chong_tham' => [
            'tier' => 'C',
            'name' => 'Miếng lót & ga chống thấm',
            'primary' => 'bab',
            'secondary' => ['psa'],
            'personas' => 'P2',
            'note' => 'Đổ nước lên tấm lót — trực quan tuyệt đối.',
            'warning' => null,
        ],
        'bao_tay_bao_chan' => [
            'tier' => 'C',
            'name' => 'Bao tay – bao chân',
            'primary' => 'psa',
            'secondary' => [],
            'personas' => 'P1',
            'note' => 'Insight thật: bo vải để lại vết hằn cổ tay bé.',
            'warning' => null,
        ],
        'keo_bam_cat_mong' => [
            'tier' => 'C',
            'name' => 'Kéo / bấm cắt móng',
            'primary' => 'hook_value_cta',
            'secondary' => [],
            'personas' => 'P4',
            'note' => '⭐ Hook phản trực giác mạnh nhất danh mục: "kéo cùn mới là kéo an toàn".',
            'warning' => null,
            'sample_script' => [
                'title' => 'HVC · Kéo cắt móng · Chân dung P4 · 50 giây',
                'body' => <<<'MD'
**[HOOK — 0:00–0:06]** "Cái kéo cắt móng tốt nhất cho trẻ sơ sinh là cái kéo CÙN. Nghe vô lý nhưng mình nói thật."

**[VALUE — 0:06–0:40]** "Móng trẻ sơ sinh mềm như giấy — không cần kéo sắc để cắt. Nhưng da đầu ngón tay bé cũng mỏng tương đương. Kéo càng sắc, chỉ cần bé giật tay một cái là đứt da." "Mình cắt cho hai đứa, đứa đầu mình mua bộ inox sắc bén 200k, cắt vào tay con hai lần. Đứa thứ hai mình dùng loại kéo đầu tròn, lưỡi cùn, 35 nghìn." "Thêm mẹo: cắt lúc bé đang ngủ say, và cắt ngang, không cắt tròn theo khóe."

**[CTA — 0:40–0:50]** "Loại 35k mình để link dưới. Mẹ nào đang định mua bộ dụng cụ đắt tiền thì cân nhắc lại nhé — chỗ đó để dành mua bỉm hợp lý hơn."

*Vì sao hiệu quả: hook phản trực giác giữ chân 3 giây đầu — yếu tố quyết định trên TikTok. Value chứa kiến thức dùng được ngay cả khi không mua. CTA đề xuất tiêu ÍT tiền hơn — xây uy tín cho toàn kênh.*
MD,
            ],
        ],
        'bo_quan_ao_mu_tat' => [
            'tier' => 'C',
            'name' => 'Bộ quần áo · Mũ · Tất',
            'primary' => 'hook_value_cta',
            'secondary' => ['abcd'],
            'personas' => 'P1',
            'note' => '"Size nào, mua mấy bộ" — chống lãng phí.',
            'warning' => null,
        ],
        'den_ngu_tieng_on_trang_goi' => [
            'tier' => 'C',
            'name' => 'Đèn ngủ · Tiếng ồn trắng · Gối đầu',
            'primary' => 'psa',
            'secondary' => ['hook_value_cta'],
            'personas' => 'P2',
            'note' => 'Gối/đệm chống trào ngược: phải nhắc khuyến cáo tư thế ngủ an toàn.',
            'warning' => '⚠️ Bắt buộc nhắc khuyến cáo tư thế ngủ an toàn nếu là gối/đệm chống trào ngược.',
        ],
        'may_loc_kk_hut_bui_camera' => [
            'tier' => 'D',
            'name' => 'Máy lọc KK · Hút bụi giường · Camera',
            'primary' => 'abcd',
            'secondary' => ['testimonial_5part'],
            'personas' => 'P10',
            'note' => 'Giá cao + thương hiệu rõ + hợp chạy ads.',
            'warning' => null,
        ],
        'ke_tu_ban_man_gap' => [
            'tier' => 'D',
            'name' => 'Kệ 3 tầng · Tủ mini · Bàn gấp · Màn gấp',
            'primary' => 'bab',
            'secondary' => [],
            'personas' => 'P13',
            'note' => 'Before/after không gian là ứng dụng thuần khiết nhất của BAB.',
            'warning' => null,
        ],
        'do_o_cu_bvs_lot_san_dich' => [
            'tier' => 'D',
            'name' => 'Đồ ở cữ · BVS · Miếng lót sản dịch',
            'primary' => 'psa',
            'secondary' => ['hook_value_cta'],
            'personas' => 'P1 → P11',
            'note' => 'Nhu cầu rõ, mua theo checklist.',
            'warning' => null,
        ],
        'dai_nit_bung_tui_chuom_nuoc_tam' => [
            'tier' => 'D',
            'name' => 'Đai nịt bụng · Túi chườm · Nước tắm gừng nghệ',
            'primary' => 'testimonial_5part',
            'secondary' => ['hook_value_cta'],
            'personas' => 'P12 → P11',
            'note' => 'Khung đúng là phục hồi và thoải mái.',
            'warning' => '⚠️ Tuyệt đối không dùng khung "lấy lại dáng".',
        ],
        'kem_boi_dau_ti' => [
            'tier' => 'D',
            'name' => 'Kem bôi đầu ti',
            'primary' => 'testimonial_5part',
            'secondary' => ['psa'],
            'personas' => 'P8',
            'note' => 'Nứt đầu ti là nỗi đau rất thật của P8.',
            'warning' => null,
        ],
        'noi_dung_5_mon_mua_phi_tien' => [
            'tier' => 'D',
            'name' => 'Nội dung "5 món mua phí tiền"',
            'primary' => 'hook_value_cta',
            'secondary' => [],
            'personas' => 'P4',
            'note' => 'Không bán món nào — xây uy tín để bán 20 món sau đó.',
            'warning' => null,
        ],
    ];

    /**
     * Nhóm bị cấm/hạn chế quảng cáo (Phần 6) — Nghị định 100/2014/NĐ-CP + quy định quảng cáo TPCN/
     * thiết bị y tế. `allowed_formula` = duy nhất được dùng (đã bỏ CTA bán); `forbidden` = danh sách
     * khoá VIDEO_FORMULAS tuyệt đối không dùng; `no_affiliate` = true nghĩa là không nên làm content
     * affiliate cho sản phẩm này (chỉ hướng dẫn "hỏi bác sĩ/dược sĩ").
     */
    public const BANNED_CATEGORIES = [
        'sua_cong_thuc_duoi_24_thang' => [
            'name' => 'Sữa công thức <24 tháng',
            'allowed_formula' => 'onboarding_5part',
            'forbidden' => ['psa', 'bab', 'testimonial_5part', 'abcd'],
            'no_affiliate' => false,
            'note' => 'Chỉ nội dung kỹ thuật: nhiệt độ nước pha, thứ tự pha, bảo quản. Không nhắc tên thương hiệu như lời khuyên.',
        ],
        'binh_sua_num_ti' => [
            'name' => 'Bình sữa · Núm ti',
            'allowed_formula' => 'onboarding_5part',
            'forbidden' => ['psa', 'bab', 'testimonial_5part', 'abcd'],
            'no_affiliate' => false,
            'note' => 'Chuyển hướng doanh thu sang phụ kiện KHÔNG bị cấm: cọ rửa, nước rửa, máy tiệt trùng, thau rửa.',
        ],
        'ti_gia' => [
            'name' => 'Ti giả',
            'allowed_formula' => 'hook_value_cta',
            'forbidden' => ['psa', 'testimonial_5part'],
            'no_affiliate' => false,
            'note' => 'Chỉ nói về thời điểm cai và dấu hiệu bé không hợp.',
        ],
        'vitamin_tpcn' => [
            'name' => 'D3K2 · Canxi · Vitamin · Ngũ cốc lợi sữa',
            'allowed_formula' => 'hook_value_cta',
            'forbidden' => ['testimonial_5part'],
            'no_affiliate' => false,
            'note' => 'Testimonial bị cấm vì phần "Result" chính là tuyên bố hiệu quả. Bắt buộc kèm câu "không phải là thuốc và không thay thế thuốc chữa bệnh".',
        ],
        'thuoc_ha_sot' => [
            'name' => 'Thuốc hạ sốt',
            'allowed_formula' => null,
            'forbidden' => ['psa', 'bab', 'hook_value_cta', 'testimonial_5part', 'abcd', 'onboarding_5part'],
            'no_affiliate' => true,
            'note' => 'Không làm affiliate. Chỉ hướng dẫn "hỏi bác sĩ/dược sĩ".',
        ],
    ];

    /** Phần 8 — ghép sai công thức × sản phẩm, hiển thị tĩnh làm tài liệu tham khảo trên UI. */
    public const FORBIDDEN_COMBOS = [
        ['pair' => 'BAB × kem dưỡng ẩm / kem hăm / sữa tắm da nhạy cảm', 'consequence' => 'Ảnh da bé before/after = tuyên bố hiệu quả điều trị. Vi phạm quy định + khiến phụ huynh khác trì hoãn đưa con đi khám.'],
        ['pair' => 'Testimonial × D3K2, canxi, vitamin, ngũ cốc lợi sữa', 'consequence' => 'Phần "Result" chính là tuyên bố hiệu quả TPCN — nhóm bị quản lý chặt nhất.'],
        ['pair' => 'PSC ngắn × ghế ô tô, địu, nhộng chũn', 'consequence' => 'Sản phẩm an toàn tính mạng không được rút gọn thành 20 giây. Bắt buộc Onboarding.'],
        ['pair' => 'ABCD × bông tăm, khăn khô, cọ rửa bình', 'consequence' => 'Chi phí dựng vượt xa hoa hồng thu về.'],
        ['pair' => 'PSC × bông tăm, nhiệt ẩm kế phòng', 'consequence' => 'Khán giả chưa ý thức vấn đề → PSC rơi vào khoảng không. Phải dùng HVC.'],
        ['pair' => 'Bất kỳ công thức có CTA bán × sữa công thức <24 tháng, bình sữa, ti giả', 'consequence' => 'Vi phạm Nghị định 100/2014/NĐ-CP.'],
        ['pair' => 'PSC dùng hình ảnh trẻ khóc/đau để mở đầu', 'consequence' => 'Thổi phồng nỗi sợ với đối tượng đang lo lắng sẵn — phi đạo đức và phản tác dụng dài hạn.'],
    ];

    /** Phần 9 — phân bổ ngân sách sản xuất theo công thức, hiển thị tĩnh làm gợi ý lập kế hoạch. */
    public const PRODUCTION_BUDGET = [
        'psa' => ['time' => '30–60 phút', 'share' => '35%', 'role' => 'Bắt traffic, số lượng lớn'],
        'bab' => ['time' => '1–2 giờ (cần setup test)', 'share' => '15%', 'role' => 'Tạo nội dung dễ lan truyền'],
        'hook_value_cta' => ['time' => '1–2 giờ (cần nghiên cứu)', 'share' => '25%', 'role' => 'Xây uy tín, phủ nhóm unaware'],
        'onboarding_5part' => ['time' => '2–4 giờ', 'share' => '15%', 'role' => 'Chốt đơn nhóm máy móc + nội dung an toàn'],
        'testimonial_5part' => ['time' => '2–3 giờ', 'share' => '8%', 'role' => 'Chốt đơn giá trị cao'],
        'abcd' => ['time' => '3–5 giờ (+ chi phí ads)', 'share' => '2%', 'role' => 'Chỉ cho sản phẩm >1 triệu'],
    ];

    /** @return array<string, array> Toàn bộ catalog, key = slug nội bộ. */
    public static function all(): array
    {
        return self::PRODUCTS;
    }

    public static function find(string $slug): ?array
    {
        return self::PRODUCTS[$slug] ?? null;
    }

    /** Tìm theo tên gần đúng (không phân biệt hoa/thường, không dấu) — dùng cho ô tìm kiếm catalog. */
    public static function search(string $term): array
    {
        $needle = self::normalize($term);
        if ($needle === '') {
            return self::PRODUCTS;
        }

        return array_filter(self::PRODUCTS, function (array $entry) use ($needle): bool {
            return str_contains(self::normalize($entry['name']), $needle);
        });
    }

    private static function normalize(string $value): string
    {
        $value = Str::of($value)->lower()->ascii()->toString();

        return trim($value);
    }
}
