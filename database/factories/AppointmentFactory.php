<?php

namespace Database\Factories;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'slot_id' => Slot::factory(), 'code' => (string) Str::uuid(), 'request_key' => (string) Str::uuid(), 'status' => 'CONFIRMADA', 'channel' => 'web'];
    }
}
