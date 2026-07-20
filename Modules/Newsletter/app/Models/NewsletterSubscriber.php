<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Newsletter\Enums\SubscriberStatus;

/**
 * spec/Newsletter_Technical_Specification.md §6 — platform-wide, không organization_id.
 * DB nội bộ là bản ghi "ai đăng ký qua site của mình"; Resend Contacts là nguồn sự thật cho
 * "ai đang thật sự nhận được email" (§0 mục 3).
 */
class NewsletterSubscriber extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'uuid', 'full_name', 'email', 'resend_contact_id', 'status',
        'source', 'subscribed_at', 'confirmed_at', 'unsubscribed_at',
    ];

    protected $casts = [
        'status'          => SubscriberStatus::class,
        'subscribed_at'   => 'datetime',
        'confirmed_at'    => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', SubscriberStatus::Active);
    }
}
