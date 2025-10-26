<?php

$migrations = [
    'teacher_documents' => [
        'teacher_id' => 'unsignedBigInteger',
        'document_type' => 'string',
        'file_path' => 'string',
        'upload_date' => 'date',
        'verified' => 'boolean',
        'remarks' => 'text|nullable',
        'foreign_keys' => [
            ['teacher_id', 'teachers', 'id']
        ]
    ],
    'student_verifications' => [
        'student_id' => 'unsignedBigInteger',
        'document_type' => 'string',
        'verification_status' => 'enum:pending,verified,rejected',
        'verified_by' => 'unsignedBigInteger|nullable',
        'verification_date' => 'date|nullable',
        'remarks' => 'text|nullable',
        'foreign_keys' => [
            ['student_id', 'students', 'id'],
            ['verified_by', 'users', 'id']
        ]
    ],
    'bell_schedules' => [
        'day_of_week' => 'enum:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        'period_number' => 'integer',
        'start_time' => 'time',
        'end_time' => 'time',
        'is_break' => 'boolean',
        'description' => 'string|nullable',
        'is_active' => 'boolean'
    ],
    'teacher_salaries' => [
        'teacher_id' => 'unsignedBigInteger',
        'month' => 'string',
        'year' => 'year',
        'basic_salary' => 'decimal:10,2',
        'allowances' => 'decimal:10,2',
        'deductions' => 'decimal:10,2',
        'net_salary' => 'decimal:10,2',
        'payment_date' => 'date|nullable',
        'payment_method' => 'string|nullable',
        'transaction_id' => 'string|nullable',
        'status' => 'enum:pending,paid,cancelled',
        'remarks' => 'text|nullable',
        'foreign_keys' => [
            ['teacher_id', 'teachers', 'id']
        ]
    ],
    'student_attendances' => [
        'student_id' => 'unsignedBigInteger',
        'class_id' => 'string',
        'section_id' => 'string',
        'date' => 'date',
        'status' => 'enum:present,absent,late,half_day,leave',
        'remarks' => 'text|nullable',
        'marked_by' => 'unsignedBigInteger',
        'foreign_keys' => [
            ['student_id', 'students', 'id'],
            ['class_id', 'classes', 'id'],
            ['section_id', 'sections', 'id'],
            ['marked_by', 'users', 'id']
        ]
    ],
    'results' => [
        'student_id' => 'unsignedBigInteger',
        'exam_id' => 'unsignedBigInteger',
        'subject_id' => 'string',
        'marks_obtained' => 'decimal:5,2',
        'max_marks' => 'decimal:5,2',
        'grade' => 'string|nullable',
        'remarks' => 'text|nullable',
        'foreign_keys' => [
            ['student_id', 'students', 'id'],
            ['exam_id', 'exams', 'id'],
            ['subject_id', 'subjects', 'id']
        ]
    ],
    'admit_cards' => [
        'student_id' => 'unsignedBigInteger',
        'exam_id' => 'unsignedBigInteger',
        'issue_date' => 'date',
        'roll_number' => 'string',
        'is_active' => 'boolean',
        'remarks' => 'text|nullable',
        'foreign_keys' => [
            ['student_id', 'students', 'id'],
            ['exam_id', 'exams', 'id']
        ]
    ],
    'inventory_items' => [
        'name' => 'string',
        'category' => 'string',
        'quantity' => 'integer',
        'unit' => 'string',
        'purchase_date' => 'date|nullable',
        'purchase_price' => 'decimal:10,2|nullable',
        'supplier' => 'string|nullable',
        'location' => 'string|nullable',
        'condition' => 'enum:new,good,fair,poor',
        'last_maintenance' => 'date|nullable',
        'next_maintenance' => 'date|nullable',
        'remarks' => 'text|nullable'
    ],
    'budgets' => [
        'title' => 'string',
        'fiscal_year' => 'string',
        'category' => 'string',
        'amount_allocated' => 'decimal:12,2',
        'amount_spent' => 'decimal:12,2',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'enum:draft,approved,closed',
        'approved_by' => 'unsignedBigInteger|nullable',
        'remarks' => 'text|nullable',
        'foreign_keys' => [
            ['approved_by', 'users', 'id']
        ]
    ],
    'exam_papers' => [
        'exam_id' => 'unsignedBigInteger',
        'subject_id' => 'string',
        'title' => 'string',
        'duration_minutes' => 'integer',
        'max_marks' => 'decimal:5,2',
        'passing_marks' => 'decimal:5,2',
        'created_by' => 'unsignedBigInteger',
        'approved_by' => 'unsignedBigInteger|nullable',
        'status' => 'enum:draft,approved,published',
        'foreign_keys' => [
            ['exam_id', 'exams', 'id'],
            ['subject_id', 'subjects', 'id'],
            ['created_by', 'users', 'id'],
            ['approved_by', 'users', 'id']
        ]
    ],
    'daily_syllabus' => [
        'class_id' => 'string',
        'section_id' => 'string',
        'subject_id' => 'string',
        'date' => 'date',
        'topic' => 'string',
        'description' => 'text|nullable',
        'resources' => 'text|nullable',
        'homework' => 'text|nullable',
        'teacher_id' => 'unsignedBigInteger',
        'status' => 'enum:planned,completed,rescheduled',
        'foreign_keys' => [
            ['class_id', 'classes', 'id'],
            ['section_id', 'sections', 'id'],
            ['subject_id', 'subjects', 'id'],
            ['teacher_id', 'teachers', 'id']
        ]
    ],
    'sr_registers' => [
        'student_id' => 'unsignedBigInteger',
        'sr_number' => 'string|unique',
        'admission_date' => 'date',
        'leaving_date' => 'date|nullable',
        'leaving_reason' => 'string|nullable',
        'conduct' => 'string|nullable',
        'academic_performance' => 'string|nullable',
        'remarks' => 'text|nullable',
        'foreign_keys' => [
            ['student_id', 'students', 'id']
        ]
    ],
    'alumni' => [
        'student_id' => 'unsignedBigInteger',
        'graduation_year' => 'year',
        'current_occupation' => 'string|nullable',
        'current_organization' => 'string|nullable',
        'higher_education' => 'string|nullable',
        'achievements' => 'text|nullable',
        'contact_email' => 'string|nullable',
        'contact_phone' => 'string|nullable',
        'is_active' => 'boolean',
        'foreign_keys' => [
            ['student_id', 'students', 'id']
        ]
    ]
];

foreach ($migrations as $table => $columns) {
    $migrationName = "create_{$table}_table";
    $command = "php artisan make:migration {$migrationName}";
    echo shell_exec($command) . PHP_EOL;
    
    // Get the latest migration file
    $migrationFiles = glob(__DIR__ . '/database/migrations/*_' . $migrationName . '.php');
    $latestMigration = end($migrationFiles);
    
    if ($latestMigration) {
        $content = file_get_contents($latestMigration);
        
        // Build the schema
        $schemaContent = "        if (!Schema::hasTable('{$table}')) {" . PHP_EOL;
        $schemaContent .= "            Schema::create('{$table}', function (Blueprint \$table) {" . PHP_EOL;
        $schemaContent .= "                \$table->id();" . PHP_EOL;
        
        foreach ($columns as $column => $type) {
            if ($column === 'foreign_keys') continue;
            
            $parts = explode('|', $type);
            $columnType = $parts[0];
            $nullable = in_array('nullable', $parts);
            $unique = in_array('unique', $parts);
            
            if (strpos($columnType, ':') !== false) {
                list($method, $params) = explode(':', $columnType, 2);
                $params = explode(',', $params);
                
                $paramsStr = implode(', ', array_map(function($param) {
                    return is_numeric($param) ? $param : "'{$param}'";
                }, $params));
                
                $schemaContent .= "                \$table->{$method}('{$column}', {$paramsStr})";
            } else {
                $schemaContent .= "                \$table->{$columnType}('{$column}')";
            }
            
            if ($nullable) {
                $schemaContent .= "->nullable()";
            }
            
            if ($unique) {
                $schemaContent .= "->unique()";
            }
            
            $schemaContent .= ";" . PHP_EOL;
        }
        
        $schemaContent .= "                \$table->timestamps();" . PHP_EOL;
        
        // Add foreign keys
        if (isset($columns['foreign_keys'])) {
            $schemaContent .= PHP_EOL;
            foreach ($columns['foreign_keys'] as $foreignKey) {
                list($column, $referenceTable, $referenceColumn) = $foreignKey;
                $schemaContent .= "                \$table->foreign('{$column}')->references('{$referenceColumn}')->on('{$referenceTable}')" . PHP_EOL;
                $schemaContent .= "                    ->onDelete('cascade')->onUpdate('cascade');" . PHP_EOL;
            }
        }
        
        $schemaContent .= "            });" . PHP_EOL;
        $schemaContent .= "        }";
        
        // Replace the schema in the migration file
        $pattern = '/Schema::create\([^;]+\);/s';
        $newContent = preg_replace($pattern, $schemaContent, $content);
        
        file_put_contents($latestMigration, $newContent);
        echo "Updated migration: " . basename($latestMigration) . PHP_EOL;
    }
}

echo "All migrations created and updated successfully!";