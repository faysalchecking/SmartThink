<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->uuid('application_id');
            $table->foreignId('interviewer_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('scheduled_at');
            $table->text('result')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['company_id', 'scheduled_at']);
            $table->index(['company_id', 'application_id']);
        });

        DB::statement("ALTER TABLE interviews ADD CONSTRAINT interviews_result_check CHECK (result IN ('pending', 'passed', 'failed', 'no_show'))");

        // The application must belong to the same company as the interview.
        DB::statement('ALTER TABLE interviews ADD CONSTRAINT interviews_application_fk FOREIGN KEY (application_id, company_id) REFERENCES candidate_applications (id, company_id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE interviews ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY interviews_tenant_isolation ON interviews
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
