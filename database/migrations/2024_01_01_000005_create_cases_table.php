<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            // Format: CASE/YYYY/NNNNN (e.g., CASE/2026/00123)

            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('case_type_id')->constrained();
            $table->foreignId('court_id')->constrained();
            $table->foreignId('assigned_judge_id')->nullable()->constrained('judges')->nullOnDelete();
            $table->foreignId('filed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('filing_date');

            // DCFM core fields
            $table->enum('track', ['fast', 'standard', 'complex']);
            $table->decimal('priority_score', 6, 2)->default(0);
            // 0-100 scale, computed by PriorityScorer

            $table->date('expected_disposal_date')->nullable();
            // Computed: filing_date + case_type.typical_duration_days

            // Workflow
            $table->enum('current_stage', [
                'registered',       // Just filed
                'notice_issued',    // Notice sent to parties
                'reply_filed',
                'evidence',
                'arguments',
                'judgment_reserved',
                'disposed',
                'transferred',
                'withdrawn'
            ])->default('registered');

            $table->enum('status', ['active', 'disposed', 'transferred', 'on_hold'])->default('active');

            // Tracking metrics (for analytics and re-scoring)
            $table->integer('hearing_count')->default(0);
            $table->integer('adjournment_count')->default(0);
            $table->date('last_hearing_date')->nullable();
            $table->date('next_hearing_date')->nullable();

            // Flags for priority scoring
            $table->boolean('has_interim_relief_pending')->default(false);
            $table->boolean('has_in_custody_accused')->default(false);
            $table->boolean('involves_senior_citizen')->default(false);

            $table->date('disposed_on')->nullable();
            $table->text('disposal_remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'track']);
            $table->index(['court_id', 'status']);
            $table->index('priority_score');
            $table->index('next_hearing_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
