<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UsuarioFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'rol' => fake()->randomElement(['admin', 'usuario']),
            'telefono' => fake()->phoneNumber(),
            'saldo_incentivos' => fake()->randomFloat(2, 0, 100),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
