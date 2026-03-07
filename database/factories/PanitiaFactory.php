<?php

namespace Database\Factories;

use App\Models\Institusi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class PanitiaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'whatsapp' => fake()->phoneNumber(),
            'gender' => fake()->boolean(),
            'committee' => fake()->word(),
            'institusi_id' => Institusi::inRandomOrder()->first()->id,
            'document' => fake()->filePath() . '.' . fake()->fileExtension(),
        ];
    }
}
