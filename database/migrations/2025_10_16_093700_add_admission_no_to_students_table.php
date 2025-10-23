<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return; // Safety: some test environments may not have the table yet
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'admission_no')) {
                $table->string('admission_no')->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'admission_no')) {
                $table->dropColumn('admission_no');
            }
        });
    }
};