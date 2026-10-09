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
        Schema::table('post_author_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('post_author_profiles', 'job_title')) {
                $table->string('job_title', 150)->nullable()->comment('Chức danh/nghề nghiệp chuyên môn (VD: Bác sĩ Nhi khoa) — dùng cho Person schema (E-E-A-T)');
            }
            if (!Schema::hasColumn('post_author_profiles', 'credentials')) {
                $table->string('credentials', 255)->nullable()->after('job_title')->comment('Bằng cấp/chứng chỉ chuyên môn (VD: Thạc sĩ Dinh dưỡng, 10 năm kinh nghiệm)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_author_profiles', function (Blueprint $table) {
            $cols = array_filter(['job_title', 'credentials'], fn($c) => Schema::hasColumn('post_author_profiles', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};