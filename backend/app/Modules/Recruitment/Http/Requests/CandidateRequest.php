<?php

namespace App\Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'full_name' => [$required, 'string', 'min:2', 'max:150'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\- ]+$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1940-01-01'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'passport_number' => ['nullable', 'string', 'max:30', 'alpha_num'],
            'summary' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
