<?php

namespace Modules\AIVideoStudioTemplate\Features\FormulaAdvisor\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\AIVideoStudioTemplate\Features\FormulaAdvisor\Actions\BuildMasterScriptPromptAction;
use Modules\AIVideoStudioTemplate\Features\FormulaAdvisor\Support\ProductFormulaCatalog;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §13 (v1.25) — 1 trang GET duy nhất. Trước
 * v1.25 trang này tự tính công thức đề xuất (`FormulaDecisionTree`, đã xoá); từ v1.25 chỉ RENDER 1
 * Master Prompt để người dùng copy sang AI ngoài — AI ngoài mới là nơi chọn công thức + viết kịch
 * bản. Vẫn thuần GET (không ghi DB, không CSRF) — cùng lý do v1.23.
 */
class FormulaAdvisorController extends Controller
{
    public function index(Request $request, BuildMasterScriptPromptAction $buildPrompt): View
    {
        $search = trim((string) $request->query('q', ''));
        $selectedSlug = $request->query('product');
        $selected = $selectedSlug ? ProductFormulaCatalog::find($selectedSlug) : null;

        $topic = trim((string) $request->query('topic', ''));
        $description = $request->query('description');
        $isMotherBaby = $request->boolean('is_mother_baby');

        return view('aivideostudiotemplate::formula-advisor', [
            'catalog' => ProductFormulaCatalog::search($search),
            'search' => $search,
            'selectedSlug' => $selectedSlug,
            'selected' => $selected,
            'bannedCategories' => ProductFormulaCatalog::BANNED_CATEGORIES,
            'forbiddenCombos' => ProductFormulaCatalog::FORBIDDEN_COMBOS,
            'productionBudget' => ProductFormulaCatalog::PRODUCTION_BUDGET,
            'topic' => $topic,
            'description' => $description,
            'isMotherBaby' => $isMotherBaby,
            'masterPrompt' => $topic !== '' ? $buildPrompt->handle($topic, $description, $isMotherBaby) : null,
        ]);
    }
}
