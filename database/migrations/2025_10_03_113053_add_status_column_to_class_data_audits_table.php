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
        Schema::table('class_data_audits', function (Blueprint $table) {
            $table->string('status')->default('pending_approval')->after('auditable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('class_data_audits', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
