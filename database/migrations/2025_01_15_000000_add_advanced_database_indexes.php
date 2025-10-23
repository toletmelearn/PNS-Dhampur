<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAdvancedDatabaseIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Detect database driver to guard MySQL-specific features (e.g., FULLTEXT, partitions)
        $driver = Schema::getConnection()->getDriverName();

        // Helper function to check if index exists (supports MySQL/MariaDB and SQLite)
        $indexExists = function ($table, $indexName) use ($driver) {
            try {
                if (in_array($driver, ['mysql', 'mariadb'])) {
                    $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
                    return !empty($indexes);
                }
                if ($driver === 'sqlite') {
                    $indexes = DB::select(
                        "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name = ? AND name = ?",
                        [$table, $indexName]
                    );
                    return !empty($indexes);
                }
                return false;
            } catch (\Throwable $e) {
                return false;
            }
        };

        // Advanced indexes for users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) use ($indexExists, $driver) {
                // Composite index for role-based queries
                if (Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'status') && !$indexExists('users', 'idx_users_role_status_advanced')) {
                    $table->index(['role', 'status'], 'idx_users_role_status_advanced');
                }
                
                // Index for login tracking
                if (Schema::hasColumn('users', 'last_login_at') && !$indexExists('users', 'idx_users_last_login')) {
                    $table->index('last_login_at', 'idx_users_last_login');
                }
                
                // Full-text search index for name and email
                if (
                    Schema::hasColumn('users', 'name') &&
                    Schema::hasColumn('users', 'email') &&
                    in_array($driver, ['mysql', 'mariadb'])
                ) {
                    DB::statement('ALTER TABLE users ADD FULLTEXT idx_users_search (name, email)');
                }
            });
        }

        // Performance indexes for exam_results table
        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) use ($indexExists) {
                // Composite index for grade calculations
                if (Schema::hasColumn('exam_results', 'student_id') && Schema::hasColumn('exam_results', 'exam_id') && Schema::hasColumn('exam_results', 'subject_id') && !$indexExists('exam_results', 'idx_exam_results_composite')) {
                    $table->index(['student_id', 'exam_id', 'subject_id'], 'idx_exam_results_composite');
                }
                
                // Index for marks-based queries
                if (Schema::hasColumn('exam_results', 'marks_obtained') && !$indexExists('exam_results', 'idx_exam_results_marks')) {
                    $table->index('marks_obtained', 'idx_exam_results_marks');
                }
            });
        }

        // Indexes for fees table
        if (Schema::hasTable('fees')) {
            Schema::table('fees', function (Blueprint $table) use ($indexExists) {
                // Composite index for payment tracking
                if (Schema::hasColumn('fees', 'student_id') && Schema::hasColumn('fees', 'payment_status') && Schema::hasColumn('fees', 'due_date') && !$indexExists('fees', 'idx_fees_payment_tracking')) {
                    $table->index(['student_id', 'payment_status', 'due_date'], 'idx_fees_payment_tracking');
                }
                
                // Index for amount-based queries
                if (Schema::hasColumn('fees', 'amount') && !$indexExists('fees', 'idx_fees_amount')) {
                    $table->index('amount', 'idx_fees_amount');
                }
            });
        }

        // Indexes for library_books table
        if (Schema::hasTable('library_books')) {
            Schema::table('library_books', function (Blueprint $table) use ($indexExists, $driver) {
                // Composite index for availability
                if (Schema::hasColumn('library_books', 'status') && Schema::hasColumn('library_books', 'category') && !$indexExists('library_books', 'idx_library_status_category')) {
                    $table->index(['status', 'category'], 'idx_library_status_category');
                }
                
                // Full-text search for books
                if (
                    Schema::hasColumn('library_books', 'title') &&
                    Schema::hasColumn('library_books', 'author') &&
                    in_array($driver, ['mysql', 'mariadb'])
                ) {
                    DB::statement('ALTER TABLE library_books ADD FULLTEXT idx_library_search (title, author)');
                }
            });
        }

        // Indexes for notifications table
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) use ($indexExists) {
                // Composite index for user notifications
                if (Schema::hasColumn('notifications', 'user_id') && Schema::hasColumn('notifications', 'read_at') && Schema::hasColumn('notifications', 'created_at') && !$indexExists('notifications', 'idx_notifications_user_status')) {
                    $table->index(['user_id', 'read_at', 'created_at'], 'idx_notifications_user_status');
                }
                
                // Index for notification type
                if (Schema::hasColumn('notifications', 'type') && !$indexExists('notifications', 'idx_notifications_type')) {
                    $table->index('type', 'idx_notifications_type');
                }
            });
        }

        // Indexes for system performance tables
        if (Schema::hasTable('performance_metrics')) {
            Schema::table('performance_metrics', function (Blueprint $table) use ($indexExists) {
                // Time-based index for metrics
                if (Schema::hasColumn('performance_metrics', 'created_at') && Schema::hasColumn('performance_metrics', 'metric_type') && !$indexExists('performance_metrics', 'idx_performance_time_type')) {
                    $table->index(['created_at', 'metric_type'], 'idx_performance_time_type');
                }
            });
        }

        if (Schema::hasTable('error_logs')) {
            Schema::table('error_logs', function (Blueprint $table) use ($indexExists) {
                // Index for error analysis
                if (Schema::hasColumn('error_logs', 'level') && Schema::hasColumn('error_logs', 'created_at') && !$indexExists('error_logs', 'idx_error_logs_level_time')) {
                    $table->index(['level', 'created_at'], 'idx_error_logs_level_time');
                }
            });
        }

        // Partitioning for large tables (only for MySQL/MariaDB)
        if (in_array($driver, ['mysql', 'mariadb'])) {
            $this->createPartitions();
        }
    }

    /**
     * Create table partitions for better performance
     */
    private function createPartitions()
    {
        try {
            // Partition attendance table by month
            if (Schema::hasTable('attendances')) {
                DB::statement("
                    ALTER TABLE attendances 
                    PARTITION BY RANGE (YEAR(date) * 100 + MONTH(date)) (
                        PARTITION p202501 VALUES LESS THAN (202502),
                        PARTITION p202502 VALUES LESS THAN (202503),
                        PARTITION p202503 VALUES LESS THAN (202504),
                        PARTITION p202504 VALUES LESS THAN (202505),
                        PARTITION p202505 VALUES LESS THAN (202506),
                        PARTITION p202506 VALUES LESS THAN (202507),
                        PARTITION p202507 VALUES LESS THAN (202508),
                        PARTITION p202508 VALUES LESS THAN (202509),
                        PARTITION p202509 VALUES LESS THAN (202510),
                        PARTITION p202510 VALUES LESS THAN (202511),
                        PARTITION p202511 VALUES LESS THAN (202512),
                        PARTITION p202512 VALUES LESS THAN (202601),
                        PARTITION p_future VALUES LESS THAN MAXVALUE
                    )
                ");
            }
        } catch (Exception $e) {
            // Partitioning might not be supported in all MySQL versions
            // Log the error but don't fail the migration
            \Log::info('Table partitioning not supported: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop advanced indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_role_status_advanced');
                $table->dropIndex('idx_users_last_login');
            });
            
            try {
                DB::statement('ALTER TABLE users DROP INDEX idx_users_search');
            } catch (Exception $e) {
                // Index might not exist
            }
        }

        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) {
                $table->dropIndex('idx_exam_results_composite');
                $table->dropIndex('idx_exam_results_marks');
            });
        }

        if (Schema::hasTable('fees')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->dropIndex('idx_fees_payment_tracking');
                $table->dropIndex('idx_fees_amount');
            });
        }

        if (Schema::hasTable('library_books')) {
            Schema::table('library_books', function (Blueprint $table) {
                $table->dropIndex('idx_library_status_category');
            });
            
            try {
                DB::statement('ALTER TABLE library_books DROP INDEX idx_library_search');
            } catch (Exception $e) {
                // Index might not exist
            }
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notifications_user_status');
                $table->dropIndex('idx_notifications_type');
            });
        }

        if (Schema::hasTable('performance_metrics')) {
            Schema::table('performance_metrics', function (Blueprint $table) {
                $table->dropIndex('idx_performance_time_type');
            });
        }

        if (Schema::hasTable('error_logs')) {
            Schema::table('error_logs', function (Blueprint $table) {
                $table->dropIndex('idx_error_logs_level_time');
            });
        }
    }
}