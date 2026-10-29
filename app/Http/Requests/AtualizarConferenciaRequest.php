<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarConferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Temporário, enquanto a autenticação ainda não foi implementada.
        return true;
    }

    public function rules(): array
    {
        return [
            'resultado' => [
                'required',
                'in:PENDENTE,ATENDE,NAO_ATENDE',
            ],

            'observacao' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:resultado,NAO_ATENDE',
            ],
        ];
    }
}
