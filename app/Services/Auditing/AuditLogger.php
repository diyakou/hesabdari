<?php

namespace App\Services\Auditing;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogger
{
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'secret',
    ];

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function record(
        string $action,
        Model $auditable,
        array $before,
        array $after,
        ?User $actor = null,
    ): AuditLog {
        $correlationId = $this->correlationId();

        return AuditLog::query()->create([
            'actor_user_id' => $actor?->getKey(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'changes' => [
                'before' => $this->withoutSensitiveFields($before),
                'after' => $this->withoutSensitiveFields($after),
            ],
            'correlation_id' => $correlationId,
            'ip_address' => app()->bound('request') ? request()->ip() : null,
        ]);
    }

    private function correlationId(): string
    {
        if (app()->bound('request')) {
            $candidate = request()->header('X-Correlation-ID');

            if (is_string($candidate) && Str::isUuid($candidate)) {
                return $candidate;
            }
        }

        return (string) Str::uuid();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function withoutSensitiveFields(array $values): array
    {
        return collect($values)
            ->reject(fn (mixed $value, string $key): bool => in_array($key, self::SENSITIVE_FIELDS, true))
            ->all();
    }
}
