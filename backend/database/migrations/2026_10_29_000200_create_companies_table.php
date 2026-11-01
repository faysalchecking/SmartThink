<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('slug', 80)->unique();
            $table->text('status')->default('pending');
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE companies ADD CONSTRAINT companies_status_check CHECK (status IN ('pending', 'active', 'suspended'))");
        DB::statement("ALTER TABLE companies ADD CONSTRAINT companies_slug_check CHECK (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
