<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_exports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->uuid('cv_draft_id');
            $table->uuid('storage_key')->unique();
            $table->char('sha256', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->char('content_hash', 64);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['company_id', 'cv_draft_id']);
        });

        DB::statement('ALTER TABLE cv_exports ADD CONSTRAINT cv_exports_size_check CHECK (size_bytes > 0 AND size_bytes <= 16777216)');
        DB::statement('ALTER TABLE cv_exports ADD CONSTRAINT cv_exports_draft_fk FOREIGN KEY (cv_draft_id, company_id) REFERENCES cv_drafts (id, company_id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE cv_exports ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY cv_exports_tenant_isolation ON cv_exports
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);

        // Exports are a record of what was sent, so they cannot be changed afterwards.
        DB::statement('CREATE TRIGGER cv_exports_no_update_delete BEFORE UPDATE OR DELETE ON cv_exports FOR EACH ROW EXECUTE FUNCTION stage_history_block_mutation()');
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_exports');
    }
};
