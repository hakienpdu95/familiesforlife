<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SystemSeed extends Command
{
    protected $signature = 'system:seed
        {--force : Bỏ qua xác nhận ở môi trường production}';

    protected $description = 'Seed toàn bộ dữ liệu mặc định hệ thống qua Master Seeder (idempotent)';

    public function handle(): int
    {
        if (app()->isProduction() && !$this->option('force')) {
            $this->warn('Môi trường production! Lệnh này sẽ thêm/cập nhật dữ liệu mặc định.');
            if (!$this->confirm('Tiếp tục?')) {
                return self::SUCCESS;
            }
        }

        return $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\SystemDataSeeder',
            '--force' => true,
        ]) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
