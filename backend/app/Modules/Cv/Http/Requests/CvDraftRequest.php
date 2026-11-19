<?php

namespace App\Modules\Cv\Http\Requests;

use App\Modules\Cv\Models\CvDraft;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CvDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Create accepts only a template. Update accepts content, order and visibility.
     * Unknown keys (for example company_id) are ignored, because validated() returns only ruled keys.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $create = $this->isMethod('POST');

        return [
            'template_key' => ['sometimes', 'string', Rule::in(CvDraft::TEMPLATES)],

            'content' => $create ? ['prohibited'] : ['sometimes', 'array'],
            'content.personal' => ['sometimes', 'array'],
            'content.personal.full_name' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'content.personal.email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'content.personal.phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+()\- ]+$/'],
            'content.personal.nationality' => ['sometimes', 'nullable', 'string', 'max:80'],
            'content.personal.date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'content.summary' => ['sometimes', 'nullable', 'string', 'max:3000'],

            'content.experience' => ['sometimes', 'array', 'max:20'],
            'content.experience.*.title' => ['sometimes', 'required', 'string', 'max:150'],
            'content.experience.*.employer' => ['sometimes', 'nullable', 'string', 'max:150'],
            'content.experience.*.country' => ['sometimes', 'nullable', 'string', 'max:80'],
            'content.experience.*.start' => ['sometimes', 'nullable', 'string', 'max:20'],
            'content.experience.*.end' => ['sometimes', 'nullable', 'string', 'max:20'],
            'content.experience.*.description' => ['sometimes', 'nullable', 'string', 'max:2000'],

            'content.education' => ['sometimes', 'array', 'max:10'],
            'content.education.*.institution' => ['sometimes', 'required', 'string', 'max:150'],
            'content.education.*.qualification' => ['sometimes', 'nullable', 'string', 'max:150'],
            'content.education.*.year' => ['sometimes', 'nullable', 'string', 'max:10'],

            'content.skills' => ['sometimes', 'array', 'max:50'],
            'content.skills.*' => ['sometimes', 'required', 'string', 'max:80'],

            'content.languages' => ['sometimes', 'array', 'max:10'],
            'content.languages.*.name' => ['sometimes', 'required', 'string', 'max:60'],
            'content.languages.*.level' => ['sometimes', 'nullable', 'string', 'max:40'],

            'section_order' => $create ? ['prohibited'] : ['sometimes', 'array'],
            'section_order.*' => ['sometimes', 'string', Rule::in(CvDraft::SECTIONS)],

            'section_visibility' => $create ? ['prohibited'] : ['sometimes', 'array'],
            'section_visibility.*' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $content = $this->input('content');

            if (is_array($content)) {
                $unknown = array_diff(array_keys($content), CvDraft::SECTIONS);
                $absent = array_diff(CvDraft::SECTIONS, array_keys($content));

                if ($unknown !== [] || $absent !== []) {
                    $v->errors()->add('content', 'Content must contain exactly the six CV sections.');
                }

                $personal = $content['personal'] ?? null;
                if (! is_array($personal) || blank($personal['full_name'] ?? null)) {
                    $v->errors()->add('content.personal.full_name', 'Full name is required.');
                }
            }

            $order = $this->input('section_order');
            if (is_array($order)) {
                $valid = count($order) === count(CvDraft::SECTIONS)
                    && array_diff(CvDraft::SECTIONS, $order) === []
                    && count(array_unique($order)) === count($order);

                if (! $valid) {
                    $v->errors()->add('section_order', 'The order must list each section exactly once.');
                }
            }

            $visibility = $this->input('section_visibility');
            if (is_array($visibility) && array_diff(array_keys($visibility), CvDraft::OPTIONAL_SECTIONS) !== []) {
                $v->errors()->add('section_visibility', 'Only optional sections can be hidden.');
            }
        });
    }
}
