<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Ocop\Models\OcopProduct;

/**
 * Ảnh sản phẩm OCOP chuyển từ collection `cover` (1 ảnh) sang `ocop_gallery` (1-n ảnh).
 * Đường dẫn file không chứa tên collection (MediaPathGenerator) nên chỉ cần đổi collection_name.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('media')
            ->where('model_type', OcopProduct::class)
            ->where('collection_name', 'cover')
            ->update(['collection_name' => OcopProduct::IMAGE_COLLECTION]);
    }

    public function down(): void
    {
        DB::table('media')
            ->where('model_type', OcopProduct::class)
            ->where('collection_name', OcopProduct::IMAGE_COLLECTION)
            ->update(['collection_name' => 'cover']);
    }
};
