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
    public function up()
    {
        // Add soft deletes to exams table
        Schema::table('exams', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to fees table
        Schema::table('fees', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to subjects table
        Schema::table('subjects', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to results table
        Schema::table('results', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to salaries table
        Schema::table('salaries', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove soft deletes from exams table
        Schema::table('exams', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from fees table
        Schema::table('fees', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from subjects table
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from results table
        Schema::table('results', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from salaries table
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
