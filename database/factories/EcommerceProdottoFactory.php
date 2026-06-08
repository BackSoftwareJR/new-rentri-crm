<?php

namespace Database\Factories;

use App\Models\EcommerceProdotto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EcommerceProdotto>
 */
class EcommerceProdottoFactory extends Factory
{
    protected $model = EcommerceProdotto::class;

    public function definition(): array
    {
        return [
            'codice'      => strtoupper(fake()->unique()->bothify('RIC-####')),
            'nome'        => fake()->words(3, true),
            'descrizione' => fake()->sentence(),
            'categoria'   => fake()->randomElement(['motore', 'carrozzeria', 'elettronica', 'interni']),
            'prezzo'      => fake()->randomFloat(2, 10, 500),
            'giacenza'    => fake()->numberBetween(0, 20),
            'attivo'      => true,
        ];
    }
}
