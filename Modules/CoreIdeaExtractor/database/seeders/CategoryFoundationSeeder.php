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

            'audience' => 'Cha mẹ Việt 27-40 tuổi có con 3-6 tuổi học mầm non (người đọc chính là mẹ, con đầu hoặc vừa có thêm con thứ hai), sống thành thị/ven đô, cả hai đi làm toàn thời gian, chỉ thực sự bên con buổi tối và cuối tuần, nhiều nhà có ông bà chăm cùng; đọc trên điện thoại lúc 21h-23h, tìm giải pháp cho tình huống vừa xảy ra trong ngày, đang mệt và dễ thấy tội lỗi.',

            'constraints' => 'Không giọng hàn lâm, thuyết giảng hay phán xét cha mẹ; không hù dọa, không "con nhà người ta"; không cổ vũ đòn roi, quát mắng, so sánh; không đổ lỗi cho ông bà hay mẹ đi làm; lời khuyên phải khả thi với nhà chung cư chật, bố mẹ về nhà 18h; luôn có bước làm được ngay tối nay; kịch bản thoại phải là tiếng Việt tự nhiên, không dịch máy.',

            'style_sample' => <<<'TEXT'
                9 giờ tối, con đã ngủ, còn bạn vẫn ngồi nghĩ lại cảnh con lăn ra sàn siêu thị gào khóc đòi mua siêu nhân — và cả tiếng quát của chính mình sau đó. Trước hết, hãy thở ra một cái: một đứa trẻ 3-4 tuổi ăn vạ không phải là đứa trẻ hư, và một người mẹ lỡ quát con không phải là người mẹ tồi. Ở tuổi này, não bộ phụ trách kiềm chế cảm xúc của con mới chỉ đang xây những viên gạch đầu tiên — con không "cố tình thử thách" bạn, con thật sự chưa đủ khả năng dừng cơn giận lại. Hiểu điều đó không làm cơn ăn vạ biến mất, nhưng nó đổi câu hỏi trong đầu bạn từ "làm sao trị được con" thành "làm sao dạy con vượt qua cơn giận" — và đó là hai con đường rất khác nhau. Trong bài này, chúng ta sẽ đi qua 4 bước xử lý ngay tại chỗ khi con ăn vạ nơi công cộng, những câu nên nói và nên tránh (kèm lời thoại mẫu bạn có thể dùng nguyên văn), cách thống nhất trước với ông bà để không ai "phá vỡ thế trận", và cuối cùng — làm gì với cảm giác tội lỗi của chính bạn khi mọi chuyện đã qua.
                TEXT,
        ],

        // === Chuẩn bị mang thai ===
        [
            'parent_slug' => null,
            'slug'        => 'chuan-bi-mang-thai',

            'core_focus' => <<<'TEXT'
                Đồng hành với các cặp vợ chồng Việt từ lúc "quyết định có con" đến lúc que thử lên 2 vạch: chuẩn bị sức khỏe trước mang thai (khám tiền hôn nhân/tiền sản, tiêm phòng trước mang thai, bổ sung axit folic, cai thuốc lá - rượu bia), hiểu chu kỳ và canh thời điểm dễ thụ thai, chuẩn bị tài chính - tâm lý - công việc trước khi có con, xử lý áp lực "bao giờ có tin vui" từ hai bên nội ngoại, và nhận biết khi nào chậm con là bình thường - khi nào nên đi khám hiếm muộn (chuẩn WHO: 1 năm với vợ dưới 35 tuổi, 6 tháng với vợ trên 35). Bài viết dạng lộ trình theo mốc thời gian (6 tháng - 3 tháng - 1 tháng trước khi thả) và dạng giải đáp tình huống thật. KHÔNG lấn sân: dinh dưỡng sau khi ĐÃ có thai (thuộc Dinh dưỡng thai kỳ), theo dõi thai (thuộc Sự phát triển của thai nhi), điều trị hiếm muộn chuyên sâu (chỉ dừng ở mức nhận biết dấu hiệu và hướng dẫn chọn nơi khám).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Hầu hết bài "chuẩn bị mang thai" trên mạng là checklist y khoa dịch lại hoặc bài PR của phòng khám. Chuyên mục này khác ở 3 điểm: (1) Viết cho CẢ HAI vợ chồng — sức khỏe tinh trùng, vai trò người chồng trong chuẩn bị và trong hành trình mong con, điều gần như mọi nguồn tiếng Việt bỏ qua; (2) Nói thẳng về phần không ai nói: áp lực giục đẻ từ gia đình, tủi thân khi bạn bè lần lượt có con, mệt mỏi khi canh trứng biến chuyện vợ chồng thành nghĩa vụ — kèm cách giữ tinh thần và hôn nhân trong giai đoạn chờ đợi; (3) Phân biệt rõ ràng cái gì là y khoa (tiêm phòng, axit folic, khám sàng lọc) và cái gì là quan niệm dân gian chưa có bằng chứng (kiêng khem cực đoan, canh năm sinh con hợp tuổi) — tôn trọng văn hóa nhưng không để người đọc hoang mang giữa hai luồng.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Là điểm CHẠM ĐẦU TIÊN của độc giả với site — người đọc đến từ trước khi có con và nếu được phục vụ tốt sẽ đi cùng site suốt hành trình thai kỳ → sơ sinh → mầm non; ưu tiên chuyển đọc giả sang chuỗi Dinh dưỡng thai kỳ, Sức khỏe mẹ bầu ngay khi họ có tin vui. (2) SEO cho truy vấn giai đoạn sớm: "chuẩn bị gì trước khi mang thai", "uống axit folic trước khi mang thai bao lâu", "thả 6 tháng chưa có thai có sao không", "khám tiền sản ở đâu, hết bao nhiêu tiền". (3) Xây niềm tin y khoa có kiểm chứng ngay từ chuyên mục đầu phễu để định vị site là nguồn đáng tin cho cả hành trình làm cha mẹ.
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

            'core_focus' => <<<'TEXT'
                Sức khỏe THỂ CHẤT và TINH THẦN của người mẹ trong 9 tháng thai kỳ: xử lý các triệu chứng khó chịu theo tam cá nguyệt (ốm nghén, ợ nóng, chuột rút, đau lưng, phù chân, mất ngủ, táo bón, rạn da), nhận biết và theo dõi các bệnh lý thai kỳ thường gặp (tiểu đường thai kỳ, tăng huyết áp - tiền sản giật, thiếu máu, viêm âm đạo khi mang thai), dấu hiệu nguy hiểm phải đi viện NGAY (ra máu, đau bụng dữ dội, phù mặt đột ngột, thai giảm máy), thuốc và vaccine khi mang thai (loại nào an toàn, loại nào cấm), vận động - làm việc - quan hệ vợ chồng khi mang bầu, sức khỏe tinh thần (lo âu thai kỳ, thay đổi cảm xúc, áp lực công việc khi bụng bầu), và bóc tách kiêng cữ dân gian: cái nào có lý, cái nào vô căn cứ. KHÔNG lấn sân: chỉ số phát triển của thai (thuộc Sự phát triển của thai nhi), thực đơn ăn uống (thuộc Dinh dưỡng thai kỳ), dấu hiệu chuyển dạ (thuộc Chuyển dạ & đi sinh).
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

            'core_focus' => <<<'TEXT'
                Ăn uống thực tế cho mẹ bầu Việt trong 9 tháng: nguyên tắc dinh dưỡng theo tam cá nguyệt (3 tháng đầu nghén ăn được gì thì ăn, 3 tháng giữa tăng chất, 3 tháng cuối kiểm soát đường - muối), vi chất quan trọng và cách bổ sung đúng (sắt, canxi, DHA, axit folic — uống lúc nào, cái nào kỵ nhau, có cần uống đủ loại như quảng cáo không), thực phẩm nên ăn - nên hạn chế - phải tránh (có bằng chứng, không hù dọa), thực đơn mẫu kiểu cơm nhà Việt Nam theo túi tiền, ăn uống khi có bệnh lý thai kỳ (tiểu đường thai kỳ ăn gì, thiếu máu ăn gì), giải quyết tình huống thật: nghén không ăn nổi, thèm ăn vặt, đi ăn cỗ - ăn quán, và hóa giải các "giáo lý" ăn uống truyền miệng (ăn cho hai người, uống nước dừa cho con trắng, ăn trứng ngỗng cho con thông minh). KHÔNG lấn sân: triệu chứng và bệnh lý thai kỳ ngoài khía cạnh ăn uống (thuộc Sức khỏe mẹ bầu), chỉ số của thai (thuộc Sự phát triển của thai nhi).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung dinh dưỡng thai kỳ tiếng Việt bị hai thái cực chiếm đóng: bài khoa học khô khan liệt kê "protein, lipid, glucid" không nấu thành bữa cơm được, và bài bán sữa bầu - TPCN đội lốt tư vấn. Chuyên mục chọn đường thứ ba: (1) Quy mọi khuyến nghị ra BỮA CƠM VIỆT cụ thể — "cần 27mg sắt/ngày" phải thành "một lạng thịt bò + một bát canh rau dền + tráng miệng ổi thay cam"; thực đơn mẫu có phiên bản 50 nghìn/bữa chứ không chỉ cá hồi - hạt chia; (2) Nói thẳng về ngân sách: vi chất nào đáng đồng tiền (sắt, axit folic, canxi), cái nào là marketing (đa phần combo 5-7 lọ TPCN), sữa bầu có bắt buộc không (không — và nói rõ vì sao); (3) Mỗi món "truyền miệng" (trứng ngỗng, nước dừa, cá chép) được kiểm chứng tử tế bằng bằng chứng + văn hóa, không chế nhạo người khuyên.
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

            'core_focus' => <<<'TEXT'
                Toàn bộ hành trình từ tuần 36 đến lúc mẹ con về nhà: nhận biết dấu hiệu sắp sinh và chuyển dạ thật - giả (cơn gò Braxton Hicks vs chuyển dạ, vỡ ối, ra nhớt hồng), khi nào phải vào viện ngay, chuẩn bị đồ đi sinh sát thực tế bệnh viện Việt Nam (giỏ đồ cho mẹ - cho bé - giấy tờ bảo hiểm, thứ bệnh viện phát sẵn không cần mang), chọn nơi sinh và hiểu chi phí (viện công vs tư, sinh thường vs mổ, bảo hiểm y tế trái tuyến chi trả thế nào), diễn biến cuộc sinh theo từng giai đoạn để mẹ bớt sợ vì biết trước điều gì sẽ xảy ra, giảm đau khi sinh (gây tê ngoài màng cứng - thực hư đồn đại đau lưng về già), sinh mổ: khi nào cần, hồi phục ra sao, và 24-72 giờ đầu sau sinh tại viện (da kề da, khớp ngậm bú mẹ lần đầu, chăm sóc vết khâu/vết mổ, tắm gội sau sinh). Vai trò người chồng trong ngày đi sinh có mặt xuyên suốt. KHÔNG lấn sân: chăm bé sau khi VỀ NHÀ (thuộc nhóm Trẻ sơ sinh), nuôi sữa mẹ dài hạn (thuộc Nuôi con bằng sữa mẹ).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung "đi sinh" tiếng Việt hoặc là lý thuyết y khoa về các giai đoạn chuyển dạ, hoặc là review viện lẻ tẻ trong hội nhóm — không ai gộp thành cẩm nang HẬU CẦN + TÂM LÝ hoàn chỉnh. Khác biệt: (1) Viết như người dẫn đường đã đi trước: thủ tục nhập viện lúc 2h sáng thế nào, ai được vào phòng sinh, người nhà chờ ở đâu, đưa phong bì có phải "luật" không — những điều mẹ nào cũng thắc thỏm mà không bài chính thống nào dám viết; (2) Người chồng là NHÂN VẬT CHÍNH thứ hai: mỗi bài đều có phần việc cụ thể cho chồng (cầm giấy tờ gì, nói gì với bác sĩ, làm gì khi vợ đau), thay vì để anh ấy đứng ngoài hút thuốc chờ tin; (3) Chi phí minh bạch từng khoản theo cả hai kịch bản công - tư, thường - mổ, kèm cách dùng đúng BHYT và bảo hiểm thai sản — chủ đề mọi gia đình cần mà rất ít nơi viết tử tế, không PR cho viện nào.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Đón trọn lớp độc giả tuần 32-40 đang ở đỉnh lo lắng và nhu cầu tìm kiếm: "dấu hiệu sắp sinh", "giỏ đồ đi sinh cần những gì", "chi phí sinh ở bệnh viện X", "gây tê ngoài màng cứng có hại không", "vỡ ối bao lâu phải sinh". (2) Bài "chuẩn bị đồ đi sinh" và "chi phí đi sinh" là 2 bài trụ cột có khả năng được lưu (bookmark) và chia sẻ cho chồng/bà ngoại cao nhất site — tối ưu dạng checklist tải được. (3) Là cầu nối chiến lược chuyển độc giả thai kỳ sang hệ sinh thái sau sinh: cuối mỗi bài dẫn sang Chăm sóc trẻ sơ sinh, Nuôi con bằng sữa mẹ — giữ được người đọc ở đúng khoảnh khắc họ "chuyển vai" thành cha mẹ. (4) Xây tin cậy bằng sự minh bạch chi phí — nội dung không viện nào tự viết về mình.
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

            'core_focus' => <<<'TEXT'
                Kỹ năng chăm sóc HẰNG NGÀY cho bé 0-3 tháng, dạy từng bước như có nữ hộ sinh đứng cạnh: tắm bé và vệ sinh (tắm mấy lần/tuần, nhiệt độ nước, trình tự tắm an toàn, vệ sinh mắt - mũi - tai - vùng kín bé trai/bé gái), chăm sóc rốn đến khi rụng và dấu hiệu nhiễm trùng rốn, thay tã và chăm da (hăm tã, rôm sảy, cứt trâu, mụn sữa, vàng da sinh lý nhận biết ban đầu), bế - ẵm - vỗ ợ hơi đúng cách, quấn khăn, cắt móng tay, mặc ấm đúng chuẩn (nguyên tắc hơn người lớn 1 lớp — chống lại thói quen ủ quá kỹ), môi trường an toàn (nhiệt độ phòng, nằm điều hòa, ngủ an toàn chống đột tử SIDS: nằm ngửa, cũi thoáng, không gối chăn mềm), massage cho bé, và các mốc chăm sóc y tế thường quy (tiêm chủng tháng đầu, vitamin D3, sàng lọc sơ sinh). KHÔNG lấn sân: bú mẹ/sữa công thức (thuộc Nuôi con bằng sữa mẹ), lịch ngủ - luyện ngủ (thuộc Giấc ngủ của bé), bệnh lý (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Cha mẹ mới không thiếu bài "cách tắm bé" — họ thiếu bài dạy như dạy người CHƯA TỪNG BẾ TRẺ SƠ SINH, và xử lý được xung đột thực tế trong nhà. Khác biệt: (1) Hướng dẫn chi tiết đến mức thao tác (đỡ gáy bằng tay nào, xả nước bên nào trước) kèm lỗi người mới hay mắc ở từng bước — vì người đọc đang run tay thật; (2) Mỗi chủ đề đều xử lý trực diện "độ vênh" giữa khoa học hiện đại và cách ông bà làm: ủ ấm quá kỹ, rơ lưỡi bằng mật ong (nguy cơ ngộ độc botulinum — phải nói thẳng), nặn mụn sữa, đắp lá lên rốn, kiêng tắm — giải thích gốc quan niệm và đưa "kịch bản thương lượng" với ông bà thay vì chỉ phán đúng sai; (3) Chuẩn an toàn giấc ngủ SIDS và chuẩn tiêm chủng lấy theo khuyến cáo WHO/Bộ Y tế, nói rõ nguồn — tạo thế đứng vững trước lời khuyên truyền miệng.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn kỹ năng đầu đời: "cách tắm trẻ sơ sinh", "trẻ sơ sinh nằm điều hòa bao nhiêu độ", "chăm sóc rốn trẻ sơ sinh", "trị hăm tã", "quấn khăn cho trẻ sơ sinh đúng cách" — truy vấn ổn định quanh năm, đối thủ chủ yếu là bài bệnh viện khô cứng thiếu thao tác chi tiết. (2) Trở thành chuỗi bài cha mẹ mở đi mở lại trong tháng đầu (bookmark cao) — cấu trúc bài dạng các bước đánh số + ảnh minh họa để giữa đêm vẫn tra nhanh được. (3) Nội dung "khoa học vs ông bà" tạo khác biệt được chia sẻ trong hội nhóm. (4) Liên kết chặt trong cụm 0-3 tháng: bài tắm bé dẫn sang vàng da (Bệnh thường gặp), bài ngủ an toàn dẫn sang Giấc ngủ của bé — tăng chiều sâu phiên đọc.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Lần đầu tắm con mà tay run, sợ tuột, sợ nước vào tai — trình tự chuẩn là gì?"; "Rốn con 10 ngày chưa rụng, hơi ướt ở chân rốn — bình thường hay nhiễm trùng?"; "Bà bắt ủ con 3 lớp áo + quấn chăn giữa mùa hè, sờ lưng con toàn mồ hôi — nói bà không nghe"; "Con nằm điều hòa được không, bao nhiêu độ, có cần đội mũ đi tất không?"; "Da con nổi mụn trắng li ti, má sần đỏ, đầu đóng vảy vàng — có phải dị ứng sữa không, bôi gì được?"; "Hăm đỏ cả vùng mặc tã, bà bảo xức phấn rôm, mạng bảo phấn rôm hại phổi — tin ai?"; "Bà đòi rơ lưỡi cho con bằng mật ong/lá hẹ — nghe nói nguy hiểm mà không biết giải thích sao"; "Cắt móng tay cho con mà sợ phạm vào thịt"; "Con hay vặn mình đỏ mặt — thiếu canxi như bà nói hay bình thường?"; "Quên chưa cho con uống vitamin D3 mấy hôm có sao không?"; "Nhà nội bảo phải nằm than cho ấm — mình biết độc mà không cản được". Nền chung: mỗi thao tác đều là lần đầu, làm dưới ánh mắt giám sát của ông bà, và mọi vết đỏ trên da con đều thành nỗi sợ lúc nửa đêm.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Mẹo dân gian chữa X cho trẻ sơ sinh (đắp lá, nặn sữa vào mắt, mật ong rơ lưỡi, nằm than)" dạng hướng dẫn — nguy hiểm thực tế đã có ca ngộ độc/bỏng/nhiễm trùng; chỉ viết dạng bài cảnh báo giải thích cơ chế rủi ro. "Trọn bộ 60 món đồ sơ sinh phải mua" — bài mua sắm affiliate, gây lãng phí; khía cạnh sản phẩm cụ thể thuộc danh mục Đánh giá sản phẩm. "Luyện ngủ cho bé từ tuần đầu" — thuộc Giấc ngủ của bé, không viết ở đây. "Cách chữa vàng da/sốt/nghẹt mũi" — thuộc Bệnh thường gặp; chuyên mục này chỉ dừng ở nhận biết ban đầu và chỉ dấu đi khám. "Phương pháp EASY/4S/5S trọn bộ" dạng tôn sùng một trường phái — cộng đồng mẹ Việt đang chia phe gay gắt; chỉ lấy kỹ thuật cụ thể có bằng chứng (vỗ ợ, quấn khăn, white noise) trình bày trung lập, không gắn nhãn trường phái để tránh war và tránh bó người đọc vào giáo điều.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-35 tuổi có con đầu lòng 0-3 tháng, chưa từng chăm trẻ sơ sinh trước đó; mẹ đang ở cữ tại nhà (thường có bà nội/ngoại cùng chăm và hay bất đồng cách làm), bố tham gia buổi tối; tra cứu bằng điện thoại một tay ngay TRƯỚC KHI làm thao tác (sắp tắm bé, sắp cắt móng) hoặc giữa đêm khi thấy dấu hiệu lạ trên da, rốn, hơi thở của con.',

            'constraints' => 'Không dùng từ chuyên môn không giải thích; hướng dẫn phải chia bước đánh số làm theo được ngay, nêu rõ lỗi thường gặp; không phán xét hay chế giễu cách chăm truyền thống — phân tích và đưa cách nói chuyện với ông bà; mẹo dân gian nguy hiểm phải cảnh báo thẳng, dẫn nguồn y khoa (Bộ Y tế, WHO, AAP); luôn có ngưỡng "dấu hiệu cần đi khám"; không quảng cáo sản phẩm chăm sóc da, sữa tắm.',

            'style_sample' => <<<'TEXT'
                Hôm nay là ngày đầu tiên bạn tự tắm cho con, không còn cô hộ sinh nào đứng cạnh. Tay bạn hơi run — và điều đó hoàn toàn bình thường: một em bé ba tuần tuổi trơn như cá khi dính nước, ai lần đầu cũng sợ. Nhưng có một bí mật khiến mọi thứ dễ hơn hẳn: trẻ sơ sinh không hề bẩn như ta tưởng. Con chưa nghịch cát, chưa đổ mồ hôi chua — nên 2-3 lần tắm mỗi tuần là đủ, những ngày còn lại chỉ cần lau người, và mỗi lần tắm chỉ cần gọn trong 5-7 phút. Nghĩa là áp lực "tắm cho sạch, tắm cho lâu" mà bà đang đứng cạnh nhắc bạn — có thể buông bớt được rồi. Việc của mình bây giờ chỉ là làm đúng trình tự an toàn: chuẩn bị sẵn mọi thứ trong tầm với TRƯỚC khi cởi đồ con (khăn, quần áo, tã — vì bạn sẽ không có tay nào rảnh nữa), nước ấm 37 độ thử bằng khuỷu tay, rửa mặt trước - gội đầu sau - thân mình cuối cùng, và một tay LUÔN đỡ dưới gáy con từ đầu đến cuối. Giờ mình đi từng bước một nhé, có cả phần "nếu con khóc giữa chừng thì sao" — vì gần như chắc chắn con sẽ khóc, và điều đó cũng bình thường nốt.
                TEXT,
        ],

        // === Trẻ sơ sinh (0-3 tháng) > Phát triển của trẻ ===
        [
            'parent_slug' => 'tre-so-sinh-0-3-thang',
            'slug'        => 'phat-trien-cua-tre',

            'core_focus' => <<<'TEXT'
                Sự phát triển của bé 0-3 tháng theo từng tuần/tháng và cách cha mẹ đồng hành đúng lứa tuổi: các mốc vận động - giác quan - giao tiếp (khi nào con nhìn theo, hóng chuyện, cười xã giao, ngóc đầu, phát hiện ra bàn tay mình), tăng trưởng cân nặng - chiều cao theo chuẩn WHO và CÁCH ĐỌC biểu đồ percentile (con ở kênh 25% vẫn bình thường — chống lại văn hóa so cân nặng), thời gian nằm sấp (tummy time) tập cổ an toàn, kích thích giác quan đúng cách (nói chuyện, hát, tranh tương phản đen trắng — không cần đồ chơi đắt tiền), hiểu ngôn ngữ của trẻ sơ sinh (các kiểu khóc, tín hiệu đói - buồn ngủ - quá tải kích thích), giai đoạn wonder weeks/growth spurt khiến con bám mẹ gắt gỏng, và ranh giới bình thường - cần theo dõi (khi nào đáng đưa đi khám: không nhìn theo, không phản ứng âm thanh, trương lực cơ bất thường). KHÔNG lấn sân: kỹ năng chăm sóc hằng ngày (thuộc Chăm sóc trẻ sơ sinh), bú - ngủ - bệnh (các chuyên mục con còn lại).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung mốc phát triển tiếng Việt hoặc dịch máy từ CDC/AAP, hoặc thành bảng "chuẩn" cứng nhắc khiến cha mẹ đối chiếu như chấm thi. Khác biệt: (1) Mỗi mốc luôn trình bày kèm KHOẢNG dao động bình thường và triết lý "mốc là khoảng, không phải deadline" — giảm lo âu thay vì tạo thêm; đồng thời nói rõ ngưỡng nào mới thật sự cần đi khám (red flags theo AAP), không ba phải; (2) Đánh thẳng vào văn hóa so sánh cân nặng của người Việt — "con em 3 tháng 6kg có còi không chị?" — bằng cách dạy đọc percentile và đường cong tăng trưởng của CHÍNH con thay vì so hàng xóm; (3) Mục "chơi với con tuần này" dùng đồ có sẵn trong nhà Việt (khăn xô, chai nhựa bỏ gạo) thay vì danh sách đồ chơi giáo dục đắt tiền — hoạt động 5-10 phút vừa sức cha mẹ đang thiếu ngủ.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Chuỗi bài trụ cột "Bé N tuần/tháng tuổi" (0-12 tuần) kéo cha mẹ quay lại định kỳ như đã làm với chuỗi tuần thai — chuyển tiếp tự nhiên cho độc giả từ Sự phát triển của thai nhi. (2) SEO truy vấn lo âu so sánh: "trẻ 2 tháng biết làm gì", "trẻ 3 tháng chưa biết lẫy có sao không", "bảng cân nặng trẻ sơ sinh chuẩn WHO", "trẻ mấy tháng biết hóng chuyện", "tummy time là gì". (3) Giảm nhu cầu hỏi hội nhóm bằng bài "đọc biểu đồ tăng trưởng" — nội dung giáo dục nền tảng ít ai làm kỹ. (4) Dẫn luồng sang Bệnh thường gặp khi chạm red flags và sang chuyên mục 3-12 tháng khi con qua mốc — giữ độc giả trong hành trình dài của site.
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

            'core_focus' => <<<'TEXT'
                Đồng hành thực tế với hành trình sữa mẹ từ cữ bú đầu tiên: khớp ngậm đúng và các tư thế cho bú (kèm cách nhận biết - sửa khớp ngậm sai gây đau nứt đầu ti), cơ chế cung - cầu của sữa mẹ và cách gọi sữa về sau sinh (đặc biệt sau sinh mổ), nhận biết con bú ĐỦ (số tã ướt, cân nặng — thay cho cảm giác "hình như ít sữa" khiến 70% mẹ bỏ cuộc oan), xử lý sự cố: cương sữa, tắc tia (mẹo chườm - massage đúng, khi nào cần thông tia), nứt đầu ti, viêm vú, con chê ti - bú vặt - gắt bú, kích sữa và hút sữa đúng cách (chọn chế độ, lịch hút cho mẹ đi làm lại, bảo quản - rã đông sữa chuẩn), ăn uống của mẹ cho bú (thực hư móng giò - chè vằng - lá đinh lăng, mẹ ăn gì con đau bụng?), cai sữa văn minh, và phần KHÔNG PHÁN XÉT: khi mẹ không đủ sữa hoặc chọn sữa công thức — cách kết hợp, cách chọn, cách pha đúng. KHÔNG lấn sân: lịch ngủ (Giấc ngủ của bé), bệnh của bé (Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Mảng sữa mẹ tiếng Việt bị chia đôi chiến tuyến: phe "sữa mẹ bằng mọi giá" phán xét mẹ bú bình, và phe quảng cáo sữa công thức rình mẹ yếu lòng. Chuyên mục đứng hẳn về phía NGƯỜI MẸ: (1) Ủng hộ sữa mẹ hết mình về mặt kỹ thuật (bài khớp ngậm, gọi sữa, kích sữa chi tiết nhất có thể) nhưng tuyệt đối không tội lỗi hóa mẹ thiếu sữa — có hẳn tuyến bài "nuôi con bằng sữa công thức không phải thất bại"; (2) Đánh trúng "khủng hoảng ảo giác ít sữa" — nguyên nhân số 1 khiến mẹ Việt bỏ bú mẹ sớm: dạy đếm tã, theo dõi cân thay vì nghe bà nội phán "sữa mày trong, nóng, con bú không no"; (3) Thực chiến cho mẹ công sở đi làm lại sau 6 tháng thai sản — lịch hút sữa tại văn phòng, quyền vắt sữa theo luật lao động (nối sang Quyền lợi & pháp lý), trữ đông - vận chuyển; kịch bản gần như mọi mẹ Việt gặp mà bài dịch từ Tây không cover.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn khủng và cảm xúc cao: "cách gọi sữa về nhanh", "tắc tia sữa phải làm sao", "khớp ngậm đúng", "trẻ sơ sinh bú bao nhiêu là đủ", "bảo quản sữa mẹ được bao lâu", "mẹ ăn gì để nhiều sữa". (2) Giữ chân mẹ qua từng khủng hoảng sữa (tuần 1: gọi sữa; tuần 3-6: ảo giác ít sữa; tháng 5-6: đi làm lại) bằng chuỗi bài theo giai đoạn — mỗi khủng hoảng được vượt qua là một lần niềm tin với site sâu thêm. (3) Trở thành nguồn trung lập hiếm hoi không bán sữa, không bán khóa kích sữa — định vị được chia sẻ mạnh trong hội nhóm. (4) Chuyển tiếp mượt sang Ăn dặm & dinh dưỡng (3-12 tháng) khi con gần 6 tháng.
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

            'core_focus' => <<<'TEXT'
                Mọi thứ về giấc ngủ của bé 0-3 tháng và sự sống còn của giấc ngủ cha mẹ: hiểu giấc ngủ sơ sinh khác người lớn thế nào (chu kỳ ngắn 40-50 phút, ngủ ngày cày đêm do chưa có nhịp sinh học, ngủ REM nhiều nên vặn mình è è là bình thường), tổng thời gian ngủ theo tuần tuổi và dấu hiệu buồn ngủ cần bắt trước khi con gắt (over-tired), thiết lập nếp ngủ nhẹ nhàng từ sớm: phân biệt ngày - đêm, trình tự trước giờ ngủ, môi trường ngủ (tối, white noise, nhiệt độ), quấn khăn và mốc phải bỏ quấn (khi con biết lật), AN TOÀN giấc ngủ chống đột tử SIDS theo chuẩn AAP (nằm ngửa, nôi/cũi thoáng, không gối - chăn mềm - nằm sấp; đối thoại thẳng với thực tế ngủ chung giường phổ biến ở Việt Nam: nếu ngủ chung thì giảm rủi ro thế nào), xử lý tình huống: con gắt ngủ khóc dai, ngủ ngày 30 phút dậy, lẫn lộn ngày đêm, chỉ ngủ trên tay - đặt xuống là dậy, và khủng hoảng ngủ tháng thứ 4 (chuẩn bị tâm lý trước). KHÔNG lấn sân: bú đêm thuộc phối hợp với Nuôi con bằng sữa mẹ; bệnh làm con khó ngủ (thuộc Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Nội dung giấc ngủ trẻ em tiếng Việt đang bị các "trung tâm luyện ngủ" chiếm sóng — mọi bài đều dẫn về khóa học nghìn đô với lời hứa "con ngủ xuyên đêm 12 tiếng". Khác biệt: (1) Đặt kỳ vọng THẬT: trẻ 0-3 tháng dậy đêm ăn là sinh lý bình thường và cần thiết, "ngủ xuyên đêm" ở tuổi này là ngoại lệ chứ không phải mục tiêu — giải phóng cha mẹ khỏi cảm giác thất bại; (2) Trung lập với các trường phái (EASY, tự ngủ, bế ru, ngủ chung) — mô tả được - mất của từng lựa chọn theo bằng chứng, tôn trọng hoàn cảnh từng nhà thay vì giáo điều; đặc biệt KHÔNG phán xét bế ru và ngủ chung như các nguồn dịch Tây, vì đó là thực tế của đa số gia đình Việt (kèm hướng dẫn ngủ chung giảm rủi ro); (3) Nhìn giấc ngủ của con và của MẸ như một hệ — mẹ ngủ đủ mới sống sót qua giai đoạn này, nên mọi giải pháp đều cân nhắc chi phí giấc ngủ của người lớn, kể cả phương án "chấp nhận bế ru thêm tháng nữa" nếu điều đó tốt cho cả nhà.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn tuyệt vọng lúc nửa đêm: "trẻ sơ sinh gắt ngủ khóc thét", "trẻ ngủ ngày cày đêm phải làm sao", "trẻ sơ sinh đặt xuống là dậy", "trẻ 2 tháng ngủ bao nhiêu tiếng", "white noise cho trẻ sơ sinh", "khủng hoảng ngủ 4 tháng". (2) Là nguồn MIỄN PHÍ đáng tin thay thế khóa luyện ngủ tiền triệu — nội dung đủ chi tiết để tự làm; định vị này lan mạnh trong hội nhóm. (3) Bài an toàn giấc ngủ SIDS bản địa hóa cho bối cảnh ngủ chung của người Việt là nội dung trách nhiệm xã hội tạo uy tín khác biệt. (4) Chuỗi "chuẩn bị cho tuần khủng hoảng" giữ độc giả quay lại theo mốc tuổi của con, chuyển tiếp sang giấc ngủ 3-12 tháng ở chuyên mục Chăm sóc trẻ nhỏ.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con ngủ cả ngày, đêm thức chơi từ 1h đến 4h sáng — hai vợ chồng thay nhau bế mà sắp gục"; "Đặt xuống cũi là khóc, bế lên lại ngủ — cứ thế cả đêm, tay mình sắp gãy, có phải con hư tại được bế nhiều như bà nói?"; "Con gắt ngủ khóc tím mặt, càng ru càng gào — mình làm sai gì?"; "Ngủ ngày cứ đúng 30 phút là dậy, không kịp làm gì cả"; "Con è è vặn mình cả đêm, mặt đỏ gay — thiếu canxi hay bình thường?"; "Nhà bảo cho con nằm sấp ngủ ngon hơn — mà mạng nói nằm sấp đột tử, ai đúng?"; "Cả nhà ngủ chung giường từ xưa, giờ đọc thấy phải ngủ cũi riêng — có bắt buộc không, nhà chật thì sao?"; "Có nên quấn con không, quấn đến bao giờ, con cứ giãy ra khỏi khăn"; "Trung tâm luyện ngủ báo giá 8 triệu cam kết con tự ngủ — có đáng tiền không hay tự làm được?"; "Đọc về khủng hoảng tháng thứ 4 mà sợ — chuẩn bị gì trước được không?"; "Mình thèm ngủ đến mức gật gù lúc bế con — nguy hiểm không, làm sao chia ca?". Nền chung: thiếu ngủ là khủng hoảng số 1 của giai đoạn này, cha mẹ đọc trong tuyệt vọng lúc 3h sáng và là mồi ngon của mọi lời hứa "ngủ xuyên đêm" có phí.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Luyện con tự ngủ bằng cry-it-out cho trẻ dưới 4 tháng" — mọi trường phái đều thống nhất KHÔNG để trẻ dưới 4-6 tháng khóc một mình có chủ đích; không viết dạng hướng dẫn ở cụm 0-3 tháng. "Mẹo dân gian cho bé ngủ ngon: đốt vía, treo tỏi, xông phòng" — không bằng chứng, một số có rủi ro (khói); chỉ viết dạng bài kiểm chứng nhẹ nhàng. "Review máy đưa nôi tự động, camera AI theo dõi thở" — thuộc Đánh giá sản phẩm; hơn nữa thiết bị theo dõi thở tạo an toàn giả, cần bài phân tích riêng đúng bằng chứng chứ không phải review khen. "Lịch sinh hoạt EASY chuẩn từng phút theo tuần tuổi" dạng giáo án cứng — trẻ sơ sinh không chạy theo lịch của app, lịch cứng tạo cảm giác thất bại; chỉ viết nhịp sinh hoạt linh hoạt theo tín hiệu của con. "Thuốc/siro giúp bé ngủ ngon" — nguy hiểm (kháng histamine cho trẻ sơ sinh), tuyệt đối không; chỉ có bài cảnh báo. "So sánh xếp hạng trung tâm luyện ngủ" — không tiếp tay thị trường chưa được kiểm soát; viết bài trang bị kiến thức tự đánh giá thay thế.
                TEXT,

            'audience' => 'Cha mẹ Việt 25-35 tuổi có con 0-3 tháng (đỉnh khủng hoảng ngủ ở tuần 2-8), con đầu lòng; cả hai đều thiếu ngủ trầm trọng, mẹ đọc lúc bế con ru đêm 1-4h sáng, bố tra "trẻ gắt ngủ" trong giờ làm; chịu áp lực hai chiều: ông bà bảo "bế nhiều quen tay, kệ nó khóc", mạng thì dọa "để khóc hại não" — không biết đường nào; sẵn sàng chi tiền triệu cho bất cứ thứ gì hứa hẹn được ngủ, nên rất dễ bị khóa học/thiết bị moi tiền.',

            'constraints' => 'Không hứa hẹn "ngủ xuyên đêm" cho trẻ dưới 4 tháng; không phán xét bế ru, ngủ chung, hay bất kỳ lựa chọn nào của gia đình; an toàn SIDS là ranh giới cứng không thỏa hiệp nhưng trình bày không dọa nạt; không bán/gợi ý khóa luyện ngủ, thiết bị, siro ngủ; bài phải đọc nổi lúc kiệt sức — kết luận trước, các bước đánh số, có phần "làm ngay đêm nay"; dẫn nguồn AAP/NHS/WHO; luôn nhắc chăm sóc giấc ngủ của chính cha mẹ.',

            'style_sample' => <<<'TEXT'
                3 giờ 40 phút sáng. Con vừa bú xong, mắt nhắm tịt trên vai bạn, thở đều như một thiên thần — và bạn bắt đầu nghi thức quen thuộc: hạ con xuống nôi chậm như tháo bom, từng centimet một, nín thở… và đúng khoảnh khắc lưng con chạm đệm, hai mắt ấy mở bừng ra. Lại bế lên. Lại từ đầu. Nếu bạn đang đọc những dòng này bằng một tay trong tư thế đó, thì trước hết: bạn không làm gì sai, và con bạn cũng không "hư vì được bế nhiều" đâu. Trẻ sơ sinh có một cơ chế rất thật gọi là phản xạ giật mình — khi cảm giác được ôm ấm đột ngột biến mất, não con báo động như thể đang rơi. Cộng thêm việc 20 phút đầu giấc con vẫn ở pha ngủ nông, thì "đặt xuống là dậy" gần như được lập trình sẵn. Tin tốt: có vài cách đánh lừa cơ chế ấy mà các nữ hộ sinh vẫn truyền tay nhau — đợi đủ dấu hiệu ngủ sâu (tay con rơi thõng như sợi bún), đặt con xuống NGHIÊNG người rồi mới xoay ngửa, giữ tay trên ngực con thêm 30 giây như một lời "mẹ vẫn ở đây". Mình sẽ đi từng bước một, kèm cả phương án B rất đáng nói: nếu đêm nay tất cả đều thất bại, bế con ngủ thêm vài tuần nữa không làm hỏng con — nó chỉ làm mỏi tay bạn, và mình có cách chia ca cho đỡ mỏi ở cuối bài.
                TEXT,
        ],

        // === Trẻ sơ sinh (0-3 tháng) > Bệnh thường gặp ===
        [
            'parent_slug' => 'tre-so-sinh-0-3-thang',
            'slug'        => 'benh-thuong-gap',

            'core_focus' => <<<'TEXT'
                Các vấn đề sức khỏe hay gặp nhất ở bé 0-3 tháng, viết theo đúng logic cha mẹ cần lúc lo lắng: đây là gì → mức độ nào bình thường → chăm tại nhà thế nào → NGƯỠNG NÀO đi khám ngay. Trọng tâm: vàng da sơ sinh (sinh lý vs bệnh lý, ngưỡng chiếu đèn), sốt ở trẻ dưới 3 tháng (nguyên tắc thép: dưới 3 tháng sốt ≥38°C là đi viện, không tự hạ sốt ở nhà), nghẹt mũi - thở khò khè - hắt hơi (khi nào là bình thường do mũi bé, cách vệ sinh mũi đúng, hút mũi có hại không), nôn trớ - trào ngược sinh lý vs nôn vọt bất thường, khóc dạ đề/colic (cách sống chung và loại trừ nguyên nhân khác), táo bón - són phân - phân hoa cà hoa cải (đọc màu phân: khi nào bình thường, khi nào báo động), viêm da - chàm sữa - hăm nặng, đau bụng - đầy hơi - vặn mình, nấm miệng, viêm mắt - tắc lệ đạo, và các mốc tiêm chủng 0-3 tháng kèm chăm sóc sau tiêm (sốt sau tiêm 6in1). KHÔNG lấn sân: chăm sóc thường quy khỏe mạnh (thuộc Chăm sóc trẻ sơ sinh), vấn đề bú (thuộc Nuôi con bằng sữa mẹ).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Bài bệnh trẻ em tiếng Việt hoặc của bệnh viện (chuẩn nhưng viết cho đồng nghiệp đọc), hoặc của trang tin (xào nấu, kết bài nào cũng "đưa trẻ đến cơ sở y tế gần nhất" vô thưởng vô phạt). Khác biệt: (1) Mỗi bài xây quanh MỘT bảng phân tầng rõ ràng ba mức "theo dõi ở nhà / khám trong 24h / đi viện NGAY" với dấu hiệu cụ thể quan sát được (con số, màu sắc, hành vi) — trả lời đúng câu hỏi thật của cha mẹ lúc nửa đêm: "có phải đi viện bây giờ không?"; (2) Giải oan cho các hiện tượng sinh lý bị bệnh-hóa (vặn mình đỏ mặt, phân hoa cà, hắt hơi, nấc) — giảm những chuyến đi viện không cần thiết và những liều canxi/men vi sinh vô ích mà hàng xóm mách; (3) Đối đầu tử tế với mẹo dân gian nguy hiểm ở đúng bối cảnh bệnh: uống nước lá chữa vàng da (chậm trễ chiếu đèn gây biến chứng não), mật ong cho trẻ ho, chích lể — nói thẳng hậu quả bằng ca thực tế đã được báo chí y tế ghi nhận, không mỉa mai người mách.
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

            'core_focus' => <<<'TEXT'
                Danh mục CHA của cụm 3-12 tháng — chứa các bài TỔNG QUAN xuyên suốt giai đoạn con "ra khỏi tổ": cẩm nang từng quý (3-6, 6-9, 9-12 tháng: mỗi quý con thay đổi gì, cha mẹ cần chuẩn bị gì), bước ngoặt mẹ đi làm lại sau thai sản 6 tháng (chọn người trông: ông bà - giúp việc - nhóm trẻ; chuẩn bị con quen người mới; sữa và ăn dặm khi mẹ vắng nhà; cảm giác tội lỗi của mẹ), an toàn trong nhà khi con biết lẫy - bò - vịn đứng (child-proofing kiểu nhà Việt: cầu thang, ổ điện, phích nước, bàn thờ), lịch tổng hợp tiêm chủng - khám định kỳ 3-12 tháng, đưa con ra ngoài: về quê, đi máy bay lần đầu, và sinh nhật 1 tuổi (thôi nôi, ý nghĩa - tổ chức vừa sức). Chi tiết ăn dặm - mốc phát triển - bệnh - chăm sóc hằng ngày KHÔNG viết ở đây — đẩy xuống 4 chuyên mục con.
                TEXT,

            'unique_angle' => <<<'TEXT'
                Giai đoạn 3-12 tháng có một sự kiện chi phối mọi thứ mà nội dung mẹ-bé Việt gần như bỏ trống: MẸ ĐI LÀM LẠI ở tháng thứ 6-7. Khác biệt của danh mục: (1) Xây tuyến bài "bàn giao con" tử tế nhất thị trường — chọn và làm việc với người trông (ông bà lên chăm cháu: thỏa thuận thế nào để bà không thành osin và mẹ không thành người ngoài; thuê giúp việc: hợp đồng, camera, ranh giới; gửi nhóm trẻ tư trước 12 tháng: tiêu chí an toàn tối thiểu) — quyết định lớn nhất, ít được trợ giúp nhất của năm đầu; (2) An toàn nhà cửa viết theo hiện trạng nhà Việt thật (nhà ống cầu thang dốc, phích nước sôi trên bàn, xe máy trong nhà, bàn thờ nến hương) chứ không dịch checklist nhà Mỹ; (3) Giọng "quý nào việc nấy" giúp cha mẹ đi trước con một bước thay vì chạy theo — mỗi bài quý đều có mục "tháng tới con sẽ làm bạn bất ngờ vì...".
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Hai bài pillar "Mẹ đi làm lại" và "An toàn nhà cửa theo giai đoạn bò - đứng - đi" là nội dung khác biệt định vị site, mục tiêu được share trong hội nhóm mẹ bỉm quay lại công sở. (2) SEO truy vấn giai đoạn: "chuẩn bị đi làm lại sau thai sản", "có nên thuê giúp việc trông con", "gửi trẻ 10 tháng được không", "trẻ mấy tháng biết bò", "đi máy bay với trẻ dưới 1 tuổi". (3) Điều phối luồng đọc xuống 4 chuyên mục con và giữ nhịp quay lại theo quý tuổi của con. (4) Chuyển giao độc giả mượt sang cụm Trẻ tập đi (1-3 tuổi) sau sinh nhật 1 tuổi — tiếp tục chuỗi hành trình mà site theo đuổi từ thai kỳ.
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

            'core_focus' => <<<'TEXT'
                Sự phát triển của bé 3-12 tháng — năm của các mốc vận động lớn: lẫy (3-6 tháng), ngồi (6-8), bò (7-10), vịn đứng - men đồ (9-12), và có thể những bước đi đầu tiên; kèm mốc tinh tế quan trọng không kém: cầm nắm chuyền tay (4-6), nhặt bằng hai ngón (9-12), bập bẹ "ba ba ma ma" (6-9), gọi tên có quay lại - vẫy tay - chỉ trỏ (9-12), lo lắng khi xa mẹ và sợ người lạ (6-12 — giải thích đây là mốc TỐT của gắn bó, không phải "hư"). Với mỗi mốc: khoảng tuổi bình thường (luôn là KHOẢNG rộng), cách tạo điều kiện cho con tập (không gian sàn, trò chơi tương tác bằng đồ trong nhà), điều KHÔNG nên làm (xe tròn tập đi, đỡ ngồi sớm, ép tập đứng), red flags theo chuẩn AAP/CDC cần khám phát triển (6 tháng chưa lẫy chưa với đồ, 9 tháng chưa ngồi vững chưa bập bẹ, 12 tháng chưa chỉ trỏ không phản ứng gọi tên), và đọc biểu đồ tăng trưởng giai đoạn tốc độ tăng cân chậm lại (giải oan "con dạo này còi đi"). KHÔNG lấn sân: ăn dặm (chuyên mục riêng), chăm sóc - luyện ngủ (Chăm sóc trẻ nhỏ), bệnh (Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Đây là giai đoạn văn hóa so sánh của người Việt hoạt động hết công suất: "3 tháng biết lẫy, 7 tháng biết bò, 9 tháng lò dò biết đi" — câu ca dao thành thước đo cứng khiến hàng nghìn mẹ lo lắng oan. Khác biệt: (1) Đối thoại thẳng với các "chuẩn dân gian" (câu ca dao trên, "trốn lẫy", "chân vòng kiềng do đóng bỉm") — giải thích khoa học đằng sau, mốc nào là khoảng rộng, hiện tượng nào là bình thường (trẻ bỏ qua bò đi thẳng, chân cong sinh lý dưới 2 tuổi); (2) Chống can thiệp sai phổ biến ở Việt Nam bằng bằng chứng: xe tròn tập đi (AAP khuyến cáo cấm — làm chậm biết đi và gây tai nạn), đỡ ngồi - xốc nách tập đứng sớm, địu ngồi sai tư thế — các bài này cứu cha mẹ khỏi mua sắm và tập luyện có hại; (3) Ngôn ngữ và giao tiếp sớm được đặt ngang vận động — cha mẹ Việt chỉ đếm mốc lẫy-bò-đi mà bỏ qua bập bẹ, chỉ trỏ, phản ứng gọi tên: chính là các mốc quan trọng nhất để phát hiện sớm vấn đề thính lực và phát triển.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) Chuỗi bài trụ cột "Bé N tháng tuổi biết làm gì" (3→12 tháng) tiếp nối chuỗi 0-3 tháng, giữ độc giả quay lại hằng tháng. (2) SEO truy vấn so sánh - lo âu volume lớn: "trẻ 6 tháng chưa biết lẫy", "trẻ 9 tháng chưa mọc răng", "trẻ mấy tháng biết ngồi", "trẻ đi chân vòng kiềng", "xe tập đi có tốt không", "trẻ 11 tháng chưa biết đi có sao không". (3) Bài "xe tròn tập đi" và "chuẩn ca dao vs chuẩn y khoa" là nội dung phản đề tạo khác biệt và được trích dẫn. (4) Red flags trình bày chuẩn mực dẫn sang hành động khám phát triển đúng nơi — xây uy tín nội dung có trách nhiệm; luồng đọc nối sang Phát triển của trẻ (1-3 tuổi) khi con tròn 1 tuổi.
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

            'core_focus' => <<<'TEXT'
                Toàn bộ hành trình ăn dặm 6-12 tháng (và chuẩn bị từ tháng 5): dấu hiệu con SẴN SÀNG ăn dặm (ngồi vững, hết phản xạ đẩy lưỡi — thay vì "cứ tròn 6 tháng" hay "4 tháng cho ăn sớm cứng cáp"), bản đồ trung lập 3 trường phái (ăn dặm truyền thống, kiểu Nhật, BLW bé tự chỉ huy — ưu nhược từng kiểu, kết hợp thế nào, chọn theo hoàn cảnh nhà mình), lộ trình thô mịn theo tháng (6-7-8-10-12: độ thô, số bữa, lượng ăn tham khảo), thực đơn mẫu nguyên liệu chợ Việt theo tuần, nhóm chất và thực phẩm giàu SẮT (mối thiếu hụt số 1 tuổi này), thực phẩm cần tránh trước 1 tuổi (mật ong, muối - nước mắm, sữa bò tươi, hạt nguyên), an toàn hóc nghẹn (cắt đồ ăn đúng cách, phân biệt ọe sinh lý vs hóc, sơ cứu Heimlich cho trẻ nhỏ), uống nước - sữa song song ăn dặm, và xử lý khủng hoảng: con ăn ít, biếng ăn sinh lý, ném đồ ăn, ĂN RONG - XEM TIVI KHI ĂN (cuộc chiến lớn nhất với ông bà), dị ứng thực phẩm (nguyên tắc thử món mới, dấu hiệu dị ứng). KHÔNG lấn sân: sữa mẹ/công thức chuyên sâu (Nuôi con bằng sữa mẹ), cân nặng - biểu đồ (Phát triển của trẻ), tiêu chảy - táo bón bệnh lý (Bệnh thường gặp).
                TEXT,

            'unique_angle' => <<<'TEXT'
                Ăn dặm là chiến trường khốc liệt nhất của nội dung mẹ-bé Việt: các phe truyền thống - kiểu Nhật - BLW cãi nhau như tôn giáo, trong khi bà và mẹ trong cùng một nhà cũng đang cãi nhau về bát cháo. Khác biệt: (1) TRUNG LẬP có nguyên tắc: không tôn sùng trường phái nào, chỉ giữ các nguyên tắc bất biến có bằng chứng (đủ sắt, tăng thô đúng nhịp, không ép ăn, ăn có ghế có giờ) — còn hình thức thì hướng dẫn kết hợp linh hoạt kiểu "truyền thống buổi trưa cho bà đút, BLW bữa tối cùng cả nhà"; (2) Xử lý trực diện văn hóa ăn uống Việt quanh đứa trẻ: ăn rong đầu ngõ, bật tivi cho há mồm, nước hầm xương "đủ chất", ép hết bát mới thôi, so cân nặng — mỗi thứ một bài phân tích + kịch bản thương lượng với ông bà, vì mẹ thắng lý thuyết mà thua bữa cơm trưa ở nhà với bà; (3) An toàn hóc nghẹn được nâng thành tuyến bài bắt buộc (các site khác chỉ nói lướt): cắt quả nho, xử lý xương cá, sơ cứu — kỹ năng cứu mạng mà gần như không cha mẹ Việt nào được dạy.
                TEXT,

            'content_goals' => <<<'TEXT'
                (1) SEO cụm truy vấn dày đặc nhất của năm đầu đời: "thực đơn ăn dặm cho bé 6/7/8 tháng", "ăn dặm BLW là gì", "bé 7 tháng ăn được gì", "cách nấu cháo cho bé", "bé ăn dặm bị táo bón", "trẻ 8 tháng biếng ăn" — cạnh tranh bằng độ thực dụng (nguyên liệu chợ Việt, ảnh độ thô từng tháng). (2) Bộ thực đơn tuần theo tháng tuổi là nội dung bookmark/in ra dán tủ lạnh — đo bằng lượt lưu và quay lại. (3) Tuyến bài "thương lượng với ông bà về bữa ăn" và "an toàn hóc nghẹn" là nội dung khác biệt được share mạnh. (4) Nối luồng: dấu hiệu sẵn sàng ↔ Phát triển của trẻ (ngồi vững), biếng ăn bệnh lý ↔ Bệnh thường gặp, và chuyển sang Dinh dưỡng cho trẻ (1-3 tuổi) khi con tròn 1 tuổi — vốn là giai đoạn "ăn cơm cùng nhà" nhiều vấn đề mới.
                TEXT,

            'pain_points' => <<<'TEXT'
                "Con 5 tháng rưỡi nhìn mồm người lớn tóp tép — cho ăn dặm sớm được chưa hay đợi đủ 6 tháng, bà bảo ngày xưa 4 tháng đã ăn bột?"; "Chọn truyền thống hay BLW đây — theo BLW thì bà không dám cho ăn sợ hóc, theo truyền thống thì mạng dọa con không biết nhai"; "Con ăn cứ ọe — bình thường hay sắp hóc, phân biệt sao, lỡ hóc thật thì làm gì?"; "Nấu cháo có được nêm mắm muối không, bà bảo nhạt thế ai ăn được"; "Bà hầm xương lấy nước nấu cháo cả tuần bảo đủ chất — mình nói không có chất bà không tin"; "Con 8 tháng đột nhiên ăn ít hẳn, nhè hết — biếng ăn sinh lý là gì, kéo dài bao lâu?"; "Cả nhà bế cháu ăn rong đầu ngõ, bật tivi mới há mồm — mình muốn ngồi ghế ăn nghiêm chỉnh mà một mình chống lại cả nhà"; "Mỗi bữa bà ép hết bát cháo, con khóc vẫn đút — mình xót mà không dám nói"; "Thử món mới thấy quanh miệng nổi đỏ — dị ứng hay dặm nước dãi, khi nào nguy hiểm?"; "Con ném thức ăn xuống sàn cười khanh khách — kệ hay phạt?"; "Lượng ăn bao nhiêu là đủ — con ăn 3 thìa mà hội nhóm khoe con họ hết bát tô". Nền chung: bữa ăn của con là nơi va chạm thế hệ gay gắt nhất trong nhà, và mẹ thường là người thua vì ban ngày không có mặt.
                TEXT,

            'rejected_ideas' => <<<'TEXT'
                "Trường phái X là tốt nhất, phân tích tại sao Y sai" — nuôi war tôn giáo ăn dặm, mất một nửa độc giả; chỉ viết trung lập kết hợp. "Thực đơn ăn dặm cho bé TĂNG CÂN VÙ VÙ" — tư duy nhồi cân phản khoa học đang là nỗi ám ảnh có hại của gia đình Việt; thay bằng bài "cân nặng bao nhiêu là đủ" theo percentile. "Bột ăn dặm/váng sữa/phô mai nào tốt nhất" dạng xếp hạng — thuộc Đánh giá sản phẩm, và váng sữa cần bài bóc tách riêng (bản chất là kem béo, không phải "tinh túy sữa" như quảng cáo). "Gia vị ăn dặm cho bé dưới 1 tuổi (mắm nhĩ, hạt nêm trẻ em)" dạng khuyên dùng — dưới 1 tuổi không cần nêm, "hạt nêm cho bé" là lách marketing; viết bài phản biện. "Mẹo cho con ăn hết bát: xem tivi, ăn rong có kiểm soát" — thỏa hiệp với thói quen có hại, đi ngược nguyên tắc ăn chủ động; chỉ viết lộ trình cai. "Nước hầm xương/nước dashi thần thánh" dạng tôn vinh — dashi ok như nước nấu nhưng không thay được đạm thật; viết đúng vai trò, không thần thánh hóa.
                TEXT,

            'audience' => 'Mẹ Việt 26-36 tuổi có con 5-12 tháng bắt đầu ăn dặm — thời điểm trùng khớp đi làm lại nên bữa ăn ban ngày do bà/giúp việc đảm nhận theo cách cũ (ăn rong, tivi, ép ăn), mẹ chỉ nấu và cho ăn được bữa tối + cuối tuần; đọc công thức buổi tối để chuẩn bị đồ ăn hôm sau, tra cứu khẩn khi con ọe/nổi mẩn/biếng ăn; áp lực lớn nhất: cân nặng của con bị cả họ theo dõi như KPI và mọi bữa con ăn ít đều bị quy về "tại mẹ cho ăn kiểu mới".',

            'constraints' => 'Trung lập giữa các trường phái ăn dặm, không tôn sùng - không dè bỉu; không dùng cân nặng làm thước đo thành công bữa ăn; lượng ăn luôn ghi "tham khảo, tôn trọng tín hiệu no của con"; không ủng hộ ép ăn, ăn rong, màn hình khi ăn nhưng phê phán hành vi chứ không phê phán ông bà; công thức phải nấu được với chợ Việt, có phương án tiết kiệm; an toàn hóc nghẹn và thực phẩm cấm dưới 1 tuổi là ranh giới cứng dẫn nguồn WHO/Viện Dinh dưỡng; không quảng cáo bột, váng sữa, gia vị ăn dặm.',

            'style_sample' => <<<'TEXT'
                Bữa trưa nay ở nhà, bà cho cháu ăn hết veo bát cháo — bằng cách bế ra đầu ngõ chỉ chim chỉ xe, thêm 15 phút quảng cáo trên điện thoại. Tối về nghe bà khoe, bạn không biết nên mừng hay nên lo. Mình hiểu cảm giác đó, và trước khi bàn chuyện đúng sai, hãy công bằng với bà một câu: bà làm thế vì thương cháu thật lòng — trong "hệ điều hành" nuôi con của thế hệ trước, một đứa trẻ ăn hết bát là một đứa trẻ được chăm tốt, và người cho ăn giỏi là người có công. Vấn đề là khoa học dinh dưỡng hiện đại đã phát hiện ra cái giá của bát cháo ăn rong: khi con nuốt trong lúc mải nhìn tivi, não con không hề ghi nhận "mình đang ăn" — con không học được cảm giác đói - no, không học nhai, và dần dần chỉ ăn được khi có màn hình, thành cái vòng luẩn quẩn mà chính bà sau này cũng khổ. Cho nên cuộc chiến này không phải mẹ hiện đại chống bà cổ hủ — mà là cả nhà cùng chống một thói quen sẽ làm khổ tất cả. Bài này sẽ đưa bạn lộ trình 3 tuần "hạ cánh mềm": tuần 1 đưa bữa ăn về ghế nhưng giữ một "đặc quyền" cho bà, tuần 2 tắt dần màn hình bằng trò thay thế ngay tại bàn, tuần 3 trả bữa ăn về đúng nghĩa — kèm những câu nói giúp bà thấy mình là đồng minh chứ không phải bị tước quyền chăm cháu.
                TEXT,
        ],

        // TODO: thêm định nghĩa insight cho các danh mục khác tại đây (mỗi danh mục 1 phần tử,
        // theo đúng cấu trúc như trên).
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
            'core_focus'     => $definition['core_focus'],
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
