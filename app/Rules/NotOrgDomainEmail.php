<?php

namespace App\Rules;

use App\Shared\Tenancy\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class NotOrgDomainEmail implements ValidationRule
{
    public function __construct(
        private readonly Organization $organization,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $orgDomain = $this->organization->email_domain;

        if (!$orgDomain) {
            return;
        }

        $inputDomain = Str::after($value, '@');

        if (strtolower($inputDomain) === strtolower($orgDomain)) {
            $fail("Email @{$orgDomain} thuộc tổ chức. Vui lòng nhập email cá nhân của nhân viên (Gmail, Yahoo, Outlook cá nhân...) để đảm bảo họ có thể truy cập Passport sau khi rời tổ chức.");
        }
    }
}
