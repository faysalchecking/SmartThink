<?php

namespace App\Modules\Recruitment\Models;

use App\Modules\Tenancy\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $company_id
 * @property string $client_id
 * @property string $title
 * @property string|null $country
 * @property string $status
 * @property int $openings
 * @property int $created_by
 * @property-read Client $client
 */
class JobOrder extends Model
{
    use BelongsToCompany, HasUuids;

    public const STATUSES = ['draft', 'open', 'on_hold', 'closed'];

    protected $fillable = ['client_id', 'title', 'country', 'status', 'openings', 'created_by'];

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
