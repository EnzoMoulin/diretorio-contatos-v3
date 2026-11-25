<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BuscaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'limite' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
