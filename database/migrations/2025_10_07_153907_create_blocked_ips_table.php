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
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->text('reason');
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('blocked_by');
            $table->timestamps();

            $table->index('ip_address');
            $table->index('blocked_at');
            $table->index('expires_at');
            
            $table->foreign('blocked_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blocked_ips');
    }
};
