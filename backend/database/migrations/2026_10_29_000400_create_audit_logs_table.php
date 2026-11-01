<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->timestampTz('occurred_at')->useCurrent();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->string('action', 80);
            $table->string('result', 20);
            $table->string('target_type', 60);
            $table->string('target_id', 80)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->jsonb('metadata')->default('{}');

            $table->index(['company_id', 'occurred_at']);
        });

        // Defence in depth: even a privileged session cannot change or delete audit rows.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_logs_block_mutation() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'audit_logs is append-only';
            END;
            $$;
        SQL);

        DB::statement(
            'CREATE TRIGGER audit_logs_no_update_delete BEFORE UPDATE OR DELETE ON audit_logs '
            .'FOR EACH ROW EXECUTE FUNCTION audit_logs_block_mutation()',
        );

        // The runtime role may only read and append. The role name is validated before use.
        $runtimeRole = config('database.connections.pgsql.username');
        if (! is_string($runtimeRole) || preg_match('/^[a-z_][a-z0-9_]{0,62}$/', $runtimeRole) !== 1) {
            throw new RuntimeException('Runtime database role name is not a safe identifier.');
        }

        DB::statement("REVOKE UPDATE, DELETE, TRUNCATE ON audit_logs FROM \"{$runtimeRole}\"");
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        DB::statement('DROP FUNCTION IF EXISTS audit_logs_block_mutation()');
    }
};
