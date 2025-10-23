<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Safe helper to check index existence (MySQL only; SQLite returns false)
        $indexExists = function (string $table, string $indexName) {
            if (DB::getDriverName() === 'sqlite') {
                return false;
            }
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                return !empty($indexes);
            } catch (\Throwable $e) {
                return false;
            }
        };

        // hostel_buildings indexes (name, gender) for quick filtering
        if (Schema::hasTable('hostel_buildings')) {
            Schema::table('hostel_buildings', function (Blueprint $table) use ($indexExists) {
                if (Schema::hasColumn('hostel_buildings', 'name') && !$indexExists('hostel_buildings', 'idx_hostel_buildings_name')) {
                    $table->index('name', 'idx_hostel_buildings_name');
                }
                if (Schema::hasColumn('hostel_buildings', 'gender') && !$indexExists('hostel_buildings', 'idx_hostel_buildings_gender')) {
                    $table->index('gender', 'idx_hostel_buildings_gender');
                }
            });
        }

        // hostel_rooms performance indexes
        if (Schema::hasTable('hostel_rooms')) {
            Schema::table('hostel_rooms', function (Blueprint $table) use ($indexExists) {
                if (Schema::hasColumn('hostel_rooms', 'room_number') && !$indexExists('hostel_rooms', 'idx_hostel_rooms_room_number')) {
                    $table->index('room_number', 'idx_hostel_rooms_room_number');
                }
                if (Schema::hasColumn('hostel_rooms', 'status') && !$indexExists('hostel_rooms', 'idx_hostel_rooms_status')) {
                    $table->index('status', 'idx_hostel_rooms_status');
                }
                if (
                    Schema::hasColumn('hostel_rooms', 'building_id') &&
                    Schema::hasColumn('hostel_rooms', 'gender') &&
                    Schema::hasColumn('hostel_rooms', 'status') &&
                    !$indexExists('hostel_rooms', 'idx_hostel_rooms_building_gender_status')
                ) {
                    $table->index(['building_id', 'gender', 'status'], 'idx_hostel_rooms_building_gender_status');
                }
            });
        }

        // hostel_allocations performance indexes
        if (Schema::hasTable('hostel_allocations')) {
            Schema::table('hostel_allocations', function (Blueprint $table) use ($indexExists) {
                if (Schema::hasColumn('hostel_allocations', 'room_id') && Schema::hasColumn('hostel_allocations', 'status') && !$indexExists('hostel_allocations', 'idx_hostel_allocations_room_status')) {
                    $table->index(['room_id', 'status'], 'idx_hostel_allocations_room_status');
                }
                if (
                    Schema::hasColumn('hostel_allocations', 'student_id') &&
                    Schema::hasColumn('hostel_allocations', 'status') &&
                    Schema::hasColumn('hostel_allocations', 'vacated_at') &&
                    !$indexExists('hostel_allocations', 'idx_hostel_allocations_student_status_vacated')
                ) {
                    $table->index(['student_id', 'status', 'vacated_at'], 'idx_hostel_allocations_student_status_vacated');
                }
                if (Schema::hasColumn('hostel_allocations', 'allocated_at') && !$indexExists('hostel_allocations', 'idx_hostel_allocations_allocated_at')) {
                    $table->index('allocated_at', 'idx_hostel_allocations_allocated_at');
                }
            });
        }

        // library_issues indexes to speed up library operations
        if (Schema::hasTable('library_issues')) {
            Schema::table('library_issues', function (Blueprint $table) use ($indexExists) {
                if (
                    Schema::hasColumn('library_issues', 'book_id') &&
                    Schema::hasColumn('library_issues', 'status') &&
                    !$indexExists('library_issues', 'idx_library_issues_book_status')
                ) {
                    $table->index(['book_id', 'status'], 'idx_library_issues_book_status');
                }
                if (Schema::hasColumn('library_issues', 'student_id') && !$indexExists('library_issues', 'idx_library_issues_student')) {
                    $table->index('student_id', 'idx_library_issues_student');
                }
                if (Schema::hasColumn('library_issues', 'returned_at') && !$indexExists('library_issues', 'idx_library_issues_returned_at')) {
                    $table->index('returned_at', 'idx_library_issues_returned_at');
                }
                if (
                    Schema::hasColumn('library_issues', 'issued_at') &&
                    Schema::hasColumn('library_issues', 'due_at') &&
                    !$indexExists('library_issues', 'idx_library_issues_issued_due')
                ) {
                    $table->index(['issued_at', 'due_at'], 'idx_library_issues_issued_due');
                }
            });
        }
    }

    public function down(): void
    {
        // Drop indexes with guards (ignore if not present)
        $dropIndex = function (Blueprint $table, string $indexName) {
            try {
                $table->dropIndex($indexName);
            } catch (\Throwable $e) {
                // ignore
            }
        };

        if (Schema::hasTable('hostel_buildings')) {
            Schema::table('hostel_buildings', function (Blueprint $table) use ($dropIndex) {
                $dropIndex($table, 'idx_hostel_buildings_name');
                $dropIndex($table, 'idx_hostel_buildings_gender');
            });
        }

        if (Schema::hasTable('hostel_rooms')) {
            Schema::table('hostel_rooms', function (Blueprint $table) use ($dropIndex) {
                $dropIndex($table, 'idx_hostel_rooms_room_number');
                $dropIndex($table, 'idx_hostel_rooms_status');
                $dropIndex($table, 'idx_hostel_rooms_building_gender_status');
            });
        }

        if (Schema::hasTable('hostel_allocations')) {
            Schema::table('hostel_allocations', function (Blueprint $table) use ($dropIndex) {
                $dropIndex($table, 'idx_hostel_allocations_room_status');
                $dropIndex($table, 'idx_hostel_allocations_student_status_vacated');
                $dropIndex($table, 'idx_hostel_allocations_allocated_at');
            });
        }

        if (Schema::hasTable('library_issues')) {
            Schema::table('library_issues', function (Blueprint $table) use ($dropIndex) {
                $dropIndex($table, 'idx_library_issues_book_status');
                $dropIndex($table, 'idx_library_issues_student');
                $dropIndex($table, 'idx_library_issues_returned_at');
                $dropIndex($table, 'idx_library_issues_issued_due');
            });
        }
    }
};
