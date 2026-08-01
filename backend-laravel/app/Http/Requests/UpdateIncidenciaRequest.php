<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateIncidenciaRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        return [
            'titulo'=>['sometimes','required','string','min:5','max:200'],
            'descripcion'=>['sometimes','required','string','min:10','max:5000'],
            'prioridad'=>['sometimes','required',Rule::in(['Baja','Media','Alta'])],
            'id_tipo'=>['sometimes','required','integer','exists:tipos_incidencia,id_tipo'],
            'id_subtipo'=>['nullable','integer','exists:subtipos_incidencia,id_subtipo'],
            'id_zona'=>['sometimes','required','integer','exists:zonas,id_zona'],
            'latitud'=>['sometimes','required','numeric','between:-90,90'],
            'longitud'=>['sometimes','required','numeric','between:-180,180'],
            'direccion_texto'=>['sometimes','nullable','string','max:255'],
            'fecha_ocurrencia'=>['sometimes','required','date','before_or_equal:today'],
        ];
    }
}
