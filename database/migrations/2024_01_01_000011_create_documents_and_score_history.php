<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users');

            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size_bytes');

            $table->enum('document_type', [
                'petition', 'reply', 'affidavit', 'evidence',
                'witness_statement', 'order', 'judgment', 'other'
            ]);
            $table->integer('version')->default(1);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['case_id', 'document_type']);
        });

        // Tracks priority score evolution over time — used for analytics
        // and helps explain "why this case is listed today" in the viva demo
        Schema::create('priority_score_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 6, 2);
            $table->json('factors');
            // Stores the breakdown: {age: 12, urgency: 25, adjournments: 15, ...}
            $table->string('computed_by')->default('system');
            // 'system' (cron) or user_id if manually triggered
            $table->timestamp('computed_at');

            $table->index(['case_id', 'computed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priority_score_history');
        Schema::dropIfExists('case_documents');
    }
};
