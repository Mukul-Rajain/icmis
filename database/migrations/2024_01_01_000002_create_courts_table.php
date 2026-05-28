<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('court_code')->unique();
            $table->enum('court_type', ['district', 'sessions', 'high_court', 'magistrate', 'family', 'commercial']);
            $table->string('location');
            $table->string('jurisdiction')->nullable();
            $table->integer('total_courtrooms')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('court_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
