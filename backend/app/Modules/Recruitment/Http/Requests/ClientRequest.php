<?php

namespace App\Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any active company member may manage clients. The company gate is already applied by ResolveCompany.
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'min:2', 'max:150'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
