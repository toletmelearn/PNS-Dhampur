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
        // Add soft deletes to critical tables
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->softDeletes();
            // Add foreign key constraint for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('class_models', function (Blueprint $table) {
            $table->softDeletes();
            // Add foreign key constraint for class_teacher_id
            $table->foreign('class_teacher_id')->references('id')->on('teachers')->onDelete('set null');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add missing foreign key constraints
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('set null');
            $table->foreign('marked_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('fees', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key constraints first (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('fees', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
            });

            Schema::table('attendances', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->dropForeign(['class_id']);
                $table->dropForeign(['marked_by']);
            });

            Schema::table('class_models', function (Blueprint $table) {
                $table->dropForeign(['class_teacher_id']);
            });

            Schema::table('teachers', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        // Remove soft deletes
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('class_models', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
