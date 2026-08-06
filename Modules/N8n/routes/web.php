<?php

use Illuminate\Support\Facades\Route;
use Modules\N8n\Features\ConnectionManagement\Http\N8nConnectionApiController;
use Modules\N8n\Features\ConnectionManagement\Http\N8nConnectionController;
use Modules\N8n\Features\LogViewing\Http\N8nLogApiController;
use Modules\N8n\Features\LogViewing\Http\N8nLogController;

// spec/N8n_Integration_Technical_Specification.md §6 — admin CRUD kết nối + log. KHÔNG middleware
// 'tenant' — đúng mẫu dashboard/platform-users, dashboard/subscription/admin (Platform Roles,
// Lớp A, N8nConnection không thuộc tổ chức nào). Gate theo role/permission bên trong Controller
// + middleware 'can:manage-n8n' cho các route ghi (Gate::define ở N8nServiceProvider::boot()).
Route::middleware(['auth'])
    ->prefix('dashboard/n8n')
    ->name('backend.n8n.')
    ->group(function (): void {
        Route::prefix('connections')->name('connections.')->group(function (): void {
            Route::get('/', [N8nConnectionController::class, 'index'])->name('index');
            Route::get('create', [N8nConnectionController::class, 'create'])->middleware('can:manage-n8n')->name('create');
            Route::post('/', [N8nConnectionController::class, 'store'])->middleware('can:manage-n8n')->name('store');
            Route::get('{connection}/edit', [N8nConnectionController::class, 'edit'])->middleware('can:manage-n8n')->name('edit');
            Route::put('{connection}', [N8nConnectionController::class, 'update'])->middleware('can:manage-n8n')->name('update');
            Route::delete('{connection}', [N8nConnectionController::class, 'destroy'])->middleware('can:manage-n8n')->name('destroy'); // soft-delete
            Route::post('{connection}/restore', [N8nConnectionController::class, 'restore'])->middleware('can:manage-n8n')->name('restore');
            // body: {rotate_inbound_token, rotate_inbound_secret, rotate_outbound_secret} — §3.2.
            Route::post('{connection}/rotate', [N8nConnectionController::class, 'rotate'])->middleware('can:manage-n8n')->name('rotate');
        });

        Route::get('logs', [N8nLogController::class, 'index'])->name('logs.index'); // platform_ops HOẶC platform_viewer
    });

// JSON backend cho Tabulator (session-based auth, cùng guard trang quản trị) — cùng pattern
// Modules/Video/routes/web.php (backend/api/videos).
Route::middleware(['auth'])
    ->prefix('backend/api/n8n')
    ->name('backend.api.n8n.')
    ->group(function (): void {
        Route::get('connections', [N8nConnectionApiController::class, 'index'])->name('connections');
        Route::get('logs/inbound', [N8nLogApiController::class, 'inbound'])->name('logs.inbound');
        Route::get('logs/outbound', [N8nLogApiController::class, 'outbound'])->name('logs.outbound');
    });
