<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->uuid('candidate_id');
            $table->text('document_type');
            // Random name of the file on disk. Never shown to the client.
            $table->uuid('storage_key')->unique();
            $table->string('original_name', 120);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->text('review_status')->default('pending');
            $table->string('review_reason', 500)->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['company_id', 'candidate_id']);
            $table->unique(['id', 'company_id']);
        });

        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_type_check CHECK (document_type IN ('passport', 'medical', 'visa', 'certificate', 'other'))");
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_review_check CHECK (review_status IN ('pending', 'verified', 'rejected'))");
        DB::statement('ALTER TABLE documents ADD CONSTRAINT documents_size_check CHECK (size_bytes > 0 AND size_bytes <= 10485760)');

        // The candidate must belong to the same company as the document.
        DB::statement('ALTER TABLE documents ADD CONSTRAINT documents_candidate_fk FOREIGN KEY (candidate_id, company_id) REFERENCES candidates (id, company_id) ON DELETE RESTRICT');

        DB::statement('ALTER TABLE documents ENABLE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY documents_tenant_isolation ON documents
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
