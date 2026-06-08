<?php

namespace Database\Factories;

use App\Models\Anagrafica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anagrafica>
 */
class AnagraficaFactory extends Factory
{
    protected $model = Anagrafica::class;

    public function definition(): array
    {
        return [
            'tipo' => fake()->randomElement(Anagrafica::TIPI),
            'ragione_sociale' => fake()->company(),
            'piva' => fake()->numerify('###########'),
            'codice_fiscale' => strtoupper(fake()->bothify('??????##?##?###?')),
            'email' => fake()->companyEmail(),
            'telefono' => fake()->phoneNumber(),
            'indirizzo' => fake()->streetAddress(),
            'citta' => fake()->city(),
            'cap' => fake()->postcode(),
            'provincia' => strtoupper(fake()->lexify('??')),
            'gestisce_trasporti' => false,
        ];
    }

    public function trasportatore(): static
    {
        return $this->state(fn () => [
            'tipo' => 'trasportatore',
            'gestisce_trasporti' => true,
        ]);
    }
}
