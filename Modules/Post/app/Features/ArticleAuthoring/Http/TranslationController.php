<?php

namespace Modules\Post\Features\ArticleAuthoring\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Post\Features\ArticleAuthoring\Actions\ApproveArticleTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\ArchiveArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CancelScheduleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\CreateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\DeleteTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\FastTrackApprovalAction;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\ReturnArticleToDraftAction;
use Modules\Post\Features\ArticleAuthoring\Actions\ScheduleArticleAction;
use Modules\Post\Features\ArticleAuthoring\Actions\SubmitArticleForReviewAction;
use Modules\Post\Features\ArticleAuthoring\Actions\TakeDownArticleTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\UnpublishArticleTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Actions\UpdateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Features\ArticleAuthoring\Exceptions\CannotDeleteMainLocaleException;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Features\ArticleAuthoring\Exceptions\ProductBlockValidationException;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;

class TranslationController extends Controller
{
    public function store(Request $request, PostArticle $article, CreateTranslationAction $action): RedirectResponse
    {
        // Article vừa tạo (chưa có translation nào) — không có Translation nào để authorize
        // theo Policy, fallback về quyền tạo bài viết nói chung. Nếu đã có ≥1 translation,
        // dùng chính translation đó (thường là main_locale) để check quyền update.
        $existing = $article->mainTranslation() ?? $article->translations()->first();

        if ($existing) {
            $this->authorize('update', $existing);
        } else {
            $this->authorize('create', PostArticle::class);
        }

        $locale = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', array_keys(config('post.locales')))],
        ])['locale'];

        abort_if($article->translation($locale), 422, __('Bản dịch ":locale" đã tồn tại.', ['locale' => $locale]));

        $data = TranslationData::from($this->validated($request, null, $locale, $article));

        try {
            $translation = $action->handle($article, $locale, $data);
        } catch (ProductBlockValidationException $e) {
            return back()->withInput()->withErrors(['blocks' => $e->errors]);
        }

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', __('Đã tạo bản dịch ":locale".', ['locale' => $locale]))
            ->with('active_locale', $translation->locale);
    }

    public function update(Request $request, PostArticleTranslation $translation, UpdateTranslationAction $action): RedirectResponse
    {
        $this->authorize('update', $translation);

        $data = TranslationData::from($this->validated($request, $translation, $translation->locale, null));

        try {
            $action->handle($translation, $data);
        } catch (ProductBlockValidationException $e) {
            return back()->withInput()->withErrors(['blocks' => $e->errors]);
        }

        return redirect()->route('backend.post.articles.edit', $translation->article)
            ->with('success', __('Cập nhật bản dịch thành công.'))
            ->with('active_locale', $translation->locale);
    }

    public function destroy(PostArticleTranslation $translation, DeleteTranslationAction $action): RedirectResponse
    {
        $this->authorize('delete', $translation);

        $article = $translation->article;
        $locale = $translation->locale;

        try {
            $action->handle($translation);
        } catch (CannotDeleteMainLocaleException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (PostArticle::find($article->id) === null) {
            return redirect()->route('backend.post.articles.index')->with('success', __('Đã xoá bài viết (không còn bản dịch nào).'));
        }

        return redirect()->route('backend.post.articles.edit', $article)
            ->with('success', __('Đã xoá bản dịch ":locale".', ['locale' => $locale]));
    }

    public function submit(Request $request, PostArticleTranslation $translation, SubmitArticleForReviewAction $action): RedirectResponse
    {
        $this->authorize('submitForReview', $translation);

        if (Gate::allows('publish', $translation)) {
            return back()->with('error', __('Tài khoản của bạn có quyền xuất bản trực tiếp, không cần gửi duyệt.'));
        }

        $user = $request->user();
        $toHead = Gate::allows('approve', $translation);

        return $this->runTransition(
            $translation,
            fn () => $action->handle($translation, $user),
            $toHead ? __('Đã gửi bản dịch lên Tổng biên tập duyệt xuất bản.') : __('Đã gửi bản dịch tới Biên tập viên duyệt sơ bộ.'),
        );
    }

    public function approve(Request $request, PostArticleTranslation $translation, ApproveArticleTranslationAction $action): RedirectResponse
    {
        $this->authorize('approve', $translation);

        return $this->runTransition($translation, fn () => $action->handle($translation, $request->user()), __('Đã duyệt sơ bộ, chuyển Tổng biên tập duyệt xuất bản.'));
    }

    public function returnToDraft(Request $request, PostArticleTranslation $translation, ReturnArticleToDraftAction $action): RedirectResponse
    {
        $this->authorize('returnToDraft', $translation);

        $reason = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']])['reason'];

        return $this->runTransition($translation, fn () => $action->handle($translation, $request->user(), $reason), __('Đã trả bài về người viết để chỉnh sửa.'));
    }

    public function publish(Request $request, PostArticleTranslation $translation, PublishArticleAction $action, FastTrackApprovalAction $fastTrack): RedirectResponse
    {
        $this->authorize('publish', $translation);

        return $this->runTransition($translation, fn () => DB::transaction(function () use ($request, $translation, $action, $fastTrack) {
            $fastTrack->handle($translation, $request->user());
            $action->handle($translation);
        }), __('Đã xuất bản bản dịch.'));
    }

    public function schedule(Request $request, PostArticleTranslation $translation, ScheduleArticleAction $action, FastTrackApprovalAction $fastTrack): RedirectResponse
    {
        $this->authorize('schedule', $translation);

        $validated = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);

        return $this->runTransition(
            $translation,
            fn () => DB::transaction(function () use ($request, $translation, $action, $fastTrack, $validated) {
                $fastTrack->handle($translation, $request->user());
                $action->handle($translation, Carbon::parse($validated['scheduled_at']));
            }),
            __('Đã lên lịch xuất bản bản dịch.'),
        );
    }

    public function cancelSchedule(PostArticleTranslation $translation, CancelScheduleAction $action): RedirectResponse
    {
        $this->authorize('schedule', $translation);

        return $this->runTransition($translation, fn () => $action->handle($translation), __('Đã huỷ lịch xuất bản.'));
    }

    public function unpublish(Request $request, PostArticleTranslation $translation, UnpublishArticleTranslationAction $action): RedirectResponse
    {
        $this->authorize('unpublish', $translation);

        $reason = $request->validate(['reason' => ['required', 'string', 'min:10']])['reason'];

        return $this->runTransition($translation, fn () => $action->handle($translation, $reason), __('Đã gỡ bản dịch.'));
    }

    public function takedown(Request $request, PostArticleTranslation $translation, TakeDownArticleTranslationAction $action): RedirectResponse
    {
        $this->authorize('unpublish', $translation);

        $reason = $request->validate(['reason' => ['required', 'string', 'min:10']])['reason'];

        return $this->runTransition($translation, fn () => $action->handle($translation, $reason), __('Đã gỡ khẩn cấp bản dịch.'));
    }

    public function archive(PostArticleTranslation $translation, ArchiveArticleAction $action): RedirectResponse
    {
        $this->authorize('archive', $translation);

        return $this->runTransition($translation, fn () => $action->handle($translation), __('Đã lưu trữ bản dịch.'));
    }

    private function runTransition(PostArticleTranslation $translation, \Closure $callback, string $successMessage): RedirectResponse
    {
        try {
            $callback();
            $translation->loadMissing('article');
        } catch (InvalidTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('backend.post.articles.edit', $translation->article)
            ->with('success', $successMessage)
            ->with('active_locale', $translation->locale);
    }

    private function validated(Request $request, ?PostArticleTranslation $translation, string $locale, ?PostArticle $article): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'slug' => [
                'nullable', 'string', 'max:320', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('post_article_translations', 'slug')
                    ->where(fn ($q) => $q->where('locale', $locale))
                    ->ignore($translation?->id),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'direct_answer' => ['nullable', 'string', 'max:500'],
            'blocks_json' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:300'],

            // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §6.2 — dùng closure (không phải attribute
            // #[RequiredIf] trên DTO) vì điều kiện nằm ở ArticleData, 1 DTO KHÁC với
            // TranslationData đang validate; Spatie Data không hỗ trợ required-if tham chiếu
            // chéo giữa 2 DTO. $translation->article dùng khi update (translation đã tồn tại);
            // $article (route param truyền vào) dùng khi tạo mới translation đầu tiên — 2 nguồn
            // loại trừ nhau (không bao giờ cả 2 cùng null hoặc cùng có giá trị).
            'disclosure_text' => [
                Rule::requiredIf(fn () => ($translation?->article ?? $article)->is_sponsored),
                'nullable', 'string', 'max:500',
            ],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'url', 'max:500'],
        ]);

        $blocks = json_decode($validated['blocks_json'] ?? '[]', true);
        $validated['blocks'] = is_array($blocks) ? $blocks : [];
        unset($validated['blocks_json']);

        return $validated;
    }
}
