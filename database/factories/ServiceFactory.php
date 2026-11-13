<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Servicio sintético '.fake()->unique()->numerify('######'), 'description' => 'Servicio ficticio de laboratorio', 'duration_minutes' => 30, 'enabled' => true];
    }
}
