<?php

namespace App\Modules\Documents\Http\Requests;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Support\FileSignature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Size and extension are checked here. The real content type is checked again in the service.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.(int) (FileSignature::MAX_BYTES / 1024), 'mimes:pdf,jpg,jpeg,png'],
            'document_type' => ['required', 'string', Rule::in(Document::TYPES)],
        ];
    }
}
