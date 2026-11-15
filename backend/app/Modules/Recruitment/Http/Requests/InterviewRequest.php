<?php

namespace App\Modules\Recruitment\Http\Requests;

use App\Modules\Recruitment\Models\Interview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Create sets the schedule. Update changes only the result, notes, or time.
     * Fields that do not belong to the operation are rejected, not silently ignored.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'application_id' => ['required', 'string', 'uuid'],
                'interviewer_id' => ['required', 'integer', 'min:1'],
                'scheduled_at' => ['required', 'date'],
                'result' => ['prohibited'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return [
            'application_id' => ['prohibited'],
            'interviewer_id' => ['prohibited'],
            'scheduled_at' => ['sometimes', 'date'],
            'result' => ['sometimes', 'string', Rule::in(Interview::RESULTS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
