<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

/**
 * Safe .env writer — updates specific keys without destroying file formatting.
 *
 * Reads the current .env, replaces existing KEY=value lines or appends new ones.
 * Never deletes keys, never touches keys it wasn't asked to change.
 */
class EnvWriter
{
    public function __construct(
        private readonly string $envPath,
    ) {}

    /** @param array<string, string|null> $values  key => value map */
    public function write(array $values): void
    {
        $content = file_exists($this->envPath)
            ? file_get_contents($this->envPath)
            : '';

        foreach ($values as $key => $value) {
            $content = $this->upsertLine($content, $key, $value);
        }

        file_put_contents($this->envPath, $content);

        // Clear cached config so new env values take effect in this request.
        try {
            Artisan::call('config:clear');
        } catch (\Throwable) {
            // Silently ignore — bootstrap error during tests or CLI-less env.
        }
    }

    public function read(string $key): ?string
    {
        $content = file_exists($this->envPath) ? file_get_contents($this->envPath) : '';

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$k, $v] = explode('=', $line, 2);

            if (trim($k) === $key) {
                return trim($v, " \t\n\r\0\x0B\"'");
            }
        }

        return null;
    }

    private function upsertLine(string $content, string $key, ?string $value): string
    {
        $escaped = $this->escapeValue($value ?? '');
        $newLine = "{$key}={$escaped}";
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $newLine, $content);
        }

        // Append at end of file with a trailing newline.
        return rtrim($content)."\n".$newLine."\n";
    }

    private function escapeValue(string $value): string
    {
        // Wrap in double quotes if value contains spaces, special chars, or is empty.
        if ($value === '' || preg_match('/[\s#"\'\\\\]/', $value)) {
            $value = '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
