<?php

namespace Modules\Post\Database\Seeders;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Modules\Post\Features\ArticleAuthoring\Actions\ApproveArticleTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\SubmitArticleForReviewAction;
use Modules\Post\Features\ArticleAuthoring\Actions\SyncContentBlocksAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Features\CategoryManagement\Actions\CreateCategoryAction;
use Modules\Post\Features\CategoryManagement\Data\CategoryData;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;

/**
 * Demo content cho Post (bài viết đã xuất bản) — chạy Action THẬT (create → translate →
 * submit → approve → publish) qua đúng 3 tài khoản nền tảng (content-creator/editor/
 * content-head, xem Modules\Approval\Database\Seeders\ContentReviewHierarchySeeder +
 * PlatformContentCreatorSeeder) để log/timestamp sinh ra thực tế, cùng nguyên tắc
 * PostReviewDemoSeeder. Khác PostReviewDemoSeeder ở chỗ: seeder này CÓ nằm trong
 * SystemDataSeeder (chạy sau ApprovalDatabaseSeeder để 3 tài khoản trên đã tồn tại).
 *
 * Idempotent — mỗi bài dùng slug cố định, bỏ qua nếu translation đã tồn tại.
 */
class PostDemoSeeder extends Seeder
{
    /** @var array<int, array{key: string, name: string, icon: string, color: string}> */
    private const CATEGORIES = [
        ['key' => 'parenting',   'name' => 'Nuôi dạy con',        'icon' => 'baby',        'color' => '#F59E0B'],
        ['key' => 'health',      'name' => 'Sức khỏe gia đình',   'icon' => 'heart-pulse', 'color' => '#EF4444'],
        ['key' => 'education',   'name' => 'Giáo dục',            'icon' => 'graduation',  'color' => '#3B82F6'],
        ['key' => 'finance',     'name' => 'Tài chính gia đình',  'icon' => 'wallet',      'color' => '#10B981'],
        ['key' => 'marriage',    'name' => 'Hôn nhân & Gia đình', 'icon' => 'heart',       'color' => '#EC4899'],
        ['key' => 'nutrition',   'name' => 'Dinh dưỡng',          'icon' => 'apple',       'color' => '#84CC16'],
        ['key' => 'travel',      'name' => 'Du lịch gia đình',    'icon' => 'plane',       'color' => '#06B6D4'],
        ['key' => 'life_skills', 'name' => 'Kỹ năng sống',        'icon' => 'sparkles',    'color' => '#8B5CF6'],
    ];

    /** @var array<int, array{category: string, title: string, slug: string, excerpt: string, body: string, tags: string, is_featured?: bool}> */
    private const ARTICLES = [
        // ── Nuôi dạy con ──────────────────────────────────────────────
        [
            'category' => 'parenting',
            'title'    => '10 nguyên tắc kỷ luật tích cực giúp con hợp tác hơn',
            'slug'     => 'demo-ky-luat-tich-cuc-giup-con-hop-tac',
            'excerpt'  => 'Kỷ luật không cần la mắng — 10 nguyên tắc giúp cha mẹ đồng hành cùng con mà vẫn giữ được ranh giới rõ ràng.',
            'body'     => '<p>Kỷ luật tích cực tập trung vào việc dạy con hiểu hậu quả hành vi thay vì trừng phạt. Cha mẹ giữ vai trò hướng dẫn, kiên định và nhất quán trong từng quy tắc đặt ra.</p><p>Bắt đầu bằng việc lắng nghe cảm xúc của con trước khi đưa ra giới hạn, giải thích lý do rõ ràng và luôn khen ngợi khi con hợp tác đúng cách.</p>',
            'tags'     => 'nuôi dạy con,kỷ luật tích cực',
            'is_featured' => true,
        ],
        [
            'category' => 'parenting',
            'title'    => 'Làm gì khi con bước vào tuổi dậy thì?',
            'slug'     => 'demo-con-buoc-vao-tuoi-day-thi',
            'excerpt'  => 'Tuổi dậy thì mang đến nhiều thay đổi tâm sinh lý — cha mẹ cần chuẩn bị gì để đồng hành cùng con giai đoạn này?',
            'body'     => '<p>Giai đoạn dậy thì khiến trẻ nhạy cảm hơn với lời nói của người lớn. Thay vì áp đặt, cha mẹ nên trò chuyện cởi mở và tôn trọng không gian riêng của con.</p><p>Duy trì kết nối bằng những cuộc trò chuyện ngắn hằng ngày sẽ giúp con cảm thấy an toàn để chia sẻ khi gặp khó khăn.</p>',
            'tags'     => 'nuôi dạy con,tuổi dậy thì',
        ],
        [
            'category' => 'parenting',
            'title'    => 'Cách xây dựng thói quen đọc sách cho trẻ từ nhỏ',
            'slug'     => 'demo-xay-dung-thoi-quen-doc-sach-cho-tre',
            'excerpt'  => 'Thói quen đọc sách hình thành từ sớm sẽ theo con suốt đời — bắt đầu từ đâu và duy trì thế nào?',
            'body'     => '<p>Chọn sách phù hợp độ tuổi và để con tự lật trang là bước khởi đầu quan trọng. Đọc cùng con mỗi tối giúp gắn kết thói quen này với cảm giác ấm áp gia đình.</p><p>Hãy để con tự chọn sách mình thích, kể cả khi đó không phải lựa chọn cha mẹ mong muốn — sự hứng thú tự nhiên mới là động lực lâu dài.</p>',
            'tags'     => 'nuôi dạy con,đọc sách',
        ],
        [
            'category' => 'parenting',
            'title'    => 'Giúp con vượt qua nỗi sợ khi đi học ngày đầu tiên',
            'slug'     => 'demo-vuot-qua-noi-so-ngay-dau-di-hoc',
            'excerpt'  => 'Ngày đầu đến trường có thể khiến trẻ lo lắng — vài cách đơn giản giúp con tự tin bước vào môi trường mới.',
            'body'     => '<p>Chuẩn bị tâm lý cho con bằng cách kể trước những gì sẽ diễn ra ở lớp học, cùng con chọn balo/hộp bút để tạo sự háo hức.</p><p>Buổi đón/tiễn ngắn gọn, dứt khoát nhưng ấm áp sẽ giúp con nhanh thích nghi hơn là kéo dài chia tay.</p>',
            'tags'     => 'nuôi dạy con,tựu trường',
        ],
        [
            'category' => 'parenting',
            'title'    => 'Quản lý thời gian dùng thiết bị điện tử của trẻ hiệu quả',
            'slug'     => 'demo-quan-ly-thoi-gian-dung-thiet-bi-dien-tu',
            'excerpt'  => 'Thiết bị điện tử là con dao hai lưỡi — làm sao để thiết lập giới hạn hợp lý mà không gây xung đột?',
            'body'     => '<p>Thống nhất khung giờ sử dụng thiết bị rõ ràng ngay từ đầu, áp dụng đồng đều cho cả gia đình để trẻ không cảm thấy bị đối xử bất công.</p><p>Thay thế thời gian màn hình bằng hoạt động ngoài trời hoặc trò chơi gia đình giúp trẻ giảm phụ thuộc một cách tự nhiên.</p>',
            'tags'     => 'nuôi dạy con,thiết bị điện tử',
        ],

        // ── Sức khỏe gia đình ─────────────────────────────────────────
        [
            'category' => 'health',
            'title'    => 'Lịch tiêm chủng đầy đủ cho trẻ từ 0-6 tuổi',
            'slug'     => 'demo-lich-tiem-chung-cho-tre-0-6-tuoi',
            'excerpt'  => 'Tiêm chủng đúng lịch giúp bảo vệ trẻ khỏi nhiều bệnh nguy hiểm — tổng hợp mốc thời gian quan trọng cha mẹ cần nhớ.',
            'body'     => '<p>Từ sơ sinh đến 6 tuổi, trẻ cần hoàn thành nhiều mũi tiêm quan trọng theo khuyến cáo của Bộ Y tế. Cha mẹ nên lưu sổ tiêm chủng và đặt lịch nhắc trước mỗi đợt.</p><p>Nếu trẻ bị trễ lịch vì ốm hoặc lý do khác, hãy liên hệ cơ sở y tế để được tư vấn tiêm bù phù hợp, không tự ý bỏ qua mũi tiêm.</p>',
            'tags'     => 'sức khỏe gia đình,tiêm chủng',
            'is_featured' => true,
        ],
        [
            'category' => 'health',
            'title'    => 'Dấu hiệu cảnh báo cần đưa trẻ đi khám ngay',
            'slug'     => 'demo-dau-hieu-can-dua-tre-di-kham-ngay',
            'excerpt'  => 'Không phải cơn sốt nào cũng đáng lo, nhưng một số dấu hiệu cha mẹ tuyệt đối không nên chủ quan.',
            'body'     => '<p>Sốt cao trên 39 độ kéo dài, co giật, khó thở hoặc li bì bất thường là những dấu hiệu cần đưa trẻ đến cơ sở y tế ngay lập tức.</p><p>Ghi lại thời điểm triệu chứng xuất hiện và diễn biến sẽ giúp bác sĩ chẩn đoán nhanh và chính xác hơn.</p>',
            'tags'     => 'sức khỏe gia đình,khám bệnh',
        ],
        [
            'category' => 'health',
            'title'    => 'Chăm sóc giấc ngủ cho cả gia đình mùa nắng nóng',
            'slug'     => 'demo-cham-soc-giac-ngu-mua-nang-nong',
            'excerpt'  => 'Giấc ngủ chất lượng giúp cả nhà khỏe mạnh hơn — vài mẹo đơn giản để vượt qua những đêm oi bức.',
            'body'     => '<p>Giữ phòng ngủ thoáng mát, hạn chế ánh sáng xanh từ điện thoại trước giờ ngủ và duy trì giờ giấc cố định giúp cải thiện chất lượng giấc ngủ rõ rệt.</p><p>Với trẻ nhỏ, tắm nước ấm trước khi ngủ và mặc đồ thoáng khí sẽ giúp bé ngủ sâu giấc hơn trong những ngày nóng.</p>',
            'tags'     => 'sức khỏe gia đình,giấc ngủ',
        ],
        [
            'category' => 'health',
            'title'    => 'Phòng tránh tai nạn thương tích thường gặp tại nhà',
            'slug'     => 'demo-phong-tranh-tai-nan-thuong-tich-tai-nha',
            'excerpt'  => 'Ngôi nhà tưởng chừng an toàn nhất lại tiềm ẩn nhiều rủi ro với trẻ nhỏ — nhận diện và phòng tránh sớm.',
            'body'     => '<p>Bỏng, ngã cầu thang và điện giật là những tai nạn phổ biến nhất tại nhà. Lắp đặt rào chắn cầu thang và cất vật sắc nhọn ngoài tầm với của trẻ là bước phòng ngừa cơ bản.</p><p>Trang bị kiến thức sơ cứu cơ bản cho cả gia đình sẽ giúp xử lý kịp thời nếu chẳng may xảy ra sự cố.</p>',
            'tags'     => 'sức khỏe gia đình,an toàn tại nhà',
        ],
        [
            'category' => 'health',
            'title'    => 'Chăm sóc sức khỏe tinh thần cho cha mẹ bận rộn',
            'slug'     => 'demo-cham-soc-suc-khoe-tinh-than-cho-cha-me',
            'excerpt'  => 'Cha mẹ khỏe mạnh về tinh thần mới có thể đồng hành lâu dài cùng con — đừng quên chăm sóc chính mình.',
            'body'     => '<p>Áp lực công việc và chăm sóc con cái dễ khiến cha mẹ kiệt sức. Dành ra vài phút mỗi ngày cho bản thân, dù chỉ là một tách trà yên tĩnh, cũng giúp cân bằng cảm xúc.</p><p>Đừng ngại chia sẻ khó khăn với bạn đời hoặc tìm kiếm hỗ trợ chuyên môn khi cảm thấy quá tải kéo dài.</p>',
            'tags'     => 'sức khỏe gia đình,sức khỏe tinh thần',
        ],

        // ── Giáo dục ──────────────────────────────────────────────────
        [
            'category' => 'education',
            'title'    => 'Chọn trường tiểu học phù hợp cho con — những tiêu chí cần cân nhắc',
            'slug'     => 'demo-chon-truong-tieu-hoc-phu-hop-cho-con',
            'excerpt'  => 'Chọn trường không chỉ dựa vào thành tích — cha mẹ cần cân nhắc nhiều yếu tố để phù hợp với con.',
            'body'     => '<p>Khoảng cách di chuyển, triết lý giáo dục và môi trường bạn bè là những yếu tố ảnh hưởng trực tiếp đến sự thích nghi của trẻ.</p><p>Nên tham quan trường thực tế và trò chuyện với phụ huynh đang có con theo học để có góc nhìn khách quan trước khi quyết định.</p>',
            'tags'     => 'giáo dục,chọn trường',
        ],
        [
            'category' => 'education',
            'title'    => 'Phương pháp học tập chủ động giúp con ghi nhớ lâu hơn',
            'slug'     => 'demo-phuong-phap-hoc-tap-chu-dong',
            'excerpt'  => 'Học thuộc lòng thụ động không còn hiệu quả — các phương pháp học chủ động giúp con hiểu sâu và nhớ lâu.',
            'body'     => '<p>Sơ đồ tư duy, tự đặt câu hỏi và dạy lại kiến thức cho người khác là những kỹ thuật học chủ động được chứng minh hiệu quả.</p><p>Cha mẹ có thể khuyến khích con tóm tắt bài học bằng lời của mình sau mỗi buổi học để kiểm tra mức độ hiểu bài thực sự.</p>',
            'tags'     => 'giáo dục,phương pháp học tập',
        ],
        [
            'category' => 'education',
            'title'    => 'Đồng hành cùng con trong kỳ thi chuyển cấp',
            'slug'     => 'demo-dong-hanh-cung-con-ky-thi-chuyen-cap',
            'excerpt'  => 'Áp lực thi cử có thể khiến con căng thẳng — vai trò của cha mẹ là đồng hành chứ không phải tạo thêm áp lực.',
            'body'     => '<p>Lắng nghe và chia sẻ áp lực cùng con, thay vì chỉ tập trung vào kết quả điểm số, giúp con giữ được tâm lý ổn định trong giai đoạn ôn thi.</p><p>Sắp xếp thời gian nghỉ ngơi hợp lý xen kẽ ôn tập sẽ hiệu quả hơn việc học liên tục nhiều giờ liền.</p>',
            'tags'     => 'giáo dục,thi cử',
        ],
        [
            'category' => 'education',
            'title'    => 'Dạy con kỹ năng tự học trong thời đại số',
            'slug'     => 'demo-day-con-ky-nang-tu-hoc-thoi-dai-so',
            'excerpt'  => 'Kỹ năng tự học là nền tảng giúp con thích nghi với môi trường học tập thay đổi liên tục.',
            'body'     => '<p>Hướng dẫn con cách đặt mục tiêu học tập nhỏ, tự đánh giá tiến độ và tìm kiếm tài liệu bổ sung khi cần là những kỹ năng tự học cốt lõi.</p><p>Nguồn học liệu trực tuyến phong phú là cơ hội tốt nếu con biết chọn lọc và quản lý thời gian hợp lý.</p>',
            'tags'     => 'giáo dục,tự học',
        ],
        [
            'category' => 'education',
            'title'    => 'Vai trò của hoạt động ngoại khóa trong sự phát triển của trẻ',
            'slug'     => 'demo-vai-tro-hoat-dong-ngoai-khoa',
            'excerpt'  => 'Ngoại khóa không chỉ là giải trí — đây là cơ hội để trẻ phát triển kỹ năng mềm quan trọng.',
            'body'     => '<p>Tham gia câu lạc bộ thể thao, nghệ thuật hay hoạt động cộng đồng giúp trẻ rèn luyện kỹ năng làm việc nhóm và quản lý thời gian.</p><p>Cha mẹ nên để con tự chọn hoạt động yêu thích thay vì áp đặt theo kỳ vọng cá nhân, giúp con duy trì hứng thú lâu dài.</p>',
            'tags'     => 'giáo dục,hoạt động ngoại khóa',
        ],

        // ── Tài chính gia đình ────────────────────────────────────────
        [
            'category' => 'finance',
            'title'    => 'Lập quỹ dự phòng gia đình — bắt đầu từ đâu?',
            'slug'     => 'demo-lap-quy-du-phong-gia-dinh',
            'excerpt'  => 'Quỹ dự phòng là tấm đệm an toàn tài chính cho mọi gia đình — nên bắt đầu tích lũy như thế nào?',
            'body'     => '<p>Chuyên gia khuyến nghị quỹ dự phòng nên đủ trang trải chi phí sinh hoạt từ 3-6 tháng. Bắt đầu bằng khoản nhỏ đều đặn mỗi tháng dễ duy trì hơn một mục tiêu lớn ngay từ đầu.</p><p>Nên giữ quỹ này ở tài khoản riêng, dễ rút khi cần nhưng tách biệt khỏi chi tiêu hằng ngày để tránh sử dụng sai mục đích.</p>',
            'tags'     => 'tài chính gia đình,quỹ dự phòng',
            'is_featured' => true,
        ],
        [
            'category' => 'finance',
            'title'    => 'Dạy con quản lý tiền tiêu vặt từ nhỏ',
            'slug'     => 'demo-day-con-quan-ly-tien-tieu-vat',
            'excerpt'  => 'Thói quen quản lý tài chính nên được hình thành từ nhỏ thông qua những bài học đơn giản, thực tế.',
            'body'     => '<p>Cho con khoản tiêu vặt cố định hằng tuần và hướng dẫn con phân chia thành các mục tiết kiệm, chi tiêu, chia sẻ giúp hình thành tư duy tài chính lành mạnh.</p><p>Để con tự quyết định việc chi tiêu trong giới hạn cho phép, kể cả khi mắc sai lầm nhỏ, là cách học thực tế nhất.</p>',
            'tags'     => 'tài chính gia đình,dạy con về tiền',
        ],
        [
            'category' => 'finance',
            'title'    => 'Lập ngân sách chi tiêu hàng tháng cho gia đình 4 người',
            'slug'     => 'demo-lap-ngan-sach-chi-tieu-gia-dinh-4-nguoi',
            'excerpt'  => 'Một bản ngân sách rõ ràng giúp gia đình kiểm soát chi tiêu và đạt được mục tiêu tài chính dễ dàng hơn.',
            'body'     => '<p>Phân chia ngân sách theo các nhóm cố định (nhà ở, ăn uống), linh hoạt (giải trí) và tiết kiệm giúp gia đình dễ theo dõi dòng tiền hằng tháng.</p><p>Rà soát lại ngân sách định kỳ mỗi quý để điều chỉnh phù hợp với thay đổi thu nhập hoặc nhu cầu thực tế.</p>',
            'tags'     => 'tài chính gia đình,ngân sách',
        ],
        [
            'category' => 'finance',
            'title'    => 'Bảo hiểm nhân thọ cho gia đình trẻ — có cần thiết?',
            'slug'     => 'demo-bao-hiem-nhan-tho-cho-gia-dinh-tre',
            'excerpt'  => 'Bảo hiểm nhân thọ là công cụ bảo vệ tài chính dài hạn — nhưng chọn gói nào phù hợp với gia đình trẻ?',
            'body'     => '<p>Với gia đình trẻ có con nhỏ, bảo hiểm nhân thọ giúp đảm bảo tài chính cho các thành viên nếu trụ cột gia đình gặp rủi ro.</p><p>Nên so sánh kỹ quyền lợi, thời hạn và mức phí giữa các gói trước khi quyết định, tránh mua theo cảm tính hoặc áp lực từ tư vấn viên.</p>',
            'tags'     => 'tài chính gia đình,bảo hiểm',
        ],
        [
            'category' => 'finance',
            'title'    => 'Tiết kiệm cho việc học đại học của con ngay từ bây giờ',
            'slug'     => 'demo-tiet-kiem-cho-viec-hoc-dai-hoc-cua-con',
            'excerpt'  => 'Chi phí học đại học ngày càng tăng — kế hoạch tiết kiệm sớm giúp giảm áp lực tài chính sau này.',
            'body'     => '<p>Bắt đầu tiết kiệm từ khi con còn nhỏ, dù số tiền ban đầu không lớn, sẽ tận dụng được lợi thế lãi kép theo thời gian.</p><p>Cân nhắc các kênh đầu tư dài hạn phù hợp với khẩu vị rủi ro của gia đình thay vì chỉ gửi tiết kiệm ngân hàng thông thường.</p>',
            'tags'     => 'tài chính gia đình,tiết kiệm giáo dục',
        ],

        // ── Hôn nhân & Gia đình ───────────────────────────────────────
        [
            'category' => 'marriage',
            'title'    => 'Giữ lửa hôn nhân sau khi có con nhỏ',
            'slug'     => 'demo-giu-lua-hon-nhan-sau-khi-co-con-nho',
            'excerpt'  => 'Có con là niềm hạnh phúc nhưng cũng dễ khiến vợ chồng xao nhãng nhau — làm sao để giữ kết nối?',
            'body'     => '<p>Dành thời gian riêng cho nhau, dù chỉ 15-20 phút mỗi ngày, giúp vợ chồng duy trì sự kết nối giữa bộn bề chăm con.</p><p>Chia sẻ công việc nhà và chăm sóc con cái công bằng cũng là cách thể hiện sự tôn trọng và giảm căng thẳng trong hôn nhân.</p>',
            'tags'     => 'hôn nhân,gia đình',
        ],
        [
            'category' => 'marriage',
            'title'    => 'Giải quyết mâu thuẫn vợ chồng một cách lành mạnh',
            'slug'     => 'demo-giai-quyet-mau-thuan-vo-chong-lanh-manh',
            'excerpt'  => 'Mâu thuẫn là điều khó tránh trong hôn nhân — quan trọng là cách hai người cùng nhau giải quyết.',
            'body'     => '<p>Tránh tranh cãi trước mặt con nhỏ và luôn giữ thái độ tôn trọng dù đang bất đồng quan điểm là nguyên tắc cơ bản.</p><p>Lắng nghe để hiểu thay vì lắng nghe để phản bác sẽ giúp cuộc trò chuyện đi đến giải pháp nhanh hơn.</p>',
            'tags'     => 'hôn nhân,giao tiếp vợ chồng',
        ],
        [
            'category' => 'marriage',
            'title'    => 'Cân bằng giữa công việc và gia đình cho cả hai vợ chồng',
            'slug'     => 'demo-can-bang-cong-viec-va-gia-dinh',
            'excerpt'  => 'Cân bằng công việc — gia đình không có công thức chung, nhưng có vài nguyên tắc giúp mọi cặp đôi áp dụng.',
            'body'     => '<p>Thống nhất lịch trình và phân chia trách nhiệm rõ ràng giữa hai vợ chồng giúp giảm xung đột do quá tải công việc nhà.</p><p>Đặt ra khung giờ "không công việc" mỗi tối để cả gia đình có thời gian chất lượng bên nhau.</p>',
            'tags'     => 'hôn nhân,cân bằng cuộc sống',
        ],
        [
            'category' => 'marriage',
            'title'    => 'Xây dựng truyền thống gia đình ý nghĩa mỗi năm',
            'slug'     => 'demo-xay-dung-truyen-thong-gia-dinh-y-nghia',
            'excerpt'  => 'Những truyền thống nhỏ lặp lại hằng năm sẽ trở thành ký ức đẹp gắn kết các thế hệ trong gia đình.',
            'body'     => '<p>Một bữa cơm đoàn viên cuối tuần, chuyến du xuân đầu năm hay buổi tổng kết năm cùng nhau đều có thể trở thành truyền thống ý nghĩa.</p><p>Điều quan trọng không phải quy mô hoạt động mà là sự đều đặn và cảm giác mong chờ mà nó mang lại cho cả nhà.</p>',
            'tags'     => 'hôn nhân,truyền thống gia đình',
        ],
        [
            'category' => 'marriage',
            'title'    => 'Vai trò của ông bà trong việc nuôi dạy cháu',
            'slug'     => 'demo-vai-tro-cua-ong-ba-trong-nuoi-day-chau',
            'excerpt'  => 'Ông bà là nguồn hỗ trợ quý giá — nhưng cần thống nhất ranh giới rõ ràng để tránh xung đột trong cách dạy con.',
            'body'     => '<p>Sự khác biệt thế hệ trong quan niệm nuôi dạy trẻ là điều bình thường — cha mẹ nên chủ động trao đổi thẳng thắn với ông bà về nguyên tắc chung.</p><p>Tôn trọng vai trò của ông bà đồng thời giữ vững quyết định cuối cùng thuộc về cha mẹ sẽ giúp cả gia đình hài hòa hơn.</p>',
            'tags'     => 'hôn nhân,ông bà và cháu',
        ],

        // ── Dinh dưỡng ─────────────────────────────────────────────────
        [
            'category' => 'nutrition',
            'title'    => 'Thực đơn ăn dặm khoa học cho bé 6-12 tháng',
            'slug'     => 'demo-thuc-don-an-dam-khoa-hoc-cho-be',
            'excerpt'  => 'Giai đoạn ăn dặm đặt nền móng cho thói quen ăn uống của trẻ — xây dựng thực đơn khoa học ngay từ đầu.',
            'body'     => '<p>Bắt đầu ăn dặm từ thực phẩm nghiền mịn, tăng dần độ thô theo khả năng nhai của bé, đa dạng nhóm chất để tránh biếng ăn về sau.</p><p>Không nên nêm gia vị mặn/ngọt trong giai đoạn đầu để bảo vệ thận và vị giác tự nhiên của trẻ.</p>',
            'tags'     => 'dinh dưỡng,ăn dặm',
            'is_featured' => true,
        ],
        [
            'category' => 'nutrition',
            'title'    => 'Cách xử lý khi trẻ biếng ăn kéo dài',
            'slug'     => 'demo-cach-xu-ly-khi-tre-bieng-an-keo-dai',
            'excerpt'  => 'Biếng ăn kéo dài khiến cha mẹ lo lắng — tìm hiểu nguyên nhân trước khi áp dụng giải pháp phù hợp.',
            'body'     => '<p>Biếng ăn có thể xuất phát từ tâm lý, bệnh lý hoặc đơn giản là bé đang trong giai đoạn phát triển chậm cân tự nhiên.</p><p>Tạo không khí vui vẻ trong bữa ăn, không ép buộc và đa dạng cách chế biến sẽ giúp cải thiện tình trạng biếng ăn hiệu quả hơn la mắng.</p>',
            'tags'     => 'dinh dưỡng,biếng ăn',
        ],
        [
            'category' => 'nutrition',
            'title'    => 'Xây dựng bữa cơm gia đình cân bằng dinh dưỡng',
            'slug'     => 'demo-xay-dung-bua-com-gia-dinh-can-bang',
            'excerpt'  => 'Một bữa cơm cân bằng không cần cầu kỳ — chỉ cần đủ 4 nhóm chất và khẩu phần hợp lý cho từng thành viên.',
            'body'     => '<p>Đảm bảo mỗi bữa ăn có đủ tinh bột, đạm, rau xanh và chất béo lành mạnh là nguyên tắc cơ bản cho bữa cơm gia đình khỏe mạnh.</p><p>Ưu tiên thực phẩm tươi, hạn chế đồ chế biến sẵn giúp cả nhà duy trì thói quen ăn uống lành mạnh lâu dài.</p>',
            'tags'     => 'dinh dưỡng,bữa cơm gia đình',
        ],
        [
            'category' => 'nutrition',
            'title'    => 'Bổ sung vi chất cần thiết cho trẻ trong độ tuổi đi học',
            'slug'     => 'demo-bo-sung-vi-chat-cho-tre-tuoi-di-hoc',
            'excerpt'  => 'Trẻ trong độ tuổi đi học cần nhiều vi chất để phát triển thể chất và trí não — bổ sung đúng cách như thế nào?',
            'body'     => '<p>Canxi, sắt, kẽm và vitamin D là những vi chất quan trọng cần chú ý bổ sung qua thực phẩm hằng ngày cho trẻ độ tuổi đi học.</p><p>Nên ưu tiên bổ sung qua chế độ ăn tự nhiên, chỉ dùng thực phẩm chức năng khi có chỉ định của bác sĩ dinh dưỡng.</p>',
            'tags'     => 'dinh dưỡng,vi chất',
        ],
        [
            'category' => 'nutrition',
            'title'    => 'Đồ ăn vặt lành mạnh thay thế snack công nghiệp',
            'slug'     => 'demo-do-an-vat-lanh-manh-thay-the-snack',
            'excerpt'  => 'Snack công nghiệp hấp dẫn nhưng nhiều muối/đường — gợi ý vài món ăn vặt lành mạnh dễ chuẩn bị tại nhà.',
            'body'     => '<p>Trái cây cắt sẵn, sữa chua không đường hay các loại hạt rang là lựa chọn ăn vặt lành mạnh, dễ chuẩn bị cho cả tuần.</p><p>Cho trẻ tham gia chuẩn bị đồ ăn vặt cùng cha mẹ cũng là cách khơi gợi hứng thú ăn uống lành mạnh một cách tự nhiên.</p>',
            'tags'     => 'dinh dưỡng,ăn vặt lành mạnh',
        ],

        // ── Du lịch gia đình ───────────────────────────────────────────
        [
            'category' => 'travel',
            'title'    => 'Kinh nghiệm du lịch biển cùng trẻ nhỏ lần đầu',
            'slug'     => 'demo-kinh-nghiem-du-lich-bien-cung-tre-nho',
            'excerpt'  => 'Chuyến du lịch biển đầu tiên cùng con cần chuẩn bị kỹ để chuyến đi trọn vẹn niềm vui cho cả nhà.',
            'body'     => '<p>Chuẩn bị đầy đủ đồ bơi, kem chống nắng dành riêng cho trẻ em và luôn giám sát sát sao khi con tiếp xúc với nước là ưu tiên hàng đầu.</p><p>Chọn khung giờ tắm biển tránh nắng gắt (trước 9h hoặc sau 16h) giúp trẻ thoải mái vui chơi mà không bị say nắng.</p>',
            'tags'     => 'du lịch gia đình,du lịch biển',
        ],
        [
            'category' => 'travel',
            'title'    => 'Danh sách vật dụng không thể thiếu khi đi du lịch cùng em bé',
            'slug'     => 'demo-danh-sach-vat-dung-du-lich-cung-em-be',
            'excerpt'  => 'Đi du lịch cùng em bé cần chuẩn bị kỹ lưỡng hơn — danh sách vật dụng giúp cha mẹ không bỏ sót điều gì.',
            'body'     => '<p>Bỉm, sữa, thuốc hạ sốt cơ bản và đồ chơi quen thuộc của bé là những vật dụng cần có trong túi đồ du lịch.</p><p>Nên chuẩn bị dư một chút so với dự tính ban đầu, đặc biệt với những chuyến đi xa hoặc di chuyển bằng máy bay.</p>',
            'tags'     => 'du lịch gia đình,em bé',
        ],
        [
            'category' => 'travel',
            'title'    => 'Gợi ý các điểm du lịch gần Hà Nội phù hợp cho gia đình',
            'slug'     => 'demo-diem-du-lich-gan-ha-noi-cho-gia-dinh',
            'excerpt'  => 'Không cần đi xa, nhiều điểm đến gần Hà Nội vẫn mang lại trải nghiệm nghỉ dưỡng trọn vẹn cho cả gia đình.',
            'body'     => '<p>Các khu nghỉ dưỡng sinh thái ven đô là lựa chọn lý tưởng cho gia đình có trẻ nhỏ nhờ không gian rộng rãi, gần gũi thiên nhiên.</p><p>Nên đặt phòng trước vào cuối tuần cao điểm và kiểm tra tiện ích dành cho trẻ em trước khi lựa chọn nơi lưu trú.</p>',
            'tags'     => 'du lịch gia đình,gần Hà Nội',
        ],
        [
            'category' => 'travel',
            'title'    => 'Mẹo giữ trẻ không quấy khóc trên chuyến bay dài',
            'slug'     => 'demo-meo-giu-tre-khong-quay-khoc-tren-may-bay',
            'excerpt'  => 'Chuyến bay dài cùng trẻ nhỏ có thể là thử thách — vài mẹo nhỏ giúp hành trình nhẹ nhàng hơn.',
            'body'     => '<p>Chuẩn bị đồ chơi mới lạ, đồ ăn nhẹ yêu thích và lên lịch giấc ngủ trùng với giờ bay sẽ giúp trẻ bớt quấy khóc trên máy bay.</p><p>Thông báo trước với tiếp viên nếu cần hỗ trợ đặc biệt cho trẻ nhỏ hoặc em bé sơ sinh.</p>',
            'tags'     => 'du lịch gia đình,đi máy bay cùng trẻ',
        ],
        [
            'category' => 'travel',
            'title'    => 'Lên kế hoạch ngân sách cho chuyến du lịch gia đình',
            'slug'     => 'demo-len-ke-hoach-ngan-sach-du-lich-gia-dinh',
            'excerpt'  => 'Một chuyến đi trọn vẹn không nhất thiết phải tốn kém — lập ngân sách hợp lý giúp cả nhà thoải mái tận hưởng.',
            'body'     => '<p>Xác định tổng ngân sách trước khi chọn điểm đến, phân bổ rõ cho di chuyển, lưu trú, ăn uống và các hoạt động vui chơi.</p><p>Đặt vé và phòng sớm, tận dụng ưu đãi mùa thấp điểm là cách tiết kiệm chi phí hiệu quả cho gia đình đông thành viên.</p>',
            'tags'     => 'du lịch gia đình,ngân sách du lịch',
        ],

        // ── Kỹ năng sống ────────────────────────────────────────────────
        [
            'category' => 'life_skills',
            'title'    => 'Dạy con kỹ năng tự bảo vệ bản thân trước người lạ',
            'slug'     => 'demo-day-con-ky-nang-tu-bao-ve-ban-than',
            'excerpt'  => 'Kỹ năng tự bảo vệ là hành trang quan trọng giúp trẻ an toàn hơn khi không có cha mẹ bên cạnh.',
            'body'     => '<p>Dạy con quy tắc "không đi theo người lạ", nhớ số điện thoại của cha mẹ và biết cách kêu cứu khi cảm thấy không an toàn.</p><p>Thực hành qua tình huống giả định giúp con ghi nhớ và phản xạ tốt hơn là chỉ nghe giảng giải lý thuyết.</p>',
            'tags'     => 'kỹ năng sống,an toàn cho trẻ',
        ],
        [
            'category' => 'life_skills',
            'title'    => 'Rèn kỹ năng giao tiếp cho trẻ nhút nhát',
            'slug'     => 'demo-ren-ky-nang-giao-tiep-cho-tre-nhut-nhat',
            'excerpt'  => 'Trẻ nhút nhát cần được khích lệ đúng cách để tự tin thể hiện bản thân hơn trong giao tiếp hằng ngày.',
            'body'     => '<p>Tạo cơ hội cho trẻ tiếp xúc với môi trường mới từ từ, không ép buộc, kết hợp khen ngợi mỗi khi con chủ động giao tiếp.</p><p>Cha mẹ làm gương bằng cách giao tiếp cởi mở trong gia đình cũng là cách gián tiếp giúp trẻ tự tin hơn.</p>',
            'tags'     => 'kỹ năng sống,giao tiếp',
        ],
        [
            'category' => 'life_skills',
            'title'    => 'Dạy con kỹ năng quản lý cảm xúc từ sớm',
            'slug'     => 'demo-day-con-ky-nang-quan-ly-cam-xuc',
            'excerpt'  => 'Quản lý cảm xúc là kỹ năng nền tảng giúp trẻ đối mặt với khó khăn một cách bình tĩnh hơn khi trưởng thành.',
            'body'     => '<p>Giúp con gọi tên cảm xúc đang trải qua (buồn, giận, sợ) là bước đầu tiên để trẻ học cách kiểm soát chúng.</p><p>Áp dụng các kỹ thuật đơn giản như hít thở sâu, đếm số khi tức giận sẽ giúp trẻ bình tĩnh lại nhanh hơn.</p>',
            'tags'     => 'kỹ năng sống,quản lý cảm xúc',
        ],
        [
            'category' => 'life_skills',
            'title'    => 'Hướng dẫn trẻ làm việc nhà phù hợp theo độ tuổi',
            'slug'     => 'demo-huong-dan-tre-lam-viec-nha-theo-do-tuoi',
            'excerpt'  => 'Việc nhà không chỉ giúp đỡ cha mẹ mà còn rèn tính tự lập và trách nhiệm cho trẻ từ nhỏ.',
            'body'     => '<p>Trẻ 3-5 tuổi có thể tự dọn đồ chơi, trẻ 6-10 tuổi có thể phụ giúp dọn bàn ăn hoặc gấp quần áo đơn giản.</p><p>Giao việc phù hợp độ tuổi và kiên nhẫn hướng dẫn thay vì làm thay sẽ giúp trẻ dần hình thành tính tự lập.</p>',
            'tags'     => 'kỹ năng sống,việc nhà',
        ],
        [
            'category' => 'life_skills',
            'title'    => 'Kỹ năng quản lý thời gian cho học sinh cấp 2',
            'slug'     => 'demo-ky-nang-quan-ly-thoi-gian-cho-hoc-sinh',
            'excerpt'  => 'Học sinh cấp 2 bắt đầu có nhiều môn học và hoạt động hơn — quản lý thời gian tốt giúp con chủ động hơn trong học tập.',
            'body'     => '<p>Hướng dẫn con lập thời gian biểu hằng tuần, ưu tiên việc quan trọng trước và để lại khoảng trống cho việc phát sinh.</p><p>Sử dụng công cụ nhắc việc đơn giản như sổ tay hoặc ứng dụng lịch giúp con dần hình thành thói quen tự quản lý thời gian.</p>',
            'tags'     => 'kỹ năng sống,quản lý thời gian',
        ],
    ];

    public function run(): void
    {
        $org = Organization::withoutGlobalScopes()->where('slug', 'system')->first();
        if (! $org) {
            $this->command->warn('  ⚠ Không tìm thấy Organization slug=system — chạy SystemOrganizationSeeder trước.');

            return;
        }

        $creator = User::withoutGlobalScopes()->where('email', 'content-creator@system.local')->first();
        $editor  = User::withoutGlobalScopes()->where('email', 'editor@system.local')->first();
        $head    = User::withoutGlobalScopes()->where('email', 'content-head@system.local')->first();

        if (! $creator || ! $editor || ! $head) {
            $this->command->warn('  ⚠ Thiếu tài khoản platform (content-creator/editor/content-head@system.local) — chạy ApprovalDatabaseSeeder trước.');

            return;
        }

        $previousUser = Auth::user();
        $created      = 0;

        TenantContext::runForOrganization($org, function () use ($creator, $editor, $head, &$created) {
            $categories = $this->seedCategories($creator);

            foreach (self::ARTICLES as $definition) {
                if ($this->seedArticle($definition, $categories[$definition['category']], $creator, $editor, $head)) {
                    $created++;
                }
            }
        });

        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info("  ✓ Post demo data seeded ({$created} bài viết mới, đã published).");
    }

    /** @return array<string, int> map category key → PostCategory id */
    private function seedCategories(User $creator): array
    {
        Auth::login($creator);

        $map = [];

        foreach (self::CATEGORIES as $sortOrder => $definition) {
            $category = PostCategory::where('slug', \Illuminate\Support\Str::slug($definition['name']))->first();

            if (! $category) {
                $category = app(CreateCategoryAction::class)->handle(CategoryData::from([
                    'name'       => $definition['name'],
                    'icon'       => $definition['icon'],
                    'color_hex'  => $definition['color'],
                    'sort_order' => $sortOrder,
                ]));
            }

            $map[$definition['key']] = $category->id;
        }

        return $map;
    }

    private function seedArticle(array $def, int $categoryId, User $creator, User $editor, User $head): bool
    {
        if (PostArticleTranslation::where('slug', $def['slug'])->exists()) {
            return false;
        }

        Auth::login($creator);

        $article = app(CreateArticleAction::class)->handle(ArticleData::from([
            'category_ids'          => [$categoryId],
            'is_primary_category_id' => $categoryId,
            'tags'                  => $def['tags'] ?? null,
            'is_featured'           => $def['is_featured'] ?? false,
        ]));

        $translation = app(CreateTranslationAction::class)->handle($article, 'vi', TranslationData::from([
            'title'   => $def['title'],
            'slug'    => $def['slug'],
            'excerpt' => $def['excerpt'],
        ]));

        app(SyncContentBlocksAction::class)->handle($translation, [
            ['type' => 'text', 'html' => $def['body']],
        ]);

        app(SubmitArticleForReviewAction::class)->handle($translation);

        Auth::login($editor);
        app(ApproveArticleTranslationAction::class)->handle($translation);

        Auth::login($head);
        app(PublishArticleAction::class)->handle($translation);

        return true;
    }
}
