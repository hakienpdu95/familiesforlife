<?php

namespace Modules\Auth\Rules;

use App\Shared\Tenancy\Enums\OrganizationStatus;
use App\Shared\Tenancy\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class NotOrgDomainEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = Str::after($value, '@');

        $isOrgDomain = Organization::where('email_domain', $domain)
            ->where('status', OrganizationStatus::Active->value)
            ->exists();

        if ($isOrgDomain) {
            $fail(
                "Email @{$domain} là email tổ chức. " .
                "Vui lòng dùng email cá nhân (Gmail, Yahoo, Outlook cá nhân...) " .
                "hoặc đổi email chính trong cài đặt LinkedIn."
            );
        }
    }
}
