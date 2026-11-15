<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name', 80);
            $table->unsignedSmallInteger('position');
            $table->text('kind')->default('standard');
            $table->boolean('is_terminal')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestampsTz();

            $table->unique(['company_id', 'name']);
            $table->unique(['id', 'company_id']);
        });

        DB::statement("ALTER TABLE pipeline_stages ADD CONSTRAINT pipeline_stages_kind_check CHECK (kind IN ('standard', 'overseas'))");

        DB::statement('ALTER TABLE pipeline_stages ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY pipeline_stages_tenant_isolation ON pipeline_stages
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
