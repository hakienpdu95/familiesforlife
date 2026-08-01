<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Http;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ContentCalendar\Enums\CalendarEntryOrigin;
use Modules\ContentCalendar\Enums\CalendarEntryStatus;
use Modules\ContentCalendar\Features\CalendarPlanning\Actions\ChangeCalendarEntryStatusAction;
use Modules\ContentCalendar\Features\CalendarPlanning\Actions\CreateCalendarEntryAction;
use Modules\ContentCalendar\Features\CalendarPlanning\Actions\LinkCalendarEntryToArticleAction;
use Modules\ContentCalendar\Features\CalendarPlanning\Actions\ListCategoryPlannedTitlesAction;
use Modules\ContentCalendar\Features\CalendarPlanning\Actions\UpdateCalendarEntryAction;
use Modules\ContentCalendar\Features\CalendarPlanning\Data\CalendarBoardFilterData;
use Modules\ContentCalendar\Features\CalendarPlanning\Data\CalendarEntryData;
use Modules\ContentCalendar\Features\CalendarPlanning\Queries\ListCalendarEntriesAction;
use Modules\ContentCalendar\Features\CalendarPlanning\Queries\ListCategoryTreeOptionsAction;
use Modules\ContentCalendar\Models\ContentCalendarEntry;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostCategory;

/**
 * spec/ContentCalendar_Technical_Specification.md §8 — route model binding theo `uuid` cho
 * {entry}/{category}, nhất quán PostArticle/PostCategory. Middleware route chỉ gate
 * 'content_calendar.view' (ai xem board thì vào được trang) — quyền ghi cụ thể theo từng entry
 * check bằng $this->authorize() trong từng method, đúng pattern ArticleAdminController.
 */
class CalendarEntryController extends Controller
{
    public function board(): View
    {
        return view('contentcalendar::board', $this->sharedBootstrapData());
    }

    public function calendar(): View
    {
        return view('contentcalendar::calendar', $this->sharedBootstrapData() + [
            'defaultLookaheadDays' => (int) config('content_calendar.board.default_lookahead_days', 60),
        ]);
    }

    public function list(Request $request, ListCalendarEntriesAction $listEntries): JsonResponse
    {
        $this->authorize('viewAny', ContentCalendarEntry::class);

        $filter = CalendarBoardFilterData::from([
            'categoryId'  => $request->integer('category_id') ?: null,
            'assignedTo'  => $request->integer('assigned_to') ?: null,
            'from'        => $request->string('from')->value() ?: null,
            'to'          => $request->string('to')->value() ?: null,
            'includeDone' => $request->boolean('include_done'),
            'perPage'     => (int) $request->integer('per_page', 50),
        ]);

        $entries = $listEntries->handle($request->user(), $filter);

        return response()->json([
            'data'         => $entries->getCollection()->map(fn (ContentCalendarEntry $entry) => $this->present($entry))->values(),
            'current_page' => $entries->currentPage(),
            'last_page'    => $entries->lastPage(),
            'total'        => $entries->total(),
        ]);
    }

    public function store(Request $request, CreateCalendarEntryAction $create): JsonResponse
    {
        $this->authorize('create', ContentCalendarEntry::class);

        $data = CalendarEntryData::from($this->validated($request));

        $entry = $create->handle($request->user(), $data);

        return response()->json(['entry' => $this->present($entry)], 201);
    }

    public function update(Request $request, ContentCalendarEntry $entry, UpdateCalendarEntryAction $update): JsonResponse
    {
        $this->authorize('update', $entry);

        $data = CalendarEntryData::from($this->validated($request));

        $entry = $update->handle($entry, $request->user(), $data);

        return response()->json(['entry' => $this->present($entry)]);
    }

    public function changeStatus(Request $request, ContentCalendarEntry $entry, ChangeCalendarEntryStatusAction $changeStatus): JsonResponse
    {
        $this->authorize('update', $entry);

        $validated = $request->validate([
            'status' => ['required', 'string'],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái cần chuyển sang.',
        ]);

        $target = CalendarEntryStatus::from($validated['status']);

        $entry = $changeStatus->handle($entry, $target);

        return response()->json(['entry' => $this->present($entry)]);
    }

    public function linkArticle(Request $request, ContentCalendarEntry $entry, LinkCalendarEntryToArticleAction $link): JsonResponse
    {
        $this->authorize('update', $entry);

        $validated = $request->validate([
            'article_uuid' => ['required', 'string', 'uuid', 'exists:post_articles,uuid'],
        ], [
            'article_uuid.required' => 'Vui lòng dán UUID bài viết cần gắn.',
            'article_uuid.uuid'     => 'UUID không đúng định dạng.',
            'article_uuid.exists'   => 'Không tìm thấy bài viết với UUID này.',
        ]);

        $article = PostArticle::where('uuid', $validated['article_uuid'])->firstOrFail();

        $entry = $link->handle($entry, $article);

        return response()->json(['entry' => $this->present($entry)]);
    }

    public function destroy(ContentCalendarEntry $entry): JsonResponse
    {
        $this->authorize('delete', $entry);

        $entry->delete();

        return response()->json(['deleted' => true]);
    }

    public function plannedTitles(PostCategory $category, ListCategoryPlannedTitlesAction $listPlannedTitles): JsonResponse
    {
        $this->authorize('viewAny', ContentCalendarEntry::class);

        return response()->json(['titles' => $listPlannedTitles->handle($category)]);
    }

    /**
     * docs/form-ui-spec.md §16.3 — bắt buộc truyền $messages tiếng Việt, không bao giờ để
     * Laravel rơi về message mặc định tiếng Anh.
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'post_category_id'     => ['required', 'integer', 'exists:post_categories,id'],
            'title'                => ['required', 'string', 'max:255'],
            'brief'                => ['nullable', 'string', 'max:2000'],
            'origin'               => ['required', 'string', 'in:'.implode(',', array_column(CalendarEntryOrigin::cases(), 'value'))],
            // §17.1 — bắt buộc khi origin khác thủ công, tránh mất ngữ cảnh gốc của ý tưởng.
            'origin_note'          => ['required_unless:origin,manual', 'nullable', 'string', 'max:5000'],
            'target_publish_date'  => ['nullable', 'date'],
            'assigned_to'          => ['nullable', 'integer', 'exists:users,id'],
        ], [
            'post_category_id.required' => 'Vui lòng chọn category.',
            'post_category_id.exists'   => 'Category được chọn không hợp lệ.',
            'title.required'             => 'Vui lòng nhập tiêu đề.',
            'title.max'                  => 'Tiêu đề không được vượt quá :max ký tự.',
            'brief.max'                  => 'Tóm tắt không được vượt quá :max ký tự.',
            'origin.required'            => 'Vui lòng chọn nguồn gốc ý tưởng.',
            'origin.in'                  => 'Nguồn gốc ý tưởng không hợp lệ.',
            'origin_note.required_unless' => 'Vui lòng ghi chú ý tưởng gốc khi nguồn gốc khác "Thủ công".',
            'origin_note.max'            => 'Ghi chú không được vượt quá :max ký tự.',
            'target_publish_date.date'   => 'Ngày dự kiến đăng không đúng định dạng.',
            'assigned_to.exists'         => 'Người phụ trách được chọn không hợp lệ.',
        ]);
    }

    private function present(ContentCalendarEntry $entry): array
    {
        return [
            'uuid'                 => $entry->uuid,
            'title'                => $entry->title,
            'brief'                => $entry->brief,
            'origin'               => $entry->origin->value,
            'origin_label'         => $entry->origin->label(),
            'origin_note'          => $entry->origin_note,
            'status'               => $entry->status->value,
            'status_label'         => $entry->displayStatusLabel(),
            'status_badge_class'   => $entry->status->badgeClass(),
            'is_linked'            => $entry->isLinkedToArticle(),
            'target_publish_date'  => $entry->target_publish_date?->toDateString(),
            'category'             => $entry->category ? ['id' => $entry->category->id, 'uuid' => $entry->category->uuid, 'name' => $entry->category->name] : null,
            'assigned_to'          => $entry->assignedTo ? ['id' => $entry->assignedTo->id, 'name' => $entry->assignedTo->name] : null,
            'post_article'         => $entry->postArticle ? ['uuid' => $entry->postArticle->uuid] : null,
            'created_by'           => $entry->created_by,
            'can_manage'           => auth()->user()?->can('update', $entry) ?? false,
            'can_delete'           => auth()->user()?->can('delete', $entry) ?? false,
        ];
    }

    private function sharedBootstrapData(): array
    {
        $user = auth()->user();

        // docs/form-ui-spec.md §22 + Post::admin.categories.create — cây phân cấp (depth), không
        // phải danh sách phẳng theo alphabet (xem ListCategoryTreeOptionsAction docblock).
        $categories = app(ListCategoryTreeOptionsAction::class)->handle($user);

        $assignableUsers = User::query()
            ->where('account_type', AccountType::Platform)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->values();

        return [
            'categories'      => $categories,
            'assignableUsers' => $assignableUsers,
            'statuses'        => array_map(fn (CalendarEntryStatus $s) => ['value' => $s->value, 'label' => $s->label(), 'badge' => $s->badgeClass()], CalendarEntryStatus::boardColumns()),
            'origins'         => array_map(fn (CalendarEntryOrigin $o) => ['value' => $o->value, 'label' => $o->label()], CalendarEntryOrigin::cases()),
            'canManage'       => $user->can('content_calendar.manage'),
        ];
    }
}
