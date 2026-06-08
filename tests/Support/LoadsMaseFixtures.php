<?php

namespace Tests\Support;

trait LoadsMaseFixtures
{
    /**
     * @return array<string, mixed>
     */
    protected function maseFixture(string $name): array
    {
        $path = base_path("tests/fixtures/rentri/mase/{$name}.json");

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
