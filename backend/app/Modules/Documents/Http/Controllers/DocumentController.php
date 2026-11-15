<?php

namespace App\Modules\Documents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Documents\Http\Requests\ReviewDocumentRequest;
use App\Modules\Documents\Http\Requests\UploadDocumentRequest;
use App\Modules\Documents\Http\Resources\DocumentResource;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentService;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $candidate = $this->candidate($request);
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $query = Document::query()
            ->where('candidate_id', $candidate->id)
            ->orderBy('created_at')
            ->orderBy('id');

        if (! $request->boolean('include_archived')) {
            $query->whereNull('archived_at');
        }

        return DocumentResource::collection($query->paginate($perPage))->response();
    }

    public function store(UploadDocumentRequest $request): JsonResponse
    {
        $candidate = $this->candidate($request);
        $companyId = ResolveCompany::membership($request)->company_id;
        $data = $request->validated();

        $document = $this->documents->store(
            $request->file('file'),
            $candidate,
            (string) $data['document_type'],
            $this->actorId($request),
            $companyId,
        );

        return DocumentResource::make($document)->response()->setStatusCode(201);
    }

    public function download(Request $request): Response
    {
        $document = $this->document($request);
        $companyId = ResolveCompany::membership($request)->company_id;
        $path = DocumentService::pathFor($document);

        if (! Storage::disk(DocumentService::DISK)->exists($path)) {
            // The record exists but the file is missing. Log it for the operator, return nothing to the client.
            Log::error('document file missing', ['document_id' => $document->id]);
            abort(404);
        }

        $this->audit->record(
            'document.downloaded',
            'success',
            'document',
            $document->id,
            $this->actorId($request),
            $companyId,
        );

        return Storage::disk(DocumentService::DISK)->download($path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function review(ReviewDocumentRequest $request): JsonResponse
    {
        $document = $this->document($request);
        $companyId = ResolveCompany::membership($request)->company_id;
        $data = $request->validated();

        $reviewed = $this->documents->review(
            $document,
            (string) $data['status'],
            isset($data['reason']) ? (string) $data['reason'] : null,
            $this->actorId($request),
            $companyId,
        );

        return DocumentResource::make($reviewed)->response();
    }

    public function archive(Request $request): JsonResponse
    {
        $document = $this->document($request);
        $companyId = ResolveCompany::membership($request)->company_id;

        $archived = $this->documents->archive($document, $this->actorId($request), $companyId);

        return DocumentResource::make($archived)->response();
    }

    private function candidate(Request $request): Candidate
    {
        return Candidate::query()->findOrFail($this->routeUuid($request, 'candidate'));
    }

    private function document(Request $request): Document
    {
        return Document::query()->findOrFail($this->routeUuid($request, 'document'));
    }
}
