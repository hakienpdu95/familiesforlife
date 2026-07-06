<?php

namespace App\Foundation\Vertical;

use App\Foundation\VerticalRegistry;

class DeactivateVerticalAction
{
    public function execute(int $organizationId, string $code): void
    {
        // Query builder update — không đi qua Eloquent save(), không tự trigger
        // VerticalTemplate::booted() → phải tự gọi clearCache().
        $updated = VerticalTemplate::where('organization_id', $organizationId)
            ->where('code', $code)
            ->update(['status' => 'inactive']);

        if (! $updated) {
            throw new \InvalidArgumentException("Vertical '{$code}' chưa được kích hoạt cho tổ chức này.");
        }

        VerticalRegistry::clearCache($organizationId, $code);
    }
}
