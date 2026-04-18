<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecomendacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sintomas'              => ['required', 'string', 'min:10', 'max:2000'],
            'enfermedades_previas'  => ['nullable', 'string', 'max:1000'],
            'descripcion_adicional' => ['nullable', 'string', 'max:1000'],
            'edad'                  => ['nullable', 'integer', 'min:0', 'max:120'],
            'sintomas_json'         => ['nullable', 'array'],
            'sintomas_json.*'       => ['string', 'max:100'],
        ];
    }
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
