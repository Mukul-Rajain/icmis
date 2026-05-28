<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Linked if the party has registered as a litigant in the system

            $table->enum('party_type', ['petitioner', 'respondent', 'plaintiff', 'defendant', 'accused', 'complainant']);
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_in_custody')->default(false);
            $table->boolean('is_senior_citizen')->default(false);

            $table->timestamps();

            $table->index(['case_id', 'party_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_parties');
    }
};
