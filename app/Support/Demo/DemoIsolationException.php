<?php

namespace App\Support\Demo;

use RuntimeException;

class DemoIsolationException extends RuntimeException
{
    public static function crossModeWrite(bool $recordIsDemo): self
    {
        $mode = DemoContext::isActive() ? 'demo' : 'produzione';

        return new self(sprintf(
            'Isolamento demo: impossibile scrivere record is_demo=%s in modalità %s.',
            $recordIsDemo ? 'true' : 'false',
            $mode,
        ));
    }

    public static function crossReference(string $entity, string $related): self
    {
        return new self(sprintf(
            'Isolamento demo: %s e %s devono appartenere allo stesso ambiente (demo o produzione).',
            $entity,
            $related,
        ));
    }
}
