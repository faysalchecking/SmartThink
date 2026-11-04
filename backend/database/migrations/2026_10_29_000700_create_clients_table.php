<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name', 150);
            $table->char('country', 2)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['company_id', 'archived_at']);
            // Needed as a composite foreign key target, so job orders can only point at their own company's clients.
            $table->unique(['id', 'company_id']);
        });

        DB::statement('ALTER TABLE clients ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY clients_tenant_isolation ON clients
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
