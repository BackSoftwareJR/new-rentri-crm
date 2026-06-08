<?php

namespace Database\Factories;

use App\Models\CodiceCer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodiceCer>
 */
class CodiceCerFactory extends Factory
{
    protected $model = CodiceCer::class;

    public function definition(): array
    {
        return [
            'codice' => fake()->unique()->numerify('##.##.##'),
            'descrizione' => fake()->sentence(4),
            'categoria' => fake()->randomElement(['pericoloso', 'altro']),
            'um' => 'kg',
            'limite_kg' => null,
            'attivo' => true,
        ];
    }

    public function pericoloso(): static
    {
        return $this->state(fn () => ['categoria' => 'pericoloso']);
    }
}
