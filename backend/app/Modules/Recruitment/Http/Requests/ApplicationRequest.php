<?php

namespace App\Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApplicationRequest extends FormRequest
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
        return [
            'candidate_id' => ['required', 'string', 'uuid'],
            'job_order_id' => ['required', 'string', 'uuid'],
        ];
    }
}
