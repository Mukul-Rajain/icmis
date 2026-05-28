<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjournments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hearing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users');

            $table->enum('requested_by_role', ['judge', 'lawyer_petitioner', 'lawyer_respondent', 'court_staff']);
            $table->text('reason');
            $table->enum('reason_category', [
                'lawyer_unavailable',
                'witness_unavailable',
                'document_pending',
                'judge_unavailable',
                'medical',
                'natural_calamity',
                'other'
            ]);

            $table->boolean('granted')->default(false);
            $table->date('new_date')->nullable();
            $table->text('judge_remarks')->nullable();
            $table->foreignId('decided_by_judge_id')->nullable()->constrained('judges')->nullOnDelete();

            $table->timestamps();

            $table->index(['case_id', 'granted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjournments');
    }
};
