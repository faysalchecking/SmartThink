<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('full_name', 150);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 80)->nullable();
            // Encrypted by the application (Eloquent encrypted cast). The API never returns it in full.
            $table->text('passport_number')->nullable();
            $table->text('summary')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['company_id', 'archived_at']);
            $table->index(['company_id', 'full_name']);
            $table->unique(['id', 'company_id']);
        });

        DB::statement('ALTER TABLE candidates ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY candidates_tenant_isolation ON candidates
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
