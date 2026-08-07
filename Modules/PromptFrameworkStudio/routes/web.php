<?php

use Illuminate\Support\Facades\Route;
use Modules\PromptFrameworkStudio\Features\FrameworkLibrary\Http\FrameworkLibraryController;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\PromptGenerationApiController;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\PromptGenerationController;

// spec/PromptFrameworkStudio_Technical_Specification.md §6 — gate phẳng bằng permission
// 'prompt_framework_studio.use' (cùng nhóm CONTENT_OUTLINES_USE/CORE_IDEA_EXTRACTOR_USE), KHÔNG
// Policy riêng theo model (§2.1/§5 — không owner-based ACL).
Route::middleware(['auth', 'can:prompt_framework_studio.use'])
    ->prefix('dashboard/prompt-studio')
    ->name('backend.promptstudio.')
    ->group(function (): void {
        Route::get('library', [FrameworkLibraryController::class, 'index'])->name('library');

        Route::prefix('prompts')->name('prompts.')->group(function (): void {
            Route::get('/', [PromptGenerationController::class, 'index'])->name('index');
            Route::get('create', [PromptGenerationController::class, 'create'])->name('create');
            Route::post('/', [PromptGenerationController::class, 'store'])->name('store');
            Route::get('{prompt}', [PromptGenerationController::class, 'show'])->name('show');
            Route::get('{prompt}/edit', [PromptGenerationController::class, 'edit'])->name('edit');
            Route::put('{prompt}', [PromptGenerationController::class, 'update'])->name('update'); // = "Sinh lại"
            Route::delete('{prompt}', [PromptGenerationController::class, 'destroy'])->name('destroy');
        });
    });

// JSON backend cho Tabulator (session-based auth, cùng guard trang quản trị).
Route::middleware(['auth', 'can:prompt_framework_studio.use'])
    ->prefix('backend/api/prompt-studio')
    ->name('backend.api.promptstudio.')
    ->group(function (): void {
        Route::get('prompts', [PromptGenerationApiController::class, 'index'])->name('prompts');

        // §4.4 (v2.7) — xem trước khối "Bối cảnh biên tập" sẽ chèn cho 1 chuyên mục. Gác bằng
        // permission CỦA MODULE NÀY (không phải content_foundation.use) — xem docblock action.
        Route::get('editorial-context/{category}', [PromptGenerationApiController::class, 'editorialContext'])
            ->name('editorial-context');
    });
