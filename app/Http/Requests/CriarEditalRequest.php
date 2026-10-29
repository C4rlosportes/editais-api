<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CriarEditalRequest extends FormRequest
{
    /**
     * Autoriza a requisição.
     * A autorização por perfil será implementada na etapa de segurança.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação dos dados do edital.
     */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('editais', 'codigo'),
            ],

            'titulo' => [
                'required',
                'string',
                'max:255',
            ],

            'descricao' => [
                'nullable',
                'string',
            ],

            'max_opcoes' => [
                'required',
                'integer',
                'min:1',
            ],

            'situacao' => [
                'prohibited',
            ],

            'requisitos' => [
                'required',
                'array',
                'min:1',
            ],

            'requisitos.*' => [
                'required',
                'array:descricao',
            ],

            'requisitos.*.descricao' => [
                'required',
                'string',
            ],

            'destinos' => [
                'required',
                'array',
                'min:1',
            ],

            'destinos.*' => [
                'required',
                'array:opm_id,vagas',
            ],

            'destinos.*.opm_id' => [
                'required',
                'integer',
                'exists:opms,id',
            ],

            'destinos.*.vagas' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Validações adicionais entre os campos.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $validator->errors()->has('max_opcoes')
                    || $validator->errors()->has('destinos')
                ) {
                    return;
                }

                $destinos = $this->input('destinos');
                $maxOpcoes = $this->input('max_opcoes');

                if (! is_array($destinos) || ! is_numeric($maxOpcoes)) {
                    return;
                }

                if ((int) $maxOpcoes > count($destinos)) {
                    $validator->errors()->add(
                        'max_opcoes',
                        'A quantidade de opções permitidas não pode ultrapassar a quantidade de destinos cadastrados no edital.'
                    );
                }
            },
        ];
    }
}
