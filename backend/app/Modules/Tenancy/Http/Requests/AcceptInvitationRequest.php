<?php

namespace App\Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint. The token is the credential, and it is rate limited.
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64', 'alpha_num'],
            'name' => ['nullable', 'string', 'max:150'],
            'password' => ['nullable', 'string', 'min:12', 'max:255'],
        ];
    }
}
