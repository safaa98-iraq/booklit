<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** Defaults to a student. */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '07'.fake()->unique()->numerify('#########'),
            'email' => null,
            'role' => UserRole::Student,
            'is_active' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Admin,
            'phone' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
        ]);
    }

    public function editor(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Editor,
            'phone' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
