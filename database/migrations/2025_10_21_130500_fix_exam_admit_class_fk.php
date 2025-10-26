<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $schema = Schema::getConnection()->getDoctrineSchemaManager();
        $details = $schema->listTableDetails($table);
        return $details->hasForeignKey($fkName);
    }

    public function up(): void
    {
        // Fix exam_seat_allocations.class_id to reference class_models
        if (Schema::hasTable('exam_seat_allocations')) {
            if ($this->foreignKeyExists('exam_seat_allocations', 'exam_seat_allocations_class_id_foreign')) {
                Schema::table('exam_seat_allocations', function (Blueprint $table) {
                    $table->dropForeign(['class_id']);
                });
            }
            Schema::table('exam_seat_allocations', function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
            });
        }

        // Fix admit_cards.class_id to reference class_models
        if (Schema::hasTable('admit_cards')) {
            if (Schema::hasColumn('admit_cards', 'class_id')) {
                if ($this->foreignKeyExists('admit_cards', 'admit_cards_class_id_foreign')) {
                    Schema::table('admit_cards', function (Blueprint $table) {
                        $table->dropForeign(['class_id']);
                    });
                }
                Schema::table('admit_cards', function (Blueprint $table) {
                    $table->foreign('class_id')->references('id')->on('class_models')->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        // Revert exam_seat_allocations.class_id to reference classes
        if (Schema::hasTable('exam_seat_allocations') && Schema::hasColumn('exam_seat_allocations', 'class_id')) {
            if ($this->foreignKeyExists('exam_seat_allocations', 'exam_seat_allocations_class_id_foreign')) {
                Schema::table('exam_seat_allocations', function (Blueprint $table) {
                    $table->dropForeign(['class_id']);
                });
            }
            Schema::table('exam_seat_allocations', function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            });
        }

        // Revert admit_cards.class_id to reference classes
        if (Schema::hasTable('admit_cards') && Schema::hasColumn('admit_cards', 'class_id')) {
            if ($this->foreignKeyExists('admit_cards', 'admit_cards_class_id_foreign')) {
                Schema::table('admit_cards', function (Blueprint $table) {
                    $table->dropForeign(['class_id']);
                });
            }
            Schema::table('admit_cards', function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            });
        }
    }
};