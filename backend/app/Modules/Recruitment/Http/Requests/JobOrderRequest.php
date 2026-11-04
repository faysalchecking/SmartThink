<?php

namespace App\Modules\Recruitment\Http\Requests;

use App\Modules\Recruitment\Models\JobOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class JobOrderRequest extends FormRequest
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
            'client_id' => [$required, 'string', 'uuid'],
            'title' => [$required, 'string', 'min:2', 'max:150'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'openings' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'status' => ['sometimes', 'string', Rule::in(JobOrder::STATUSES)],
        ];
    }
}
