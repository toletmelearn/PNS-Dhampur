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
        // If the system_settings table already exists from an earlier migration,
        // skip re-creation to prevent duplicate table errors.
        if (Schema::hasTable('system_settings')) {
            return;
        }

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json, array, file
            $table->string('group')->default('general'); // general, academic, security, notification, etc.
            $table->string('category')->nullable(); // Sub-category within group
            
            // Setting Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('help_text')->nullable();
            $table->json('validation_rules')->nullable(); // Validation rules for the setting
            $table->json('options')->nullable(); // Available options for select/radio types
            $table->text('default_value')->nullable();
            
            // UI and Display
            $table->string('input_type')->default('text'); // text, textarea, select, radio, checkbox, file, etc.
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->string('placeholder')->nullable();
            
            // Access Control
            $table->json('allowed_roles')->nullable(); // Roles that can modify this setting
            $table->boolean('requires_admin')->default(true);
            $table->boolean('requires_restart')->default(false); // If changing requires app restart
            $table->boolean('is_sensitive')->default(false); // For passwords, API keys, etc.
            
            // Environment and Context
            $table->string('environment')->nullable(); // production, staging, development
            $table->boolean('is_global')->default(true); // Global or per-tenant setting
            $table->string('tenant_id')->nullable(); // For multi-tenant applications
            $table->string('module')->nullable(); // Which module this setting belongs to
            
            // Versioning and History
            $table->text('previous_value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_modified')->nullable();
            $table->json('change_history')->nullable(); // History of changes
            $table->text('change_reason')->nullable();
            
            // Caching and Performance
            $table->boolean('is_cached')->default(false);
            $table->integer('cache_ttl')->nullable(); // Cache time-to-live in seconds
            $table->string('cache_key')->nullable();
            $table->timestamp('cached_at')->nullable();
            
            // Backup and Recovery
            $table->boolean('include_in_backup')->default(true);
            $table->boolean('is_critical')->default(false); // Critical system settings
            $table->text('backup_notes')->nullable();
            
            // Additional Information
            $table->json('metadata')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index(['key', 'is_active']);
            $table->index(['group', 'category']);
            $table->index(['type', 'is_visible']);
            $table->index(['sort_order', 'group']);
            $table->index(['is_editable', 'requires_admin']);
            $table->index(['module', 'environment']);
            $table->index(['is_cached', 'cache_ttl']);
            $table->index(['updated_by', 'last_modified']);
            $table->index(['is_critical', 'include_in_backup']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
};
