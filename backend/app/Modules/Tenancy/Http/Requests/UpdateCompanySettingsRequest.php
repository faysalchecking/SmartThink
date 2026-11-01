<?php

namespace App\Modules\Tenancy\Http\Requests;

use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ResolveCompany::membership($this)->role->isCompanyAdmin();
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
        ];
    }
}
