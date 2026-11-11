<?php

namespace Modules\User\Data;

use App\Support\PhoneNumber;
use App\Support\Username;

final class AccountProfileFields
{
    public static function prepare(array $properties): array
    {
        $trim = fn (string $key) => filled($properties[$key] ?? null) ? trim((string) $properties[$key]) : null;

        return array_merge($properties, [
            'name' => $trim('name'),
            'username' => filled($properties['username'] ?? null) ? Username::normalize($properties['username']) : null,
            'phone' => PhoneNumber::normalize($properties['phone'] ?? null),
            'job_title' => $trim('job_title'),
            'department' => $trim('department'),
        ]);
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => PhoneNumber::rules(),
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
        ];
    }

    public static function messages(): array
    {
        return [
            'username.regex' => __('Tên đăng nhập chỉ gồm chữ thường a-z, số và . _ -, bắt đầu và kết thúc bằng chữ hoặc số.'),
            'username.not_regex' => __('Tên đăng nhập không được có hai ký tự . _ - liền nhau.'),
            'username.unique' => __('Tên đăng nhập này đã được sử dụng.'),
            'phone.regex' => __('Số điện thoại không hợp lệ (10–11 số, bắt đầu bằng 0 hoặc +84).'),
            'role.in' => __('Vai trò không hợp lệ.'),
            'role.exists' => __('Vai trò chưa tồn tại trong bảng roles — chạy seeder tương ứng trước.'),
        ];
    }
}
