<?php

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Laravelcm\Subscriptions\Models\Plan;

class PlanSeeder extends Seeder
{
    private array $plans = [
        [
            'slug'             => 'starter',
            'name'             => ['vi' => 'Starter'],
            'description'      => ['vi' => 'Gói miễn phí để bắt đầu'],
            'price'            => 0.00,
            'annual_price'     => null,
            'currency'         => 'VND',
            'invoice_interval' => 'month',
            'invoice_period'   => 1,
            'trial_period'     => 14,
            'trial_interval'   => 'day',
            'grace_period'     => 3,
            'grace_interval'   => 'day',
            'is_active'        => true,
            'is_public'        => true,
            'tier'             => 'starter',
            'tag_line'         => null,
            'badge_color'      => null,
            'sort_order'       => 1,
        ],
        [
            'slug'             => 'growth',
            'name'             => ['vi' => 'Growth'],
            'description'      => ['vi' => 'Dành cho đội nhóm đang tăng trưởng'],
            'price'            => 990000.00,
            'annual_price'     => 9900000.00,
            'currency'         => 'VND',
            'invoice_interval' => 'month',
            'invoice_period'   => 1,
            'trial_period'     => 0,
            'trial_interval'   => 'day',
            'grace_period'     => 3,
            'grace_interval'   => 'day',
            'is_active'        => true,
            'is_public'        => true,
            'tier'             => 'growth',
            'tag_line'         => 'Phổ biến nhất',
            'badge_color'      => 'badge-primary',
            'sort_order'       => 2,
        ],
        [
            'slug'             => 'scale',
            'name'             => ['vi' => 'Scale'],
            'description'      => ['vi' => 'Dành cho doanh nghiệp tăng trưởng mạnh'],
            'price'            => 2490000.00,
            'annual_price'     => 24900000.00,
            'currency'         => 'VND',
            'invoice_interval' => 'month',
            'invoice_period'   => 1,
            'trial_period'     => 0,
            'trial_interval'   => 'day',
            'grace_period'     => 7,
            'grace_interval'   => 'day',
            'is_active'        => true,
            'is_public'        => true,
            'tier'             => 'scale',
            'tag_line'         => 'Tăng trưởng mạnh',
            'badge_color'      => 'badge-success',
            'sort_order'       => 3,
        ],
        [
            'slug'             => 'enterprise',
            'name'             => ['vi' => 'Enterprise'],
            'description'      => ['vi' => 'Giải pháp không giới hạn cho doanh nghiệp lớn'],
            'price'            => 0.00,
            'annual_price'     => null,
            'currency'         => 'VND',
            'invoice_interval' => 'month',
            'invoice_period'   => 1,
            'trial_period'     => 0,
            'trial_interval'   => 'day',
            'grace_period'     => 7,
            'grace_interval'   => 'day',
            'is_active'        => true,
            'is_public'        => false,
            'tier'             => 'enterprise',
            'tag_line'         => 'Không giới hạn',
            'badge_color'      => 'badge-secondary',
            'sort_order'       => 4,
        ],
    ];

    public function run(): void
    {
        foreach ($this->plans as $data) {
            $augmented = array_splice($data, 0);
            $extra = [
                'tier'         => $augmented['tier'],
                'is_public'    => $augmented['is_public'],
                'annual_price' => $augmented['annual_price'],
                'badge_color'  => $augmented['badge_color'],
                'tag_line'     => $augmented['tag_line'],
            ];
            unset($augmented['tier'], $augmented['is_public'], $augmented['annual_price'],
                  $augmented['badge_color'], $augmented['tag_line']);

            $plan = Plan::updateOrCreate(
                ['slug' => $augmented['slug']],
                $augmented,
            );

            $plan->forceFill($extra)->save();
        }
    }
}
