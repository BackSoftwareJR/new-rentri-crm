<?php

namespace App\Support\Logging;

use App\Models\ApplicationLog;
use App\Support\Demo\DemoContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Log\LogLevel;

class StructuredLogService
{
    public function __construct(
        private readonly LogSensitiveDataMasker $masker,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $module, string $action, string $message, array $context = []): void
    {
        $this->write(LogLevel::INFO, $module, $action, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $module, string $action, string $message, array $context = []): void
    {
        $this->write(LogLevel::WARNING, $module, $action, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(string $module, string $action, string $message, array $context = []): void
    {
        $this->write(LogLevel::ERROR, $module, $action, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function write(string $level, string $module, string $action, string $message, array $context = []): void
    {
        $module = strtolower($module);
        $this->assertModule($module);

        $normalized = $this->normalizeContext($context);
        $channel = (string) config("application_log.module_channels.{$module}", 'single');
        $traceId = RequestContext::traceId();

        $logContext = $this->masker->mask(array_merge(RequestContext::context(), [
            'module'      => $module,
            'action'      => $action,
            'entity_type' => $normalized['entity_type'],
            'entity_id'   => $normalized['entity_id'],
            'user_id'     => $normalized['user_id'],
            'demo_mode'   => $normalized['demo_mode'],
            'duration_ms' => $normalized['duration_ms'],
            'outcome'     => $normalized['outcome'],
            'extra'       => $normalized['extra'],
        ]));

        Log::channel($channel)->log($level, $message, $logContext);

        if (config('application_log.persist_to_database', true)) {
            ApplicationLog::query()->create([
                'trace_id'    => $traceId,
                'level'       => strtolower($level),
                'module'      => $module,
                'channel'     => $channel,
                'action'      => $action,
                'message'     => $message,
                'entity_type' => $normalized['entity_type'],
                'entity_id'   => $normalized['entity_id'] !== null ? (string) $normalized['entity_id'] : null,
                'user_id'     => $normalized['user_id'],
                'demo_mode'   => $normalized['demo_mode'],
                'outcome'     => $normalized['outcome'],
                'duration_ms' => $normalized['duration_ms'],
                'context'     => $normalized['extra'] !== [] ? $this->masker->mask($normalized['extra']) : null,
                'created_at'  => now(),
            ]);
        }
    }

    public function moduloLabel(string $module): string
    {
        return match ($module) {
            'rentri'      => 'RENTRI',
            'ecommerce'   => 'E-commerce',
            'gps'         => 'GPS trasporti',
            'stripe'      => 'Stripe',
            'security'    => 'Sicurezza',
            'business'    => 'Business / KPI',
            'operatore'   => 'Operatore',
            'integration' => 'Integrazioni',
            default       => ucfirst($module),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *   entity_type: ?string,
     *   entity_id: int|string|null,
     *   user_id: ?int,
     *   demo_mode: bool,
     *   duration_ms: ?int,
     *   outcome: ?string,
     *   extra: array<string, mixed>
     * }
     */
    private function normalizeContext(array $context): array
    {
        $extra = $context['context'] ?? $context['extra'] ?? [];
        unset($context['context'], $context['extra']);

        foreach (['entity_type', 'entity_id', 'user_id', 'demo_mode', 'duration_ms', 'outcome'] as $key) {
            if (array_key_exists($key, $context)) {
                continue;
            }
        }

        return [
            'entity_type' => isset($context['entity_type']) ? (string) $context['entity_type'] : null,
            'entity_id'   => $context['entity_id'] ?? null,
            'user_id'     => isset($context['user_id']) ? (int) $context['user_id'] : Auth::id(),
            'demo_mode'   => array_key_exists('demo_mode', $context)
                ? (bool) $context['demo_mode']
                : DemoContext::isActive(),
            'duration_ms' => isset($context['duration_ms']) ? max(0, (int) $context['duration_ms']) : null,
            'outcome'     => isset($context['outcome']) ? (string) $context['outcome'] : null,
            'extra'       => is_array($extra) ? $extra : [],
        ];
    }

    private function assertModule(string $module): void
    {
        $allowed = config('application_log.modules', []);

        if ($allowed !== [] && ! in_array($module, $allowed, true)) {
            throw new InvalidArgumentException("Modulo log non valido: {$module}");
        }
    }
}
