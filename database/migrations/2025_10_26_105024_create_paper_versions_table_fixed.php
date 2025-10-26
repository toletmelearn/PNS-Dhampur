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
        Schema::create('paper_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paper_id');
            $table->string('version_number');
            $table->text('content')->nullable();
            $table->timestamps();
            
            // Add foreign key later in separate migration
            // $table->foreign('paper_id')->references('id')->on('papers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paper_versions');
    }
};
