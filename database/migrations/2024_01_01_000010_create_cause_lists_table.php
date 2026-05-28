<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cause_lists', function (Blueprint $table) {
            $table->id();
            $table->date('list_date');
            $table->foreignId('court_id')->constrained();
            $table->foreignId('judge_id')->constrained();

            $table->enum('status', ['draft', 'published', 'completed'])->default('draft');
            $table->integer('total_cases')->default(0);
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['list_date', 'judge_id']);
            $table->index(['list_date', 'court_id']);
        });

        Schema::create('cause_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cause_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained();
            $table->foreignId('hearing_id')->nullable()->constrained()->nullOnDelete();

            $table->integer('serial_number');
            $table->time('estimated_time_slot')->nullable();
            $table->integer('estimated_duration_minutes')->default(30);

            // Snapshot of priority score at the time of listing (for audit)
            $table->decimal('priority_score_at_listing', 6, 2)->default(0);
            $table->string('track_at_listing')->nullable();

            $table->timestamps();

            $table->unique(['cause_list_id', 'serial_number']);
            $table->index(['cause_list_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cause_list_items');
        Schema::dropIfExists('cause_lists');
    }
};
