<?php

namespace App\Modules\Recruitment\Http\Requests;

use App\Modules\Recruitment\Models\PipelineStage;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PipelineStageRequest extends FormRequest
{
    /**
     * Stage configuration is an admin decision. Employees get 403 before any validation runs.
     */
    public function authorize(): bool
    {
        return ResolveCompany::membership($this)->role->isCompanyAdmin();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'min:2', 'max:80'],
            // The kind of an existing stage never changes, so an update cannot set it.
            'kind' => $isCreate ? ['required', Rule::in(PipelineStage::KINDS)] : ['prohibited'],
            // A new stage is always enabled. Enabling and disabling use the update route.
            'enabled' => $isCreate ? ['prohibited'] : ['sometimes', 'boolean'],
        ];
    }
}
