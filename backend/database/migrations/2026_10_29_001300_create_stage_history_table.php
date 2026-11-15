<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $runtimeRole = $this->runtimeRole();

        Schema::create('stage_history', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->uuid('application_id');
            $table->uuid('from_stage_id')->nullable();
            $table->uuid('to_stage_id');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('changed_at');
            $table->string('note', 500)->nullable();

            $table->index(['company_id', 'application_id']);
        });

        DB::statement('ALTER TABLE stage_history ADD CONSTRAINT stage_history_application_fk FOREIGN KEY (application_id, company_id) REFERENCES candidate_applications (id, company_id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE stage_history ADD CONSTRAINT stage_history_from_stage_fk FOREIGN KEY (from_stage_id, company_id) REFERENCES pipeline_stages (id, company_id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE stage_history ADD CONSTRAINT stage_history_to_stage_fk FOREIGN KEY (to_stage_id, company_id) REFERENCES pipeline_stages (id, company_id) ON DELETE RESTRICT');

        // Append-only, in the database, even for the runtime role.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION stage_history_block_mutation() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'stage_history is append-only';
            END;
            $$;
        SQL);
        DB::statement('CREATE TRIGGER stage_history_no_update_delete BEFORE UPDATE OR DELETE ON stage_history FOR EACH ROW EXECUTE FUNCTION stage_history_block_mutation()');
        DB::statement("REVOKE UPDATE, DELETE, TRUNCATE ON stage_history FROM \"{$runtimeRole}\"");

        DB::statement('ALTER TABLE stage_history ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY stage_history_tenant_isolation ON stage_history
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_history');
        DB::statement('DROP FUNCTION IF EXISTS stage_history_block_mutation()');
    }

    private function runtimeRole(): string
    {
        $role = config('database.connections.pgsql.username');

        if (! is_string($role) || preg_match('/^[a-z_][a-z0-9_]{0,62}$/', $role) !== 1) {
            throw new RuntimeException('Runtime database role name is not a safe identifier.');
        }

        return $role;
    }
};
