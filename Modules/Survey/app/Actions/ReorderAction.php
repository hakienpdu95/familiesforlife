<?php

namespace Modules\Survey\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Generic reorder: cập nhật sort_order cho nhiều rows trong 1 table.
 *
 * @param  string  $table    Tên bảng DB
 * @param  array   $items    [{id, sort_order}, ...]
 */
class ReorderAction
{
    use AsAction;

    public function handle(string $table, array $items): void
    {
        DB::transaction(function () use ($table, $items) {
            foreach ($items as $item) {
                DB::table($table)
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });
    }
}
