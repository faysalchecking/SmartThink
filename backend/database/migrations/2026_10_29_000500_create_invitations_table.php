<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('email', 255);
            $table->text('role');
            // Only the SHA-256 hash of the invitation token is stored.
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index(['company_id', 'email']);
        });

        DB::statement("ALTER TABLE invitations ADD CONSTRAINT invitations_role_check CHECK (role IN ('company_admin', 'employee'))");

        // At most one open invitation per email per company.
        DB::statement(
            'CREATE UNIQUE INDEX invitations_one_open_per_email ON invitations (company_id, email) '
            .'WHERE accepted_at IS NULL AND revoked_at IS NULL',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};