<?php

namespace App\Modules\Tenancy\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class PlatformCreateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->is_platform_admin;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:companies,slug',
            ],
        ];
    }
}
