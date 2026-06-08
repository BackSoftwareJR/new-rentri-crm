<?php

namespace App\Support\Logging;

use Illuminate\Support\Str;

/**
 * Correlazione richieste HTTP / job queue via trace_id.
 */
class RequestContext
{
    private static ?string $traceId = null;

    public static function traceId(): string
    {
        return self::$traceId ??= (string) Str::uuid();
    }

    public static function setTraceId(string $traceId): void
    {
        self::$traceId = $traceId;
    }

    public static function reset(): void
    {
        self::$traceId = null;
    }

    /**
     * @return array{trace_id: string}
     */
    public static function context(): array
    {
        return ['trace_id' => self::traceId()];
    }
}
