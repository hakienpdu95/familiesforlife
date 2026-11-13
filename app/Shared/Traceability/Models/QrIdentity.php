<?php

namespace App\Shared\Traceability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;
use Modules\Post\Models\PostArticle;

class QrIdentity extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const KIND_LABELS = [
        'OcopProduct' => 'Sản phẩm OCOP',
        'Product' => 'Sản phẩm',
        'PostArticle' => 'Bài viết',
        'HeritageSite' => 'Di tích',
        'OcopSubject' => 'Chủ thể OCOP',
    ];

    public const STATUS_REVOKED = 'revoked';

    public const CODE_LENGTH = 8;

    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const IMMUTABLE = ['qr_code', 'target_type', 'target_id'];

    protected $table = 'qr_identities';

    protected $fillable = ['qr_code', 'target_type', 'target_id', 'status', 'snapshot', 'snapshot_at', 'revoked_at'];

    protected $casts = [
        'snapshot' => 'array',
        'snapshot_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $identity): void {
            if ($identity->isDirty(self::IMMUTABLE)) {
                throw new LogicException(__('Mã QR truy xuất là bất biến — không được sửa mã hoặc đối tượng gốc.'));
            }
        });

        static::deleting(function (): void {
            throw new LogicException(__('Không được xoá mã QR truy xuất — chỉ được thu hồi (revoke).'));
        });
    }

    public static function issueFor(Model $target): self
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = self::generateCode();

            if (! self::query()->where('qr_code', $code)->exists()) {
                return self::query()->create([
                    'qr_code' => $code,
                    'target_type' => $target->getMorphClass(),
                    'target_id' => $target->getKey(),
                    'status' => self::STATUS_ACTIVE,
                ]);
            }
        }

        throw new LogicException(__('Không sinh được mã QR duy nhất sau 5 lần thử.'));
    }

    public static function generateCode(int $length = self::CODE_LENGTH): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }

    public function storeSnapshot(array $payload): void
    {
        $this->forceFill(['snapshot' => $payload, 'snapshot_at' => now()])->save();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function scans(): HasMany
    {
        return $this->hasMany(QrScan::class, 'qr_identity_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(QrReport::class, 'qr_identity_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function revoke(): void
    {
        if ($this->isActive()) {
            $this->forceFill(['status' => self::STATUS_REVOKED, 'revoked_at' => now()])->save();
        }
    }

    public function targetKind(): string
    {
        return class_basename((string) (Relation::getMorphedModel($this->target_type) ?? $this->target_type));
    }

    public function targetKindLabel(): string
    {
        $kind = $this->targetKind();

        return isset(self::KIND_LABELS[$kind]) ? __(self::KIND_LABELS[$kind]) : $kind;
    }

    public function targetTitle(): ?string
    {
        $target = $this->target;

        if ($target instanceof PostArticle) {
            return $target->translations->firstWhere('locale', $target->main_locale)?->title ?? $target->translations->first()?->title;
        }

        if (! $target) {
            return null;
        }

        return method_exists($target, 'originalAttribute') ? $target->originalAttribute('name') : $target->name;
    }

    public function targetTrashed(): bool
    {
        $target = $this->target;

        return $target && method_exists($target, 'trashed') && $target->trashed();
    }

    public static function targetEagerLoad(): array
    {
        return ['target' => fn (MorphTo $morph) => $morph->withTrashed()->morphWith([PostArticle::class => ['translations']])];
    }

    public function publicUrl(): string
    {
        return route('traceability.qr', $this->qr_code);
    }
}
