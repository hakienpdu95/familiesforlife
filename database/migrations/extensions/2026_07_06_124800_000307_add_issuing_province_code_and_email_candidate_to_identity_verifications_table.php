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
        Schema::table('identity_verifications', function (Blueprint $table) {
            if (!Schema::hasColumn('identity_verifications', 'issuing_province_code')) {
                $table->char('issuing_province_code', 2)->nullable()->comment('2-char province code từ provinces.province_code — derived từ 3 chữ số đầu CCCD');
            }
            if (!Schema::hasColumn('identity_verifications', 'email_candidate')) {
                $table->string('email_candidate', 255)->nullable()->after('issuing_province_code')->comment('Email address captured at send time; compared on confirm to detect mid-flight email changes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $cols = array_filter(['issuing_province_code', 'email_candidate'], fn($c) => Schema::hasColumn('identity_verifications', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};