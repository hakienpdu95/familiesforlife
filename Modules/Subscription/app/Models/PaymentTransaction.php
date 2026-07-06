<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Subscription\Enums\TransactionStatus;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'gateway',
        'direction',
        'status',
        'gateway_ref',
        'amount',
        'raw_payload',
        'ip_addr',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'invoice_id');
    }
}
