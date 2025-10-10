<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_models', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('class_teacher_id');
            $table->text('description')->nullable()->after('section');
            $table->integer('capacity')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_models', function (Blueprint $table) {
            if (Schema::hasColumn('class_models', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
        
        Schema::table('class_models', function (Blueprint $table) {
            if (Schema::hasColumn('class_models', 'description')) {
                $table->dropColumn('description');
            }
        });
        
        Schema::table('class_models', function (Blueprint $table) {
            if (Schema::hasColumn('class_models', 'capacity')) {
                $table->dropColumn('capacity');
            }
        });
    }
};