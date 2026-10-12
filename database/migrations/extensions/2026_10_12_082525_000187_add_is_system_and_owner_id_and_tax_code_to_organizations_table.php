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
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('settings')->comment('True = org hệ thống mặc định cho super-admin');
            }
            if (!Schema::hasColumn('organizations', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->index()->after('is_system')->comment('ID người sở hữu — không có FK ở DB');
            }
            if (!Schema::hasColumn('organizations', 'tax_code')) {
                $table->string('tax_code', 20)->nullable()->after('owner_id')->comment('Mã số thuế doanh nghiệp');
            }
            if (!Schema::hasColumn('organizations', 'phone')) {
                $table->string('phone', 20)->nullable()->after('tax_code')->comment('Số điện thoại liên hệ');
            }
            if (!Schema::hasColumn('organizations', 'email')) {
                $table->string('email', 255)->nullable()->after('phone')->comment('Email liên hệ của tổ chức');
            }
            if (!Schema::hasColumn('organizations', 'website')) {
                $table->string('website', 255)->nullable()->after('email')->comment('Website của tổ chức');
            }
            if (!Schema::hasColumn('organizations', 'industry')) {
                $table->string('industry', 100)->nullable()->index()->after('website')->comment('Ngành nghề kinh doanh');
            }
            if (!Schema::hasColumn('organizations', 'address')) {
                $table->string('address', 500)->nullable()->after('industry')->comment('Địa chỉ đầy đủ');
            }
            if (!Schema::hasColumn('organizations', 'city')) {
                $table->string('city', 100)->nullable()->after('address')->comment('Thành phố');
            }
            if (!Schema::hasColumn('organizations', 'country')) {
                $table->string('country', 2)->default('VN')->index()->after('city')->comment('Mã quốc gia ISO 3166-1');
            }
            if (!Schema::hasColumn('organizations', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('country')->comment('Mã bưu chính');
            }
            if (!Schema::hasColumn('organizations', 'description')) {
                $table->text('description')->nullable()->after('postal_code')->comment('Mô tả về tổ chức');
            }
            if (!Schema::hasColumn('organizations', 'logo_path')) {
                $table->string('logo_path', 500)->nullable()->after('description')->comment('Đường dẫn đến file logo');
            }
            if (!Schema::hasColumn('organizations', 'province_code')) {
                $table->char('province_code', 2)->nullable()->after('logo_path')->comment('Tỉnh/thành phố — FK tới provinces.province_code');
                $table->foreign('province_code')->references('province_code')->on('provinces')->nullOnDelete();
            }
            if (!Schema::hasColumn('organizations', 'ward_code')) {
                $table->char('ward_code', 5)->nullable()->after('province_code')->comment('Phường/xã — FK tới wards.ward_code');
                $table->foreign('ward_code')->references('ward_code')->on('wards')->nullOnDelete();
            }
            if (!Schema::hasColumn('organizations', 'full_address')) {
                $table->text('full_address')->nullable()->after('ward_code')->comment('Địa chỉ đầy đủ kết hợp (số nhà + phường/xã + tỉnh)');
            }
            if (!Schema::hasIndex('organizations', 'organizations_province_code_ward_code_status_created_at_index')) {
                $table->index(['province_code', 'ward_code', 'status', 'created_at']);
            }
            if (!Schema::hasColumn('organizations', 'lead_assessment_code')) {
                $table->string('lead_assessment_code', 64)->nullable()->after('full_address')->comment('Assessment code dùng để chấm điểm lead sâu. NULL = tắt.');
            }
            if (!Schema::hasColumn('organizations', 'source')) {
                $table->string('source', 50)->nullable()->after('lead_assessment_code')->comment('marketplace_signup | admin_created | etc.');
            }
            if (!Schema::hasColumn('organizations', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('source');
            }
            if (!Schema::hasColumn('organizations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('organizations', 'email_domain')) {
                $table->string('email_domain', 100)->nullable()->after('approved_at')->comment('VD: company.com — dùng để phát hiện email tổ chức khi HR tạo tài khoản NV');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'province_code')) $table->dropForeign(['province_code']);
            if (Schema::hasColumn('organizations', 'ward_code')) $table->dropForeign(['ward_code']);
            $cols = array_filter(['is_system', 'owner_id', 'tax_code', 'phone', 'email', 'website', 'industry', 'address', 'city', 'country', 'postal_code', 'description', 'logo_path', 'province_code', 'ward_code', 'full_address', 'lead_assessment_code', 'source', 'approved_by', 'approved_at', 'email_domain'], fn($c) => Schema::hasColumn('organizations', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};