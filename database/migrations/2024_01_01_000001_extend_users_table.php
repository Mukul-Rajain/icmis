<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type')->after('email')->default('litigant');
            // Values: judge, court_staff, lawyer, litigant, admin

            $table->string('phone', 20)->nullable()->after('user_type');
            $table->string('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_senior_citizen')->default(false);
            $table->boolean('is_active')->default(true);

            // Lawyer-specific
            $table->string('bar_council_number')->nullable();
            $table->integer('years_of_practice')->nullable();

            // Judge-specific (most fields go in judges table, but quick reference)
            $table->string('designation')->nullable();

            $table->index('user_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'user_type', 'phone', 'address', 'date_of_birth',
                'is_senior_citizen', 'is_active', 'bar_council_number',
                'years_of_practice', 'designation'
            ]);
        });
    }
};
