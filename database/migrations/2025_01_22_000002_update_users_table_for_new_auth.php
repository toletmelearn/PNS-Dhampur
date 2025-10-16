<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add new security and authentication fields
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }
            
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }
            
            // Enhanced security fields
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('password');
            }
            
            if (!Schema::hasColumn('users', 'password_never_expires')) {
                $table->boolean('password_never_expires')->default(false)->after('must_change_password');
            }
            
            if (!Schema::hasColumn('users', 'account_locked_at')) {
                $table->timestamp('account_locked_at')->nullable()->after('locked_until');
            }
            
            if (!Schema::hasColumn('users', 'account_locked_reason')) {
                $table->string('account_locked_reason')->nullable()->after('account_locked_at');
            }
            
            // Two-factor authentication enhancements
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
            }
            
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('two_factor_recovery_codes');
            }
            
            // Session and login tracking
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                // Do not rely on column order to avoid migration dependency issues
                $table->ipAddress('last_login_ip')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'last_login_user_agent')) {
                $table->text('last_login_user_agent')->nullable()->after('last_login_ip');
            }
            
            if (!Schema::hasColumn('users', 'login_count')) {
                $table->unsignedInteger('login_count')->default(0)->after('last_login_user_agent');
            }
            
            // Account status and metadata
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended', 'pending_verification'])->default('pending_verification')->after('is_active');
            }
            
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('status');
            }
            
            if (!Schema::hasColumn('users', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
            }
            
            if (!Schema::hasColumn('users', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null')->after('updated_by');
            }
            
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes()->after('deleted_by');
            }
            
            // Preferences and settings
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('Asia/Kolkata')->after('deleted_at');
            }
            
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language', 5)->default('en')->after('timezone');
            }
            
            if (!Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable()->after('language');
            }
            
            // Add indexes for performance (only if columns exist)
            if (Schema::hasColumn('users', 'status') && Schema::hasColumn('users', 'is_active')) {
                $table->index(['status', 'is_active']);
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->index(['email_verified_at']);
            }
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->index(['last_login_at']);
            }
            if (Schema::hasColumn('users', 'created_by')) {
                $table->index(['created_by']);
            }
            if (Schema::hasColumn('users', 'username')) {
                $table->index(['username']);
            }
        });
        
        // Add soft deletes index if it doesn't exist
        try {
            DB::statement('ALTER TABLE users ADD INDEX idx_users_deleted_at (deleted_at)');
        } catch (Exception $e) {
            // Index might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status', 'is_active']);
            $table->dropIndex(['email_verified_at']);
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['username']);
        });
        
        // Drop columns
        $columnsToRemove = [
            'username',
            'phone_verified_at',
            'must_change_password',
            'password_never_expires',
            'account_locked_at',
            'account_locked_reason',
            'two_factor_recovery_codes',
            'two_factor_enabled',
            'last_login_ip',
            'last_login_user_agent',
            'login_count',
            'status',
            'created_by',
            'updated_by',
            'deleted_by',
            'deleted_at',
            'timezone',
            'language',
            'preferences'
        ];
        
        foreach ($columnsToRemove as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};