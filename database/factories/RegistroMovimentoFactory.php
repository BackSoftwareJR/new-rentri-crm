<?php

namespace Database\Factories;

use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Models\RegistroMovimento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistroMovimento>
 */
class RegistroMovimentoFactory extends Factory
{
    protected $model = RegistroMovimento::class;

    public function definition(): array
    {
        return [
            'tipo'            => fake()->randomElement([RegistroMovimentoTipo::Carico->value, RegistroMovimentoTipo::Scarico->value]),
            'codice_cer_id'   => CodiceCer::factory(),
            'peso_kg'         => fake()->randomFloat(2, 10, 5000),
            'data_movimento'  => fake()->dateTimeBetween('-1 year', 'now'),
            'note'            => null,
            'rentri_trasmesso'=> false,
        ];
    }

    public function carico(): static
    {
        return $this->state(fn () => ['tipo' => RegistroMovimentoTipo::Carico->value]);
    }

    public function scarico(): static
    {
        return $this->state(fn () => ['tipo' => RegistroMovimentoTipo::Scarico->value]);
    }
}
