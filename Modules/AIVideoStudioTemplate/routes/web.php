<?php

use Illuminate\Support\Facades\Route;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http\ProjectApiController;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http\ProjectController;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\ShotApiController;

// spec/AIVideoStudioTemplate_Technical_Specification.md §5/§6 — gate phẳng bằng permission
// 'ai_video_studio_template.use', KHÔNG Policy riêng theo model (không owner-based ACL).
Route::middleware(['auth', 'can:ai_video_studio_template.use'])
    ->prefix('dashboard/ai-video-studio')
    ->name('backend.aivideostudiotemplate.')
    ->group(function (): void {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [ProjectController::class, 'show'])->name('show');
        Route::get('{project}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::get('{project}/export', [ProjectController::class, 'export'])->name('export');
    });

// JSON — quản lý Shot inline trên trang show (fetch, không reload trang), §6.1.
Route::middleware(['auth', 'can:ai_video_studio_template.use'])
    ->prefix('backend/api/ai-video-studio')
    ->name('backend.api.aivideostudiotemplate.')
    ->group(function (): void {
        Route::get('projects', [ProjectApiController::class, 'index'])->name('projects');
        Route::post('projects/{project}/shots', [ShotApiController::class, 'store'])->name('shots.store');
        Route::put('shots/{shot}', [ShotApiController::class, 'update'])->name('shots.update');
        Route::delete('shots/{shot}', [ShotApiController::class, 'destroy'])->name('shots.destroy');
        Route::post('projects/{project}/shots/reorder', [ShotApiController::class, 'reorder'])->name('shots.reorder');
        Route::put('shots/{shot}/result', [ShotApiController::class, 'saveResult'])->name('shots.save-result');
    });
