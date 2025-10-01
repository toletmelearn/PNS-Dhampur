<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->boolean('verified')->default(false);
    });
}

public function down(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->dropColumn('verified');
    });
}

};
