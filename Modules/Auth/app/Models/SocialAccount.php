<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'provider_name',
        'provider_avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'linked_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token'     => 'encrypted',
            'refresh_token'    => 'encrypted',
            'token_expires_at' => 'datetime',
            'linked_at'        => 'datetime',
            'last_used_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
