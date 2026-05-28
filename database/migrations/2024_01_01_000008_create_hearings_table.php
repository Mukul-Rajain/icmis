<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hearings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('court_id')->constrained();

            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->string('courtroom_number')->nullable();
            $table->integer('estimated_duration_minutes')->default(30);

            $table->enum('status', [
                'scheduled',
                'in_progress',
                'completed',
                'adjourned',
                'cancelled',
                'judge_unavailable'
            ])->default('scheduled');

            $table->enum('stage_at_hearing', [
                'first_hearing', 'evidence', 'cross_examination',
                'arguments', 'judgment', 'miscellaneous'
            ])->nullable();

            $table->text('outcome')->nullable();
            $table->text('next_action')->nullable();
            $table->date('next_hearing_date')->nullable();

            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();

            $table->timestamps();

            $table->index(['scheduled_date', 'court_id']);
            $table->index(['case_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hearings');
    }
};
