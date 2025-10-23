<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // razorpay, stripe
            $table->string('mode')->default('test'); // test or live
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['provider', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
    }
};