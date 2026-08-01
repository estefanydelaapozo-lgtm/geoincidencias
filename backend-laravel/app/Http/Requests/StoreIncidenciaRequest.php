<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidenciaRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'titulo' => ['required','string','min:5','max:200'],
            'descripcion' => ['required','string','min:3','max:5000'],
            'prioridad' => ['required', Rule::in(['Baja','Media','Alta'])],
            'id_tipo' => ['required','integer','exists:tipos_incidencia,id_tipo'],
            'id_subtipo' => ['nullable','integer','exists:subtipos_incidencia,id_subtipo'],
            'id_zona' => ['required','integer','exists:zonas,id_zona'],
            'latitud' => ['required','numeric','between:-90,90'],
            'longitud' => ['required','numeric','between:-180,180'],
            'direccion_texto' => ['nullable','string','max:255'],
            'fecha_ocurrencia' => ['required','date','before_or_equal:today'],
            'hora_ocurrencia' => ['nullable','date_format:H:i'],
            'reportante_nombre' => ['nullable','string','max:100'],
            'reportante_contacto' => ['nullable','string','max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.min' => 'El título debe tener al menos 5 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.min' => 'La descripción debe tener al menos 3 caracteres.',
            'prioridad.required' => 'Selecciona la prioridad.',
            'id_tipo.required' => 'Selecciona el tipo de incidencia.',
            'id_tipo.exists' => 'El tipo seleccionado no existe.',
            'id_subtipo.exists' => 'El subtipo seleccionado no existe.',
            'id_zona.required' => 'Selecciona la zona.',
            'id_zona.exists' => 'La zona seleccionada no existe.',
            'fecha_ocurrencia.before_or_equal' => 'La fecha de ocurrencia no puede ser futura.',
            'hora_ocurrencia.date_format' => 'La hora debe tener formato de 24 horas (HH:mm).',
        ];
    }
}
