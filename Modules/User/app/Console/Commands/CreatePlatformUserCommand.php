<?php

namespace Modules\User\Console\Commands;

use App\Enums\RoleEnum;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Modules\User\Actions\CreateAccountAction;
use Modules\User\Data\CreateAccountData;

class CreatePlatformUserCommand extends Command
{
    protected $signature = 'platform:user-create
        {email : Email đăng nhập}
        {name : Họ và tên hiển thị}
        {role : Một vai trò Platform (không gồm super-admin)}';

    protected $description = 'Tạo tài khoản Platform và gửi email đặt mật khẩu — không tạo được super-admin';

    public function handle(): int
    {
        try {
            $data = CreateAccountData::validateAndCreate([
                'email' => $this->argument('email'),
                'name' => $this->argument('name'),
                'role' => $this->argument('role'),
            ]);
        } catch (ValidationException $e) {
            $this->error('Dữ liệu không hợp lệ:');
            foreach ($e->validator->errors()->all() as $message) {
                $this->line("  - {$message}");
            }
            $this->line('  Vai trò hợp lệ: '.implode(', ', RoleEnum::assignableValues()));

            return self::FAILURE;
        }

        $user = CreateAccountAction::run($data, 'artisan platform:user-create');

        $this->info("✓ Đã tạo tài khoản {$user->email} (".RoleEnum::from($data->role)->label().') và gửi email đặt mật khẩu.');

        return self::SUCCESS;
    }
}
