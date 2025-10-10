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
        Schema::table('users', function (Blueprint $table) {
            // Password security fields
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->timestamp('password_expires_at')->nullable()->after('password_changed_at');
            $table->boolean('password_reset_required')->default(false)->after('password_expires_at');
            
            // Account lockout fields
            $table->integer('failed_login_attempts')->default(0)->after('password_reset_required');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            
            // Add index for performance
            $table->index(['password_expires_at']);
            $table->index(['locked_until']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['password_expires_at']);
            $table->dropIndex(['locked_until']);
        });
        
        $columns = [
            'password_changed_at',
            'password_expires_at',
            'password_reset_required',
            'failed_login_attempts',
            'locked_until'
        ];
        
        foreach ($columns as $column) {
            Schema::table('users', function (Blueprint $table) use ($column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            });
        }
    }
};