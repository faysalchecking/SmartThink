<?php

namespace App\Modules\Cv\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Cv\Models\CvDraft;
use App\Modules\Cv\Models\CvExport;
use App\Modules\Cv\Rendering\CvPdfRenderer;
use App\Modules\Cv\Support\CvContent;
use App\Modules\Cv\Support\CvLayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CvExportService
{
    public const DISK = 'local';

    public function __construct(
        private readonly CvPdfRenderer $renderer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * The gate. Export runs only when the draft is reviewed AND the content still matches
     * the fingerprint taken at review time (D-40).
     */
    public function export(CvDraft $draft, int $actorId, string $companyId): CvExport
    {
        if ($draft->status !== 'reviewed' || $draft->content_hash === null) {
            throw ValidationException::withMessages([
                'status' => 'The CV must be reviewed before it can be exported.',
            ]);
        }

        if (! hash_equals($draft->content_hash, CvContent::fingerprintOf($draft))) {
            throw ValidationException::withMessages([
                'status' => 'The CV changed after review. Review it again before exporting.',
            ]);
        }

        $bytes = $this->renderer->render(CvLayout::build($draft));

        $key = (string) Str::uuid();
        $path = self::directory($companyId).'/'.$key.'.pdf';

        if (! Storage::disk(self::DISK)->put($path, $bytes)) {
            throw new \RuntimeException('Could not store the generated PDF.');
        }

        try {
            return DB::transaction(function () use ($draft, $bytes, $key, $actorId, $companyId): CvExport {
                $export = new CvExport;
                $export->forceFill([
                    'cv_draft_id' => $draft->id,
                    'storage_key' => $key,
                    'sha256' => hash('sha256', $bytes),
                    'size_bytes' => strlen($bytes),
                    'content_hash' => (string) $draft->content_hash,
                    'created_by' => $actorId,
                    'created_at' => now(),
                ]);
                $export->save();

                $this->audit->record(
                    'cv_export.created',
                    'success',
                    'cv_export',
                    $export->id,
                    $actorId,
                    $companyId,
                    ['size_bytes' => $export->size_bytes],
                );

                return $export;
            });
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($path);

            throw $e;
        }
    }

    public static function directory(string $companyId): string
    {
        return 'cv-exports/'.$companyId;
    }

    public static function pathFor(CvExport $export): string
    {
        return self::directory($export->company_id).'/'.$export->storage_key.'.pdf';
    }
}
