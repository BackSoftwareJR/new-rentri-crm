<?php

namespace App\Support\Sito;

use App\Models\Sito;

class SitoContext
{
    public const SESSION_KEY = 'active_sito_id';

    private static ?int $consoleOverride = null;

    public static function withSitoId(?int $sitoId, callable $callback): mixed
    {
        $previous = self::$consoleOverride;
        self::$consoleOverride = $sitoId;

        try {
            return $callback();
        } finally {
            self::$consoleOverride = $previous;
        }
    }

    public static function activeSitoId(): ?int
    {
        if (self::$consoleOverride !== null) {
            if (self::$consoleOverride === 0) {
                return null;
            }

            if (Sito::query()->whereKey(self::$consoleOverride)->where('is_active', true)->exists()) {
                return self::$consoleOverride;
            }
        }

        $stored = session(self::SESSION_KEY);

        if (is_numeric($stored)) {
            $id = (int) $stored;

            if (Sito::query()->whereKey($id)->where('is_active', true)->exists()) {
                return $id;
            }
        }

        $default = Sito::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if ($default === null) {
            return null;
        }

        session([self::SESSION_KEY => $default->id]);

        return $default->id;
    }

    public static function setActiveSitoId(int $sitoId): void
    {
        session([self::SESSION_KEY => $sitoId]);
    }

    public static function activeSito(): ?Sito
    {
        $id = self::activeSitoId();

        return $id !== null ? Sito::query()->find($id) : null;
    }

    public static function active(): ?Sito
    {
        return self::activeSito();
    }
}
