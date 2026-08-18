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
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (\App\Models\User $user) {
            if (!$user->employee_id) {
                $employee = \App\Models\Employee::create([
                    'fullname' => fake()->name(),
                    'gender' => 'Male',
                    'station' => 'CGK',
                    'status' => 'Tetap',
                    'join_date' => now()->toDateString(),
                ]);
                $user->employee_id = $employee->id;
            }
        });
    }
}
