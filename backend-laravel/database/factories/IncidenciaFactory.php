<?php

namespace Database\Factories;

use App\Models\Incidencia;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidenciaFactory extends Factory
{
    protected $model = Incidencia::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(),
            'descripcion' => fake()->paragraph(),
            'prioridad' => fake()->randomElement(['Baja', 'Media', 'Alta']),
            'id_tipo' => fake()->numberBetween(1, 7),
            'id_subtipo' => fake()->numberBetween(1, 22),
            'id_estado_actual' => fake()->numberBetween(1, 4),
            'estado_aprobacion' => fake()->randomElement(['pendiente_revision', 'aprobada', 'rechazada']),
            'id_zona' => fake()->numberBetween(1, 8),
            'latitud' => fake()->latitude(-2.5, -2.0),
            'longitud' => fake()->longitude(-80.0, -79.0),
            'fecha_ocurrencia' => fake()->date(),
            'hora_ocurrencia' => fake()->time(),
            'reportante_nombre' => fake()->name(),
            'reportante_contacto' => fake()->phoneNumber(),
            'id_usuario_creador' => Usuario::factory(),
            'fecha_registro' => now(),
            'fecha_actualizacion' => now(),
        ];
    }
}
