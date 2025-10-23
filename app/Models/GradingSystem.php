<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'format',
        'rules',
        'pass_mark',
        'max_mark',
        'is_default',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_default' => 'boolean',
    ];

    public function templates()
    {
        return $this->hasMany(ResultTemplate::class);
    }

    /**
     * Calculate grade and grade point based on rules.
     */
    public function resolveGrade(float $marksObtained, float $totalMarks = null): array
    {
        $effectiveTotal = $totalMarks ?: ($this->max_mark ?: 100);
        $percentage = $effectiveTotal > 0 ? ($marksObtained / $effectiveTotal) * 100.0 : 0.0;

        $selected = null;
        foreach ($this->rules ?? [] as $rule) {
            $min = $rule['min'] ?? null; $max = $rule['max'] ?? null;
            if ($min === null || $max === null) { continue; }
            if ($percentage >= $min && $percentage <= $max) { $selected = $rule; break; }
        }

        return [
            'grade' => $selected['grade'] ?? null,
            'point' => isset($selected['point']) ? (float)$selected['point'] : null,
            'percentage' => round($percentage, 2),
            'is_pass' => $this->pass_mark === null ? null : ($marksObtained >= $this->pass_mark)
        ];
    }
}