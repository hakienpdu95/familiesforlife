<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable();
            }
            if (!Schema::hasColumn('media', 'uploaded_at')) {
                $table->timestamp('uploaded_at')->nullable()->after('organization_id')->comment('Timestamp user thực sự upload — bảo toàn khi migrate từ bảng cũ');
            }
            if (!Schema::hasColumn('media', 'last_touched_at')) {
                $table->timestamp('last_touched_at')->nullable()->after('uploaded_at')->comment('Jodit orphan TTL: update mỗi khi session active; NULL = không phải jodit orphan');
            }
            if (!Schema::hasIndex('media', 'idx_media_polymorphic')) {
                $table->index(['model_type', 'model_id', 'collection_name'], 'idx_media_polymorphic');
            }
            if (!Schema::hasIndex('media', 'idx_media_disk')) {
                $table->index(['disk', 'created_at'], 'idx_media_disk');
            }
            if (!Schema::hasIndex('media', 'idx_media_orphan')) {
                $table->index(['collection_name', 'model_type', 'last_touched_at'], 'idx_media_orphan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $cols = array_filter(['organization_id', 'uploaded_at', 'last_touched_at'], fn($c) => Schema::hasColumn('media', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};