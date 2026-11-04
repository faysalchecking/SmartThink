<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->uuid('client_id');
            $table->string('title', 150);
            $table->char('country', 2)->nullable();
            $table->text('status')->default('draft');
            $table->unsignedInteger('openings')->default(1);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['company_id', 'status']);
        });

        // Composite key: a job can only reference a client of the same company.
        DB::statement(
            'ALTER TABLE job_orders ADD CONSTRAINT job_orders_client_same_company '
            .'FOREIGN KEY (client_id, company_id) REFERENCES clients (id, company_id) ON DELETE RESTRICT',
        );
        DB::statement("ALTER TABLE job_orders ADD CONSTRAINT job_orders_status_check CHECK (status IN ('draft', 'open', 'on_hold', 'closed'))");
        DB::statement('ALTER TABLE job_orders ADD CONSTRAINT job_orders_openings_check CHECK (openings >= 1)');

        DB::statement('ALTER TABLE job_orders ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY job_orders_tenant_isolation ON job_orders
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
