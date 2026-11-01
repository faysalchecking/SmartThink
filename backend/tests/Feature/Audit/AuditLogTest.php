<?php

namespace Tests\Feature\Audit;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Audit\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_company_creation_is_audited_with_actor(): void
    {
        $platformAdmin = $this->makeUser(platformAdmin: true);
        $slug = 'audit-'.Str::lower(Str::random(6));

        $companyId = $this->actingAs($platformAdmin)->frontend()
            ->postJson('/api/v1/platform/companies', ['name' => 'Audit Co', 'slug' => $slug])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'company.created',
            'target_id' => $companyId,
            'actor_user_id' => $platformAdmin->id,
            'result' => 'success',
        ]);
    }

    public function test_sensitive_metadata_is_redacted_before_storage(): void
    {
        app(AuditLogger::class)->record(
            action: 'test.redaction',
            result: 'success',
            targetType: 'test',
            metadata: [
                'password' => 'should-not-be-stored',
                'nested' => ['api_token' => 'should-not-be-stored', 'safe' => 'kept'],
            ],
        );

        $log = AuditLog::query()->where('action', 'test.redaction')->firstOrFail();

        $this->assertSame('[redacted]', $log->metadata['password']);
        $this->assertSame('[redacted]', $log->metadata['nested']['api_token']);
        $this->assertSame('kept', $log->metadata['nested']['safe']);
    }

    public function test_audit_model_refuses_updates(): void
    {
        app(AuditLogger::class)->record(action: 'test.immutable', result: 'success', targetType: 'test');
        $log = AuditLog::query()->where('action', 'test.immutable')->firstOrFail();

        $this->expectException(LogicException::class);
        $log->update(['result' => 'tampered']);
    }

    public function test_runtime_role_cannot_update_audit_rows_in_the_database(): void
    {
        app(AuditLogger::class)->record(action: 'test.db_immutable', result: 'success', targetType: 'test');

        $this->expectException(QueryException::class);

        // Nested transaction creates a savepoint, so the outer test transaction stays usable.
        DB::transaction(fn () => DB::table('audit_logs')
            ->where('action', 'test.db_immutable')
            ->update(['result' => 'tampered']));
    }
}
