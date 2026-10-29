<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CriarInscricaoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
        'aceite_condicoes' => [
            'required',
            'boolean',
            'accepted',
        ],

        'destinos' => [
            'required',
            'array',
            'min:1',
        ],

        'destinos.*' => [
            'required',
            'integer',
            'distinct',
        ],
    ];
    }
}
