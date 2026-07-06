<?php

namespace Modules\Subscription\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Subscription\Enums\InvoiceStatus;
use Modules\Subscription\Enums\InvoiceType;

class SubscriptionInvoice extends TenantAwareModel
{
    protected $table = 'subscription_invoices';

    protected $fillable = [
        'organization_id',
        'subscription_id',
        'plan_id',
        'new_plan_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'invoice_type',
        'billing_period_start',
        'billing_period_end',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_ref',
        'notes',
        'idempotent_key',
        'gateway',
    ];

    protected function casts(): array
    {
        return [
            'status'               => InvoiceStatus::class,
            'invoice_type'        => InvoiceType::class,
            'amount'               => 'decimal:2',
            'due_date'             => 'date',
            'paid_at'              => 'datetime',
            'billing_period_start' => 'date',
            'billing_period_end'   => 'date',
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Pending && $this->due_date?->isPast();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(config('laravel-subscriptions.models.subscription'));
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(config('laravel-subscriptions.models.plan'));
    }

    /** The plan being switched to — only set for InvoiceType::Upgrade */
    public function newPlan(): BelongsTo
    {
        return $this->belongsTo(config('laravel-subscriptions.models.plan'), 'new_plan_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'invoice_id');
    }
}
