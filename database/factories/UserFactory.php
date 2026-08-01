<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'id' => 'USR' . Str::upper(Str::random(8)),
            'fullname' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'gender' => 'Male',
            'job_title' => 'Staff',
            'station' => 'CGK',
            'status' => 'Tetap',
            'join_date' => now()->toDateString(),
            'is_active' => true,
        ];
    }
}
