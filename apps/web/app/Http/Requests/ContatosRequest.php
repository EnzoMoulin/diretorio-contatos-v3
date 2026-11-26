<?php

namespace App\Http\Requests;

use App\Actions\ListarContatos;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class ContatosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `tipo` é validado pela forma normalizada, não pelo rótulo: `E-MAIL` e
     * `email` são o mesmo canal do banco, e recusar a variação de caixa criaria
     * 422 que o cliente não entende.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'tipo' => [
                'nullable',
                'string',
                'max:12',
                function (string $atributo, mixed $valor, Closure $falha): void {
                    if (is_string($valor) && $valor !== '' && ListarContatos::rotulo($valor) === null) {
                        $falha('Tipo de contato inválido: use Telefone, WhatsApp, Ramal, E-mail ou Fax.');
                    }
                },
            ],
            'comarca' => ['nullable', 'string', 'max:8'],
            'local' => ['nullable', 'string', 'max:8'],
            'no' => ['nullable', 'string', 'max:8'],
            'q' => ['nullable', 'string', 'max:80'],
            'pagina' => ['nullable', 'integer', 'min:1'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:'.ListarContatos::POR_PAGINA_MAXIMO],
        ];
    }
}
