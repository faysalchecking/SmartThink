<?php

namespace App\Modules\Documents\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Support\FileSignature;
use App\Modules\Recruitment\Models\Candidate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class DocumentService
{
    public const DISK = 'local';

    private const MAX_NAME_LENGTH = 120;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Stores the file under a random name, then records it. If the database write fails,
     * the stored file is removed, so no orphan file is left behind.
     */
    public function store(UploadedFile $file, Candidate $candidate, string $type, int $actorId, string $companyId): Document
    {
        $path = $file->getRealPath();
        $mime = $path === false ? null : FileSignature::detect($path);

        if ($path === false || $mime === null) {
            throw ValidationException::withMessages([
                'file' => 'The file content is not a supported PDF, JPEG, or PNG.',
            ]);
        }

        $hash = hash_file('sha256', $path);

        if ($hash === false) {
            throw new RuntimeException('Could not hash the uploaded file.');
        }

        $key = (string) Str::uuid();
        $stored = Storage::disk(self::DISK)->putFileAs(self::directory($companyId), $file, $key);

        if ($stored === false) {
            throw new RuntimeException('Could not store the uploaded file.');
        }

        try {
            return DB::transaction(function () use ($file, $candidate, $type, $actorId, $companyId, $key, $mime, $hash): Document {
                $document = new Document;

                // forceFill is used here on purpose: these fields come from the server, never from the request.
                // company_id is set by the BelongsToCompany trait when the row is created.
                $document->forceFill([
                    'candidate_id' => $candidate->id,
                    'document_type' => $type,
                    'storage_key' => $key,
                    'original_name' => self::safeName((string) $file->getClientOriginalName()),
                    'mime_type' => $mime,
                    'size_bytes' => (int) $file->getSize(),
                    'sha256' => $hash,
                    'uploaded_by' => $actorId,
                    'review_status' => 'pending',
                ]);
                $document->save();

                $this->audit->record(
                    'document.uploaded',
                    'success',
                    'document',
                    $document->id,
                    $actorId,
                    $companyId,
                    ['document_type' => $type, 'size_bytes' => $document->size_bytes],
                );

                return $document;
            });
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete(self::directory($companyId).'/'.$key);

            throw $e;
        }
    }

    public function review(Document $document, string $status, ?string $reason, int $actorId, string $companyId): Document
    {
        return DB::transaction(function () use ($document, $status, $reason, $actorId, $companyId): Document {
            $document->review_status = $status;
            $document->review_reason = $status === 'rejected' ? $reason : null;
            $document->reviewed_by = $actorId;
            $document->reviewed_at = now();
            $document->save();

            $this->audit->record(
                'document.reviewed',
                'success',
                'document',
                $document->id,
                $actorId,
                $companyId,
                ['status' => $status],
            );

            return $document;
        });
    }

    /**
     * Soft archive. The file stays on disk, so it can be restored or audited.
     */
    public function archive(Document $document, int $actorId, string $companyId): Document
    {
        if ($document->archived_at !== null) {
            return $document;
        }

        return DB::transaction(function () use ($document, $actorId, $companyId): Document {
            $document->archived_at = now();
            $document->save();

            $this->audit->record('document.archived', 'success', 'document', $document->id, $actorId, $companyId);

            return $document;
        });
    }

    public static function directory(string $companyId): string
    {
        return 'documents/'.$companyId;
    }

    public static function pathFor(Document $document): string
    {
        return self::directory($document->company_id).'/'.$document->storage_key;
    }

    /**
     * Keeps only letters, digits, dot, dash, underscore and space. Path parts are removed first.
     */
    public static function safeName(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $clean = preg_replace('/[^A-Za-z0-9._ -]/', '_', $base) ?? '';
        $clean = trim($clean, " .\t\n\r");

        if ($clean === '') {
            $clean = 'document';
        }

        return substr($clean, 0, self::MAX_NAME_LENGTH);
    }
}
