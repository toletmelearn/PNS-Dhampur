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
        Schema::table('students', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['class_id']);
            
            // Make admission_no not nullable (if there are existing null values, they need to be handled first)
            $table->string('admission_no')->nullable(false)->change();
            
            // Re-add foreign key constraint with cascade delete
            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop the cascade foreign key constraint
            $table->dropForeign(['class_id']);
            
            // Make admission_no nullable again
            $table->string('admission_no')->nullable()->change();
            
            // Re-add foreign key constraint with set null
            $table->foreign('class_id')->references('id')->on('class_models')->onDelete('set null');
        });
    }
};
