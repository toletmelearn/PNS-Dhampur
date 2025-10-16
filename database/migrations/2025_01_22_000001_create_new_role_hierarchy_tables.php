<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create schools table for multi-school support
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('principal_name')->nullable();
            $table->date('established_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('settings')->nullable(); // School-specific settings
            $table->timestamps();
            
            $table->index(['status', 'code']);
        });

        // Create new roles table with hierarchy support
        Schema::create('new_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // super_admin, admin, principal, teacher, student, parent
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('hierarchy_level'); // 1=super_admin, 2=admin, 3=principal, 4=teacher, 5=student/parent
            $table->json('permissions'); // Array of permissions
            $table->json('default_permissions')->nullable(); // Default permissions for this role
            $table->boolean('can_create_users')->default(false);
            $table->json('can_create_roles')->nullable(); // Array of role names this role can create
            $table->boolean('is_system_role')->default(false); // Cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['hierarchy_level', 'is_active']);
            $table->index(['name', 'is_active']);
        });

        // Create user profiles table for extended user information
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('alternate_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country')->default('India');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('profile_photo')->nullable();
            $table->json('additional_info')->nullable(); // Role-specific additional information
            $table->timestamps();
            
            $table->index(['first_name', 'last_name']);
            $table->index(['phone']);
        });

        // Create user_role_assignments table for flexible role assignment
        Schema::create('user_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained('new_roles')->onDelete('cascade');
            $table->foreignId('school_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false); // Primary role for the user
            $table->json('scope_restrictions')->nullable(); // Additional restrictions for this role assignment
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'role_id', 'school_id'], 'unique_user_role_school');
            $table->index(['user_id', 'is_active', 'is_primary']);
            $table->index(['role_id', 'school_id', 'is_active']);
            $table->index(['assigned_by', 'assigned_at']);
        });

        // Create permissions table for granular permission management
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'users.create', 'attendance.view_all'
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('module'); // e.g., 'users', 'attendance', 'reports'
            $table->string('action'); // e.g., 'create', 'view', 'edit', 'delete'
            $table->string('scope')->default('own'); // 'own', 'assigned', 'school', 'all'
            $table->boolean('is_system_permission')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['module', 'action', 'scope']);
            $table->index(['is_active', 'module']);
        });

        // Create role_permissions table for role-permission mapping
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('new_roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->boolean('is_granted')->default(true);
            $table->json('conditions')->nullable(); // Additional conditions for this permission
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
            $table->index(['role_id', 'is_granted']);
        });

        // Create user_permissions table for user-specific permission overrides
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->boolean('is_granted'); // true = grant, false = deny (override role permission)
            $table->foreignId('granted_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'permission_id']);
            $table->index(['user_id', 'is_granted']);
            $table->index(['granted_by', 'granted_at']);
        });

        // Create teacher_assignments table for teacher-class relationships
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('class_name');
            $table->string('section')->nullable();
            $table->string('subject')->nullable();
            $table->boolean('is_class_teacher')->default(false);
            $table->date('assigned_from');
            $table->date('assigned_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['teacher_id', 'school_id', 'is_active']);
            $table->index(['class_name', 'section', 'school_id']);
        });

        // Create student_enrollments table for student-school relationships
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('student_id_number')->unique();
            $table->string('class_name');
            $table->string('section')->nullable();
            $table->string('roll_number')->nullable();
            $table->date('enrollment_date');
            $table->date('graduation_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active');
            $table->json('academic_info')->nullable(); // Academic year, stream, etc.
            $table->timestamps();
            
            $table->unique(['student_id_number', 'school_id']);
            $table->index(['student_id', 'school_id', 'status']);
            $table->index(['class_name', 'section', 'school_id']);
        });

        // Create parent_student_relationships table
        Schema::create('parent_student_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->enum('relationship_type', ['father', 'mother', 'guardian', 'other']);
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_pickup')->default(true);
            $table->boolean('emergency_contact')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['parent_id', 'student_id']);
            $table->index(['student_id', 'is_primary_contact']);
            $table->index(['parent_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student_relationships');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_role_assignments');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('new_roles');
        Schema::dropIfExists('schools');
    }
};