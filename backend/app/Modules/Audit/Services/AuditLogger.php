<?php

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditLogger
{
    private const REDACTED = '[redacted]';

    /** @var list<string> */
    private const SENSITIVE_KEY_PARTS = ['password', 'token', 'secret', 'cookie', 'authorization', 'session', 'api_key'];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        string $result,
        string $targetType,
        ?string $targetId = null,
        ?int $actorId = null,
        ?string $companyId = null,
        array $metadata = [],
    ): void {
        $request = request();

        AuditLog::query()->create([
            'action' => $action,
            'result' => $result,
            'actor_user_id' => $actorId,
            'company_id' => $companyId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip' => $request->ip(),
            'request_id' => $this->requestId($request),
            'metadata' => $this->redact($metadata),
        ]);
    }

    private function requestId(Request $request): string
    {
        $header = $request->header('X-Request-Id');

        // A client may send a correlation ID, but only a well-formed one is kept.
        if (is_string($header) && preg_match('/^[A-Za-z0-9-]{8,64}$/', $header) === 1) {
            return $header;
        }

        return (string) Str::uuid();
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redact(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if ($this->isSensitive($lowerKey)) {
                $clean[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $clean[$key] = $this->redact($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function isSensitive(string $lowerKey): bool
    {
        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($lowerKey, $part)) {
                return true;
            }
        }

        return false;
    }
}
