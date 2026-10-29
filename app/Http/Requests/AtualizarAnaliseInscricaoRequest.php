<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarAnaliseInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Temporário, até implementarmos a autenticação da SME.
        return true;
    }

    public function rules(): array
    {
        return [
            'situacao' => [
                'required',
                'string',
                'in:EM_ANALISE,CONCLUIDA',
            ],
        ];
    }
}
