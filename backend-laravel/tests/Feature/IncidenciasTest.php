<?php

namespace Tests\Feature;

use App\Models\Incidencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IncidenciasTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test: Registro de nueva incidencia
     */
    public function test_registrar_nueva_incidencia()
    {
        $usuario = Usuario::factory()->create(['rol' => 'usuario']);
        $token = $usuario->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/incidencias', [
                'titulo' => 'Falla en servidor de pruebas',
                'descripcion' => 'El servidor no responde a pings',
                'prioridad' => 'Alta',
                'id_tipo' => 2,
                'id_zona' => 1,
                'fecha_ocurrencia' => '2026-06-30',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'ok' => true,
                'mensaje' => 'Incidencia registrada. Quedará visible una vez aprobada por un administrador.',
            ]);

        $this->assertDatabaseHas('incidencias', [
            'titulo' => 'Falla en servidor de pruebas',
            'prioridad' => 'Alta',
        ]);
    }

    /**
     * Test: Listar incidencias
     */
    public function test_listar_incidencias()
    {
        $usuario = Usuario::factory()->create(['rol' => 'usuario']);
        $token = $usuario->createToken('test-token')->plainTextToken;

        Incidencia::factory()->create([
            'estado_aprobacion' => 'aprobada',
            'id_usuario_creador' => $usuario->id_usuario,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/incidencias');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'datos',
                'total',
                'pagina',
                'por_pagina',
            ]);
    }

    /**
     * Test: Filtrar incidencias por estado
     */
    public function test_filtrar_incidencias_por_estado()
    {
        $usuario = Usuario::factory()->create(['rol' => 'usuario']);
        $token = $usuario->createToken('test-token')->plainTextToken;

        Incidencia::factory()->create([
            'estado_aprobacion' => 'aprobada',
            'id_estado_actual' => 1, // Pendiente
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/incidencias?estado=Pendiente');

        $response->assertStatus(200);
    }

    /**
     * Test: Validación de campos requeridos al crear incidencia
     */
    public function test_validacion_campos_requeridos_incidencia()
    {
        $usuario = Usuario::factory()->create(['rol' => 'usuario']);
        $token = $usuario->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/incidencias', [
                'titulo' => '', // Campo vacío
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'ok' => false,
            ]);
    }

    /**
     * Test: Actualizar incidencia existente
     */
    public function test_actualizar_incidencia()
    {
        $usuario = Usuario::factory()->create(['rol' => 'usuario']);
        $token = $usuario->createToken('test-token')->plainTextToken;

        $incidencia = Incidencia::factory()->create([
            'id_usuario_creador' => $usuario->id_usuario,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
                'titulo' => 'Título actualizado',
                'descripcion' => 'Descripción actualizada',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'titulo' => 'Título actualizado',
        ]);
    }

    /**
     * Test: Obtener detalle de una incidencia
     */
    public function test_obtener_detalle_incidencia()
    {
        $usuario = Usuario::factory()->create(['rol' => 'usuario']);
        $token = $usuario->createToken('test-token')->plainTextToken;

        $incidencia = Incidencia::factory()->create([
            'estado_aprobacion' => 'aprobada',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/incidencias/{$incidencia->id_incidencia}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id_incidencia',
                'titulo',
                'descripcion',
                'tipo',
                'estado',
                'zona',
            ]);
    }

    /**
     * Test: Eliminar incidencia (solo admin)
     */
    public function test_eliminar_incidencia_como_admin()
    {
        $admin = Usuario::factory()->create(['rol' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $incidencia = Incidencia::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/incidencias/{$incidencia->id_incidencia}");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertDatabaseMissing('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
        ]);
    }

    /**
     * Test: Usuario normal no puede eliminar incidencia
     */
    public function test_usuario_normal_no_puede_eliminar_incidencia()
    {
        $usuario = Usuario::factory()->create(['rol' => 'usuario']);
        $token = $usuario->createToken('test-token')->plainTextToken;

        $incidencia = Incidencia::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/incidencias/{$incidencia->id_incidencia}");

        $response->assertStatus(403);
    }
}
