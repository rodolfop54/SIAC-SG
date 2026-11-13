<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class SlotFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(2)->startOfHour()->addMinutes(fake()->unique()->numberBetween(1, 10000) * 30);

        return ['service_id' => Service::factory(), 'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes(30), 'enabled' => true];
    }
}
