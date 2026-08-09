<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §3.5 — xuất tài liệu Director Prompt
 * Template tổng hợp = ghép các `compiled_prompt` theo `sort_order`, tính động mỗi lần xem (KHÔNG
 * lưu snapshot riêng — giống ContentOutlines::show() tính promptHtml động).
 *
 * v1.2 (Hedra "how-to-make-ai-video"): thêm khối Creative Brief đầu tài liệu (Step 1 — chốt mục
 * tiêu/đối tượng/định dạng TRƯỚC khi generate, tránh sai lầm phổ biến "bỏ qua creative brief") + gợi
 * ý checklist đánh giá output (Step 3) + metadata model/tool đã dùng cho từng shot (Step 4 + Model
 * Selection Criteria) — CHỈ hiển thị nếu người dùng có điền, không ép buộc.
 *
 * v1.3 (deepreel.com/blog/ai-video-prompts): thêm khối checklist TRƯỚC khi generate (khác khối QC
 * ở v1.2 vốn dùng SAU khi generate để đánh giá output) + tổng thời lượng ước tính của project (cộng
 * `duration_seconds` các shot đã điền) — 1 thành phần công thức nguồn ("Duration") mà việc lập kế
 * hoạch nhiều shot/nhiều nền tảng cần biết trước khi generate hàng loạt.
 *
 * v1.4 (byteplus.com — "how to write AI video prompts"): thêm dòng **Tài liệu tham chiếu bổ sung**
 * (`reference_assets`) mỗi shot nếu có điền — cùng nhóm metadata với `model_tool`, KHÔNG có trong
 * `compiled_prompt` (khái niệm multi-reference của nguồn: ảnh/video/audio tham chiếu riêng cho từng
 * shot, ghi chú lại để nhớ dùng file nào, không gửi thẳng vào prompt vì cú pháp tham chiếu khác nhau
 * tuỳ tool AI ngoài).
 *
 * v1.5 (pyxeljam.com — "10 best practices for writing effective AI video prompts"): thêm dòng
 * **Độ phân giải** (`resolution`) vào khối Creative Brief nếu có điền — thực hành #7 của nguồn "Set
 * Video Length, Quality, and File Format Requirements" (phần Quality; phần Video Length đã có qua
 * `duration_seconds`/tổng thời lượng ở v1.3).
 *
 * v1.6 (bài LinkedIn "step-by-step guide creating AI marketing videos prompts"): thêm dòng **Loại
 * video** (`video_type`) và **Thông điệp cốt lõi** (`core_message`) vào khối Creative Brief — Step 1
 * "Define Objectives" của nguồn tách 2 khái niệm này khỏi `objective`/`target_audience` đã có (v1.2).
 * Bổ sung 3 mục checklist đặc thù marketing vào QC_CHECKLIST (thời lượng khớp tỷ lệ nền tảng, nhất
 * quán thương hiệu, âm thanh rõ ràng).
 *
 * v1.8 (sentx.ai/blog/how-to-write-ai-video-prompts): công thức 7-lớp + đa số nguyên tắc (subject cụ
 * thể, action khả thi trong vài giây, front-load 20-30 từ đầu, show-vs-tell, "swipe file" prompt tái
 * dùng) nguồn liệt kê ĐÃ phủ từ v1.0-v1.6 (swipe file trùng "Duplicate/clone Shot", đã ghi nhận để dành
 * bản sau ở §10 spec — không lặp lại quyết định ở đây). 2 kỹ thuật thật sự còn thiếu: (1) **"Single
 * hero focus"** — nguồn tách riêng khỏi nguyên tắc "1 shot = 1 hành động" đã có (Hedra v1.2): giới hạn
 * SỐ LƯỢNG chủ thể trong khung hình (1 chính + tối đa 1 phụ), không phải số hành động — bổ sung 1 mục
 * vào `PRE_GENERATION_CHECKLIST`; (2) **Troubleshooting theo triệu chứng** (nguồn: "static appearance →
 * strengthen action + camera", "wrong mood/color → name light source + time of day", "style ignored →
 * move earlier with concrete anchors", "inconsistent subject → fixed identifying traits") — nguồn
 * TRƯỚC đây chỉ cho checklist đánh giá kết quả (Hedra QC_CHECKLIST) mà không map triệu chứng cụ thể
 * sang field cần sửa; bổ sung `TROUBLESHOOTING_GUIDE` + khối `## Xử lý sự cố theo triệu chứng` (đặt
 * SAU checklist QC — dùng khi checklist QC phát hiện lỗi, cần biết sửa Ở ĐÂU). Đồng thời cập nhật
 * placeholder Camera/Style ở `show.blade.php` với vốn từ vựng cỡ cảnh/góc máy/chuyển động máy và gọi
 * tên nguồn sáng + thời điểm trong ngày mà nguồn liệt kê cụ thể hơn callout hiện có.
 *
 * v1.9 (veed.io/learn/video-prompts): công thức "3 thành phần" (Subject & Action/Visual Style/
 * Technical Direction) + phần lớn "power words" (temporal/spatial/emotional cues), tổ chức prompt
 * theo thứ tự, sai lầm phổ biến — ĐÃ phủ từ v1.0-v1.8. Gap thật sự: nguồn có 1 khối **"Platform-
 * Specific Strategies"** map RÕ cách viết theo định dạng/loại nội dung (TikTok-Reels 9:16 cần hook
 * mạnh 1-2s đầu + cắt nhanh; YouTube 16:9 giáo dục cần narrator+overlay, tutorial cần hands-in-frame,
 * storytelling cần mạch cảm xúc; Business cần benefit/lighting/CTA placement theo từng loại) mà
 * module trước đó chỉ có 1 câu chung "điều chỉnh giọng văn theo nền tảng" (v1.6) — không map cụ thể
 * sang field nào. Bổ sung `PLATFORM_TIPS_BY_ASPECT_RATIO` (map `aspect_ratio` đã có sẵn — CHỈ 9:16/
 * 16:9 vì nguồn chỉ nói rõ 2 định dạng này, không đoán thêm cho 1:1/4:5) và `CONTENT_TIPS_BY_VIDEO_
 * TYPE` (map `video_type` đã có sẵn — bỏ qua `other` vì nguồn không có gợi ý chung phù hợp), hiển thị
 * trong khối Creative Brief nếu field tương ứng có điền và có tip khớp. KHÔNG cần schema mới — dùng
 * lại đúng 2 field đã tồn tại từ v1.2/v1.6. Đồng thời enrich placeholder Environment (`show.blade.php`)
 * với "spatial descriptors" (hẻm nhỏ, đồng cỏ rộng, studio, hang động...) + "temporal cues" (rạng
 * đông, chiều tà, giờ vàng...) — field CUỐI CÙNG trong nhóm 5 field cốt lõi còn thiếu ví dụ (Camera/
 * Style đã có từ v1.8); và Style thêm ví dụ phong cách hoạt hình/nghệ thuật (anime, claymation, tranh
 * màu nước, VHS cũ) — nguồn liệt kê riêng như 1 kỹ thuật anchor bản sắc hình ảnh. "Leveraging ChatGPT
 * để soạn thảo" và danh sách model cụ thể (MiniMax/PixVerse/Kling...) CỐ Ý không áp dụng — trùng
 * quyết định "không phụ thuộc 1 tool cụ thể" đã chốt ở §0 spec (module chỉ ghi `model_tool` tự do).
 */
class CompileProjectDirectorPromptAction
{
    use AsAction;

    /**
     * Checklist đánh giá SAU khi có kết quả AI — Hedra Step 3 "Generate First Draft" (v1.2) + 3 mục
     * đặc thù marketing từ bài LinkedIn "step-by-step guide creating AI marketing videos" (v1.6).
     */
    private const QC_CHECKLIST = [
        'Đúng chủ thể/nhân vật (subject accuracy)',
        'Chuyển động mượt, không giật/lỗi khung hình (motion smoothness)',
        'Góc máy đúng ý đồ (camera angle effectiveness)',
        'Đúng phong cách/thương hiệu xuyên suốt (style consistency)',
        'Không có artifact/lỗi hình ảnh lạ (visual artifacts)',
        'Thời lượng khớp tỷ lệ khung hình của nền tảng phát hành (v1.6)',
        'Nhất quán thương hiệu — vị trí logo, đúng bảng màu đã khai (v1.6)',
        'Âm thanh rõ ràng, không tạp âm nền (v1.6)',
    ];

    /** Checklist rà lại prompt TRƯỚC khi chạy trên tool AI ngoài — deepreel.com (v1.3). */
    private const PRE_GENERATION_CHECKLIST = [
        'Subject đã mô tả cụ thể (không còn từ chung chung như "một người phụ nữ")',
        // v1.8 (sentx.ai "Single hero focus") — khác nguyên tắc "1 shot = 1 hành động" đã có: đây
        // giới hạn SỐ chủ thể trong khung hình, không phải số hành động.
        'Chỉ 1 chủ thể chính + tối đa 1 yếu tố phụ trong khung hình (không dồn nhiều người/vật cùng lúc)',
        'Style/phong cách đã xác định rõ',
        'Camera (loại cảnh + chuyển động) đã điền',
        'Mood/tâm trạng đã xác định',
        'Ánh sáng/lighting đã mô tả (thường nằm trong Style/Constraints)',
        'Duration/thời lượng đã điền nếu cần',
        'Không có yêu cầu đối lập nhau trong cùng 1 shot (VD "vừa dữ dội vừa yên bình")',
        'Yêu cầu khả thi về mặt vật lý/hình ảnh (không mâu thuẫn logic)',
    ];

    /**
     * v1.8 (sentx.ai "Troubleshooting by Slot") — map triệu chứng output SAI sang field cần sửa; dùng
     * SAU khi checklist QC (`QC_CHECKLIST`) phát hiện có vấn đề, để biết sửa ở đâu trước khi tạo lại.
     *
     * @var array<string, string> triệu chứng => cách sửa
     */
    private const TROUBLESHOOTING_GUIDE = [
        'Video tĩnh, gần như không có chuyển động' => 'Làm rõ động từ hành động cụ thể ở Action + thêm 1 chỉ dẫn chuyển động máy ở Camera (đẩy chậm, lia ngang, tracking...) — thiếu 1 trong 2 dễ ra kết quả tĩnh.',
        'Hình ảnh hỗn loạn, méo/biến dạng giữa khung hình (morphing)' => 'Giảm về đúng 1 chủ thể + 1 hành động ở Subject/Action, rút ngắn Duration — quá nhiều yêu cầu trong 1 shot ngắn khiến model không xử lý hết.',
        'Sai tâm trạng hoặc màu sắc so với ý muốn' => 'Gọi tên RÕ nguồn sáng + thời điểm trong ngày ở Style/Mood (VD "nắng vàng lúc hoàng hôn" thay vì "ánh sáng đẹp", "ánh trăng lạnh" thay vì "tối") — ánh sáng quyết định tông màu và cảm xúc của cảnh.',
        'Model bỏ qua yêu cầu Style' => 'Không để Style là ý phụ ở cuối câu — gắn nó với đặc điểm cụ thể (chất liệu film, kiểu ánh sáng, tông màu) chứ không chỉ 1 nhãn chung như "cinematic".',
        'Nhân vật/sản phẩm bị lệch (identity drift) giữa các shot trong cùng project' => 'Dùng lại NGUYÊN VĂN các đặc điểm nhận diện cố định (màu tóc, trang phục, phụ kiện) đã khai ở Subject mặc định — không diễn đạt lại theo cách khác giữa các shot; kèm Ảnh tham chiếu (anchor) nếu cần chắc hơn.',
    ];

    /**
     * v1.9 (veed.io "Platform-Specific Strategies") — map `aspect_ratio` (đã có sẵn từ v1.2/v1.5,
     * `AiVideoStudioProject::ASPECT_RATIOS`) sang gợi ý viết prompt riêng cho định dạng đó. CHỈ 2
     * khoá vì nguồn chỉ nói rõ 2 định dạng này — không đoán thêm tip cho `1:1`/`4:5`.
     *
     * @var array<string, string>
     */
    private const PLATFORM_TIPS_BY_ASPECT_RATIO = [
        '9:16' => 'TikTok/Reels/Shorts: 1-2 giây đầu PHẢI có hành động/phản ứng gây chú ý ngay (hook) — đừng mở đầu chậm; ưu tiên mô tả cắt nhanh, zoom-in, nhấn cá tính/chuyển động nhân vật.',
        '16:9' => 'YouTube: nội dung giáo dục — nêu rõ kiểu người dẫn/kể (narrator) + hình thức hiển thị + chỉ dẫn text overlay; nội dung hướng dẫn từng bước (tutorial) — mô tả khung có tay/vật thể đang thực hiện (hands-in-frame); kể chuyện thương hiệu — mô tả rõ mạch cảm xúc, tiến triển cảnh, kiểu chuyển cảnh điện ảnh.',
    ];

    /**
     * v1.9 (veed.io "Platform-Specific Strategies" — nhóm Business/Commercial) — map `video_type`
     * (đã có sẵn từ v1.6, `AiVideoStudioProject::VIDEO_TYPES`) sang gợi ý viết prompt riêng cho loại
     * nội dung đó. Bỏ qua `other` vì nguồn không có gợi ý chung nào phù hợp để gán vào.
     *
     * @var array<string, string>
     */
    private const CONTENT_TIPS_BY_VIDEO_TYPE = [
        'explainer' => 'Nêu rõ lợi ích/thông tin chính cần truyền đạt + hình thức hiển thị (talking head/screen recording/hoạt hình).',
        'testimonial' => 'Mô tả rõ bối cảnh người chia sẻ (ở đâu, đang làm gì) + vị trí đặt call-to-action trong cảnh.',
        'product_demo' => 'Nêu rõ lợi ích chính của sản phẩm cần lên hình + kiểu ánh sáng, góc máy, tâm trạng nhạc nền phù hợp.',
        'storytelling' => 'Mô tả mạch cảm xúc xuyên suốt, tiến triển cảnh theo trình tự, phong cách lời dẫn, kiểu chuyển cảnh điện ảnh.',
    ];

    public function handle(AiVideoStudioProject $project): string
    {
        $shots = $project->shots()->orderBy('sort_order')->get();

        $header = $this->buildCreativeBriefBlock($project, $shots);

        $sections = $shots->map(function (AiVideoStudioShot $shot, int $index) {
            $heading = '## Shot '.($index + 1).($shot->label ? " — {$shot->label}" : '');
            $body = $shot->compiled_prompt ?: '_(chưa có prompt — điền field còn thiếu)_';
            $modelTool = filled($shot->model_tool) ? "\n\n**Model/Tool đã dùng:** {$shot->model_tool}" : '';
            $referenceAssets = filled($shot->reference_assets) ? "\n\n**Tài liệu tham chiếu bổ sung:** {$shot->reference_assets}" : '';
            $result = filled($shot->ai_result) ? "\n\n**Kết quả AI:**\n{$shot->ai_result}" : '';
            $qcNotes = filled($shot->qc_notes) ? "\n\n**Ghi chú đánh giá (QC):** {$shot->qc_notes}" : '';

            return "{$heading}\n\n{$body}{$modelTool}{$referenceAssets}{$result}{$qcNotes}";
        });

        return "# Director Prompt Template — {$project->name}\n\n{$header}".$sections->implode("\n\n---\n\n");
    }

    /**
     * Creative Brief (§0 v1.2) + tổng thời lượng ước tính (v1.3) + 2 checklist (trước/sau generate)
     * — chỉ in field NÀO CÓ giá trị, bỏ qua rỗng.
     */
    private function buildCreativeBriefBlock(AiVideoStudioProject $project, Collection $shots): string
    {
        $brief = [];
        if (filled($project->objective)) {
            $brief[] = "- **Mục tiêu:** {$project->objective}";
        }
        if (filled($project->target_audience)) {
            $brief[] = "- **Đối tượng khán giả:** {$project->target_audience}";
        }
        if (filled($project->video_type)) {
            $brief[] = "- **Loại video:** {$project->video_type}";
            // v1.9 (veed.io) — chỉ in nếu CÓ tip khớp; bỏ qua `other` (nguồn không có gợi ý phù hợp).
            if ($tip = self::CONTENT_TIPS_BY_VIDEO_TYPE[$project->video_type] ?? null) {
                $brief[] = "- **Gợi ý theo loại video:** {$tip}";
            }
        }
        if (filled($project->core_message)) {
            $brief[] = "- **Thông điệp cốt lõi:** {$project->core_message}";
        }
        if (filled($project->aspect_ratio)) {
            $brief[] = "- **Tỷ lệ khung hình:** {$project->aspect_ratio}";
            // v1.9 (veed.io) — chỉ in nếu CÓ tip khớp (9:16/16:9); im lặng bỏ qua với 1:1/4:5.
            if ($tip = self::PLATFORM_TIPS_BY_ASPECT_RATIO[$project->aspect_ratio] ?? null) {
                $brief[] = "- **Gợi ý theo nền tảng:** {$tip}";
            }
        }
        if (filled($project->resolution)) {
            $brief[] = "- **Độ phân giải:** {$project->resolution}";
        }
        if (filled($project->reference_image_url)) {
            $brief[] = "- **Ảnh tham chiếu (anchor):** {$project->reference_image_url}";
        }

        $totalDuration = $shots->sum('duration_seconds');
        $shotsWithDuration = $shots->whereNotNull('duration_seconds')->count();
        if ($shotsWithDuration > 0) {
            $brief[] = "- **Tổng thời lượng ước tính:** {$totalDuration} giây ({$shotsWithDuration}/{$shots->count()} shot có điền thời lượng)";
        }

        $preChecklist = implode("\n", array_map(fn (string $item) => "- [ ] {$item}", self::PRE_GENERATION_CHECKLIST));
        $qcChecklist = implode("\n", array_map(fn (string $item) => "- [ ] {$item}", self::QC_CHECKLIST));

        $sections = [];
        if ($brief !== []) {
            $sections[] = "## Creative Brief\n\n".implode("\n", $brief);
        }
        $sections[] = "## Checklist trước khi generate (mỗi shot, ngay trước khi dán prompt sang tool AI)\n\n{$preChecklist}";
        $sections[] = "## Checklist đánh giá output (mỗi shot, sau khi có kết quả AI, trước khi chấp nhận)\n\n{$qcChecklist}";
        $sections[] = $this->buildTroubleshootingBlock();

        return implode("\n\n", $sections)."\n\n---\n\n";
    }

    /**
     * v1.8 (sentx.ai "Troubleshooting by Slot") — khối tĩnh, không phụ thuộc dữ liệu project/shot,
     * đặt SAU checklist QC vì đây là bước tiếp theo khi checklist đó phát hiện có vấn đề.
     */
    private function buildTroubleshootingBlock(): string
    {
        $items = [];
        foreach (self::TROUBLESHOOTING_GUIDE as $symptom => $fix) {
            $items[] = "- **{$symptom}** → {$fix}";
        }

        return "## Xử lý sự cố theo triệu chứng (nếu output AI chưa đúng ý, trước khi tạo lại)\n\n".implode("\n", $items);
    }
}
