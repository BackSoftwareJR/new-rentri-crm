<?php

namespace Database\Factories;

use App\Enums\VfuStato;
use App\Models\VfuRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VfuRegistration>
 */
class VfuRegistrationFactory extends Factory
{
    protected $model = VfuRegistration::class;

    public function definition(): array
    {
        return [
            'tipo_veicolo' => 'Autovettura',
            'nazione' => 'Italia',
            'targa' => strtoupper(fake()->bothify('??###??')),
            'telaio' => strtoupper(fake()->bothify('?????????????????')),
            'codice_motore' => fake()->bothify('MOT-#####'),
            'marca' => fake()->randomElement(['FIAT', 'VOLKSWAGEN', 'FORD']),
            'modello' => fake()->word(),
            'nome' => fake()->firstName(),
            'cognome' => fake()->lastName(),
            'proprietario' => fake()->name(),
            'stato' => VfuStato::Bozza,
            'peso_kg' => fake()->randomFloat(2, 800, 1800),
            'data_consegna' => now()->toDateString(),
        ];
    }

    public function inAccettazione(): static
    {
        return $this->state(fn () => ['stato' => VfuStato::InAccettazione]);
    }

    public function attesaBonifica(): static
    {
        return $this->state(fn () => [
            'stato' => VfuStato::AttesaBonifica,
            'data_accettazione' => now()->toDateString(),
        ]);
    }

    public function accettatoPerBonifica(): static
    {
        return $this->state(fn () => [
            'stato' => VfuStato::Accettato,
            'data_accettazione' => now()->toDateString(),
        ]);
    }

    public function bonificato(): static
    {
        return $this->state(fn () => [
            'stato' => VfuStato::Bonificato,
            'data_accettazione' => now()->subDays(7)->toDateString(),
        ]);
    }

    public function rottamato(): static
    {
        return $this->state(fn () => [
            'stato' => VfuStato::Rottamato,
            'data_accettazione' => now()->subDays(14)->toDateString(),
        ]);
    }
}
