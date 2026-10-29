<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarSituacaoEditalRequest extends FormRequest
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
                'in:RASCUNHO,ABERTO,ENCERRADO,EM_ANALISE',
            ],
        ];
    }
}
