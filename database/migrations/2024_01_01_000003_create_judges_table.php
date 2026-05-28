<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->string('courtroom_number')->nullable();
            $table->json('specializations')->nullable();
            // e.g., ["criminal", "civil", "family"]
            $table->integer('max_daily_cases')->default(30);
            $table->integer('current_pending_count')->default(0);
            $table->date('appointment_date')->nullable();
            $table->boolean('is_available')->default(true);
            $table->date('unavailable_until')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'court_id']);
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judges');
    }
};
