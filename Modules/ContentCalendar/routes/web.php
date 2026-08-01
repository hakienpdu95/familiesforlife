<?php

use Illuminate\Support\Facades\Route;
use Modules\ContentCalendar\Features\CalendarPlanning\Http\CalendarEntryController;

// spec/ContentCalendar_Technical_Specification.md §8 — middleware chỉ gate 'content_calendar.view'
// (ai xem được board thì vào được trang) — quyền ghi cụ thể theo từng entry (create/update/delete)
// check bằng $this->authorize() trong từng method controller, đúng pattern ArticleAdminController.
Route::middleware(['auth', 'can:content_calendar.view'])
    ->prefix('dashboard/content-calendar')
    ->name('backend.contentcalendar.')
    ->group(function (): void {
        Route::get('/', [CalendarEntryController::class, 'board'])->name('board');
        Route::get('/schedule', [CalendarEntryController::class, 'calendar'])->name('calendar');
    });

Route::middleware(['auth', 'can:content_calendar.view'])
    ->prefix('backend/api/content-calendar')
    ->name('backend.api.contentcalendar.')
    ->group(function (): void {
        Route::get('entries', [CalendarEntryController::class, 'list'])->name('entries.list');
        Route::post('entries', [CalendarEntryController::class, 'store'])->name('entries.store');
        Route::put('entries/{entry:uuid}', [CalendarEntryController::class, 'update'])->name('entries.update');
        Route::patch('entries/{entry:uuid}/status', [CalendarEntryController::class, 'changeStatus'])->name('entries.change-status');
        Route::post('entries/{entry:uuid}/link-article', [CalendarEntryController::class, 'linkArticle'])->name('entries.link-article');
        Route::delete('entries/{entry:uuid}', [CalendarEntryController::class, 'destroy'])->name('entries.destroy');
        Route::get('categories/{category}/planned-titles', [CalendarEntryController::class, 'plannedTitles'])->name('categories.planned-titles');
    });
