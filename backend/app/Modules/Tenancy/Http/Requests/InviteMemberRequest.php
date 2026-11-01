<?php

namespace App\Modules\Tenancy\Http\Requests;

use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ResolveCompany::membership($this)->role->isCompanyAdmin();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', 'string', Rule::in(['company_admin', 'employee'])],
        ];
    }
}
