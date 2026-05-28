<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('category', ['civil', 'criminal', 'family', 'commercial', 'constitutional']);
            $table->enum('default_track', ['fast', 'standard', 'complex']);

            // DCFM scoring inputs — base values used by PriorityScorer
            $table->integer('base_priority')->default(50);
            // 0-100 scale; bail=90, property=40, etc.

            $table->integer('typical_duration_days')->default(180);
            // Expected disposal time for the track

            $table->boolean('is_time_sensitive')->default(false);
            // Bail, habeas corpus, urgent injunctions

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'default_track']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_types');
    }
};
