<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schema_health', function (Blueprint $table): void {
            $table->unsignedSmallInteger('id')->primary();
            $table->timestampTz('checked_at')->useCurrent();
        });

        // Fixed SQL string, no interpolated values (ADR-0002).
        DB::statement('ALTER TABLE schema_health ADD CONSTRAINT schema_health_single_row CHECK (id = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('schema_health');
    }
};
