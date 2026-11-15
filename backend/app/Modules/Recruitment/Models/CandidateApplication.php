<?php

namespace App\Modules\Recruitment\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $company_id
 * @property string $candidate_id
 * @property string $job_order_id
 * @property string $stage_id
 * @property string $status
 * @property int $created_by
 */
class CandidateApplication extends Model
{
    use BelongsToCompany, HasUuids;

    public const STATUSES = ['active', 'rejected', 'withdrawn', 'placed'];

    protected $fillable = ['candidate_id', 'job_order_id', 'stage_id', 'status', 'created_by'];

    /** @return BelongsTo<Candidate, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /** @return BelongsTo<JobOrder, $this> */
    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    /** @return BelongsTo<PipelineStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class);
    }
}
