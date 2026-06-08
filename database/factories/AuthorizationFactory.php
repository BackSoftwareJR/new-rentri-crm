<?php

namespace Database\Factories;

use App\Models\Anagrafica;
use App\Models\Authorization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Authorization>
 */
class AuthorizationFactory extends Factory
{
    protected $model = Authorization::class;

    public function definition(): array
    {
        $rilasciata = fake()->dateTimeBetween('-2 years', '-1 month');

        return [
            'anagrafica_id' => Anagrafica::factory(),
            'numero' => 'AUT-'.fake()->unique()->numerify('####'),
            'rilasciata_il' => $rilasciata,
            'scade_il' => fake()->dateTimeBetween($rilasciata, '+6 months'),
            'tipo' => 'trasporto_rifiuti',
        ];
    }

    public function valid(): static
    {
        return $this->state(fn () => [
            'scade_il' => now()->addYear(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'scade_il' => now()->subDay(),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'scade_il' => now()->addDays(10),
        ]);
    }
}
