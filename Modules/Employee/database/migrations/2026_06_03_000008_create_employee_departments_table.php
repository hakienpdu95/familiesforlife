<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uses VIRTUAL generated column (not STORED) because MySQL 8 disallows
        // FK on a column referenced by a STORED generated column in the same table.
        DB::statement("
            CREATE TABLE employee_departments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                employee_id BIGINT UNSIGNED NOT NULL,
                department_id BIGINT UNSIGNED NOT NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                primary_lock BIGINT UNSIGNED AS (IF(is_primary = 1 AND left_at IS NULL, employee_id, NULL)) VIRTUAL,
                role_in_dept VARCHAR(50) NULL,
                joined_at DATE NULL,
                left_at DATE NULL,
                note TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_emp_dept_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
                CONSTRAINT fk_emp_dept_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,

                UNIQUE KEY uq_emp_dept_active (employee_id, department_id, left_at),
                UNIQUE KEY uq_primary_active (primary_lock),
                INDEX idx_emp_depts_emp (employee_id, left_at),
                INDEX idx_emp_depts_dept (department_id, is_primary)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        DB::statement("ALTER TABLE employee_departments ADD CONSTRAINT chk_emp_dept_role CHECK (role_in_dept IS NULL OR role_in_dept IN ('contributor','reviewer','lead','coordinator'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_departments');
    }
};
