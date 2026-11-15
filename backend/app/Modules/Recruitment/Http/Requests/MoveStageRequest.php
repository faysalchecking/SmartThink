<?php

namespace App\Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MoveStageRequest extends FormRequest
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
            'stage_id' => ['required', 'string', 'uuid'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
