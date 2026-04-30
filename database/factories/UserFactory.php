<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Phase 1: User Factory
 * Generates test user data for testing
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('TestPassword123!'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'role' => 'borrower',
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function admin(): self
    {
        return $this->state(fn(array $attributes) => ['role' => 'admin']);
    }

    public function loanOfficer(): self
    {
        return $this->state(fn(array $attributes) => ['role' => 'loan_officer']);
    }

    public function collector(): self
    {
        return $this->state(fn(array $attributes) => ['role' => 'collector']);
    }

    public function unverified(): self
    {
        return $this->state(fn(array $attributes) => ['email_verified_at' => null]);
    }
}
