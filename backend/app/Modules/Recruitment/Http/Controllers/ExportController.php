<?php

namespace App\Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Tenancy\Http\Middleware\ResolveCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportController extends Controller
{
    private const BOM = "\xEF\xBB\xBF";

    /** @var list<string> */
    private const HEADERS = ['full_name', 'email', 'phone', 'nationality', 'date_of_birth'];

    private const DEFAULT_MAX_ROWS = 5000;

    public function __construct(private readonly AuditLogger $audit) {}

    public function candidates(Request $request): StreamedResponse|JsonResponse
    {
        $membership = ResolveCompany::membership($request);

        if (! $membership->canExport()) {
            abort(403);
        }

        $max = (int) config('smartthink.export_max_rows', self::DEFAULT_MAX_ROWS);

        // Passport numbers are not selected at all, so they cannot reach the file.
        $candidates = Candidate::query()
            ->whereNull('archived_at')
            ->orderBy('full_name')
            ->orderBy('id')
            ->limit($max + 1)
            ->get();

        if ($candidates->count() > $max) {
            return response()->json([
                'error' => [
                    'code' => 'EXPORT_TOO_LARGE',
                    'message' => "An export is limited to {$max} rows. Narrow the data before exporting.",
                ],
            ], 422);
        }

        // The audit entry holds the row count only, never names or contact details (D-49).
        $this->audit->record(
            action: 'export.candidates',
            result: 'success',
            targetType: 'candidate',
            actorId: $this->actorId($request),
            companyId: $membership->company_id,
            metadata: ['row_count' => $candidates->count()],
        );

        return response()->streamDownload(function () use ($candidates): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            // BOM makes Excel read UTF-8 (Bangla names) correctly.
            fwrite($out, self::BOM);
            fputcsv($out, self::HEADERS, ',', '"', '\\');

            foreach ($candidates as $candidate) {
                fputcsv($out, [
                    self::safe($candidate->full_name),
                    self::safe($candidate->email),
                    self::safe($candidate->phone),
                    self::safe($candidate->nationality),
                    self::safe($candidate->date_of_birth?->toDateString()),
                ], ',', '"', '\\');
            }

            fclose($out);
        }, 'candidates.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Neutralises spreadsheet formulas (CSV injection). A leading quote makes the cell text.
     */
    private static function safe(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
