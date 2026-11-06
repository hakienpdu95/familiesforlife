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
        Schema::table('approval_subjects', function (Blueprint $table) {
            if (!Schema::hasIndex('approval_subjects', 'idx_approval_status_type')) {
                $table->index(['status', 'subject_type'], 'idx_approval_status_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('approval_subjects', function (Blueprint $table) {
            $cols = array_filter([], fn($c) => Schema::hasColumn('approval_subjects', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};