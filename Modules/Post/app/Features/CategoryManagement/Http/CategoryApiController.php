<?php

namespace Modules\Post\Features\CategoryManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Features\CategoryManagement\Actions\BuildCategoryAdminTreeAction;
use Modules\Post\Models\PostCategory;

class CategoryApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PostCategory::class);

        return response()->json(BuildCategoryAdminTreeAction::run(
            $request->user(),
            $request->string('search')->value() ?: null,
            $request->string('status')->value() ?: null,
        ));
    }
}
