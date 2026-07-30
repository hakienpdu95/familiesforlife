<?php

namespace Modules\CoreIdeaExtractor\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CoreIdeaExtractor\Models\CategoryContentFoundation;
use Modules\Post\Models\PostCategory;

/**
 * Seeder CHUNG cho Category Content Foundation (spec/CoreIdeaExtractor.md §12) — mỗi danh mục
 * (PostCategory) cần insight tương ứng 1 phần tử trong self::DEFINITIONS bên dưới. Tra category
 * theo `slug` (+ `parent_slug` khi là danh mục con) thay vì hardcode id, vì nhiều nhóm tuổi dùng
 * CHUNG tên danh mục ("Chăm sóc & nuôi dạy", "Phát triển của trẻ", "Dinh dưỡng cho trẻ", "Bệnh
 * thường gặp" lặp lại ở tre-tap-di-1-3-tuoi / tre-mam-non-3-6-tuoi / tre-tieu-hoc-6-12-tuoi...) —
 * tra riêng theo id rất dễ seed nhầm nhóm tuổi.
 *
 * Idempotent theo TỪNG định nghĩa: chạy lại KHÔNG tạo bản ghi trùng — cập nhật foundation hiện có
 * của category (update nếu đã tồn tại, insert nếu chưa) rồi gắn lại categories() qua
 * syncWithoutDetaching (không detach các category khác đang share chung bộ tiêu chí này, xem
 * UpsertCategoryFoundationAction để biết vì sao 1 foundation có thể áp dụng cho N category).
 *
 * Thêm insight cho danh mục MỚI: append 1 phần tử vào self::DEFINITIONS bên dưới — KHÔNG tạo file
 * seeder riêng cho từng danh mục, tránh phình số lượng file nhỏ lẻ khi danh mục lên tới hàng chục.
 */
class CategoryFoundationSeeder extends Seeder
{
    /**
     * @var array<int, array{
     *     parent_slug: ?string,
     *     slug: string,
     *     writer_insights: string,
     *     core_focus: string,
     *     unique_angle: string,
     *     content_goals: string,
     *     pain_points: string,
     *     rejected_ideas: string,
     *     audience: string,
     *     constraints: string,
     *     style_sample: string,
     * }>
     */
    private const DEFINITIONS = [
        // === Trẻ mầm non (3-6 tuổi) > Chăm sóc & nuôi dạy ===
        [
            'parent_slug' => 'tre-mam-non-3-6-tuoi',
            'slug'        => 'cham-soc-nuoi-day-2',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: HÀNH VI/CẢM XÚC hằng ngày của trẻ 3-6 tuổi (ăn vạ, nề nếp, tự lập, màn hình, câu hỏi khó, tiền lớp 1).
                - KHÔNG viết: mốc phát triển/chậm nói (→ Phát triển của trẻ), biếng ăn (→ Dinh dưỡng cho trẻ), bệnh (→ Bệnh thường gặp), chọn trường (→ Trường mầm non & tiểu học).
                - Góc khác biệt: bản địa hóa phương pháp Tây (time-out, Montessori) cho hoàn cảnh Việt — ông bà cùng chăm, lớp 30-40 cháu, bố mẹ chỉ có 2 tiếng buổi tối.
                - Giọng bắt buộc: thừa nhận cha mẹ sẽ mất bình tĩnh và thất bại — không lý tưởng hóa, không phán xét.
                - Ranh giới với "Chăm sóc & nuôi dạy" ở tuổi khác: tuổi 1-3 là ăn vạ/cai bỉm/cai ti (chưa có tư duy trừu tượng); tuổi 3-6 (ở đây) thêm nói dối/ghen tị em/câu hỏi khó; tuổi 6-12 là tự học/mạng xã hội/bắt nạt — không viết chồng giữa 3 mốc.
                - Điểm nhạy cảm phải xử lý tử tế, không né: thương lượng với ông bà, bất đồng vợ chồng, tội lỗi của mẹ đi làm.
                - Kịch bản thoại và ví dụ nên có cả tình huống BỐ xử lý con một mình (không chỉ mặc định mẹ là người xử lý chính) — tránh viết như thể nuôi dạy con 3-6 tuổi là việc riêng của mẹ.
                TEXT,

            'core_focus' => <<<'TEXT'
                Cẩm nang thực hành hằng ngày về nuôi dạy trẻ 3-6 tuổi cho cha mẹ Việt đi làm: xử lý hành vi và cảm xúc (ăn vạ, bướng bỉnh, cãi lại, khủng hoảng tuổi lên 3-4, nói dối, nói bậy, ghen tị với em), xây nề nếp sinh hoạt (giờ ngủ, vệ sinh cá nhân, ngủ riêng, buổi sáng đi học không nước mắt), rèn tính tự lập và tự phục vụ (tự xúc ăn, tự mặc đồ, làm việc nhà theo tuổi), quản lý màn hình (giới hạn, lộ trình cai điện thoại/tivi), kỷ luật tích cực áp dụng được trong gia đình Việt (có ông bà cùng chăm), trả lời các câu hỏi khó của con (cái chết, giới tính, "con sinh ra từ đâu"), dạy con tự bảo vệ trước xâm hại, và chuẩn bị tâm thế - thói quen - kỹ năng cho giai đoạn tiền lớp 1. Mỗi bài đi từ MỘT tình huống cụ thể có thật, giải thích ngắn gọn vì sao trẻ hành xử vậy theo tâm lý lứa tuổi, rồi đưa các bước xử lý làm được ngay và cách xây thói quen trong 2-4 tuần. KHÔNG lấn sân: cột mốc phát triển/chậm nói (thuộc Phát triển của trẻ), biếng ăn/thực đơn (thuộc Dinh dưỡng cho trẻ), bệnh tật (thuộc Bệnh thường gặp), chọn trường (thuộc Trường mầm non & tiểu học).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Viết từ bối cảnh thật của gia đình Việt mà các bài dịch từ nguồn Tây và bài PR của trường mầm non không chạm tới: bố mẹ đi làm về 18h chỉ có 2 tiếng buổi tối với con, nhà có ông bà cùng chăm và nuông chiều theo cách khác bố mẹ, lớp mầm non 30-40 cháu/2 cô chứ không phải lớp Montessori 15 cháu. Ba điểm khác biệt cụ thể: (1) Điều chỉnh phương pháp quốc tế (time-out, kỷ luật tích cực, Montessori tại nhà) vào điều kiện Việt Nam thay vì dịch nguyên xi — nói rõ cái gì áp dụng được, cái gì phải biến tấu, kèm kịch bản thoại mẫu bằng tiếng Việt tự nhiên cha mẹ nói được ngay; (2) Thừa nhận cha mẹ sẽ mất bình tĩnh và thất bại — mỗi bài có phần "khi bạn lỡ quát con rồi thì làm gì", không lý tưởng hóa, không phán xét; (3) Dám xử lý tử tế các đề tài nhạy cảm gần như không ai viết sâu: thương lượng với ông bà về cách dạy cháu mà không sứt mẻ tình cảm, bất đồng quan điểm nuôi dạy giữa vợ chồng, cảm giác tội lỗi của mẹ đi làm.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Trở thành nơi cha mẹ QUAY LẠI mỗi khi con bước sang tình huống mới trong 3 năm mầm non — đo bằng tỷ lệ đọc từ bài này sang bài khác trong chuỗi và lượng người đọc quay lại. (2) Chiếm traffic tìm kiếm dài hạn cho các truy vấn dạng tình huống ít cạnh tranh từ trường học/bệnh viện: "con 4 tuổi hay cãi lại phải làm sao", "cai điện thoại cho trẻ 5 tuổi", "có nên dạy chữ trước khi vào lớp 1", "trẻ ăn vạ nơi công cộng". (3) Xây uy tín biên tập để dẫn người đọc sang các danh mục liền kề: Phát triển của trẻ, Dinh dưỡng cho trẻ, Trường mầm non & tiểu học và (khi con lớn) Trẻ tiểu học 6-12 tuổi — chuỗi liên kết nội bộ theo hành trình tuổi của con. (4) Tạo nền tảng nội dung trụ cột (pillar): mỗi cụm đề tài có 1 bài tổng quan + các bài tình huống vệ tinh, thay vì các bài lẻ rời rạc.
                TEXT,

            'pain_points' => <<<'TEXT'
                Hành vi: "Con lên 3 tự nhiên bướng hẳn, cái gì cũng KHÔNG, ăn vạ lăn ra đất giữa siêu thị — con hư hay bình thường?"; "Mình biết không nên quát mà sáng nào vội đi làm cũng quát, xong lại dằn vặt cả ngày"; "Phạt time-out mà ông bà chạy vào bênh, công cốc". Màn hình: "Trót cho xem điện thoại lúc ăn từ bé, giờ không có là không chịu ăn, cai kiểu gì?"; "Con xem tivi thì gọi không thưa". Tự lập: "5 tuổi chưa tự xúc ăn vì bà đút cho nhanh — nói bà thì sợ mất lòng"; "Con ở lớp tự làm hết, về nhà lại õng ẹo đòi phục vụ". Vào lớp 1: "Có nên cho học chữ trước không, cả lớp mẫu giáo đi học thêm hết rồi, không học sợ con tụt lại"; "Con nhút nhát, sợ vào lớp 1 không hòa nhập". Câu hỏi khó: "Con hỏi chết là gì / con sinh ra từ đâu — trả lời sao cho đúng?"; "Con học nói bậy ở lớp về"; "Dạy con vùng riêng tư, phòng xâm hại từ tuổi nào, nói thế nào không làm con sợ?". Gia đình: "Từ ngày có em, anh lớn đổi tính, đánh em, bám mẹ"; "Hai vợ chồng dạy con hai kiểu, con biết ai dễ thì theo"; "Nên cho ngủ riêng chưa?". Nền chung: cha mẹ ngập trong lời khuyên mâu thuẫn từ mạng xã hội, thiếu thời gian, và cảm giác tội lỗi vì đi làm cả ngày.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Review/so sánh trường mầm non tốt nhất Hà Nội, TP.HCM" — thuộc danh mục Trường mầm non & tiểu học, viết ở đây gây chồng lấn nội bộ. "Các cột mốc phát triển trẻ 3-6 tuổi" và "Dấu hiệu chậm nói" — thuộc danh mục Phát triển của trẻ. "Thực đơn cho trẻ biếng ăn" — thuộc Dinh dưỡng cho trẻ; chuyên mục này chỉ viết khía cạnh HÀNH VI bữa ăn (tự xúc, ngồi vào bàn, không xem tivi khi ăn). "So sánh lý thuyết Montessori vs Steiner vs Reggio" — các trường và trang giáo dục đã viết dày đặc để bán tuyển sinh, khó cạnh tranh SEO và ít giá trị hành động; chỉ viết khi quy về "áp dụng tại nhà thế nào". "Dạy con 4-5 tuổi đọc thông viết thạo/học tiếng Anh sớm thành thần đồng" — đi ngược quan điểm biên tập phát triển đúng lứa tuổi, cổ vũ áp lực sớm; thay bằng bài chuẩn bị tiền lớp 1 đúng cách. "Tác hại của điện thoại với trẻ" dạng liệt kê hù dọa — độc giả đã bão hòa và chỉ tăng cảm giác tội lỗi, không đổi được hành vi; chỉ viết dạng lộ trình cai cụ thể. "Trẻ hư tại ông bà" giọng đổ lỗi — đề tài cần viết nhưng KHÔNG theo hướng phán xét ông bà, chỉ viết dạng hướng dẫn thương lượng thiện chí.
                TEXT,

            'audience' => 'Cha mẹ Việt 27-40 tuổi có con 3-6 tuổi học mầm non (mẹ thường đọc nhiều hơn nhưng bố cũng chủ động tìm khi cần xử lý một tình huống cụ thể vừa xảy ra), con đầu hoặc vừa có thêm con thứ hai, sống thành thị/ven đô, cả hai đi làm toàn thời gian, chỉ thực sự bên con buổi tối và cuối tuần, nhiều nhà có ông bà chăm cùng; đọc trên điện thoại lúc 21h-23h, tìm giải pháp cho tình huống vừa xảy ra trong ngày, đang mệt và dễ thấy tội lỗi.',

            'constraints' => 'Không giọng hàn lâm, thuyết giảng hay phán xét cha mẹ; không hù dọa, không "con nhà người ta"; không cổ vũ đòn roi, quát mắng, so sánh; không đổ lỗi cho ông bà hay mẹ đi làm; lời khuyên phải khả thi với nhà chung cư chật, bố mẹ về nhà 18h; luôn có bước làm được ngay tối nay; kịch bản thoại phải là tiếng Việt tự nhiên, không dịch máy.',

            'style_sample' => <<<'TEXT'
                9 giờ tối, con đã ngủ, còn bạn vẫn ngồi nghĩ lại cảnh con lăn ra sàn siêu thị gào khóc đòi mua siêu nhân — và cả tiếng quát của chính mình sau đó. Trước hết, hãy thở ra một cái: một đứa trẻ 3-4 tuổi ăn vạ không phải là đứa trẻ hư, và một người mẹ lỡ quát con không phải là người mẹ tồi. Ở tuổi này, não bộ phụ trách kiềm chế cảm xúc của con mới chỉ đang xây những viên gạch đầu tiên — con không "cố tình thử thách" bạn, con thật sự chưa đủ khả năng dừng cơn giận lại. Hiểu điều đó không làm cơn ăn vạ biến mất, nhưng nó đổi câu hỏi trong đầu bạn từ "làm sao trị được con" thành "làm sao dạy con vượt qua cơn giận" — và đó là hai con đường rất khác nhau. Trong bài này, chúng ta sẽ đi qua 4 bước xử lý ngay tại chỗ khi con ăn vạ nơi công cộng, những câu nên nói và nên tránh (kèm lời thoại mẫu bạn có thể dùng nguyên văn), cách thống nhất trước với ông bà để không ai "phá vỡ thế trận", và cuối cùng — làm gì với cảm giác tội lỗi của chính bạn khi mọi chuyện đã qua.
                TEXT,
        ],

        // === Chuẩn bị mang thai ===
        [
            'parent_slug' => null,
            'slug'        => 'chuan-bi-mang-thai',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: TRƯỚC khi có thai — khám tiền hôn nhân, tiêm phòng, axit folic, canh thời điểm, chuẩn bị tài chính/tâm lý, nhận biết chậm con.
                - KHÔNG viết: dinh dưỡng SAU khi đã có thai (→ Dinh dưỡng thai kỳ), theo dõi thai (→ Sự phát triển của thai nhi), điều trị hiếm muộn chuyên sâu.
                - Điểm khác biệt bắt buộc giữ: viết cho CẢ HAI vợ chồng (sức khỏe tinh trùng, vai trò người chồng) — không viết như chỉ vợ có trách nhiệm.
                - Nói thẳng phần không ai nói: áp lực giục sinh, tủi thân nhìn bạn bè có con, mệt mỏi khi canh trứng biến chuyện chăn gối thành nghĩa vụ.
                - Phân định rõ y khoa (tiêm phòng, folic) vs quan niệm dân gian chưa kiểm chứng (xem tuổi, kiêng khem) — tôn trọng nhưng không để hoang mang.
                - Đây là ĐIỂM CHẠM ĐẦU TIÊN của độc giả với site — ưu tiên chuyển tiếp mượt sang Dinh dưỡng thai kỳ/Sức khỏe mẹ bầu ngay khi có tin vui.
                TEXT,

            'core_focus' => <<<'TEXT'
                Đồng hành với các cặp vợ chồng Việt từ lúc "quyết định có con" đến lúc que thử lên 2 vạch: chuẩn bị sức khỏe trước mang thai (khám tiền hôn nhân/tiền sản, tiêm phòng trước mang thai, bổ sung axit folic, cai thuốc lá - rượu bia), hiểu chu kỳ và canh thời điểm dễ thụ thai, chuẩn bị tài chính - tâm lý - công việc trước khi có con, xử lý áp lực "bao giờ có tin vui" từ hai bên nội ngoại, và nhận biết khi nào chậm con là bình thường - khi nào nên đi khám hiếm muộn (chuẩn WHO: 1 năm với vợ dưới 35 tuổi, 6 tháng với vợ trên 35). Bài viết dạng lộ trình theo mốc thời gian (6 tháng - 3 tháng - 1 tháng trước khi thả) và dạng giải đáp tình huống thật. KHÔNG lấn sân: dinh dưỡng sau khi ĐÃ có thai (thuộc Dinh dưỡng thai kỳ), theo dõi thai (thuộc Sự phát triển của thai nhi), điều trị hiếm muộn chuyên sâu (chỉ dừng ở mức nhận biết dấu hiệu và hướng dẫn chọn nơi khám).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Hầu hết bài "chuẩn bị mang thai" trên mạng là checklist y khoa dịch lại hoặc bài PR của phòng khám. Chuyên mục này khác ở 3 điểm: (1) Viết cho CẢ HAI vợ chồng — sức khỏe tinh trùng, vai trò người chồng trong chuẩn bị và trong hành trình mong con, điều gần như mọi nguồn tiếng Việt bỏ qua; (2) Nói thẳng về phần không ai nói: áp lực giục đẻ từ gia đình, tủi thân khi bạn bè lần lượt có con, mệt mỏi khi canh trứng biến chuyện vợ chồng thành nghĩa vụ — kèm cách giữ tinh thần và hôn nhân trong giai đoạn chờ đợi; (3) Phân biệt rõ ràng cái gì là y khoa (tiêm phòng, axit folic, khám sàng lọc) và cái gì là quan niệm dân gian chưa có bằng chứng (kiêng khem cực đoan, canh năm sinh con hợp tuổi) — tôn trọng văn hóa nhưng không để người đọc hoang mang giữa hai luồng.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Là điểm CHẠM ĐẦU TIÊN của độc giả với site — người đọc đến từ trước khi có con và nếu được phục vụ tốt sẽ đi cùng site suốt hành trình thai kỳ → sơ sinh → mầm non; ưu tiên chuyển đọc giả sang chuỗi Dinh dưỡng thai kỳ, Sức khỏe mẹ bầu ngay khi họ có tin vui — đo bằng CTR từ bài "chuẩn bị mang thai" sang 2 chuyên mục đó. (2) SEO cho truy vấn giai đoạn sớm: "chuẩn bị gì trước khi mang thai", "uống axit folic trước khi mang thai bao lâu", "thả 6 tháng chưa có thai có sao không", "khám tiền sản ở đâu, hết bao nhiêu tiền". (3) Xây niềm tin y khoa có kiểm chứng ngay từ chuyên mục đầu phễu để định vị site là nguồn đáng tin cho cả hành trình làm cha mẹ — đo bằng thời gian đọc trung bình và tỷ lệ quay lại của độc giả trong nhóm tuổi này.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Hai vợ chồng thả 4-5 tháng chưa có gì, mình có vấn đề không hay do chưa đúng thời điểm?"; "Muốn đi khám trước khi mang thai mà không biết khám những gì, ở đâu, bao nhiêu tiền, có bị vẽ thêm dịch vụ không?"; "Tiêm phòng trước mang thai cần mũi nào, tiêm xong phải tránh thai bao lâu?"; "Uống axit folic loại nào, trước bao lâu?"; "Chồng hút thuốc, uống bia tiếp khách — ảnh hưởng thế nào, thuyết phục chồng kiểu gì?"; "Hai bên nội ngoại giục liên tục, mỗi lần giỗ Tết là sợ về quê"; "Bạn bè cưới sau đã có con, mình tủi thân và bắt đầu lo"; "Canh trứng mãi thành áp lực, vợ chồng căng thẳng chuyện chăn gối"; "Tài chính bao nhiêu thì đủ để sinh con, lỡ có bầu lúc công việc chưa ổn định thì sao?"; "Có nên xem tuổi, chọn năm sinh con không — khoa học nói gì?"; "Vừa ngưng thuốc tránh thai thì bao lâu có thai lại được?". Nền chung: giai đoạn này người đọc âm thầm tìm kiếm một mình (chưa dám nói với ai), rất dễ rơi vào ma trận thông tin từ hội nhóm và quảng cáo phòng khám hiếm muộn.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Dấu hiệu mang thai tuần đầu" — truy vấn khổng lồ nhưng mọi trang sức khỏe lớn (Vinmec, Hello Bacsi, Long Châu) đã phủ dày đặc, khó cạnh tranh và người đọc đến rồi đi ngay; chỉ viết nếu có góc "phân biệt với dấu hiệu sắp có kinh" thực dụng hơn. "Cách sinh con trai/con gái theo ý muốn" — không có cơ sở khoa học, rủi ro pháp lý (lựa chọn giới tính thai nhi bị cấm tại Việt Nam theo Pháp lệnh Dân số), tuyệt đối không viết dù truy vấn rất lớn. "Review chi tiết các gói IVF/IUI từng bệnh viện" — vượt phạm vi chuyên mục (điều trị chuyên sâu), dễ thành bài quảng cáo trá hình; chỉ dừng ở bài hướng dẫn khi nào nên đi khám hiếm muộn và cách chọn cơ sở uy tín. "Bài thuốc dân gian giúp dễ thụ thai" — chưa kiểm chứng, rủi ro sức khỏe, đi ngược định vị y khoa có bằng chứng của site.
                TEXT,

            'audience' => 'Cặp vợ chồng Việt 25-35 tuổi mới cưới hoặc kết hôn 1-3 năm, sống thành thị, dự định có con đầu lòng trong 1 năm tới hoặc đang "thả" mà chưa có; người tìm đọc chủ yếu là vợ, âm thầm tự tìm hiểu trên điện thoại, chưa chia sẻ với gia đình; một phần đang chịu áp lực giục sinh từ hai bên nội ngoại và bắt đầu lo lắng khi chờ mãi chưa có tin vui.',

            'constraints' => 'Không giọng phán xét hay tạo thêm áp lực sinh con; không hù dọa vô sinh để câu view; không quảng cáo trá hình phòng khám/thực phẩm chức năng; thông tin y khoa phải dẫn nguồn chuẩn (WHO, Bộ Y tế); tôn trọng quan niệm dân gian nhưng phân định rõ với y khoa; không viết như thể chỉ vợ có trách nhiệm chuẩn bị.',

            'style_sample' => <<<'TEXT'
                Có một câu hỏi mà rất nhiều người vợ gõ vào ô tìm kiếm lúc 11 giờ đêm, sau khi que thử lại chỉ hiện một vạch: "Thả 5 tháng chưa có thai, mình có bị làm sao không?". Nếu bạn cũng đang ở trong khoảnh khắc đó — trước tiên, hãy biết rằng bạn không hề đơn độc và phần lớn khả năng là bạn không "bị làm sao" cả. Y học tính thế này: với phụ nữ dưới 35 tuổi, các bác sĩ chỉ bắt đầu gọi là "chậm con" khi hai vợ chồng quan hệ đều đặn, không tránh thai TRÒN MỘT NĂM mà chưa có thai. Nghĩa là ở tháng thứ 5, bạn vẫn đang ở trong vùng hoàn toàn bình thường của xác suất — mỗi chu kỳ, ngay cả một cặp đôi khỏe mạnh cũng chỉ có khoảng 15-25% cơ hội thụ thai. Trong bài này, chúng ta sẽ xem xác suất đó thực ra hoạt động thế nào, những gì hai vợ chồng có thể chủ động làm ngay từ tháng này (có việc cho cả chồng nữa, không phải chỉ mình bạn), và những dấu hiệu cụ thể nào mới thật sự là lúc nên đặt lịch đi khám.
                TEXT,
        ],

        // === Sự phát triển của thai nhi ===
        [
            'parent_slug' => null,
            'slug'        => 'su-phat-trien-cua-thai-nhi',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: sự phát triển của THAI NHI theo từng tuần (1-40) — kích thước, cơ quan, mốc siêu âm/xét nghiệm sàng lọc, cách đọc chỉ số phiếu siêu âm, đếm thai máy.
                - KHÔNG viết: triệu chứng/bệnh của MẸ (→ Sức khỏe mẹ bầu), ăn uống (→ Dinh dưỡng thai kỳ), chuẩn bị sinh (→ Chuyển dạ & đi sinh).
                - Định dạng chủ lực: chuỗi 40 bài "Thai nhi tuần thứ N" chuẩn hóa cấu trúc — đây là cỗ máy giữ chân độc giả theo tuần suốt 9 tháng.
                - Khác biệt bắt buộc giữ: bản địa hóa so sánh kích thước (không dùng trái cây Tây xa lạ), khớp đúng lịch khám thai thực tế Việt Nam (kể cả xếp hàng viện công).
                - Dạy mẹ ĐỌC HIỂU phiếu siêu âm (BPD, FL, EFW...) — không chỉ mô tả thai, vì bác sĩ thường chỉ có 2 phút giải thích.
                - Mọi chỉ số bất thường phải kèm khoảng bình thường + bước tiếp theo, viết điềm tĩnh — đây là lúc mẹ dễ hoảng loạn nhất.
                TEXT,

            'core_focus' => <<<'TEXT'
                Theo chân sự phát triển của em bé trong bụng theo TỪNG TUẦN thai (tuần 1-40): kích thước và hình hài của con tuần này (so sánh trực quan kiểu "bằng hạt đậu, quả chanh, quả bưởi"), cơ quan nào đang hình thành, con đã nghe - đã máy - đã xoay đầu chưa, kèm theo đúng mốc đó là: mốc siêu âm - xét nghiệm sàng lọc quan trọng (NIPT, đo độ mờ da gáy tuần 11-13, siêu âm hình thái tuần 20-22, tiểu đường thai kỳ tuần 24-28), cách đọc hiểu các chỉ số trong phiếu siêu âm (BPD, FL, EFW, chỉ số ối...), thai máy - cách đếm cử động thai và khi nào cần đi khám ngay. Định dạng chủ lực: chuỗi bài "Thai nhi tuần thứ N" chuẩn hóa cấu trúc (con thế nào - mẹ thế nào - việc cần làm tuần này) để người đọc theo dõi như lịch. KHÔNG lấn sân: triệu chứng và bệnh của MẸ (thuộc Sức khỏe mẹ bầu), ăn uống (thuộc Dinh dưỡng thai kỳ), chuẩn bị sinh (thuộc Chuyển dạ & đi sinh).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Chuỗi bài theo tuần thai là "món" mọi site mẹ và bé đều có, nhưng đa số dịch máy từ BabyCenter/What to Expect với so sánh kích thước bằng trái cây Tây (quả mâm xôi, bí ngòi...) và lịch khám không khớp thực tế Việt Nam. Khác biệt của chuyên mục: (1) Bản địa hóa thật sự — so sánh bằng hình ảnh quen thuộc với người Việt, lịch khám khớp với quy trình khám thai tại bệnh viện công và phòng khám tư ở Việt Nam (kể cả chuyện xếp hàng lấy số từ 5h sáng ở viện công); (2) Dạy mẹ ĐỌC HIỂU phiếu siêu âm thay vì chỉ mô tả thai — mẹ Việt cầm phiếu đầy chỉ số viết tắt mà bác sĩ chỉ nói "thai bình thường nhé" trong 2 phút; (3) Mỗi mốc sàng lọc đều có phần "nếu kết quả bất thường thì hiểu thế nào" viết điềm tĩnh, chính xác — vì đây là lúc mẹ hoảng loạn nhất và dễ bị dắt đi làm thêm dịch vụ không cần thiết nhất.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Chuỗi "thai nhi tuần thứ N" (40 bài trụ cột) là cỗ máy giữ chân độc giả: mẹ quay lại MỖI TUẦN trong 9 tháng — mục tiêu đo bằng lượng người đọc quay lại hằng tuần và đăng ký nhận thông báo theo tuần thai. (2) SEO cho cụm truy vấn đều đặn quanh năm: "thai N tuần phát triển như thế nào", "thai N tuần nặng bao nhiêu", "chỉ số BPD là gì", "độ mờ da gáy bao nhiêu là bình thường", "thai máy như thế nào". (3) Liên kết chéo chặt với Sức khỏe mẹ bầu và Dinh dưỡng thai kỳ theo đúng tuần tương ứng để tăng số trang mỗi phiên đọc. (4) Sau tuần 36, dẫn người đọc sang Chuyển dạ & đi sinh và các chuyên mục Trẻ sơ sinh — nối liền phễu nội dung theo hành trình.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Thai 12 tuần rồi mà bụng chưa thấy gì, con có đang lớn bình thường không?"; "Phiếu siêu âm ghi BPD, FL, HC toàn chữ viết tắt, bác sĩ chỉ nói 'bình thường' rồi gọi người tiếp theo — về nhà mình tự tra mà mỗi trang nói một kiểu"; "Đo độ mờ da gáy 1.8mm có sao không, ngưỡng bao nhiêu là nguy hiểm?"; "NIPT với Double test khác gì nhau, có cần làm cả hai không hay phòng khám vẽ thêm?"; "20 tuần chưa thấy thai máy trong khi bạn cùng tháng đã thấy — có sao không?"; "Đêm nằm đếm mãi không thấy con đạp, hoảng quá 2h sáng lên mạng tìm"; "Siêu âm ước tính cân nặng 2.8kg lúc 36 tuần — con nhỏ hơn chuẩn có phải mẹ ăn thiếu chất?"; "Kết quả sàng lọc 'nguy cơ cao' — mình đọc xong tay run, không biết bước tiếp theo là gì, chọc ối có nguy hiểm không?"; "Dây rốn quấn cổ 1 vòng có phải mổ không?". Nền chung: mẹ bầu đọc trong trạng thái lo âu, giữa các lần khám cách nhau 2-4 tuần là khoảng trống thông tin dài, mỗi dấu hiệu lạ đều thành nỗi sợ lúc nửa đêm.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Cách nhận biết mang thai con trai hay con gái qua nhịp tim/hình dáng bụng/lịch Trung Quốc" — mê tín, không cơ sở khoa học, và dính đến lựa chọn giới tính là vùng cấm pháp lý; tuyệt đối không viết. "Ảnh siêu âm 4D đẹp tuần bao nhiêu, ở đâu chụp đẹp" dạng review dịch vụ — dễ thành bài PR, không đúng định vị y khoa; chỉ nhắc mốc siêu âm cần thiết về mặt y tế. "Thai giáo cho con thông minh từ trong bụng" dạng cam kết kết quả — bằng chứng khoa học yếu, dễ thành bán khóa học; chỉ viết ở mức gắn kết mẹ con (trò chuyện, nhạc) không hứa hẹn IQ. "Giải mã giấc mơ khi mang bầu" — câu view rẻ, làm loãng uy tín chuyên mục. "Bảng cân nặng thai nhi chuẩn từng ngày" — chuẩn theo NGÀY không tồn tại về mặt y khoa, gây lo âu sai lệch; chỉ dùng bảng theo tuần theo percentile của WHO/INTERGROWTH kèm giải thích khoảng dao động bình thường.
                TEXT,

            'audience' => 'Mẹ bầu Việt 25-35 tuổi, phần lớn mang thai lần đầu, sống thành thị/ven đô, đi làm văn phòng; theo dõi thai theo tuần như một nghi thức (chụp phiếu siêu âm, lưu ảnh từng mốc); đọc trên điện thoại vào giờ nghỉ trưa và buổi đêm, đặc biệt sau mỗi lần khám thai hoặc khi có dấu hiệu lạ giữa hai kỳ khám; chồng thỉnh thoảng đọc cùng ở các mốc lớn (siêu âm hình thái, sàng lọc).',

            'constraints' => 'Không gây hoang mang — mọi chỉ số bất thường phải kèm khoảng bình thường và bước tiếp theo rõ ràng; không thay thế chẩn đoán của bác sĩ, luôn nhắc khám đúng lịch; số liệu y khoa dẫn nguồn WHO/Bộ Y tế/ACOG; không so sánh kích thước bằng trái cây xa lạ với người Việt; không quảng cáo phòng khám, gói siêu âm, xét nghiệm thương mại; tuyệt đối không nội dung đoán/chọn giới tính thai nhi.',

            'style_sample' => <<<'TEXT'
                Tuần này, con của bạn đã dài khoảng 14cm và nặng chừng 190 gram — cỡ một quả xoài cát nhỏ nằm gọn trong lòng bàn tay. Nhưng con số đáng nhớ nhất của tuần 20 không nằm trên phiếu siêu âm, mà nằm ở cảm giác là lạ trong bụng bạn những ngày này: như có cánh bướm khẽ chạm, như bong bóng nước vỡ nhẹ — đó rất có thể là những cú máy đầu tiên mẹ cảm nhận được. Nếu bạn chưa thấy gì thì cũng đừng vội lo: với mẹ mang thai lần đầu, cảm nhận thai máy muộn hơn, ở tuần 20-22, là hoàn toàn bình thường, vì bạn chưa quen "nhận diện" tín hiệu ấy giữa những âm thanh sôi bụng thường ngày. Tuần này cũng là lúc bạn cầm trên tay tờ phiếu siêu âm hình thái dày đặc chữ viết tắt — BPD, FL, HC, AC — mà bác sĩ có khi chỉ kịp nói "em bé bình thường nhé". Ở phần dưới, mình sẽ cùng giải nghĩa từng chỉ số đó một cách dễ hiểu, xem con đang được đo những gì, khoảng nào là bình thường, và câu hỏi nào đáng mang theo cho lần khám tới.
                TEXT,
        ],

        // === Sức khỏe mẹ bầu ===
        [
            'parent_slug' => null,
            'slug'        => 'suc-khoe-me-bau',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: sức khỏe THỂ CHẤT + TINH THẦN của MẸ (không phải thai) — triệu chứng khó chịu, bệnh lý thai kỳ, dấu hiệu nguy hiểm, thuốc/vaccine, kiêng cữ dân gian.
                - Ranh giới với Dinh dưỡng thai kỳ (hay chồng lấn nhất): bệnh lý thai kỳ như tiểu đường/thiếu máu — CHẨN ĐOÁN, theo dõi, thuốc thuộc chuyên mục này; THỰC ĐƠN/món ăn cụ thể để kiểm soát bệnh đó thuộc Dinh dưỡng thai kỳ. Không viết thực đơn ở đây, chỉ dẫn link sang.
                - KHÔNG viết: chỉ số phát triển của thai (→ Sự phát triển của thai nhi), dấu hiệu chuyển dạ (→ Chuyển dạ & đi sinh).
                - Khác biệt đã làm tốt, GIỮ NGUYÊN khung này: đứng giữa "bệnh viện chuẩn nhưng lạnh" và "hội nhóm ấm nhưng nguy hiểm" — không lặp lại cách viết này ở chuyên mục khác.
                - Sức khỏe tinh thần (lo âu thai kỳ, trầm cảm trước sinh) phải ngang hàng thể chất — chủ đề mọi site tiếng Việt khác đang bỏ trống.
                - Kiêng cữ dân gian: giải thích nguồn gốc + đối chiếu y khoa + cho "kịch bản nói chuyện" giữ hòa khí, không chế giễu.
                TEXT,

            'core_focus' => <<<'TEXT'
                Sức khỏe THỂ CHẤT và TINH THẦN của người mẹ trong 9 tháng thai kỳ: xử lý các triệu chứng khó chịu theo tam cá nguyệt (ốm nghén, ợ nóng, chuột rút, đau lưng, phù chân, mất ngủ, táo bón, rạn da), nhận biết và theo dõi các bệnh lý thai kỳ thường gặp (tiểu đường thai kỳ, tăng huyết áp - tiền sản giật, thiếu máu, viêm âm đạo khi mang thai), dấu hiệu nguy hiểm phải đi viện NGAY (ra máu, đau bụng dữ dội, phù mặt đột ngột, thai giảm máy), thuốc và vaccine khi mang thai (loại nào an toàn, loại nào cấm), vận động - làm việc - quan hệ vợ chồng khi mang bầu, sức khỏe tinh thần (lo âu thai kỳ, thay đổi cảm xúc, áp lực công việc khi bụng bầu), và bóc tách kiêng cữ dân gian: cái nào có lý, cái nào vô căn cứ. KHÔNG lấn sân: chỉ số phát triển của thai (thuộc Sự phát triển của thai nhi), dấu hiệu chuyển dạ (thuộc Chuyển dạ & đi sinh). Ranh giới với Dinh dưỡng thai kỳ: khi bệnh lý thai kỳ (tiểu đường, thiếu máu...) cần điều chỉnh ăn uống, chuyên mục này chỉ nêu NGUYÊN TẮC y khoa (cần kiêng nhóm chất gì) — thực đơn/món ăn cụ thể thuộc Dinh dưỡng thai kỳ, luôn dẫn link sang đó thay vì tự viết thực đơn.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Các trang bệnh viện (Vinmec, Tâm Anh) viết chuẩn y khoa nhưng lạnh và né mọi thứ "ngoài sách"; hội nhóm Facebook thì ấm áp nhưng đầy lời khuyên nguy hiểm. Chuyên mục đứng giữa: (1) Trả lời thẳng những câu mẹ bầu chỉ dám hỏi hội kín — quan hệ khi mang bầu có hại con không, ngồi làm việc 8 tiếng có sao không, sếp giao việc nặng có quyền từ chối không (nối sang chuyên mục Quyền lợi & pháp lý về chế độ thai sản); (2) Xử lý kiêng cữ dân gian bằng thái độ tôn trọng thay vì chế giễu — bà nội bảo không được với tay cao, không ăn ốc: giải thích nguồn gốc quan niệm, đối chiếu y khoa, và cho mẹ "kịch bản nói chuyện" để giữ hòa khí mà không phải làm theo điều vô lý; (3) Sức khỏe tinh thần được đối xử ngang hàng thể chất — lo âu thai kỳ, khóc vô cớ, sợ đẻ, trầm cảm TRƯỚC sinh là chủ đề bị mọi site tiếng Việt bỏ trống trong khi tỷ lệ gặp rất cao.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn triệu chứng cực lớn và đều quanh năm: "bầu 3 tháng đầu bị đau bụng lâm râm", "ốm nghén nặng phải làm sao", "bà bầu mất ngủ", "tiểu đường thai kỳ kiêng gì", "bầu mấy tháng thì ngừng đi xe máy". (2) Trở thành "người bạn tỉnh táo" mẹ bầu tin cậy giữa hai kỳ khám — đo bằng tỷ lệ quay lại và thời gian đọc. (3) Chuỗi bài kiêng cữ dân gian là nội dung khác biệt để được chia sẻ trong hội nhóm mẹ bầu (kênh phát tán mạnh nhất của phân khúc này). (4) Liên kết theo tuần thai với chuỗi Sự phát triển của thai nhi và đổ về Chuyển dạ & đi sinh ở cuối thai kỳ, giữ độc giả trong hệ sinh thái site trọn 9 tháng.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Nghén nặng không ăn được gì, nôn cả ra mật xanh — con có bị đói không, đến khi nào thì hết?"; "Đau bụng lâm râm 3 tháng đầu — bình thường hay dọa sảy? Ngưỡng nào phải đi viện ngay?"; "Kết quả đường huyết 5.4 — bác sĩ bảo theo dõi thêm mà không giải thích, về nhà mình sợ không dám ăn cơm"; "Đêm nào cũng 2-3h mới ngủ được, nằm nghiêng trái mỏi quá mà nghe nói nằm ngửa hại con"; "Mẹ chồng bắt kiêng đủ thứ: không cắt tóc, không ăn ốc con chậm nói, không bước qua võng — mình không tin nhưng cãi thì mất lòng"; "Đang bầu bị cảm/đau răng — uống thuốc gì được, ai cũng dọa thuốc hại con nên đành chịu đau"; "Chồng hỏi chuyện vợ chồng lúc bầu có sao không mà mình cũng không biết, ngại chẳng dám hỏi bác sĩ"; "Tự nhiên khóc vô cớ, cáu gắt với chồng, sợ đẻ đến mất ngủ — mình có bị làm sao không?"; "Bụng bầu 7 tháng vẫn phải chạy deadline, đứng dạy cả ngày — xin nghỉ sớm thì sợ mất việc". Nền chung: mẹ bầu Việt bị kẹp giữa ba luồng — y khoa nói một đằng, mẹ chồng nói một nẻo, hội nhóm nói kiểu khác — và không có ai đứng ra phân xử đáng tin.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Uống nước dừa/nước mía cho con trắng, sạch ối" dạng khẳng định — quan niệm dân gian chưa có bằng chứng, chỉ viết dạng bài kiểm chứng "thực hư" chứ không viết bài khuyên làm theo. "Bảng cân nặng mẹ bầu chuẩn từng tuần" cứng nhắc — tăng cân thai kỳ phụ thuộc BMI trước mang thai, bảng chuẩn chung gây ám ảnh cân nặng; chỉ viết theo khuyến nghị IOM phân theo BMI kèm nhấn mạnh khoảng dao động. "Danh sách 50 thứ bà bầu tuyệt đối không được ăn/làm" — định dạng câu view gieo rắc sợ hãi, đi ngược giọng chuyên mục; thay bằng từng bài nhỏ phân tích có bằng chứng. "Sinh con năm đẹp, xem ngày mổ đẻ hợp tuổi" — mê tín, ảnh hưởng quyết định y khoa (mổ chủ động sớm vì ngày đẹp là thực trạng có hại), không viết dạng cổ vũ; chỉ có thể viết bài phản biện rủi ro y khoa của mổ chọn ngày. "Review bệnh viện phụ sản" — để dành cho Chuyển dạ & đi sinh, tránh trùng phạm vi nội bộ.
                TEXT,

            'audience' => 'Mẹ bầu Việt 25-35 tuổi mang thai lần đầu (một phần lần hai), đi làm văn phòng hoặc công việc đứng nhiều, sống cùng hoặc gần gia đình chồng nên chịu nhiều luồng kiêng cữ trái ngược; đọc trên điện thoại buổi trưa và đêm khuya khi có triệu chứng lạ; trạng thái đọc đặc trưng: vừa lo cho con, vừa mệt trong người, vừa không biết tin ai giữa bác sĩ - mẹ chồng - hội nhóm.',

            'constraints' => 'Không hù dọa, không liệt kê rủi ro thiếu ngữ cảnh xác suất; mọi triệu chứng phải phân tầng rõ "bình thường / theo dõi / đi viện ngay"; không kê đơn hay khuyên tự dùng thuốc — chỉ nêu nguyên tắc và nhắc hỏi bác sĩ; không chế giễu kiêng cữ dân gian hay hình ảnh mẹ chồng; dẫn nguồn y khoa (Bộ Y tế, WHO, ACOG); không quảng cáo TPCN, sữa bầu, gói thai sản.',

            'style_sample' => <<<'TEXT'
                "Không được ăn ốc, con sinh ra chậm nói đấy" — nếu bạn đang mang bầu ở Việt Nam, gần như chắc chắn bạn đã nghe câu này từ bà, từ mẹ chồng, hoặc từ một người hàng xóm tốt bụng. Và có thể tối nay bạn vừa lén ăn một bát bún ốc, rồi nằm áy náy không biết mình có vừa làm hại con không. Hãy để mình nói ngay điều quan trọng nhất: chưa có bất kỳ nghiên cứu y khoa nào cho thấy ăn ốc khiến trẻ chậm nói. Quan niệm này nhiều khả năng ra đời từ thời ốc là món dễ nhiễm ký sinh trùng do nấu không kỹ — nghĩa là lời dặn của các cụ có một cái lõi hợp lý (đồ thủy sản phải nấu chín kỹ), chỉ là cái lõi ấy được bọc trong một lời giải thích không đúng. Vậy nên bát bún ốc nấu sôi kỹ của bạn không có lỗi gì với con cả. Trong bài này, mình sẽ điểm qua những món "bị kết án oan" phổ biến nhất, món nào thật sự cần thận trọng, và — phần có khi bạn cần nhất — gợi ý vài cách trả lời mẹ chồng sao cho vừa giữ được hòa khí, vừa không phải nhịn oan món mình thèm.
                TEXT,
        ],

        // === Dinh dưỡng thai kỳ ===
        [
            'parent_slug' => null,
            'slug'        => 'dinh-duong-thai-ky',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: ĂN UỐNG của mẹ bầu — nguyên tắc theo tam cá nguyệt, vi chất, thực đơn cơm Việt, "ăn X được không", giải oan giáo lý truyền miệng.
                - Ranh giới với Sức khỏe mẹ bầu (hay chồng lấn nhất): khi bệnh lý thai kỳ cần ăn kiêng (tiểu đường, thiếu máu), chuyên mục này viết THỰC ĐƠN CỤ THỂ; chẩn đoán/theo dõi bệnh thuộc Sức khỏe mẹ bầu, luôn dẫn link sang đó.
                - KHÔNG viết: triệu chứng/bệnh lý ngoài ăn uống (→ Sức khỏe mẹ bầu), chỉ số của thai (→ Sự phát triển của thai nhi).
                - Khác biệt bắt buộc giữ: mọi khuyến nghị khoa học phải quy ra BỮA CƠM VIỆT cụ thể (không dừng ở "protein, lipid, glucid"), có phiên bản ngân sách thấp.
                - Xây uy tín "không bán gì cả" — không gắn link sữa bầu/TPCN, khác 90% nội dung cùng ngách.
                - Định dạng mỏ vàng SEO: "bầu ăn X được không" — trả lời rõ mức độ (thoải mái/có chừng mực/nên tránh) kèm lý do, không hù dọa.
                TEXT,

            'core_focus' => <<<'TEXT'
                Ăn uống thực tế cho mẹ bầu Việt trong 9 tháng: nguyên tắc dinh dưỡng theo tam cá nguyệt (3 tháng đầu nghén ăn được gì thì ăn, 3 tháng giữa tăng chất, 3 tháng cuối kiểm soát đường - muối), vi chất quan trọng và cách bổ sung đúng (sắt, canxi, DHA, axit folic — uống lúc nào, cái nào kỵ nhau, có cần uống đủ loại như quảng cáo không), thực phẩm nên ăn - nên hạn chế - phải tránh (có bằng chứng, không hù dọa), thực đơn mẫu kiểu cơm nhà Việt Nam theo túi tiền, ăn uống khi có bệnh lý thai kỳ (tiểu đường thai kỳ ăn gì, thiếu máu ăn gì), giải quyết tình huống thật: nghén không ăn nổi, thèm ăn vặt, đi ăn cỗ - ăn quán, và hóa giải các "giáo lý" ăn uống truyền miệng (ăn cho hai người, uống nước dừa cho con trắng, ăn trứng ngỗng cho con thông minh). KHÔNG lấn sân: triệu chứng và bệnh lý thai kỳ ngoài khía cạnh ăn uống (thuộc Sức khỏe mẹ bầu), chỉ số của thai (thuộc Sự phát triển của thai nhi). Ranh giới với Sức khỏe mẹ bầu: khi bệnh lý thai kỳ cần ăn kiêng, chuyên mục này viết THỰC ĐƠN CỤ THỂ (món gì, bao nhiêu); nguyên tắc y khoa/chẩn đoán/theo dõi bệnh thuộc Sức khỏe mẹ bầu — không lặp lại phần đó ở đây, chỉ dẫn link.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Mẹ bầu Việt tra "bầu ăn dứa/rau ngót/ốc được không" mỗi ngày và nhận về câu trả lời khác nhau ở mỗi trang — trong khi trang khoa học chỉ liệt kê "protein, lipid, glucid" không nấu thành bữa cơm được, còn phần lớn bài "tư vấn dinh dưỡng thai kỳ" khác thực chất là quảng cáo sữa bầu - TPCN cài cắm. Chuyên mục chọn đường thứ ba: (1) Quy mọi khuyến nghị ra BỮA CƠM VIỆT cụ thể — "cần 27mg sắt/ngày" phải thành "một lạng thịt bò + một bát canh rau dền + tráng miệng ổi thay cam"; thực đơn mẫu có phiên bản 50 nghìn/bữa chứ không chỉ cá hồi - hạt chia; (2) Nói thẳng về ngân sách: vi chất nào đáng đồng tiền (sắt, axit folic, canxi), cái nào là marketing (đa phần combo 5-7 lọ TPCN), sữa bầu có bắt buộc không (không — và nói rõ vì sao); (3) Mỗi món "truyền miệng" (trứng ngỗng, nước dừa, cá chép) được kiểm chứng tử tế bằng bằng chứng + văn hóa, không chế nhạo người khuyên.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn khổng lồ, tách khỏi các bệnh viện bằng độ THỰC DỤNG: "bầu 3 tháng đầu nên ăn gì", "bầu ăn X được không" (dứa, rau ngót, ốc, măng, trứng vịt lộn...), "thực đơn cho bà bầu tiểu đường", "uống sắt và canxi cách nhau mấy tiếng". Định dạng "bầu ăn X được không" là mỏ truy vấn dài hạn cần phủ có hệ thống. (2) Xây uy tín "không bán gì cả" — khác biệt với 90% nội dung cùng ngách đang gắn link sữa bầu/TPCN; đo bằng share vào hội nhóm mẹ bầu. (3) Liên kết theo tuần với chuỗi Sự phát triển của thai nhi (mốc xét nghiệm tiểu đường thai kỳ tuần 24-28 nối thẳng sang bài ăn uống tương ứng) và bàn giao độc giả sang Nuôi con bằng sữa mẹ + Ăn dặm & dinh dưỡng sau sinh.
                TEXT,

            'pain_points' => <<<'TEXT'
                "3 tháng đầu nghén không nuốt nổi cơm, chỉ ăn được bánh mì với xoài xanh — con có bị thiếu chất không?"; "Mỗi lần ăn gì cũng phải tra 'bầu ăn được không', mỗi trang nói một kiểu, hoang mang không biết tin ai"; "Nghe nói ăn dứa, rau ngót sảy thai — thật hay đồn?"; "Mẹ chồng ép ăn gấp đôi 'ăn cho cả cháu', mình tăng 8kg trong 4 tháng, bác sĩ nhắc mà bà bảo bầu phải thế"; "Bị tiểu đường thai kỳ, phát cho tờ giấy kiêng đường rồi thôi — cụ thể bữa sáng ăn gì, thèm chè thì làm sao?"; "Uống sắt vào là táo bón, nôn nao — có cách nào đỡ không, đổi loại nào?"; "Sữa bầu tanh không uống nổi, không uống thì sợ con thiếu chất — có bắt buộc không?"; "Được cho cả túi TPCN xách tay: DHA, tảo, vitamin tổng hợp — uống hết thì sợ thừa, bỏ thì tiếc"; "Trưa ăn cơm văn phòng, tối tăng ca — làm sao đủ chất khi không tự nấu được?"; "Ăn trứng ngỗng con có thông minh thật không, một quả to thế ăn muốn xỉu". Nền chung: mẹ bầu bị bủa vây bởi lời khuyên ăn uống từ mọi phía và cảm giác tội lỗi thường trực rằng mình ăn sai là hại con.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Ăn gì để con thông minh/da trắng/chân dài" dạng cam kết — không có bằng chứng nhân quả, chỉ viết dạng bài kiểm chứng quan niệm. "Top 10 sữa bầu tốt nhất" — bản chất là bài affiliate, phá vỡ định vị "không bán gì" của chuyên mục; nếu cần, chỉ viết "có bắt buộc uống sữa bầu không" trung lập (đánh giá sản phẩm cụ thể thuộc danh mục Đánh giá sản phẩm). "Thực đơn giảm cân khi mang bầu" — nhạy cảm y khoa, khuyến nghị sai có thể gây hại thai; kiểm soát cân nặng thai kỳ chỉ viết dưới hướng dẫn nguyên tắc + nhắc làm việc với bác sĩ. "Detox/ăn thực dưỡng/thuần chay cho mẹ bầu" theo trend — rủi ro thiếu vi chất nghiêm trọng nếu làm theo phong trào; chỉ viết bài phân tích rủi ro cho người ĐÃ ăn chay sẵn cần bổ sung gì. "Bầu 3 tháng đầu kiêng ăn 30 món" dạng tổng hợp hù dọa — gieo sợ hãi, thay bằng từng bài "ăn X được không" có bằng chứng và mức độ.
                TEXT,

            'audience' => 'Mẹ bầu Việt 25-35 tuổi ở thành thị/ven đô, đi làm cả ngày, ăn trưa văn phòng, tối mới ăn cơm nhà (thường do mẹ chồng hoặc chính mình nấu); ngân sách ăn uống có hạn, bị người thân và quảng cáo bủa vây bằng lời khuyên bổ sung đủ thứ; tra cứu trên điện thoại NGAY TRƯỚC hoặc SAU bữa ăn khi phân vân một món cụ thể, và đọc kỹ hơn vào buổi tối.',

            'constraints' => 'Không hù dọa "ăn X hại con" thiếu bằng chứng; câu trả lời "ăn được không" phải rõ mức độ (thoải mái / có chừng mực / nên tránh) kèm lý do; không quảng cáo hay gắn link sữa bầu, TPCN; khuyến nghị phải nấu được bằng nguyên liệu chợ Việt Nam, có phương án tiết kiệm; không body-shaming chuyện tăng cân; dẫn nguồn Viện Dinh dưỡng quốc gia, WHO; trường hợp bệnh lý luôn nhắc tham vấn bác sĩ.',

            'style_sample' => <<<'TEXT'
                Đêm qua bạn thèm một đĩa dứa lạnh đến mức mơ cả vào giấc ngủ, nhưng sáng nay đứng trước sạp hoa quả lại rụt tay: "nghe nói bầu ăn dứa sảy thai". Vậy hôm nay mình giải quyết dứt điểm chuyện quả dứa nhé. Lời đồn bắt nguồn từ bromelain — một enzyme trong dứa mà ở dạng CHIẾT XUẤT liều cao từng được ghi nhận gây co bóp tử cung trong phòng thí nghiệm. Nhưng đây là chỗ lời đồn đi quá xa: lượng bromelain trong dứa ăn thường rất nhỏ, lại tập trung chủ yếu ở lõi — phần chẳng ai ăn — và bị phá hủy gần hết qua dạ dày. Các hiệp hội sản khoa lớn đều xếp dứa vào nhóm trái cây an toàn cho thai kỳ; để đạt được liều gây co bóp trên lý thuyết, bạn phải ăn cỡ 7-10 quả dứa nguyên lõi trong một lần — điều mà chưa cơn thèm nào làm nổi. Nghĩa là: một đĩa dứa tráng miệng không những vô tội, mà còn cho bạn thêm vitamin C và chất xơ đang rất cần. Điều duy nhất đáng lưu ý là dứa nhiều axit — nếu bạn đang ợ nóng, hãy ăn sau bữa chính thay vì lúc đói. Còn lại, cứ ăn cho đã cơn thèm, mẹ nhé.
                TEXT,
        ],

        // === Chuyển dạ & đi sinh ===
        [
            'parent_slug' => null,
            'slug'        => 'chuyen-da-di-sinh',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: tuần 36 đến khi mẹ con về nhà — dấu hiệu chuyển dạ thật/giả, chuẩn bị đồ đi sinh, chọn nơi sinh/chi phí, diễn biến cuộc sinh, 24-72h đầu sau sinh.
                - KHÔNG viết: chăm bé sau khi VỀ NHÀ (→ nhóm Trẻ sơ sinh), nuôi sữa mẹ dài hạn (→ Nuôi con bằng sữa mẹ).
                - Khác biệt bắt buộc giữ: người chồng là NHÂN VẬT CHÍNH thứ hai — mỗi bài có phần việc cụ thể cho chồng, không để anh đứng ngoài hút thuốc chờ tin.
                - Minh bạch chi phí từng khoản (công/tư, thường/mổ, BHYT) — không PR viện nào, đây là chủ đề rất ít nơi viết tử tế.
                - Bài "giỏ đồ đi sinh" và "chi phí đi sinh" là 2 bài trụ cột bookmark/share cao nhất site — tối ưu dạng checklist tải được.
                - Nỗi sợ lớn nhất của độc giả không phải cơn đau mà là KHÔNG BIẾT TRƯỚC — mọi bài phải giảm sự không chắc chắn đó trước tiên.
                TEXT,

            'core_focus' => <<<'TEXT'
                Toàn bộ hành trình từ tuần 36 đến lúc mẹ con về nhà: nhận biết dấu hiệu sắp sinh và chuyển dạ thật - giả (cơn gò Braxton Hicks vs chuyển dạ, vỡ ối, ra nhớt hồng), khi nào phải vào viện ngay, chuẩn bị đồ đi sinh sát thực tế bệnh viện Việt Nam (giỏ đồ cho mẹ - cho bé - giấy tờ bảo hiểm, thứ bệnh viện phát sẵn không cần mang), chọn nơi sinh và hiểu chi phí (viện công vs tư, sinh thường vs mổ, bảo hiểm y tế trái tuyến chi trả thế nào), diễn biến cuộc sinh theo từng giai đoạn để mẹ bớt sợ vì biết trước điều gì sẽ xảy ra, giảm đau khi sinh (gây tê ngoài màng cứng - thực hư đồn đại đau lưng về già), sinh mổ: khi nào cần, hồi phục ra sao, và 24-72 giờ đầu sau sinh tại viện (da kề da, khớp ngậm bú mẹ lần đầu, chăm sóc vết khâu/vết mổ, tắm gội sau sinh). Vai trò người chồng trong ngày đi sinh có mặt xuyên suốt. KHÔNG lấn sân: chăm bé sau khi VỀ NHÀ (thuộc nhóm Trẻ sơ sinh), nuôi sữa mẹ dài hạn (thuộc Nuôi con bằng sữa mẹ).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung "đi sinh" tiếng Việt hoặc là lý thuyết y khoa về các giai đoạn chuyển dạ, hoặc là review viện lẻ tẻ trong hội nhóm — không ai gộp thành cẩm nang HẬU CẦN + TÂM LÝ hoàn chỉnh. Khác biệt: (1) Viết như người dẫn đường đã đi trước: thủ tục nhập viện lúc 2h sáng thế nào, ai được vào phòng sinh, người nhà chờ ở đâu, đưa phong bì có phải "luật" không — những điều mẹ nào cũng thắc thỏm mà không bài chính thống nào dám viết; (2) Người chồng là NHÂN VẬT CHÍNH thứ hai: mỗi bài đều có phần việc cụ thể cho chồng (cầm giấy tờ gì, nói gì với bác sĩ, làm gì khi vợ đau), thay vì để anh ấy đứng ngoài hút thuốc chờ tin; (3) Chi phí minh bạch từng khoản theo cả hai kịch bản công - tư, thường - mổ, kèm cách dùng đúng BHYT và bảo hiểm thai sản — chủ đề mọi gia đình cần mà rất ít nơi viết tử tế, không PR cho viện nào.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Đón trọn lớp độc giả tuần 32-40 đang ở đỉnh lo lắng và nhu cầu tìm kiếm: "dấu hiệu sắp sinh", "giỏ đồ đi sinh cần những gì", "chi phí sinh ở bệnh viện X", "gây tê ngoài màng cứng có hại không", "vỡ ối bao lâu phải sinh". (2) Bài "chuẩn bị đồ đi sinh" và "chi phí đi sinh" là 2 bài trụ cột có khả năng được lưu (bookmark) và chia sẻ cho chồng/bà ngoại cao nhất site — tối ưu dạng checklist tải được, đo bằng tỷ lệ bookmark và lượt chia sẻ. (3) Là cầu nối chiến lược chuyển độc giả thai kỳ sang hệ sinh thái sau sinh: cuối mỗi bài dẫn sang Chăm sóc trẻ sơ sinh, Nuôi con bằng sữa mẹ — giữ được người đọc ở đúng khoảnh khắc họ "chuyển vai" thành cha mẹ. (4) Xây tin cậy bằng sự minh bạch chi phí — nội dung không viện nào tự viết về mình.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Gò cứng bụng cả tối — chuyển dạ thật hay gò sinh lý? Vào viện sớm sợ bị trả về, muộn thì sợ đẻ rơi"; "Vỡ ối mà chưa đau đẻ có nguy hiểm không, được chờ bao lâu?"; "Giỏ đồ đi sinh mạng liệt kê 40 món, mang đến nơi viện bảo thừa hết — cái gì CẦN THẬT?"; "Sinh viện công hết bao nhiêu, viện tư hết bao nhiêu, BHYT trái tuyến được bao nhiêu phần trăm — hỏi ai cũng ậm ờ"; "Có nên đăng ký gói sinh trọn gói 30-60 triệu không hay phí tiền?"; "Sợ đẻ đau mất ngủ cả tháng cuối — gây tê ngoài màng cứng nghe nói đau lưng suốt đời, thực hư?"; "Bác sĩ nói thai to khuyên mổ chủ động — mình muốn sinh thường, làm sao biết lời khuyên vì mình hay vì lịch của viện?"; "Chồng hỏi 'anh phải làm gì' mà mình cũng chẳng biết phân công gì ngoài đứng chờ"; "Có phải đưa phong bì cho ê-kíp không, đưa bao nhiêu, không đưa có bị làm khó không?"; "Sinh xong nằm viện mấy ngày, ai được vào chăm, bà nội hay bà ngoại — chưa sinh đã căng". Nền chung: nỗi sợ lớn nhất không phải cơn đau mà là sự KHÔNG BIẾT TRƯỚC — quy trình, chi phí, và việc phải ra quyết định y khoa trong phòng sinh khi không hiểu chuyện gì đang xảy ra.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Xem ngày giờ đẹp mổ đẻ" — cổ vũ can thiệp y khoa vì lý do phi y khoa (mổ chủ động sớm khi chưa đủ tuần có hại cho trẻ), không viết dạng hướng dẫn; chỉ viết bài phân tích rủi ro của trào lưu này. "Sinh thuận tự nhiên (lotus birth, sinh tại nhà không nhân viên y tế)" — trào lưu nguy hiểm đã gây tử vong tại Việt Nam, tuyệt đối không viết dạng trung lập "hai mặt"; chỉ viết bài cảnh báo có dẫn chứng. "Review xếp hạng bệnh viện phụ sản tốt nhất" — dễ thành PR và gây war hội nhóm; thay bằng khung "tiêu chí tự chọn viện phù hợp" + bài chi phí minh bạch theo LOẠI hình. "Kể chuyện đi đẻ kinh dị" dạng giật gân — tăng nỗi sợ cho mẹ sắp sinh, đi ngược mục tiêu trấn an bằng hiểu biết. "Hướng dẫn tự đỡ đẻ khẩn cấp" — vượt phạm vi an toàn của nội dung không phải y tế; chỉ dừng ở "gọi 115 và làm gì trong lúc chờ".
                TEXT,

            'audience' => 'Mẹ bầu Việt tuần 30-40 thai kỳ (đỉnh đọc ở tuần 34-38) và người chồng lần đầu làm bố — nhóm hiếm hoi mà đàn ông chủ động tìm đọc; sống thành thị/ven đô, đang phải chốt các quyết định lớn: sinh ở đâu, chuẩn bị gì, bao nhiêu tiền; đọc kỹ, lưu bài, chia sẻ cho nhau và cho ông bà; trạng thái đặc trưng: hồi hộp đếm ngược, sợ cơn đau và sợ nhất là "không biết trước điều gì".',

            'constraints' => 'Không kể chuyện sinh nở giật gân gây sợ; không PR bệnh viện, gói sinh; chi phí nêu theo khoảng và ghi rõ thời điểm tham khảo, tránh con số tuyệt đối lỗi thời; không phán xét lựa chọn sinh thường/mổ, có/không gây tê; thông tin y khoa dẫn nguồn và luôn nhắc quyết định cuối thuộc về bác sĩ trực tiếp; chủ đề "phong bì" viết trung thực nhưng không cổ xúy.',

            'style_sample' => <<<'TEXT'
                2 giờ sáng, bụng bạn gò cứng lại lần thứ tư trong một tiếng, và cả nhà đang họp khẩn quanh giường: mẹ chồng giục đi viện ngay, chồng lóng ngóng cầm chìa khóa xe, còn bạn thì kẹt giữa hai nỗi sợ — vào sớm quá bị trả về "chưa mở phân nào", mà chờ thêm thì sợ không kịp. Bình tĩnh nào, có một quy tắc đơn giản được các nữ hộ sinh tin dùng để bạn tự kiểm tra ngay trên giường: quy tắc 5-1-1. Cơn gò cách nhau 5 phút, mỗi cơn kéo dài 1 phút, và nhịp điệu ấy đã duy trì được 1 tiếng — đó là lúc xách giỏ đồ lên xe. Còn nếu cơn gò lúc 10 phút lúc 20 phút, lúc mạnh lúc nhẹ, và dịu đi khi bạn đổi tư thế hay uống cốc nước ấm — nhiều khả năng đây là gò Braxton Hicks, kiểu "diễn tập" quen thuộc của tử cung ở những tuần cuối. Tất nhiên, quy tắc nào cũng có ngoại lệ phải nhớ: ra máu đỏ tươi, vỡ ối (dù chưa đau), hoặc thai máy yếu hẳn đi — ba tình huống này thì không đếm không chờ gì nữa, đi viện ngay. Giờ mình đi qua từng dấu hiệu một cách chi tiết nhé, kèm phần việc rất cụ thể cho anh chồng đang cầm chìa khóa xe kia.
                TEXT,
        ],

        // === Trẻ sơ sinh (0-3 tháng) — danh mục cha, bài pillar tổng quan giai đoạn ===
        [
            'parent_slug' => null,
            'slug'        => 'tre-so-sinh-0-3-thang',

            'writer_insights' => <<<'TEXT'
                - Đây là danh mục CHA — chỉ bài TỔNG QUAN xuyên suốt 0-3 tháng, dẫn vào 5 chuyên mục con. KHÔNG viết chi tiết tắm/rốn/bú/ngủ/bệnh ở đây.
                - Vai trò: "mục lục sống" giúp cha mẹ mới biết vấn đề của mình thuộc mảng nào, đọc gì trước.
                - 3 mảng CHỈ chuyên mục cha này viết (không chuyên mục con nào ôm): phân công vợ/chồng/ông bà theo ca, thủ tục hành chính (khai sinh, BHYT), sức khỏe tinh thần CẢ BỐ lẫn mẹ.
                - Giọng "đồng đội cùng trực đêm" — thừa nhận đây là chặng khó nhất đời làm cha mẹ, không tô hồng thiên chức, không phán xét mẹ kiệt sức.
                - KPI chính: CTR từ bài pillar xuống đúng 5 chuyên mục con theo nhu cầu (hành vi/phát triển/bú/ngủ/bệnh).
                TEXT,

            'core_focus' => <<<'TEXT'
                Danh mục CHA của cụm 0-3 tháng — chỉ chứa các bài TỔNG QUAN xuyên suốt giai đoạn, không đi sâu vào mảng đã có chuyên mục con: cẩm nang "sống sót" 3 tháng đầu cho cha mẹ mới (tuần đầu về nhà, nhịp một ngày với trẻ sơ sinh, phân công ca kíp vợ chồng - ông bà), lịch tổng hợp các mốc quan trọng (tiêm chủng, khám sau sinh của mẹ và bé, làm giấy khai sinh - BHYT cho con), sức khỏe tinh thần cha mẹ mới (baby blues, trầm cảm sau sinh — nhận biết và tìm trợ giúp), kiêng cữ ở cữ hiện đại (cái gì giữ, cái gì bỏ, thương lượng với ông bà), và bài định hướng "con 0-3 tháng: cần đọc gì, khi nào" dẫn vào 5 chuyên mục con (Chăm sóc trẻ sơ sinh, Phát triển của trẻ, Nuôi con bằng sữa mẹ, Giấc ngủ của bé, Bệnh thường gặp). Chi tiết tắm - rốn - bú - ngủ - bệnh KHÔNG viết ở đây — đẩy xuống đúng chuyên mục con.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Giai đoạn 0-3 tháng là lúc cha mẹ Việt bị "ngợp" nhất: kiến thức rời rạc từ trăm nguồn, mẹ kiệt sức sau sinh, ông bà can thiệp dày đặc nhất. Vai trò riêng của danh mục cha: (1) Là "mục lục sống" — giúp cha mẹ mới biết vấn đề của mình thuộc mảng nào và đọc gì trước, thay vì trôi dạt giữa hội nhóm lúc 3h sáng; (2) Những bài xuyên suốt mà không chuyên mục con nào ôm được: phân công vợ - chồng - ông bà theo ca, thủ tục hành chính cho bé (khai sinh, BHYT, nhập hộ khẩu — bài cực hữu ích không site mẹ-bé nào làm tử tế), sức khỏe tinh thần của CẢ BỐ lẫn mẹ; (3) Giọng "đồng đội cùng trực đêm": thừa nhận 3 tháng đầu là chặng khó nhất đời làm cha mẹ, không tô hồng thiên chức, không phán xét mẹ kiệt sức.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Bài pillar "Cẩm nang 3 tháng đầu" và "Lịch mốc quan trọng 0-3 tháng" làm điểm đổ về của mọi liên kết nội bộ trong cụm, đón độc giả chuyển tiếp từ Chuyển dạ & đi sinh. (2) SEO truy vấn tổng quan: "chăm trẻ sơ sinh 3 tháng đầu", "những việc cần làm khi mới sinh con", "làm giấy khai sinh cho con cần gì", "trầm cảm sau sinh". (3) Điều phối luồng đọc xuống 5 chuyên mục con đúng nhu cầu — đo bằng CTR từ bài pillar sang bài con. (4) Giữ chân độc giả đã theo site từ thai kỳ tiếp tục sang giai đoạn 3-12 tháng — mắt xích trong chuỗi hành trình cha mẹ mà site theo đuổi.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Ngày đầu bế con về nhà, hai vợ chồng nhìn nhau: giờ làm gì tiếp?"; "Mọi người ai cũng khuyên một kiểu — mẹ đẻ bảo thế này, mẹ chồng bảo thế kia, hội nhóm nói khác hẳn, mình như quả bóng bị đá qua lại"; "Đêm con dậy 4-5 lần, hai vợ chồng đều đi làm lại sau 1-6 tháng — chia ca kiểu gì để không gục?"; "Mình khóc suốt tuần thứ hai sau sinh, không hiểu sao — mình tệ hay mình ốm?"; "Ở cữ có phải kiêng tắm gội, kiêng ra gió cả tháng như bà bắt không?"; "Bao giờ phải làm giấy khai sinh, BHYT cho con, cần giấy tờ gì, ai đi làm được?"; "Tháng đầu con phải khám lại những mốc nào, tiêm gì, quên mất thì sao?"; "Khách đến thăm liên tục, ai cũng đòi bế hôn con — từ chối sao cho khéo?"; "Chồng muốn giúp mà không biết làm gì, mình thì kiệt sức nhưng không yên tâm giao con". Nền chung: quá tải thông tin + thiếu ngủ trầm trọng + áp lực làm đúng ngay lập tức với một sinh linh bé xíu — cha mẹ cần được cầm tay chỉ việc và được nghe câu "bạn đang làm tốt rồi".
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Cách tắm bé, chăm rốn, cắt móng tay" chi tiết — thuộc chuyên mục con Chăm sóc trẻ sơ sinh, viết ở đây gây trùng nội bộ. "Luyện ngủ, cữ bú, sữa mẹ - sữa công thức" — thuộc Giấc ngủ của bé và Nuôi con bằng sữa mẹ. "Vàng da, sốt, nghẹt mũi ở trẻ sơ sinh" — thuộc Bệnh thường gặp (0-3 tháng). "Đồ sơ sinh cần mua trọn bộ 60 món" dạng liệt kê mua sắm — dễ thành bài affiliate và gây tốn kém không cần thiết cho cha mẹ mới; khía cạnh đánh giá đồ dùng cụ thể để danh mục Đánh giá sản phẩm làm. "Ở cữ theo chuẩn cung đình/truyền thống 100 điều kiêng" — cổ vũ kiêng cữ cực đoan phản khoa học (kiêng tắm cả tháng gây nhiễm trùng); chỉ viết dạng đối chiếu khoa học - truyền thống có chọn lọc. "So sánh dịch vụ chăm sóc mẹ và bé sau sinh (bảo mẫu, ở cữ trọn gói)" — thị trường loạn giá, dễ PR trá hình; chỉ viết khung tiêu chí lựa chọn, không xếp hạng đơn vị.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-35 tuổi vừa đón con đầu lòng 0-3 tháng, đang nghỉ thai sản (mẹ) hoặc vừa đi làm lại (bố), sống thành thị/ven đô, thường có bà nội/bà ngoại đến ở cùng chăm cữ; thiếu ngủ nặng, đọc điện thoại một tay lúc bế con ti đêm 2-4h sáng; cần câu trả lời NGẮN - RÕ - LÀM ĐƯỢC NGAY và sự trấn an rằng mình không phải cha mẹ tồi.',

            'constraints' => 'Không tô hồng "thiên chức làm mẹ", không phán xét mẹ kiệt sức hay cho con bú bình; không gieo cảm giác tội lỗi; bài phải đọc nhanh được lúc thiếu ngủ (đoạn ngắn, kết luận trước); không chê bai kiêng cữ truyền thống bằng giọng bề trên; thông tin y tế - hành chính phải cập nhật, dẫn nguồn Bộ Y tế/quy định hiện hành; luôn có mục "khi nào cần trợ giúp chuyên môn".',

            'style_sample' => <<<'TEXT'
                Có một sự thật mà chẳng ai nói với bạn trước khi sinh: khoảnh khắc bế con rời bệnh viện về đến cửa nhà, cảm giác đầu tiên của rất nhiều cha mẹ không phải hạnh phúc vỡ òa — mà là hoang mang. "Ơ, thế… giờ làm gì tiếp?". Không còn nữ hộ sinh nào để bấm chuông gọi, chỉ còn hai người lớn thiếu ngủ và một em bé ba ngày tuổi. Nếu bạn đang ở đúng khoảnh khắc ấy, bài viết này chính là "nữ hộ sinh" mà bạn muốn bấm chuông. Chúng ta sẽ không học mọi thứ về nuôi con hôm nay — không ai làm được thế cả. Chúng ta chỉ cần nắm được nhịp của 24 giờ đầu tiên ở nhà: con cần gì (thật ra chỉ xoay quanh bú - ngủ - tã - và được ôm), mẹ cần gì (một bữa ăn nóng, một giấc ngủ bù, và không phải tiếp khách), bố làm gì (rất nhiều việc, mình liệt kê cụ thể bên dưới), và cả nhà thống nhất với ông bà ra sao để tuần đầu tiên không thành cuộc chiến quan điểm. Còn những chuyện lớn hơn — tắm bé thế nào, ngủ xuyên đêm ra sao, tiêm phòng mốc nào — mỗi thứ đã có một bài riêng chi tiết, mình đặt link ở đúng chỗ bạn cần trong bài.
                TEXT,
        ],

        // === Trẻ sơ sinh (0-3 tháng) > Chăm sóc trẻ sơ sinh ===
        [
            'parent_slug' => 'tre-so-sinh-0-3-thang',
            'slug'        => 'cham-soc-tre-so-sinh',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: kỹ năng chăm sóc HẰNG NGÀY 0-3 tháng — tắm, rốn, thay tã, bế/ẵm, quấn khăn, ngủ an toàn (SIDS), mốc y tế thường quy.
                - KHÔNG viết: bú mẹ/sữa công thức (→ Nuôi con bằng sữa mẹ), lịch ngủ/luyện ngủ (→ Giấc ngủ của bé), bệnh lý (→ Bệnh thường gặp).
                - Khác biệt bắt buộc giữ: hướng dẫn tới mức THAO TÁC (đỡ gáy tay nào, xả nước bên nào trước) + xử lý trực diện độ vênh khoa học vs cách ông bà làm.
                - Mẹo dân gian nguy hiểm (mật ong rơ lưỡi, đắp lá rốn) phải cảnh báo thẳng bằng dẫn chứng, không chỉ liệt kê.
                - Chuẩn SIDS/tiêm chủng lấy theo WHO/Bộ Y tế, ghi rõ nguồn — tạo thế đứng vững trước lời khuyên truyền miệng.
                - KPI: đo bằng tỷ lệ mở lại bài trong tháng đầu (bookmark) và tỷ lệ chia sẻ trong hội nhóm cho nội dung "khoa học vs ông bà".
                - Ví dụ/kịch bản nên có cả bố trực tiếp tắm bé, cắt móng tay, quấn khăn — không mặc định chỉ mẹ làm dù mẹ đang ở cữ và làm nhiều hơn.
                TEXT,

            'core_focus' => <<<'TEXT'
                Kỹ năng chăm sóc HẰNG NGÀY cho bé 0-3 tháng, dạy từng bước như có nữ hộ sinh đứng cạnh: tắm bé và vệ sinh (tắm mấy lần/tuần, nhiệt độ nước, trình tự tắm an toàn, vệ sinh mắt - mũi - tai - vùng kín bé trai/bé gái), chăm sóc rốn đến khi rụng và dấu hiệu nhiễm trùng rốn, thay tã và chăm da (hăm tã, rôm sảy, cứt trâu, mụn sữa, vàng da sinh lý nhận biết ban đầu), bế - ẵm - vỗ ợ hơi đúng cách, quấn khăn, cắt móng tay, mặc ấm đúng chuẩn (nguyên tắc hơn người lớn 1 lớp — chống lại thói quen ủ quá kỹ), môi trường an toàn (nhiệt độ phòng, nằm điều hòa, ngủ an toàn chống đột tử SIDS: nằm ngửa, cũi thoáng, không gối chăn mềm), massage cho bé, và các mốc chăm sóc y tế thường quy (tiêm chủng tháng đầu, vitamin D3, sàng lọc sơ sinh). KHÔNG lấn sân: bú mẹ/sữa công thức (thuộc Nuôi con bằng sữa mẹ), lịch ngủ - luyện ngủ (thuộc Giấc ngủ của bé), bệnh lý (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Cha mẹ mới không thiếu bài "cách tắm bé" — họ thiếu bài dạy như dạy người CHƯA TỪNG BẾ TRẺ SƠ SINH, và xử lý được xung đột thực tế trong nhà. Khác biệt: (1) Hướng dẫn chi tiết đến mức thao tác (đỡ gáy bằng tay nào, xả nước bên nào trước) kèm lỗi người mới hay mắc ở từng bước — vì người đọc đang run tay thật; (2) Mỗi chủ đề đều xử lý trực diện "độ vênh" giữa khoa học hiện đại và cách ông bà làm: ủ ấm quá kỹ, rơ lưỡi bằng mật ong (nguy cơ ngộ độc botulinum — phải nói thẳng), nặn mụn sữa, đắp lá lên rốn, kiêng tắm — giải thích gốc quan niệm và đưa "kịch bản thương lượng" với ông bà thay vì chỉ phán đúng sai; (3) Chuẩn an toàn giấc ngủ SIDS và chuẩn tiêm chủng lấy theo khuyến cáo WHO/Bộ Y tế, nói rõ nguồn — tạo thế đứng vững trước lời khuyên truyền miệng.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn kỹ năng đầu đời: "cách tắm trẻ sơ sinh", "trẻ sơ sinh nằm điều hòa bao nhiêu độ", "chăm sóc rốn trẻ sơ sinh", "trị hăm tã", "quấn khăn cho trẻ sơ sinh đúng cách" — truy vấn ổn định quanh năm, đối thủ chủ yếu là bài bệnh viện khô cứng thiếu thao tác chi tiết. (2) Trở thành chuỗi bài cha mẹ mở đi mở lại trong tháng đầu — cấu trúc bài dạng các bước đánh số + ảnh minh họa để giữa đêm vẫn tra nhanh được, đo bằng tỷ lệ bookmark và số lần quay lại cùng 1 bài trong tháng đầu. (3) Nội dung "khoa học vs ông bà" tạo khác biệt, đo bằng tỷ lệ chia sẻ trong hội nhóm. (4) Liên kết chặt trong cụm 0-3 tháng: bài tắm bé dẫn sang vàng da (Bệnh thường gặp), bài ngủ an toàn dẫn sang Giấc ngủ của bé — tăng chiều sâu phiên đọc.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Lần đầu tắm con mà tay run, sợ tuột, sợ nước vào tai — trình tự chuẩn là gì?"; "Rốn con 10 ngày chưa rụng, hơi ướt ở chân rốn — bình thường hay nhiễm trùng?"; "Bà bắt ủ con 3 lớp áo + quấn chăn giữa mùa hè, sờ lưng con toàn mồ hôi — nói bà không nghe"; "Con nằm điều hòa được không, bao nhiêu độ, có cần đội mũ đi tất không?"; "Da con nổi mụn trắng li ti, má sần đỏ, đầu đóng vảy vàng — có phải dị ứng sữa không, bôi gì được?"; "Hăm đỏ cả vùng mặc tã, bà bảo xức phấn rôm, mạng bảo phấn rôm hại phổi — tin ai?"; "Bà đòi rơ lưỡi cho con bằng mật ong/lá hẹ — nghe nói nguy hiểm mà không biết giải thích sao"; "Cắt móng tay cho con mà sợ phạm vào thịt"; "Con hay vặn mình đỏ mặt — thiếu canxi như bà nói hay bình thường?"; "Quên chưa cho con uống vitamin D3 mấy hôm có sao không?"; "Nhà nội bảo phải nằm than cho ấm — mình biết độc mà không cản được". Nền chung: mỗi thao tác đều là lần đầu, làm dưới ánh mắt giám sát của ông bà, và mọi vết đỏ trên da con đều thành nỗi sợ lúc nửa đêm.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Mẹo dân gian chữa X cho trẻ sơ sinh (đắp lá, nặn sữa vào mắt, mật ong rơ lưỡi, nằm than)" dạng hướng dẫn — nguy hiểm thực tế đã có ca ngộ độc/bỏng/nhiễm trùng; chỉ viết dạng bài cảnh báo giải thích cơ chế rủi ro. "Trọn bộ 60 món đồ sơ sinh phải mua" — bài mua sắm affiliate, gây lãng phí; khía cạnh sản phẩm cụ thể thuộc danh mục Đánh giá sản phẩm. "Luyện ngủ cho bé từ tuần đầu" — thuộc Giấc ngủ của bé, không viết ở đây. "Cách chữa vàng da/sốt/nghẹt mũi" — thuộc Bệnh thường gặp; chuyên mục này chỉ dừng ở nhận biết ban đầu và chỉ dấu đi khám. "Phương pháp EASY/4S/5S trọn bộ" dạng tôn sùng một trường phái — cộng đồng mẹ Việt đang chia phe gay gắt; chỉ lấy kỹ thuật cụ thể có bằng chứng (vỗ ợ, quấn khăn, white noise) trình bày trung lập, không gắn nhãn trường phái để tránh war và tránh bó người đọc vào giáo điều.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-35 tuổi có con đầu lòng 0-3 tháng, chưa từng chăm trẻ sơ sinh trước đó — cả bố lẫn mẹ đều có thể là người trực tiếp tắm bé/cắt móng/thay tã lần đầu, không chỉ mẹ (mẹ thường ở cữ tại nhà cả ngày nên có bà nội/ngoại cùng chăm và hay bất đồng cách làm, bố trực tiếp làm nhiều hơn vào buổi tối và cuối tuần); tra cứu bằng điện thoại một tay ngay TRƯỚC KHI làm thao tác (sắp tắm bé, sắp cắt móng) hoặc giữa đêm khi thấy dấu hiệu lạ trên da, rốn, hơi thở của con.',

            'constraints' => 'Không dùng từ chuyên môn không giải thích; hướng dẫn phải chia bước đánh số làm theo được ngay, nêu rõ lỗi thường gặp; không phán xét hay chế giễu cách chăm truyền thống — phân tích và đưa cách nói chuyện với ông bà; mẹo dân gian nguy hiểm phải cảnh báo thẳng, dẫn nguồn y khoa (Bộ Y tế, WHO, AAP); luôn có ngưỡng "dấu hiệu cần đi khám"; không quảng cáo sản phẩm chăm sóc da, sữa tắm.',

            'style_sample' => <<<'TEXT'
                Hôm nay là ngày đầu tiên bạn tự tắm cho con, không còn cô hộ sinh nào đứng cạnh. Tay bạn hơi run — và điều đó hoàn toàn bình thường: một em bé ba tuần tuổi trơn như cá khi dính nước, ai lần đầu cũng sợ. Nhưng có một bí mật khiến mọi thứ dễ hơn hẳn: trẻ sơ sinh không hề bẩn như ta tưởng. Con chưa nghịch cát, chưa đổ mồ hôi chua — nên 2-3 lần tắm mỗi tuần là đủ, những ngày còn lại chỉ cần lau người, và mỗi lần tắm chỉ cần gọn trong 5-7 phút. Nghĩa là áp lực "tắm cho sạch, tắm cho lâu" mà bà đang đứng cạnh nhắc bạn — có thể buông bớt được rồi. Việc của mình bây giờ chỉ là làm đúng trình tự an toàn: chuẩn bị sẵn mọi thứ trong tầm với TRƯỚC khi cởi đồ con (khăn, quần áo, tã — vì bạn sẽ không có tay nào rảnh nữa), nước ấm 37 độ thử bằng khuỷu tay, rửa mặt trước - gội đầu sau - thân mình cuối cùng, và một tay LUÔN đỡ dưới gáy con từ đầu đến cuối. Giờ mình đi từng bước một nhé, có cả phần "nếu con khóc giữa chừng thì sao" — vì gần như chắc chắn con sẽ khóc, và điều đó cũng bình thường nốt.
                TEXT,
        ],

        // === Trẻ sơ sinh (0-3 tháng) > Phát triển của trẻ ===
        [
            'parent_slug' => 'tre-so-sinh-0-3-thang',
            'slug'        => 'phat-trien-cua-tre',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: mốc vận động/giác quan/giao tiếp 0-3 tháng, đọc biểu đồ tăng trưởng percentile, tummy time, ngôn ngữ khóc của trẻ, wonder weeks, red flags.
                - KHÔNG viết: kỹ năng chăm sóc hằng ngày (→ Chăm sóc trẻ sơ sinh), bú/ngủ/bệnh (→ 3 chuyên mục con còn lại).
                - Nguyên tắc xuyên suốt: mọi mốc là KHOẢNG, không phải deadline — chống văn hóa so sánh cân nặng/mốc giữa các gia đình Việt.
                - Dạy đọc percentile trong sổ khám (đường cong của CHÍNH con, không so hàng xóm) — nội dung giáo dục nền tảng ít nơi làm kỹ.
                - Hoạt động gợi ý phải rẻ/miễn phí bằng đồ có sẵn trong nhà — không bán đồ chơi giáo dục.
                - KPI: đo bằng tỷ lệ quay lại theo chuỗi "Bé N tuần/tháng tuổi" và tỷ lệ giảm câu hỏi trùng lặp hỏi hội nhóm.
                TEXT,

            'core_focus' => <<<'TEXT'
                Sự phát triển của bé 0-3 tháng theo từng tuần/tháng và cách cha mẹ đồng hành đúng lứa tuổi: các mốc vận động - giác quan - giao tiếp (khi nào con nhìn theo, hóng chuyện, cười xã giao, ngóc đầu, phát hiện ra bàn tay mình), tăng trưởng cân nặng - chiều cao theo chuẩn WHO và CÁCH ĐỌC biểu đồ percentile (con ở kênh 25% vẫn bình thường — chống lại văn hóa so cân nặng), thời gian nằm sấp (tummy time) tập cổ an toàn, kích thích giác quan đúng cách (nói chuyện, hát, tranh tương phản đen trắng — không cần đồ chơi đắt tiền), hiểu ngôn ngữ của trẻ sơ sinh (các kiểu khóc, tín hiệu đói - buồn ngủ - quá tải kích thích), giai đoạn wonder weeks/growth spurt khiến con bám mẹ gắt gỏng, và ranh giới bình thường - cần theo dõi (khi nào đáng đưa đi khám: không nhìn theo, không phản ứng âm thanh, trương lực cơ bất thường). KHÔNG lấn sân: kỹ năng chăm sóc hằng ngày (thuộc Chăm sóc trẻ sơ sinh), bú - ngủ - bệnh (các chuyên mục con còn lại).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Câu cửa miệng của cha mẹ Việt ở buổi tiêm phòng không phải "con phát triển thế nào" mà là "con em mấy cân rồi, có bằng con chị không" — mốc phát triển ở Việt Nam bị dùng như thước đo ganh đua giữa các gia đình chứ không phải công cụ theo dõi sức khỏe. Khác biệt: (1) Mỗi mốc luôn trình bày kèm KHOẢNG dao động bình thường và triết lý "mốc là khoảng, không phải deadline" — giảm lo âu thay vì tạo thêm; đồng thời nói rõ ngưỡng nào mới thật sự cần đi khám (red flags theo AAP), không ba phải; (2) Đánh thẳng vào văn hóa so sánh cân nặng của người Việt — "con em 3 tháng 6kg có còi không chị?" — bằng cách dạy đọc percentile và đường cong tăng trưởng của CHÍNH con thay vì so hàng xóm; (3) Mục "chơi với con tuần này" dùng đồ có sẵn trong nhà Việt (khăn xô, chai nhựa bỏ gạo) thay vì danh sách đồ chơi giáo dục đắt tiền — hoạt động 5-10 phút vừa sức cha mẹ đang thiếu ngủ.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Chuỗi bài trụ cột "Bé N tuần/tháng tuổi" (0-12 tuần) kéo cha mẹ quay lại định kỳ như đã làm với chuỗi tuần thai — chuyển tiếp tự nhiên cho độc giả từ Sự phát triển của thai nhi, đo bằng tỷ lệ quay lại theo tuần/tháng tuổi con. (2) SEO truy vấn lo âu so sánh: "trẻ 2 tháng biết làm gì", "trẻ 3 tháng chưa biết lẫy có sao không", "bảng cân nặng trẻ sơ sinh chuẩn WHO", "trẻ mấy tháng biết hóng chuyện", "tummy time là gì". (3) Giảm nhu cầu hỏi hội nhóm bằng bài "đọc biểu đồ tăng trưởng" — nội dung giáo dục nền tảng ít ai làm kỹ, đo bằng thời gian đọc trung bình của bài này. (4) Dẫn luồng sang Bệnh thường gặp khi chạm red flags và sang chuyên mục 3-12 tháng khi con qua mốc — đo bằng CTR sang 2 chuyên mục đó, giữ độc giả trong hành trình dài của site.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con 2 tháng chưa cười thành tiếng trong khi con bạn cùng tháng đã hóng chuyện — con mình chậm à?"; "Mỗi lần đi tiêm phòng cân con là một lần áp lực: bà so với cháu hàng xóm, hội nhóm hỏi sao còi thế"; "Biểu đồ tăng trưởng ở sổ khám ghi kênh 15% — bác sĩ bảo bình thường mà mình vẫn hoang mang, 15% nghĩa là gì?"; "Tummy time con khóc ré lên sau 30 giây — có nên ép không, tập thế nào?"; "Con nhìn mãi cái quạt trần, có phải dấu hiệu tự kỷ như mạng đồn không?"; "Tuần này con tự nhiên gắt gỏng bám mẹ cả ngày, bú vặt liên tục — con làm sao vậy?"; "Mắt con thỉnh thoảng hơi lác trong — bình thường hay phải khám?"; "Con vặn mình, giật mình khi ngủ — bà bảo thiếu canxi đòi mua canxi cho uống"; "Muốn kích thích con thông minh sớm mà chỉ thấy quảng cáo thẻ học flashcard, đồ chơi Montessori tiền triệu — có cần thật không?". Nền chung: cha mẹ Việt bị kẹt giữa văn hóa so sánh con nhà người ta (từ chính ông bà, hàng xóm) và nỗi sợ bỏ lỡ dấu hiệu bất thường — cần vừa được trấn an có cơ sở, vừa được chỉ rõ khi nào lo là đúng.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Dấu hiệu trẻ sơ sinh thông minh/IQ cao" — câu view phản khoa học, nuôi văn hóa so sánh mà chuyên mục đang chống lại. "Kích não sớm, flashcard Glenn Doman cho trẻ 0-3 tháng" — phương pháp gây tranh cãi, không bằng chứng, bản chất là phễu bán khóa học/bộ thẻ; chỉ viết bài phân tích trung lập nếu cần phản biện. "Bảng cân nặng chuẩn theo NGÀY tuổi" — không có cơ sở y khoa, khuếch đại lo âu; chỉ dùng chuẩn WHO theo tuần/tháng kèm percentile. "Trẻ vặn mình là thiếu canxi — cách bổ sung" — quan niệm sai phổ biến, viết ngược lại (bài giải oan cho vặn mình sinh lý và cảnh báo tự ý bổ sung canxi) chứ không viết xuôi. "So sánh mốc phát triển bé trai vs bé gái tháng đầu" — khác biệt không có ý nghĩa thực tiễn ở tuổi này, chỉ thêm cớ so sánh. "Chẩn đoán tự kỷ qua dấu hiệu 0-3 tháng" — chưa thể chẩn đoán ở tuổi này, bài kiểu này gieo hoảng loạn; chỉ nêu red flags chuẩn AAP kèm khuyến nghị khám phát triển.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-35 tuổi có con đầu lòng 0-3 tháng, học vấn khá, ham tìm hiểu nhưng đang bơi giữa chuẩn Tây (CDC, AAP) và chuẩn "hàng xóm" (cân nặng, ngoan - hư); mẹ nghỉ thai sản đọc ban ngày khi con ngủ, hay chụp màn hình gửi nhóm chat hỏi "con em thế có sao không"; nhạy cảm cao độ với mọi so sánh và rất dễ mua đồ/khóa học vì sợ con thua thiệt.',

            'constraints' => 'Mọi mốc phải ghi KHOẢNG bình thường, cấm giọng deadline; không nội dung kích thích so sánh hay "dấu hiệu thần đồng"; red flags nêu điềm tĩnh kèm bước hành động cụ thể, không bỏ lửng gây hoảng; hoạt động gợi ý phải rẻ/miễn phí, 5-10 phút; số liệu theo WHO/AAP có dẫn nguồn; không bán hay gợi ý đồ chơi giáo dục thương mại; không dùng từ "còi", "vượt trội" khi nói về cân nặng.',

            'style_sample' => <<<'TEXT'
                Ở buổi tiêm phòng 2 tháng hôm nay, có thể bạn đã trải qua khoảnh khắc quen thuộc này: cô y tá đọc "5 cân 8", và ngay lập tức trong đầu bạn bật ra phép so sánh với bé nhà chị đồng nghiệp — "con bé ấy 2 tháng đã 6 cân rưỡi". Trước khi nỗi lo kịp lớn thêm, mình muốn chỉ cho bạn một thứ đáng nhìn hơn con số ấy nhiều: cái ĐƯỜNG CONG trong sổ khám của con. Cân nặng của một em bé không phải bài thi để đạt điểm chuẩn — nó là một hành trình, và thứ bác sĩ thật sự quan tâm không phải hôm nay con nặng bao nhiêu, mà là con có đang đi ĐỀU trên làn đường của chính mình không. Một em bé ở kênh 25% mà tháng nào cũng bám đều kênh 25% là một em bé đang lớn hoàn toàn khỏe mạnh — trong khi một em bé kênh 90% mà đột ngột rơi xuống 50% mới là em bé cần được xem xét. Nói cách khác: con bạn không thi đấu với con chị đồng nghiệp; con chỉ đang chạy trên làn của riêng mình. Trong bài này, mình sẽ hướng dẫn bạn đọc biểu đồ percentile trong sổ của con — 5 phút thôi — để từ buổi cân sau, bạn nhìn con số bằng con mắt của bác sĩ chứ không phải bằng nỗi lo của hàng xóm.
                TEXT,
        ],

        // === Trẻ sơ sinh (0-3 tháng) > Nuôi con bằng sữa mẹ ===
        [
            'parent_slug' => 'tre-so-sinh-0-3-thang',
            'slug'        => 'nuoi-con-bang-sua-me',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: khớp ngậm, gọi sữa, nhận biết bú ĐỦ, tắc tia/viêm vú, kích/hút sữa cho mẹ đi làm lại, VÀ sữa công thức khi mẹ không đủ sữa — không phán xét.
                - KHÔNG viết: lịch ngủ (→ Giấc ngủ của bé), bệnh của bé (→ Bệnh thường gặp).
                - Khác biệt đã làm tốt, GIỮ NGUYÊN khung này: đứng về phía NGƯỜI MẸ giữa phe "sữa mẹ bằng mọi giá" và phe quảng cáo sữa công thức — không lặp khung này ở chuyên mục khác.
                - Trọng tâm giải quyết "khủng hoảng ảo giác ít sữa" bằng đếm tã/theo dõi cân — không nghe cảm nhận chủ quan của ông bà.
                - Thực chiến cho mẹ đi làm lại sau 6 tháng: lịch hút sữa văn phòng, quyền vắt sữa theo luật lao động (nối Quyền lợi & pháp lý).
                - KPI: đo bằng tỷ lệ mẹ vượt qua từng khủng hoảng sữa theo tuần (đọc hết chuỗi bài theo giai đoạn) và tỷ lệ chia sẻ hội nhóm.
                - Có phần việc CỤ THỂ cho người bố ở mỗi bài (mang nước, đổi ca dậy đêm để mẹ chợp mắt, đứng ra chắn lời nhận xét của ông bà) — không để bố đứng ngoài cuộc chỉ vì đây là "chuyện sữa của mẹ".
                TEXT,

            'core_focus' => <<<'TEXT'
                Đồng hành thực tế với hành trình sữa mẹ từ cữ bú đầu tiên: khớp ngậm đúng và các tư thế cho bú (kèm cách nhận biết - sửa khớp ngậm sai gây đau nứt đầu ti), cơ chế cung - cầu của sữa mẹ và cách gọi sữa về sau sinh (đặc biệt sau sinh mổ), nhận biết con bú ĐỦ (số tã ướt, cân nặng — thay cho cảm giác "hình như ít sữa" khiến 70% mẹ bỏ cuộc oan), xử lý sự cố: cương sữa, tắc tia (mẹo chườm - massage đúng, khi nào cần thông tia), nứt đầu ti, viêm vú, con chê ti - bú vặt - gắt bú, kích sữa và hút sữa đúng cách (chọn chế độ, lịch hút cho mẹ đi làm lại, bảo quản - rã đông sữa chuẩn), ăn uống của mẹ cho bú (thực hư móng giò - chè vằng - lá đinh lăng, mẹ ăn gì con đau bụng?), cai sữa văn minh, và phần KHÔNG PHÁN XÉT: khi mẹ không đủ sữa hoặc chọn sữa công thức — cách kết hợp, cách chọn, cách pha đúng. KHÔNG lấn sân: lịch ngủ (Giấc ngủ của bé), bệnh của bé (Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Mảng sữa mẹ tiếng Việt bị chia đôi chiến tuyến: phe "sữa mẹ bằng mọi giá" phán xét mẹ bú bình, và phe quảng cáo sữa công thức rình mẹ yếu lòng. Chuyên mục đứng hẳn về phía NGƯỜI MẸ: (1) Ủng hộ sữa mẹ hết mình về mặt kỹ thuật (bài khớp ngậm, gọi sữa, kích sữa chi tiết nhất có thể) nhưng tuyệt đối không tội lỗi hóa mẹ thiếu sữa — có hẳn tuyến bài "nuôi con bằng sữa công thức không phải thất bại"; (2) Đánh trúng "khủng hoảng ảo giác ít sữa" — nguyên nhân số 1 khiến mẹ Việt bỏ bú mẹ sớm: dạy đếm tã, theo dõi cân thay vì nghe bà nội phán "sữa mày trong, nóng, con bú không no"; (3) Thực chiến cho mẹ công sở đi làm lại sau 6 tháng thai sản — lịch hút sữa tại văn phòng, quyền vắt sữa theo luật lao động (nối sang Quyền lợi & pháp lý), trữ đông - vận chuyển; kịch bản gần như mọi mẹ Việt gặp mà bài dịch từ Tây không cover.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn khủng và cảm xúc cao: "cách gọi sữa về nhanh", "tắc tia sữa phải làm sao", "khớp ngậm đúng", "trẻ sơ sinh bú bao nhiêu là đủ", "bảo quản sữa mẹ được bao lâu", "mẹ ăn gì để nhiều sữa". (2) Giữ chân mẹ qua từng khủng hoảng sữa (tuần 1: gọi sữa; tuần 3-6: ảo giác ít sữa; tháng 5-6: đi làm lại) bằng chuỗi bài theo giai đoạn — đo bằng tỷ lệ đọc hết chuỗi và tỷ lệ quay lại đúng thời điểm khủng hoảng tiếp theo. (3) Trở thành nguồn trung lập hiếm hoi không bán sữa, không bán khóa kích sữa — đo bằng tỷ lệ chia sẻ trong hội nhóm. (4) Chuyển tiếp mượt sang Ăn dặm & dinh dưỡng (3-12 tháng) khi con gần 6 tháng.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Sinh xong 3 ngày sữa chưa về, con khóc, bà giục pha sữa ngoài — mình stress muốn khóc theo"; "Cho bú mà đau thấu trời, đầu ti nứt rớm máu — cắn răng chịu hay làm sai chỗ nào?"; "Con bú 10 phút đã ngủ, ti mãi không thấy no hay tại sữa mình ít?"; "Bà nội bảo sữa mình trong như nước vo gạo, không có chất — có đúng không mà con vẫn tăng cân?"; "Nửa đêm ngực cứng như đá, sốt rét run — tắc tia rồi, chườm nóng hay lạnh, có phải đi thông không, dịch vụ thông tia 500k/lần có lừa không?"; "Hút mỗi bên được 40ml có phải quá ít không, thấy hội nhóm khoe trữ đông cả tủ mà tủi thân"; "Sắp đi làm lại, công ty không có phòng vắt sữa — hút ở đâu, trữ thế nào, sếp có phải cho mình nghỉ vắt sữa không?"; "Mẹ ăn rau muống/đồ tanh con có bị đau bụng, mưng mủ vết thương như bà nói không?"; "Uống chè vằng, móng giò ngày 3 bữa mà sữa chẳng thêm, chỉ thêm 5kg"; "Con 2 tháng tự nhiên chê ti gào khóc khi bú — con ghét mình rồi à?"; "Phải trộn thêm sữa công thức mà cảm giác như mình thất bại, không dám nói với ai". Nền chung: sữa mẹ ở Việt Nam không chỉ là dinh dưỡng mà là thước đo phẩm chất người mẹ trong mắt gia đình — mọi hướng dẫn phải gỡ được cả nút kỹ thuật lẫn nút tâm lý đó.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Sữa mẹ là tốt nhất — 20 lợi ích của sữa mẹ" dạng tụng ca — ai cũng biết rồi, chỉ tăng áp lực lên mẹ đang thiếu sữa; thay bằng bài kỹ thuật giải quyết vấn đề cụ thể. "Review xếp hạng sữa công thức tốt nhất cho trẻ sơ sinh" — vùng nhạy cảm đạo đức (WHO Code hạn chế quảng bá sữa công thức cho trẻ dưới 6 tháng) và dễ thành affiliate; chỉ viết bài CÁCH chọn theo tiêu chí và CÁCH pha an toàn, không xếp hạng thương hiệu (review cụ thể thuộc Đánh giá sản phẩm và cũng phải theo nguyên tắc này). "Thuốc/thực phẩm chức năng lợi sữa thần kỳ" — thị trường loạn quảng cáo lừa mẹ bỉm; chỉ viết bài bóc tách bằng chứng. "Kích sữa L3-L4-L5 quyền năng" dạng thần thánh hóa giáo án bán khóa học — viết kỹ thuật kích sữa chuẩn WHO/La Leche League miễn phí là đủ, không tiếp tay hệ sinh thái khóa học. "Nuôi con hoàn toàn sữa mẹ đến 2 tuổi bất chấp" giọng giáo điều — đúng khuyến cáo WHO nhưng phải trình bày kèm hoàn cảnh thực tế mẹ đi làm, không phán xét người dừng sớm.
                TEXT,

            'audience' => 'Mẹ Việt 25-35 tuổi đang cho con 0-3 tháng bú (đọc tiếp đến khi cai sữa), phần lớn lần đầu; nghỉ thai sản 6 tháng rồi đi làm lại — nút thắt lớn nhất của hành trình sữa; chịu áp lực trực tiếp từ mẹ chồng/mẹ đẻ về chuyện "đủ sữa hay không"; đọc điện thoại MỘT TAY trong lúc cho con bú (đặc biệt cữ đêm 2-4h) và ngay giữa sự cố (tắc tia, con chê ti); trạng thái: đau thể xác + nghi ngờ bản thân + sợ bị phán xét.',

            'constraints' => 'Tuyệt đối không tội lỗi hóa mẹ ít sữa hay dùng sữa công thức; không tụng ca sữa mẹ sáo rỗng; không quảng cáo sữa công thức, TPCN lợi sữa, khóa kích sữa, dịch vụ thông tia; kỹ thuật phải chi tiết mức thao tác kèm dấu hiệu "đang làm đúng"; phân tầng rõ tự xử lý được / cần gặp chuyên gia sữa mẹ / cần đi viện (viêm vú sốt cao); dẫn nguồn WHO, La Leche League, Bộ Y tế; bài đọc được bằng một tay lúc 3h sáng: đoạn ngắn, kết luận trước.',

            'style_sample' => <<<'TEXT'
                Cữ bú 3 giờ sáng, con vừa ti 15 phút đã ngủ tít, và bạn ngồi trong bóng tối với câu hỏi đang gặm nhấm mọi bà mẹ mới: "Mình có đủ sữa cho con không?". Ban chiều bà nội vừa nhận xét sữa bạn "trong thế thì làm gì có chất", và giờ bạn đang định bụng mai bảo chồng mua sẵn hộp sữa ngoài. Khoan đã — trước khi quyết định, mình muốn đưa bạn một công cụ đo đáng tin hơn mọi lời nhận xét: cái TÃ của con. Ngực mẹ không có vạch chia ml, cảm giác "hình như ít sữa" là ảo giác phổ biến đến mức có tên riêng trong y văn — nhưng đầu ra của con thì không biết nói dối. Một em bé trên 5 ngày tuổi làm ướt nặng 6 cái tã trở lên mỗi 24 giờ, đi ị đều, bú xong tự nhả ti với vẻ mặt "no rồi đấy", và tăng cân đều ở các lần khám — em bé đó đang bú ĐỦ, bất kể sữa mẹ trong hay đục, ngực mẹ căng hay mềm. Còn màu sữa "trong như nước vo gạo" mà bà nhắc? Đó là sữa đầu cữ, nhiều nước và lactose để con giải khát — hoàn toàn bình thường, phần sữa cuối cữ béo đục sẽ đến sau. Trong bài này, mình sẽ đưa bạn bảng đếm tã theo ngày tuổi để dán cửa tủ lạnh, các dấu hiệu no - đói thật sự của con, và cả vài câu trả lời nhẹ nhàng mà chắc để dành cho lần nhận xét tới của bà.
                TEXT,
        ],

        // === Trẻ sơ sinh (0-3 tháng) > Giấc ngủ của bé ===
        [
            'parent_slug' => 'tre-so-sinh-0-3-thang',
            'slug'        => 'giac-ngu-cua-be',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: giấc ngủ 0-3 tháng — chu kỳ ngủ sơ sinh, thiết lập nếp ngủ nhẹ nhàng, an toàn SIDS, xử lý gắt ngủ/ngủ ngày cày đêm/khủng hoảng tháng 4.
                - KHÔNG viết: bú đêm (phối hợp với Nuôi con bằng sữa mẹ), bệnh làm khó ngủ (→ Bệnh thường gặp).
                - Khác biệt đã làm tốt, GIỮ NGUYÊN khung này: đối trọng với "trung tâm luyện ngủ" thương mại hóa — là nguồn MIỄN PHÍ đủ chi tiết để tự làm, không lặp khung này ở chuyên mục khác.
                - Trung lập tuyệt đối giữa các trường phái (EASY, tự ngủ, bế ru, ngủ chung) — KHÔNG phán xét bế ru/ngủ chung như nguồn dịch Tây, vì đó là thực tế đa số gia đình Việt.
                - Đặt kỳ vọng THẬT: dậy đêm ăn ở 0-3 tháng là sinh lý bình thường, "ngủ xuyên đêm" là ngoại lệ chứ không phải mục tiêu.
                - KPI: đo bằng tỷ lệ được nhắc tên thay thế khóa luyện ngủ trong hội nhóm, và tỷ lệ quay lại theo chuỗi "chuẩn bị cho tuần khủng hoảng".
                - Chia ca dậy đêm nên có phương án cụ thể cho BỐ (bú bình sữa mẹ vắt sẵn, đổi ca cuối tuần) — không chỉ nhắc mẹ "ngủ khi con ngủ" như phần lớn nguồn khác.
                TEXT,

            'core_focus' => <<<'TEXT'
                Mọi thứ về giấc ngủ của bé 0-3 tháng và sự sống còn của giấc ngủ cha mẹ: hiểu giấc ngủ sơ sinh khác người lớn thế nào (chu kỳ ngắn 40-50 phút, ngủ ngày cày đêm do chưa có nhịp sinh học, ngủ REM nhiều nên vặn mình è è là bình thường), tổng thời gian ngủ theo tuần tuổi và dấu hiệu buồn ngủ cần bắt trước khi con gắt (over-tired), thiết lập nếp ngủ nhẹ nhàng từ sớm: phân biệt ngày - đêm, trình tự trước giờ ngủ, môi trường ngủ (tối, white noise, nhiệt độ), quấn khăn và mốc phải bỏ quấn (khi con biết lật), AN TOÀN giấc ngủ chống đột tử SIDS theo chuẩn AAP (nằm ngửa, nôi/cũi thoáng, không gối - chăn mềm - nằm sấp; đối thoại thẳng với thực tế ngủ chung giường phổ biến ở Việt Nam: nếu ngủ chung thì giảm rủi ro thế nào), xử lý tình huống: con gắt ngủ khóc dai, ngủ ngày 30 phút dậy, lẫn lộn ngày đêm, chỉ ngủ trên tay - đặt xuống là dậy, và khủng hoảng ngủ tháng thứ 4 (chuẩn bị tâm lý trước). KHÔNG lấn sân: bú đêm thuộc phối hợp với Nuôi con bằng sữa mẹ; bệnh làm con khó ngủ (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung giấc ngủ trẻ em tiếng Việt đang bị các "trung tâm luyện ngủ" chiếm sóng — mọi bài đều dẫn về khóa học nghìn đô với lời hứa "con ngủ xuyên đêm 12 tiếng". Khác biệt: (1) Đặt kỳ vọng THẬT: trẻ 0-3 tháng dậy đêm ăn là sinh lý bình thường và cần thiết, "ngủ xuyên đêm" ở tuổi này là ngoại lệ chứ không phải mục tiêu — giải phóng cha mẹ khỏi cảm giác thất bại; (2) Trung lập với các trường phái (EASY, tự ngủ, bế ru, ngủ chung) — mô tả được - mất của từng lựa chọn theo bằng chứng, tôn trọng hoàn cảnh từng nhà thay vì giáo điều; đặc biệt KHÔNG phán xét bế ru và ngủ chung như các nguồn dịch Tây, vì đó là thực tế của đa số gia đình Việt (kèm hướng dẫn ngủ chung giảm rủi ro); (3) Nhìn giấc ngủ của con và của MẸ như một hệ — mẹ ngủ đủ mới sống sót qua giai đoạn này, nên mọi giải pháp đều cân nhắc chi phí giấc ngủ của người lớn, kể cả phương án "chấp nhận bế ru thêm tháng nữa" nếu điều đó tốt cho cả nhà.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn tuyệt vọng lúc nửa đêm: "trẻ sơ sinh gắt ngủ khóc thét", "trẻ ngủ ngày cày đêm phải làm sao", "trẻ sơ sinh đặt xuống là dậy", "trẻ 2 tháng ngủ bao nhiêu tiếng", "white noise cho trẻ sơ sinh", "khủng hoảng ngủ 4 tháng". (2) Là nguồn MIỄN PHÍ đáng tin thay thế khóa luyện ngủ tiền triệu — nội dung đủ chi tiết để tự làm; đo bằng tỷ lệ được nhắc tên/chia sẻ trong hội nhóm thay vì khóa học trả phí. (3) Bài an toàn giấc ngủ SIDS bản địa hóa cho bối cảnh ngủ chung của người Việt là nội dung trách nhiệm xã hội tạo uy tín khác biệt, đo bằng thời gian đọc và tỷ lệ chia sẻ. (4) Chuỗi "chuẩn bị cho tuần khủng hoảng" giữ độc giả quay lại theo mốc tuổi của con, chuyển tiếp sang giấc ngủ 3-12 tháng ở chuyên mục Chăm sóc trẻ nhỏ.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con ngủ cả ngày, đêm thức chơi từ 1h đến 4h sáng — hai vợ chồng thay nhau bế mà sắp gục"; "Đặt xuống cũi là khóc, bế lên lại ngủ — cứ thế cả đêm, tay mình sắp gãy, có phải con hư tại được bế nhiều như bà nói?"; "Con gắt ngủ khóc tím mặt, càng ru càng gào — mình làm sai gì?"; "Ngủ ngày cứ đúng 30 phút là dậy, không kịp làm gì cả"; "Con è è vặn mình cả đêm, mặt đỏ gay — thiếu canxi hay bình thường?"; "Nhà bảo cho con nằm sấp ngủ ngon hơn — mà mạng nói nằm sấp đột tử, ai đúng?"; "Cả nhà ngủ chung giường từ xưa, giờ đọc thấy phải ngủ cũi riêng — có bắt buộc không, nhà chật thì sao?"; "Có nên quấn con không, quấn đến bao giờ, con cứ giãy ra khỏi khăn"; "Trung tâm luyện ngủ báo giá 8 triệu cam kết con tự ngủ — có đáng tiền không hay tự làm được?"; "Đọc về khủng hoảng tháng thứ 4 mà sợ — chuẩn bị gì trước được không?"; "Mình thèm ngủ đến mức gật gù lúc bế con — nguy hiểm không, làm sao chia ca?". Nền chung: thiếu ngủ là khủng hoảng số 1 của giai đoạn này, cha mẹ đọc trong tuyệt vọng lúc 3h sáng và là mồi ngon của mọi lời hứa "ngủ xuyên đêm" có phí.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Luyện con tự ngủ bằng cry-it-out cho trẻ dưới 4 tháng" — mọi trường phái đều thống nhất KHÔNG để trẻ dưới 4-6 tháng khóc một mình có chủ đích; không viết dạng hướng dẫn ở cụm 0-3 tháng. "Mẹo dân gian cho bé ngủ ngon: đốt vía, treo tỏi, xông phòng" — không bằng chứng, một số có rủi ro (khói); chỉ viết dạng bài kiểm chứng nhẹ nhàng. "Review máy đưa nôi tự động, camera AI theo dõi thở" — thuộc Đánh giá sản phẩm; hơn nữa thiết bị theo dõi thở tạo an toàn giả, cần bài phân tích riêng đúng bằng chứng chứ không phải review khen. "Lịch sinh hoạt EASY chuẩn từng phút theo tuần tuổi" dạng giáo án cứng — trẻ sơ sinh không chạy theo lịch của app, lịch cứng tạo cảm giác thất bại; chỉ viết nhịp sinh hoạt linh hoạt theo tín hiệu của con. "Thuốc/siro giúp bé ngủ ngon" — nguy hiểm (kháng histamine cho trẻ sơ sinh), tuyệt đối không; chỉ có bài cảnh báo. "So sánh xếp hạng trung tâm luyện ngủ" — không tiếp tay thị trường chưa được kiểm soát; viết bài trang bị kiến thức tự đánh giá thay thế.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-35 tuổi có con 0-3 tháng (đỉnh khủng hoảng ngủ ở tuần 2-8), con đầu lòng; cả hai đều thiếu ngủ trầm trọng và cùng thay phiên bế con ru đêm 1-4h sáng nếu có chia ca, người còn lại tra "trẻ gắt ngủ" trong giờ làm để tìm giải pháp cho phiên trực tiếp theo; chịu áp lực hai chiều: ông bà bảo "bế nhiều quen tay, kệ nó khóc", mạng thì dọa "để khóc hại não" — không biết đường nào; sẵn sàng chi tiền triệu cho bất cứ thứ gì hứa hẹn được ngủ, nên rất dễ bị khóa học/thiết bị moi tiền.',

            'constraints' => 'Không hứa hẹn "ngủ xuyên đêm" cho trẻ dưới 4 tháng; không phán xét bế ru, ngủ chung, hay bất kỳ lựa chọn nào của gia đình; an toàn SIDS là ranh giới cứng không thỏa hiệp nhưng trình bày không dọa nạt; không bán/gợi ý khóa luyện ngủ, thiết bị, siro ngủ; bài phải đọc nổi lúc kiệt sức — kết luận trước, các bước đánh số, có phần "làm ngay đêm nay"; dẫn nguồn AAP/NHS/WHO; luôn nhắc chăm sóc giấc ngủ của chính cha mẹ.',

            'style_sample' => <<<'TEXT'
                3 giờ 40 phút sáng. Con vừa bú xong, mắt nhắm tịt trên vai bạn, thở đều như một thiên thần — và bạn bắt đầu nghi thức quen thuộc: hạ con xuống nôi chậm như tháo bom, từng centimet một, nín thở… và đúng khoảnh khắc lưng con chạm đệm, hai mắt ấy mở bừng ra. Lại bế lên. Lại từ đầu. Nếu bạn đang đọc những dòng này bằng một tay trong tư thế đó, thì trước hết: bạn không làm gì sai, và con bạn cũng không "hư vì được bế nhiều" đâu. Trẻ sơ sinh có một cơ chế rất thật gọi là phản xạ giật mình — khi cảm giác được ôm ấm đột ngột biến mất, não con báo động như thể đang rơi. Cộng thêm việc 20 phút đầu giấc con vẫn ở pha ngủ nông, thì "đặt xuống là dậy" gần như được lập trình sẵn. Tin tốt: có vài cách đánh lừa cơ chế ấy mà các nữ hộ sinh vẫn truyền tay nhau — đợi đủ dấu hiệu ngủ sâu (tay con rơi thõng như sợi bún), đặt con xuống NGHIÊNG người rồi mới xoay ngửa, giữ tay trên ngực con thêm 30 giây như một lời "có bố/mẹ ở đây rồi". Mình sẽ đi từng bước một, kèm cả phương án B rất đáng nói: nếu đêm nay tất cả đều thất bại, bế con ngủ thêm vài tuần nữa không làm hỏng con — nó chỉ làm mỏi tay bạn, và mình có cách chia ca cho đỡ mỏi ở cuối bài.
                TEXT,
        ],

        // === Trẻ sơ sinh (0-3 tháng) > Bệnh thường gặp ===
        [
            'parent_slug' => 'tre-so-sinh-0-3-thang',
            'slug'        => 'benh-thuong-gap',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: vàng da, sốt dưới 3 tháng, nghẹt mũi, nôn trớ, khóc dạ đề, táo bón, chàm sữa, tiêm chủng — LOGIC bắt buộc: đây là gì → mức nào bình thường → chăm tại nhà → NGƯỠNG đi khám.
                - KHÔNG viết: chăm sóc thường quy khỏe mạnh (→ Chăm sóc trẻ sơ sinh), vấn đề bú (→ Nuôi con bằng sữa mẹ).
                - Nguyên tắc cứng không thỏa hiệp: sốt ≥38°C ở trẻ dưới 3 tháng = đi viện ngay, không tự hạ sốt ở nhà, không ngoại lệ.
                - Kết luận hành động (bảng phân tầng 3 mức) phải nằm NGAY ĐẦU BÀI — cha mẹ đọc trong 30 giây lúc nửa đêm, không có kiên nhẫn đọc hết bài.
                - Giải oan hiện tượng sinh lý bị bệnh-hóa (vặn mình, phân hoa cà, hắt hơi) — giảm đi viện không cần thiết và canxi/men vi sinh vô ích.
                - Mẹo dân gian nguy hiểm (nước lá chữa vàng da, mật ong) phải cảnh báo bằng ca thực tế đã ghi nhận, không mỉa mai người mách.
                TEXT,

            'core_focus' => <<<'TEXT'
                Các vấn đề sức khỏe hay gặp nhất ở bé 0-3 tháng, viết theo đúng logic cha mẹ cần lúc lo lắng: đây là gì → mức độ nào bình thường → chăm tại nhà thế nào → NGƯỠNG NÀO đi khám ngay. Trọng tâm: vàng da sơ sinh (sinh lý vs bệnh lý, ngưỡng chiếu đèn), sốt ở trẻ dưới 3 tháng (nguyên tắc thép: dưới 3 tháng sốt ≥38°C là đi viện, không tự hạ sốt ở nhà), nghẹt mũi - thở khò khè - hắt hơi (khi nào là bình thường do mũi bé, cách vệ sinh mũi đúng, hút mũi có hại không), nôn trớ - trào ngược sinh lý vs nôn vọt bất thường, khóc dạ đề/colic (cách sống chung và loại trừ nguyên nhân khác), táo bón - són phân - phân hoa cà hoa cải (đọc màu phân: khi nào bình thường, khi nào báo động), viêm da - chàm sữa - hăm nặng, đau bụng - đầy hơi - vặn mình, nấm miệng, viêm mắt - tắc lệ đạo, và các mốc tiêm chủng 0-3 tháng kèm chăm sóc sau tiêm (sốt sau tiêm 6in1). KHÔNG lấn sân: chăm sóc thường quy khỏe mạnh (thuộc Chăm sóc trẻ sơ sinh), vấn đề bú (thuộc Nuôi con bằng sữa mẹ).
                TEXT,

            'unique_angle' => <<<'TEXT'
                2 giờ sáng, con 6 tuần tuổi sốt 38.1°C — câu hỏi thật của cha mẹ lúc đó không phải "vàng da là gì" mà là "có phải đi viện NGAY BÂY GIỜ không". Bài bệnh viện trả lời đúng nhưng viết cho đồng nghiệp đọc; bài trang tin thì xào nấu rồi kết bằng "đưa trẻ đến cơ sở y tế gần nhất" vô thưởng vô phạt — không ai trả lời thẳng câu hỏi "đêm nay có cần đi không". Khác biệt: (1) Mỗi bài xây quanh MỘT bảng phân tầng rõ ràng ba mức "theo dõi ở nhà / khám trong 24h / đi viện NGAY" với dấu hiệu cụ thể quan sát được (con số, màu sắc, hành vi) — trả lời đúng câu hỏi thật của cha mẹ lúc nửa đêm: "có phải đi viện bây giờ không?"; (2) Giải oan cho các hiện tượng sinh lý bị bệnh-hóa (vặn mình đỏ mặt, phân hoa cà, hắt hơi, nấc) — giảm những chuyến đi viện không cần thiết và những liều canxi/men vi sinh vô ích mà hàng xóm mách; (3) Đối đầu tử tế với mẹo dân gian nguy hiểm ở đúng bối cảnh bệnh: uống nước lá chữa vàng da (chậm trễ chiếu đèn gây biến chứng não), mật ong cho trẻ ho, chích lể — nói thẳng hậu quả bằng ca thực tế đã được báo chí y tế ghi nhận, không mỉa mai người mách.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn khẩn cấp có volume lớn quanh năm: "trẻ sơ sinh bị nghẹt mũi", "trẻ sơ sinh sốt 38 độ phải làm sao", "vàng da ở trẻ sơ sinh bao lâu thì hết", "trẻ sơ sinh nôn trớ nhiều", "khóc dạ đề", "sốt sau tiêm 6in1" — nhóm truy vấn mà chất lượng câu trả lời ảnh hưởng trực tiếp đến an toàn của trẻ. (2) Trở thành "bộ lọc bình tĩnh" cha mẹ mở TRƯỚC KHI quyết định bế con đi viện đêm — đo bằng lượng truy cập trực tiếp/bookmark và tỷ lệ quay lại. (3) Xây uy tín y khoa bằng phân tầng chuẩn + dẫn nguồn, làm nền tin cậy cho mọi chuyên mục khác của site. (4) Nối luồng: bài vàng da ↔ Chăm sóc trẻ sơ sinh (tắm nắng đúng cách), bài nôn trớ ↔ Nuôi con bằng sữa mẹ (tư thế bú, ợ hơi), chuyển tiếp sang Bệnh thường gặp 3-12 tháng.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con 20 ngày tuổi hâm hấp 37.8 độ — mặc bớt áo theo dõi hay đi viện luôn giữa đêm mưa?"; "Da con vàng đến ngực rồi, bà bảo tắm nắng với uống nước lá là hết — mà mạng nói vàng da nặng hại não, phân biệt kiểu gì?"; "Con thở khò khè như có đờm, ngủ phải nghiêng đầu — viêm phổi hay mũi bé bình thường?"; "Ọc sữa cả vòi sau bú — trớ sinh lý hay hẹp môn vị như bài mình lỡ đọc?"; "Cứ 6h tối là gào khóc 2-3 tiếng, dỗ kiểu gì cũng không nín, bà bảo khóc dạ đề phải đốt vía — mình phải làm gì thật sự?"; "3 ngày con không ị, rặn đỏ mặt — táo bón phải bơm hay vẫn bình thường với trẻ bú mẹ?"; "Phân con màu xanh/có hạt trắng/có nhầy — soi Google mỗi trang phán một bệnh"; "Má con nổi mảng đỏ khô — chàm sữa bôi gì, kem hàng xóm mách trộn corticoid có sao không?"; "Tiêm 6in1 về sốt 38.5, khóc thét — bình thường hay phản ứng nặng, ngưỡng nào đi viện?"; "Lưỡi con trắng — cặn sữa hay nấm, rơ thế nào?"; "Mắt con đổ ghèn dính mi cả sáng — nhỏ sữa mẹ như bà bảo được không?". Nền chung: với con dưới 3 tháng, mọi triệu chứng đều đáng sợ gấp đôi vì con quá bé — cha mẹ cần ngưỡng hành động RÕ RÀNG chứ không cần thêm một bài liệt kê nguyên nhân.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Mẹo dân gian chữa bệnh sơ sinh (nước lá chữa vàng da, chích lể, mật ong trị ho, nhỏ sữa mẹ vào mắt)" dạng hướng dẫn — nguy hiểm trực tiếp, đã có ca biến chứng não do trì hoãn chiếu đèn vàng da, ngộ độc botulinum do mật ong; chỉ viết bài cảnh báo dẫn chứng cụ thể. "Tự chẩn đoán phân biệt viêm phổi/viêm tiểu phế quản tại nhà" — vượt ranh giới an toàn với trẻ dưới 3 tháng, nhóm tuổi diễn tiến nhanh nhất; chuyên mục chỉ dạy nhận diện dấu hiệu nguy hiểm (rút lõm ngực, thở nhanh, bỏ bú) và đi khám. "Kháng sinh nào cho trẻ sơ sinh" — tuyệt đối không nội dung kê đơn; chỉ có bài "vì sao không tự mua kháng sinh cho con". "Men vi sinh/canxi/vitamin tổng hợp chữa vặn mình, khóc đêm" — thị trường TPCN đang khai thác đúng nỗi lo này, không tiếp tay; viết bài bóc tách bằng chứng. "Tổng hợp 30 bệnh trẻ sơ sinh thường gặp" trong 1 bài — nông và vô dụng lúc khẩn cấp; mỗi triệu chứng một bài sâu có bảng phân tầng.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-35 tuổi có con 0-3 tháng, con đầu lòng, chưa có kinh nghiệm phân biệt nặng - nhẹ; tra cứu trong trạng thái LO LẮNG CẤP TÍNH (con đang sốt/vàng da/khò khè ngay lúc đó), thường vào ban đêm khi phòng khám đóng cửa; bị giằng co giữa bà nội "để bà chữa mẹo cho" và nỗi sợ đưa con đi viện đêm không cần thiết; đọc trên điện thoại, chỉ đủ kiên nhẫn cho câu trả lời trong 30 giây đầu.',

            'constraints' => 'Kết luận hành động phải nằm NGAY ĐẦU BÀI (bảng phân tầng 3 mức trước, giải thích sau); không thay thế chẩn đoán — với trẻ dưới 3 tháng luôn nghiêng về ngưỡng an toàn; không liệt kê bệnh hiếm gây hoảng; không nội dung kê đơn, liều thuốc (trừ hạ sốt paracetamol theo cân nặng có cảnh báo hỏi bác sĩ); cảnh báo mẹo dân gian bằng dẫn chứng, không giễu cợt; nguồn: Bộ Y tế, WHO, AAP, bệnh viện Nhi TW; không quảng cáo thuốc, TPCN, phòng khám.',

            'style_sample' => <<<'TEXT'
                Nhiệt kế hiện 38.1°C, con bạn mới 6 tuần tuổi, và đồng hồ chỉ 1 giờ sáng. Bà đang bảo "trẻ con ấm đầu là chuyện thường, mặc bớt áo rồi theo dõi" — và với đứa trẻ 2 tuổi thì bà nói đúng. Nhưng với em bé dưới 3 tháng, mình cần nói với bạn một nguyên tắc mà mọi bác sĩ nhi trên thế giới đều thống nhất, không có ngoại lệ: sốt từ 38°C trở lên ở trẻ dưới 3 tháng tuổi = đi viện ngay trong đêm, không chờ sáng, không tự cho hạ sốt trước. Không phải vì bệnh chắc chắn nặng — phần lớn sau cùng chỉ là nhiễm siêu vi nhẹ — mà vì ở tuổi này, hệ miễn dịch của con còn quá non để "khoanh vùng" ổ nhiễm trùng, một nhiễm khuẩn nghiêm trọng có thể diễn tiến trong vài giờ mà biểu hiện bên ngoài vẫn chỉ là... sốt nhẹ. Chỉ bác sĩ với xét nghiệm mới phân biệt được, nên quy tắc mới tuyệt đối như vậy. Điều bạn cần làm ngay bây giờ: kẹp lại nhiệt độ lần nữa cho chắc (hướng dẫn đo đúng ở dưới), KHÔNG cho uống hạ sốt (để bác sĩ đánh giá cơn sốt thật), mang theo sổ tiêm chủng, và đi viện có khoa nhi gần nhất. Còn nếu nhiệt kế của bạn đang hiện 37.6°C? Đó là vùng khác — kéo xuống phần "37.5-38°C: theo dõi thế nào cho đúng" ngay bên dưới nhé.
                TEXT,
        ],

        // === Trẻ nhỏ (3-12 tháng) — danh mục cha, bài pillar tổng quan giai đoạn ===
        [
            'parent_slug' => null,
            'slug'        => 'tre-nho-3-12-thang',

            'writer_insights' => <<<'TEXT'
                - Đây là danh mục CHA — chỉ bài TỔNG QUAN 3-12 tháng, dẫn vào 4 chuyên mục con. KHÔNG viết chi tiết ăn dặm/mốc phát triển/bệnh/chăm sóc hằng ngày ở đây.
                - Sự kiện chi phối cả giai đoạn: MẸ ĐI LÀM LẠI tháng 6-7 — tuyến bài "bàn giao con" (ông bà/giúp việc/nhóm trẻ) là nội dung KHÔNG chuyên mục con nào viết.
                - An toàn nhà cửa viết theo hiện trạng nhà Việt thật (nhà ống, phích nước, bàn thờ) — không dịch checklist nhà Mỹ.
                - Giọng "quý nào việc nấy" — giúp cha mẹ đi trước con một bước.
                - KPI: đo bằng tỷ lệ share trong hội nhóm mẹ bỉm quay lại công sở, và CTR từ pillar xuống đúng 4 chuyên mục con theo quý tuổi.
                TEXT,

            'core_focus' => <<<'TEXT'
                Danh mục CHA của cụm 3-12 tháng — chứa các bài TỔNG QUAN xuyên suốt giai đoạn con "ra khỏi tổ": cẩm nang từng quý (3-6, 6-9, 9-12 tháng: mỗi quý con thay đổi gì, cha mẹ cần chuẩn bị gì), bước ngoặt mẹ đi làm lại sau thai sản 6 tháng (chọn người trông: ông bà - giúp việc - nhóm trẻ; chuẩn bị con quen người mới; sữa và ăn dặm khi mẹ vắng nhà; cảm giác tội lỗi của mẹ), an toàn trong nhà khi con biết lẫy - bò - vịn đứng (child-proofing kiểu nhà Việt: cầu thang, ổ điện, phích nước, bàn thờ), lịch tổng hợp tiêm chủng - khám định kỳ 3-12 tháng, đưa con ra ngoài: về quê, đi máy bay lần đầu, và sinh nhật 1 tuổi (thôi nôi, ý nghĩa - tổ chức vừa sức). Chi tiết ăn dặm - mốc phát triển - bệnh - chăm sóc hằng ngày KHÔNG viết ở đây — đẩy xuống 4 chuyên mục con.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Giai đoạn 3-12 tháng có một sự kiện chi phối mọi thứ mà nội dung mẹ-bé Việt gần như bỏ trống: MẸ ĐI LÀM LẠI ở tháng thứ 6-7. Khác biệt của danh mục: (1) Xây tuyến bài "bàn giao con" tử tế nhất thị trường — chọn và làm việc với người trông (ông bà lên chăm cháu: thỏa thuận thế nào để bà không thành osin và mẹ không thành người ngoài; thuê giúp việc: hợp đồng, camera, ranh giới; gửi nhóm trẻ tư trước 12 tháng: tiêu chí an toàn tối thiểu) — quyết định lớn nhất, ít được trợ giúp nhất của năm đầu; (2) An toàn nhà cửa viết theo hiện trạng nhà Việt thật (nhà ống cầu thang dốc, phích nước sôi trên bàn, xe máy trong nhà, bàn thờ nến hương) chứ không dịch checklist nhà Mỹ; (3) Giọng "quý nào việc nấy" giúp cha mẹ đi trước con một bước thay vì chạy theo — mỗi bài quý đều có mục "tháng tới con sẽ làm bạn bất ngờ vì...".
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Hai bài pillar "Mẹ đi làm lại" và "An toàn nhà cửa theo giai đoạn bò - đứng - đi" là nội dung khác biệt định vị site — đo bằng tỷ lệ share trong hội nhóm mẹ bỉm quay lại công sở. (2) SEO truy vấn giai đoạn: "chuẩn bị đi làm lại sau thai sản", "có nên thuê giúp việc trông con", "gửi trẻ 10 tháng được không", "trẻ mấy tháng biết bò", "đi máy bay với trẻ dưới 1 tuổi". (3) Điều phối luồng đọc xuống 4 chuyên mục con và giữ nhịp quay lại theo quý tuổi của con. (4) Chuyển giao độc giả mượt sang cụm Trẻ tập đi (1-3 tuổi) sau sinh nhật 1 tuổi — tiếp tục chuỗi hành trình mà site theo đuổi từ thai kỳ.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Còn 3 tuần nữa hết thai sản mà chưa chốt được ai trông con — bà nội muốn lên nhưng hay ốm, thuê người thì sợ camera quay không hết, nhóm trẻ thì con còn bé quá"; "Ngày đầu đi làm lại, ngồi họp mà nước mắt chảy vì nhớ con — có ai như mình không?"; "Bà trông cháu giúp mà mọi thứ làm ngược ý mình: cho ăn rong, bật tivi cả ngày — góp ý thì bà giận bảo 'tao nuôi 4 đứa có sao đâu'"; "Con 7 tháng lăn từ giường xuống đất — mình ân hận mất ngủ, giờ phải rào chắn thế nào?"; "Nhà ống 3 tầng, con sắp biết bò — chặn cầu thang bằng gì, mua gate loại nào?"; "Về quê ăn Tết 300km với con 8 tháng — đi xe khách hay tự lái, mang gì, con say xe thì sao?"; "Đi máy bay con có bị đau tai không, cần giấy tờ gì?"; "Thôi nôi phải làm mâm cúng thế nào, nhà nội đòi làm to nhà ngoại bảo thôi — mệt cả người"; "Con bám mẹ khóc thét khi thấy người lạ — làm sao cho con quen bà trước khi mình đi làm?". Nền chung: đây là giai đoạn cha mẹ phải RA NHIỀU QUYẾT ĐỊNH THUÊ - GỬI - GIAO CON nhất, mỗi quyết định đều day dứt và không có chuẩn nào để bám.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Thực đơn ăn dặm chi tiết" — thuộc chuyên mục con Ăn dặm & dinh dưỡng, viết ở đây trùng nội bộ. "Mốc phát triển từng tháng, trẻ mấy tháng biết ngồi/bò/đứng" chi tiết — thuộc Phát triển của trẻ (3-12 tháng). "Sốt mọc răng, viêm tai giữa, tay chân miệng" — thuộc Bệnh thường gặp (3-12 tháng). "Review camera giám sát giúp việc, ghế ăn dặm, cũi quây" — thuộc Đánh giá sản phẩm; ở đây chỉ nêu tiêu chí chọn theo an toàn. "Bài cúng thôi nôi chuẩn văn khấn" — sa đà tâm linh lệch định vị; chỉ viết ý nghĩa phong tục + gợi ý tổ chức vừa sức, phần nghi lễ dẫn nguồn văn hóa tham khảo. "Có nên cho ông bà trông cháu không" dạng tranh luận hai phe — gây war và tổn thương chính độc giả đang nhờ ông bà; chỉ viết dạng hướng dẫn hợp tác ba thế hệ.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-36 tuổi có con 3-12 tháng; mẹ đang ở nút thắt đi làm lại sau 6 tháng thai sản — vừa sắp xếp người trông con vừa duy trì sữa vừa ổn định công việc; nhà thường có ông bà từ quê lên chăm cháu hoặc thuê giúp việc lần đầu; đọc buổi trưa ở văn phòng và tối muộn sau khi con ngủ; trạng thái đặc trưng: tội lỗi vì xa con + căng thẳng phối hợp người trông + lo an toàn khi không ở cạnh.',

            'constraints' => 'Không phán xét mọi lựa chọn trông con (ông bà/giúp việc/nhóm trẻ/mẹ nghỉ việc); không khoét sâu cảm giác tội lỗi của mẹ đi làm; không vẽ chuẩn nuôi con tốn kém — mọi gợi ý có phương án tiết kiệm; an toàn trẻ em là ranh giới cứng, nói thẳng nhưng không dọa; tôn trọng phong tục (thôi nôi, về quê) không mê tín hóa; không quảng cáo dịch vụ giúp việc, nhóm trẻ, sản phẩm.',

            'style_sample' => <<<'TEXT'
                Trong điện thoại của bạn đang có một cái đếm ngược mà chẳng ứng dụng nào hiển thị: 21 ngày nữa hết thai sản. Sáu tháng qua bạn là người duy nhất biết con ăn bao nhiêu ml thì đủ, ru kiểu gì thì ngủ, tiếng khóc nào là đói tiếng nào là buồn — và ba tuần nữa, bạn phải "bàn giao" toàn bộ kho tri thức ấy cho một người khác để quay lại làm nhân viên bình thường lúc 8 giờ sáng. Không có quyết định nào của năm đầu đời khó hơn quyết định "ai sẽ trông con", và cũng không có quyết định nào bị người ngoài bình luận nhiều hơn thế: bà nội bảo cứ để bà, chị đồng nghiệp khuyên thuê người cho chuyên nghiệp, mẹ đẻ thì thở dài "hay mày nghỉ hẳn đi". Bài này không chọn hộ bạn — không ai chọn hộ được — nhưng nó sẽ đặt lên bàn cả ba phương án với đầy đủ chi phí thật, ưu nhược thật và những câu hỏi phải hỏi trước khi chốt, kèm một thứ mình cho là quan trọng nhất: lộ trình 3 tuần cho con làm quen dần với người trông mới, để ngày đầu bạn đi làm, cả con lẫn mẹ đều không phải bắt đầu từ con số không.
                TEXT,
        ],

        // === Trẻ nhỏ (3-12 tháng) > Chăm sóc trẻ nhỏ ===
        [
            'parent_slug' => 'tre-nho-3-12-thang',
            'slug'        => 'cham-soc-tre-nho',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: giấc ngủ 3-12 tháng (khủng hoảng tháng 4, luyện tự ngủ trung lập), mọc răng/chăm răng sữa, tắm/vệ sinh khi con hoạt động nhiều, màn hình = 0 trước 18 tháng.
                - KHÔNG viết: bữa ăn dặm (→ Ăn dặm & dinh dưỡng), mốc vận động (→ Phát triển của trẻ), sốt mọc răng/bệnh (→ Bệnh thường gặp).
                - Khác biệt bắt buộc giữ: chuyên mục DUY NHẤT trình bày luyện tự ngủ trung lập (từ fading đến Ferber) kèm cả lựa chọn "không luyện".
                - Mọi bài phải có phần "thống nhất với người trông ban ngày (bà/giúp việc)" — mẹ chỉ kiểm soát được buổi tối.
                - Chăm răng sữa được nâng đúng tầm — cha mẹ Việt thường bỏ qua đến khi con sâu răng mới đi khám.
                - Phần "thống nhất với người trông ban ngày" nên tính cả trường hợp BỐ là người trông chính (không chỉ mặc định bà/giúp việc) khi mẹ đi công tác hoặc làm ca.
                TEXT,

            'core_focus' => <<<'TEXT'
                Chăm sóc hằng ngày cho bé 3-12 tháng — giai đoạn con từ "nằm yên" thành "cỗ máy di chuyển": giấc ngủ 3-12 tháng (khủng hoảng ngủ tháng 4, gộp giấc ngày, luyện tự ngủ các phương pháp từ nhẹ đến mạnh — trình bày trung lập cho trẻ đủ tuổi, cai ti đêm khi nào và thế nào), vệ sinh cơ thể khi con hoạt động nhiều (tắm bé biết ngồi - biết đứng, chăm da mùa nóng rôm sảy - mùa hanh nẻ má, cắt tóc máu), mọc răng và chăm răng sữa đầu tiên (rơ nướu, chải răng từ răng đầu tiên, ti đêm và sâu răng), mặc đồ theo mùa cho trẻ vận động, quần áo - bỉm size nào khi con bò trườn, thói quen sinh hoạt: tập nếp ăn - chơi - ngủ theo nhịp, thời gian màn hình = 0 trước 18 tháng (chuẩn WHO, xử lý thực tế bà bật tivi cho cháu ăn), ra ngoài hằng ngày: tắm nắng đúng cách vs quan niệm cũ, đi dạo, chống nắng - chống muỗi an toàn cho trẻ dưới 1 tuổi. KHÔNG lấn sân: bữa ăn dặm (thuộc Ăn dặm & dinh dưỡng), mốc vận động (thuộc Phát triển của trẻ), sốt mọc răng và bệnh (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Sau 3 tháng đầu được cả họ xúm vào chăm, giai đoạn 3-12 tháng là lúc cha mẹ bắt đầu "tự bơi" — bà về quê, mẹ đi làm, và các thói quen tốt xấu hình thành từ đây. Khác biệt: (1) Chuyên mục duy nhất xử lý luyện tự ngủ một cách TRUNG LẬP cho thị trường Việt: trình bày đủ phổ phương pháp (từ fading nhẹ nhàng đến Ferber) với bằng chứng, điều kiện áp dụng và cả lựa chọn "không luyện" — người đọc tự quyết theo hoàn cảnh, không giáo điều như các trung tâm bán khóa; (2) Chủ đề răng sữa được nâng đúng tầm — cha mẹ Việt gần như bỏ qua chải răng cho đến khi con sâu răng đi khám: tuyến bài "chăm răng từ chiếc đầu tiên" kèm xử lý thói quen ngậm ti/bú đêm gây sâu răng sớm; (3) Mỗi thói quen chăm sóc đều tính đến "người trông thứ hai" (bà, giúp việc): bài nào cũng có phần "thống nhất với người trông con" — vì mẹ chỉ kiểm soát được buổi tối, ban ngày là ca của người khác.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn thói quen - kỹ năng: "khủng hoảng ngủ tháng thứ 4", "cách luyện con tự ngủ", "trẻ mấy tháng cai ti đêm được", "chăm sóc răng sữa", "trẻ mấy tháng cắt tóc máu", "kem chống nắng cho bé dưới 1 tuổi". (2) Bộ bài luyện ngủ trung lập miễn phí cạnh tranh trực tiếp với khóa học nghìn đô — nội dung định vị được nhắc tên trong hội nhóm. (3) Xây nếp "đọc trước một bước" (bài khủng hoảng tháng 4 đọc từ tháng 3) — đo bằng đăng ký nhận bài theo tuổi con. (4) Liên kết chéo trong cụm: ngủ ↔ ăn dặm (lịch sinh hoạt), răng ↔ bệnh (sốt mọc răng), và bàn giao sang Chăm sóc & nuôi dạy (1-3 tuổi) khi con tròn 1 tuổi.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con 4 tháng tự nhiên phá ngủ toàn tập: đêm dậy 6-7 lần, ngày ngủ 30 phút — mình làm sai gì hay đây là khủng hoảng tháng 4, bao giờ hết?"; "Muốn luyện con tự ngủ mà đọc mỗi nơi một phương pháp, nơi dọa để khóc hại não, nơi bảo bế là hư — rối không dám làm gì"; "Con 8 tháng vẫn dậy 3-4 cữ đêm đòi ti — cai được chưa, cai kiểu gì để cả nhà không thức trắng?"; "Bao giờ phải đánh răng cho con, răng sữa sâu có sao đâu như bà nói?"; "Con ngậm ti bình mới ngủ — nghe nói hỏng răng mà bỏ thì con gào"; "Trời nắng 38 độ con nổi rôm đầy lưng — tắm lá gì được, phấn rôm có hại không?"; "Bà cứ bật tivi quảng cáo cho cháu ăn — mình biết là hại mà nói bà lại bảo 'có tí tivi mà cũng cấm'"; "Đi biển với con 9 tháng — bôi kem chống nắng được chưa, loại nào?"; "Xịt muỗi, vòng chống muỗi có an toàn cho trẻ dưới 1 tuổi không, con bị đốt sưng cả chân"; "Cắt tóc máu cho con có phải xem ngày, tóc có dày lên thật không?". Nền chung: giai đoạn hình thành thói quen nền tảng (ngủ, răng miệng, màn hình) nhưng cha mẹ chỉ trực tiếp chăm buổi tối — mọi hướng dẫn phải "triển khai được qua người trông ban ngày".
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Phương pháp luyện ngủ X là duy nhất đúng" dạng truyền giáo — gây war và bó người đọc; chỉ viết dạng bản đồ trung lập các phương pháp. "Để mặc con khóc đến khi ngủ (extinction) cho trẻ dưới 6 tháng" — dưới ngưỡng tuổi khuyến cáo; chỉ đề cập từ 6 tháng trở lên kèm điều kiện. "Mẹo dân gian: cắt tóc máu cho tóc dày, tắm lá X trị rôm cho mọi bé" dạng khẳng định — tóc dày do gen không do cắt, một số lá gây kích ứng; viết dạng kiểm chứng thực hư. "Review ghế rung, xe tròn tập đi" — xe tròn tập đi bị AAP khuyến cáo cấm vì nguy cơ tai nạn và không giúp biết đi sớm — viết bài cảnh báo riêng chứ không review; sản phẩm khác thuộc Đánh giá sản phẩm. "Lịch sinh hoạt chuẩn từng 30 phút cho bé N tháng" — lịch cứng tạo áp lực thất bại; chỉ viết khung nhịp linh hoạt. "Cho trẻ xem màn hình học tiếng Anh sớm" — ngược khuyến cáo WHO 0 phút trước 18 tháng; chỉ viết bài giải thích khuyến cáo và cách thay thế.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-36 tuổi có con 3-12 tháng, mẹ đã đi làm lại (con ban ngày do bà hoặc giúp việc trông), chỉ trực tiếp chăm con sáng sớm và từ 18h; muốn xây nếp tốt (ngủ, răng, không màn hình) nhưng lực bất tòng tâm vì người trông ban ngày làm khác; đọc tối muộn sau khi con ngủ và cuối tuần; điểm nóng tra cứu: khủng hoảng ngủ tháng 4 và cai ti đêm — hai chủ đề gây kiệt sức nhất giai đoạn này.',

            'constraints' => 'Trung lập tuyệt đối giữa các phương pháp luyện ngủ, ghi rõ độ tuổi tối thiểu; không phán xét gia đình ngủ chung, bế ru, hay bà bật tivi — đưa giải pháp thương lượng thay vì chỉ trích; khuyến cáo màn hình theo WHO nhưng thực dụng, không cực đoan hóa; mẹo dân gian phân tích thực hư có dẫn chứng; không quảng cáo sản phẩm chăm sóc, khóa luyện ngủ; hướng dẫn phải kèm phần "thống nhất với ông bà/người trông".',

            'style_sample' => <<<'TEXT'
                Suốt ba tháng, con bạn là "em bé thiên thần" ngủ ngoan có tiếng trong họ — rồi đúng tuần này, mọi thứ sụp đổ: đêm dậy sáu lần, giấc ngày vỡ vụn còn 30 phút, đặt xuống là mắt mở thao láo. Bạn bắt đầu rà lại xem mình đã làm sai điều gì. Câu trả lời nghe có vẻ ngược đời: bạn không làm sai gì cả — con bạn vừa được NÂNG CẤP. Quanh mốc 4 tháng, não bộ của con chuyển hẳn cấu trúc giấc ngủ từ kiểu sơ sinh (chỉ có ngủ nông - ngủ sâu) sang kiểu người lớn với đủ các chu kỳ — và giống mọi bản nâng cấp phần mềm, nó đi kèm lỗi vặt: giữa mỗi chu kỳ 40 phút, con tỉnh dậy thật sự thay vì tự trôi sang giấc tiếp. Đây là lý do các chuyên gia gọi giai đoạn này là "regression" nhưng thực chất là "progression" — một bước tiến vĩnh viễn, không phải cơn bão sẽ tan. Nghĩa là: không có cách "chữa" cho con quay về như cũ, nhưng có cách giúp con học kỹ năng mới — tự nối giấc — nhanh hơn nhiều. Trong bài này mình đi qua: dấu hiệu nhận biết đúng là khủng hoảng tháng 4 (chứ không phải đói hay ốm), 5 điều chỉnh làm được ngay tuần này, và một lời khuyên thật lòng về việc có nên bắt đầu luyện tự ngủ ngay giữa tâm bão hay đợi thêm vài tuần.
                TEXT,
        ],

        // === Trẻ nhỏ (3-12 tháng) > Phát triển của trẻ ===
        [
            'parent_slug' => 'tre-nho-3-12-thang',
            'slug'        => 'phat-trien-cua-tre-2',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: mốc vận động lớn (lẫy/ngồi/bò/đứng), mốc tinh tế (cầm nắm, bập bẹ, chỉ trỏ, sợ người lạ), red flags AAP/CDC, đọc biểu đồ tăng trưởng giai đoạn chậm lại.
                - KHÔNG viết: ăn dặm (chuyên mục riêng), chăm sóc/luyện ngủ (→ Chăm sóc trẻ nhỏ), bệnh (→ Bệnh thường gặp).
                - Đối thoại thẳng với câu ca dao "3 tháng biết lẫy, 7 tháng biết bò, 9 tháng lò dò biết đi" — đúng về TRÌNH TỰ, sai về THỜI ĐIỂM (lấy mốc sớm nhất làm chuẩn).
                - Chống can thiệp sai phổ biến bằng bằng chứng: xe tròn tập đi (AAP khuyến cáo cấm), đỡ ngồi/xốc nách tập đứng sớm.
                - Ngôn ngữ/giao tiếp sớm (bập bẹ, chỉ trỏ, phản ứng gọi tên) đặt ngang vận động — cha mẹ Việt hay chỉ đếm lẫy-bò-đi mà bỏ qua các mốc này.
                - KPI: đo bằng tỷ lệ quay lại theo chuỗi "Bé N tháng tuổi" và tỷ lệ trích dẫn bài "chuẩn ca dao vs chuẩn y khoa".
                TEXT,

            'core_focus' => <<<'TEXT'
                Sự phát triển của bé 3-12 tháng — năm của các mốc vận động lớn: lẫy (3-6 tháng), ngồi (6-8), bò (7-10), vịn đứng - men đồ (9-12), và có thể những bước đi đầu tiên; kèm mốc tinh tế quan trọng không kém: cầm nắm chuyền tay (4-6), nhặt bằng hai ngón (9-12), bập bẹ "ba ba ma ma" (6-9), gọi tên có quay lại - vẫy tay - chỉ trỏ (9-12), lo lắng khi xa mẹ và sợ người lạ (6-12 — giải thích đây là mốc TỐT của gắn bó, không phải "hư"). Với mỗi mốc: khoảng tuổi bình thường (luôn là KHOẢNG rộng), cách tạo điều kiện cho con tập (không gian sàn, trò chơi tương tác bằng đồ trong nhà), điều KHÔNG nên làm (xe tròn tập đi, đỡ ngồi sớm, ép tập đứng), red flags theo chuẩn AAP/CDC cần khám phát triển (6 tháng chưa lẫy chưa với đồ, 9 tháng chưa ngồi vững chưa bập bẹ, 12 tháng chưa chỉ trỏ không phản ứng gọi tên), và đọc biểu đồ tăng trưởng giai đoạn tốc độ tăng cân chậm lại (giải oan "con dạo này còi đi"). KHÔNG lấn sân: ăn dặm (chuyên mục riêng), chăm sóc - luyện ngủ (Chăm sóc trẻ nhỏ), bệnh (Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Đây là giai đoạn văn hóa so sánh của người Việt hoạt động hết công suất: "3 tháng biết lẫy, 7 tháng biết bò, 9 tháng lò dò biết đi" — câu ca dao thành thước đo cứng khiến hàng nghìn mẹ lo lắng oan. Khác biệt: (1) Đối thoại thẳng với các "chuẩn dân gian" (câu ca dao trên, "trốn lẫy", "chân vòng kiềng do đóng bỉm") — giải thích khoa học đằng sau, mốc nào là khoảng rộng, hiện tượng nào là bình thường (trẻ bỏ qua bò đi thẳng, chân cong sinh lý dưới 2 tuổi); (2) Chống can thiệp sai phổ biến ở Việt Nam bằng bằng chứng: xe tròn tập đi (AAP khuyến cáo cấm — làm chậm biết đi và gây tai nạn), đỡ ngồi - xốc nách tập đứng sớm, địu ngồi sai tư thế — các bài này cứu cha mẹ khỏi mua sắm và tập luyện có hại; (3) Ngôn ngữ và giao tiếp sớm được đặt ngang vận động — cha mẹ Việt chỉ đếm mốc lẫy-bò-đi mà bỏ qua bập bẹ, chỉ trỏ, phản ứng gọi tên: chính là các mốc quan trọng nhất để phát hiện sớm vấn đề thính lực và phát triển.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Chuỗi bài trụ cột "Bé N tháng tuổi biết làm gì" (3→12 tháng) tiếp nối chuỗi 0-3 tháng — đo bằng tỷ lệ quay lại hằng tháng theo tuổi con. (2) SEO truy vấn so sánh - lo âu volume lớn: "trẻ 6 tháng chưa biết lẫy", "trẻ 9 tháng chưa mọc răng", "trẻ mấy tháng biết ngồi", "trẻ đi chân vòng kiềng", "xe tập đi có tốt không", "trẻ 11 tháng chưa biết đi có sao không". (3) Bài "xe tròn tập đi" và "chuẩn ca dao vs chuẩn y khoa" là nội dung phản đề tạo khác biệt — đo bằng số lượt trích dẫn/chia sẻ. (4) Red flags trình bày chuẩn mực dẫn sang hành động khám phát triển đúng nơi — xây uy tín nội dung có trách nhiệm; luồng đọc nối sang Phát triển của trẻ (1-3 tuổi) khi con tròn 1 tuổi.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con 5 tháng chưa lẫy trong khi 'ba tháng biết lẫy' — bà giục đi khám, con có chậm thật không hay do bụ quá?"; "Con 8 tháng bỏ qua bò, cứ trườn rồi đòi đứng — nghe nói không bò sau này học kém, thật không?"; "Con ngồi kiểu chữ W có hại không?"; "9 tháng chưa mọc cái răng nào — thiếu canxi không, có phải uống bổ sung?"; "Con 7 tháng người khác bế là gào khóc — trước ai bế cũng theo, giờ 'hư' vậy là sao?"; "Xe tròn tập đi bà mới mua cho cháu, mình đọc thấy bảo cấm — bỏ thì phí, nói bà sao đây?"; "Chân con cong cong khi đứng — vòng kiềng do đóng bỉm nhiều như hàng xóm nói?"; "Con 10 tháng chỉ nói 'mămăm', con nhà người ta đã gọi bà gọi mẹ — chậm nói không?"; "6 tháng đi khám bác sĩ bảo tăng cân chậm lại là bình thường mà bà cứ ép ăn thêm vì 'dạo này còi'"; "Gọi tên con không quay lại, mải chơi hay có vấn đề thính giác — phân biệt thế nào?". Nền chung: mỗi mốc con đạt chậm hơn "con nhà người ta" vài tuần đều thành đề tài bàn luận của cả họ — cha mẹ cần trọng tài khoa học đủ uy tín để chấm dứt tranh cãi.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Bài tập ép mốc: giúp con biết lẫy/ngồi/đi SỚM" — mốc vận động không cần và không nên ép sớm, nội dung kiểu này nuôi lo âu chạy đua; chỉ viết "tạo điều kiện" không viết "tăng tốc". "Xe tròn tập đi loại nào tốt" — thiết bị bị AAP khuyến cáo cấm; chỉ viết bài cảnh báo kèm lựa chọn thay thế an toàn. "Trẻ không bò sẽ kém thông minh/khó đọc viết" — phóng đại từ nghiên cứu yếu, gây hoảng cho cha mẹ có con bỏ qua bò (hiện tượng bình thường); viết bài giải oan thay vì lặp lại. "Bổ sung canxi cho nhanh mọc răng, cứng chân" — răng mọc theo gen không theo canxi, tự bổ sung có rủi ro; viết bài phản biện. "So sánh mốc bé trai bé gái" — khác biệt không có ý nghĩa hành động, chỉ thêm cớ so sánh. "Dấu hiệu trẻ thông minh sớm qua tháng biết đi/nói" — không có cơ sở, nuôi văn hóa so sánh mà chuyên mục đang chống. "Sàng lọc tự kỷ tại nhà cho bé dưới 12 tháng" — chưa đủ tuổi cho công cụ chuẩn (M-CHAT từ 16 tháng); chỉ nêu red flags giao tiếp sớm kèm hướng đi khám, không tự chẩn đoán.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-36 tuổi có con 3-12 tháng, con đầu lòng; sống trong mạng lưới so sánh dày đặc (ông bà, họ hàng, hội nhóm cùng tháng sinh — nơi mỗi ngày đều có bài "con em N tháng đã/chưa biết X"); mẹ đọc buổi trưa và tối, hay chụp ảnh/quay video con gửi nhóm hỏi ý kiến; mua sắm đồ tập (xe tập đi, địu, thảm chơi) theo lời khuyên truyền miệng nên rất cần bài phản biện dựa trên bằng chứng.',

            'constraints' => 'Mọi mốc ghi khoảng tuổi rộng kèm chữ "bình thường nếu..."; cấm giọng deadline, cấm nội dung "giúp đạt mốc sớm"; red flags nêu điềm tĩnh, kèm địa chỉ loại hình khám (khám phát triển ở khoa nhi/BV nhi) chứ không bỏ lửng; phản biện quan niệm dân gian bằng dẫn chứng, không chế giễu ông bà; nguồn AAP/CDC/WHO ghi rõ; không quảng cáo đồ chơi giáo dục, thiết bị tập; không dùng từ "chậm chạp", "còi", "vượt trội" mô tả trẻ.',

            'style_sample' => <<<'TEXT'
                "Ba tháng biết lẫy, bảy tháng biết bò, chín tháng lò dò biết đi" — câu ca dao ấy có lẽ là "bảng chuẩn phát triển" lâu đời nhất Việt Nam, và thú thật, nó cũng là nguồn cơn lo lắng của không biết bao nhiêu bà mẹ có con 4 tháng tuổi chưa chịu lật. Hôm nay mình thử đặt câu ca dao cạnh dữ liệu của Tổ chức Y tế Thế giới xem sao nhé. Kết quả thú vị lắm: về trình tự, các cụ đúng một cách đáng nể — lẫy rồi mới ngồi, ngồi rồi mới bò, bò rồi mới đi, đúng y trình tự y văn hiện đại. Nhưng về THỜI ĐIỂM, câu ca dao lấy mốc sớm nhất của những đứa trẻ nhanh nhất: dữ liệu WHO theo dõi hàng nghìn trẻ khỏe mạnh cho thấy trẻ biết lẫy ở bất kỳ đâu trong khoảng 3-7 tháng, biết đi độc lập trong khoảng 8-18 tháng — nghĩa là một em bé 6 tháng mới lẫy và 16 tháng mới đi vẫn nằm gọn trong vùng hoàn toàn bình thường, dù theo "chuẩn ca dao" thì đã chậm nửa năm. Vậy nên nếu chiều nay bà lại nhắc "con nhà người ta ba tháng đã lẫy", bạn có thể mỉm cười trả lời: các cụ đúng về đường đi, chỉ hơi vội về giờ đến. Còn khi nào sự chờ đợi mới thật sự cần chuyển thành một cuộc hẹn khám — mình có bảng dấu hiệu cụ thể ở cuối bài, rõ ràng và không dọa dẫm.
                TEXT,
        ],

        // === Trẻ nhỏ (3-12 tháng) > Ăn dặm & dinh dưỡng ===
        [
            'parent_slug' => 'tre-nho-3-12-thang',
            'slug'        => 'an-dam-dinh-duong',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: dấu hiệu sẵn sàng ăn dặm, 3 trường phái (truyền thống/Nhật/BLW) trung lập, lộ trình thô mịn, sắt, thực phẩm cấm dưới 1 tuổi, an toàn hóc nghẹn.
                - KHÔNG viết: sữa mẹ/công thức chuyên sâu (→ Nuôi con bằng sữa mẹ), cân nặng/biểu đồ (→ Phát triển của trẻ), tiêu chảy/táo bón bệnh lý (→ Bệnh thường gặp).
                - Trung lập CÓ NGUYÊN TẮC: không tôn sùng trường phái nào, chỉ giữ nguyên tắc bất biến (đủ sắt, tăng thô đúng nhịp, không ép ăn).
                - An toàn hóc nghẹn là tuyến bài BẮT BUỘC (cắt nho, xử lý xương cá, sơ cứu) — kỹ năng cứu mạng gần như không cha mẹ Việt nào được dạy.
                - Viết cho CẢ NHÀ nấu ăn dặm, không chỉ mẹ — bố/ông bà thường là người trực tiếp cho ăn ban ngày khi mẹ đi làm.
                - KPI: đo bằng lượt lưu/in bộ thực đơn tuần và tỷ lệ chia sẻ bài "thương lượng với ông bà về bữa ăn".
                TEXT,

            'core_focus' => <<<'TEXT'
                Toàn bộ hành trình ăn dặm 6-12 tháng (và chuẩn bị từ tháng 5): dấu hiệu con SẴN SÀNG ăn dặm (ngồi vững, hết phản xạ đẩy lưỡi — thay vì "cứ tròn 6 tháng" hay "4 tháng cho ăn sớm cứng cáp"), bản đồ trung lập 3 trường phái (ăn dặm truyền thống, kiểu Nhật, BLW bé tự chỉ huy — ưu nhược từng kiểu, kết hợp thế nào, chọn theo hoàn cảnh nhà mình), lộ trình thô mịn theo tháng (6-7-8-10-12: độ thô, số bữa, lượng ăn tham khảo), thực đơn mẫu nguyên liệu chợ Việt theo tuần, nhóm chất và thực phẩm giàu SẮT (mối thiếu hụt số 1 tuổi này), thực phẩm cần tránh trước 1 tuổi (mật ong, muối - nước mắm, sữa bò tươi, hạt nguyên), an toàn hóc nghẹn (cắt đồ ăn đúng cách, phân biệt ọe sinh lý vs hóc, sơ cứu Heimlich cho trẻ nhỏ), uống nước - sữa song song ăn dặm, và xử lý khủng hoảng: con ăn ít, biếng ăn sinh lý, ném đồ ăn, ĂN RONG - XEM TIVI KHI ĂN (cuộc chiến lớn nhất với ông bà), dị ứng thực phẩm (nguyên tắc thử món mới, dấu hiệu dị ứng). KHÔNG lấn sân: sữa mẹ/công thức chuyên sâu (Nuôi con bằng sữa mẹ), cân nặng - biểu đồ (Phát triển của trẻ), tiêu chảy - táo bón bệnh lý (Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Ăn dặm là chiến trường khốc liệt nhất của nội dung mẹ-bé Việt: các phe truyền thống - kiểu Nhật - BLW cãi nhau như tôn giáo, trong khi bà và mẹ trong cùng một nhà cũng đang cãi nhau về bát cháo. Khác biệt: (1) TRUNG LẬP có nguyên tắc: không tôn sùng trường phái nào, chỉ giữ các nguyên tắc bất biến có bằng chứng (đủ sắt, tăng thô đúng nhịp, không ép ăn, ăn có ghế có giờ) — còn hình thức thì hướng dẫn kết hợp linh hoạt kiểu "truyền thống buổi trưa cho bà đút, BLW bữa tối cùng cả nhà"; (2) Xử lý trực diện văn hóa ăn uống Việt quanh đứa trẻ: ăn rong đầu ngõ, bật tivi cho há mồm, nước hầm xương "đủ chất", ép hết bát mới thôi, so cân nặng — mỗi thứ một bài phân tích + kịch bản thương lượng với ông bà, vì mẹ thắng lý thuyết mà thua bữa cơm trưa ở nhà với bà; (3) An toàn hóc nghẹn được nâng thành tuyến bài bắt buộc (các site khác chỉ nói lướt): cắt quả nho, xử lý xương cá, sơ cứu — kỹ năng cứu mạng mà gần như không cha mẹ Việt nào được dạy.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn dày đặc nhất của năm đầu đời: "thực đơn ăn dặm cho bé 6/7/8 tháng", "ăn dặm BLW là gì", "bé 7 tháng ăn được gì", "cách nấu cháo cho bé", "bé ăn dặm bị táo bón", "trẻ 8 tháng biếng ăn" — cạnh tranh bằng độ thực dụng (nguyên liệu chợ Việt, ảnh độ thô từng tháng). (2) Bộ thực đơn tuần theo tháng tuổi là nội dung bookmark/in ra dán tủ lạnh — đo bằng lượt lưu và tỷ lệ quay lại. (3) Tuyến bài "thương lượng với ông bà về bữa ăn" và "an toàn hóc nghẹn" là nội dung khác biệt — đo bằng tỷ lệ chia sẻ. (4) Nối luồng: dấu hiệu sẵn sàng ↔ Phát triển của trẻ (ngồi vững), biếng ăn bệnh lý ↔ Bệnh thường gặp, và chuyển sang Dinh dưỡng cho trẻ (1-3 tuổi) khi con tròn 1 tuổi — vốn là giai đoạn "ăn cơm cùng nhà" nhiều vấn đề mới.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con 5 tháng rưỡi nhìn mồm người lớn tóp tép — cho ăn dặm sớm được chưa hay đợi đủ 6 tháng, bà bảo ngày xưa 4 tháng đã ăn bột?"; "Chọn truyền thống hay BLW đây — theo BLW thì bà không dám cho ăn sợ hóc, theo truyền thống thì mạng dọa con không biết nhai"; "Con ăn cứ ọe — bình thường hay sắp hóc, phân biệt sao, lỡ hóc thật thì làm gì?"; "Nấu cháo có được nêm mắm muối không, bà bảo nhạt thế ai ăn được"; "Bà hầm xương lấy nước nấu cháo cả tuần bảo đủ chất — mình nói không có chất bà không tin"; "Con 8 tháng đột nhiên ăn ít hẳn, nhè hết — biếng ăn sinh lý là gì, kéo dài bao lâu?"; "Cả nhà bế cháu ăn rong đầu ngõ, bật tivi mới há mồm — mình muốn ngồi ghế ăn nghiêm chỉnh mà một mình chống lại cả nhà"; "Mỗi bữa bà ép hết bát cháo, con khóc vẫn đút — mình xót mà không dám nói"; "Thử món mới thấy quanh miệng nổi đỏ — dị ứng hay dặm nước dãi, khi nào nguy hiểm?"; "Con ném thức ăn xuống sàn cười khanh khách — kệ hay phạt?"; "Lượng ăn bao nhiêu là đủ — con ăn 3 thìa mà hội nhóm khoe con họ hết bát tô". Nền chung: bữa ăn của con là nơi va chạm thế hệ gay gắt nhất trong nhà, và mẹ thường là người thua vì ban ngày không có mặt.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Trường phái X là tốt nhất, phân tích tại sao Y sai" — nuôi war tôn giáo ăn dặm, mất một nửa độc giả; chỉ viết trung lập kết hợp. "Thực đơn ăn dặm cho bé TĂNG CÂN VÙ VÙ" — tư duy nhồi cân phản khoa học đang là nỗi ám ảnh có hại của gia đình Việt; thay bằng bài "cân nặng bao nhiêu là đủ" theo percentile. "Bột ăn dặm/váng sữa/phô mai nào tốt nhất" dạng xếp hạng — thuộc Đánh giá sản phẩm, và váng sữa cần bài bóc tách riêng (bản chất là kem béo, không phải "tinh túy sữa" như quảng cáo). "Gia vị ăn dặm cho bé dưới 1 tuổi (mắm nhĩ, hạt nêm trẻ em)" dạng khuyên dùng — dưới 1 tuổi không cần nêm, "hạt nêm cho bé" là lách marketing; viết bài phản biện. "Mẹo cho con ăn hết bát: xem tivi, ăn rong có kiểm soát" — thỏa hiệp với thói quen có hại, đi ngược nguyên tắc ăn chủ động; chỉ viết lộ trình cai. "Nước hầm xương/nước dashi thần thánh" dạng tôn vinh — dashi ok như nước nấu nhưng không thay được đạm thật; viết đúng vai trò, không thần thánh hóa.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-36 tuổi có con 5-12 tháng bắt đầu ăn dặm — thời điểm trùng khớp mẹ đi làm lại nên bữa ăn ban ngày do bà/giúp việc/đôi khi bố đảm nhận theo cách cũ (ăn rong, tivi, ép ăn), mẹ hoặc bố chỉ nấu và cho ăn được bữa tối + cuối tuần; đọc công thức buổi tối để chuẩn bị đồ ăn hôm sau, tra cứu khẩn khi con ọe/nổi mẩn/biếng ăn; áp lực lớn nhất: cân nặng của con bị cả họ theo dõi như KPI và mọi bữa con ăn ít đều bị quy về "tại cho ăn kiểu mới".',

            'constraints' => 'Trung lập giữa các trường phái ăn dặm, không tôn sùng - không dè bỉu; không dùng cân nặng làm thước đo thành công bữa ăn; lượng ăn luôn ghi "tham khảo, tôn trọng tín hiệu no của con"; không ủng hộ ép ăn, ăn rong, màn hình khi ăn nhưng phê phán hành vi chứ không phê phán ông bà; công thức phải nấu được với chợ Việt, có phương án tiết kiệm; an toàn hóc nghẹn và thực phẩm cấm dưới 1 tuổi là ranh giới cứng dẫn nguồn WHO/Viện Dinh dưỡng; không quảng cáo bột, váng sữa, gia vị ăn dặm.',

            'style_sample' => <<<'TEXT'
                Bữa trưa nay ở nhà, bà cho cháu ăn hết veo bát cháo — bằng cách bế ra đầu ngõ chỉ chim chỉ xe, thêm 15 phút quảng cáo trên điện thoại. Tối về nghe bà khoe, bạn không biết nên mừng hay nên lo. Mình hiểu cảm giác đó, và trước khi bàn chuyện đúng sai, hãy công bằng với bà một câu: bà làm thế vì thương cháu thật lòng — trong "hệ điều hành" nuôi con của thế hệ trước, một đứa trẻ ăn hết bát là một đứa trẻ được chăm tốt, và người cho ăn giỏi là người có công. Vấn đề là khoa học dinh dưỡng hiện đại đã phát hiện ra cái giá của bát cháo ăn rong: khi con nuốt trong lúc mải nhìn tivi, não con không hề ghi nhận "mình đang ăn" — con không học được cảm giác đói - no, không học nhai, và dần dần chỉ ăn được khi có màn hình, thành cái vòng luẩn quẩn mà chính bà sau này cũng khổ. Cho nên cuộc chiến này không phải mẹ hiện đại chống bà cổ hủ — mà là cả nhà cùng chống một thói quen sẽ làm khổ tất cả. Bài này sẽ đưa bạn lộ trình 3 tuần "hạ cánh mềm": tuần 1 đưa bữa ăn về ghế nhưng giữ một "đặc quyền" cho bà, tuần 2 tắt dần màn hình bằng trò thay thế ngay tại bàn, tuần 3 trả bữa ăn về đúng nghĩa — kèm những câu nói giúp bà thấy mình là đồng minh chứ không phải bị tước quyền chăm cháu.
                TEXT,
        ],

        // === Trẻ nhỏ (3-12 tháng) > Bệnh thường gặp ===
        [
            'parent_slug' => 'tre-nho-3-12-thang',
            'slug'        => 'benh-thuong-gap-2',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: sốt mọc răng (phân biệt sốt bệnh thật), rối loạn tiêu hóa khi chuyển ăn dặm, viêm hô hấp tăng vọt khi đi nhà trẻ, tay chân miệng, viêm tai giữa, mốc tiêm 6-12 tháng.
                - KHÔNG viết: bệnh đặc thù sơ sinh (→ nhóm Trẻ sơ sinh), chấn thương do biết đi (→ nhóm Trẻ tập đi), ăn uống/thực đơn (→ Ăn dặm & dinh dưỡng), mốc phát triển (→ Phát triển của trẻ).
                - Bảng phân biệt CỤ THỂ "mọc răng vs bệnh thật" (ngưỡng nhiệt độ, thời gian sốt) — thay vì câu chung chung "sốt do mọc răng là bình thường".
                - Gắn chặt với cột mốc XÃ HỘI: con bắt đầu đi nhà trẻ nên lây chéo tăng vọt — mỗi bài bệnh hô hấp/tay chân miệng có phần "khi nào nên cho nghỉ học".
                - Kết luận hành động (phân tầng 3 mức) luôn đặt đầu bài, kèm mốc tiêm chủng đúng lịch Việt Nam.
                TEXT,

            'core_focus' => <<<'TEXT'
                Các vấn đề sức khỏe đặc trưng của giai đoạn 3-12 tháng — khi con vừa hết miễn dịch thụ động từ mẹ (suy giảm rõ sau tháng thứ 6), vừa bắt đầu ăn dặm, và một số đi nhà trẻ sớm nên ốm vặt tăng vọt so với 3 tháng đầu. Trọng tâm: sốt mọc răng — phân biệt sốt bệnh thật bằng ngưỡng cụ thể (mọc răng chỉ gây sốt nhẹ dưới 38.5°C kèm chảy dãi, cắn gặm — không gây sốt cao kéo dài, tiêu chảy nặng hay ho); rối loạn tiêu hóa khi chuyển ăn dặm (tiêu chảy thích nghi thức ăn mới vs tiêu chảy nhiễm trùng cần theo dõi mất nước; táo bón do thiếu chất xơ/nước); viêm hô hấp tăng vọt khi đi nhà trẻ (viêm mũi họng, viêm tiểu phế quản, viêm phổi — dấu hiệu thở nhanh/rút lõm lồng ngực cần cấp cứu); bệnh lây chéo ở môi trường nhóm trẻ (tay chân miệng phân độ nhẹ - nặng; viêm tai giữa khó nhận biết ở trẻ chưa biết nói); và lịch tiêm 6-12 tháng (cúm, viêm não Nhật Bản, phế cầu) kèm cách phân biệt sốt sau tiêm với sốt bệnh thật. KHÔNG lấn sân: bệnh đặc thù sơ sinh như vàng da (thuộc Bệnh thường gặp của Trẻ sơ sinh); chấn thương/ngộ độc do biết đi (thuộc nhóm Trẻ tập đi); ăn uống/thực đơn (thuộc Ăn dặm & dinh dưỡng); mốc phát triển vận động - ngôn ngữ (thuộc Phát triển của trẻ, cùng nhóm tuổi).
                TEXT,

            'unique_angle' => <<<'TEXT'
                "Sốt do mọc răng, bình thường thôi" — đúng ở nhà, nhưng vô dụng lúc 9 giờ tối khi con sốt 38.6°C và câu hỏi thật là CÓ phải đi khám không. Bài bệnh viện dừng lại đúng ở câu trấn an đó; bài dịch nước ngoài thì liệt kê triệu chứng khô khan, không gắn với việc con vừa đi nhà trẻ về. Ba điểm khác biệt: (1) Bảng phân biệt CỤ THỂ "mọc răng vs bệnh thật" — đúng ngưỡng nhiệt độ, thời gian sốt, để cha mẹ tự đánh giá tại chỗ; (2) Gắn với cột mốc XÃ HỘI thật — con đi nhà trẻ/gửi ông bà nên lây chéo tăng vọt, mỗi bài hô hấp/tay chân miệng có phần "khi nào nên cho nghỉ ở nhà" mà trang bệnh viện không đề cập; (3) Mỗi bệnh có bảng phân tầng ba mức hành động (theo dõi ở nhà / khám trong 24h / đi viện ngay) kèm mốc tiêm đúng lịch Việt Nam — thay vì kết luận mơ hồ "đưa trẻ đến cơ sở y tế gần nhất".
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn khẩn cấp có volume lớn và ổn định quanh năm: "trẻ sốt mọc răng bao nhiêu độ", "phân biệt sốt mọc răng và sốt bệnh", "bé ăn dặm bị tiêu chảy/táo bón", "trẻ đi nhà trẻ hay ốm vặt", "dấu hiệu tay chân miệng ở trẻ nhỏ", "trẻ 8 tháng chích ngừa viêm não Nhật Bản được chưa". (2) Trở thành nguồn tra cứu TRƯỚC KHI quyết định cho con nghỉ học/đi viện — đo bằng lượng truy cập trực tiếp và tỷ lệ quay lại mỗi lần con ốm, đặc biệt trong 1-2 tháng đầu đi nhà trẻ. (3) Liên kết chéo có chủ đích trong cụm 3-12 tháng: bài tiêu chảy - táo bón dẫn sang Ăn dặm & dinh dưỡng (cách điều chỉnh thực đơn khi con vừa khỏi bệnh), bài theo dõi sau ốm dẫn sang Phát triển của trẻ (khi ốm dài ngày ảnh hưởng tạm thời đến vận động - cân nặng, cha mẹ không nên hoảng vì mốc bị lùi vài tuần). (4) Bàn giao mượt sang nhóm tuổi kế tiếp — chuyên mục Bệnh thường gặp của Trẻ tập đi 1-3 tuổi (nơi trọng tâm chuyển sang chấn thương do biết đi và ngộ độc do tự bốc ăn) — giữ độc giả trong hành trình theo tuổi con mà site theo đuổi xuyên suốt từ thai kỳ.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con sốt 38 độ đúng lúc đang nhú cái răng đầu tiên — sốt vì mọc răng hay ốm thật, có cần hạ sốt và đi khám không?"; "Bắt đầu ăn dặm được hai tuần, con đi ngoài lỏng hơn, mùi khác hẳn — do đổi món hay bị tiêu chảy, có cần dừng ăn dặm không?"; "Con ba ngày không ị từ khi tập ăn dặm, rặn đỏ mặt gồng cả người — táo bón vì đổi thức ăn, xử lý sao mà không phải đi viện?"; "Cho con đi trẻ được một tháng mà tháng nào cũng ốm, hết viêm họng lại sốt — con yếu hay đi trẻ sớm quá, có bình thường không?"; "Con ho, thở hơi khò khè, thở nhanh hơn mọi khi — cảm thường hay viêm phế quản, khi nào phải đưa đi cấp cứu?"; "Lớp con vừa có bạn bị tay chân miệng — con có nguy cơ lây không, dấu hiệu ban đầu là gì, nổi mấy nốt thì phải cho nghỉ học?"; "Con sốt cao ba hôm liền không dứt, nổi mấy nốt nước nhỏ ở lòng bàn tay — tay chân miệng độ mấy, dấu hiệu nào là phải nhập viện ngay?"; "Sau đợt ho sổ mũi, con cứ dụi tai, đêm quấy khóc hơn hẳn mà chưa biết nói để chỉ chỗ đau — có phải viêm tai giữa, phân biệt kiểu gì?"; "Sổ tiêm ghi mốc viêm não Nhật Bản lúc 1 tuổi mà con mới 9 tháng đã sắp đi trẻ — có vắc-xin nào tiêm sớm hơn không, hay phải đợi?"; "Con sốt sau mũi tiêm nhắc 6in1/cúm — sốt do vắc-xin hay đang ủ bệnh khác, theo dõi ở nhà được đến khi nào?". Nền chung: đây là giai đoạn con ốm liên tục hơn hẳn ba tháng đầu vì hết kháng thể mẹ truyền lại mà chưa kịp xây miễn dịch riêng, cộng thêm môi trường nhóm trẻ mới — cha mẹ cần một ngưỡng hành động rõ ràng cho từng bệnh, thay vì phải đoán mò giữa hàng chục khả năng mỗi lần con húng hắng.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Vàng da kéo dài, nhiễm trùng sơ sinh, viêm rốn" — thuộc Bệnh thường gặp của nhóm Trẻ sơ sinh 0-3 tháng, đặc thù sinh lý giai đoạn đó đã khác hẳn; viết ở đây gây chồng lấn nội bộ và sai đối tượng đọc. "Chấn thương do ngã cầu thang/bỏng nước sôi khi con tự bò trèo, ngộ độc do tự bốc đồ vật cho vào miệng" — những rủi ro này rộ lên khi trẻ biết đi và hoạt động độc lập hơn hẳn, thuộc nhóm Trẻ tập đi 1-3 tuổi, chuyên mục Bệnh thường gặp ở đó đã đảm nhiệm phần này. "Thực đơn chi tiết cho trẻ đang tiêu chảy/táo bón, món nên kiêng khi ốm" — khía cạnh dinh dưỡng cụ thể (nấu gì, kiêng gì, đổi khẩu phần ra sao) thuộc Ăn dặm & dinh dưỡng; chuyên mục này chỉ dừng ở nhận biết bệnh lý và ngưỡng cần đi khám, không lấn sang thực đơn. "Con ốm liên miên có làm chậm mốc biết ngồi, biết bò không" — thuộc Phát triển của trẻ (3-12 tháng), viết ở đây sẽ trùng phạm vi cột mốc vận động; chỉ nhắc ngắn rằng ốm có thể làm con tạm lười vận động vài ngày, không đi sâu phân tích mốc. "Mẹo dân gian chữa sốt mọc răng/tay chân miệng bằng đắp lá, chích lể, cắt lể nướu" và "tự chẩn đoán - tự điều trị tay chân miệng hoàn toàn tại nhà không cần khám" — cả hai đều nguy hiểm thực tế với một nhóm bệnh có thể trở nặng rất nhanh ở tuổi này; không viết dạng hướng dẫn làm theo, chỉ viết dạng cảnh báo có dẫn chứng và luôn kèm ngưỡng phải đi khám.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-36 tuổi có con 3-12 tháng, giai đoạn con hết miễn dịch tự nhiên từ mẹ nên ốm vặt tăng rõ rệt; một phần đã cho con đi nhà trẻ/gửi ông bà trông cùng trẻ khác nên lo lây chéo; tra cứu trên điện thoại trong trạng thái LO LẮNG CẤP TÍNH ngay khi con vừa sốt/vừa tiêu chảy/vừa nổi nốt lạ, thường vào buổi tối hoặc giữa đêm; cần câu trả lời phân tầng rõ ràng để tự quyết theo dõi tại nhà hay đưa đi viện ngay.',

            'constraints' => 'Không hù dọa, không liệt kê bệnh hiếm gây hoảng loạn; mọi bệnh phải phân tầng rõ theo dõi tại nhà / khám trong 24h / đi viện ngay; không thay thế chẩn đoán của bác sĩ, luôn nhắc khám khi vượt ngưỡng; không kê đơn hay liều thuốc cụ thể ngoài nguyên tắc hạ sốt theo cân nặng có cảnh báo hỏi bác sĩ; dẫn nguồn Bộ Y tế, WHO, AAP, Bệnh viện Nhi TW; không quảng cáo thuốc, TPCN, phòng khám, dịch vụ tiêm chủng.',

            'style_sample' => <<<'TEXT'
                Con vừa nhú được nửa cái răng cửa hàng dưới, tối nay lại sốt 38.2 độ, má đỏ hây và chảy dãi ướt cả áo — bạn phân vân: cho uống hạ sốt luôn hay đợi xem có phải chỉ tại cái răng đang lên? Đây là câu hỏi gần như cha mẹ nào cũng gặp ở giai đoạn này, và có một nguyên tắc đáng nhớ: mọc răng có thể làm con khó chịu, chảy dãi, thích cắn gặm và sốt NHẸ (thường dưới 38.5°C, chỉ 1-2 ngày quanh lúc răng nhú), nhưng mọc răng không gây sốt cao kéo dài, không gây tiêu chảy nặng, không gây ho hay phát ban. Nói cách khác, nếu cơn sốt của con nhẹ và ngắn ngày, nhiều khả năng đúng là do răng; còn nếu sốt cao hơn, kéo dài hơn, hoặc đi kèm bất kỳ triệu chứng nào khác — đó là lúc cần tìm nguyên nhân thật sự, không nên đổ hết cho chiếc răng đang lên. Điều này càng quan trọng ở giai đoạn 3-12 tháng, khi con vừa hết lớp kháng thể mẹ truyền lại và có thể vừa đi nhà trẻ, nên một cơn sốt trùng hợp với đợt mọc răng rất dễ là hai chuyện khác nhau xảy ra cùng lúc. Trong bài này, mình sẽ đưa bạn bảng phân biệt cụ thể, cách hạ sốt an toàn tại nhà, và ngưỡng nhiệt độ - thời gian nào thì nên đưa con đi khám.
                TEXT,
        ],

        // === Trẻ tập đi (1-3 tuổi) — danh mục cha, bài pillar tổng quan giai đoạn ===
        [
            'parent_slug' => null,
            'slug'        => 'tre-tap-di-1-3-tuoi',

            'writer_insights' => <<<'TEXT'
                - Đây là danh mục CHA — chỉ bài TỔNG QUAN chuẩn bị tâm thế trước "khủng hoảng tuổi lên 2", dẫn vào 4 chuyên mục con. KHÔNG viết chi tiết ăn vạ/mốc/thực đơn/bệnh ở đây.
                - Vai trò: "bản đồ TRƯỚC bão" — mọi bài khủng hoảng tuổi lên 2 khác viết SAU khi cha mẹ đã hoảng; bài này giúp nhận ra ngay dấu hiệu đầu tiên.
                - Gộp 3 cuộc "cai" diễn ra gần như cùng lúc (bỉm, ti, bế/bồng) thành 1 bức tranh có thứ tự ưu tiên — không nguồn nào ở Việt Nam làm việc này.
                - Đây là MẮT XÍCH bận rộn nhất trong chuỗi bài theo tuổi — biến động dồn dập nhiều mặt cùng lúc khiến cha mẹ dễ lạc hướng đọc nhất.
                TEXT,

            'core_focus' => <<<'TEXT'
                Danh mục CHA của cụm 1-3 tuổi — chỉ bài TỔNG QUAN xuyên suốt giai đoạn: cẩm nang chuẩn bị tâm thế bước vào "khủng hoảng tuổi lên 2" (là gì, vì sao xảy ra, kéo dài bao lâu, khác gì con hư), các mốc lớn hội tụ cùng lúc (đi vững rồi chạy, nói câu ngắn 2-3 từ, cai bỉm, cai ti - bình sữa — những cuộc "chia tay" đồng thời khiến cả nhà đảo lộn), hai thay đổi tâm sinh lý cốt lõi (bùng nổ ý thức "cái tôi" khiến con nói KHÔNG, năng lực kiềm chế cảm xúc chưa theo kịp mong muốn), lịch khám - tiêm chủng 1-3 tuổi, và bài định hướng dẫn vào 4 chuyên mục con (Chăm sóc & nuôi dạy, Phát triển của trẻ, Dinh dưỡng cho trẻ, Bệnh thường gặp). Chi tiết kỹ thuật xử lý ăn vạ, mốc phát triển, thực đơn, bệnh cụ thể KHÔNG viết ở đây.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nếu 0-3 tháng là giai đoạn ngợp vì thiếu ngủ và 3-12 tháng là giai đoạn ra quyết định thuê - gửi con, thì 1-3 tuổi là lần đầu cha mẹ đối mặt một con người có Ý CHÍ RIÊNG — và gần như không ai được chuẩn bị trước cho cú sốc này. Vai trò riêng của danh mục cha: (1) Là "bản đồ trước bão" thay vì "nhật ký sau bão" — mọi bài khủng hoảng tuổi lên 2 khác viết SAU khi phụ huynh đã hoảng, còn bài pillar này giúp nhận ra ngay dấu hiệu đầu tiên; (2) Gộp ba cuộc "cai" (bỉm, ti, bế) diễn ra gần như cùng lúc thành một bức tranh có thứ tự ưu tiên — không nguồn nào ở Việt Nam làm việc này; (3) Định vị đây là MẮT XÍCH bận rộn nhất trong chuỗi bài theo tuổi, vì biến động dồn dập nhiều mặt cùng lúc khiến cha mẹ dễ lạc hướng đọc nhất trong ba năm đầu.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Bài pillar "Cẩm nang giai đoạn tập đi: chuẩn bị gì trước khủng hoảng tuổi lên 2" là điểm neo nhận diện giai đoạn, đón độc giả chuyển tiếp ngay sau sinh nhật 1 tuổi từ cụm Trẻ nhỏ 3-12 tháng và bàn giao sang Trẻ mầm non 3-6 tuổi khi con qua 3 tuổi — hai đầu nối quan trọng nhất của chuỗi hành trình theo tuổi con mà site theo đuổi. (2) SEO cho các truy vấn định danh - tổng quan giai đoạn có volume ổn định quanh năm: "khủng hoảng tuổi lên 2 là gì", "trẻ 1-3 tuổi cần chú ý những gì", "các cột mốc của trẻ tập đi", "nuôi con 2 tuổi mệt mỏi phải làm sao" — nhóm truy vấn cha mẹ gõ NGAY KHI vừa thấy dấu hiệu đầu tiên, trước khi họ biết mình cần tìm bài cụ thể nào. (3) Điều phối luồng đọc xuống đúng 4 chuyên mục con theo đúng loại nhu cầu (hành vi, phát triển, ăn uống, bệnh) — đo bằng tỷ lệ click từ bài pillar sang bài con tương ứng. (4) Xây cảm giác "được chuẩn bị trước" thành thương hiệu riêng của site cho giai đoạn khó nhất trong ba năm đầu, tăng khả năng độc giả tiếp tục theo site sang giai đoạn mầm non.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con tự nhiên đổi tính chỉ sau một đêm: cái gì cũng lắc đầu 'Hông!', ăn vạ lăn ra sàn giữa siêu thị — mới tháng trước còn là em bé ngoan gật đầu nghe lời, giờ mình không nhận ra con nữa"; "Nghe hội bạn nhắc 'khủng hoảng tuổi lên 2' như một bóng ma sắp đến — nó thực sự là gì, có phải bệnh không, kéo dài đến bao giờ thì hết?"; "Con vừa biết đi vững đã trèo lên bàn, mở được mọi ngăn tủ, mình chạy theo không kịp, cả ngày chỉ để canh con khỏi ngã"; "Bạn cùng tuổi con đã bỏ bỉm ban ngày, con mình chưa có dấu hiệu gì cả — có phải mình chậm cho con tập không?"; "Con 18 tháng vẫn ngậm ti giả cả ngày, cai thì gào khóc không ngủ nổi, không cai thì sợ hỏng răng"; "Sắp cho con đi nhà trẻ lần đầu, đọc đâu cũng thấy 'khủng hoảng xa cách', lo con khóc cả tháng không quen mà mình vẫn phải đi làm"; "Con nói được vài từ đơn lẻ chưa ghép được câu, trong khi con hàng xóm cùng tháng tuổi đã nói líu lo cả câu — con mình có chậm nói không?"; "Đùng một cái con biếng ăn hẳn, bữa nào cũng thành trận chiến, trong khi vài tháng trước ăn ngoan là thế"; "Cứ đi nhà trẻ về vài hôm là ốm, tay chân miệng, sốt virus, nghỉ làm liên tục sếp cũng bắt đầu để ý". Nền chung: đây là giai đoạn nhiều thay đổi lớn dồn vào cùng một lúc — thân thể, cảm xúc, ngôn ngữ, và cả môi trường sống khi con lần đầu rời vòng tay gia đình để đến nhà trẻ — khiến cha mẹ vừa mừng vì con lớn nhanh, vừa hoang mang không phân biệt được đâu là chuyện bình thường của tuổi lên 2 và đâu là dấu hiệu thực sự đáng lo.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Kịch bản xử lý ăn vạ chi tiết từng bước, lời thoại mẫu cụ thể" — thuộc chuyên mục con Chăm sóc & nuôi dạy, viết ở bài tổng quan sẽ trùng lặp nội bộ và làm bài pillar quá dài. "Bảng mốc vận động - ngôn ngữ - nhận thức chi tiết theo từng tháng tuổi" — thuộc Phát triển của trẻ; ở đây chỉ nhắc tên mốc lớn mang tính định hướng, không đi sâu số liệu. "Thực đơn mẫu, cách xử lý biếng ăn sinh lý cụ thể" — thuộc Dinh dưỡng cho trẻ. "Hướng dẫn xử trí tay chân miệng, sốt virus, tai nạn té ngã cụ thể" — thuộc Bệnh thường gặp; bài pillar chỉ nêu đây là giai đoạn cần lưu ý an toàn và bệnh theo mùa nhà trẻ, không đi vào xử trí. "So sánh chi tiết các phương pháp dạy con quốc tế (kỷ luật tích cực, Montessori, time-out)" — quá cụ thể cho một bài tổng quan giai đoạn, dễ khiến người đọc lạc hướng ngay từ điểm chạm đầu tiên; để dành phân tích sâu cho các bài tình huống cụ thể ở Chăm sóc & nuôi dạy. "Danh sách đồ dùng cần mua cho trẻ tập đi (ghế ăn, xe đẩy, giày tập đi)" — dễ thành bài mua sắm affiliate lệch trọng tâm, và đánh giá sản phẩm cụ thể đã có danh mục riêng. "Liệt kê 'dấu hiệu con là thiên tài' qua tốc độ biết đi, biết nói" — nuôi văn hóa so sánh mà toàn bộ chuỗi bài phát triển của site đang chủ trương chống lại, không phù hợp giọng điệu trấn an của bài mở đầu giai đoạn.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-38 tuổi có con 1-3 tuổi, phần lớn vừa mừng sinh nhật 1 tuổi cho con đầu lòng hoặc đang chuẩn bị tâm lý bước sang "tuổi lên 2"; nghe nhiều về khủng hoảng tuổi lên 2 qua lời kể bạn bè, hội nhóm nhưng chưa hình dung cụ thể; đọc ngay khi con vừa xuất hiện dấu hiệu lạ đầu tiên (lắc đầu liên tục, ăn vạ lần đầu) hoặc trước khi các mốc "cai" (bỉm, ti, bế) đến gần, muốn được biết trước để không hoảng khi việc thật sự xảy đến.',

            'constraints' => 'Không mô tả "khủng hoảng tuổi lên 2" như thảm họa hay bệnh lý, phải trấn an đây là giai đoạn phát triển bình thường và sẽ qua; không phán xét cha mẹ khi con ăn vạ hay chưa cai được bỉm - ti đúng "chuẩn"; không tạo deadline cứng cho các mốc "cai" — luôn nhấn mạnh khoảng dao động bình thường; giọng "đồng hành đi trước", không giảng dạy từ trên xuống; luôn dẫn rõ bài con tương ứng cho từng nhu cầu cụ thể; nội dung y tế - phát triển dẫn nguồn WHO/AAP/Bộ Y tế.',

            'style_sample' => <<<'TEXT'
                Mới tháng trước, con bạn vẫn còn là em bé ngoan ngoãn, bảo gì nghe nấy — vậy mà sáng nay, chỉ vì bạn đưa nhầm cái cốc màu xanh thay vì màu đỏ, con đã lăn ra sàn bếp gào khóc như thể cả thế giới sụp đổ. Bạn đứng đó, vừa buồn cười vừa hoang mang: con mình đổi tính từ bao giờ? Tin được không, đây chính xác là khoảnh khắc mà rất nhiều cha mẹ Việt bắt đầu nghe đến hai chữ "khủng hoảng tuổi lên 2" — và tin tốt là bạn không hề đơn độc, đây không phải con bạn hư đi mà là một bước ngoặt phát triển hoàn toàn dự đoán được. Ở tuổi này, con vừa khám phá ra một điều chấn động: mình là MỘT NGƯỜI RIÊNG BIỆT, có ý muốn khác với bố mẹ — nhưng vốn từ và khả năng kiềm chế cảm xúc lại chưa đủ để diễn đạt điều đó một cách văn minh, nên mọi thứ bung ra thành tiếng "Không!" và những cơn ăn vạ. Cùng lúc đó, cơ thể con cũng đang tập đi vững, tập nói câu ngắn, và sắp phải nói lời chia tay với bỉm, với ti giả — nhiều thay đổi dồn vào một năm ngắn ngủi. Bài này sẽ là tấm bản đồ giúp bạn biết trước điều gì sắp đến, và đọc gì ở đâu khi nó đến thật.
                TEXT,
        ],

        // === Trẻ tập đi (1-3 tuổi) > Chăm sóc & nuôi dạy ===
        [
            'parent_slug' => 'tre-tap-di-1-3-tuoi',
            'slug'        => 'cham-soc-nuoi-day',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: HÀNH VI/CẢM XÚC trẻ 1-3 tuổi — ăn vạ/khủng hoảng tuổi lên 2, cai bỉm, cai ti, hành vi xã hội đầu đời, ngày đầu đi nhà trẻ, an toàn nhà khi con biết trèo.
                - Ranh giới với "Chăm sóc & nuôi dạy" ở tuổi khác: đây là mốc con CHƯA có tư duy trừu tượng (ăn vạ vì chưa kiềm chế được cảm xúc) — khác hẳn tuổi 3-6 (nói dối, ghen tị em — đã hiểu được hậu quả hành động) và tuổi 6-12 (tự học, mạng xã hội).
                - KHÔNG viết: hành vi phức tạp hơn của 3-6 tuổi như nói dối/câu hỏi khó (→ Chăm sóc & nuôi dạy của Mầm non), mốc phát triển (→ Phát triển của trẻ), ăn uống (→ Dinh dưỡng cho trẻ).
                - Đây là lần đầu cha mẹ đối mặt "tantrum" — viết đúng tâm thế người mới, không giả định đã biết xử lý.
                - Xử lý 3 cuộc "cai" (bỉm, ti, bế) như 3 dự án riêng có lộ trình — không phải lời khuyên mơ hồ "đến lúc là tự cai".
                - Ví dụ ăn vạ nên có cả tình huống BỐ ở một mình với con nơi công cộng — không mặc định mẹ luôn có mặt khi con ăn vạ.
                TEXT,

            'core_focus' => <<<'TEXT'
                Cẩm nang thực hành hằng ngày cho cha mẹ đối mặt "tantrum" lần đầu: hiểu và xử lý ăn vạ - khủng hoảng tuổi lên 2 (phân biệt ăn vạ thao túng và ăn vạ vỡ oà cảm xúc thật, phản ứng tại nhà và nơi công cộng, thống nhất với ông bà), cai bỉm ban ngày (dấu hiệu sẵn sàng, lộ trình từng bước, xử lý tái phát), cai ti giả - ti mẹ - bình sữa (thời điểm phù hợp, cách cai nhẹ nhàng, đêm đầu không ti), dạy hành vi xã hội đầu đời (nói "không" đúng cách thay vì cắn - đánh khi tranh đồ chơi, chào hỏi, chia sẻ vừa sức), ngày đầu đi nhà trẻ (lo âu xa cách, cách chia tay dứt khoát), và an toàn nhà khi con biết trèo, mở được mọi ngăn tủ (child-proofing giai đoạn hiếu động nhất). KHÔNG lấn sân: hành vi phức tạp hơn của 3-6 tuổi như nói dối, ghen tị với em, câu hỏi khó (thuộc Chăm sóc & nuôi dạy của Trẻ mầm non — ranh giới: 1-3 tuổi là PHẢN XẠ CẢM XÚC, 3-6 tuổi là hành vi có TÍNH TOÁN/hiểu hậu quả); mốc phát triển vận động - ngôn ngữ (thuộc Phát triển của trẻ); ăn uống (thuộc Dinh dưỡng cho trẻ).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Phần lớn bài "ăn vạ tuổi lên 2" trên mạng viết chung chung "hãy kiên nhẫn, hãy đồng cảm" mà không có kịch bản cụ thể cho cha mẹ ĐANG RUN TAY lần đầu chứng kiến con gào khóc giữa đám đông. Ba điểm khác biệt: (1) Viết đúng tâm thế người mới lần đầu đối mặt "tantrum" — giải thích vì sao ăn vạ 1-3 tuổi khác hẳn "làm mình làm mẩy" của trẻ lớn (não kiềm chế cảm xúc chưa hình thành, không phải cố tình), kèm kịch bản thoại tự nhiên cho cả ở nhà lẫn nơi công cộng trước mặt ông bà; (2) Xử lý ba cuộc "cai" (bỉm, ti, bế) như ba dự án riêng có lộ trình, thay vì lời khuyên mơ hồ "đến lúc là tự cai"; (3) Kịch bản thương lượng với ông bà xót cháu khóc đòi đón về giữa buổi khi con lần đầu đi nhà trẻ, và cách phân biệt lo âu xa cách bình thường với dấu hiệu con thực sự không ổn ở lớp.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Trở thành nơi cha mẹ QUAY LẠI ngay khi cơn ăn vạ đầu tiên xảy ra và trong suốt năm đầu "tập đi" — đo bằng tỷ lệ đọc chuỗi bài liên quan (ăn vạ → cai bỉm → cai ti → đi nhà trẻ) và lượng người đọc quay lại theo từng cột mốc con đạt tới. (2) SEO cho truy vấn tình huống có volume lớn, ít bị bệnh viện - trang dịch chiếm sóng: "trẻ 2 tuổi ăn vạ phải làm sao", "cách cai bỉm cho bé 2 tuổi", "cai ti giả cho bé cách nào", "trẻ khóc khi đi nhà trẻ bao lâu thì hết", "trẻ cắn bạn ở lớp phải dạy sao". (3) Bàn giao độc giả sang Phát triển của trẻ khi câu hỏi ngả sang mốc ngôn ngữ - vận động, sang Dinh dưỡng cho trẻ khi ăn vạ chuyển thành ăn vạ trong bữa ăn, và lên Chăm sóc & nuôi dạy (mầm non) khi con qua 3 tuổi với hành vi phức tạp hơn. (4) Xây chuỗi nội dung nối tiếp từ Chăm sóc trẻ nhỏ (3-12 tháng) — nơi độc giả vừa quen với việc luyện ngủ, cai ti đêm — nay bước tiếp sang thử thách hành vi mới của tuổi biết đi.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con lăn ra giữa siêu thị gào khóc đòi mua đồ chơi, mọi ánh mắt đổ dồn vào mình — dỗ cũng không được, quát cũng không xong, bế lên thì càng giãy mạnh hơn"; "Bạn cùng tuổi con đã bỏ bỉm ban ngày cả tháng nay, con mình 2 tuổi rưỡi vẫn chưa có dấu hiệu muốn ngồi bô — có phải mình cho tập muộn quá không?"; "Tập cho con ngồi bô được vài hôm rồi lại tè dầm liên tục — có phải mình làm sai bước nào, hay dừng lại đợi con lớn thêm?"; "Con 18 tháng ngậm ti giả suốt ngày kể cả lúc chơi, nha sĩ dọa hỏng khớp cắn — cai thì con khóc không ngủ nổi cả đêm, cả nhà cùng mất ngủ theo"; "Con đi nhà trẻ được một tuần, sáng nào cũng bám chân mẹ khóc thét ở cổng trường, cô giáo bảo vào lớp là nín ngay nhưng mình vẫn đứng ngoài cổng nước mắt lưng tròng"; "Con giành đồ chơi với bạn là cắn tay bạn đau điếng, cô giáo gọi điện mà mình không biết dạy con thế nào cho đúng ở tuổi còn chưa nói sõi"; "Từ ngày biết trèo, con leo lên bàn, mở được cả khóa an toàn tủ bếp, mình không dám rời mắt một giây"; "Ông bà thương cháu khóc lúc đi học nên hay xin cô cho về sớm, khiến con càng không chịu quen lớp"; "Không biết ăn vạ đến mức nào là bình thường của tuổi lên 2, đến mức nào là mình đang chiều hư con". Nền chung: cha mẹ lần đầu chứng kiến một đứa trẻ có ý chí riêng và biết phản kháng, trong khi bản thân cũng lần đầu phải học cách đặt giới hạn mà không quát mắng hay nhượng bộ vô điều kiện — và thường phải làm điều đó dưới ánh mắt giám sát (và đôi khi can thiệp) của ông bà.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Trẻ nói dối, ghen tị với em mới sinh, hỏi về cái chết hay giới tính" — đây là hành vi phức tạp hơn, đòi hỏi tư duy trừu tượng mà trẻ 1-3 tuổi hầu như chưa có; thuộc phạm vi Chăm sóc & nuôi dạy của nhóm Trẻ mầm non 3-6 tuổi, viết ở đây sẽ đánh giá quá cao khả năng nhận thức của trẻ tập đi. "Bảng mốc vận động - ngôn ngữ chi tiết theo từng tháng (bao nhiêu từ vựng, khi nào chạy vững)" — thuộc chuyên mục Phát triển của trẻ, viết ở đây gây trùng lặp và lệch trọng tâm hành vi. "Thực đơn xử lý biếng ăn, mẹo cho con ăn rau" — thuộc Dinh dưỡng cho trẻ; chuyên mục này chỉ chạm ăn vạ ở khía cạnh CẢM XÚC - HÀNH VI chung, không đi vào bữa ăn cụ thể. "Phạt roi, dọa nạt, nhốt con vào phòng tối khi ăn vạ" — đi ngược nguyên tắc kỷ luật tích cực và có nguy cơ gây hại tâm lý; không viết dạng khuyên áp dụng, chỉ có thể viết bài giải thích vì sao các cách này phản tác dụng. "Ép con cai bỉm - cai ti trong 3 ngày bằng phương pháp cứng rắn kiểu nước ngoài" — nhiều phương pháp "cai nhanh" gây stress ngược cho trẻ và không phù hợp nhịp sống có ông bà tham gia chăm sóc của gia đình Việt; chỉ viết lộ trình từ tốn, tôn trọng nhịp riêng của từng trẻ. "So sánh trường mầm non - nhóm trẻ tốt nhất cho bé 18-24 tháng" — thuộc danh mục Trường mầm non & tiểu học, không viết ở đây để tránh chồng lấn nội bộ; chuyên mục này chỉ dừng ở khía cạnh tâm lý chia tay - làm quen lớp.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-38 tuổi có con đầu lòng 1-3 tuổi, đang đi làm toàn thời gian, lần đầu chứng kiến con ăn vạ và phản kháng nên chưa có kinh nghiệm xử lý; nhà thường có ông bà cùng chăm nên dễ bất đồng cách dạy; đọc trên điện thoại ngay sau một cơn ăn vạ vừa xảy ra hoặc tối muộn khi ôn lại chuyện trong ngày, cần giải pháp làm được ngay lần ăn vạ tiếp theo.',

            'constraints' => 'Không phán xét cha mẹ khi mất bình tĩnh hay khi con "thua" trong một tình huống cụ thể; không hù dọa hậu quả nếu chưa cai bỉm - ti "đúng tuổi"; không cổ vũ đòn roi, dọa nạt, phạt úp mặt vào tường; giải pháp phải khả thi khi có ông bà cùng chăm và khi cha mẹ đi làm cả ngày; luôn có kịch bản thoại tiếng Việt tự nhiên; luôn phân biệt rõ hành vi bình thường của tuổi và dấu hiệu cần thêm hỗ trợ chuyên môn.',

            'style_sample' => <<<'TEXT'
                Giữa lối đi siêu thị đông người, con bạn đột nhiên nằm vật xuống sàn, gào khóc ầm ĩ chỉ vì bạn không mua gói bánh in hình siêu nhân — và bạn đứng chôn chân, mặt nóng bừng vì cảm giác mọi ánh mắt xung quanh đang đánh giá mình là một người không biết dạy con. Hít một hơi đã, rồi mình nói bạn nghe điều này trước: con không đang "diễn" để chơi khăm bạn giữa đám đông, và bạn cũng không phải phụ huynh tồi. Ở tuổi 18-30 tháng, phần não chịu trách nhiệm kiềm chế cảm xúc của con còn cách rất xa mức trưởng thành — khi mong muốn (gói bánh) và khả năng kiểm soát cơn thất vọng va vào nhau, thứ bung ra ngoài chính là cơn ăn vạ này, không hơn không kém. Hiểu điều đó không làm tiếng khóc nhỏ lại ngay lập tức, nhưng nó giúp bạn đứng vững thay vì hoảng loạn nhượng bộ hay quát tháo. Trong bài này, mình sẽ đi qua các bước xử lý ngay tại chỗ (kể cả khi có hàng chục ánh mắt xung quanh), câu nên nói - nên tránh, cách thống nhất trước với ông bà để không ai "phá thế trận" của nhau, và cả phần bạn cần nhất: làm gì sau khi cơn bão đã qua và bạn vẫn còn run.
                TEXT,
        ],

        // === Trẻ tập đi (1-3 tuổi) > Phát triển của trẻ ===
        [
            'parent_slug' => 'tre-tap-di-1-3-tuoi',
            'slug'        => 'phat-trien-cua-tre-3',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: mốc vận động thô/tinh, NGÔN NGỮ bùng nổ mạnh nhất (18-36 tháng), và "giai đoạn vàng" tầm soát tự kỷ 18-24 tháng theo M-CHAT-R.
                - KHÔNG viết: hành vi/ăn vạ (→ Chăm sóc & nuôi dạy), bệnh (→ Bệnh thường gặp).
                - Tách bạch then chốt: "chậm nói đơn thuần" (vốn từ ít nhưng chỉ trỏ tốt, giao tiếp mắt tốt) vs dấu hiệu cần tầm soát sâu hơn — phần lớn nội dung Việt gộp chung gây hoang mang hoặc chủ quan sai.
                - M-CHAT-R chỉ có giá trị SÀNG LỌC, không phải chẩn đoán — luôn dẫn tới khám chuyên khoa, không để cha mẹ tự kết luận.
                - Đối thoại thẳng với văn hóa so sánh "con nhà người ta 2 tuổi đã nói cả câu" bằng dữ liệu khoảng bình thường.
                - KPI: đo bằng tỷ lệ quay lại theo chuỗi "Con N tháng tuổi" và tỷ lệ dẫn tới hành động khám đúng nơi (không chỉ đọc rồi hoang mang).
                TEXT,

            'core_focus' => <<<'TEXT'
                Các mốc phát triển của trẻ 1-3 tuổi và cách cha mẹ đồng hành đúng nhịp: vận động thô (đi vững rồi chạy, leo - xuống cầu thang, đá bóng), vận động tinh (xếp chồng khối, cầm bút nguệch ngoạc, tự xúc ăn), ngôn ngữ — mảng bùng nổ nhất giai đoạn này (vốn từ tăng từ ~20 từ lúc 18 tháng lên hàng trăm từ lúc 2 tuổi, ghép câu 2-3 từ, hiểu chỉ dẫn đơn giản), nhận thức và chơi tưởng tượng (chơi giả vờ, phân loại theo màu - hình), và mốc xã hội - cảm xúc (chơi cạnh bạn, đồng cảm sơ khai). Mỗi mốc nêu khoảng tuổi bình thường, cách tạo điều kiện tại nhà, và — nội dung nhạy cảm nhất của cụm 1-3 tuổi — dấu hiệu cần tầm soát sớm tự kỷ trong "giai đoạn vàng" 18-24 tháng (chưa chỉ trỏ, chưa gọi tên phản ứng, chưa chơi giả vờ) theo chuẩn M-CHAT/AAP, viết điềm tĩnh không hù dọa. KHÔNG lấn sân: hành vi ăn vạ - kỷ luật (thuộc Chăm sóc & nuôi dạy), bệnh lý (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Đây là giai đoạn ngôn ngữ bùng nổ mạnh nhất đời trẻ và cũng là "giai đoạn vàng" phát hiện sớm vấn đề phát triển — nội dung ở đây cần chính xác và cẩn trọng hơn mọi chuyên mục phát triển khác. Ba điểm khác biệt: (1) Tách bạch "chậm nói đơn thuần" (vốn từ ít nhưng hiểu tốt, giao tiếp mắt tốt, chỉ trỏ tốt) và dấu hiệu cần tầm soát sâu hơn (không chỉ trỏ, không phản ứng gọi tên) — phân biệt then chốt mà nội dung Việt hay gộp chung khiến cha mẹ quá hoảng hoặc quá chủ quan; (2) Viết tầm soát tự kỷ sớm bằng giọng điềm tĩnh, dựa công cụ chuẩn (M-CHAT-R), luôn kèm hành động cụ thể (khám ở đâu) thay vì liệt kê triệu chứng rồi bỏ lửng; (3) Đối thoại thẳng với văn hóa so sánh "con nhà người ta 2 tuổi đã nói cả câu" bằng dữ liệu khoảng bình thường, đặc biệt khi ông bà nói giọng địa phương khác khiến trẻ tiếp nhận ngôn ngữ chậm hơn.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Chuỗi bài trụ cột "Con 18/20/24/30/36 tháng biết làm gì" tiếp nối chuỗi mốc phát triển từ 0-3 tháng và 3-12 tháng — đo bằng tỷ lệ quay lại theo từng mốc tuổi của con. (2) SEO cho cụm truy vấn lo âu và tầm soát có volume rất lớn: "trẻ 2 tuổi chưa biết nói phải làm sao", "dấu hiệu tự kỷ ở trẻ 18 tháng", "trẻ mấy tháng biết ghép câu", "trẻ 2 tuổi chưa biết chạy có sao không", "M-CHAT là gì". (3) Bài "giai đoạn vàng tầm soát tự kỷ 18-24 tháng" là nội dung trách nhiệm xã hội cao — đo bằng tỷ lệ độc giả đi tới hành động khám đúng nơi thay vì chỉ đọc rồi hoang mang. (4) Nối luồng hai chiều: từ Phát triển của trẻ (3-12 tháng) khi con vừa qua 1 tuổi, và sang Phát triển của trẻ mầm non khi con qua 3 tuổi; đồng thời dẫn sang Chăm sóc & nuôi dạy khi câu hỏi ngả về hành vi thay vì mốc phát triển.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con 2 tuổi mới nói được vài từ đơn lẻ, chưa ghép được câu nào, trong khi con hàng xóm cùng tháng đã nói líu lo cả câu dài — con mình có chậm nói không hay chỉ là mỗi đứa một kiểu?"; "Đọc trên mạng thấy nói 18-24 tháng là 'giai đoạn vàng' phát hiện tự kỷ, tự nhiên soi lại con từng cử chỉ mà phát hoảng — con ít giao tiếp mắt hơn dạo trước có phải dấu hiệu không?"; "Con 20 tháng chưa biết chỉ tay vào thứ mình muốn, toàn kéo tay mẹ đến chỗ đó — có đáng lo không?"; "Con 2 tuổi rưỡi vẫn chưa chạy vững, hay ngã dúi dụi trong khi bạn cùng lớp đã chạy nhảy thoăn thoắt"; "Nghe người ta đồn nhà có ông bà nói giọng địa phương khác, bố mẹ nói tiếng Anh xen tiếng Việt làm con rối loạn ngôn ngữ, chậm nói hơn — có đúng không?"; "Con thích xếp đồ chơi thành hàng dài, không thích ai động vào — bình thường hay là dấu hiệu gì đó cần lo?"; "Không biết bao giờ nên đưa con đi khám tầm soát phát triển, đi đâu khám, quy trình thế nào, có phải chờ đến khi 'rõ hẳn' mới đi không?"; "Con nói được rồi mà tự nhiên vài tuần nay lại ít nói hẳn đi, có phải đang mất kỹ năng đã có không?". Nền chung: đây là giai đoạn ngôn ngữ và nhận thức của con thay đổi nhanh nhất, khiến mọi so sánh với "con nhà người ta" trở nên sắc bén hơn hẳn giai đoạn trước, trong khi đây cũng chính là cửa sổ thời gian quý giá nhất để can thiệp sớm nếu thực sự có vấn đề — cha mẹ cần vừa được trấn an đúng mức, vừa được chỉ rõ ràng khi nào nên hành động.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Ép con học nói sớm, học đếm số, nhận biết chữ cái từ 18 tháng để không thua bạn" — không phù hợp năng lực nhận thức tuổi này và nuôi tư duy chạy đua mốc mà chuyên mục đang chống lại; chỉ viết dạng "tạo điều kiện chơi mà học" tự nhiên. "Tự chẩn đoán tự kỷ tại nhà qua vài dấu hiệu rồi kết luận chắc chắn" — công cụ sàng lọc như M-CHAT chỉ có giá trị SÀNG LỌC, không phải chẩn đoán; bài viết chỉ dừng ở nêu dấu hiệu cần khám thêm, luôn nhấn mạnh cần chuyên gia đánh giá, tuyệt đối không để cha mẹ tự kết luận rồi hoảng loạn hoặc tự trấn an sai. "So sánh mốc phát triển bé trai - bé gái" — khác biệt ở tuổi này chưa đủ ý nghĩa thực tiễn, chỉ tạo thêm cớ so sánh không cần thiết. "Bài kiểu 'dấu hiệu con là thiên tài' qua tốc độ nói, tốc độ đi" — phản khoa học, nuôi văn hóa so sánh cùng gốc với nỗi lo lắng mà chuyên mục muốn hoá giải chứ không cổ vũ. "Sản phẩm - ứng dụng, thẻ học giúp con nói nhanh, phát triển IQ sớm" — thị trường đang khai thác đúng nỗi lo chậm nói của cha mẹ Việt bằng các sản phẩm chưa có bằng chứng; chuyên mục không quảng bá, chỉ viết bài phân tích trung lập nếu cần phản biện. "Liệt kê toàn bộ các dạng rối loạn phát triển hiếm gặp" — vượt quá nhu cầu và gây hoang mang không cần thiết cho phần lớn độc giả đang có con phát triển bình thường; chỉ tập trung vào các dấu hiệu sàng lọc phổ biến, thực dụng.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-38 tuổi có con 1-3 tuổi, quan tâm sát sao đến mốc phát triển của con, thường xuyên so sánh (chủ động hoặc bị động qua ông bà, hội nhóm, lớp học) với "con nhà người ta" cùng tháng tuổi; một bộ phận đang thấp thỏm sau khi đọc được thông tin về "giai đoạn vàng" tầm soát tự kỷ; đọc kỹ, tra cứu nhiều nguồn để đối chiếu, dễ lo âu nhưng cũng dễ được trấn an nếu thông tin đủ rõ ràng và có cơ sở.',

            'constraints' => 'Mọi mốc ghi khoảng tuổi rộng, cấm giọng deadline hay "phải đạt được lúc X tháng"; nội dung tầm soát tự kỷ phải viết đặc biệt cẩn trọng — điềm tĩnh, không hù dọa, không để người đọc tự chẩn đoán, luôn dẫn đến hành động khám chuyên khoa cụ thể; không nội dung so sánh giới tính hay "thiên tài sớm"; nguồn dẫn chuẩn WHO/AAP/CDC/M-CHAT-R chính thức; không quảng cáo sản phẩm - ứng dụng học sớm; không dùng từ "chậm", "kém" mô tả trẻ mà không kèm ngữ cảnh khoảng bình thường.',

            'style_sample' => <<<'TEXT'
                Sau bữa tối, bạn ngồi lướt điện thoại và vô tình đọc được dòng chữ khiến tim khẽ hẫng một nhịp: "18-24 tháng là giai đoạn vàng để phát hiện sớm tự kỷ". Bạn ngẩng lên nhìn con đang chơi xếp hình một mình trong góc phòng, và tự nhiên mọi cử chỉ quen thuộc bỗng trở nên đáng ngờ. Trước khi nỗi lo kịp cuốn bạn đi xa hơn, hãy cùng nhìn lại một cách bình tĩnh và có cơ sở. Cái gọi là "giai đoạn vàng" không có nghĩa là đi tìm bệnh trong từng cử chỉ của con, mà nghĩa là: đây là lúc vài dấu hiệu sàng lọc cụ thể — như con có chỉ tay vào thứ mình muốn không, có quay lại khi được gọi tên không, có chơi giả vờ (cho búp bê ăn, giả vờ nghe điện thoại) không — trở nên đáng tin cậy hơn để bác sĩ đánh giá, chứ không phải để cha mẹ tự phán đoán tại nhà rồi kết luận. Một đứa trẻ thích chơi một mình, thích xếp đồ theo hàng, hoàn toàn có thể là một đứa trẻ phát triển bình thường với sở thích riêng. Trong bài này, mình sẽ đưa bạn đúng bộ câu hỏi sàng lọc chuẩn M-CHAT-R mà bác sĩ nhi vẫn dùng, cách đọc kết quả mà không tự dọa mình, và bước tiếp theo cụ thể nếu bạn thấy cần yên tâm hơn bằng một buổi khám.
                TEXT,
        ],

        // === Trẻ tập đi (1-3 tuổi) > Dinh dưỡng cho trẻ ===
        [
            'parent_slug' => 'tre-tap-di-1-3-tuoi',
            'slug'        => 'dinh-duong-cho-tre',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: biếng ăn SINH LÝ tuổi lên 2, chuyển cháo sang cơm nát/thô, cai sữa công thức, ăn CÙNG mâm cơm gia đình, food jag (kén ăn tạm thời).
                - KHÔNG viết: kỹ thuật ăn dặm khởi đầu/3 trường phái/an toàn hóc nghẹn cơ bản (đã ở Ăn dặm & dinh dưỡng của nhóm Trẻ nhỏ — chuyên mục này chỉ TIẾP NỐI từ lúc con đã ăn thô cơ bản).
                - Giải oan bằng CON SỐ: tốc độ tăng cân sau 1 tuổi chậm hẳn so với năm đầu — con ăn ít hơn là điều chỉnh ĐÚNG, không phải "bỏ ăn".
                - Thực đơn phải tính đến nêm nếp thật của mâm cơm Việt (mắm, mì chính) — không phải công thức tách biệt không nhà nào duy trì nổi.
                - KPI: đo bằng lượt lưu/in bộ thực đơn tuần và tỷ lệ chia sẻ bài "giải oan biếng ăn sinh lý" trong hội nhóm.
                - Thực đơn và cảnh nấu ăn nên hình dung cả BỐ là người nấu/cho ăn cuối tuần — không mặc định chỉ mẹ vào bếp.
                TEXT,

            'core_focus' => <<<'TEXT'
                Dinh dưỡng cho trẻ 1-3 tuổi từ khi con đã qua ăn dặm và bắt đầu ăn theo mâm cơm gia đình: biếng ăn sinh lý tuổi lên 2 — biếng ăn phổ biến nhất đời trẻ vì tăng trưởng chậm lại so với năm đầu (không phải "con có bệnh" mà là quy luật tự nhiên), lộ trình chuyển từ cháo sang cơm nát rồi cơm hạt theo khả năng nhai, cai sữa mẹ - sữa công thức và thay bằng sữa tươi - nguồn canxi khác, xây thực đơn ăn CÙNG mâm cơm gia đình (nêm nếm vừa nhạt cho con vừa ngon cho người lớn), xử lý kén ăn (food jag), số bữa/lượng ăn tham khảo, và ăn vặt lành mạnh. KHÔNG lấn sân: kỹ thuật ăn dặm khởi đầu 6-12 tháng, 3 trường phái ăn dặm, an toàn hóc nghẹn cơ bản (đã thuộc Ăn dặm & dinh dưỡng — chuyên mục này chỉ tiếp nối từ lúc con đã ăn thô cơ bản).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nếu ăn dặm là cuộc chiến kỹ thuật, thì dinh dưỡng 1-3 tuổi là cuộc chiến TÂM LÝ — cha mẹ hoảng vì con ăn ít hơn hẳn so với hồi ăn dặm, dù đây là hiện tượng SINH LÝ BÌNH THƯỜNG. Ba điểm khác biệt: (1) Giải oan biếng ăn sinh lý bằng con số cụ thể — tốc độ tăng cân sau 1 tuổi chỉ bằng một phần nhỏ năm đầu, con ăn ít hơn là điều chỉnh ĐÚNG theo nhu cầu cơ thể; (2) Hướng dẫn chuyển từ cháo sang cơm nát CỤ THỂ theo tuổi và độ cứng — cha mẹ Việt hay sai theo hai hướng: giữ cháo xay quá lâu hoặc chuyển cơm hạt quá sớm; (3) Thực đơn "ăn cùng mâm cơm" tính đúng nêm nếp thật của bữa cơm Việt, thay vì công thức tách biệt không nhà nào duy trì nổi khi có ông bà cùng nấu.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn biếng ăn - chuyển thô có volume rất lớn và dai dẳng quanh năm: "trẻ 2 tuổi biếng ăn phải làm sao", "cách cho bé ăn cơm nát", "bé lười nhai chỉ nuốt chửng", "thực đơn cơm cho bé 2 tuổi", "trẻ chỉ ăn vài món phải làm sao". (2) Bộ thực đơn tuần "ăn cùng mâm cơm gia đình" theo độ thô tăng dần là nội dung bookmark - in dán tủ lạnh — đo bằng lượt lưu, nối tiếp thành công của bộ thực đơn ăn dặm ở nhóm tuổi trước. (3) Bài "giải oan biếng ăn sinh lý tuổi lên 2" là nội dung giảm lo âu — đo bằng tỷ lệ chia sẻ trong hội nhóm, vì chạm đúng nỗi sợ phổ biến nhất giai đoạn này. (4) Nối luồng hai chiều: tiếp nhận độc giả từ Ăn dặm & dinh dưỡng (nhóm Trẻ nhỏ 3-12 tháng) ngay khi con qua 1 tuổi, và bàn giao sang Dinh dưỡng cho trẻ của nhóm Trẻ mầm non khi con qua 3 tuổi và bắt đầu ăn ở trường.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con hồi ăn dặm ăn khỏe bao nhiêu, giờ 18 tháng lại ăn ít hẳn, mỗi bữa chỉ vài thìa cơm — con có bị làm sao không hay đang biếng ăn?"; "Chuyển từ cháo sang cơm nát mà con cứ ngậm, không chịu nhai, có khi nuốt chửng cả miếng to — sợ hóc mà không biết làm sao tập nhai cho con"; "Cai sữa công thức rồi mà không biết thay bằng gì, uống sữa tươi được chưa, bao nhiêu là đủ?"; "Con chỉ ăn đúng vài món quen: cơm trắng, trứng chiên, xúc xích — đút gì khác cũng nhè ra, có phải con kén ăn bệnh lý không?"; "Nấu riêng cho con mãi mệt quá, muốn cho con ăn chung mâm cơm cả nhà mà sợ nêm mắm muối hại thận con"; "Bà cứ ép con ăn hết bát, con khóc vẫn đút, mình xót mà không dám cản vì sợ bà giận"; "Con ngày càng gầy đi so với lúc 1 tuổi, đi khám bác sĩ bảo bình thường mà mình vẫn không yên tâm, có nên cho uống thêm sữa - thuốc bổ không?"; "Bạn con cùng tuổi ăn được cơm hạt rau củ đủ thứ, con mình vẫn phải xay nhuyễn mới chịu ăn — có phải mình chiều con quá không?"; "Giờ ăn nào cũng phải bật tivi - đi rong mới xong bát cơm, biết là không nên mà bỏ thì con nhịn đói cả buổi". Nền chung: đây là giai đoạn tăng trưởng chậm lại một cách tự nhiên khiến hầu hết trẻ ăn ít hơn so với hồi ăn dặm, nhưng vì không ai giải thích trước điều này, cha mẹ dễ hoảng và vô tình biến mỗi bữa ăn thành một cuộc chiến càng làm con sợ ăn hơn.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Kỹ thuật ăn dặm khởi đầu, ba trường phái truyền thống - kiểu Nhật - BLW" — đã thuộc chuyên mục Ăn dặm & dinh dưỡng của nhóm Trẻ nhỏ 3-12 tháng; viết lại ở đây sẽ trùng lặp, chuyên mục này chỉ tiếp nối từ lúc con đã ăn thô cơ bản. "Thực đơn tăng cân vù vù, ép ăn nhiều bữa phụ để bù cân" — tư duy nhồi cân bất chấp nhu cầu sinh lý đã chậm lại, đi ngược tinh thần "giải oan biếng ăn" của chuyên mục; thay bằng bài giải thích đúng nhu cầu năng lượng theo tuổi. "Bột ăn dặm - sữa tăng cân - thuốc kích thích ăn ngon miệng" dạng khuyên dùng — thị trường TPCN khai thác đúng nỗi sợ biếng ăn của cha mẹ Việt; chuyên mục không quảng bá, chỉ viết bài bóc tách bằng chứng nếu cần phản biện. "Mẹo cho con ăn hết bát bằng tivi - ăn rong có kiểm soát" — thỏa hiệp với thói quen gây hại lâu dài đã được cảnh báo ở chuyên mục ăn dặm trước đó; chuyên mục 1-3 tuổi tiếp tục giữ nguyên tắc ăn chủ động, chỉ viết lộ trình cai cho gia đình đang mắc phải. "Ép con ăn đa dạng ngay lập tức, phê phán con kén ăn là hư" — kén ăn (food jag) là hiện tượng phát triển bình thường ở tuổi này; viết dạng ép buộc sẽ phản khoa học và gây thêm áp lực bữa ăn; chỉ viết chiến lược giới thiệu món mới kiên nhẫn, không ép. "So sánh các loại sữa tươi - sữa công thức chuyển tiếp tốt nhất" — dạng xếp hạng sản phẩm thuộc Đánh giá sản phẩm, ở đây chỉ nêu tiêu chí chọn theo dinh dưỡng.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-38 tuổi có con 1-3 tuổi vừa qua giai đoạn ăn dặm, đang vật lộn với hiện tượng con ăn ít hẳn so với trước ("biếng ăn tuổi lên 2"); nấu ăn hằng ngày một mình hoặc cùng ông bà, muốn cho con ăn chung mâm cơm gia đình nhưng còn e ngại về nêm nếm và độ thô; tra cứu buổi tối để chuẩn bị bữa hôm sau, hoặc ngay sau một bữa ăn căng thẳng vừa xảy ra; áp lực cân nặng của con luôn bị cả nhà theo dõi sát.',

            'constraints' => 'Không dùng cân nặng - lượng ăn làm thước đo duy nhất, luôn giải thích biếng ăn sinh lý trước khi đưa giải pháp; không cổ vũ ép ăn, ăn rong, xem màn hình khi ăn nhưng không phê phán ông bà; thực đơn phải nấu được từ mâm cơm chợ Việt, có phương án tiết kiệm và nêm nếm phù hợp thận trẻ nhỏ; không quảng cáo sữa, TPCN, bột ăn dặm; luôn phân biệt "kén ăn bình thường" và dấu hiệu cần khám dinh dưỡng; dẫn nguồn Viện Dinh dưỡng quốc gia, WHO.',

            'style_sample' => <<<'TEXT'
                Hồi ăn dặm, con bạn từng ăn hết veo cả bát cháo thịt bò, vậy mà giờ 20 tháng tuổi, bữa nào con cũng chỉ nhón vài hạt cơm rồi đẩy bát ra, ngậm miệng lắc đầu nguầy nguậy. Bạn bắt đầu lo: hay con đang ốm, hay mình nấu dở đi, hay phải ép con ăn thêm cho đủ chất? Trước khi tự trách mình, hãy nhìn vào một con số ít ai kể cho bạn nghe: tốc độ tăng cân của trẻ sau 1 tuổi chậm lại RẤT NHIỀU so với năm đầu đời — một em bé có thể tăng gấp ba cân nặng lúc sinh trong năm đầu, nhưng cả năm thứ hai có khi chỉ tăng thêm 2-3kg. Cơ thể con đơn giản là không còn cần nhiều năng lượng như trước nữa, và việc con ăn ít hơn chính là con đang điều chỉnh ĐÚNG theo nhu cầu thật của mình, không phải dấu hiệu bệnh tật hay bạn nấu ăn kém. Cái khó không nằm ở việc ép con ăn nhiều hơn, mà ở việc bạn học cách tin vào tín hiệu no - đói của con và chuyển bữa ăn từ "nhồi cho đủ" sang "đa dạng và đúng giờ". Trong bài này, mình sẽ chỉ bạn cách nhận biết biếng ăn sinh lý bình thường, lộ trình chuyển từ cháo sang cơm nát không gây sợ ăn, và một thực đơn mẫu để cả nhà cùng ngồi vào một mâm cơm mỗi tối.
                TEXT,
        ],

        // === Trẻ tập đi (1-3 tuổi) > Bệnh thường gặp ===
        [
            'parent_slug' => 'tre-tap-di-1-3-tuoi',
            'slug'        => 'benh-thuong-gap-3',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: bệnh theo mùa dịch nhà trẻ (tay chân miệng, sốt virus, viêm tai giữa), dị ứng thực phẩm mới phát hiện, và TRỌNG TÂM SỐ MỘT: an toàn/sơ cứu tai nạn tại nhà (hóc, ngộ độc, té ngã, bỏng, đuối nước).
                - KHÔNG viết: bệnh sơ sinh (→ nhóm Trẻ sơ sinh), bệnh 3-12 tháng (→ Bệnh thường gặp của Trẻ nhỏ), ăn uống/biếng ăn (→ Dinh dưỡng cho trẻ).
                - Khác biệt bắt buộc giữ: nâng an toàn/sơ cứu tại nhà thành TRỌNG TÂM SỐ MỘT (không phải mục phụ) — cha mẹ Việt hầu như chưa từng được dạy sơ cứu, chỉ biết "bế chạy đi viện".
                - Mỗi loại tai nạn (hóc, ngộ độc, té ngã, bỏng) cần 1 bài riêng có sơ cứu chi tiết từng bước — không gộp chung 1 bài liệt kê nông.
                - KPI: đo bằng tỷ lệ lưu/in bài "sơ cứu tại nạn tại nhà" và tỷ lệ chia sẻ trong hội nhóm phụ huynh — nội dung có khả năng cứu mạng thực sự.
                TEXT,

            'core_focus' => <<<'TEXT'
                Các vấn đề sức khỏe và an toàn nổi bật khi con 1-3 tuổi bắt đầu đi nhà trẻ và hiếu động khắp nhà, viết theo logic: nhận biết → mức độ → xử trí tại nhà → ngưỡng đi khám. Trọng tâm bệnh theo mùa dịch nhà trẻ: tay chân miệng (dấu hiệu, phân biệt nhẹ - nặng, cách ly), sốt virus - cúm - viêm họng lây lan nhanh khi tiếp xúc đông trẻ; viêm tai giữa (dấu hiệu kéo tai - quấy khóc đêm); dị ứng thực phẩm mới phát hiện khi con ăn thô đa dạng món (hải sản, trứng, đậu phộng); và mảng TRỌNG TÂM SỐ MỘT của giai đoạn hiếu động: an toàn và sơ cứu tai nạn tại nhà (hóc dị vật, ngộ độc do nuốt nhầm, té ngã từ ban công - cầu thang, bỏng, đuối nước); và lịch tiêm nhắc 18-24 tháng. KHÔNG lấn sân: bệnh đặc thù sơ sinh (thuộc Bệnh thường gặp của Trẻ sơ sinh), bệnh giai đoạn 3-12 tháng (thuộc Bệnh thường gặp của Trẻ nhỏ); ăn uống - biếng ăn (thuộc Dinh dưỡng cho trẻ).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Đây là giai đoạn hai rủi ro sức khỏe tăng vọt cùng lúc: con lần đầu tiếp xúc đông trẻ khác nên bệnh lây lan nhanh hơn hẳn, và con đủ nhanh nhẹn - tò mò để tự gây tai nạn trong chính ngôi nhà mình. Ba điểm khác biệt: (1) Bài tay chân miệng - sốt virus viết theo đúng nhịp MÙA DỊCH nhà trẻ Việt Nam (cao điểm tháng 3-5 và 9-11) kèm hướng dẫn khi nào nên cho con nghỉ học — thứ mọi phụ huynh có con đi nhà trẻ cần mà bài bệnh viện chung chung không đề cập; (2) Nâng an toàn - sơ cứu tại nhà thành TRỌNG TÂM SỐ MỘT thay vì mục phụ — cha mẹ Việt hầu như chưa từng được dạy sơ cứu hay xử trí ngộ độc tại nhà, chỉ biết "bế chạy đi viện"; (3) Dị ứng thực phẩm đặt đúng bối cảnh — mốc con bắt đầu ăn thô đa dạng món trong mâm cơm gia đình chính là lúc dị ứng lần đầu bộc lộ.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn khẩn cấp và theo mùa dịch có volume lớn: "dấu hiệu tay chân miệng ở trẻ", "trẻ đi nhà trẻ hay ốm vặt phải làm sao", "sơ cứu hóc dị vật cho trẻ", "trẻ bị ngộ độc thực phẩm tại nhà", "lịch tiêm nhắc cho trẻ 18 tháng", "trẻ kéo tai quấy khóc đêm". (2) Bài "sơ cứu tai nạn tại nhà" (hóc - ngộ độc - té ngã - bỏng) là nội dung TRÁCH NHIỆM XÃ HỘI cao nhất của cả site — đo bằng tỷ lệ lưu/in ra và chia sẻ rộng trong hội nhóm phụ huynh, có khả năng cứu mạng thực sự. (3) Trở thành "bộ lọc bình tĩnh" cha mẹ mở khi con vừa đi nhà trẻ về mà sốt - mệt — đo bằng tỷ lệ giảm số chuyến đi viện không cần thiết đồng thời không bỏ sót ca cần cấp cứu thật. (4) Nối luồng: bài tay chân miệng ↔ dấu hiệu cần cách ly ở nhà trẻ, dị ứng thực phẩm ↔ Dinh dưỡng cho trẻ (giới thiệu món mới), và tiếp nối từ Bệnh thường gặp (3-12 tháng) sang Bệnh thường gặp của nhóm Trẻ mầm non khi con qua 3 tuổi.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con mới đi nhà trẻ được hai tuần đã sốt, ho, nổi mấy nốt đỏ trong miệng — cô giáo bảo lớp đang có vài bạn tay chân miệng, mình có nên cho con nghỉ học luôn không?"; "Cứ đi học về là ốm, hết đợt này đến đợt khác, xin nghỉ làm trông con hoài mà sếp bắt đầu để ý, mình cũng kiệt sức không kém con"; "Đêm nào con cũng tỉnh giấc khóc, cứ dụi tai liên tục — viêm tai giữa hay chỉ ngứa tai bình thường, có tự khỏi không?"; "Cho con ăn thử tôm lần đầu, xong nổi mẩn đỏ khắp người, sưng môi — dị ứng thật hay chỉ nổi mề đay bình thường, cần đi viện ngay không?"; "Con đang chơi ngoài sân bỗng ho sặc sụa, tím tái mặt vì nuốt hạt nhãn — lúc đó mình luống cuống không biết làm gì, chỉ biết vỗ lưng bừa"; "Con leo lên ban công rồi ngã, may chỉ xây xát nhưng nghĩ lại vẫn rùng mình — nhà mình chưa có rào chắn gì cả"; "Con mở được tủ thuốc, cắn thử vài viên thuốc của bà, không biết có sao không, có cần đi viện không hay theo dõi tại nhà"; "Con nghịch nước trong xô ở nhà tắm, mình chỉ quay đi một chút để lấy khăn"; "Lịch tiêm nhắc 18-24 tháng có những mũi nào, quên mất một đợt vì bận chuyển nhà trẻ thì phải bù lại sao?". Nền chung: con vừa hiếu động và tò mò đủ để tự tạo ra tai nạn trong chính ngôi nhà quen thuộc, vừa lần đầu tiếp xúc với môi trường đông trẻ nên bệnh lây lan dễ dàng hơn hẳn — an toàn và sức khỏe cùng lúc trở thành nỗi lo thường trực của cha mẹ.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Vàng da, nhiễm trùng rốn, các bệnh đặc thù sơ sinh" — thuộc Bệnh thường gặp của nhóm Trẻ sơ sinh 0-3 tháng, không còn phù hợp với trẻ đã 1-3 tuổi. "Sốt mọc răng, tiêu chảy do chuyển sữa, các vấn đề đặc trưng giai đoạn 3-12 tháng" — thuộc Bệnh thường gặp của nhóm Trẻ nhỏ 3-12 tháng, đang được biên tập riêng; viết lại ở đây sẽ trùng lặp nội bộ giữa hai nhóm tuổi liền kề. "Thực đơn cho trẻ dị ứng, cách chế biến món thay thế" — thuộc Dinh dưỡng cho trẻ; chuyên mục này chỉ dừng ở nhận biết phản ứng dị ứng và sơ cứu ban đầu, không đi vào thực đơn thay thế dài hạn. "Mẹo dân gian chữa tay chân miệng, sốt bằng lá thuốc - cắt lể" — nguy hiểm, có thể trì hoãn điều trị đúng cách với bệnh diễn tiến nhanh như tay chân miệng độ nặng; chỉ viết bài cảnh báo dẫn chứng, không hướng dẫn áp dụng. "Tự ý dùng kháng sinh khi con sốt - ho tại nhà" — tuyệt đối không nội dung kê đơn hay khuyến khích tự mua kháng sinh; chỉ viết bài giải thích vì sao cần bác sĩ chỉ định. "Liệt kê tất cả các loại tai nạn trẻ em có thể gặp trong 1 bài tổng hợp" — quá nông, không đủ chi tiết để thực sự cứu được ai trong tình huống khẩn cấp thật; mỗi loại tai nạn (hóc, ngộ độc, té ngã, bỏng, đuối nước) cần một bài riêng có sơ cứu chi tiết từng bước.
                TEXT,

            'audience' => 'Cha mẹ Việt 26-38 tuổi có con 1-3 tuổi mới bắt đầu đi nhà trẻ, thường xuyên đối mặt con ốm vặt do lây bệnh từ môi trường tập thể lần đầu; đồng thời lo lắng vì con đủ nhanh nhẹn để tự gây tai nạn trong nhà mà cha mẹ không kịp trở tay; tra cứu trong trạng thái lo lắng cấp tính (con đang sốt - vừa hóc - vừa ngã) thường vào buổi tối hoặc cuối tuần khi phòng khám ít giờ mở; cần câu trả lời hành động được NGAY trong vài phút.',

            'constraints' => 'Kết luận hành động (bảng phân tầng theo dõi tại nhà / khám sớm / cấp cứu ngay) phải nằm ngay đầu bài; hướng dẫn sơ cứu phải chia bước cụ thể làm theo được dưới áp lực; không hù dọa bằng liệt kê biến chứng hiếm gặp; không nội dung kê đơn thuốc, liều lượng (trừ nguyên tắc hạ sốt cơ bản kèm nhắc hỏi bác sĩ); mẹo dân gian nguy hiểm phải cảnh báo bằng dẫn chứng, không giễu cợt; nguồn Bộ Y tế, WHO, AAP, Bệnh viện Nhi; không quảng cáo thuốc, TPCN, phòng khám, dịch vụ tiêm chủng.',

            'style_sample' => <<<'TEXT'
                Con đang ngồi chơi ngoài sân, cắn hạt nhãn ai đó vô tình để rơi, rồi bất chợt ho sặc sụa, mặt tím tái, không phát ra tiếng nào — và bạn đứng đó với vài giây quý giá nhất đời mà đầu óc trống rỗng, không biết làm gì ngoài vỗ bừa vào lưng con. Đây là chính xác khoảnh khắc mà mọi cha mẹ có con 1-3 tuổi nên được chuẩn bị trước, vì ở tuổi hiếu động và tò mò này, dị vật đường thở là một trong những tai nạn có thể xảy ra bất cứ lúc nào — và cũng là một trong số ít tình huống mà vài giây xử trí đúng tạo ra khác biệt hoàn toàn. Điều đầu tiên cần phân biệt: nếu con vẫn ho được thành tiếng, vẫn khóc được, đó là dấu hiệu tốt — đường thở chưa tắc hoàn toàn, hãy để con tự ho tống dị vật ra, đừng vỗ lưng hay móc họng bừa vì có thể đẩy dị vật vào sâu hơn. Chỉ khi con không phát ra tiếng nào, không ho được, môi bắt đầu tím tái — đó là lúc cần hành động ngay lập tức bằng kỹ thuật vỗ lưng - ấn ngực dành riêng cho trẻ trên 1 tuổi. Trong bài này, mình sẽ hướng dẫn bạn từng bước chính xác, kèm hình minh họa, để bạn tập trước một lần trong đầu — vì đây là kỹ năng bạn hy vọng không bao giờ phải dùng, nhưng nếu cần, sẽ cần đến ngay lập tức.
                TEXT,
        ],

        // === Trẻ mầm non (3-6 tuổi) — danh mục cha, bài pillar tổng quan giai đoạn ===
        [
            'parent_slug' => null,
            'slug'        => 'tre-mam-non-3-6-tuoi',

            'writer_insights' => <<<'TEXT'
                - Đây là danh mục CHA — chỉ bài TỔNG QUAN 3 năm mầm non, dẫn vào 4 chuyên mục con + Trường mầm non & tiểu học ở năm cuối. KHÔNG viết chi tiết hành vi/mốc/thực đơn/bệnh ở đây.
                - Lấp khoảng trống bị bỏ lửng: nội dung Việt viết dày cho 0-3 tuổi rồi nhảy thẳng sang ôn thi lớp 1, bỏ trống chính 3 năm bản lề này.
                - Trọng tâm đúng của 3 năm: KHÔNG phải chạy đua học chữ/số/tiếng Anh sớm — mà là xây nền tâm lý, cảm xúc, kỹ năng xã hội, vận động tinh.
                - Giọng trấn an từng năm một: mỗi tuổi (3, 4, 5) có "kịch bản" thay đổi tính khí riêng mà cha mẹ ít được cảnh báo trước.
                TEXT,

            'core_focus' => <<<'TEXT'
                Danh mục CHA của cụm 3-6 tuổi — chỉ bài TỔNG QUAN xuyên suốt 3 năm mầm non: đặc điểm tâm sinh lý (trí tưởng tượng bùng nổ, tư duy còn ích kỷ hồn nhiên, học qua chơi, cái tôi lớn dần), lộ trình theo năm học (3-4 tuổi: tách mẹ, làm quen nề nếp; 4-5 tuổi: chơi nhóm phức tạp hơn, ngôn ngữ bùng nổ; 5-6 tuổi: tiền lớp 1 — sẵn sàng tâm lý/kỹ năng/nhận thức, không chỉ biết chữ biết số), lịch mốc y tế - hành chính, và bài định hướng dẫn vào 4 chuyên mục con cùng Trường mầm non & tiểu học ở năm cuối. Chi tiết hành vi - cột mốc phát triển - thực đơn - bệnh tật KHÔNG viết ở đây.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Giai đoạn 3-6 tuổi bị đa số nội dung mẹ-bé Việt bỏ lửng ở giữa: viết dày cho 0-3 tuổi rồi nhảy thẳng sang ôn thi lớp 1, bỏ trống 3 năm bản lề hình thành nhân cách và nền tảng học tập. Vai trò riêng của danh mục cha: (1) Là "mục lục sống" cho cha mẹ vừa qua 2-3 năm tập đi cực nhọc, giúp biết vấn đề mới (con cãi lại, hỏi chuyện người lớn, sắp vào lớp 1) thuộc mảng nào; (2) Đặt đúng trọng tâm: đây KHÔNG phải giai đoạn chạy đua học chữ - số - tiếng Anh sớm, mà là xây nền tâm lý, cảm xúc, kỹ năng xã hội, vận động tinh — quyết định con vào lớp 1 tự tin hay không hơn là đã đọc thông viết thạo; (3) Trấn an từng năm một: mỗi tuổi (3, 4, 5) có "kịch bản" thay đổi tính khí riêng mà cha mẹ ít được cảnh báo trước.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Bài pillar "Tổng quan 3 năm mầm non" là điểm neo cho toàn bộ cụm — đón độc giả chuyển tiếp trực tiếp từ danh mục cha Trẻ tập đi (1-3 tuổi) ngay khi con bước sang tuổi thứ 3, và là nơi đầu tiên cha mẹ ghé qua mỗi khi có thắc mắc mới chưa biết xếp vào đâu. (2) SEO cho truy vấn tổng quan, ít cạnh tranh hơn truy vấn tình huống cụ thể nhưng có giá trị định vị cao: "trẻ mầm non phát triển như thế nào", "3-6 tuổi cần chuẩn bị gì cho con", "chuẩn bị cho con vào lớp 1 từ mấy tuổi", "sự khác nhau giữa mẫu giáo bé nhỡ lớn". (3) Điều phối luồng đọc xuống đúng lúc 4 chuyên mục con — Chăm sóc & nuôi dạy, Phát triển của trẻ, Dinh dưỡng cho trẻ, Bệnh thường gặp — đo bằng CTR từ bài pillar sang bài con. (4) Ở năm cuối mầm non, dẫn độc giả sang Trường mầm non & tiểu học để tìm hiểu chọn trường, rồi tiếp tục hành trình sang cụm Trẻ tiểu học (6-12 tuổi) ngay khi con qua lễ tổng kết mầm non — mắt xích tiếp theo trong chuỗi nội dung theo tuổi con mà site theo đuổi từ thai kỳ.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con vừa qua 2-3 tuổi ẩm ương tưởng giờ nhẹ nhàng hơn, ai ngờ 4 tuổi lại phát sinh kiểu bướng khác — mọi người bảo 'hết khủng hoảng lên 3' là yên, sao mình vẫn thấy mệt?"; "Mầm non những gì cần chuẩn bị trước khi vào lớp 1, bắt đầu từ năm nào, có phải đến 5 tuổi mới cuống lên là muộn?"; "3 tuổi mẫu giáo bé khác gì 5 tuổi mẫu giáo lớn — con thay đổi ra sao mỗi năm để mình đỡ bị động?"; "Cả lớp mẫu giáo lớn của con đã đi học thêm chữ, học tiếng Anh từ 4 tuổi — không cho con học có bị tụt lại thật không?"; "Con hỏi những câu ngày càng khó (chết là gì, tại sao trời mưa, sao bà không đi làm) — ở tuổi này nên trả lời tới đâu?"; "Sắp hết mầm non rồi mà mình chưa biết bắt đầu tìm hiểu trường tiểu học từ lúc nào, tiêu chí gì, nộp hồ sơ khi nào là vừa"; "Con đi học suốt ngày, mỗi hôm về nhà đã một tính khác — hôm cáu gắt, hôm lại quấn mẹ như hồi bé — có phải do trường lớp áp lực con không?"; "Muốn có một lộ trình tổng thể 3 năm để yên tâm, chứ đọc lẻ tẻ từng bài mỗi nơi một kiểu thấy rối". Nền chung: cha mẹ bước vào giai đoạn này với tâm lý "chắc đỡ vất vả hơn hồi bé" nhưng nhanh chóng nhận ra đây là 3 năm có nhiều quyết định nền tảng (nề nếp, chuẩn bị vào lớp 1, chọn trường) mà không ai cung cấp cho họ một bản đồ tổng thể để đi theo.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Hướng dẫn chi tiết xử lý hành vi (ăn vạ, nói dối, cãi lại), kỷ luật tích cực" — thuộc chuyên mục con Chăm sóc & nuôi dạy, viết ở đây gây trùng lặp nội bộ. "Các mốc phát triển vận động tinh - ngôn ngữ - nhận thức theo từng năm" — thuộc chuyên mục con Phát triển của trẻ. "Thực đơn bán trú, suy dinh dưỡng, thừa cân" — thuộc chuyên mục con Dinh dưỡng cho trẻ. "Bệnh hô hấp, tay chân miệng, sâu răng, cận thị học đường" — thuộc chuyên mục con Bệnh thường gặp. "Review, xếp hạng trường mầm non/tiểu học cụ thể" — thuộc chuyên mục Trường mầm non & tiểu học, danh mục cha chỉ nêu định hướng "khi nào bắt đầu tìm hiểu trường", không đi vào tiêu chí chọn trường chi tiết. "Cho con học chữ - học tính - học tiếng Anh sớm để không thua bạn bè" — đi ngược quan điểm biên tập phát triển đúng lứa tuổi của toàn site, cổ vũ áp lực học sớm; thay bằng bài đúng nghĩa "chuẩn bị sẵn sàng vào lớp 1" nói rõ sẵn sàng không đồng nghĩa biết đọc biết viết trước.
                TEXT,

            'audience' => 'Cha mẹ Việt 28-40 tuổi có con vừa qua giai đoạn tập đi, sắp hoặc vừa bước vào 3 năm mầm non (3-6 tuổi), sống thành thị/ven đô, cả hai đi làm toàn thời gian; đọc bài này ở những thời điểm CHUYỂN TIẾP — con vào mẫu giáo bé lần đầu, chuyển lớp mỗi năm học, hoặc năm cuối trước khi vào lớp 1 — khi họ cần một bức tranh tổng thể trước khi lặn sâu vào từng vấn đề cụ thể.',

            'constraints' => 'Giọng người dẫn đường tổng thể, không đi sâu chi tiết (chi tiết thuộc bài con); không tạo cảm giác gấp gáp phải chuẩn bị cho con quá nhiều quá sớm; không cổ vũ học chữ - học số - ngoại ngữ sớm để "không thua bạn bè"; luôn nhấn mạnh mỗi năm một nhịp phát triển riêng, không so sánh giữa các con; kết mỗi phần bằng liên kết rõ ràng sang đúng chuyên mục con hoặc nhóm tuổi liền kề.',

            'style_sample' => <<<'TEXT'
                Sáng đầu tiên của năm học mới, bạn dắt con vào lớp mẫu giáo lớn — cũng ngôi trường quen thuộc, cũng cô giáo cũ, nhưng sao lòng bạn lại rộn lên một câu hỏi mới: "chỉ còn một năm nữa thôi là con vào lớp 1 rồi". Cảm giác đó rất thật, và bạn không phải người duy nhất vừa nhận ra 3 năm mầm non trôi nhanh đến vậy. Nếu 2-3 năm trước, mọi thứ xoay quanh việc giúp con qua cơn ăn vạ giữa siêu thị hay tập nói câu đầu tiên, thì bước vào tuổi mầm non, câu hỏi của bạn đã khác hẳn: con có đang phát triển đúng nhịp không, ăn ở trường có đủ chất không, đi học đông bạn có hay ốm vặt không, và quan trọng nhất — mình cần chuẩn bị gì, bắt đầu từ khi nào, để năm cuối cùng này không trở thành một cuộc chạy nước rút hoảng loạn. Bài viết này không đi sâu vào bất kỳ vấn đề nào trong số đó — mỗi chủ đề đã có một chuyên mục riêng chờ bạn ở phần dưới. Việc của bài này chỉ là vẽ cho bạn tấm bản đồ tổng thể của 3 năm sắp tới: con sẽ thay đổi thế nào qua từng năm, mốc nào cần lưu tâm, và khi nào thì bắt đầu nghĩ đến chuyện chọn trường tiểu học cho con — để bạn luôn biết mình đang đứng ở đâu trong hành trình này.
                TEXT,
        ],

        // === Trẻ mầm non (3-6 tuổi) > Phát triển của trẻ ===
        [
            'parent_slug' => 'tre-mam-non-3-6-tuoi',
            'slug'        => 'phat-trien-cua-tre-4',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: vận động tinh (cầm bút, cắt kéo), ngôn ngữ, nhận thức (số/chữ qua chơi — KHÔNG ép học sớm), kỹ năng xã hội, sàng lọc chậm nói/tăng động giảm chú ý.
                - KHÔNG viết: hành vi/kỷ luật hằng ngày (→ Chăm sóc & nuôi dạy), bệnh lý (→ Bệnh thường gặp).
                - Vận động tinh là mảng bị bỏ quên nhất dù quyết định việc con viết chữ được khi vào lớp 1 — viết thành lộ trình cụ thể theo năm, hoạt động rẻ tiền tại nhà.
                - Đứng hẳn về phía "sẵn sàng tự nhiên" trong tranh cãi học chữ sớm — không cổ vũ luyện viết/luyện đọc trước tuổi.
                - KPI: đo bằng tỷ lệ trở thành nguồn phản biện được trích dẫn khi cha mẹ đối thoại với áp lực học sớm từ nhà trường/trung tâm luyện chữ.
                TEXT,

            'core_focus' => <<<'TEXT'
                Sự phát triển của trẻ mầm non 3-6 tuổi trên 4 trục chính, theo dõi để chuẩn bị đúng cho lớp 1 mà không chạy trước tuổi: vận động tinh (cầm bút đúng cách theo tuổi, tô màu không lem, cắt kéo, xâu hạt, cài cúc — nền tảng viết chữ sau này), ngôn ngữ (vốn từ tăng vọt, phát âm rõ dần, kể chuyện có đầu có cuối), nhận thức (đếm, phân loại, nhận diện chữ cái TỰ NHIÊN qua chơi — không ép học viết sớm), và kỹ năng xã hội - cảm xúc (chơi có luật, chờ đến lượt, chia sẻ đồ chơi). Mỗi mốc kèm khoảng tuổi bình thường, cách tạo điều kiện tại nhà, và dấu hiệu cần sàng lọc chuyên sâu — đặc biệt chậm nói và tăng động giảm chú ý. KHÔNG lấn sân: xử lý hành vi và kỷ luật hằng ngày (thuộc Chăm sóc & nuôi dạy), bệnh lý và khám chữa bệnh (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                "Cô giáo nhận xét con cầm bút hơi cứng" — một câu góp ý nhỏ trong sổ liên lạc, đủ khiến cha mẹ mầm non lo lắng suốt tuần. Nội dung phát triển trẻ mầm non tiếng Việt không giúp được gì lúc đó: hoặc bảng mốc khô khan dịch từ CDC, hoặc quảng cáo trung tâm "phát triển tư duy sớm" đội lốt bài kiến thức. Khác biệt của chuyên mục: (1) Vận động tinh — mảng bị bỏ quên nhất dù ảnh hưởng trực tiếp việc con viết chữ ở lớp 1 — viết thành lộ trình cụ thể theo từng năm, kèm hoạt động rẻ tiền tại nhà (xé giấy, vo đất nặn, cài cúc) thay vì bán đồ chơi giáo dục đắt tiền; (2) Đứng hẳn về phía "sẵn sàng tự nhiên" trong tranh cãi nhận biết chữ - số đang gay gắt ở Việt Nam: mốc nhận biết qua chơi là bình thường và đủ, không cổ vũ ép học viết - học đọc trước lớp 1; (3) Tuyến bài nghiêm túc, không hù dọa, về sàng lọc chậm nói và tăng động giảm chú ý — thường phát hiện lần đầu qua nhận xét của giáo viên chứ không phải cha mẹ.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn lo âu so sánh rất lớn ở tuổi mầm non: "trẻ 4 tuổi chưa nói rõ có sao không", "cách cầm bút đúng cho trẻ mầm non", "trẻ mầm non học chữ trước khi vào lớp 1 có nên không", "dấu hiệu tăng động giảm chú ý ở trẻ mầm non", "trẻ mấy tuổi biết cắt kéo". (2) Là nguồn tham chiếu trung lập giúp cha mẹ phản biện lại áp lực học chữ sớm từ nhà trường - hội nhóm - trung tâm luyện chữ — đo bằng tỷ lệ trích dẫn/chia sẻ bài khi có tranh luận học sớm, giữ đúng lập trường biên tập "phát triển đúng lứa tuổi" xuyên suốt site. (3) Liên kết chặt với Chăm sóc & nuôi dạy khi mốc phát triển giao thoa với hành vi (con hiếu động vừa là mốc cần theo dõi vừa là vấn đề hành vi cần xử lý hằng ngày), và nối mạch với chuyên mục Phát triển của trẻ ở cụm Trẻ tập đi (1-3 tuổi) làm cầu nối liên tục theo tuổi con từ giai đoạn trước. (4) Dẫn độc giả sang Phát triển của trẻ ở cụm Trẻ tiểu học (6-12 tuổi) và sang Trường mầm non & tiểu học ở năm cuối mầm non, đúng lúc câu hỏi "con đã sẵn sàng vào lớp 1 chưa" trở thành mối quan tâm chính.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con 4 tuổi nói chưa rõ, nhiều âm còn ngọng, cô giáo nhận xét bạn cùng lớp đã nói sõi hơn hẳn — có cần cho đi khám ngôn ngữ trị liệu không hay đợi thêm?"; "Con cầm bút kiểu nắm cả bàn tay, tô màu lem hết ra ngoài — sắp vào lớp 1 rồi mà chưa sửa được, dạy sao cho đúng?"; "Cả lớp mẫu giáo lớn của con đã đọc được truyện tranh, con mình mới nhận được nửa bảng chữ cái — có phải con học kém, có nên cho đi học thêm không?"; "Con hiếu động không ngồi yên nổi 5 phút, giờ học nào cô cũng nhắc riêng — là tính bé năng động hay dấu hiệu tăng động giảm chú ý cần khám?"; "Con chơi một mình là chính, không thích chơi chung, giành đồ chơi không chịu nhường bạn — ở tuổi 4-5 như vậy có bình thường không?"; "Cô giáo góp ý con phản xạ chậm hơn bạn khi cô hỏi, nói câu cụt lủn không đủ ý — nên lo đến mức nào?"; "Trường yêu cầu trước khi vào lớp 1 con phải biết đếm đến 20, viết được tên mình — con nhà mình chưa làm được hết, có đáng lo không hay trường đòi hỏi quá sớm?"; "Con dùng kéo cắt giấy toàn đứt tay, không theo được đường vẽ — mấy tuổi mới nên cho cầm kéo?". Nền chung: cha mẹ mầm non sống giữa hai nỗi lo song song — sợ ép con học quá sớm làm mất tuổi thơ, nhưng cũng sợ bỏ lỡ dấu hiệu cần can thiệp sớm nếu chờ quá lâu — và cần một ranh giới rõ ràng để phân biệt hai nỗi lo đó.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Flashcard, phương pháp dạy đọc sớm cho trẻ 3-4 tuổi" — phản khoa học ở tuổi này, cổ vũ ép học chữ trước khi sẵn sàng tự nhiên, đi ngược lập trường biên tập của site; chỉ viết bài phân tích trung lập nếu cần phản biện trào lưu. "Luyện chữ đẹp, luyện viết cho trẻ mầm non" — cơ tay trẻ 3-6 tuổi chưa đủ để viết chữ nhỏ chuẩn, luyện sớm gây cầm bút sai tư thế lâu dài; thay bằng bài vận động tinh chuẩn bị nền cho viết chữ, không luyện viết trực tiếp. "So sánh IQ, trắc nghiệm thông minh sớm cho trẻ mầm non" — không có giá trị dự đoán ở tuổi này, chỉ nuôi văn hóa so sánh; không viết. "Học tiếng Anh, học 2-3 ngoại ngữ từ 3 tuổi để phát triển não bộ" — vượt phạm vi phát triển ngôn ngữ mẹ đẻ mà chuyên mục ưu tiên, dễ thành bài PR trung tâm ngoại ngữ; chỉ đề cập ở mức "nếu gia đình có nhu cầu thì lưu ý gì", không cổ vũ. "Tự chẩn đoán tự kỷ/tăng động tại nhà qua bài test online" — nguy hiểm, dễ chẩn đoán sai gây hoang mang hoặc chủ quan; chuyên mục chỉ nêu dấu hiệu cần sàng lọc chuyên môn và khuyến khích khám đúng nơi, không đưa công cụ tự chẩn đoán.
                TEXT,

            'audience' => 'Cha mẹ Việt 28-40 tuổi có con 3-6 tuổi học mầm non, quan tâm sát đến sự phát triển của con nhưng đang phải phân biệt giữa lời khuyên "cho con học sớm để không thua bạn" từ nhà trường/hội nhóm và mong muốn để con phát triển tự nhiên đúng tuổi; thường nhận thông tin qua nhận xét của giáo viên trong sổ liên lạc hoặc buổi họp phụ huynh nhiều hơn là tự quan sát; đọc để đối chiếu con mình với mốc chuẩn và để biết khi nào một nhận xét của cô giáo là bình thường, khi nào cần lưu tâm thật sự.',

            'constraints' => 'Mọi mốc ghi khoảng tuổi rộng, cấm giọng deadline hay bảng đối chiếu kiểu chấm thi; không cổ vũ học chữ - học số - ngoại ngữ sớm dưới bất kỳ hình thức nào; nói về chậm nói/tăng động giảm chú ý phải điềm tĩnh, có bước hành động rõ (khám ở đâu, sàng lọc gì) chứ không hù dọa; không dùng từ "kém", "chậm", "thua bạn" khi mô tả trẻ; hoạt động gợi ý phải rẻ hoặc miễn phí, làm tại nhà; nguồn dẫn theo AAP/CDC/khuyến cáo phát triển trẻ em, không quảng cáo trung tâm hay bộ học liệu.',

            'style_sample' => <<<'TEXT'
                Trong buổi họp phụ huynh cuối học kỳ, cô giáo nhẹ nhàng nhận xét: "Bé nhà mình còn cầm bút hơi cứng, tô màu chưa được gọn như một số bạn". Bạn về nhà, lòng hơi chùng xuống — con sắp lên lớp lớn rồi mà tay còn vụng thế, có phải mình đã bỏ lỡ điều gì? Trước khi lo thêm, hãy hình dung một chút: cây bút chì mà con đang cầm nặng và khó điều khiển hơn con tưởng rất nhiều so với cái xẻng xúc cát ở sân trường mà con vẫn cầm thoăn thoắt — vì viết đòi hỏi các cơ nhỏ ở ngón tay phối hợp tinh vi, trong khi xúc cát chỉ cần cơ lớn ở cả bàn tay và cánh tay. Hành trình từ nắm bút cả nắm tay như nắm quả cầu ở tuổi lên 3, đến cầm bút bằng ba ngón kiểu "chân nhện" ở tuổi lên 5-6, không phải chuyện tự nhiên mà có — nó cần cả một quá trình luyện cơ tay qua những trò chơi hoàn toàn không liên quan đến bút, như vo đất nặn, xé giấy, cài cúc áo, xâu hạt vòng. Vậy nên tin vui là: bạn không cần bắt con ngồi vào bàn luyện chữ ngay tối nay. Trong bài này, mình sẽ chỉ bạn đúng những trò chơi 10 phút mỗi ngày giúp tay con khỏe và khéo dần lên đúng nhịp, cùng cách nhận biết khi nào sự vụng về ấy thực sự cần một cái nhìn chuyên môn hơn.
                TEXT,
        ],

        // === Trẻ mầm non (3-6 tuổi) > Dinh dưỡng cho trẻ ===
        [
            'parent_slug' => 'tre-mam-non-3-6-tuoi',
            'slug'        => 'dinh-duong-cho-tre-2',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: dinh dưỡng khi con ăn bán trú — đọc/đánh giá thực đơn trường, thấp còi VÀ thừa cân, thói quen ăn uống nền tảng, quà vặt cổng trường.
                - KHÔNG viết: hành vi bữa ăn (tự xúc, ngồi vào bàn — → Chăm sóc & nuôi dạy), bệnh lý tiêu hóa (→ Bệnh thường gặp).
                - Khác biệt bắt buộc giữ: dạy cha mẹ ĐỌC tờ thực đơn bán trú dán cửa lớp (đối chiếu khẩu phần chuẩn) — hầu như ai cũng lướt qua mà không hiểu.
                - Xử lý CẢ HAI đầu phổ dinh dưỡng — thấp còi VÀ thừa cân — trong khi nội dung Việt khác chỉ lo "làm sao cho con tăng cân".
                - KPI: đo bằng tỷ lệ lan truyền trong nhóm phụ huynh cùng lớp/trường và tỷ lệ đọc trọn cả 2 chuyên mục (dinh dưỡng + hành vi bữa ăn).
                - Kịch bản trao đổi với giáo viên về thực đơn bán trú nên để ngỏ cho cả BỐ đi họp phụ huynh, không mặc định chỉ mẹ.
                TEXT,

            'core_focus' => <<<'TEXT'
                Dinh dưỡng cho trẻ mầm non 3-6 tuổi trong bối cảnh ăn bán trú — giai đoạn cha mẹ mất quyền kiểm soát trực tiếp phần lớn bữa ăn: đọc hiểu/đánh giá thực đơn bán trú (đối chiếu khẩu phần - 4 nhóm chất, nhận biết thực đơn đơn điệu, cách trao đổi với trường không gây căng thẳng), suy dinh dưỡng thấp còi VÀ thừa cân - béo phì (đọc biểu đồ tăng trưởng, phối hợp bữa trường - bữa nhà), xây thói quen ăn uống NỀN TẢNG lâu dài (đa dạng thực phẩm, giảm đồ chiên rán - nước ngọt, đối phó quà vặt cổng trường), cân đối bữa tối khi con đã ăn 2 bữa ở trường, và trang bị cho con khả năng TỰ CHỌN món lành mạnh khi cha mẹ vắng mặt (tiệc sinh nhật, nhà bạn). KHÔNG lấn sân: hành vi bữa ăn như tự xúc ăn, ngồi vào bàn (thuộc Chăm sóc & nuôi dạy), bệnh lý tiêu hóa (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung dinh dưỡng trẻ mầm non tiếng Việt gần như chỉ nói chuyện "ăn ở nhà", trong khi phần lớn bữa chính của con diễn ra ở trường, ngoài tầm mắt cha mẹ. Khác biệt: (1) Dạy cha mẹ ĐỌC tờ thực đơn bán trú dán cửa lớp — thứ ai cũng lướt qua mà không hiểu — đối chiếu khẩu phần chuẩn, kèm kịch bản trao đổi tế nhị với giáo viên/nhà trường; (2) Xử lý song song cả hai đầu phổ dinh dưỡng mầm non — thấp còi VÀ thừa cân — trong khi nội dung mẹ-bé Việt khác chỉ lo "làm sao cho con tăng cân"; (3) Chuẩn bị cho con năng lực CHỌN MÓN lành mạnh khi cha mẹ vắng mặt — ở lớp, nhà bạn, tiệc sinh nhật — góc nhìn thực dụng khác hẳn các bài chỉ dạy công thức nấu tại nhà.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn đặc trưng của phụ huynh có con bán trú: "thực đơn bán trú trường mầm non có đủ chất không", "con thừa cân ở tuổi mầm non phải làm sao", "trẻ mầm non thấp còi nên bổ sung gì", "hạn chế đồ ngọt nước ngọt cho trẻ mầm non", "quà vặt cổng trường có an toàn không". (2) Là nguồn hướng dẫn hiếm hoi giúp cha mẹ chủ động đối thoại với nhà trường về bữa ăn bán trú một cách xây dựng — đo bằng tỷ lệ lan truyền trong nhóm phụ huynh cùng lớp/cùng trường. (3) Liên kết chặt với Chăm sóc & nuôi dạy (khía cạnh hành vi bữa ăn) để tránh trùng lặp nhưng vẫn dẫn độc giả đọc trọn vẹn cả hai, nối mạch dinh dưỡng liên tục từ Dinh dưỡng cho trẻ ở cụm Trẻ tập đi (1-3 tuổi) sang đến Dinh dưỡng cho trẻ ở cụm Trẻ tiểu học (6-12 tuổi). (4) Cung cấp thông tin nền khi gia đình tìm hiểu chính sách bán trú lúc chọn trường tiểu học ở chuyên mục Trường mầm non & tiểu học, và xây uy tín "đứng về phía cha mẹ và đứa trẻ" khi bàn về bữa ăn ở trường mà không đổ lỗi nhà trường.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Trường dán thực đơn tuần ở cửa lớp mà mình đọc lướt qua, chẳng biết như vậy có đủ chất cho con không"; "Con đi học bán trú cả ngày, tối về hỏi hôm nay ăn gì con chỉ nhớ mỗi món tráng miệng, mình hoàn toàn mù mờ về bữa trưa - bữa xế của con"; "Cô giáo nhắn con ăn ở lớp rất ít, về nhà lại đòi ăn bù gấp đôi bữa tối — có nên chiều theo không hay sẽ hỏng bụng con?"; "Con 4 tuổi nặng 13kg, cô giáo nhắc khéo là hơi thừa cân so với tuổi — mình không biết nên bắt đầu điều chỉnh từ đâu khi bữa trưa con ăn ở trường"; "Họp phụ huynh nghe các mẹ than suất ăn bán trú 25 nghìn một ngày sợ không đủ chất, có nên bồi dưỡng thêm buổi tối và bồi dưỡng thế nào cho cân đối chứ không thừa"; "Tan học là con đòi mua xiên que, nước ngọt màu mè ở cổng trường, cấm thì khóc ầm giữa đường mà bạn nào cũng ăn nên khó cản"; "Sinh nhật bạn tổ chức ngay tại lớp, con ăn bánh kẹo nước ngọt cả buổi chiều, về nhà không ăn nổi miếng cơm nào"; "Ông bà đưa đón cháu tan học rồi tiện thể mua quà vặt cổng trường mỗi ngày, góp ý mãi mà ông bà vẫn giữ thói quen cũ"; "Con bị đánh giá thấp còi hai năm liền theo biểu đồ nhà trường đo, bác sĩ dặn theo dõi thêm mà không nói rõ cụ thể bữa cơm nhà cần bổ sung món gì". Nền chung: từ ngày con đi học bán trú, cha mẹ mất đi khả năng kiểm soát trực tiếp phần lớn lượng thức ăn con nạp vào mỗi ngày, và phải học cách chăm sóc dinh dưỡng cho con một cách GIÁN TIẾP, thông qua hợp tác với nhà trường thay vì tự tay chuẩn bị từng bữa như trước.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Thực đơn tăng cân cấp tốc cho trẻ mầm non" — tư duy nhồi cân theo con số tuyệt đối phản khoa học, đi ngược nguyên tắc đọc biểu đồ tăng trưởng theo khoảng của site; thay bằng bài xây bữa ăn cân đối theo percentile. "So sánh xếp hạng suất ăn bán trú giữa các trường cụ thể" — dễ gây tranh cãi, khiếu kiện nhà trường và mang dáng dấp review thương mại; chuyên mục chỉ đưa khung tiêu chí tự đánh giá thực đơn, không xếp hạng trường nào. "Thực phẩm chức năng tăng cân, tăng chiều cao cho trẻ mầm non" — thị trường quảng cáo khai thác đúng nỗi lo thấp còi của cha mẹ Việt, không tiếp tay; chỉ viết bài bóc tách bằng chứng nếu cần. "Ép con ăn hết suất ở trường bằng phần thưởng, dọa dẫm" — thuộc khía cạnh hành vi đã có ở Chăm sóc & nuôi dạy, và ngược lại nguyên tắc tôn trọng tín hiệu no của con mà site theo đuổi xuyên suốt; không lặp lại ở đây. "Tổng hợp danh sách 50 món ăn vặt cổng trường độc hại" dạng liệt kê hù dọa — gieo sợ hãi mà không đổi được hành vi thực tế của một đứa trẻ đang thèm quà cổng trường; thay bằng bài hướng dẫn thương lượng và gợi ý món thay thế hấp dẫn tương đương.
                TEXT,

            'audience' => 'Cha mẹ Việt 28-40 tuổi có con 3-6 tuổi học mầm non bán trú cả ngày, không còn trực tiếp chuẩn bị hay chứng kiến phần lớn bữa ăn của con như giai đoạn trước; thường chỉ nắm được thông tin bữa ăn ở trường qua thực đơn dán cửa lớp, tin nhắn ngắn của cô giáo, hoặc lời kể ngắt quãng của chính con; lo lắng âm thầm không biết con ăn gì - ăn được bao nhiêu ở lớp, và cảm thấy bất lực khi cân nặng - chiều cao của con lệch khỏi kỳ vọng mà mình không rõ nguyên nhân nằm ở đâu.',

            'constraints' => 'Không hù dọa về thực đơn trường học hay đồ ăn cổng trường; không đổ lỗi nhà trường/giáo viên, chỉ đưa cách trao đổi xây dựng; không body-shaming cân nặng trẻ, đánh giá thừa cân/thấp còi phải theo biểu đồ tăng trưởng theo khoảng, không theo số tuyệt đối; khuyến nghị ăn ở nhà phải khả thi với ngân sách/thời gian cha mẹ đi làm cả ngày; không quảng cáo TPCN, sữa tăng cân/chiều cao; dẫn nguồn Viện Dinh dưỡng quốc gia, Bộ Y tế, WHO.',

            'style_sample' => <<<'TEXT'
                Chiều nay đón con tan học, bạn hỏi như mọi ngày: "Hôm nay con ăn gì ở lớp?" — và như mọi ngày, câu trả lời chỉ vỏn vẹn: "Con ăn cơm ạ", kèm thêm đúng một chi tiết mà con nhớ rõ nhất: "Tráng miệng có thạch dưa hấu, ngon lắm mẹ ơi". Còn bữa cơm ấy có món gì, con ăn được bao nhiêu, rau có ăn hết không — tất cả nằm ngoài tầm biết của bạn. Đây là một thực tế mà ít ai nói thẳng: từ ngày con đi học bán trú, bạn đã "bàn giao" phần lớn quyền kiểm soát dinh dưỡng hằng ngày của con cho nhà trường, dù chẳng ai thông báo với bạn về sự bàn giao ấy cả. Tin tốt là bạn không hoàn toàn mù thông tin — mỗi trường đều có một tờ thực đơn tuần dán ngay cửa lớp mà rất ít phụ huynh dừng lại đọc kỹ quá 10 giây. Tờ giấy ấy thực ra là một bản đồ đầy đủ: nó cho bạn biết trong 5 ngày tới con sẽ ăn bao nhiêu loại rau, có được đổi món đạm hay lặp lại thịt heo cả tuần, và bữa xế thiên về tinh bột hay trái cây. Trong bài này, mình sẽ chỉ bạn cách đọc tờ thực đơn ấy như một chuyên gia dinh dưỡng thực thụ trong 2 phút, để từ ngày mai, câu hỏi đón con tan học của bạn có thể cụ thể hơn một chút: "Hôm nay có món canh cua không con, con có ăn thử không?".
                TEXT,
        ],

        // === Trẻ mầm non (3-6 tuổi) > Bệnh thường gặp ===
        [
            'parent_slug' => 'tre-mam-non-3-6-tuoi',
            'slug'        => 'benh-thuong-gap-4',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: bệnh hô hấp theo mùa ở lớp bán trú đông đúc, tay chân miệng, sâu răng sữa, cận thị học đường khởi phát sớm, dị ứng/hen theo mùa.
                - KHÔNG viết: bệnh đặc trưng nhóm tuổi nhỏ hơn, dinh dưỡng/thực đơn (→ Dinh dưỡng cho trẻ).
                - Trấn an có cơ sở: "con đi học là ốm liên tục" là hệ quả TỰ NHIÊN của tiếp xúc mầm bệnh mới để xây miễn dịch — không phải trường bẩn hay con yếu.
                - Sâu răng sữa và cận thị sớm là 2 chủ đề gần như bị bỏ trống ở lứa tuổi này — nâng thành tuyến bài riêng đúng "cửa sổ vàng" phòng ngừa.
                - Kết luận hành động (theo dõi/khám/nghỉ học) luôn nêu sớm và rõ trong bài.
                TEXT,

            'core_focus' => <<<'TEXT'
                Các vấn đề sức khỏe đặc trưng của trẻ mầm non 3-6 tuổi trong môi trường bán trú đông đúc (30-40 cháu/lớp): bệnh hô hấp theo mùa dễ lây trong tập thể (cúm, viêm họng, viêm phế quản — vì sao tần suất ốm tăng vọt so với ở nhà, đặc biệt học kỳ đầu), tay chân miệng (dịch tễ tuổi mầm non, dấu hiệu trở nặng cần nhập viện, quy định cách ly của trường), sâu răng sữa (do tiếp xúc đồ ngọt nhiều hơn khi đi học, hệ lụy nếu chủ quan nghĩ "răng sữa hỏng cũng thay"), cận thị học đường khởi phát sớm (liên quan thời gian dùng màn hình, khám mắt định kỳ trước lớp 1), dị ứng - hen theo mùa (quản lý khi con ở trường cả ngày không có cha mẹ theo dõi), và bộ tiêu chí quyết định khi nào nên cho con nghỉ học vì bệnh truyền nhiễm. KHÔNG lấn sân: bệnh đặc trưng nhóm tuổi nhỏ hơn, dinh dưỡng - thực đơn (thuộc Dinh dưỡng cho trẻ).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Bệnh trẻ em tuổi mầm non tiếng Việt hoặc viết chung chung không phân biệt độ tuổi, hoặc tập trung bệnh nặng hiếm gặp gây hoang mang không cần thiết. Khác biệt: (1) Trấn an có cơ sở về "con đi học là ốm liên tục" — hệ quả tự nhiên của tiếp xúc mầm bệnh mới để xây miễn dịch, không phải trường mất vệ sinh hay con yếu hơn bạn; (2) Đưa quyết định "có cho con nghỉ học không" về khung thực dụng, đối chiếu tiêu chí y tế với quy định trường và áp lực xin nghỉ làm thật; (3) Nâng sâu răng sữa và cận thị học đường sớm — hai chủ đề bị bỏ trống ở tuổi này — thành tuyến bài riêng đúng "cửa sổ vàng" phòng ngừa.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn rất thực tế của phụ huynh có con mới đi học: "con đi học mầm non hay ốm vặt phải làm sao", "dấu hiệu tay chân miệng ở trẻ mầm non", "sâu răng sữa có cần trám không", "dấu hiệu cận thị ở trẻ mầm non", "khi nào nên cho con nghỉ học vì ốm". (2) Là "bộ lọc bình tĩnh" giúp cha mẹ phân biệt ốm vặt bình thường của môi trường tập thể với dấu hiệu cần khám ngay — đo bằng lượng truy cập trực tiếp lúc con vừa có triệu chứng. (3) Liên kết với Dinh dưỡng cho trẻ khi bàn về sâu răng liên quan đồ ngọt, với Phát triển của trẻ khi bệnh mạn tính ảnh hưởng khả năng tập trung ở lớp, và nối mạch Bệnh thường gặp liên tục từ cụm Trẻ tập đi (1-3 tuổi) sang đến Bệnh thường gặp ở cụm Trẻ tiểu học (6-12 tuổi). (4) Cung cấp thông tin nền để phối hợp với Trường mầm non & tiểu học về chính sách sức khỏe học đường (yêu cầu tiêm chủng, quy định nghỉ cách ly) khi gia đình tìm hiểu trường.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con đi học mầm non mới được hai tháng mà đã ốm liên tục hết cúm lại viêm họng — có phải trường không đảm bảo vệ sinh hay do con yếu hơn các bạn?"; "Lớp có bạn bị tay chân miệng, cô nhắn cả lớp theo dõi tại nhà — dấu hiệu nào ở con mình là bắt buộc phải đưa đi viện ngay, dấu hiệu nào chỉ cần chăm ở nhà?"; "Con kêu đau răng, đưa đi khám phát hiện sâu 4 cái, bác sĩ chỉ định trám hết — răng sữa rồi cũng thay, có thực sự cần trám tốn kém như vậy không?"; "Cô giáo nhắc con hay nheo mắt nhìn lên bảng, ở nhà xem tivi cũng đòi ngồi sát lại gần hơn hẳn trước — mới mầm non đã phải đo mắt, đeo kính rồi sao?"; "Cứ đổi mùa là con hắt hơi sổ mũi kéo dài cả tháng không dứt, không sốt không mệt — là dị ứng thời tiết hay chỉ cảm vặt thông thường?"; "Con sốt nhẹ 37.8 độ, tỉnh táo vẫn chơi bình thường — nên cho đi học hay nghỉ, nghỉ hoài thì công ty nhắc khéo mãi rồi"; "Trường yêu cầu nghỉ đủ số ngày cách ly theo quy định khi con mắc bệnh truyền nhiễm, mà hai vợ chồng đã dùng gần hết phép năm, phải xoay ca nghỉ luân phiên"; "Con hay dụi mắt, mắt đỏ hoe chảy nước mỗi khi trời chuyển mùa hè — dị ứng phấn hoa hay lây viêm kết mạc từ bạn cùng lớp?". Nền chung: con đi học bán trú cả ngày khiến cha mẹ không còn quan sát trực tiếp được diễn biến sức khỏe của con như trước, phải học cách nhận thông tin gián tiếp qua giáo viên và ra quyết định (nghỉ học hay không, đi khám hay theo dõi) trong tình trạng thiếu dữ kiện.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Tổng hợp tất cả các bệnh mùa tựu trường trong một bài" dạng liệt kê hù dọa — gây hoang mang đầu năm học mà không đưa được hướng xử lý cụ thể cho từng bệnh; mỗi bệnh cần một bài riêng có bảng phân tầng hành động. "Mẹo dân gian tự chữa tay chân miệng, sốt phát ban tại nhà không cần đi khám" — nguy hiểm với bệnh có khả năng trở nặng nhanh ở tay chân miệng; chỉ viết dạng cảnh báo và ngưỡng đi khám bắt buộc. "Quảng cáo dịch vụ trám răng thẩm mỹ, nha khoa trẻ em cụ thể" — dễ thành bài PR trá hình; chuyên mục chỉ giải thích khi nào cần trám và vì sao, không giới thiệu cơ sở nha khoa. "Đeo kính sớm sẽ làm mắt yếu đi, phụ thuộc kính suốt đời" — quan niệm sai phổ biến khiến cha mẹ trì hoãn cho con đeo kính cần thiết; viết bài phản biện bằng bằng chứng nhãn khoa thay vì lặp lại quan niệm này. "Cho con uống kháng sinh, tăng đề kháng dự phòng đầu năm học" — không có cơ sở y khoa và có hại nếu lạm dụng kháng sinh; tuyệt đối không viết dạng khuyến nghị, chỉ có thể viết bài giải thích vì sao không nên tự ý làm vậy.
                TEXT,

            'audience' => 'Cha mẹ Việt 28-40 tuổi có con 3-6 tuổi học mầm non bán trú cả ngày, không thể theo dõi sát diễn biến sức khỏe của con trong giờ học như khi con còn ở nhà; nhận thông tin về tình trạng con qua tin nhắn ngắn của giáo viên hoặc lời kể không đầy đủ của con lúc đón về; thường phải cân đối giữa quyết định y tế cho con và áp lực công việc, ngày phép có hạn; đọc bài trong trạng thái vừa lo cho con vừa tính toán thực tế công việc của chính mình.',

            'constraints' => 'Kết luận hành động (theo dõi/khám/nghỉ học) nêu rõ và sớm trong bài; không hù dọa hay liệt kê bệnh hiếm gây hoang mang; không đổ lỗi nhà trường về lây bệnh tập thể, chỉ giải thích cơ chế và cách phối hợp; nhìn nhận thẳng áp lực xin nghỉ làm của cha mẹ, không phán xét; không kê đơn, không khuyến nghị thuốc/kháng sinh cụ thể; dẫn nguồn Bộ Y tế, WHO, Bệnh viện Nhi TW, khuyến cáo nhãn khoa/nha khoa nhi; không quảng cáo phòng khám, TPCN tăng đề kháng.',

            'style_sample' => <<<'TEXT'
                Mới đi học mầm non được sáu tuần mà con bạn đã ốm đến lần thứ ba — lần này là ho sốt, lần trước là đau họng, lần trước nữa là tiêu chảy nhẹ. Bạn bắt đầu tự hỏi, có khi nào lớp học của con thiếu vệ sinh, hay tệ hơn, con mình đề kháng kém hơn những đứa trẻ khác? Hãy để mình trấn an bạn bằng một góc nhìn khác: những tuần đầu đi học tập thể gần như là một "cuộc tổng duyệt" của hệ miễn dịch. Ở nhà, con chỉ tiếp xúc với vài loại vi-rút quen thuộc trong gia đình; bước vào lớp học 30-40 bạn, con đột ngột gặp hàng chục chủng vi-rút mới mà cơ thể chưa từng biết mặt — và mỗi lần "gặp mặt" như vậy thường đi kèm một đợt ốm nhẹ trong lúc hệ miễn dịch học cách nhận diện và ghi nhớ kẻ địch mới. Hầu hết trẻ mầm non trải qua 6 đến 10 đợt ốm hô hấp nhẹ trong năm học đầu tiên — cao hơn hẳn so với lúc ở nhà, và đó không phải vì trường bẩn hay con yếu, mà vì đó chính là cách hệ miễn dịch của con đang được xây dựng vững chắc hơn cho những năm sau. Điều thật sự cần bạn phân biệt không phải "ốm nhiều hay ít", mà là ốm THẾ NÀO thì bình thường và ốm thế nào thì cần khám ngay — đó chính xác là điều mình sẽ giúp bạn nhận biết rõ ràng trong bài này, cho từng loại bệnh phổ biến nhất của tuổi mầm non.
                TEXT,
        ],

        // === Trẻ tiểu học (6-12 tuổi) ===
        [
            'parent_slug' => null,
            'slug'        => 'tre-tieu-hoc-6-12-tuoi',

            'writer_insights' => <<<'TEXT'
                - Đây là danh mục CHA — chỉ bài TỔNG QUAN 5 năm tiểu học, dẫn vào 4 chuyên mục con. KHÔNG viết bài tập cụ thể, mốc chi tiết, thực đơn, bệnh cụ thể ở đây.
                - Bước ngoặt lớn nhất: cha mẹ chuyển từ vai trò CHĂM SÓC sang ĐỒNG HÀNH HỌC TẬP — không trường lớp nào dạy cách làm việc này.
                - Lấp khoảng trống 5 năm dài nhất bị bỏ ngỏ: nội dung Việt tập trung 0-6 tuổi rồi nhảy thẳng sang tuổi dậy thì 13+.
                - Đặt tên rõ: con hình thành cái tôi riêng, có bạn bè quan trọng hơn cha mẹ trong một số việc — đây là BÌNH THƯỜNG, không phải dấu hiệu xa cách.
                TEXT,

            'core_focus' => <<<'TEXT'
                Danh mục CHA của cụm 6-12 tuổi — chỉ chứa các bài TỔNG QUAN xuyên suốt 5 năm tiểu học, không đi sâu vào mảng đã có chuyên mục con: bước ngoặt lớn nhất kể từ khi sinh con — từ vai trò "chăm sóc" (cho ăn, dỗ ngủ, tắm rửa) sang vai trò "đồng hành học tập" (kèm bài tập, họp phụ huynh, quản lý thời gian biểu); đặc điểm tâm lý - nhận thức bao trùm cả giai đoạn (tư duy logic cụ thể phát triển mạnh, con bắt đầu có "nhóm bạn thân" và chính kiến riêng, ít phụ thuộc cha mẹ hơn hẳn mầm non, và cuối giai đoạn — khoảng lớp 4-5 — những dấu hiệu dậy thì sớm đầu tiên xuất hiện ở một bộ phận trẻ, đặc biệt bé gái); hành trình 5 năm tiểu học nhìn tổng thể (lớp 1: làm quen nề nếp, học chữ - học số; lớp 2-3: tăng khối lượng bài, bắt đầu có bạn thân - có mâu thuẫn bạn bè; lớp 4-5: áp lực chuyển cấp, tự lập hơn, cơ thể bắt đầu thay đổi); và bài định hướng "con vào lớp 1: cần chuẩn bị gì, đọc gì" dẫn vào 4 chuyên mục con (Chăm sóc & nuôi dạy, Phát triển của trẻ, Dinh dưỡng cho trẻ, Bệnh thường gặp). Chi tiết bài tập về nhà, quản lý màn hình, dấu hiệu dậy thì sớm, cận thị học đường... KHÔNG viết ở đây — đẩy xuống đúng chuyên mục con; chọn trường tiểu học thuộc danh mục Trường mầm non & tiểu học riêng biệt.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Giai đoạn 6-12 tuổi bị hầu hết nội dung mẹ-bé Việt bỏ ngỏ — phần lớn site tập trung vào 0-6 tuổi hoặc nhảy thẳng sang tuổi dậy thì 13+; khoảng trống ở giữa trong khi đây là NĂM NĂM DÀI NHẤT một đứa trẻ ở cùng nhà. Vai trò riêng của danh mục cha: (1) Là "bản đồ chuyển vai" cho cha mẹ vừa quen tay chăm con nhỏ giờ phải học một nghề mới — đồng hành học tập — mà không trường lớp nào dạy cách làm; (2) Đặt tên rõ ràng cho sự thay đổi lớn nhất: con không còn là "em bé của mẹ" mà đang hình thành cái tôi riêng, có bạn bè quan trọng hơn cha mẹ trong một số việc, và điều đó là BÌNH THƯỜNG chứ không phải dấu hiệu con xa cách; (3) Nhìn 5 năm tiểu học như một hành trình có nhịp, giúp cha mẹ đi trước một bước thay vì luống cuống ở từng lớp — đặc biệt bước ngoặt lớp 1 (nề nếp) và lớp 4-5 (tiền dậy thì, chuyển cấp).
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Bài pillar "Con vào lớp 1: cha mẹ cần chuẩn bị gì" và "Hành trình 5 năm tiểu học" là điểm đổ về của mọi liên kết nội bộ trong cụm, đón độc giả chuyển tiếp từ cụm Trẻ mầm non (3-6 tuổi) đúng thời điểm con chuyển cấp. (2) SEO truy vấn tổng quan giai đoạn: "con vào lớp 1 cần chuẩn bị gì", "trẻ tiểu học phát triển tâm lý như thế nào", "cách đồng hành cùng con học tiểu học", "trẻ 6-12 tuổi". (3) Điều phối luồng đọc xuống 4 chuyên mục con đúng nhu cầu — đo bằng CTR từ bài pillar sang bài con. (4) Bàn giao độc giả sang danh mục Trường mầm non & tiểu học ở khía cạnh chọn trường, và giữ chân độc giả đã theo site từ sơ sinh tiếp tục sang giai đoạn dậy thì/tuổi teen — mắt xích dài nhất trong chuỗi hành trình cha mẹ mà site theo đuổi.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con chuẩn bị vào lớp 1, mình lo hơn cả ngày con sinh ra — không biết con có theo kịp không"; "Mẫu giáo cô dỗ dành, giờ vào lớp 1 phải ngồi yên 35 phút, viết chữ, làm bài tập về nhà — con khóc không muốn đi học"; "Con lớp 3 tự nhiên có 'hội bạn thân', về nhà không kể chuyện với mẹ nữa như hồi bé — mình hụt hẫng, con đang xa cách hay đó là bình thường?"; "Con học hết lớp 1 mà đọc còn đánh vần, viết còn ngược chữ — có phải con chậm, có nên cho học thêm không?"; "Con lớp 5 tự nhiên cao vọt, ngực hơi nhú — mới lớp 5 mà đã dậy thì sớm thế sao, phải làm gì?"; "Mỗi tối kèm con học bài là một trận cãi vã, con ngồi vào bàn là than mệt, đẩy - lùi mãi không xong"; "Con phàn nàn bị bạn trong lớp trêu, tẩy chay — mình có nên can thiệp hay để con tự xử lý?"; "Họp phụ huynh nghe cô nói điểm con thấp hơn mặt bằng chung, về nhà không biết nói với con thế nào cho đúng"; "Sắp hết tiểu học, chuẩn bị thi vào lớp 6 trường điểm — bạn bè đã cho con học thêm 3-4 ca một tuần, mình có nên chạy theo không". Nền chung: đây là lần đầu tiên cha mẹ phải buông bớt vai trò chăm sóc trực tiếp để chuyển sang vai trò đồng hành gián tiếp — và không ai chuẩn bị tâm thế cho sự chuyển vai đó.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Bài tập về nhà mẫu, đề cương ôn thi từng môn từng lớp" — thuộc phạm vi giáo dục học thuật chuyên biệt, không phải nội dung nuôi dạy con; nếu viết chỉ ở góc độ PHƯƠNG PHÁP đồng hành (thuộc Chăm sóc & nuôi dạy), không viết đề thi. "Xếp hạng trường tiểu học/trường điểm tốt nhất" — thuộc danh mục Trường mầm non & tiểu học, viết ở đây gây chồng lấn nội bộ. "Cẩm nang thi chuyển cấp vào lớp 6 chuyên/chọn" chi tiết luyện thi — cổ vũ áp lực thành tích từ quá sớm, đi ngược quan điểm biên tập của site; chỉ dừng ở mức nhìn nhận tâm lý con trong giai đoạn chuyển cấp. "Dấu hiệu trẻ thiên tài/IQ cao ở tiểu học" — nuôi văn hóa so sánh con nhà người ta, không phải giá trị cốt lõi của site. "Cách dạy con giỏi vượt trội, đứng đầu lớp" — cổ vũ áp lực điểm số ngay từ bài tổng quan, mâu thuẫn với tinh thần đồng hành không phán xét mà toàn bộ cụm 6-12 tuổi theo đuổi.
                TEXT,

            'audience' => 'Cha mẹ Việt 30-42 tuổi có con 6-12 tuổi (đọc nhiều nhất ở mốc con chuẩn bị vào lớp 1 và mốc con chuẩn bị chuyển cấp lớp 6), sống thành thị/ven đô, lần đầu trải qua vai trò đồng hành học tập với con đầu lòng hoặc đã quen với con lớn nhưng vẫn bỡ ngỡ với những thay đổi tâm lý mới; đọc buổi tối sau khi kèm con học hoặc họp phụ huynh xong, mang tâm trạng vừa tự hào con lớn vừa hoang mang vì con không còn là "em bé" như trước.',

            'constraints' => 'Không giọng thành tích hay so sánh điểm số, thứ hạng; không phán xét cách dạy con của cha mẹ khác; không hù dọa về dậy thì sớm hay tụt hậu học tập; tôn trọng việc con đang hình thành cái tôi riêng, không quy về "con hư" hay "con xa cách"; lời khuyên phải khả thi với cha mẹ đi làm cả ngày chỉ có buổi tối bên con; luôn có bước làm được ngay; giọng đồng hành, không giọng chuyên gia bề trên.',

            'style_sample' => <<<'TEXT'
                Ngày khai giảng lớp 1, bạn đứng ở cổng trường nhìn con díu chân theo hàng vào lớp, và một cảm giác kỳ lạ ập đến: cách đây vài tháng con còn là đứa trẻ mẫu giáo được cô bế lên mỗi khi khóc nhè, giờ đã phải tự ngồi vào bàn, tự cầm bút, tự chịu trách nhiệm với 35 phút một tiết học. Nếu bạn đang thấy hoang mang không kém gì ngày đưa con về nhà từ viện phụ sản — thì đúng là bạn đang đứng trước một bước ngoặt lớn tương đương thật. Sáu năm qua, việc của bạn chủ yếu là CHĂM: cho ăn, dỗ ngủ, dạy nói, dạy đi vệ sinh. Từ hôm nay, một phần việc mới bắt đầu chen vào: ĐỒNG HÀNH — nghĩa là ngồi cạnh con làm bài tập mà không làm hộ, biết khi nào nên can thiệp và khi nào nên lùi lại để con tự học cách vấp ngã, và dần dần chấp nhận rằng con sẽ có những người bạn, những bí mật nhỏ, những ý kiến riêng mà không còn kể hết cho mẹ nghe nữa. Năm năm tiểu học phía trước sẽ có nhịp điệu riêng của nó — lớp 1-2 là xây nề nếp, lớp 3-4 là mở rộng thế giới bạn bè, lớp 5 là những dấu hiệu đầu tiên của một cơ thể sắp lớn. Trong bài này, mình sẽ dẫn bạn đi qua toàn cảnh hành trình đó, và chỉ đúng chỗ để đọc sâu hơn về từng mảng khi con bạn chạm tới.
                TEXT,
        ],

        // === Trẻ tiểu học (6-12 tuổi) > Chăm sóc & nuôi dạy ===
        [
            'parent_slug' => 'tre-tieu-hoc-6-12-tuoi',
            'slug'        => 'cham-soc-nuoi-day-3',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: HÀNH VI/ĐỒNG HÀNH tiểu học — tự học, màn hình/mạng xã hội, tiền tiêu vặt, bắt nạt học đường (cả 2 chiều), thi cử, giới tính/dậy thì.
                - Ranh giới với "Chăm sóc & nuôi dạy" ở tuổi khác: đây là tuổi con đã hiểu HẬU QUẢ hành động và bắt đầu có đời sống xã hội riêng (bạn bè, mạng xã hội) — khác 1-3 tuổi (phản xạ cảm xúc thuần túy) và 3-6 tuổi (chưa có nhóm bạn cố định/thiết bị riêng).
                - KHÔNG viết: hành vi tuổi nhỏ hơn (ăn vạ, cai bỉm — thuộc nhóm tuổi trước), chọn trường (→ Trường mầm non & tiểu học).
                - Dám viết bắt nạt học đường ở CẢ HAI CHIỀU (nạn nhân VÀ người bắt nạt) — điều gần như không nguồn tiếng Việt nào dám viết về chiều thứ hai.
                - Nghịch lý cốt lõi: con cần cha mẹ RÚT LUI dần khỏi việc học trong khi áp lực xã hội kéo cha mẹ CAN THIỆP sâu hơn — dạy cách buông đúng chỗ.
                - Kịch bản đồng hành học tập/nói chuyện dậy thì nên có cả góc BỐ — đặc biệt với con trai, nơi bố thường phù hợp hơn để mở đầu chuyện dậy thì, không mặc định đây là việc của mẹ.
                TEXT,

            'core_focus' => <<<'TEXT'
                Cẩm nang thực hành hằng ngày về đồng hành cùng con 6-12 tuổi cho cha mẹ Việt đi làm: rèn tự học - tự giác làm bài (chuyển dần trách nhiệm về phía con), quản lý màn hình - mạng xã hội - game khi con xin điện thoại riêng (giới hạn giờ chơi, thời điểm dùng mạng xã hội đầu tiên, giám sát không xâm phạm), dạy quản lý tiền tiêu vặt, xử lý bắt nạt học đường ở CẢ HAI CHIỀU — khi con LÀ NẠN NHÂN (nhận biết dấu hiệu con giấu cha mẹ, cách can thiệp với nhà trường) và khi con LÀ NGƯỜI BẮT NẠT (nhận trách nhiệm mà không hạ nhục con), đồng hành thi cử mà không biến điểm số thành thước đo giá trị con, và nói chuyện giới tính - dậy thì tự nhiên đúng lúc thay vì né tránh. Mỗi bài đi từ MỘT tình huống thật, giải thích ngắn gọn tâm lý lứa tuổi, rồi đưa bước xử lý làm được ngay và cách xây thói quen bền trong vài tuần. KHÔNG lấn sân: hành vi tuổi nhỏ hơn như ăn vạ, cai bỉm (ranh giới: 6-12 tuổi là hành vi con đã HIỂU HẬU QUẢ và có đời sống xã hội/mạng xã hội riêng, khác hẳn phản xạ cảm xúc thuần túy của 1-3 tuổi), chọn trường (thuộc Trường mầm non & tiểu học) — chuyên mục này chỉ nói đồng hành học tập TẠI NHÀ.
                TEXT,

            'unique_angle' => <<<'TEXT'
                "Con lớp 3 tự nhiên có 'hội bạn thân', về nhà không kể chuyện với mẹ như hồi bé nữa" — nghe như dấu hiệu xa cách, nhưng đây chính xác là điều NÊN xảy ra ở tuổi này, chỉ là không ai nói trước với cha mẹ. Nội dung nuôi dạy con tiểu học tiếng Việt hoặc là giáo dục học thuật khô khan (mẹo học giỏi, phương pháp Phần Lan), hoặc là cảnh báo mạng xã hội dịch từ Tây không khớp thực tế lớp học 40-50 học sinh Việt Nam. Ba điểm khác biệt: (1) Xử lý đúng NGHỊCH LÝ của giai đoạn này: con cần cha mẹ RÚT LUI dần khỏi việc học trong khi áp lực xã hội (nhóm lớp, so sánh điểm số, học thêm) lại kéo cha mẹ CAN THIỆP SÂU hơn — dạy cách buông đúng chỗ, giữ đúng chỗ; (2) Dám viết sâu về bắt nạt học đường ở CẢ HAI CHIỀU (bị bắt nạt và là người bắt nạt) — điều gần như không nguồn tiếng Việt nào dám viết về chiều thứ hai vì sợ chạm tự ái phụ huynh; (3) Tuổi xin điện thoại riêng và mạng xã hội đầu tiên viết như một cột mốc CÓ LỘ TRÌNH (từng bước cấp quyền kèm giám sát) thay vì chỉ có hai thái cực "cấm tiệt" hoặc "thả tự do", phù hợp thực tế lớp 4-5 đã có nhóm chat riêng trên điện thoại của bạn bè.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Trở thành nơi cha mẹ QUAY LẠI mỗi khi con bước sang tình huống mới trong 5 năm tiểu học — đo bằng tỷ lệ đọc từ bài này sang bài khác trong chuỗi và lượng người đọc quay lại theo từng lớp. (2) Chiếm traffic tìm kiếm dài hạn cho các truy vấn tình huống thật ít cạnh tranh: "con lớp 3 không tự giác học bài phải làm sao", "nên cho con dùng điện thoại riêng năm lớp mấy", "con bị bạn bắt nạt ở trường phải xử lý thế nào", "dạy con quản lý tiền tiêu vặt". (3) Xây uy tín biên tập để dẫn người đọc sang các danh mục liền kề: Phát triển của trẻ, Dinh dưỡng cho trẻ, Bệnh thường gặp trong cùng cụm 6-12 tuổi, lùi về Trẻ mầm non (3-6 tuổi) cho cha mẹ có con nhỏ hơn, và sang danh mục Trường mầm non & tiểu học khi câu hỏi chạm đến chọn trường - chuyển cấp. (4) Tạo nền tảng nội dung trụ cột: mỗi cụm đề tài (tự học, màn hình, bắt nạt, tiền tiêu vặt, thi cử, giới tính) có 1 bài tổng quan + các bài tình huống vệ tinh.
                TEXT,

            'pain_points' => <<<'TEXT'
                Tự học: "Con lớp 2 không nhắc là không mở sách, nhắc thì cãi 'con biết rồi' — làm sao để con tự giác mà không phải kèm suốt đời?"; "Mình ngồi kèm con học mà cả hai đều cáu, có hôm con khóc mình cũng muốn khóc theo". Màn hình: "Con xin điện thoại riêng vì 'cả lớp đứa nào cũng có', mình chần chừ mãi không biết cho ở tuổi nào là hợp lý"; "Cho con chơi game 30 phút mà hết giờ vẫn nài nỉ thêm, cãi nhau mỗi ngày vì cái điện thoại". Tiền: "Cho con tiền tiêu vặt mà con tiêu hết trong ngày mua đồ ăn vặt, không biết dạy tiết kiệm từ đâu"; "Phát hiện con giấu tiền, nói dối về khoản chi — có nên phạt nặng không?". Bắt nạt: "Con về nhà buồn thiu mấy hôm liền, hỏi mãi mới biết bị nhóm bạn trong lớp cô lập — mình nên gặp cô giáo hay để con tự giải quyết?"; "Cô giáo gọi điện báo con đánh bạn trong lớp — mình sốc, không tin con mình lại làm vậy, phải nói chuyện với con thế nào?". Thi cử: "Con thi giữa kỳ điểm thấp hơn mọi khi, về nhà không dám đưa bài kiểm tra cho mẹ xem — mình có đang tạo áp lực quá không mà không nhận ra?". Giới tính: "Con gái lớp 4 hỏi vì sao ngực hơi đau, mình lúng túng không biết bắt đầu nói chuyện dậy thì từ đâu cho đúng lúc". Nền chung: cha mẹ phải học một kỹ năng hoàn toàn mới — buông tay đúng lúc mà vẫn ở bên đúng lúc — trong khi guồng học tập và mạng xã hội ngoài kia đang thúc ép con lớn nhanh hơn cha mẹ kịp chuẩn bị.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Phương pháp học giỏi đứng đầu lớp/mẹo đạt điểm 10 mọi môn" — cổ vũ áp lực thành tích, đi ngược tinh thần đồng hành không phán xét của site; thay bằng bài về xây thói quen tự học bền vững, không gắn với điểm số. "Cấm tuyệt đối điện thoại/mạng xã hội đến 18 tuổi" dạng cực đoan — không thực tế khi bạn bè con đã dùng phổ biến từ lớp 4-5, dễ đẩy con sang dùng lén không giám sát được; viết dạng lộ trình cấp quyền có kiểm soát thay vì cấm cứng. "Camera giám sát điện thoại con 24/7" — vi phạm ranh giới riêng tư gây mất niềm tin ở tuổi con đang hình thành cái tôi, phản tác dụng về lâu dài; chỉ viết dạng thỏa thuận minh bạch hai chiều. "Xử lý bắt nạt bằng cách dạy con đánh trả" — nguy hiểm và có thể vi phạm nội quy trường, thay bằng kỹ năng ứng phó an toàn và quy trình phối hợp với nhà trường. "Thưởng tiền cho điểm số cao" — biến việc học thành giao dịch, làm hỏng động lực học nội tại của con về lâu dài; chỉ viết về cách khen ngợi đúng cách tách rời khỏi tiền bạc.
                TEXT,

            'audience' => 'Cha mẹ Việt 30-42 tuổi có con 6-12 tuổi học tiểu học, đi làm toàn thời gian, chỉ thực sự đồng hành việc học được vào buổi tối 2-3 tiếng và cuối tuần; con đang chuyển từ phụ thuộc hoàn toàn sang có ý kiến, có bạn bè, có bí mật riêng khiến cha mẹ vừa mừng vừa hụt hẫng; đọc trên điện thoại sau giờ kèm con học hoặc ngay sau khi nhận điện thoại/tin nhắn từ giáo viên, đang tìm cách xử lý một tình huống cụ thể vừa xảy ra.',

            'constraints' => 'Không giọng thành tích hay tạo thêm áp lực điểm số, thứ hạng; không phán xét cha mẹ hay đổ lỗi hoàn toàn cho con trong các tình huống bắt nạt; không cổ vũ kiểm soát cực đoan (cấm tuyệt đối, giám sát 24/7) hay buông lỏng hoàn toàn; lời khuyên phải khả thi với cha mẹ chỉ có buổi tối bên con; luôn có kịch bản thoại mẫu tiếng Việt tự nhiên; chủ đề giới tính - dậy thì viết tự nhiên, không né tránh nhưng cũng không quá tải thông tin y khoa (phần y khoa dẫn sang Phát triển của trẻ).',

            'style_sample' => <<<'TEXT'
                Bạn phát hiện ra khi dọn cặp sách: bài kiểm tra Toán giữa kỳ của con bị vo tròn nhét dưới đáy ngăn, điểm 5. Phản xạ đầu tiên là hỏi ngay "sao điểm thấp thế, sao không đưa mẹ xem" — nhưng khoan đã, hãy để ý con đã giấu bài suốt ba ngày, nghĩa là con đã tự dằn vặt một mình suốt ba ngày rồi. Ở tuổi này, con bắt đầu hiểu điểm số là một thứ được nhìn nhận, so sánh, đánh giá — và nỗi sợ làm cha mẹ thất vọng có khi còn nặng hơn cả nỗi sợ điểm thấp. Nếu câu đầu tiên bạn nói là về con số, con sẽ học được rằng: giấu diếm an toàn hơn là thành thật. Thay vào đó, thử bắt đầu bằng: "Mẹ thấy bài kiểm tra này rồi, chắc mấy hôm nay con buồn lắm nhỉ" — chỉ một câu đó thôi, bạn đã chuyển từ vị trí giám khảo sang vị trí đồng minh. Rồi mới cùng con xem lại bài sai ở đâu, cách nào lần sau làm tốt hơn. Trong bài này, mình sẽ đi qua cách phản hồi kết quả học tập của con mà không biến điểm số thành thước đo giá trị, kèm vài kịch bản nói chuyện cụ thể cho các tình huống điểm thấp, con giấu bài, hay con so sánh mình với bạn — vì đây là kỹ năng bạn sẽ cần dùng đi dùng lại suốt cả 5 năm tiểu học.
                TEXT,
        ],

        // === Trẻ tiểu học (6-12 tuổi) > Phát triển của trẻ ===
        [
            'parent_slug' => 'tre-tieu-hoc-6-12-tuoi',
            'slug'        => 'phat-trien-cua-tre-5',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: tư duy logic theo khối lớp, phát triển cảm xúc-xã hội, dấu hiệu dậy thì sớm (bé gái từ 8-9 tuổi), khó khăn học tập bị hiểu lầm là "lười" (ADHD, dyslexia, dysgraphia).
                - KHÔNG viết: hành vi/kỷ luật/nề nếp (→ Chăm sóc & nuôi dạy), bệnh lý thể chất như cận thị/béo phì (→ Bệnh thường gặp).
                - Tách bạch then chốt: TỐC ĐỘ HỌC chậm (đọc chậm 1 giai đoạn) vs KHÓ KHĂN HỌC TẬP cần can thiệp (dyslexia, ADHD) — cha mẹ Việt hay gộp chung thành "con lười/dốt".
                - Dậy thì sớm viết CẨN TRỌNG có kiểm soát — không hù dọa bằng ca cực đoan, luôn kèm ngưỡng bình thường.
                - KPI: đo bằng tỷ lệ độc giả được hướng đúng tới chuyên gia (bác sĩ nội tiết/tâm lý giáo dục) thay vì tự dán nhãn hoặc bỏ qua.
                TEXT,

            'core_focus' => <<<'TEXT'
                Sự phát triển NHẬN THỨC, CẢM XÚC - XÃ HỘI và dấu hiệu THỂ CHẤT SỚM ở trẻ 6-12 tuổi để cha mẹ theo dõi đúng, không thấp thỏm sai chỗ: tư duy logic theo khối lớp — từ tư duy cụ thể đầu lớp 1 đến suy luận nhiều bước ở lớp 5, kỹ năng đọc - viết - tính toán với tốc độ khác nhau giữa các trẻ khỏe mạnh, phát triển cảm xúc - xã hội (tự tin qua thành công nhỏ, đồng cảm, phục hồi sau thất bại), dấu hiệu dậy thì sớm cần chú ý không hoảng loạn (bé gái có thể từ 8-9 tuổi: ngực phát triển, tăng chiều cao đột ngột — ngưỡng nào bình thường, ngưỡng nào cần khám nội tiết), và khó khăn học tập thường bị hiểu lầm là "lười"/"hư" (ADHD, dyslexia, dysgraphia) cần tầm soát đúng chuyên môn thay vì gắn mác đạo đức. KHÔNG lấn sân: hành vi - kỷ luật - nề nếp học tập (thuộc Chăm sóc & nuôi dạy), bệnh lý thể chất như cận thị - cong vẹo cột sống - béo phì (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung phát triển trẻ tiểu học tiếng Việt gần như chỉ có hai dạng: bảng mốc dịch nguyên từ nguồn Tây không tính bối cảnh lớp học 40-50 học sinh Việt Nam, hoặc bài trung tâm gia sư viết để quảng bá phương pháp riêng. Ba điểm khác biệt: (1) Tách bạch TỐC ĐỘ HỌC (đọc chậm, viết xấu ở một giai đoạn) và KHÓ KHĂN HỌC TẬP CẦN CAN THIỆP (dyslexia, ADHD) — cha mẹ Việt hay gộp chung thành "con lười/dốt", trong khi phân biệt đúng quyết định con được hỗ trợ kịp thời hay chịu nhãn sai nhiều năm; (2) Viết dậy thì sớm ở bé gái CẨN TRỌNG có kiểm soát — không hù dọa bằng ca cực đoan, luôn kèm ngưỡng bình thường; (3) Đặt phát triển cảm xúc - xã hội ngang hàng phát triển học thuật — trong khi nội dung tiếng Việt khác chỉ xoay quanh IQ và thành tích.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho truy vấn theo dõi phát triển đang tăng nhanh nhưng ít nội dung chất lượng: "trẻ 8 tuổi dậy thì sớm có sao không", "con đọc chậm có phải bị dyslexia", "dấu hiệu tăng động giảm chú ý ở trẻ tiểu học", "trẻ lớp 2 chưa đọc thông viết thạo có đáng lo". (2) Là điểm dừng chân đầu tiên khi cha mẹ nghi ngờ có gì đó "khác thường" ở con — đo bằng tỷ lệ độc giả được hướng tới đúng chuyên gia (bác sĩ nội tiết, chuyên gia tâm lý giáo dục) thay vì tự dán nhãn hoặc bỏ qua. (3) Liên kết chặt với Chăm sóc & nuôi dạy khi vấn đề là hành vi và với Bệnh thường gặp khi có yếu tố sức khỏe tâm lý (lo âu học tập); lùi về chuyên mục Phát triển của trẻ ở Trẻ mầm non (3-6 tuổi) cho các mốc tiền lớp 1. (4) Xây uy tín bằng cách nói thẳng giới hạn của nội dung không phải y tế — luôn hướng dẫn khi nào cần chuyên gia đánh giá chính thức.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con gái mình mới lớp 3, 8 tuổi rưỡi mà ngực đã nhú, mình hoảng quá không biết có phải dậy thì sớm bệnh lý không hay chỉ là hơi sớm bình thường"; "Con học hết lớp 2 mà đọc vẫn đánh vần từng chữ, viết thì lộn ngược b với d — cô giáo bảo con lười tập, mình bắt đầu nghi ngờ có phải con bị khó đọc không"; "Con không ngồi yên nổi 10 phút, luôn tay luôn chân, cô phàn nàn liên tục trong lớp — có phải tăng động không hay chỉ là con hiếu động bình thường?"; "Con thi trượt vào đội tuyển của lớp, buồn cả tuần không chịu nói chuyện với ai — mình nên để con tự vượt qua hay can thiệp giúp con lấy lại tự tin?"; "Con so sánh bản thân với bạn giỏi hơn suốt ngày, tự nhận mình dốt — mình sợ con mất tự tin từ quá sớm"; "Con hay quên bài, mất đồ dùng học tập liên tục, cô nhắc hoài không cải thiện — có phải rối loạn chú ý hay chỉ đãng trí tuổi nhỏ?"; "Nghe nói con gái dậy thì sớm sau này sẽ thấp hơn chuẩn — thật hay chỉ là tin đồn khiến mình lo lắng quá mức?". Nền chung: ranh giới giữa "đặc điểm bình thường của một đứa trẻ" và "dấu hiệu cần can thiệp chuyên môn" rất mong manh ở tuổi này, và cha mẹ cần một nguồn đủ tin cậy để biết khi nào nên yên tâm, khi nào nên đi khám.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Bài test IQ/trắc nghiệm thiên tài cho trẻ tiểu học" — không có giá trị chẩn đoán thực sự, chỉ nuôi văn hóa dán nhãn và so sánh; không phải giá trị cốt lõi của site. "Cách nhận biết con bị tự kỷ/ADHD tại nhà qua checklist" dạng tự chẩn đoán chắc chắn — các công cụ sàng lọc chuẩn đòi hỏi chuyên gia đánh giá trực tiếp, bài kiểu "tự test xong kết luận" dễ gây hoang mang hoặc chủ quan sai; chỉ viết dấu hiệu CẦN LƯU Ý kèm khuyến nghị khám chuyên khoa, không đưa ra kết luận. "Trẻ dậy thì sớm sẽ thấp lùn/thiệt thòi cả đời" dạng khẳng định hù dọa — thực tế phụ thuộc nhiều yếu tố và cần bác sĩ nội tiết đánh giá từng ca, không nên khái quát hóa gây sợ hãi; viết cân bằng có ngưỡng bình thường rõ ràng. "Ép con đọc sách sớm để không thua bạn" — nuôi áp lực thành tích từ chính chuyên mục phát triển, mâu thuẫn với tinh thần tôn trọng tốc độ riêng của từng trẻ. "Dán nhãn con lười học/hư khi thực chất có khó khăn học tập" — đây chính là điều chuyên mục phải LẬT NGƯỢC lại chứ không được củng cố thêm.
                TEXT,

            'audience' => 'Cha mẹ Việt 30-42 tuổi có con 6-12 tuổi học tiểu học, quan tâm sát đến việc học và sự phát triển toàn diện của con, thường nhận thông tin (đúng và sai) từ giáo viên chủ nhiệm, họp phụ huynh và hội nhóm lớp; bắt đầu để ý những dấu hiệu "khác thường" ở con nhưng chưa biết nên yên tâm hay nên lo, đọc sau các buổi họp phụ huynh hoặc ngay sau khi giáo viên phản ánh về con qua tin nhắn/Zalo nhóm lớp.',

            'constraints' => 'Không hù dọa về dậy thì sớm hay các khó khăn học tập; mọi mốc phát triển ghi rõ khoảng bình thường trước khi nêu dấu hiệu cần khám; tuyệt đối không tự chẩn đoán ADHD/dyslexia thay chuyên gia — luôn hướng đến đánh giá chuyên môn; không gắn mác đạo đức (lười, hư, dốt) cho biểu hiện có thể là khó khăn phát triển; không nuôi văn hóa so sánh IQ, thành tích giữa các trẻ; dẫn nguồn y khoa - tâm lý giáo dục uy tín; giọng điềm tĩnh, trấn an có cơ sở.',

            'style_sample' => <<<'TEXT'
                Trong buổi họp phụ huynh cuối học kỳ, cô giáo nhẹ nhàng nhắc: "Con ở lớp hơi khó tập trung, hay quên vở, các bạn làm xong bài con vẫn đang loay hoay". Trên đường về, bạn vừa buồn vừa bối rối — không biết đây là do con còn ham chơi, hay có điều gì đó cần được nhìn kỹ hơn. Ở tuổi tiểu học, sự khác biệt giữa "một đứa trẻ hiếu động bình thường" và "một đứa trẻ có dấu hiệu tăng động giảm chú ý" không nằm ở việc con có nghịch hay không, mà nằm ở MỨC ĐỘ và TÍNH NHẤT QUÁN của khó khăn đó — nó có xảy ra ở nhiều bối cảnh khác nhau (ở lớp, ở nhà, khi chơi) không, có kéo dài liên tục từ nhiều tháng không, và có thực sự cản trở con học tập - kết bạn không. Đây không phải điều cha mẹ có thể tự kết luận qua một buổi họp phụ huynh, và cũng không nên vội gắn cho con hai chữ "lười học" khi có thể con đang thực sự cần một cách hỗ trợ khác. Trong bài này, mình sẽ cùng bạn nhìn kỹ những dấu hiệu đáng chú ý, phân biệt với sự hiếu động - đãng trí thông thường của tuổi tiểu học, và quan trọng nhất — biết khi nào và ở đâu để tìm một đánh giá chuyên môn đáng tin cậy, thay vì tự mình phán đoán trong lo lắng.
                TEXT,
        ],

        // === Trẻ tiểu học (6-12 tuổi) > Dinh dưỡng cho trẻ ===
        [
            'parent_slug' => 'tre-tieu-hoc-6-12-tuoi',
            'slug'        => 'dinh-duong-cho-tre-3',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: bữa sáng vội trước giờ học, suất ăn bán trú ngoài tầm kiểm soát, đồ ăn vặt cổng trường, phòng ngừa THỪA CÂN học đường (hướng ngược mầm non).
                - KHÔNG viết: ăn dặm/hành vi ăn uống tuổi nhỏ hơn, bệnh lý liên quan cân nặng như tiểu đường (→ Bệnh thường gặp).
                - Chuyển hướng nhận thức quan trọng: từ tuổi này nỗi lo chính là THỪA CÂN đô thị hóa, không phải "còi/biếng ăn" như mầm non — nhiều cha mẹ chưa kịp thích nghi.
                - Thẳng thắn thừa nhận cha mẹ chỉ còn kiểm soát 1-2 bữa/ngày — trọng tâm tối ưu ĐÚNG bữa mình còn nắm quyền, không giả vờ vẫn quản lý toàn bộ như hồi mẫu giáo.
                - KPI: đo bằng lượt lưu bài "thực đơn bữa sáng 15 phút" và mức độ nhận thức thay đổi về nguy cơ béo phì học đường (khảo sát/bình luận).
                - Bữa sáng chuẩn bị tối hôm trước là việc BỐ cũng làm được — không phải mặc định của riêng mẹ.
                TEXT,

            'core_focus' => <<<'TEXT'
                Ăn uống thực tế cho trẻ tiểu học khi phần lớn bữa ăn không còn nằm trong tầm kiểm soát trực tiếp của cha mẹ: bữa sáng trước giờ học (thường ăn vội vì cả nhà gấp giờ, ảnh hưởng khả năng tập trung buổi sáng), suất ăn bán trú (hiểu cách trường tổ chức thực đơn, biết con ăn đủ không khi không tận mắt kiểm tra, trao đổi với trường khi nghi ngờ), kiểm soát đồ ăn vặt - nước ngọt cổng trường (con đã tự cầm tiền mua được, không ai giám sát), phòng ngừa thừa cân - béo phì học đường tăng nhanh ở đô thị (ít vận động, đồ ăn nhanh dễ tiếp cận, thói quen hình thành từ đây theo đến trưởng thành), và nhu cầu vi chất tăng khi một số trẻ dậy thì sớm cuối giai đoạn. KHÔNG lấn sân: ăn dặm/hành vi ăn uống tuổi nhỏ hơn, bệnh lý liên quan cân nặng như tiểu đường (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                "Con ăn bán trú cả ngày, tối về hỏi ăn gì con chỉ nhớ mỗi món tráng miệng" — thực tế của phần lớn cha mẹ có con tiểu học, nhưng nội dung dinh dưỡng học đường tiếng Việt vẫn viết như thể cha mẹ tự nấu và quan sát được từng bữa. Ba điểm khác biệt: (1) Thẳng thắn thừa nhận cha mẹ chỉ còn kiểm soát 1-2 bữa/ngày — trọng tâm tối ưu ĐÚNG bữa mình còn nắm quyền; (2) Xử lý trực diện "kinh tế cổng trường" — con đã có tiền tiêu vặt tự mua đồ ăn vặt, giải pháp là dạy con TỰ CHỌN đúng chứ không phải cấm đoán bất khả thi; (3) Đặt vấn đề thừa cân - béo phì học đường bằng dữ liệu đô thị Việt Nam thay vì lặp lại nỗi lo "biếng ăn - còi" của mầm non — chuyển hướng quan trọng nhiều cha mẹ chưa kịp thích nghi.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn đang tăng theo xu hướng đô thị hóa: "thực đơn bữa sáng cho trẻ tiểu học đi học", "con ăn bán trú ở trường có đủ chất không", "cách hạn chế con ăn vặt ở cổng trường", "trẻ tiểu học béo phì phải làm sao". (2) Bài "thực đơn bữa sáng 15 phút trước giờ học" là nội dung bookmark cao — đo bằng lượt lưu, giải pháp cho khung giờ gấp gáp nhất trong ngày của mọi gia đình có con đi học. (3) Xây nhận thức phòng ngừa béo phì học đường như một vấn đề sức khỏe cộng đồng mới nổi tại đô thị Việt Nam, khác hẳn nỗi lo "còi, biếng ăn" thống trị các cụm tuổi nhỏ hơn — đo bằng mức thay đổi nhận thức qua khảo sát/bình luận độc giả. (4) Liên kết lùi về Dinh dưỡng cho trẻ (Trẻ mầm non 3-6 tuổi) cho cha mẹ có con nhỏ hơn, và sang Bệnh thường gặp khi vấn đề cân nặng đã thành bệnh lý cần khám.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Sáng nào cũng cuống cuồng, con ăn vội miếng bánh mì trong xe hoặc nhịn luôn vì sợ trễ học — có ảnh hưởng gì đến việc học buổi sáng không?"; "Trường tổ chức bán trú, mình không biết thực đơn hôm đó thế nào, con có ăn hết suất không — chỉ nghe con kể lại nửa vời"; "Con xin tiền mua nước ngọt, snack ở cổng trường mỗi ngày, nói không cho thì bảo bạn nào cũng có"; "Con tăng cân nhanh trong năm học, cô giáo cũng nhắc con hơi ục ịch so với bạn cùng lớp — mình lo béo phì mà không biết bắt đầu điều chỉnh từ đâu khi con ăn ở trường cả ngày"; "Con bắt đầu dậy thì sớm, ăn nhiều hẳn lên, không biết có cần bổ sung canxi - sắt gì đặc biệt không hay cứ ăn uống bình thường là đủ"; "Con chê rau, chỉ thích đồ chiên rán, gà rán mua ở cổng trường, cơm nhà nấu thì ăn qua loa"; "Suất ăn bán trú nghe review trên mạng nói nhiều trường cắt xén — mình có nên chuyển con sang mang cơm không, có bất tiện cho con khi khác bạn bè?". Nền chung: đây là giai đoạn đầu tiên cha mẹ phải buông quyền kiểm soát bữa ăn của con cho nhà trường và cho chính con, trong khi hậu quả dinh dưỡng dài hạn (béo phì, thiếu vi chất) lại đang hình thành âm thầm.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Review xếp hạng suất ăn bán trú các trường" — dễ gây tranh cãi, quy chụp nhà trường thiếu căn cứ kiểm chứng cụ thể; chỉ viết khung tiêu chí để cha mẹ tự đánh giá và trao đổi với nhà trường. "Thực đơn giảm cân cho trẻ béo phì" cấp tốc — nguy hiểm với cơ thể đang tăng trưởng, cắt giảm sai cách ảnh hưởng phát triển; chỉ viết nguyên tắc điều chỉnh cân bằng dài hạn kèm khuyến nghị gặp chuyên gia dinh dưỡng nhi khi cần. "Thực phẩm chức năng tăng chiều cao/tăng cân cho trẻ tiểu học" — thị trường quảng cáo tràn lan thiếu bằng chứng; chỉ viết bài bóc tách để cha mẹ không mất tiền oan. "Cấm tuyệt đối con ăn vặt cổng trường" dạng cứng nhắc — bất khả thi khi con đã tự mua được, dễ đẩy con ăn giấu giếm nhiều hơn; thay bằng dạy con tự chọn có kiểm soát và cho phép ăn vặt có chừng mực. "Sữa học đường/sản phẩm bổ sung vi chất cụ thể nào tốt nhất" — dễ thành bài quảng cáo trá hình; đánh giá sản phẩm cụ thể thuộc danh mục Đánh giá sản phẩm, ở đây chỉ nêu nguyên tắc dinh dưỡng chung.
                TEXT,

            'audience' => 'Cha mẹ Việt 30-42 tuổi có con học tiểu học bán trú tại các đô thị/ven đô, buổi sáng vội vã đưa con đến trường đúng giờ nên bữa sáng thường bị rút gọn, buổi trưa con ăn ở trường ngoài tầm quan sát trực tiếp, chỉ thực sự nấu và kiểm soát được bữa tối; bắt đầu lo lắng về cân nặng của con theo hướng NGƯỢC với giai đoạn mầm non — sợ con ăn quá nhiều đồ vặt và tăng cân nhanh thay vì sợ con biếng ăn.',

            'constraints' => 'Không body-shaming hay gọi con là béo, mập trước mặt trẻ; giải pháp phải khả thi trong khung giờ sáng vội và khi cha mẹ không giám sát được bữa trưa - đồ ăn vặt; không cổ vũ cấm đoán cực đoan, hướng đến dạy con tự chọn có kiểm soát; không quảng cáo sữa học đường, thực phẩm chức năng; số liệu béo phì học đường dẫn nguồn Viện Dinh dưỡng quốc gia/Bộ Y tế; giọng thực dụng, đồng cảm với lịch trình bận rộn của gia đình có con đi học.',

            'style_sample' => <<<'TEXT'
                7 giờ kém 10, con vẫn đang xỏ giày, bạn dúi vội vào tay con nửa cái bánh mì kèm câu "ăn trên đường đi nhé" — và đó gần như là toàn bộ bữa sáng của con hôm nay. Nếu cảnh này lặp lại quen thuộc trong nhà bạn, bạn không đơn độc đâu: đây là kịch bản buổi sáng của rất nhiều gia đình có con đi học, khi giờ giấc của cả nhà đều eo hẹp như nhau. Nhưng có một điều đáng để bạn dành thêm 5 phút tối hôm trước: bữa sáng ảnh hưởng trực tiếp đến khả năng tập trung của con trong 2-3 tiết học đầu ngày — một đứa trẻ bụng rỗng đến 9-10 giờ sáng sẽ mệt mỏi, khó tập trung, dễ cáu gắt hơn hẳn. Tin vui là bữa sáng tốt không cần cầu kỳ hay nấu nướng buổi sáng: bí quyết nằm ở việc CHUẨN BỊ TỪ TỐI HÔM TRƯỚC, để buổi sáng chỉ còn việc lấy ra và ăn. Trong bài này, mình sẽ gợi ý cho bạn nhóm thực đơn "chuẩn bị trước - ăn nhanh" chỉ mất 2 phút buổi sáng, cân đối đủ tinh bột - đạm để con no bụng đến giờ ra chơi, và một vài lựa chọn để con có thể tự lấy ăn nếu một sáng nào đó bạn thực sự không kịp chuẩn bị.
                TEXT,
        ],

        // === Trẻ tiểu học (6-12 tuổi) > Bệnh thường gặp ===
        [
            'parent_slug' => 'tre-tieu-hoc-6-12-tuoi',
            'slug'        => 'benh-thuong-gap-5',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: cận thị học đường, cong vẹo cột sống, béo phì, VÀ mở rộng có chủ đích: sức khỏe TÂM LÝ (lo âu học tập, biểu hiện thể chất của stress — đau bụng/đau đầu trước giờ thi).
                - KHÔNG viết: hành vi/kỷ luật (→ Chăm sóc & nuôi dạy), mốc phát triển/dậy thì sớm (→ Phát triển của trẻ).
                - Đây là category ĐẦU TIÊN trong toàn hành trình site đưa sức khỏe tâm lý vào "bệnh thường gặp" — mọi triệu chứng thể chất do lo âu phải trình bày ngang hàng bệnh thể chất, không phải "con làm nũng".
                - Luôn khuyến nghị loại trừ nguyên nhân thực thể qua khám TRƯỚC khi kết luận là tâm lý — không được bỏ qua bước này.
                - KPI: đo bằng tỷ lệ độc giả nhận diện đúng biểu hiện thể chất của lo âu (thay vì chỉ khám tiêu hóa lặp lại vô ích) và mức lan tỏa nhận thức "sức khỏe tâm lý ngang bệnh thể chất".
                TEXT,

            'core_focus' => <<<'TEXT'
                Các vấn đề sức khỏe THỂ CHẤT và — lần đầu tiên trong hành trình của site — sức khỏe TÂM LÝ của trẻ 6-12 tuổi, giai đoạn con tự lập hơn ở trường khiến cha mẹ khó giám sát: cận thị học đường (tăng mạnh nhất giai đoạn này do thời gian nhìn gần tăng vọt, dấu hiệu nhận biết sớm, khám mắt định kỳ), cong vẹo cột sống (cặp sách nặng, bàn ghế sai chuẩn, ngồi sai tư thế kéo dài — cách phát hiện sớm, chọn cặp, điều chỉnh tư thế), béo phì (biến chứng từ dinh dưỡng học đường không kiểm soát), và ĐIỂM MỞ RỘNG quan trọng: sức khỏe tâm lý — lo âu học tập, căng thẳng thi cử, biểu hiện thể chất của stress (đau bụng, đau đầu trước ngày thi, mất ngủ) mà cha mẹ dễ bỏ qua vì tưởng chỉ là bệnh vặt. KHÔNG lấn sân: hành vi - kỷ luật (thuộc Chăm sóc & nuôi dạy), mốc phát triển nhận thức - dậy thì sớm (thuộc Phát triển của trẻ).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Đây là category ĐẦU TIÊN trong hành trình từ thai kỳ đến nay của site đưa SỨC KHỎE TÂM LÝ của trẻ vào phần "bệnh thường gặp" — có chủ đích, không ngẫu nhiên: các cụm tuổi trước xoay quanh bệnh lý thể chất vì trẻ chưa đối mặt áp lực học tập thật; ở tuổi tiểu học, lo âu và stress bắt đầu là "bệnh" có thật, nhưng hầu như không nội dung "bệnh trẻ em" tiếng Việt nào xếp chung nhóm này — sức khỏe tâm lý luôn bị tách riêng xa lạ mà cha mẹ ít nghĩ tới khi con "kêu đau bụng trước giờ kiểm tra". Ba điểm khác biệt: (1) Dạy nhận ra biểu hiện THỂ CHẤT của lo âu học tập (đau bụng, đau đầu, buồn nôn sáng đi thi) để không chỉ khám tiêu hóa lặp lại mà bỏ sót gốc rễ tâm lý; (2) Cận thị/cong vẹo cột sống gắn liền NGUYÊN NHÂN HỌC ĐƯỜNG cụ thể (cặp sách, bàn ghế, ánh sáng lớp) thay vì bài y khoa chung chung; (3) Mọi nội dung tâm lý nhấn mạnh đây là VẤN ĐỀ SỨC KHỎE ngang bệnh thể chất, không phải "con làm nũng".
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn thể chất tăng mạnh theo tuổi học đường: "trẻ tiểu học cận thị phải làm sao", "dấu hiệu cong vẹo cột sống ở trẻ", "cặp sách nặng bao nhiêu là hại con", đồng thời tiên phong cho cụm truy vấn tâm lý mới nổi: "con lo lắng trước khi thi", "trẻ đau bụng mỗi khi đi học có phải tâm lý", "áp lực học tập ở trẻ tiểu học". (2) Là chuyên mục ĐẦU TIÊN gắn nhãn "sức khỏe tâm lý trẻ em" như một phần bình thường của nội dung sức khỏe con — đo bằng tỷ lệ độc giả nhận diện đúng biểu hiện thể chất của lo âu thay vì chỉ khám tiêu hóa lặp lại vô ích, đặt nền cho các cụm tuổi lớn hơn (tuổi teen) sau này tiếp tục mở rộng. (3) Liên kết chặt với Chăm sóc & nuôi dạy (đồng hành thi cử không tạo áp lực) và Phát triển của trẻ (phân biệt lo âu học tập với ADHD) trong cùng cụm 6-12 tuổi. (4) Xây uy tín y khoa - tâm lý học đường ngay từ giai đoạn này để sang cụm tuổi teen, chủ đề sức khỏe tâm lý được độc giả đã quen tiếp cận nghiêm túc, không né tránh.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con ngồi bàn học tự nhiên nheo mắt nhìn bảng, cô giáo nhắc con hay chép nhầm bài — có phải cận thị rồi không, sao con không kêu bao giờ?"; "Cặp sách con nặng trĩu đủ sách vở cả tuần, thấy con hơi lệch vai khi đi học về — có phải dấu hiệu vẹo cột sống không, cặp nặng bao nhiêu là quá tải?"; "Con tăng cân nhanh trong năm học, ngồi lâu chơi điện thoại, ít vận động hẳn so với hồi mẫu giáo"; "Cứ sáng có bài kiểm tra là con kêu đau bụng, đau đầu, có hôm nôn thật — đưa đi khám bác sĩ bảo không có bệnh gì, vậy con bị làm sao?"; "Con mất ngủ, trằn trọc cả đêm trước ngày thi học kỳ, dậy sớm hơn bình thường và có vẻ rất căng thẳng — mình có nên giảm áp lực học hay đây chỉ là chuyện bình thường của học sinh?"; "Con hay cáu gắt vô cớ vào mùa thi, có lúc khóc không rõ lý do — mình lo con bị stress mà không biết phải giúp thế nào ngoài việc bảo con đừng lo lắng nữa"; "Bác sĩ đo mắt bảo con cận 1.5 độ, chưa cần đeo kính thường xuyên — theo dõi thế nào để không tăng độ nhanh?". Nền chung: khi con tự lập hơn ở trường, nhiều vấn đề sức khỏe — cả thể chất lẫn tinh thần — âm thầm tích lũy cả ngày mà cha mẹ chỉ nhìn thấy phần nổi vào buổi tối, cần biết cách đọc đúng những tín hiệu nhỏ đó.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Bài tập mắt thần kỳ chữa khỏi cận thị không cần đeo kính" — phản khoa học, trì hoãn điều chỉnh kính đúng lúc có thể khiến mắt tăng độ nhanh hơn; chỉ viết về phòng ngừa tăng độ và thói quen bảo vệ mắt có bằng chứng. "Coi nhẹ lo âu học tập là con làm nũng, yếu đuối, thiếu bản lĩnh" — chính định kiến này khiến sức khỏe tâm lý trẻ em bị bỏ qua nhiều năm nay; chuyên mục phải LẬT NGƯỢC định kiến này chứ không củng cố. "Liệt kê các bệnh tâm lý nghiêm trọng (trầm cảm, tự hại) ở tuổi tiểu học" — chưa phù hợp mức độ phổ biến ở lứa tuổi này và dễ gây hoang mang không cần thiết; nội dung tâm lý ở cụm này chỉ dừng ở lo âu - stress học đường mức độ thường gặp, các vấn đề nặng hơn để lại cho cụm tuổi teen. "Thực phẩm chức năng bổ mắt/tăng chiều cao chống vẹo cột sống" — quảng cáo tràn lan thiếu bằng chứng thay thế được biện pháp cơ học đúng (bàn ghế, ánh sáng, tư thế); chỉ viết bài bóc tách. "Giảm cân nhanh cho trẻ béo phì" — nguy hiểm với cơ thể đang phát triển, đã bị loại ở chuyên mục Dinh dưỡng cho trẻ, không lặp lại hướng này ở góc độ bệnh lý.
                TEXT,

            'audience' => 'Cha mẹ Việt 30-42 tuổi có con học tiểu học, con dành phần lớn thời gian ban ngày ở trường ngoài tầm quan sát trực tiếp của cha mẹ nên chỉ nhận ra vấn đề sức khỏe khi đã có biểu hiện rõ (nheo mắt, lệch vai, đau bụng lặp lại); bắt đầu nhận thấy áp lực học tập thật sự đang ảnh hưởng đến con nhưng thường chỉ nghĩ đến khía cạnh thể chất trước, chưa quen liên hệ triệu chứng với nguyên nhân tâm lý; đọc sau khi đưa con đi khám mà bác sĩ không tìm ra bệnh thực thể rõ ràng.',

            'constraints' => 'Không hù dọa về cận thị, cong vẹo cột sống hay tâm lý; mọi triệu chứng thể chất do lo âu phải trình bày cẩn trọng, luôn khuyến nghị loại trừ nguyên nhân thực thể qua khám trước khi kết luận là tâm lý; tuyệt đối không coi nhẹ hay chế giễu biểu hiện lo âu của trẻ; không tạo thêm áp lực thành tích khi bàn về stress thi cử; dẫn nguồn nhãn khoa - chỉnh hình - tâm lý học đường uy tín; giọng điềm tĩnh, coi sức khỏe tâm lý ngang hàng sức khỏe thể chất.',

            'style_sample' => <<<'TEXT'
                Sáng thứ Hai nào có tiết kiểm tra 15 phút, con bạn cũng ôm bụng kêu đau, có hôm còn nôn khan trước khi ra khỏi nhà. Bạn đã đưa con đi khám tiêu hóa hai lần, bác sĩ đều nói dạ dày con hoàn toàn bình thường — vậy cơn đau ấy từ đâu ra? Đây là lúc đáng để nhìn sang một hướng khác: ở trẻ tiểu học, lo âu và căng thẳng hoàn toàn có thể biểu hiện thành triệu chứng THẬT trên cơ thể — đau bụng, đau đầu, buồn nôn — không phải con giả vờ, và cũng không phải "chỉ là tâm lý nên không đáng lo". Não bộ và hệ tiêu hóa của trẻ liên kết với nhau chặt chẽ đến mức các bác sĩ nhi khoa gọi đó là "trục não - ruột", và một đứa trẻ căng thẳng trước giờ kiểm tra hoàn toàn có thể đau bụng thật, dù dạ dày con không hề có tổn thương gì. Tin quan trọng cần nhớ: đây là dấu hiệu sức khỏe cần được coi trọng nghiêm túc như bất kỳ triệu chứng thể chất nào khác, không phải điều con "làm quá lên". Trong bài này, mình sẽ giúp bạn nhận diện đúng những biểu hiện thể chất của lo âu học tập ở con, phân biệt với vấn đề tiêu hóa thực sự, và một vài cách nhẹ nhàng để giảm áp lực trước mỗi kỳ thi mà không cần phải yêu cầu con "đừng lo nữa" — câu nói vốn dĩ chưa bao giờ thực sự có tác dụng.
                TEXT,
        ],

        // === Sức khỏe cha mẹ ===
        [
            'parent_slug' => null,
            'slug'        => 'suc-khoe-cha-me',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: sức khỏe thể chất/tinh thần của CHA MẸ (không phải con) sau khi có con — đau lưng/cổ tay, thiếu ngủ mãn tính, trầm cảm/burn-out CẢ BỐ LẪN MẸ.
                - KHÔNG viết: Sức khỏe mẹ bầu (chỉ 9 tháng thai kỳ) — đây là sức khỏe SAU khi con đã ra đời.
                - Khác biệt bắt buộc giữ: viết sức khỏe tinh thần NGƯỜI BỐ ngang hàng người mẹ — hầu hết nội dung Việt về "sau sinh" chỉ nhắm vào mẹ.
                - Mọi gợi ý phải trả lời được "làm gì trong 15 phút rảnh hiếm hoi" — không viết mẹo chung chung bất khả thi với cha mẹ có con nhỏ.
                - KPI: đo bằng tỷ lệ quay lại của độc giả khi mối lo trực tiếp về con đã dịu và thời gian đọc trung bình.
                TEXT,

            'core_focus' => <<<'TEXT'
                Sức khỏe thể chất và tinh thần của CHÍNH cha mẹ — không phải con — sau khi đã có con, giai đoạn cha mẹ có xu hướng đặt sức khỏe bản thân xuống cuối danh sách ưu tiên. Nội dung: các vấn đề thể chất phổ biến của người mới làm cha mẹ (đau lưng - đau cổ tay do bế con sai tư thế, thiếu ngủ mãn tính kéo dài nhiều năm, tăng - sụt cân sau sinh không kiểm soát, đau khớp gối khi ngồi xổm chơi cùng con), sức khỏe tinh thần (trầm cảm sau sinh không chỉ ở mẹ mà cả ở bố, burn-out khi lo toan cùng lúc công việc - con cái - nhà cửa, cảm giác cô đơn của người ở nhà chăm con toàn thời gian, áp lực phải làm "cha mẹ hoàn hảo" từ mạng xã hội), và thói quen khám sức khỏe định kỳ bị trì hoãn nhiều năm liền vì luôn ưu tiên đưa con đi khám trước. KHÔNG lấn sân: Sức khỏe mẹ bầu (chỉ trong 9 tháng thai kỳ) — đây là sức khỏe cha mẹ SAU khi con đã ra đời, viết cho cả hai vợ chồng chứ không chỉ mẹ.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Sau khi sinh, ai cũng hỏi thăm mẹ và em bé — gần như không ai hỏi "thế bố dạo này có ổn không". Đó chính xác là khoảng trống chuyên mục này lấp vào. (1) Là chuyên mục hiếm hoi viết sức khỏe tinh thần - thể chất của NGƯỜI BỐ ngang hàng người mẹ — hầu hết nội dung tiếng Việt về "sau sinh" chỉ nhắm vào mẹ, bỏ qua thực tế nhiều ông bố cũng trải qua trầm cảm hay kiệt sức nhưng không có ngôn ngữ để gọi tên hay xin giúp đỡ vì áp lực "đàn ông phải mạnh mẽ". (2) Không viết dạng "mẹo chăm sóc bản thân" chung chung (ngủ đủ giấc, ăn uống lành mạnh) vốn bất khả thi với cha mẹ có con nhỏ — mỗi bài phải trả lời được câu hỏi cụ thể "vậy làm gì trong 15 phút rảnh hiếm hoi giữa ngày". (3) Thẳng thắn gọi tên cảm giác tội lỗi khi "dành thời gian cho bản thân" bị xem là ích kỷ trong văn hóa Việt, nhất là với người mẹ — tiếp cận bằng sự đồng cảm thay vì hô hào "hãy yêu bản thân" sáo rỗng.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn còn trống trên thị trường nội dung tiếng Việt: "trầm cảm sau sinh ở nam giới", "đau lưng sau khi bế con nhiều", "làm sao để ngủ đủ khi có con nhỏ", "burn-out làm cha mẹ là gì". (2) Là chuyên mục "chăm sóc người chăm sóc" duy nhất của site — đo bằng tỷ lệ quay lại của độc giả khi các mối lo trực tiếp về con đã dịu bớt và thời gian đọc trung bình. (3) Liên kết chéo với Hôn nhân (khi cả hai vợ chồng đều kiệt sức, mối quan hệ dễ căng thẳng theo) và Quyền lợi & pháp lý (chế độ nghỉ phép, bảo hiểm y tế). (4) Khai thác mảng ít cạnh tranh SEO vì đa số site sức khỏe chỉ viết cho mẹ bầu hoặc trẻ em, bỏ trống hoàn toàn sức khỏe cha mẹ hậu sinh.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con đã 2 tuổi mà mình vẫn chưa ngủ trọn giấc đêm nào, người lúc nào cũng như đeo bao cát"; "Bế con suốt ngày, giờ cúi xuống nhặt đồ cũng đau lưng, đi khám thì bác sĩ bảo do tư thế bế sai kéo dài"; "Chồng mình sau khi có con tự nhiên ít nói hẳn, hay cáu, không biết đó có phải trầm cảm không hay chỉ do stress công việc"; "Ai cũng hỏi thăm mẹ và bé, không ai hỏi bố có ổn không — cũng không biết chia sẻ với ai"; "Cả tuần không có nổi 30 phút cho riêng mình, muốn đi cắt tóc cũng phải xin phép như xin nghỉ việc"; "Định đi khám tổng quát mà cứ lần lữa 2 năm nay vì lúc nào cũng ưu tiên đưa con đi khám trước"; "Cảm thấy tội lỗi khi nghĩ đến việc gửi con đi để hai vợ chồng ra ngoài một buổi tối, dù chỉ vài tiếng". Nền chung: cha mẹ Việt có xu hướng coi việc chăm sóc bản thân là xa xỉ hoặc ích kỷ, và thiếu hẳn ngôn ngữ lẫn không gian để thừa nhận mình đang kiệt sức.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Lịch trình skincare/làm đẹp lấy lại vóc dáng sau sinh" — thuộc mảng làm đẹp, không phải sức khỏe, dễ trôi thành nội dung PR mỹ phẩm. "Thực đơn giảm cân sau sinh" — nhạy cảm về body-shaming, trùng các trang giảm cân chuyên biệt; chỉ đề cập ở mức phục hồi sức khỏe chung, không tập trung ngoại hình. "Cách trở thành cha mẹ hoàn hảo, không bao giờ nổi nóng với con" — cổ vũ chuẩn mực phi thực tế, đi ngược tinh thần chuyên mục là thừa nhận giới hạn của cha mẹ; thay bằng góc "phục hồi tinh thần sau một ngày tệ". "Tư vấn chuyên sâu về thuốc chống trầm cảm, liều dùng cụ thể" — vượt phạm vi biên tập và có thể nguy hiểm; chỉ dừng ở nhận diện dấu hiệu và khuyến khích tìm chuyên gia.
                TEXT,

            'audience' => 'Cha và mẹ Việt 28-45 tuổi đã có ít nhất 1 con, phần lớn đang trong giai đoạn con nhỏ 0-6 tuổi nên áp lực chăm sóc cao nhất; đọc vào những khoảnh khắc hiếm hoi có thời gian cho bản thân (đêm khuya, giờ nghỉ trưa), thường đọc SAU khi đã tìm xong thông tin cho con và mới sực nhớ ra bản thân cũng cần được chăm sóc.',

            'constraints' => 'Không cổ vũ chuẩn "cha mẹ hoàn hảo"; không body-shaming hay tập trung ngoại hình; đối xử bình đẳng giữa bố và mẹ, không mặc định chỉ mẹ mới cần chăm sóc bản thân; không kê đơn thuốc hay tư vấn y khoa chuyên sâu — chỉ nhận diện dấu hiệu và khuyến khích tìm bác sĩ/chuyên gia tâm lý; mọi gợi ý phải khả thi với quỹ thời gian ít ỏi thực tế.',

            'style_sample' => <<<'TEXT'
                2 giờ sáng, con thức giấc lần thứ ba trong đêm, và trong lúc dỗ con ngủ lại, bạn chợt nhận ra mình không nhớ nổi lần cuối cùng ngủ liền một mạch sáu tiếng là khi nào. Đây không phải chuyện "làm cha mẹ ai chẳng vậy" để bạn tự nhủ rồi bỏ qua — thiếu ngủ kéo dài hàng tháng trời là một vấn đề sức khỏe thật, ảnh hưởng đến cả khả năng kiên nhẫn với con lẫn sức khỏe lâu dài của chính bạn. Và có một điều ít ai nói với bạn: người đang kiệt sức bên cạnh bạn — vợ hoặc chồng bạn — rất có thể cũng đang chịu đựng y hệt, chỉ là không ai hỏi han họ như cách người ta hỏi han người mẹ và em bé. Trong bài này, chúng ta sẽ không nói những lời khuyên sáo rỗng kiểu "ngủ khi con ngủ" — thứ gần như bất khả thi với phần lớn cha mẹ — mà đi thẳng vào những điều chỉnh nhỏ, thực hiện được ngay trong tuần này, để bạn bắt đầu lấy lại sức khỏe cho chính mình, không phải sau khi con lớn, mà ngay từ hôm nay.
                TEXT,
        ],

        // === Hôn nhân ===
        [
            'parent_slug' => null,
            'slug'        => 'hon-nhan',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: hôn nhân SAU khi có con — duy trì kết nối vợ chồng, bất đồng quan điểm dạy con, "mental load", đời sống chăn gối sau sinh, hàn gắn sau cãi vã vì con.
                - KHÔNG viết: mâu thuẫn với ông bà nội ngoại (thuộc các bài nuôi dạy con theo tuổi) — chỉ tập trung mối quan hệ HAI VỢ CHỒNG.
                - Khác biệt: viết riêng cho GIAI ĐOẠN có con nhỏ (không phải hôn nhân nói chung) — biến số con cái đã đổi hoàn toàn quỹ thời gian/năng lượng.
                - "Mental load" là chủ đề trọng tâm — gọi tên gánh nặng vô hình mà nhiều người chồng không nhận ra vợ đang mang.
                - KPI: đo bằng thời gian đọc và tỷ lệ độc giả tìm đến ngay sau một trận cãi vã (traffic tăng đột biến theo tình huống).
                TEXT,

            'core_focus' => <<<'TEXT'
                Nuôi dưỡng mối quan hệ vợ chồng SAU khi có con — giai đoạn hôn nhân dễ tổn thương nhất vì gần như toàn bộ thời gian và năng lượng dồn cho con: duy trì kết nối vợ chồng (date night thực tế với ngân sách và thời gian hạn hẹp, giao tiếp khi cả hai đều kiệt sức), bất đồng quan điểm nuôi dạy con giữa hai vợ chồng (một người nghiêm một người chiều, tranh cãi trước mặt con), chia sẻ việc nhà - chăm con công bằng (gánh nặng vô hình "mental load" thường dồn lên một người), đời sống vợ chồng thay đổi sau sinh, và cách hàn gắn sau những trận cãi vã vì chuyện con cái. KHÔNG lấn sân: mâu thuẫn với ông bà nội ngoại (thuộc các bài nuôi dạy con theo tuổi ở từng danh mục con) — chuyên mục này chỉ tập trung đúng mối quan hệ giữa hai vợ chồng với nhau.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Tối nay, sau khi con ngủ, hai vợ chồng lại ngồi cạnh nhau trên sofa — mỗi người một chiếc điện thoại, không nói với nhau câu nào ngoài "con ngủ chưa" và "mai ai đưa con đi học". Không phải vì hết yêu, mà vì năng lượng cả ngày đã dồn hết cho một đứa trẻ. (1) Viết riêng cho GIAI ĐOẠN sau khi có con thay vì hôn nhân nói chung — khác các trang "giữ lửa hôn nhân" chung chung không tính đến biến số con cái đã thay đổi hoàn toàn quỹ thời gian và năng lượng của hai vợ chồng. (2) Coi "mental load" — gánh nặng vô hình của việc luôn phải nhớ, lên kế hoạch, quản lý mọi việc trong nhà — là chủ đề trọng tâm, gọi tên cảm giác kiệt sức mà nhiều người chồng không nhận ra vợ mình đang mang. (3) Thẳng thắn về đời sống chăn gối sau sinh — chủ đề gần như không ai viết nghiêm túc bằng tiếng Việt ngoài các bài giật tít hoặc né tránh hoàn toàn.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn "giữ lửa hôn nhân sau khi có con", "chồng không giúp việc nhà phải làm sao", "hai vợ chồng bất đồng quan điểm dạy con", "quan hệ vợ chồng sau sinh bao lâu thì bình thường". (2) Là nơi hiếm hoi thừa nhận hôn nhân có con nhỏ là một thử thách thật — đo bằng thời gian đọc và tỷ lệ độc giả đến ngay sau một tình huống căng thẳng cụ thể, xây niềm tin biên tập bằng sự trung thực thay vì hình ảnh gia đình hoàn hảo. (3) Liên kết chéo với Sức khỏe cha mẹ (khi kiệt sức ảnh hưởng trực tiếp đến mối quan hệ) và các danh mục "Chăm sóc & nuôi dạy" theo tuổi con (khi bàn về bất đồng quan điểm dạy con cụ thể).
                TEXT,

            'pain_points' => <<<'TEXT'
                "Từ ngày có con, hai vợ chồng chỉ nói chuyện về con, gần như quên mất cách nói chuyện với nhau như một cặp đôi"; "Chồng về nhà là nằm xem điện thoại trong khi mình tối tăm mặt mũi với con, nói thì bảo 'anh đi làm cả ngày mệt rồi'"; "Anh dạy con kiểu nghiêm khắc, em thì mềm mỏng, con bắt đầu biết 'chọn phe' ai dễ thì xin"; "Cãi nhau chuyện dạy con ngay trước mặt con, biết là sai mà lúc nóng không kiềm được"; "Hai năm nay gần như không có đời sống vợ chồng, mệt và ngại nói ra vì sợ chồng nghĩ mình hết yêu"; "Muốn có một buổi tối riêng hai vợ chồng mà gửi con cho ai cũng thấy ngại, thuê người trông thì tốn tiền". Nền chung: cả hai đều yêu con và yêu nhau, nhưng không ai dạy họ cách làm cả hai việc cùng lúc, và sự mệt mỏi khiến những hiểu lầm nhỏ dễ bùng thành khoảng cách lớn.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Bí kíp giữ chồng/giữ vợ không ngoại tình" — giật tít nghi kỵ, đi ngược tinh thần xây dựng của chuyên mục; chỉ viết dạng xây dựng lòng tin tích cực. "Cẩm nang ly hôn êm đẹp" — thuộc phạm vi pháp lý (Quyền lợi & pháp lý), không phải trọng tâm ở đây vì chuyên mục này ưu tiên giữ và xây mối quan hệ. "Trắc nghiệm bạn có đang hôn nhân hạnh phúc" dạng quiz giải trí — không có giá trị hành động thật. "Chuyện phòng the" viết dạng giật gân, tình dục hóa — phải giữ tông điềm tĩnh, y khoa - tâm lý, không rẻ tiền hóa chủ đề nhạy cảm này.
                TEXT,

            'audience' => 'Vợ chồng Việt 27-42 tuổi đã có ít nhất 1 con nhỏ từ sơ sinh đến 10 tuổi, cả hai đi làm, cảm thấy mối quan hệ vợ chồng bị xếp xuống cuối danh sách ưu tiên; thường tìm đọc sau một trận cãi vã vì chuyện con cái hoặc vào những đêm cả hai đều mệt mỏi và xa cách.',

            'constraints' => 'Không giật tít nghi kỵ (ngoại tình, ly hôn); không đứng về phe nào trong bất đồng vợ chồng, luôn đưa góc nhìn hai chiều; chuyện chăn gối viết điềm tĩnh, y khoa - tâm lý, không rẻ tiền hóa; mọi gợi ý phải khả thi với ngân sách và thời gian hạn hẹp của gia đình có con nhỏ; không đổ lỗi một giới cho gánh nặng gia đình.',

            'style_sample' => <<<'TEXT'
                Tối nay, sau khi con đã ngủ, hai vợ chồng bạn lại ngồi cạnh nhau trên sofa — mỗi người một chiếc điện thoại, gần như không nói với nhau câu nào ngoài "con ngủ chưa" và "mai ai đưa con đi học". Nếu khung cảnh này quen thuộc đến mức khiến bạn chột dạ, bạn không hề đơn độc — đây là một trong những giai đoạn dễ xa cách nhất của bất kỳ cuộc hôn nhân nào, không phải vì hai người hết yêu nhau, mà vì gần như toàn bộ năng lượng mỗi ngày đã dồn hết cho một đứa trẻ nhỏ. Tin tốt là kết nối vợ chồng không cần một kỳ nghỉ lãng mạn để hồi phục — nó có thể bắt đầu lại từ những khoảng 10-15 phút rất nhỏ mỗi ngày, nếu cả hai cùng chủ động. Trong bài này, chúng ta sẽ nói về cách nhận ra "mental load" đang âm thầm đè nặng lên ai trong hai người, vài cách đơn giản để có một buổi tối riêng tư mà không cần rời khỏi nhà, và làm sao để hai người cùng quay lại làm một đội, thay vì hai người chỉ đang cùng vận hành một "công ty chăm con".
                TEXT,
        ],

        // === Du lịch gia đình ===
        [
            'parent_slug' => null,
            'slug'        => 'du-lich-gia-dinh',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: du lịch cùng con theo ĐỘ TUỔI cụ thể, chọn điểm đến/thời điểm, đồ mang theo, mẹo di chuyển dài, ngân sách thực tế.
                - KHÔNG viết: an toàn/tổ chức không gian sống trong nhà (→ Nhà cửa & đời sống), review sản phẩm chi tiết (→ Đánh giá sản phẩm).
                - Mọi lời khuyên gắn ĐỘ TUỔI CỤ THỂ — không viết chung chung fit-all mọi gia đình.
                - Ưu tiên điểm đến GẦN, khả thi cuối tuần — không chỉ viết chuyến đi xa mơ mộng khó thực hiện thường xuyên.
                - KPI: đo bằng tỷ lệ mở rộng thành công của công thức 5 bài đã xuất bản sang điểm đến/độ tuổi mới, và CTR sang Tài chính gia đình.
                TEXT,

            'core_focus' => <<<'TEXT'
                Du lịch cùng con nhỏ cho gia đình Việt — từ chuyến đi ngắn cuối tuần gần nhà đến kỳ nghỉ dài ngày — với trọng tâm THỰC HÀNH: chọn điểm đến và thời điểm phù hợp theo đúng độ tuổi con (chuyến đi cùng trẻ sơ sinh khác hoàn toàn chuyến đi cùng trẻ mầm non hay tiểu học), danh sách đồ cần mang không thiếu không thừa, mẹo giữ con không quấy khóc trên các chặng di chuyển dài (máy bay, ô tô đường dài), và lập ngân sách cho cả chuyến đi để không vỡ kế hoạch giữa chừng. KHÔNG lấn sân: an toàn - tổ chức không gian sống trong nhà (thuộc Nhà cửa & đời sống); các vấn đề sức khỏe khi đi xa (say tàu xe, sốt khi du lịch) chỉ nhắc sơ lược, chi tiết y khoa thuộc các danh mục Bệnh thường gặp theo tuổi con.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Chuyến bay đầu tiên của con thường là nỗi lo lớn hơn cả việc chọn điểm đến — cha mẹ tưởng tượng cảnh con khóc ré suốt hai tiếng và cả nhà xuống sân bay trong tình trạng kiệt sức trước khi kỳ nghỉ kịp bắt đầu. Bài "kinh nghiệm du lịch cùng con" tiếng Việt hiện có thường viết chung cho mọi độ tuổi, trong khi nỗi sợ thật của mỗi giai đoạn hoàn toàn khác nhau. (1) Mỗi độ tuổi giải đúng MỘT nỗi sợ cụ thể — dưới 1 tuổi là tai đau lúc cất/hạ cánh (mẹo: cho bú/ngậm ti đúng lúc máy bay đổi độ cao), 1-3 tuổi là ăn vạ giữa sân bay vì lệch giờ ngủ (mẹo: chọn giờ bay trùng giấc ngủ trưa), 3-6 tuổi là say xe đường dài — không viết chung một bài "mẹo du lịch cùng con" cho cả ba nhóm tuổi; (2) Ngân sách nêu bằng con số thật (một chuyến 2 ngày 1 đêm tốn khoảng bao nhiêu) kèm mẹo tiết kiệm cụ thể không hy sinh trải nghiệm — đủ để cha mẹ tự trả lời "đi được không với số tiền này" ngay khi đọc, không phải đoán; (3) Điểm đến ưu tiên trong bán kính vài giờ di chuyển, lặp lại được mỗi 1-2 tháng — vì gắn kết gia đình đến từ TẦN SUẤT đi, không phải từ một chuyến đi xa hoành tráng mỗi năm.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn "du lịch cùng trẻ sơ sinh cần chuẩn bị gì", "địa điểm du lịch gần Hà Nội/TP.HCM cho gia đình có con nhỏ", "mẹo cho bé đi máy bay không khóc", "chi phí du lịch gia đình 4 người". (2) Nuôi 5 bài đã xuất bản (du lịch biển cùng trẻ nhỏ, đồ dùng cần mang, điểm đến gần Hà Nội, mẹo trên máy bay, ngân sách chuyến đi) thành chuỗi nội dung mẫu mực — đo bằng tỷ lệ mở rộng thành công sang điểm đến/độ tuổi mới theo đúng công thức đã có. (3) Liên kết chéo với Tài chính gia đình (lập ngân sách) và các danh mục theo tuổi con (đồ dùng phù hợp từng giai đoạn).
                TEXT,

            'pain_points' => <<<'TEXT'
                "Lần đầu cho con 8 tháng tuổi đi máy bay, sợ con khóc ré làm phiền hành khách khác"; "Không biết mang gì cho đủ mà không lỉnh kỉnh, mỗi lần đi như chuyển nhà"; "Muốn đi biển cho con nhỏ tắm nhưng sợ nắng, sợ nước biển không hợp da bé"; "Ngân sách du lịch cứ đội lên gấp đôi dự tính vì phát sinh đồ cho con"; "Con say xe từ nhỏ, đi hơn 1 tiếng là nôn, chuyến nào cũng thành cực hình"; "Muốn đưa con đi xa mà ông bà can vì sợ con còn bé, không biết nên nghe theo hay không". Nền chung: gia đình có con nhỏ muốn đi du lịch để gắn kết và cho con trải nghiệm, nhưng thiếu thông tin thực chiến khiến mỗi chuyến đi thành nỗi lo nhiều hơn niềm vui.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Top resort 5 sao sang trọng cho gia đình" — không phù hợp túi tiền đa số độc giả và dễ thành bài PR khách sạn; chỉ viết khi có góc thực dụng rõ ràng (giá, chính sách cho trẻ em). "Kinh nghiệm du lịch nước ngoài dài ngày" — vượt khả năng tài chính và thời gian của phần lớn độc giả mục tiêu, ưu tiên điểm đến trong nước gần trước. "Review chi tiết một khách sạn/resort cụ thể" theo hướng quảng cáo — nếu viết phải khách quan, không nhận tài trợ ẩn danh. "Du lịch mạo hiểm, phượt cùng trẻ nhỏ" — rủi ro an toàn cao, không phù hợp đối tượng đang ưu tiên an toàn cho con.
                TEXT,

            'audience' => 'Gia đình Việt có con từ 6 tháng đến 10 tuổi, thu nhập trung lưu thành thị, muốn đi du lịch cùng con nhưng còn ngại vì lo con quấy khóc hoặc vướng đồ đạc; đọc để lên kế hoạch cụ thể trước mỗi chuyến đi, ưu tiên điểm đến gần và chi phí vừa phải, đã có kinh nghiệm đi 1-2 chuyến và tìm thêm mẹo cho lần sau.',

            'constraints' => 'Ưu tiên điểm đến và mức chi phí trong tầm với gia đình trung lưu Việt Nam; mọi gợi ý phải cụ thể theo độ tuổi con; không PR trá hình khách sạn, resort hay hãng bay; luôn có phần ngân sách ước tính cụ thể; không cổ vũ hình thức du lịch mạo hiểm không phù hợp trẻ nhỏ.',

            'style_sample' => <<<'TEXT'
                Chuyến bay đầu tiên của con — với không ít cha mẹ, đây là nỗi lo lớn hơn cả việc chọn điểm đến. Bạn tưởng tượng ra cảnh con khóc ré suốt hai tiếng trên máy bay, ánh mắt khó chịu của hành khách xung quanh, và cả gia đình xuống sân bay trong tình trạng kiệt sức trước khi kỳ nghỉ kịp bắt đầu. Tin tốt là phần lớn nỗi lo đó có thể giải quyết bằng vài sự chuẩn bị rất cụ thể trước giờ bay, chứ không phải phép màu. Trong bài này, chúng ta sẽ đi qua đúng thời điểm trong ngày nên chọn chuyến bay để trùng giờ ngủ của con, cách chuẩn bị "vũ khí" chống quấy khóc khi cất - hạ cánh (đây là lúc tai con dễ đau nhất), danh sách đồ mang lên khoang không thể thiếu, và một điều ít ai nhắc tới: cách giữ bình tĩnh cho chính bạn nếu con vẫn khóc dù đã chuẩn bị kỹ — vì đôi khi, đó chỉ đơn giản là một chuyến bay khó, không phải vì bạn làm sai điều gì.
                TEXT,
        ],

        // === Nhà cửa & đời sống ===
        [
            'parent_slug' => null,
            'slug'        => 'nha-cua-doi-song',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: an toàn nhà theo mốc phát triển con, tổ chức góc chơi/học trong không gian nhỏ, chia việc nhà, chọn nội thất phù hợp trẻ nhỏ.
                - KHÔNG viết: du lịch/hoạt động ngoài nhà (→ Du lịch gia đình), review sản phẩm chi tiết theo tên (→ Đánh giá sản phẩm).
                - Giải đúng bài toán KHÔNG GIAN NHỎ (chung cư/nhà phố 50-100m2) — không copy nội dung Tây (nhà rộng, sân vườn).
                - An toàn trong nhà viết theo ĐÚNG MỐC PHÁT TRIỂN — 6 tháng khác hẳn 3 tuổi, không dùng 1 checklist chung.
                - KPI: đo bằng tỷ lệ lưu lại các checklist an toàn theo tuổi con và tỷ lệ dùng lại nhiều lần.
                TEXT,

            'core_focus' => <<<'TEXT'
                Tổ chức không gian sống và vận hành việc nhà cho gia đình có con nhỏ, đặc biệt trong điều kiện nhà chung cư hoặc nhà phố diện tích hạn chế phổ biến ở đô thị Việt Nam: an toàn trong nhà theo từng giai đoạn phát triển của con (khóa tủ thuốc - ổ điện khi con biết bò - biết trèo, chặn cầu thang, bo góc bàn), tổ chức góc chơi - góc học riêng cho con trong không gian nhỏ, sắp xếp việc nhà hiệu quả khi có con mọn để không chiếm hết thời gian dành cho con, và chọn đồ nội thất - thiết bị gia đình phù hợp có trẻ nhỏ (bàn ghế bo góc, vật liệu an toàn, thiết bị tiết kiệm thời gian). KHÔNG lấn sân: du lịch và hoạt động ngoài nhà (thuộc Du lịch gia đình); đánh giá sản phẩm cụ thể chi tiết (thuộc Đánh giá sản phẩm) — ở đây chỉ nêu tiêu chí chọn, không review sản phẩm theo tên.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Tuần trước con chỉ biết bò quanh sàn, tuần này đã bắt gặp con đứng chênh vênh trên mặt bàn sau khi trèo qua hai chiếc ghế xếp cạnh nhau — ngôi nhà cần được nhìn lại từ góc "con có thể trèo tới đâu", không phải góc "đẹp". Nội dung "tổ chức nhà cửa có con nhỏ" tiếng Việt phần lớn dịch từ nguồn Tây (nhà rộng, sân vườn) hoặc thiên về thẩm mỹ hơn an toàn. (1) Mỗi mốc phát triển của con có một checklist an toàn RIÊNG, đổi theo tháng chứ không cố định — biết bò (6-9 tháng): chặn ổ điện, cất phích nước; biết trèo (10-18 tháng): bo góc bàn, rào ban công; biết mở khóa (2-3 tuổi): đổi vị trí tủ thuốc, khóa cửa ra vào; (2) Giải pháp không gian tính đúng cho căn hộ 50-100m2 — góc chơi gọn trong một góc phòng khách chung, đồ nội thất chuyển đổi được theo tuổi con — không copy ảnh nhà rộng có sân vườn kiểu Pinterest; (3) Chia việc nhà theo CA cụ thể (sáng/tối, ai làm gì cố định) thay vì "ai rảnh thì làm" — cách duy nhất thực sự giảm được cảm giác bất công đang âm thầm phá hôn nhân có con nhỏ.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn "cách chống trẻ leo trèo trong nhà", "khóa an toàn ổ điện cho bé", "sắp xếp góc chơi cho con trong nhà nhỏ", "mẹo làm việc nhà nhanh khi có con nhỏ". (2) Là chuyên mục thực dụng gắn liền đời sống hằng ngày — đo bằng tỷ lệ lưu lại và tần suất dùng lại các checklist an toàn theo tuổi con. (3) Liên kết chéo với Hôn nhân (chia sẻ việc nhà công bằng), Du lịch gia đình, và các danh mục an toàn theo tuổi con ở từng giai đoạn phát triển.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con mới biết bò đã trèo được lên ghế sofa, cả nhà giờ như bãi chiến trường phải dọn liên tục"; "Nhà chung cư 65m2, ba người, không biết đặt góc chơi cho con ở đâu ngoài giữa phòng khách"; "Ổ điện thấp ngang tầm con, mua ổ khóa về mà con vẫn gỡ được"; "Đi làm về là ngập trong việc nhà, không còn sức chơi với con nữa"; "Sắm bàn học cho con mà 2 năm sau con lớn lại phải mua bàn khác, tốn kém"; "Ông bà ở cùng muốn giữ đồ đạc như cũ, mình muốn dọn bớt cho an toàn mà ngại nói". Nền chung: nhà ở đô thị Việt Nam đa phần diện tích hạn chế, và an toàn - tổ chức không gian cho con là bài toán phải giải lại liên tục theo từng giai đoạn lớn lên của con.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Thiết kế nội thất phòng trẻ em phong cách sang trọng" — thiên về thẩm mỹ và marketing nội thất cao cấp, không phù hợp đối tượng độc giả và không đúng trọng tâm an toàn - thực dụng của chuyên mục. "Dọn nhà theo phương pháp KonMari/tối giản" nguyên bản — phương pháp gốc không tính đến việc nhà có trẻ nhỏ liên tục tạo ra đồ đạc mới; chỉ áp dụng khi biến tấu phù hợp gia đình có con. "Review đồ nội thất theo thương hiệu cụ thể" — thuộc Đánh giá sản phẩm, tránh trùng phạm vi. "DIY trang trí nhà cầu kỳ tốn nhiều thời gian" — không thực tế với cha mẹ có con nhỏ vốn đã thiếu thời gian.
                TEXT,

            'audience' => 'Cha mẹ Việt 27-40 tuổi sống tại chung cư hoặc nhà phố đô thị diện tích 50-100m2, có 1-2 con từ sơ sinh đến tiểu học, cả hai đi làm nên thời gian dọn dẹp - tổ chức nhà cửa hạn chế; đọc khi vừa gặp sự cố an toàn trong nhà hoặc khi con bước sang giai đoạn phát triển mới đòi hỏi sắp xếp lại không gian.',

            'constraints' => 'Giải pháp phải khả thi với diện tích nhà nhỏ và ngân sách trung bình; an toàn trong nhà phải phân theo đúng độ tuổi/giai đoạn phát triển; không PR nội thất cao cấp hay một thương hiệu cụ thể; thừa nhận việc nhà là gánh nặng thật, không lý tưởng hóa "nhà cửa gọn gàng" như hình mẫu mạng xã hội.',

            'style_sample' => <<<'TEXT'
                Tuần trước con bạn còn chỉ biết bò quanh sàn nhà, tuần này bạn đã bắt gặp con đứng chênh vênh trên mặt bàn uống nước sau khi trèo qua hai chiếc ghế xếp cạnh nhau. Đây chính là lúc ngôi nhà của bạn cần được nhìn lại từ một góc hoàn toàn khác — không phải góc "đẹp" mà góc "con có thể trèo tới đâu". Tin vui là bạn không cần cải tạo lại cả căn hộ; chỉ cần rà một lượt theo đúng những điểm mà trẻ ở giai đoạn biết trèo thường nhắm tới, hầu hết đều xử lý được trong một buổi cuối tuần với chi phí không đáng kể. Trong bài này, chúng ta sẽ đi qua danh sách rà soát an toàn dành riêng cho giai đoạn con vừa biết trèo - biết leo, cách chọn vị trí đặt đồ nội thất để hạn chế "bậc thang" vô tình cho con, và một mẹo nhỏ giúp việc dọn dẹp hằng ngày đỡ ngốn thời gian hơn để bạn còn sức ngồi chơi cùng con.
                TEXT,
        ],

        // === Tài chính gia đình ===
        [
            'parent_slug' => null,
            'slug'        => 'tai-chinh-gia-dinh',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: ngân sách gia đình có con, quỹ dự phòng, bảo hiểm nhân thọ/sức khỏe, tiết kiệm học đại học, dạy con quản lý tiền.
                - KHÔNG viết: chế độ BHXH/thai sản theo luật (→ Quyền lợi & pháp lý), tư vấn đầu tư chứng khoán/BĐS rủi ro cao.
                - Mọi con số tính theo MẶT BẰNG THU NHẬP VIỆT NAM — không dùng ví dụ kiểu Mỹ không áp dụng được.
                - Chỉ tư vấn nền tảng AN TOÀN (quỹ dự phòng, bảo hiểm, tiết kiệm kỷ luật) — không tư vấn làm giàu nhanh/rủi ro cao.
                - KPI: đo bằng tỷ lệ mở rộng thành công của công thức 5 bài đã xuất bản sang chủ đề tài chính mới, và CTR sang Quyền lợi & pháp lý.
                TEXT,

            'core_focus' => <<<'TEXT'
                Hoạch định tài chính thực tế cho gia đình Việt sau khi có con — giai đoạn chi phí tăng vọt trong khi thu nhập không tăng ngay: xây ngân sách chi tiêu hằng tháng cho gia đình có con (cân đối giữa nhu cầu của con và các khoản cố định), lập quỹ dự phòng khẩn cấp bắt đầu từ đâu khi thu nhập eo hẹp, đánh giá có cần bảo hiểm nhân thọ - bảo hiểm sức khỏe cho gia đình trẻ không và chọn loại nào phù hợp, tiết kiệm dài hạn cho việc học của con từ mầm non đến đại học ngay từ sớm, và dạy con quản lý tiền tiêu vặt để hình thành thói quen tài chính tốt từ nhỏ. KHÔNG lấn sân: quyền lợi bảo hiểm xã hội - chế độ thai sản theo luật (thuộc Quyền lợi & pháp lý); tư vấn đầu tư chứng khoán - bất động sản phức tạp, ngoài phạm vi biên tập vì rủi ro cao.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Cuối tháng, mở app ngân hàng kiểm tra số dư và thấy một con số quen thuộc đến nản lòng: gần như bằng không, dù cả hai vợ chồng đều đi làm đều đặn — không phải vì thiếu kỷ luật, mà vì thiếu một hệ thống. Nội dung tài chính cá nhân tiếng Việt hoặc quá chung chung ("hãy tiết kiệm 20% thu nhập") hoặc quá xa vời (ví dụ tính bằng USD, thu nhập nghìn đô). (1) Ngân sách mẫu chia đúng theo mốc thu nhập gia đình trẻ Việt Nam thật (15-25 triệu / 25-40 triệu/tháng) — trả lời thẳng "lương tầm này thì quỹ dự phòng nên bắt đầu từ con số nào" bằng một con số cụ thể, không phải công thức "6 tháng chi tiêu" nghe xa vời; (2) Chỉ tư vấn nền tảng AN TOÀN theo đúng thứ tự ưu tiên — quỹ dự phòng trước, bảo hiểm sau, tiết kiệm dài hạn cuối — không đẩy độc giả vào chứng khoán/tiền ảo dù đang là trend, vì đối tượng ưu tiên an toàn cho con hơn làm giàu nhanh; (3) "Dạy con về tiền tiêu vặt" viết thành trụ cột riêng có mốc tuổi và con số cụ thể (bao nhiêu ở tuổi nào) — điều hiếm nơi nào trả lời rõ ràng thay vì chỉ nói chung chung "dạy con biết tiết kiệm".
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn "lập ngân sách gia đình 4 người", "quỹ dự phòng gia đình bao nhiêu là đủ", "có nên mua bảo hiểm nhân thọ cho con", "tiết kiệm cho con học đại học từ khi nào". (2) Duy trì đúng tông của 5 bài đã xuất bản (quỹ dự phòng, dạy con quản lý tiền tiêu vặt, ngân sách chi tiêu gia đình 4 người, bảo hiểm nhân thọ, tiết kiệm học đại học) — thực dụng, có mốc và con số cụ thể — đo bằng tỷ lệ mở rộng thành công sang chủ đề mới theo đúng khuôn mẫu. (3) Liên kết chéo với Quyền lợi & pháp lý (chế độ BHXH) và Du lịch gia đình (lập ngân sách chuyến đi).
                TEXT,

            'pain_points' => <<<'TEXT'
                "Lương hai vợ chồng cộng lại 25 triệu, có con rồi mà tháng nào cũng hết sạch, không biết tiền chảy đi đâu"; "Nghe nói nên có quỹ dự phòng bằng 6 tháng chi tiêu, nhìn số tiền mà thấy xa vời quá không biết bắt đầu từ đâu"; "Được tư vấn bảo hiểm nhân thọ mà không hiểu hết các điều khoản, sợ mua nhầm gói không cần thiết"; "Muốn để dành cho con học đại học sau này mà lương tháng nào tiêu hết tháng đó, chưa để dành được đồng nào"; "Con 6 tuổi đòi tiền tiêu vặt như bạn cùng lớp, không biết cho bao nhiêu là hợp lý và dạy thế nào để con không tiêu hoang"; "Hai vợ chồng có hai quan điểm chi tiêu khác nhau, một người tiết kiệm một người thoải mái, hay cãi nhau vì tiền". Nền chung: chi phí nuôi con tăng nhanh và kéo dài nhiều năm, trong khi phần lớn gia đình trẻ Việt Nam chưa từng được dạy cách lập kế hoạch tài chính bài bản.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Đầu tư chứng khoán/tiền ảo để làm giàu nhanh nuôi con" — rủi ro cao, ngoài phạm vi biên tập, có thể gây thiệt hại tài chính nghiêm trọng cho gia đình; tuyệt đối không viết dạng khuyến khích. "So sánh chi tiết các gói bảo hiểm nhân thọ theo tên công ty cụ thể" — dễ thành bài PR/affiliate bảo hiểm; chỉ viết nguyên tắc chọn chung. "Làm giàu từ kinh doanh online/đa cấp trong lúc ở nhà chăm con" — rủi ro lừa đảo cao với đối tượng cha mẹ đang cần tiền gấp, không phù hợp đạo đức biên tập. "Cắt giảm chi tiêu cực đoan kiểu nhịn ăn để tiết kiệm" — không bền vững và ảnh hưởng dinh dưỡng gia đình; thay bằng tối ưu chi tiêu hợp lý.
                TEXT,

            'audience' => 'Vợ chồng Việt 27-40 tuổi thu nhập trung bình - khá ở đô thị (tổng thu nhập gia đình khoảng 15-40 triệu/tháng), có 1-2 con từ sơ sinh đến tiểu học, mới bắt đầu cảm nhận áp lực chi phí nuôi con tăng vọt và muốn có kế hoạch tài chính rõ ràng hơn thay vì chi tiêu theo cảm tính.',

            'constraints' => 'Mọi con số và ví dụ phải khớp mặt bằng thu nhập - chi phí Việt Nam thực tế; không tư vấn đầu tư rủi ro cao (chứng khoán, tiền ảo, đa cấp); không PR sản phẩm bảo hiểm hay ngân hàng cụ thể; giữ tông thực dụng có mốc/con số rõ ràng như 5 bài đã xuất bản; không phán xét cách chi tiêu của độc giả.',

            'style_sample' => <<<'TEXT'
                Cuối tháng, bạn mở app ngân hàng kiểm tra số dư và thấy một con số quen thuộc đến nản lòng: gần như bằng không, dù cả hai vợ chồng đều đi làm đều đặn suốt tháng. Nếu đây là cảm giác quen thuộc, bạn không thiếu kỷ luật — bạn chỉ đang thiếu một hệ thống. Từ ngày có con, danh sách chi tiêu của một gia đình phình to nhanh hơn bất kỳ giai đoạn nào khác trong đời: bỉm sữa, học phí, khám sức khỏe định kỳ, đồ dùng thay mới liên tục theo từng giai đoạn lớn của con — tất cả cộng dồn âm thầm mà không ai kịp nhận ra. Trong bài này, chúng ta sẽ dựng một bản ngân sách rất cụ thể cho gia đình 4 người ở mức thu nhập phổ biến của các gia đình trẻ thành thị, xem quỹ dự phòng nên bắt đầu từ con số nào chứ không phải con số lý tưởng xa vời, và một câu hỏi quan trọng bạn nên tự trả lời trước khi nghe bất kỳ ai tư vấn bảo hiểm: gia đình mình thực sự cần bảo vệ trước rủi ro nào trước tiên.
                TEXT,
        ],

        // === Quyền lợi & pháp lý ===
        [
            'parent_slug' => null,
            'slug'        => 'quyen-loi-phap-ly',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: chế độ thai sản/nghỉ con ốm, đăng ký khai sinh/BHYT, quyền nuôi con khi ly hôn, chính sách hỗ trợ trẻ em.
                - KHÔNG viết: tài chính gia đình nói chung (→ Tài chính gia đình), tư vấn pháp lý cá nhân hóa từng trường hợp cụ thể.
                - Dịch ngôn ngữ luật sang đời thường có VÍ DỤ CỤ THỂ (số ngày, số tiền) — không trích nguyên văn điều luật khô khan.
                - Mọi thông tin luật phải dẫn đúng căn cứ (tên luật/nghị định, số điều) và cập nhật văn bản hiện hành — đây là chuyên mục duy nhất viết luật nên phải chuẩn xác tuyệt đối.
                - KPI: đo bằng độ chính xác/không có khiếu nại sai luật, và CTR từ các danh mục theo tuổi con khi có phần thủ tục hành chính liên quan.
                TEXT,

            'core_focus' => <<<'TEXT'
                Phổ biến quyền lợi và quy định pháp luật liên quan trực tiếp đến cha mẹ và con cái tại Việt Nam, viết dễ hiểu cho người không có nền tảng luật: chế độ thai sản và nghỉ chăm con ốm theo Luật Bảo hiểm xã hội - Luật Lao động (thời gian nghỉ, mức hưởng, thủ tục), đăng ký khai sinh và các giấy tờ cần thiết cho con (giấy khai sinh, nhập hộ khẩu, thẻ bảo hiểm y tế cho trẻ), quyền nuôi con và cấp dưỡng khi ly hôn, các chính sách hỗ trợ trẻ em của nhà nước (tiêm chủng miễn phí, hỗ trợ học phí). KHÔNG lấn sân: tài chính gia đình nói chung (thuộc Tài chính gia đình); tư vấn pháp lý cá nhân hóa cho từng trường hợp cụ thể — chỉ phổ biến quy định chung, luôn khuyến khích gặp luật sư hoặc cơ quan bảo hiểm xã hội khi cần áp dụng cụ thể.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Bụng đã to, ngày dự sinh gần kề, nhưng câu hỏi khiến mất ngủ không kém gì chuyện chuẩn bị đồ đi sinh lại là: nghỉ thai sản xong, lương sẽ ra sao — mà hỏi phòng nhân sự thì ngại, sợ ảnh hưởng đánh giá công việc. (1) Dịch ngôn ngữ luật sang ngôn ngữ đời thường có ví dụ cụ thể (số ngày nghỉ, số tiền hưởng theo lương cơ sở) thay vì trích nguyên văn điều luật khô khan. (2) Luôn cập nhật đúng văn bản pháp luật hiện hành và ghi rõ căn cứ — tên luật/nghị định, số điều — để độc giả có thể tự tra cứu hoặc dùng làm căn cứ khi làm việc với công ty hay cơ quan nhà nước. (3) Chủ động viết cả các tình huống nhạy cảm ít ai hỏi công khai (quyền lợi khi mang thai hộ, quyền nuôi con khi ly hôn, quyền lợi của mẹ đơn thân) với thái độ trung lập, không phán xét hoàn cảnh gia đình.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn "chế độ thai sản nghỉ bao nhiêu ngày", "thủ tục làm giấy khai sinh cho con cần gì", "nghỉ con ốm có được hưởng lương không", "quyền nuôi con khi ly hôn". (2) Xây uy tín "có căn cứ pháp lý rõ ràng" cho toàn site — chuyên mục duy nhất viết về luật nên phải chuẩn xác tuyệt đối. (3) Liên kết chéo với hầu hết các danh mục theo tuổi con mỗi khi có phần thủ tục hành chính liên quan (đăng ký khai sinh khi mới sinh, chế độ nghỉ khi con ốm ở các độ tuổi) — đo bằng CTR từ các danh mục đó sang đây.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Sắp sinh mà không rõ nghỉ thai sản được bao nhiêu ngày, công ty tính lương thế nào trong thời gian nghỉ"; "Con ốm phải nghỉ trông, không biết luật có cho nghỉ hưởng lương không hay phải nghỉ không lương"; "Mới sinh con xong, chưa biết làm giấy khai sinh cần giấy tờ gì, nộp ở đâu, bao lâu thì xong"; "Ly hôn mà có con nhỏ, không biết quyền nuôi con được xác định thế nào, cấp dưỡng bao nhiêu là hợp lý"; "Công ty nói không có quy định nghỉ chăm con ốm, không biết công ty nói đúng luật hay đang lách"; "Nghe nói có hỗ trợ học phí, tiêm chủng miễn phí cho trẻ mà không biết đăng ký ở đâu". Nền chung: cha mẹ đi làm biết mình có quyền lợi nhưng không nắm rõ đủ để đòi hỏi đúng, và thường ngại hỏi thẳng công ty vì sợ ảnh hưởng đến đánh giá công việc.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Tư vấn pháp lý cho trường hợp cụ thể của từng độc giả" dạng hỏi-đáp cá nhân — vượt phạm vi biên tập, rủi ro tư vấn sai gây hậu quả pháp lý thật; chỉ phổ biến quy định chung và hướng dẫn tìm luật sư hoặc cơ quan có thẩm quyền. "Mẹo lách luật để hưởng chế độ" (gian lận bảo hiểm, khai gian giấy tờ) — vi phạm đạo đức và pháp luật, tuyệt đối không viết. "Phân tích án lệ ly hôn phức tạp" mang tính học thuật luật — quá chuyên sâu, không đúng đối tượng đại chúng của site. "Tổng hợp toàn bộ Luật Lao động/Bảo hiểm xã hội" dạng liệt kê hết mọi điều — không có giá trị hành động; chỉ viết theo đúng tình huống cụ thể của độc giả (đang mang thai, con ốm, ly hôn).
                TEXT,

            'audience' => 'Cha mẹ Việt 25-40 tuổi đang đi làm tại công ty hoặc cơ quan nhà nước, sắp hoặc vừa có con, cần biết quyền lợi luật định cụ thể về thai sản, nghỉ chăm con ốm, giấy tờ cho con nhưng ngại hỏi trực tiếp phòng nhân sự vì sợ ảnh hưởng đến đánh giá công việc hoặc không biết hỏi ai.',

            'constraints' => 'Mọi thông tin luật phải dẫn đúng căn cứ (tên luật/nghị định, số điều) và cập nhật văn bản hiện hành; không tư vấn pháp lý cá nhân hóa cho tình huống cụ thể — chỉ phổ biến quy định chung, luôn khuyến khích gặp luật sư hoặc cơ quan bảo hiểm xã hội/tư pháp khi cần áp dụng cụ thể; không hướng dẫn lách luật; giữ thái độ trung lập, không phán xét hoàn cảnh gia đình như ly hôn hay làm mẹ đơn thân.',

            'style_sample' => <<<'TEXT'
                Bụng đã to, ngày dự sinh gần kề, nhưng có một câu hỏi khiến bạn mất ngủ không kém gì việc chuẩn bị đồ đi sinh: nghỉ thai sản xong, lương của mình sẽ ra sao? Đây là câu hỏi hoàn toàn chính đáng, và câu trả lời không hề mơ hồ như nhiều người vẫn tưởng — pháp luật Việt Nam quy định rất rõ ràng về thời gian nghỉ và mức hưởng, chỉ là ít công ty chủ động giải thích đầy đủ cho người lao động. Trong bài này, chúng ta sẽ đi thẳng vào những con số cụ thể: bạn được nghỉ bao nhiêu tháng, trong thời gian đó khoản tiền bạn nhận đến từ đâu và tính theo công thức nào, hồ sơ cần chuẩn bị là gì, và một điều nhiều người bỏ sót — quyền lợi của người chồng cũng được nghỉ vài ngày khi vợ sinh con. Không cần phải là dân luật mới hiểu được những điều này, và bạn hoàn toàn có quyền yêu cầu công ty thực hiện đúng.
                TEXT,
        ],

        // === Trường mầm non & tiểu học ===
        [
            'parent_slug' => null,
            'slug'        => 'truong-mam-non-tieu-hoc',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: khung tiêu chí chọn trường (ngân sách, 3 loại hình, quy trình tuyển sinh, checklist tham quan) — KHÔNG xếp hạng trường cụ thể.
                - KHÔNG viết: đánh giá/xếp hạng nêu tên một trường cụ thể (rủi ro PR hoặc bôi nhọ).
                - Luôn quy về NGÂN SÁCH THỰC của gia đình TRƯỚC khi nói triết lý giáo dục — nhiều bài khác bỏ qua bước này.
                - Thẳng thắn: KHÔNG có trường nào hoàn hảo, một số con sẽ cần đổi trường giữa chừng — đây là chuyện bình thường, không phải "chọn sai".
                - Dạy "đọc" buổi tham quan trường thay vì tin brochure (giờ đón trả thực tế, tỷ lệ giáo viên nghỉ việc).
                TEXT,

            'core_focus' => <<<'TEXT'
                Khung tiêu chí chọn trường cho con từ mầm non đến hết tiểu học — quyết định lớn nhất, tốn kém nhất trong 6 năm đầu đời con mà cha mẹ Việt phải đưa ra: xác định ngân sách thực tế theo 3 tuyến (công lập gần như miễn phí, tư thục 3-15 triệu/tháng, quốc tế/song ngữ 15-50+ triệu/tháng), hiểu sự khác biệt thật giữa 3 loại hình (chương trình học, sĩ số lớp, triết lý giáo dục, mức độ can thiệp của phụ huynh được chấp nhận), quy trình tuyển sinh và thời điểm nộp hồ sơ theo lịch năm học (đặc biệt trường tư/quốc tế thường tuyển trước cả năm), danh sách câu hỏi nên hỏi và dấu hiệu cần quan sát khi đi thăm trường thực tế (bữa ăn, sĩ số thực tế so với quảng cáo, thái độ giáo viên với trẻ khi không có phụ huynh theo dõi), và cách xử lý khi con không thích nghi được với trường đã chọn. Định dạng chủ lực: bài khung tiêu chí + checklist tham quan trường + bài giải đáp tình huống chuyển trường. KHÔNG lấn sân: không đánh giá, xếp hạng hay nêu tên một trường cụ thể nào (tránh trở thành bài PR hoặc bôi nhọ trường), chỉ cung cấp khung tiêu chí và quy trình chung để cha mẹ tự áp dụng vào trường họ đang cân nhắc.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Phần lớn nội dung "chọn trường" tiếng Việt hiện nay là 2 loại: bài PR trả tiền của chính trường tư/quốc tế (viết như quảng cáo, chỉ nói ưu điểm) hoặc bài xếp hạng "Top 10 trường tốt nhất" thiếu tiêu chí rõ ràng và dễ lỗi thời khi trường đổi ban giám hiệu/chương trình. Chuyên mục này khác ở 3 điểm: (1) Luôn quy mọi lựa chọn về NGÂN SÁCH THỰC của gia đình trước khi nói tới triết lý giáo dục — nhiều bài viết bỏ qua bước này khiến cha mẹ mơ mộng về trường vượt quá khả năng chi trả dài hạn 6-12 năm; (2) Dạy cách "đọc" một buổi tham quan trường thay vì tin vào brochure — chỉ ra chính xác nên nhìn gì (giờ đón trả trẻ thực tế, tỷ lệ giáo viên nghỉ việc, sân chơi có được dùng thật không hay chỉ để chụp ảnh); (3) Thẳng thắn về việc KHÔNG có trường nào hoàn hảo và một số con sẽ cần đổi trường giữa chừng — đây là thất bại rất thường gặp nhưng gần như không ai viết, cha mẹ tự trách bản thân "chọn sai" trong khi đó là chuyện bình thường.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn quyết định lớn, tần suất tìm theo mùa tuyển sinh (tháng 3-6 hằng năm): "nên cho con học trường công hay tư", "tiêu chí chọn trường mầm non cho con", "hồ sơ tuyển sinh lớp 1 cần gì", "học phí trường quốc tế TP.HCM/Hà Nội bao nhiêu". (2) Trở thành điểm tựa quyết định trước các mốc chuyển cấp lớn nhất (vào mầm non, vào lớp 1) — đo bằng thời gian đọc và tỷ lệ lưu bài. (3) Liên kết chặt với "Trường năng khiếu & kỹ năng" và "Trung tâm học tập" (khi trường chính không đáp ứng đủ) và với các danh mục nuôi dạy theo tuổi (Trẻ mầm non, Trẻ tiểu học) để giữ độc giả xuyên suốt hành trình giáo dục con. (4) Xây uy tín "không PR trường nào" để được tin cậy hơn các hội nhóm review trường vốn đầy ý kiến trái chiều thiếu hệ thống.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Lương hai vợ chồng 25 triệu/tháng, trường tư gần nhà học phí 8 triệu — có nên cố không hay học công lập thiệt thòi con?"; "Đi xem 3 trường mầm non tư mà trường nào cũng nói hay như nhau, không biết dựa vào đâu để chọn"; "Trường quốc tế quảng cáo học theo Cambridge, sĩ số 15 học sinh/lớp — thực tế có đúng không hay chỉ là con số trên giấy?"; "Nộp hồ sơ lớp 1 trường tư tháng nào, cần giấy tờ gì, có phải xếp hàng từ nửa đêm không?"; "Con học trường công lớp 45 học sinh, cô không thể sát sao — có nên chuyển sang tư dù tốn kém hơn?"; "Cho con học trường quốc tế từ nhỏ, giờ tiếng Việt của con kém hơn bạn bè — có phải sai lầm không?"; "Đã đóng học phí cả năm ở trường tư mà con không thích nghi, khóc mỗi sáng đi học — có nên rút giữa chừng, tiền đã đóng mất luôn không?"; "Ông bà chê trường tư 'chỉ chơi không học', trong khi mình thấy con vui vẻ hơn hẳn — ai đúng?". Nền chung: cha mẹ phải quyết định trong khi thông tin thật (không phải quảng cáo) về từng trường gần như không có, và mỗi lựa chọn đều ràng buộc tài chính - thời gian nhiều năm khó đảo ngược.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Top 10 trường mầm non/tiểu học tốt nhất Hà Nội/TP.HCM" nêu tên cụ thể — rủi ro trở thành bài PR trá hình hoặc bị khiếu nại từ trường không được xếp hạng cao, không kiểm chứng được khách quan; thay bằng khung tiêu chí để cha mẹ tự đánh giá bất kỳ trường nào. "Review chi tiết trường X có tốt không" theo yêu cầu bạn đọc về 1 trường cụ thể — cùng lý do trên, từ chối viết dạng bài, có thể trả lời chung chung trong phần hỏi đáp. "So sánh học phí toàn bộ hệ thống trường quốc tế tại Việt Nam" dạng bảng liệt kê — dữ liệu học phí thay đổi liên tục theo năm học, nhanh lỗi thời, dễ sai lệch gây khiếu nại; chỉ nêu khoảng ngân sách tham khảo kèm lưu ý luôn xác minh trực tiếp. "Nên chọn trường theo phong thủy/tuổi hợp mệnh" — mê tín, không có cơ sở, không phù hợp định vị khung tiêu chí thực dụng của chuyên mục. "Trường công lập là lựa chọn kém, nên cố hết sức cho con học tư" định kiến một chiều — nhiều trường công vẫn rất tốt tùy khu vực/giáo viên, đi ngược tinh thần trung lập theo hoàn cảnh từng gia đình.
                TEXT,

            'audience' => 'Cha mẹ Việt 28-42 tuổi có con chuẩn bị vào mầm non (2-3 tuổi) hoặc chuẩn bị vào lớp 1 (5-6 tuổi) — hai mốc quyết định lớn nhất; sống thành thị, thu nhập trung bình đến khá, đang cân nhắc nghiêm túc giữa nhiều loại hình trường trong bán kính di chuyển được; đọc kỹ, so sánh nhiều nguồn, thường đọc trong 1-3 tháng trước mùa tuyển sinh (tháng 3-6).',

            'constraints' => 'Tuyệt đối không nêu tên, xếp hạng hay so sánh trực tiếp một trường cụ thể nào; không PR hay nhận tài trợ nội dung từ trường/hệ thống giáo dục; số liệu học phí chỉ nêu dạng khoảng tham khảo kèm ghi chú "cần xác minh trực tiếp vì thay đổi theo năm"; không định kiến công lập kém hơn tư thục hay ngược lại; tôn trọng giới hạn ngân sách của mọi gia đình, không cổ vũ vay nợ để học trường đắt tiền.',

            'style_sample' => <<<'TEXT'
                Bạn vừa rời buổi tham quan trường tư thứ ba trong tuần, đầu óc quay cuồng với những con số học phí và những lời giới thiệu nghe trường nào cũng "tốt nhất cho con". Trước khi xem thêm một trường nào nữa, hãy dừng lại 5 phút làm một việc ít ai làm đầu tiên: mở bảng chi tiêu gia đình ra và tính xem, với mức học phí đang cân nhắc, bạn có thể duy trì được bao lâu — 1 năm, hay suốt 5 năm tiểu học mà không phải rút con giữa chừng vì hụt tài chính. Bởi câu hỏi quan trọng nhất khi chọn trường không phải là "trường nào tốt nhất" mà là "trường nào tốt nhất mà gia đình mình duy trì được ổn định lâu dài" — một đứa trẻ bị chuyển trường giữa chừng vì bố mẹ đuối tài chính thường tổn thương nhiều hơn một đứa trẻ học trường "kém danh tiếng" hơn nhưng ổn định. Trong bài này, mình sẽ cùng bạn đi qua khung 4 bước để so sánh các lựa chọn đang cân nhắc một cách công bằng — từ ngân sách, triết lý giáo dục, đến những dấu hiệu bạn nên quan sát kỹ khi bước qua cổng trường mà không có hướng dẫn viên đi cùng.
                TEXT,
        ],

        // === Trường năng khiếu & kỹ năng ===
        [
            'parent_slug' => null,
            'slug'        => 'truong-nang-khieu-ky-nang',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: nhận biết năng khiếu thật vs hứng thú nhất thời, tiêu chí chọn lớp/trung tâm, thử môn mới không tốn kém, dừng môn không phù hợp.
                - KHÔNG viết: học thêm môn văn hóa (→ Trung tâm học tập), hành vi/kỷ luật hằng ngày (→ Chăm sóc & nuôi dạy).
                - Dạy phân biệt "hứng thú nhất thời" vs "năng khiếu thật" bằng dấu hiệu quan sát TẠI NHÀ — không cần test chuyên môn tốn tiền.
                - Bình thường hóa việc con thử — bỏ — thử môn khác, không coi là lãng phí hay "cả thèm chóng chán".
                - KPI: đo bằng mức giảm cảm giác tội lỗi/áp lực khi so sánh lịch ngoại khóa (khảo sát/bình luận) và tỷ lệ CTR sang Trung tâm học tập.
                TEXT,

            'core_focus' => <<<'TEXT'
                Giúp cha mẹ chọn đúng hoạt động ngoại khóa/năng khiếu cho con thay vì chạy theo phong trào của bạn bè, đám đông mạng xã hội: cách nhận biết dấu hiệu năng khiếu thật của con theo từng độ tuổi (3-6 tuổi: quan sát qua trò chơi tự do con chọn lặp lại; 6-12 tuổi: qua mức độ kiên trì khi gặp khó chứ không chỉ hứng thú ban đầu), tiêu chí chọn lớp/trung tâm năng khiếu cụ thể (bơi, đàn, vẽ, võ, bóng đá, tiếng Anh, cờ vua...) theo an toàn - chất lượng giáo viên - lộ trình rõ ràng thay vì chọn theo quảng cáo hay "cả lớp đi học", cách thử một môn mới mà không tốn kém (lớp trải nghiệm, mượn dụng cụ trước khi mua), dấu hiệu nên dừng một môn con không phù hợp mà không tạo cảm giác thất bại cho con, và cân bằng số lượng hoạt động ngoại khóa để con vẫn có thời gian chơi tự do - nghỉ ngơi. KHÔNG lấn sân: không viết về học thêm các môn văn hóa (Toán, tiếng Việt, tiếng Anh học thuật — thuộc Trung tâm học tập), không viết hành vi/kỷ luật hằng ngày (thuộc các danh mục Chăm sóc & nuôi dạy theo tuổi).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung năng khiếu hiện nay chủ yếu là quảng cáo của chính các trung tâm (mỗi trung tâm nói môn của mình "giúp con phát triển toàn diện") hoặc các bài liệt kê chung chung "10 lợi ích của việc học đàn". Chuyên mục này khác ở 3 điểm: (1) Dạy phân biệt "hứng thú nhất thời" và "năng khiếu thật" bằng dấu hiệu quan sát cụ thể tại nhà, không cần bài test chuyên môn tốn tiền; (2) Nói thẳng về áp lực đám đông — khi cả lớp con đi học piano/tiếng Anh, cha mẹ dễ cho con học theo vì sợ con "thua kém" chứ không phải vì con thích, bài viết giúp cha mẹ tách bạch hai động cơ này; (3) Chấp nhận và bình thường hóa việc con thử — bỏ — thử môn khác, thay vì coi đó là lãng phí tiền hay con "cả thèm chóng chán", kèm cách nói chuyện với con khi muốn dừng một môn mà không làm con thấy mình thất bại.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho truy vấn theo môn cụ thể và theo độ tuổi: "con mấy tuổi học bơi được", "có nên cho con học piano từ nhỏ", "dấu hiệu trẻ có năng khiếu hội họa", "con học được 2 tháng đòi nghỉ có nên ép học tiếp". (2) Giảm cảm giác tội lỗi/áp lực của cha mẹ khi so sánh lịch học ngoại khóa của con với con nhà khác — đo bằng phản hồi định tính (bình luận, khảo sát), xây niềm tin rằng ít hoạt động nhưng đúng còn hơn nhiều hoạt động theo phong trào. (3) Liên kết chéo với Trung tâm học tập (khi nhu cầu chuyển từ năng khiếu sang học thuật) và với các danh mục Phát triển của trẻ theo từng giai đoạn tuổi.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Bạn cùng lớp con đứa nào cũng học piano, mình cho con học theo mà không biết con có thật sự thích không hay chỉ vì các bạn đi học"; "Cho con thử học vẽ, học được 3 buổi đòi nghỉ — có phải con mình cả thèm chóng chán, hay do chọn sai lớp?"; "Muốn biết con có năng khiếu gì thật sự mà không biết quan sát dấu hiệu nào, có cần cho đi test chuyên môn không?"; "Lịch ngoại khóa của con kín cả tuần: tiếng Anh, bơi, vẽ, đàn — nhìn thì tự hào nhưng thấy con lúc nào cũng mệt, không biết có đang quá tải không"; "Trung tâm quảng cáo giáo viên nước ngoài, học phí cao gấp đôi chỗ khác — có đáng tin không hay chỉ là chiêu marketing?"; "Con thích đá bóng nhưng ông bà bảo học võ để tự vệ, khó tính hơn — chọn theo ý con hay theo người lớn?"; "Cho con nghỉ học đàn giữa chừng, sợ con nghĩ mình bỏ cuộc dễ dàng, không biết nói sao cho đúng". Nền chung: cha mẹ vừa sợ con "kém phát triển toàn diện" nếu không cho học đủ thứ, vừa sợ nhồi nhét quá tải — và hiếm khi có tiêu chí rõ ràng để quyết định.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Top 10 trung tâm năng khiếu tốt nhất" nêu tên cụ thể — dễ thành PR, không kiểm chứng khách quan được, để dành nguyên tắc chọn lựa chung thay vì xếp hạng thương hiệu. "Bài test đo IQ/năng khiếu bẩm sinh cho trẻ" dạng cổ vũ các dịch vụ đo năng khiếu thương mại — thiếu cơ sở khoa học vững chắc ở tuổi quá nhỏ, dễ khiến cha mẹ định hướng con quá sớm và cứng nhắc theo 1 kết quả test; chỉ viết dạng quan sát hành vi tự nhiên. "10 môn năng khiếu con nhất định phải học trước 6 tuổi" dạng liệt kê tạo áp lực chạy đua thành tích — đi ngược tinh thần "ít nhưng đúng" của chuyên mục. "Đầu tư năng khiếu để sau này thành vận động viên/nghệ sĩ chuyên nghiệp" — đặt kỳ vọng quá xa và tạo áp lực thành tích lên trẻ nhỏ, chuyên mục chỉ tập trung năng khiếu như một phần phát triển toàn diện, không phải con đường sự nghiệp định sẵn.
                TEXT,

            'audience' => 'Cha mẹ Việt 28-40 tuổi có con 3-12 tuổi, thu nhập đủ để đầu tư 1-3 hoạt động ngoại khóa/tuần, chịu ảnh hưởng từ việc so sánh với bạn bè và mạng xã hội về hoạt động của con nhà khác; băn khoăn giữa mong muốn con phát triển toàn diện và nỗi lo nhồi nhét quá tải; tìm kiếm khi con sắp bắt đầu năm học mới hoặc khi đang cân nhắc dừng một môn.',

            'constraints' => 'Không cổ vũ chạy đua thành tích hay học theo phong trào; không PR trung tâm/thương hiệu cụ thể; không cổ vũ dịch vụ đo IQ/năng khiếu thương mại thiếu cơ sở; luôn tôn trọng giới hạn thời gian nghỉ ngơi - chơi tự do của trẻ; không tạo cảm giác cha mẹ thất bại nếu con không theo đuổi môn nào lâu dài.',

            'style_sample' => <<<'TEXT'
                Nhóm chat lớp của con đang rôm rả khoe những tấm bằng khen từ cuộc thi piano, còn bạn thì đang tự hỏi có phải mình đang để con "tụt lại" vì chưa cho học một nhạc cụ nào. Trước khi đăng ký ngay lớp piano gần nhà, hãy thử dành một tuần chỉ để QUAN SÁT thay vì hành động: xem con tự chọn chơi gì trong lúc rảnh rỗi không ai ép, con lặp lại hoạt động nào nhiều lần dù không ai nhắc, con kiên trì với thử thách nào lâu hơn bình thường. Năng khiếu thật ở tuổi này thường lộ ra qua những khoảnh khắc rất nhỏ như vậy, chứ không nằm ở việc cả lớp đang học gì. Trong bài này, mình sẽ cùng bạn xây một bộ dấu hiệu đơn giản để nhận ra hướng con đang nghiêng về, cách thử một môn mới mà không tốn kém trước khi đầu tư dài hạn, và — quan trọng không kém — cách nói với con khi muốn dừng một môn mà không làm con cảm thấy mình vừa thất bại.
                TEXT,
        ],

        // === Trung tâm học tập ===
        [
            'parent_slug' => null,
            'slug'        => 'trung-tam-hoc-tap',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: quyết định KHI NÀO con thật sự cần học thêm (vs áp lực so sánh), tiêu chí chọn trung tâm/gia sư, chi phí hợp lý theo thu nhập.
                - KHÔNG viết: năng khiếu/ngoại khóa phi học thuật (→ Trường năng khiếu & kỹ năng), phương pháp tự học tại nhà hằng ngày (→ Chăm sóc & nuôi dạy).
                - Phân biệt rạch ròi "con cần học thêm thật" và "cha mẹ lo vì so sánh" bằng dấu hiệu cụ thể từ bài kiểm tra — không phải cảm tính.
                - Tính chi phí học thêm như khoản chi DÀI HẠN có kế hoạch, đặt trong tổng ngân sách giáo dục — không phải chi tiêu bột phát vì lo âu.
                - KPI: đo bằng mức giảm hoang mang/tội lỗi khi quyết định chi tiền học thêm (khảo sát/bình luận) và CTR sang Tài chính gia đình.
                TEXT,

            'core_focus' => <<<'TEXT'
                Hướng dẫn cha mẹ có con tiểu học/THCS quyết định đúng lúc, đúng chỗ khi cần cho con học thêm ngoài giờ chính khóa: nhận biết khi nào con THẬT SỰ cần hỗ trợ thêm (hổng kiến thức cụ thể, chuẩn bị thi chuyển cấp) so với khi nào là áp lực thành tích ảo từ so sánh với bạn bè, tiêu chí chọn trung tâm/gia sư uy tín (chứng chỉ giáo viên, sĩ số lớp, lộ trình học có đo lường được tiến bộ hay không, chính sách học thử/hoàn tiền), so sánh học nhóm tại trung tâm với gia sư 1-kèm-1 theo ngân sách và đặc điểm con, cách sắp xếp lịch học thêm không chồng chéo gây quá tải (đặc biệt khi con đã có lịch năng khiếu), và chi phí học thêm hợp lý theo thu nhập gia đình để không rơi vào vòng xoáy chi tiêu vượt khả năng. KHÔNG lấn sân: không viết về hoạt động năng khiếu/ngoại khóa phi học thuật (thuộc Trường năng khiếu & kỹ năng), không viết phương pháp dạy con tự học tại nhà hằng ngày (thuộc các danh mục Chăm sóc & nuôi dạy theo tuổi).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Thông tin về trung tâm học thêm tại Việt Nam chủ yếu đến từ 2 nguồn thiên lệch: quảng cáo của chính trung tâm (cam kết đầu ra không kiểm chứng được) và lời truyền miệng cảm tính trong hội phụ huynh ("cô X dạy hay lắm") thiếu tiêu chí khách quan. Chuyên mục này khác ở 3 điểm: (1) Đưa ra bộ câu hỏi cụ thể để cha mẹ tự hỏi trung tâm trước khi đăng ký (thay vì tin quảng cáo) — sĩ số thực tế, cách đo tiến bộ của con, chính sách khi con không theo kịp; (2) Phân biệt rạch ròi "con cần học thêm" và "cha mẹ lo lắng vì so sánh với con nhà khác" bằng dấu hiệu cụ thể từ bài kiểm tra và nhận xét của giáo viên trên lớp, giúp tránh chi tiền học thêm không cần thiết; (3) Tính toán chi phí học thêm như một khoản chi tiêu dài hạn có kế hoạch (không phải chi tiêu bột phát theo cảm xúc lo lắng), đặt trong tổng ngân sách giáo dục của gia đình.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho truy vấn ra quyết định rất thực dụng: "con học kém môn Toán có nên cho học thêm không", "học thêm 1 kèm 1 hay học nhóm tốt hơn", "chi phí học thêm hợp lý là bao nhiêu", "dấu hiệu trung tâm học thêm không uy tín". (2) Giảm cảm giác hoang mang/tội lỗi của cha mẹ khi quyết định có nên chi thêm tiền học cho con hay không — đo bằng phản hồi định tính và tỷ lệ đọc trọn bài trước khi quyết định. (3) Liên kết với Trẻ tiểu học > Chăm sóc & nuôi dạy (áp lực học tập, đồng hành thi cử) và với Tài chính gia đình (lập ngân sách cho khoản chi học thêm).
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con bị điểm kém 2 bài kiểm tra liên tiếp môn Toán, không biết là con đuối tạm thời hay cần học thêm ngay"; "Bạn cùng lớp ai cũng đi học thêm buổi tối, mình sợ không cho con học sẽ tụt lại dù con vẫn theo kịp trên lớp"; "Tìm gia sư trên mạng, không biết dựa vào đâu để tin — bằng cấp thật hay giả, dạy có phương pháp không"; "Chi 3-4 triệu/tháng học thêm 2 môn, không thấy điểm số cải thiện rõ, không biết có nên đổi chỗ học hay đổi cách học"; "Lịch của con: học chính khóa, học thêm Toán, học thêm Anh, học đàn — nhìn lịch mà thấy thương con, không biết cắt bớt cái nào"; "Trung tâm cam kết 'đảm bảo đỗ chuyên' nghe hấp dẫn nhưng không biết có phải chiêu marketing không"; "Con nói không thích học thêm nhưng mình sợ không ép thì con lười, không biết nên tôn trọng ý con hay giữ kỷ luật". Nền chung: cha mẹ quyết định chi tiêu học thêm phần lớn dựa trên nỗi lo so sánh và quảng cáo, thiếu công cụ đánh giá khách quan hiệu quả thực tế.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Top trung tâm luyện thi/gia sư uy tín nhất" nêu tên cụ thể — dễ thành PR trá hình, chất lượng trung tâm phụ thuộc nhiều vào giáo viên đứng lớp cụ thể chứ không cố định theo thương hiệu; thay bằng bộ tiêu chí câu hỏi cha mẹ tự đánh giá. "Lịch học thêm dày đặc cho con đạt học sinh giỏi toàn diện" dạng cổ vũ chạy đua thành tích — đi ngược tinh thần cân bằng của chuyên mục. "Mẹo học tủ, đoán đề thi" — khuyến khích gian lận học tập, không phù hợp định vị giáo dục nghiêm túc của site. "So sánh học phí toàn bộ hệ thống trung tâm luyện thi lớn" dạng bảng liệt kê — dữ liệu thay đổi nhanh theo năm học, dễ lỗi thời và sai lệch; chỉ nêu khoảng chi phí tham khảo. "Trẻ không học thêm sẽ thua kém bạn bè" giọng hù dọa tạo áp lực — phần lớn trẻ tiểu học không cần học thêm nếu theo kịp chương trình chính khóa, cần nói rõ điều này để giảm áp lực không cần thiết.
                TEXT,

            'audience' => 'Cha mẹ Việt 30-42 tuổi có con tiểu học hoặc THCS, thu nhập trung bình đến khá ở thành thị, đang cân nhắc hoặc đã cho con học thêm ít nhất 1 môn; chịu áp lực so sánh từ nhóm chat phụ huynh và thành tích của con bạn bè; tìm kiếm khi con vừa có kết quả học tập không như mong đợi hoặc trước các kỳ thi chuyển cấp quan trọng.',

            'constraints' => 'Không PR trung tâm/gia sư cụ thể; không cổ vũ chạy đua thành tích hay học tủ/gian lận; luôn phân biệt rõ "cần học thêm thật sự" và "áp lực so sánh"; chi phí chỉ nêu khoảng tham khảo kèm lưu ý thay đổi theo thời gian/khu vực; tôn trọng ý kiến và giới hạn sức chịu đựng của trẻ, không chỉ nhìn từ góc độ kỳ vọng cha mẹ.',

            'style_sample' => <<<'TEXT'
                Tối qua bạn vừa ký vào phiếu đăng ký lớp học thêm Toán buổi thứ ba trong tuần cho con, sau khi đọc tin nhắn "lớp mình bạn nào cũng đi học thêm hết rồi" trong nhóm chat phụ huynh. Trước khi đăng ký buổi thứ tư, hãy thử làm một việc đơn giản: mở vở bài tập và bài kiểm tra gần nhất của con ra xem CHÍNH XÁC con đang vướng ở đâu — sai vì chưa hiểu khái niệm, hay chỉ vì ẩu, hay vì tốc độ làm bài chậm. Ba nguyên nhân này cần ba cách xử lý hoàn toàn khác nhau, và không phải nguyên nhân nào cũng cần đến một lớp học thêm mới. Nhiều khi điều con cần chỉ là 15 phút mỗi tối ngồi cùng bố mẹ xem lại bài sai, chứ không phải thêm hai giờ ngồi trung tâm sau một ngày dài ở trường. Trong bài này, mình sẽ cùng bạn đi qua cách nhận diện đúng vấn đề của con, khi nào học thêm thực sự cần thiết, và nếu cần thì chọn hình thức nào — gia sư riêng hay học nhóm — phù hợp với con và với ngân sách gia đình.
                TEXT,
        ],

        // === Giáo dục tại nhà ===
        [
            'parent_slug' => null,
            'slug'        => 'giao-duc-tai-nha',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: homeschool là gì, rào cản pháp lý Việt Nam, ai phù hợp/không phù hợp, xây chương trình, bù đắp tương tác xã hội.
                - KHÔNG viết: cổ vũ phong trào một chiều, chọn trường truyền thống (→ Trường mầm non & tiểu học).
                - Trung thực TUYỆT ĐỐI về rào cản pháp lý — không được luật công nhận là hình thức chính quy độc lập, mọi giải pháp hiện tại là "lách" hợp lý.
                - Nhóm độc giả NHỎ nhưng đặc thù — thành công là giúp người không phù hợp nhận ra rõ ràng "không hợp với mình", tránh thử rồi bỏ giữa chừng gây xáo trộn cho con.
                - KPI: đo bằng tỷ lệ gắn bó lâu dài của nhóm độc giả nhỏ và tỷ lệ độc giả tự đánh giá đúng mức độ phù hợp trước khi thử.
                TEXT,

            'core_focus' => <<<'TEXT'
                Cung cấp thông tin trung thực, không cổ vũ một chiều, cho nhóm nhỏ cha mẹ đang cân nhắc hoặc đã chọn giáo dục tại nhà (homeschool) cho con tại Việt Nam: giải thích homeschool là gì và các mô hình phổ biến (tự soạn chương trình, theo chương trình quốc tế online, kết hợp học nhóm homeschool), thực trạng pháp lý tại Việt Nam (giáo dục bắt buộc theo Luật Giáo dục, những vướng mắc thực tế về học bạ - xét tuyển - thi tốt nghiệp mà gia đình homeschool phải tự tìm giải pháp như đăng ký học bạ song song ở trường hoặc thi theo diện tự học), ai thực sự phù hợp với homeschool (gia đình có 1 phụ huynh đủ thời gian, con có tính tự học tốt hoặc có nhu cầu đặc biệt trường không đáp ứng được) và ai không nên theo đuổi, cách xây dựng chương trình học tại nhà có hệ thống, và làm sao để con vẫn có tương tác xã hội đầy đủ khi không đến trường. KHÔNG lấn sân: không viết như một lời cổ vũ phong trào "bỏ trường học ở nhà cho tự do", không lấn nội dung chọn trường truyền thống (thuộc Trường mầm non & tiểu học).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung tiếng Việt về homeschool hiện chia hai cực đoan: nhóm cổ vũ nhiệt thành (thường là chính người đang homeschool, chia sẻ trải nghiệm tích cực nhưng ít nói về khó khăn) và nhóm phản đối gay gắt vì lo ngại pháp lý/xã hội hóa. Chuyên mục này chọn đứng giữa với 3 điểm khác biệt: (1) Nói thẳng và cụ thể về rào cản pháp lý tại Việt Nam — không né tránh hay tô hồng, giải thích rõ giáo dục tại nhà không được luật Việt Nam công nhận như một hình thức chính quy độc lập, mọi giải pháp hiện tại đều là "lách" hợp lý (đăng ký học bạ trường, thi tự do) chứ không phải con đường chính thức trơn tru; (2) Đưa ra tiêu chí thành thật để cha mẹ TỰ ĐÁNH GIÁ mình có phù hợp không, thay vì cổ vũ ai cũng nên thử — nói rõ đây là lựa chọn đòi hỏi nguồn lực thời gian rất lớn từ ít nhất một phụ huynh; (3) Chỉ ra rủi ro thật về xã hội hóa và cách bù đắp cụ thể (nhóm sinh hoạt homeschool, hoạt động ngoại khóa, thể thao đồng đội), không giả vờ đây không phải vấn đề.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cho cụm truy vấn ngách nhưng có ý định tìm kiếm rất rõ ràng: "giáo dục tại nhà có hợp pháp ở Việt Nam không", "homeschool là gì", "con không đến trường có được thi đại học không", "cộng đồng homeschool Việt Nam". (2) Trở thành nguồn thông tin trung thực hiếm hoi giữa hai cực đoan cổ vũ/phản đối — đo bằng tỷ lệ quay lại và mức gắn bó lâu dài của nhóm độc giả nhỏ này. (3) Với đa số độc giả chỉ đang tò mò (không thực sự theo đuổi), bài viết đóng vai trò giúp họ đưa ra quyết định "không phù hợp với mình" một cách rõ ràng, tránh thử rồi bỏ giữa chừng gây xáo trộn cho con.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Nghe nói ở nước ngoài homeschool phổ biến, không biết ở Việt Nam có hợp pháp không, con không đến trường thì học bạ, chuyển cấp, thi cử tính thế nào?"; "Con bị bắt nạt/căng thẳng ở trường, đang nghĩ đến việc cho nghỉ học ở nhà nhưng sợ pháp luật không cho phép"; "Mình muốn tự dạy con nhưng không phải giáo viên, sợ dạy không đủ kiến thức, con thiệt thòi so với bạn bè"; "Đọc các trang nước ngoài toàn nói homeschool tuyệt vời, nhưng ở Việt Nam mình chưa thấy ai chia sẻ thật về khó khăn"; "Nếu homeschool thì con có bạn bè không, có bị thiếu kỹ năng xã hội không?"; "Cả hai vợ chồng đều đi làm toàn thời gian, liệu có thể homeschool được không hay bắt buộc phải có người ở nhà toàn thời gian?"; "Con học hết cấp 2 tại nhà, giờ muốn thi vào cấp 3 công lập thì làm thủ tục ra sao?". Nền chung: đây là quyết định thiểu số, thiếu thông tin tiếng Việt đáng tin cậy, và cha mẹ phải tự mò mẫm giải quyết các vướng mắc hành chính không có hướng dẫn chính thức rõ ràng.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Homeschool là tương lai của giáo dục, nên cho con nghỉ học truyền thống" dạng cổ vũ phong trào một chiều — không trung thực về rào cản pháp lý/xã hội thật tại Việt Nam, đi ngược yêu cầu trung thực cốt lõi của chuyên mục. "Hướng dẫn 'lách luật' hoàn toàn để tránh mọi ràng buộc pháp lý" — có thể khuyến khích hành vi vi phạm quy định giáo dục bắt buộc; chỉ trình bày các giải pháp thực tế đang được áp dụng hợp pháp (đăng ký song song, thi tự do) kèm rủi ro rõ ràng, không tư vấn né tránh trách nhiệm pháp lý. "Danh sách chương trình học homeschool quốc tế tốt nhất" dạng review thương mại — không đủ cơ sở đánh giá khách quan cho từng gia đình, dễ thành quảng cáo; chỉ nêu các loại hình chương trình phổ biến và tiêu chí lựa chọn chung. "Câu chuyện thành công của 1 gia đình cụ thể" dạng ca ngợi cá nhân — thiên lệch, không đại diện cho đa số trường hợp; nếu dùng case study phải kèm cả khó khăn thực tế đã trải qua, không chỉ mặt tích cực.
                TEXT,

            'audience' => 'Nhóm nhỏ, đặc thù: cha mẹ Việt 30-45 tuổi đang thực sự cân nhắc hoặc đã bắt đầu giáo dục tại nhà cho con, thường vì lý do cụ thể (con gặp vấn đề ở trường truyền thống, gia đình có triết lý giáo dục riêng, hoặc sống ở nơi không có trường phù hợp); ít nhất một phụ huynh có thời gian đáng kể dành cho việc dạy con; chủ động tìm hiểu sâu, đọc kỹ nhiều nguồn kể cả tiếng Anh do thiếu tài liệu tiếng Việt.',

            'constraints' => 'Phải trung thực tuyệt đối về rào cản pháp lý và xã hội thực tế tại Việt Nam — không tô hồng cũng không phủ nhận homeschool, không cổ vũ một chiều theo phong trào; luôn nêu rõ ràng buộc pháp lý hiện hành và khuyên xác minh với cơ quan giáo dục địa phương khi cần quyết định cụ thể; không tư vấn né tránh nghĩa vụ giáo dục bắt buộc; tôn trọng cả gia đình chọn homeschool lẫn gia đình chọn trường truyền thống, không ngầm định cái nào ưu việt hơn.',

            'style_sample' => <<<'TEXT'
                Có thể bạn vừa đọc xong một bài blog nước ngoài kể về hành trình homeschool đầy cảm hứng của một gia đình Mỹ, và đang tự hỏi: liệu mình có thể làm điều tương tự cho con ở Việt Nam không? Câu trả lời thật, không tô vẽ, là: có thể — nhưng con đường sẽ khác khá nhiều so với những gì bạn vừa đọc. Ở Việt Nam, giáo dục tại nhà chưa được luật công nhận là một hình thức học chính quy độc lập, nghĩa là gần như mọi gia đình homeschool hiện nay đều đang tự xây giải pháp riêng — có thể là đăng ký học bạ song song ở một trường, hoặc chuẩn bị cho con thi theo diện tự học khi đến các mốc quan trọng. Đây không phải là con đường sai, nhưng nó đòi hỏi bạn chủ động tìm hiểu và chuẩn bị kỹ hơn nhiều so với việc chỉ quyết định "không cho con đến trường nữa". Trong bài này, mình sẽ đi thẳng vào những gì bạn cần biết trước khi bắt đầu: rào cản pháp lý cụ thể, ai thực sự phù hợp với con đường này, và những gì ít người kể cho bạn nghe về phần khó khăn của hành trình.
                TEXT,
        ],

        // === Đánh giá sản phẩm ===
        [
            'parent_slug' => null,
            'slug'        => 'danh-gia-san-pham',

            'writer_insights' => <<<'TEXT'
                - Phạm vi: SO SÁNH khách quan tối thiểu 2-3 lựa chọn (bỉm, sữa công thức, xe đẩy, đồ chơi...) theo 4 tiêu chí cố định — KHÔNG kết luận "nên mua sản phẩm X".
                - KHÔNG viết: kỹ thuật sử dụng (→ các danh mục theo tuổi), xếp hạng Top/Best-of (→ Giải thưởng nổi bật), review đơn lẻ 1 sản phẩm.
                - Vai trò tư vấn viên trung lập — luôn nêu ít nhất 1 nhược điểm mỗi lựa chọn kể cả lựa chọn đắt nhất, dù có Product CTA Box hiển thị giá/link mua.
                - Neo tiêu chí vào HOÀN CẢNH gia đình Việt cụ thể (nhà không thang máy, con hoạt động nhiều ban đêm) — không dùng thông số kỹ thuật chung chung.
                - KPI: đo bằng CTR thương mại (nhóm từ khóa "so sánh X và Y" có giá trị chuyển đổi cao nhất site) và traffic dẫn về từ các danh mục theo tuổi con.
                TEXT,

            'core_focus' => <<<'TEXT'
                So sánh KHÁCH QUAN các nhóm sản phẩm mẹ và bé cha mẹ Việt phải quyết định mua thường xuyên nhất: bỉm - tã (cân nặng, độ thấm hút, da nhạy cảm), sữa công thức (độ tuổi, thành phần, nhu cầu đặc biệt), xe đẩy - địu - ghế ăn - cũi (không gian nhà, ngân sách), đồ chơi giáo dục, và thiết bị hỗ trợ khác (máy hâm sữa, máy tiệt trùng, monitor theo dõi trẻ). Mỗi bài theo khung 4 tiêu chí cố định — giá & chi phí lâu dài, công năng thực tế (không phải thông số quảng cáo), độ an toàn (chứng nhận, cảnh báo thu hồi), đối tượng phù hợp — trình bày bảng ưu - nhược song song giữa các lựa chọn phổ biến (hàng nội lẫn nhập, bình dân lẫn cao cấp), để người đọc tự đối chiếu và quyết định, KHÔNG kết luận "nên mua sản phẩm X". KHÔNG lấn sân: kỹ thuật ăn dặm/dinh dưỡng (thuộc Dinh dưỡng theo độ tuổi), hướng dẫn sử dụng an toàn chuyên sâu như quấn khăn - lắp ghế ô tô (thuộc Chăm sóc & nuôi dạy theo độ tuổi), và danh sách "Top sản phẩm tốt nhất" dạng xếp hạng (thuộc Giải thưởng nổi bật).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Gần như mọi bài "review sản phẩm mẹ và bé" tiếng Việt hiện có là PR trá hình do nhãn hàng đặt viết hoặc affiliate one-sided chỉ khen một sản phẩm — người đọc phải tự lọc thật giả giữa hàng chục bài "top 1 thị trường" mâu thuẫn nhau. Khác biệt: (1) Vai trò tư vấn viên trung lập, không phải người bán hàng — dù có Product CTA Box hiển thị giá/ảnh/link mua, LỜI VĂN không viết như quảng cáo: luôn nêu ít nhất một nhược điểm thật mỗi lựa chọn kể cả lựa chọn đắt nhất; (2) Neo tiêu chí vào HOÀN CẢNH gia đình Việt cụ thể thay vì thông số chung chung — xe đẩy nào hợp nhà không thang máy, bỉm nào đáng tiền khi con hoạt động nhiều ban đêm; (3) Luôn có mục "khi nào KHÔNG cần mua" — thẳng thắn nói ra khoản chi không cần thiết mà marketing thổi phồng, xây niềm tin bằng cách chủ động khuyên tiết kiệm.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Chiếm traffic tìm kiếm cực lớn ở nhóm truy vấn "so sánh X và Y", "nên mua bỉm/sữa/xe đẩy loại nào" — nhóm từ khóa thương mại (commercial intent) có giá trị chuyển đổi cao nhất trên toàn site — đo bằng CTR sang Product CTA Box, cạnh tranh bằng độ trung thực thay vì bằng ngân sách quảng cáo. (2) Tận dụng hạ tầng Product CTA Box đã có sẵn (catalog giá - ảnh - 4 link affiliate) để tạo doanh thu affiliate thực tế mà không đánh đổi uy tín biên tập — mỗi bài so sánh gắn CTA Box cho TẤT CẢ các lựa chọn được nhắc tới, không riêng một sản phẩm được "ưu ái". (3) Xây danh mục thành điểm dừng chân cuối trước khi ra quyết định mua, nhận traffic dẫn về từ các danh mục theo độ tuổi con (ví dụ từ bài ăn dặm dẫn sang so sánh ghế ăn dặm) và từ Giải thưởng nổi bật (bài Top dẫn độc giả cần so sánh sâu hơn về đây). (4) Xây uy tín dài hạn: độc giả tin vào một bài so sánh trung lập sẽ tin cả các bài khác của site, kể cả các bài không liên quan thương mại.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Bỉm hàng Nhật đắt gấp đôi bỉm nội, có thật sự đáng tiền hay chỉ là marketing?"; "Con bị hăm liên tục, đổi bỉm loại nào thì hết, hay do cách dùng?"; "Sữa công thức loại nào tốt cho con hay táo bón — có nên đổi loại đắt hơn không?"; "Xe đẩy gấp gọn hay xe đẩy 3 bánh địa hình, nhà mình ở chung cư tầng 5 không thang máy thì chọn loại nào?"; "Địu ergonomic xịn giá 2-3 triệu với địu vải thường 200 nghìn khác nhau thế nào, có cần thiết không?"; "Ghế ăn dặm loại gỗ hay loại nhựa đa năng, cái nào dùng được lâu hơn?"; "Đồ chơi giáo dục quảng cáo giúp con thông minh sớm — có thật không hay chỉ là đồ chơi bình thường gắn mác?"; "Mua đồ sơ sinh cho con đầu lòng, không biết cái gì thật sự cần và cái gì mua rồi không dùng đến, tiếc tiền"; "Hàng xách tay với hàng chính hãng phân phối trong nước, giá chênh nhiều, chất lượng có khác không?"; "Đọc review trên mạng thấy ai cũng khen sản phẩm họ đang bán, không biết tin ai". Nền chung: cha mẹ (đặc biệt con đầu lòng) phải ra hàng chục quyết định mua sắm dồn dập trong vài tháng, ngân sách có hạn, và bị bủa vây bởi quảng cáo lẫn trong nội dung tưởng như tư vấn.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Top 10 bỉm/sữa/xe đẩy tốt nhất năm nay" dạng liệt kê xếp hạng — đây là định dạng của Giải thưởng nổi bật, viết ở đây gây trùng lặp và làm loãng vai trò trung lập của danh mục này (danh mục này SO SÁNH theo tiêu chí, không XẾP HẠNG thứ tự). "Review chi tiết 1 sản phẩm duy nhất kèm lời khuyên nên mua" — về bản chất là bài quảng cáo có vỏ bọc review, đi ngược cam kết trung lập đã chốt với người dùng; mọi bài đều phải so sánh tối thiểu 2-3 lựa chọn. "Cách pha sữa đúng chuẩn, cách quấn khăn đúng cách" — đây là hướng dẫn SỬ DỤNG sản phẩm, thuộc các danh mục Chăm sóc & nuôi dạy hoặc Ăn dặm & dinh dưỡng theo độ tuổi, không thuộc danh mục so sánh sản phẩm. "Đập hộp/unbox sản phẩm mới ra mắt" dạng PR sớm cho nhãn hàng — ưu tiên nhãn hàng trả tiền hơn lợi ích người đọc, đi ngược định vị trung lập. "Mẹo mua hàng giá rẻ, săn sale" thuần túy — lệch trọng tâm sang tiết kiệm chi tiêu (gần với Tài chính gia đình) thay vì đúng trọng tâm chất lượng - phù hợp; chỉ nhắc giá như một trong bốn tiêu chí, không làm chủ đề chính.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-38 tuổi, phần lớn đang có con đầu lòng nên chưa có kinh nghiệm mua sắm đồ sơ sinh - trẻ nhỏ, đứng trước quyết định mua tốn kém (bỉm/sữa mua lặp lại hàng tháng, xe đẩy/cũi mua một lần dùng nhiều năm); đọc vào buổi tối trước khi đặt hàng online, đang mở nhiều tab so sánh giá và review trái chiều giữa các sàn thương mại điện tử; ngân sách có hạn, sợ mua nhầm - mua thừa, và hoài nghi sẵn với nội dung có mùi quảng cáo.',

            'constraints' => 'Mọi bài so sánh tối thiểu 2 lựa chọn, không review đơn lẻ 1 sản phẩm theo hướng khuyên mua; luôn nêu ít nhất 1 nhược điểm mỗi lựa chọn; không dùng ngôn ngữ tuyệt đối hóa ("tốt nhất", "phải mua"); Product CTA Box khi gắn phải công bằng cho các lựa chọn, không ưu ái sản phẩm hoa hồng cao hơn; không viết như bài đặt hàng của nhãn hàng; thông tin an toàn (chứng nhận, cảnh báo thu hồi) phải chính xác, dẫn nguồn; giá nêu dạng khoảng tham khảo, không cam kết cụ thể.',

            'style_sample' => <<<'TEXT'
                Bạn đang đứng giữa hai tab trình duyệt: một bên là bỉm nội giá chưa bằng nửa, một bên là bỉm Nhật được hội nhóm mẹ bỉm sữa khen suốt — và câu hỏi thật lòng là "chênh giá thế này liệu có chênh chất lượng tương ứng không, hay mình đang trả tiền cho cái mác ngoại?". Câu trả lời trung thực là: có và không, tùy vào điều gì bạn đang cần giải quyết. Nếu con bạn ngủ ngon, da không có vấn đề gì và bỉm nội đang thấm hút đủ qua đêm — thì phần chênh lệch giá kia gần như không mua thêm được lợi ích gì đáng kể, ngoài một vài chi tiết như bề mặt mềm hơn hay form ôm dáng hơn. Nhưng nếu con bạn đang bị hăm tái đi tái lại hoặc là kiểu bé "tè nhiều" khiến bỉm nội bị tràn ngược giữa đêm, thì phần chênh lệch đó lại đang mua đúng thứ bạn cần: lõi thấm hút dày hơn và bề mặt giữ khô tốt hơn thật sự tạo khác biệt. Bài này sẽ đặt các dòng bỉm phổ biến nhất thị trường Việt Nam cạnh nhau theo đúng 4 tiêu chí — giá trên mỗi miếng, khả năng thấm hút ban đêm, độ dịu với da nhạy cảm, và có phù hợp túi tiền dùng lâu dài hay không — để bạn tự khớp với tình trạng cụ thể của con mình, không phải để chúng tôi chọn hộ bạn cái tên đứng đầu.
                TEXT,
        ],

        // === Video ===
        [
            'parent_slug' => null,
            'slug'        => 'video',

            'writer_insights' => <<<'TEXT'
                - LƯU Ý: đây là một ĐỊNH DẠNG cắt ngang, không phải chủ đề — đối tượng phục vụ là ĐỘI SẢN XUẤT NỘI DUNG, không phải cha mẹ đọc giả.
                - Mọi ý tưởng phải bắt nguồn từ 1 chủ đề ĐÃ có ở danh mục khác — KHÔNG tự phát minh chủ đề nuôi dạy con mới ở đây.
                - Chỉ chọn nội dung cần CHUYỂN ĐỘNG hoặc CẢM XÚC TRỰC QUAN (thao tác tay, lát cắt đời sống, review hình ảnh) — nếu đọc chữ vẫn hiệu quả tương đương thì không thuộc về đây.
                - Luôn dẫn nguồn về danh mục chủ đề gốc trong mỗi ý tưởng, và cân nhắc tính khả thi quay dựng thực tế.
                - Hình ảnh trẻ em nhạy cảm (khóc, ốm, xử lý y tế) phải ghi rõ dùng diễn viên nhí/minh họa — không quay trẻ thật.
                TEXT,

            'core_focus' => <<<'TEXT'
                Đây KHÔNG phải một chủ đề mà là một ĐỊNH DẠNG cắt ngang toàn bộ chủ đề nuôi dạy con của site — kho ý tưởng kịch bản cho đội content khi cần sản xuất video ngắn (Reels/TikTok/Shorts, 30 giây - 3 phút) thay vì bài viết dài. Trọng tâm: xác định LOẠI nội dung nào chuyển thể sang video mới phát huy hết giá trị mà bài viết chữ không làm được: (1) thao tác tay cần nhìn chuyển động mới hiểu đúng — quấn khăn, sơ cứu hóc dị vật, bế - địu đúng tư thế, vệ sinh rốn sơ sinh; (2) lát cắt đời sống tạo đồng cảm nhanh — "một ngày của mẹ bỉm sữa đi làm"; (3) review sản phẩm trực quan hơn chữ — mở hộp, so sánh kích thước/độ dày bỉm; (4) mẹo nhanh dạng đếm ngược ("3 dấu hiệu con sẵn sàng ăn dặm trong 30 giây"). Mỗi ý tưởng ghi rõ: chủ đề gốc lấy từ danh mục nào, độ dài đề xuất, và kịch bản khung (mở đầu 3 giây - nội dung chính - lời chốt). KHÔNG lấn sân: không tự phát minh chủ đề nuôi dạy con mới — mọi ý tưởng video phải bắt nguồn từ một chủ đề ĐÃ có định hướng ở danh mục khác, chuyên mục này chỉ chọn GÓC VIDEO HÓA, không cạnh tranh phạm vi kiến thức.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Khác biệt lớn nhất của danh mục này so với 43 danh mục còn lại: đối tượng phục vụ trực tiếp không phải độc giả cha mẹ mà là BIÊN TẬP VIÊN/ĐỘI SẢN XUẤT NỘI DUNG — một loại "category nội bộ" định hướng sản xuất, dù vẫn hiển thị công khai (kỹ thuật: `ArticleFormat::Video` chỉ là enum đánh dấu định dạng, bài "thuộc Video" thực chất vẫn là bài viết mô tả/dẫn kịch bản, không phải nơi phát video). Ba nguyên tắc: (1) Mỗi ý tưởng trả lời được câu "tại sao cái NÀY hợp video hơn bài viết" — ưu tiên tuyệt đối nội dung cần CHUYỂN ĐỘNG hoặc CẢM XÚC TRỰC QUAN; (2) Luôn dẫn nguồn về danh mục chủ đề gốc — không viết như thể là kiến thức riêng của mục Video; (3) Tư duy sản xuất thực tế — cân nhắc khả thi quay dựng (diễn viên nhí, sản phẩm thật, nhạy cảm hình ảnh trẻ em) chứ không chỉ liệt kê ý tưởng suông.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Là nguồn ý tưởng kịch bản có hệ thống cho đội sản xuất video, giảm thời gian "nghĩ nội dung hôm nay quay gì" — đo bằng số ý tưởng được đội content thực sự sản xuất thành video. (2) Mở kênh phân phối mới (mạng xã hội dạng video ngắn) để kéo traffic mới về các bài viết dài tương ứng trên site — mỗi video có một bài viết "gốc" trên site để dẫn link trong caption/bio, video là công cụ tiếp thị cho bài viết chứ không phải sản phẩm cuối. (3) Tiếp cận nhóm phụ huynh trẻ (dưới 30 tuổi) quen thuộc với định dạng video ngắn hơn là đọc bài dài, mở rộng tệp độc giả mà các danh mục bài viết truyền thống khó chạm tới. (4) Tạo tư liệu trực quan có thể nhúng lại vào chính bài viết gốc (ví dụ bài "cách sơ cứu hóc dị vật" nhúng video minh họa) để tăng thời gian đọc và độ tin cậy của bài viết.
                TEXT,

            'pain_points' => <<<'TEXT'
                Đây là các câu hỏi/khó khăn của ĐỘI SẢN XUẤT NỘI DUNG chứ không phải của cha mẹ đọc giả: "Tuần này không biết quay video gì, ý tưởng cũ dùng hết rồi"; "Có nên quay lại y hệt nội dung bài viết hay phải nghĩ góc khác cho hợp định dạng ngắn"; "Chủ đề bệnh tật/an toàn có nhạy cảm khi quay hình ảnh trẻ em thật không, hay nên dùng minh họa"; "Video hướng dẫn thao tác (sơ cứu, quấn khăn) quay một lần dùng lại được lâu, nhưng video dạng 'ngày của mẹ' phải làm mới liên tục — phân bổ ưu tiên thế nào"; "Ý tưởng review sản phẩm dạng video có bị trùng với bài Đánh giá sản phẩm dạng chữ không, ai viết bài nào"; "Làm sao biết chủ đề nào cha mẹ thật sự muốn XEM thay vì chỉ muốn ĐỌC". Nền chung: đội nội dung cần một "ngân hàng ý tưởng" luôn cập nhật, phân loại theo độ dài và mức độ dễ sản xuất, để không phải suy nghĩ lại từ đầu mỗi lần cần ra video mới.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Video chuyên sâu 10-15 phút dạng phỏng vấn chuyên gia" — không phải định dạng video NGẮN mà danh mục này phục vụ, thuộc một hướng sản xuất khác (nếu có) ngoài phạm vi. "Tự nghĩ chủ đề nuôi dạy con hoàn toàn mới chưa xuất hiện ở danh mục nào" — vi phạm nguyên tắc cốt lõi "không cạnh tranh nội dung với danh mục chủ đề khác"; mọi ý tưởng phải bắt nguồn từ một danh mục đã có định hướng. "Video hài hước/giải trí đơn thuần không có giá trị thông tin cho cha mẹ" (kiểu chỉ để giải trí, không liên quan nuôi dạy con) — lệch mục tiêu site là nguồn nội dung nuôi dạy con hữu ích, không phải kênh giải trí gia đình chung chung. "Danh sách 'ý tưởng video hot trend'" đi theo trào lưu mạng xã hội bất kỳ mà không neo vào chủ đề nuôi dạy con thật — content bắt trend nhưng rỗng giá trị, dễ lạc thương hiệu. "Video có hình ảnh trẻ em thật trong tình huống nhạy cảm" (khóc lớn, ốm nặng, xử lý y tế thật) mà không cân nhắc quyền riêng tư/an toàn hình ảnh trẻ em — mọi ý tưởng loại này phải ghi chú rõ "dùng diễn viên nhí/minh họa, không quay trẻ thật trong tình huống nhạy cảm".
                TEXT,

            'audience' => 'Đội biên tập/sản xuất nội dung nội bộ của site (không phải cha mẹ đọc giả trực tiếp) — người cần một danh sách ý tưởng kịch bản video ngắn cập nhật thường xuyên để lên lịch sản xuất hàng tuần; đọc để lấy ý tưởng rồi chuyển thành kịch bản quay dựng cụ thể, quan tâm tới tính khả thi sản xuất (thời lượng, đạo cụ, có cần diễn viên nhí) hơn là độ sâu kiến thức thuần túy.',

            'constraints' => 'Không tự tạo chủ đề nuôi dạy con mới — mọi ý tưởng phải dẫn về danh mục chủ đề đã có trong seeder; luôn ghi rõ định dạng/độ dài đề xuất và khung kịch bản (mở đầu - nội dung - chốt); nội dung y tế/an toàn (sơ cứu, bệnh) phải chính xác như bản viết gốc, không đơn giản hóa sai lệch; ý tưởng có hình ảnh trẻ em trong tình huống nhạy cảm phải dùng diễn viên nhí/minh họa, không cổ vũ quay cảnh thật hại quyền riêng tư trẻ; không biến thành nơi tổng hợp video giải trí không liên quan.',

            'style_sample' => <<<'TEXT'
                Bạn vừa họp xong lịch nội dung tuần và ô "video ngắn thứ Năm" vẫn đang trống — trong khi bài viết "Sốt mọc răng: phân biệt với sốt bệnh thật" tuần trước đọc rất tốt trên site. Đây chính là lúc chuyển góc: thay vì tóm tắt lại bài viết bằng giọng đọc, hãy nghĩ tới cảnh quay 15 giây mẹ đang sờ tay vào lợi con và đưa nhiệt kế lên cùng lúc, voice-over hỏi thẳng "Con sốt 38 độ, có phải do mọc răng không?" rồi cắt sang 3 dấu hiệu phân biệt hiện dạng chữ lớn trên màn hình theo nhịp nhạc nhanh, kết bằng một câu chốt kèm lời mời đọc bài đầy đủ ở bio. Đó là công thức của cả danh mục Video: mỗi bài viết chữ đã có sẵn trên site là một MỎ Ý TƯỞNG, việc của bạn không phải viết lại nội dung mà là hỏi "khoảnh khắc nào trong bài này, nếu được NHÌN THẤY thay vì ĐỌC THẤY, sẽ chạm vào người xem nhanh hơn" — rồi biến đúng khoảnh khắc đó, và chỉ khoảnh khắc đó, thành 15-30 giây hình ảnh.
                TEXT,
        ],

        // === Giải thưởng nổi bật ===
        [
            'parent_slug' => null,
            'slug'        => 'giai-thuong-noi-bat',

            'writer_insights' => <<<'TEXT'
                - LƯU Ý: đây KHÔNG PHẢI giải thưởng chính thức có hội đồng chấm — là gợi ý biên tập tự tổng hợp, phải nói rõ điều này ngay đầu MỌI bài.
                - Phạm vi: Top/Best-of theo mùa/dịp hoặc nhu cầu cụ thể — dẫn link sang Đánh giá sản phẩm (so sánh sâu) hoặc Trường mầm non & tiểu học (tiêu chí chi tiết).
                - KHÔNG viết: so sánh sâu ưu/nhược từng cặp (→ Đánh giá sản phẩm), xếp hạng tiêu cực nhắm vào đối tượng cụ thể.
                - Tiêu chí xếp hạng phải công khai TRƯỚC danh sách, không giấu ở cuối bài — và ghi rõ thời điểm tổng hợp, cập nhật định kỳ.
                - Tuyệt đối không nhận phí từ nhãn hàng để xuất hiện/thăng hạng — đây là ranh giới cứng không thỏa hiệp.
                TEXT,

            'core_focus' => <<<'TEXT'
                Chuyên mục bài "Top 5 / Top 10 / Best-of" do CHÍNH BAN BIÊN TẬP tự tổng hợp và xếp hạng theo tiêu chí riêng — KHÔNG PHẢI giải thưởng chính thức có hội đồng chấm, không thu phí tham gia từ bất kỳ bên nào. Phạm vi: xếp hạng sản phẩm theo dịp/mùa (đồ chơi Trung thu, quà Tết, sản phẩm chống nóng mùa hè), theo nhu cầu cụ thể (bỉm đáng mua cho bé sơ sinh, sữa công thức được tin dùng), và lựa chọn phi sản phẩm (trường mầm non đáng cân nhắc theo tiêu chí công khai — không đánh giá tiêu cực về trường cụ thể, điểm đến du lịch theo mùa, trung tâm năng khiếu theo loại hình). MỌI bài mở đầu bằng đoạn minh bạch nêu tiêu chí xếp hạng (giá, độ phổ biến, phản hồi thu thập được), không phải kết quả giải thưởng chính thức, và có thể thay đổi theo thời gian. KHÔNG lấn sân: so sánh sâu ưu - nhược từng cặp (thuộc Đánh giá sản phẩm, chỉ dẫn link sang đó), tiêu chí chọn trường chi tiết (thuộc Trường mầm non & tiểu học).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Rủi ro lớn nhất của định dạng "Top/Best-of" là dễ bị hiểu nhầm thành giải thưởng chính thức hoặc quảng cáo trá hình trả tiền để lọt danh sách. Khác biệt của chuyên mục: (1) Minh bạch tuyệt đối về bản chất — luôn ghi rõ "đây là gợi ý biên tập, không phải giải thưởng chính thức" ngay dòng đầu, không dùng ngôn ngữ như "được trao giải" gây hiểu lầm có hội đồng chấm; (2) Tiêu chí xếp hạng công khai TRƯỚC danh sách, không giấu ở cuối bài; (3) Đóng vai trò "cửa ngõ nhanh" dẫn sang nội dung sâu hơn — mỗi mục link về bài so sánh chi tiết ở Đánh giá sản phẩm, phục vụ nhu cầu "cần quyết nhanh" nhưng vẫn để lối đi sâu; (4) Cập nhật định kỳ theo mùa/dịp, giữ tính thời sự thực chất chứ không phải danh hiệu "vĩnh viễn".
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Phục vụ nhóm độc giả đang ở bước quyết định gấp (mua quà theo dịp, chọn nhanh trước hạn) bằng nội dung dễ tiêu thụ, đọc lướt được trong một phút — đo bằng tỷ lệ nhấp vào các link dẫn sâu trong bài. (2) Là điểm trung chuyển traffic hiệu quả về hai danh mục nặng ký nhất về thương mại và quyết định lớn: Đánh giá sản phẩm (khi cần mua) và Trường mầm non & tiểu học (khi cần chọn trường theo mùa tuyển sinh) — mỗi bài Top đóng vai trò "trang tổng hợp" dẫn lưu lượng xuống các bài chuyên sâu. (3) SEO cho cụm truy vấn theo mùa/dịp có tính lặp lại hàng năm: "quà Trung thu cho bé", "top đồ chơi noel cho trẻ", "trường mầm non nên chọn 2026" — nội dung có thể cập nhật lại theo năm thay vì viết mới hoàn toàn. (4) Xây thói quen độc giả quay lại theo mùa/dịp (trước Tết, trước năm học mới, mùa hè) khi biết site luôn có sẵn danh sách gợi ý cập nhật.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Trung thu này chưa biết mua quà gì cho con, lướt mạng thấy quảng cáo nhiều quá không biết chọn cái nào"; "Sắp vào năm học mới, đang cuống chọn trường mầm non mà không có thời gian đọc hết các bài so sánh dài, chỉ cần vài gợi ý nhanh để tham khảo trước"; "Mùa hè nóng quá, không biết quạt/điều hòa mini, đồ chống nắng nào cho bé đang được nhiều mẹ chọn"; "Tết này biếu quà nhà có trẻ nhỏ, không biết loại nào vừa ý nghĩa vừa thiết thực"; "Đọc thấy trang nào cũng tự nhận 'top 1 được yêu thích nhất' mà không rõ họ dựa vào đâu để xếp hạng, không biết tin ai"; "Cần quyết nhanh trong hôm nay vì sắp hết hạn ưu đãi/sắp hết mùa, không có thời gian đọc bài so sánh dài 2000 chữ". Nền chung: đây là nhóm độc giả đang ở áp lực thời gian, cần một danh sách rút gọn đáng tin để quyết định nhanh mà không mất công tự tổng hợp từ nhiều nguồn.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Giải thưởng [Tên site] Awards 2026" đặt tên như một giải thưởng chính thức có logo trao tặng — dễ bị hiểu nhầm là chương trình bình chọn có hội đồng chấm hoặc nhãn hàng phải đăng ký tham gia, trong khi thực tế không hề có cơ chế này; phải luôn gọi đúng bản chất là "gợi ý biên tập/tổng hợp". "Cho phép nhãn hàng trả phí để xuất hiện trong Top" — phá vỡ hoàn toàn tính minh bạch đã cam kết, biến danh mục thành quảng cáo trá hình dưới vỏ bọc xếp hạng độc lập. "Xếp hạng, chê bai trực tiếp một trường học hoặc dịch vụ cụ thể theo hướng tiêu cực" (ví dụ 'top trường nên tránh') — rủi ro pháp lý bôi nhọ, chỉ được nêu Top theo hướng tích cực với tiêu chí rõ ràng, không xếp hạng mặt tiêu cực của đối tượng cụ thể có tên riêng. "Danh sách Top không có tiêu chí, chỉ liệt kê theo cảm tính hoặc theo thứ tự trả tiền quảng cáo" — vi phạm nguyên tắc minh bạch cốt lõi, mọi danh sách phải nêu rõ căn cứ xếp hạng. "Bài Top viết một lần rồi để mãi không cập nhật dù thị trường/giá cả đã đổi" — mất giá trị "gợi ý theo mùa/dịp" mà chuyên mục hướng tới, cần ghi chú thời điểm tổng hợp và cập nhật định kỳ.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-40 tuổi đang ở thời điểm quyết định gấp theo mùa hoặc dịp cụ thể (trước Trung thu/Tết/năm học mới/mùa hè), ít thời gian đọc sâu, cần một danh sách rút gọn đáng tin cậy để tham khảo nhanh rồi tự quyết; thường đọc trên điện thoại trong lúc tranh thủ giữa giờ làm hoặc buổi tối gấp trước hạn mua sắm/nộp hồ sơ, sẵn sàng bấm sâu vào bài chi tiết nếu mục nào đó khiến họ quan tâm hơn.',

            'constraints' => 'Mọi bài mở đầu bằng đoạn minh bạch nêu rõ đây là gợi ý/tổng hợp biên tập, KHÔNG phải giải thưởng chính thức có hội đồng chấm, và nêu tiêu chí xếp hạng trước danh sách; không dùng từ ngữ gợi ý hội đồng/ban giám khảo ("đạt giải", "được trao danh hiệu"); không nhận phí từ nhãn hàng để xuất hiện/thăng hạng; không xếp hạng tiêu cực nhắm vào đối tượng cụ thể (trường học, thương hiệu); mỗi mục nên dẫn link về bài chuyên sâu tương ứng khi có; ghi rõ thời điểm tổng hợp.',

            'style_sample' => <<<'TEXT'
                Trước khi vào danh sách, xin nói rõ một điều: đây không phải một giải thưởng chính thức có ban giám khảo chấm điểm, cũng không có nhãn hàng nào trả tiền để có mặt ở đây — đây là danh sách do đội biên tập chúng tôi tự tổng hợp, dựa trên ba tiêu chí cố định: mức độ phổ biến thực tế trên thị trường Việt Nam, tỷ lệ phản hồi tích cực từ các đánh giá công khai mà chúng tôi thu thập được, và mức giá trên hiệu quả sử dụng. Danh sách này cho dịp Trung thu năm nay, cập nhật lần gần nhất tuần trước, và chắc chắn sẽ thay đổi vào mùa sau khi có sản phẩm mới. Nếu bạn đang có 5 phút giữa giờ làm và cần một gợi ý nhanh để đặt hàng tối nay, danh sách dưới đây được sắp xếp để bạn đọc lướt là đủ quyết định — còn nếu bạn muốn hiểu sâu hơn tại sao món số 1 lại đứng trên món số 2, mỗi mục đều có link dẫn sang bài so sánh chi tiết để bạn đọc thêm trước khi xuống tiền.
                TEXT,
        ],

    ];

    public function run(): void
    {
        foreach (self::DEFINITIONS as $definition) {
            $this->seedOne($definition);
        }
    }

    /** @param array<string, mixed> $definition */
    private function seedOne(array $definition): void
    {
        $query = PostCategory::query()->where('slug', $definition['slug']);

        if ($definition['parent_slug'] === null) {
            $query->whereNull('parent_id');
        } else {
            $query->whereHas('parent', fn ($q) => $q->where('slug', $definition['parent_slug']));
        }

        $category = $query->first();

        if (! $category) {
            $this->command?->warn(sprintf(
                'Bỏ qua seed: không tìm thấy category slug="%s"%s.',
                $definition['slug'],
                $definition['parent_slug'] !== null ? sprintf(' dưới parent slug="%s"', $definition['parent_slug']) : ''
            ));

            return;
        }

        $foundation = CategoryContentFoundation::query()
            ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $category->id))
            ->first() ?? new CategoryContentFoundation();

        $foundation->fill([
            'core_focus'      => $definition['core_focus'],
            'writer_insights' => $definition['writer_insights'],
            'unique_angle'   => $definition['unique_angle'],
            'content_goals'  => $definition['content_goals'],
            'pain_points'    => $definition['pain_points'],
            'rejected_ideas' => $definition['rejected_ideas'],
            'audience'       => $definition['audience'],
            'constraints'    => $definition['constraints'],
            'style_sample'   => $definition['style_sample'],
        ]);

        $foundation->save();
        $foundation->categories()->syncWithoutDetaching([$category->id]);

        $this->command?->info(sprintf(
            'Đã seed Category Content Foundation #%d cho category "%s" (id=%d).',
            $foundation->id,
            $category->name,
            $category->id
        ));
    }
}
