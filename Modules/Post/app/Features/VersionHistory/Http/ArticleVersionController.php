<?php

namespace Modules\Post\Features\VersionHistory\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Features\VersionHistory\Actions\CompareArticleVersionsAction;
use Modules\Post\Features\VersionHistory\Actions\RestoreArticleVersionAction;
use Modules\Post\Features\VersionHistory\Exceptions\VersionRestoreException;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostArticleVersion;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §13.2/§13.3 — mọi endpoint trả JSON,
 * phục vụ Alpine.js ở edit.blade.php (không phải full-page redirect như các action lifecycle
 * khác trong TranslationController).
 */
class ArticleVersionController extends Controller
{
    public function index(Request $request, PostArticleTranslation $translation): JsonResponse
    {
        $this->authorize('viewHistory', $translation);

        $perPage = $request->integer('per_page') ?: $request->integer('limit') ?: 20;

        $versions = $translation->versions()
            ->with(['createdBy:id,name', 'restoredFrom:id,version_number'])
            ->paginate($perPage);

        $data = $versions->getCollection()->map(function (PostArticleVersion $version) use ($translation) {
            // §13.3 — char_delta/block_delta so với version liền trước (version_number - 1),
            // tính ở tầng Query (không lưu sẵn), tránh phải update lại version cũ khi có version
            // mới chèn vào.
            $previous = $translation->versions()
                ->where('version_number', $version->version_number - 1)
                ->first(['char_count', 'block_count']);

            return [
                'id'                            => $version->id,
                'version_number'                => $version->version_number,
                'trigger'                       => $version->trigger->value,
                'trigger_label'                 => $version->trigger->label(),
                'title_snapshot'                => $version->title_snapshot,
                'char_count'                    => $version->char_count,
                'block_count'                   => $version->block_count,
                'char_delta'                    => $version->char_count - ($previous?->char_count ?? 0),
                'block_delta'                   => $version->block_count - ($previous?->block_count ?? 0),
                'restored_from_version_number'  => $version->restoredFrom?->version_number,
                'created_by'                    => $version->createdBy ? ['id' => $version->createdBy->id, 'name' => $version->createdBy->name] : null,
                'created_at'                    => $version->created_at?->toIso8601String(),
                'created_at_human'              => $version->created_at?->diffForHumans(),
            ];
        });

        return response()->json([
            'data' => $data->values(),
            'meta' => [
                'current_page' => $versions->currentPage(),
                'last_page'    => $versions->lastPage(),
                'total'        => $versions->total(),
            ],
        ]);
    }

    public function show(PostArticleTranslation $translation, PostArticleVersion $version, ArticleContentRenderer $renderer): JsonResponse
    {
        $this->authorize('viewHistory', $translation);
        abort_unless($version->translation_id === $translation->id, 404);

        $version->loadMissing('createdBy:id,name');
        $rendered = $renderer->renderSnapshot($version->snapshot['blocks'] ?? []);

        return response()->json([
            'version' => [
                'id'             => $version->id,
                'version_number' => $version->version_number,
                'trigger'        => $version->trigger->value,
                'created_by'     => $version->createdBy ? ['id' => $version->createdBy->id, 'name' => $version->createdBy->name] : null,
                'created_at'     => $version->created_at?->toIso8601String(),
            ],
            'translation_snapshot' => $version->snapshot['translation'] ?? [],
            'rendered_html'         => $rendered['html'],
            'missing_products'      => $rendered['missing_products'],
        ]);
    }

    public function compare(Request $request, PostArticleTranslation $translation, CompareArticleVersionsAction $action): JsonResponse
    {
        $this->authorize('viewHistory', $translation);

        $validated = $request->validate([
            'from' => ['required', 'integer'],
            'to'   => ['required', 'integer'],
        ]);

        $from = $translation->versions()->findOrFail($validated['from']);
        $to   = $translation->versions()->findOrFail($validated['to']);

        return response()->json($action->handle($from, $to));
    }

    public function restore(PostArticleTranslation $translation, PostArticleVersion $version, RestoreArticleVersionAction $action): JsonResponse
    {
        $this->authorize('restoreVersion', $translation);
        abort_unless($version->translation_id === $translation->id, 404);

        try {
            $action->handle($version, (int) auth()->id());
        } catch (VersionRestoreException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // §13.3 — KHÔNG trả version_number của version restore vừa tạo (ghi bất đồng bộ, §9.4).
        return response()->json([
            'message'          => "Đã khôi phục nội dung từ phiên bản #{$version->version_number}.",
            'translation_uuid' => $translation->uuid,
        ]);
    }
}
