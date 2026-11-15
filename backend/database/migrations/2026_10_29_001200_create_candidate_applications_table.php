<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_applications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->uuid('candidate_id');
            $table->uuid('job_order_id');
            $table->uuid('stage_id');
            $table->text('status')->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['candidate_id', 'job_order_id']);
            $table->unique(['id', 'company_id']);
            $table->index(['company_id', 'stage_id']);
            $table->index(['company_id', 'job_order_id']);
        });

        DB::statement("ALTER TABLE candidate_applications ADD CONSTRAINT candidate_applications_status_check CHECK (status IN ('active', 'rejected', 'withdrawn', 'placed'))");

        // Every reference must point at a row of the same company.
        DB::statement('ALTER TABLE candidate_applications ADD CONSTRAINT candidate_applications_candidate_fk FOREIGN KEY (candidate_id, company_id) REFERENCES candidates (id, company_id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE candidate_applications ADD CONSTRAINT candidate_applications_job_fk FOREIGN KEY (job_order_id, company_id) REFERENCES job_orders (id, company_id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE candidate_applications ADD CONSTRAINT candidate_applications_stage_fk FOREIGN KEY (stage_id, company_id) REFERENCES pipeline_stages (id, company_id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE candidate_applications ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY candidate_applications_tenant_isolation ON candidate_applications
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_applications');
    }
};
