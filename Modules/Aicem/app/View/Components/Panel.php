<?php

namespace Modules\Aicem\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Modules\Aicem\Features\Generation\Queries\ListRunnableWorkflowsHandler;
use Modules\Aicem\Features\Generation\Queries\ListRunnableWorkflowsQuery;
use Modules\Aicem\Models\AicemGenerationRun;

/**
 * Panel AI dùng chung, nhúng vào view sửa của từng module chỉ định (Post/Product) — nhận
 * subject_type + subject_id làm prop, không biết gì về PostArticle/Product cụ thể
 * (spec/AICEM_Technical_Specification.md mục 4).
 */
class Panel extends Component
{
    public Collection $workflows;
    public ?AicemGenerationRun $latestRun;

    public function __construct(
        public string $subjectType,
        public int $subjectId,
        public array $allowedFields = [],
        public bool $allowBlockEdit = false,
        public array $subjectTaxonomyPreview = [],
    ) {
        $this->workflows = app(ListRunnableWorkflowsHandler::class)->handle(
            new ListRunnableWorkflowsQuery($subjectType, $subjectId)
        );

        // orderByDesc('id') thay vì latest()/created_at: 2 run tạo trong cùng 1 giây (created_at
        // chỉ có độ chính xác giây) sẽ có thứ tự KHÔNG xác định nếu chỉ sort theo created_at —
        // id tăng dần đơn điệu nên luôn xác định đúng run nào thật sự mới nhất.
        $this->latestRun = AicemGenerationRun::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->with('suggestions')
            ->orderByDesc('id')
            ->first();
    }

    /** Ẩn hẳn panel nếu user không có quyền chạy AI (mục 12: Marketing mở panel qua aicem.use). */
    public function shouldRender(): bool
    {
        return auth()->check() && auth()->user()->can('aicem.use');
    }

    public function render(): View
    {
        return view('aicem::components.panel');
    }
}
