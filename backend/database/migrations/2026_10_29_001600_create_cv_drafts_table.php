<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->uuid('candidate_id');
            $table->text('template_key');
            $table->jsonb('content');
            $table->jsonb('section_order');
            $table->jsonb('section_visibility');
            $table->text('status')->default('draft');
            $table->char('content_hash', 64)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();

            $table->index(['company_id', 'candidate_id']);
            $table->unique(['id', 'company_id']);
        });

        DB::statement("ALTER TABLE cv_drafts ADD CONSTRAINT cv_drafts_template_check CHECK (template_key IN ('classic', 'modern', 'compact'))");
        DB::statement("ALTER TABLE cv_drafts ADD CONSTRAINT cv_drafts_status_check CHECK (status IN ('draft', 'reviewed'))");
        DB::statement("ALTER TABLE cv_drafts ADD CONSTRAINT cv_drafts_content_check CHECK (jsonb_typeof(content) = 'object')");
        DB::statement("ALTER TABLE cv_drafts ADD CONSTRAINT cv_drafts_order_check CHECK (jsonb_typeof(section_order) = 'array')");
        DB::statement("ALTER TABLE cv_drafts ADD CONSTRAINT cv_drafts_visibility_check CHECK (jsonb_typeof(section_visibility) = 'object')");

        // A reviewed draft must carry its fingerprint and reviewer. The database enforces this too.
        DB::statement(<<<'SQL'
            ALTER TABLE cv_drafts ADD CONSTRAINT cv_drafts_reviewed_check CHECK (
                status = 'draft'
                OR (content_hash IS NOT NULL AND reviewed_by IS NOT NULL AND reviewed_at IS NOT NULL)
            )
        SQL);

        DB::statement('ALTER TABLE cv_drafts ADD CONSTRAINT cv_drafts_candidate_fk FOREIGN KEY (candidate_id, company_id) REFERENCES candidates (id, company_id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE cv_drafts ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY cv_drafts_tenant_isolation ON cv_drafts
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_drafts');
    }
};
