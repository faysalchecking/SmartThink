<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Lets application_rows reference a job only together with its own company.
        DB::statement('ALTER TABLE job_orders ADD CONSTRAINT job_orders_id_company_key UNIQUE (id, company_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE job_orders DROP CONSTRAINT IF EXISTS job_orders_id_company_key');
    }
};
