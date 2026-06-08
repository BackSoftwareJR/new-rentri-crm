<?php

namespace App\Domain\Notifications;

use App\Enums\NotificationEvent;
use Illuminate\Support\Facades\File;

class NotificationPreferenceService
{
    /** @var array<string, bool>|null */
    private ?array $cache = null;

    /** @return array<string, bool> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $defaults = $this->defaultToggles();
        $saved = $this->readSaved();

        $this->cache = array_merge($defaults, $saved);

        return $this->cache;
    }

    public function isEnabled(NotificationEvent $event): bool
    {
        return (bool) ($this->all()[$event->value] ?? true);
    }

    public function setEnabled(NotificationEvent $event, bool $enabled): void
    {
        $toggles = $this->all();
        $toggles[$event->value] = $enabled;
        $this->persist($toggles);
    }

    /** @param  array<string, bool>  $toggles */
    public function save(array $toggles): void
    {
        $merged = array_merge($this->defaultToggles(), $toggles);
        $this->persist($merged);
    }

    public function reset(): void
    {
        $path = config('notifications.preferences_path');
        if (is_string($path) && File::exists($path)) {
            File::delete($path);
        }
        $this->cache = null;
    }

    /** @return array<string, bool> */
    private function defaultToggles(): array
    {
        $events = config('notifications.events', []);
        $toggles = [];

        foreach (NotificationEvent::all() as $event) {
            $toggles[$event->value] = (bool) ($events[$event->value]['enabled'] ?? true);
        }

        return $toggles;
    }

    /** @return array<string, bool> */
    private function readSaved(): array
    {
        $path = config('notifications.preferences_path');
        if (! is_string($path) || ! File::exists($path)) {
            return [];
        }

        $decoded = json_decode(File::get($path), true);
        if (! is_array($decoded)) {
            return [];
        }

        $toggles = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $toggles[$key] = (bool) $value;
            }
        }

        return $toggles;
    }

    /** @param  array<string, bool>  $toggles */
    private function persist(array $toggles): void
    {
        $path = config('notifications.preferences_path');
        if (! is_string($path)) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($toggles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->cache = $toggles;
    }
}
