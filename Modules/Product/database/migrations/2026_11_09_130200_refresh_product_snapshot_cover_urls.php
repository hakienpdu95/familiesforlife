<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Approval\Models\ApprovalSubject;
use Modules\Product\Models\Product;

return new class extends Migration
{
    public function up(): void
    {
        ApprovalSubject::query()
            ->where('subject_type', (new Product)->getMorphClass())
            ->orWhere('subject_type', Product::class)
            ->get()
            ->each(function (ApprovalSubject $approval) {
                $snapshot = $approval->public_snapshot;

                if (! is_array($snapshot) || empty($snapshot['cover_image_url'])) {
                    return;
                }

                $fixed = str_replace('/storage/media/product/product/', '/storage/media/core/product/', $snapshot['cover_image_url']);

                if ($fixed !== $snapshot['cover_image_url']) {
                    $snapshot['cover_image_url'] = $fixed;
                    $approval->forceFill(['public_snapshot' => $snapshot])->saveQuietly();
                }
            });
    }

    public function down(): void {}
};
