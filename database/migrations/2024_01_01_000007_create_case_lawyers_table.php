<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_lawyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lawyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('representing_party_id')->nullable()->constrained('case_parties')->nullOnDelete();
            $table->enum('role', ['lead', 'junior', 'amicus_curiae'])->default('lead');
            $table->date('engaged_on');
            $table->date('disengaged_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['case_id', 'lawyer_id', 'representing_party_id'], 'case_lawyer_party_unique');
            $table->index(['lawyer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_lawyers');
    }
};
