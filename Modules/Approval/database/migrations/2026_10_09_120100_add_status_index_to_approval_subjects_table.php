<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_subjects', function (Blueprint $table) {
            $table->index(['status', 'subject_type'], 'idx_approval_status_type');
        });
    }

    public function down(): void
    {
        Schema::table('approval_subjects', function (Blueprint $table) {
            $table->dropIndex('idx_approval_status_type');
        });
    }
};
