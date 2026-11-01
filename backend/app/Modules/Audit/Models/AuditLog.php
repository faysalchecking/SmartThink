<?php

namespace App\Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property Carbon $occurred_at
 * @property int|null $actor_user_id
 * @property string|null $company_id
 * @property string $action
 * @property string $result
 * @property string $target_type
 * @property string|null $target_id
 * @property string|null $ip
 * @property string|null $request_id
 * @property array<string, mixed> $metadata
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'result',
        'actor_user_id',
        'company_id',
        'target_type',
        'target_id',
        'ip',
        'request_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit log entries are append-only.'));
    }
}
