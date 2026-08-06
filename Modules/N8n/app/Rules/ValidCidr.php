<?php

namespace Modules\N8n\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * spec/N8n_Integration_Technical_Specification.md §7.1 — chấp nhận CIDR IPv4 VÀ IPv6
 * (VD '203.0.113.0/24', '2001:db8::/32') cho `allowed_ip_cidrs.*`.
 */
class ValidCidr implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '/')) {
            $fail('Giá trị :attribute phải là 1 dải CIDR hợp lệ (VD 203.0.113.0/24).');

            return;
        }

        [$ip, $prefix] = array_pad(explode('/', $value, 2), 2, null);

        if (! is_numeric($prefix)) {
            $fail('Giá trị :attribute phải là 1 dải CIDR hợp lệ (VD 203.0.113.0/24).');

            return;
        }

        $prefix = (int) $prefix;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($prefix < 0 || $prefix > 32) {
                $fail('Giá trị :attribute không phải 1 dải CIDR IPv4 hợp lệ.');
            }

            return;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($prefix < 0 || $prefix > 128) {
                $fail('Giá trị :attribute không phải 1 dải CIDR IPv6 hợp lệ.');
            }

            return;
        }

        $fail('Giá trị :attribute không phải 1 dải CIDR hợp lệ.');
    }
}
