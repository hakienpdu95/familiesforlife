<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopSubject\Models\OcopSubject;

class DeleteOcopSubjectAction
{
    use AsAction;

    public function handle(OcopSubject $ocopSubject): void
    {
        $count = $ocopSubject->products()->count();

        if ($count > 0) {
            throw ValidationException::withMessages([
                'ocop_subject' => "Không thể xoá — chủ thể đang có {$count} sản phẩm OCOP. Chuyển các sản phẩm sang chủ thể khác trước.",
            ]);
        }

        $ocopSubject->delete();
    }
}
