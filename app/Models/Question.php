<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_paper_id',
        'question_text',
        'question_type',
        'marks',
        'options',
        'correct_answer',
        'explanation',
        'order_number',
        'image_path',
        'difficulty_level',
        'topic',
        'learning_outcome',
        'bloom_taxonomy_level'
    ];

    protected $casts = [
        'options' => 'array',
        'marks' => 'integer',
        'order_number' => 'integer'
    ];

    // Relationships
    public function examPaper()
    {
        return $this->belongsTo(ExamPaper::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('question_type', $type);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function scopeByMarks($query, $marks)
    {
        return $query->where('marks', $marks);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    // Accessors
    public function getQuestionTypeBadgeAttribute()
    {
        $badges = [
            'mcq' => '<span class="badge badge-primary">MCQ</span>',
            'short_answer' => '<span class="badge badge-info">Short Answer</span>',
            'long_answer' => '<span class="badge badge-success">Long Answer</span>',
            'true_false' => '<span class="badge badge-warning">True/False</span>',
            'fill_blank' => '<span class="badge badge-secondary">Fill in Blank</span>'
        ];

        return $badges[$this->question_type] ?? '<span class="badge badge-light">Unknown</span>';
    }

    public function getDifficultyBadgeAttribute()
    {
        $badges = [
            'easy' => '<span class="badge badge-success">Easy</span>',
            'medium' => '<span class="badge badge-warning">Medium</span>',
            'hard' => '<span class="badge badge-danger">Hard</span>'
        ];

        return $badges[$this->difficulty_level] ?? '<span class="badge badge-secondary">Not Set</span>';
    }

    public function getBloomTaxonomyBadgeAttribute()
    {
        $badges = [
            'remember' => '<span class="badge badge-light">Remember</span>',
            'understand' => '<span class="badge badge-info">Understand</span>',
            'apply' => '<span class="badge badge-primary">Apply</span>',
            'analyze' => '<span class="badge badge-warning">Analyze</span>',
            'evaluate' => '<span class="badge badge-danger">Evaluate</span>',
            'create' => '<span class="badge badge-success">Create</span>'
        ];

        return $badges[$this->bloom_taxonomy_level] ?? '<span class="badge badge-secondary">Not Set</span>';
    }

    public function getFormattedOptionsAttribute()
    {
        if (!$this->options || !is_array($this->options)) {
            return [];
        }

        $formatted = [];
        $labels = ['A', 'B', 'C', 'D', 'E', 'F'];
        
        foreach ($this->options as $index => $option) {
            $formatted[] = [
                'label' => $labels[$index] ?? ($index + 1),
                'text' => $option,
                'is_correct' => $this->correct_answer === $option || $this->correct_answer === $labels[$index]
            ];
        }

        return $formatted;
    }

    public function getShortQuestionTextAttribute()
    {
        return strlen($this->question_text) > 100 
            ? substr($this->question_text, 0, 100) . '...' 
            : $this->question_text;
    }

    public function getHasImageAttribute()
    {
        return !empty($this->image_path) && file_exists(storage_path('app/public/' . $this->image_path));
    }

    public function getImageUrlAttribute()
    {
        return $this->has_image 
            ? asset('storage/' . $this->image_path) 
            : null;
    }

    // Methods
    public function isObjectiveType()
    {
        return in_array($this->question_type, ['mcq', 'true_false', 'fill_blank']);
    }

    public function isSubjectiveType()
    {
        return in_array($this->question_type, ['short_answer', 'long_answer']);
    }

    public function requiresOptions()
    {
        return in_array($this->question_type, ['mcq']);
    }

    public function requiresCorrectAnswer()
    {
        return in_array($this->question_type, ['mcq', 'true_false', 'fill_blank']);
    }

    public function validateQuestion()
    {
        $errors = [];

        // Basic validation
        if (empty($this->question_text)) {
            $errors[] = 'Question text is required.';
        }

        if ($this->marks <= 0) {
            $errors[] = 'Question marks must be greater than 0.';
        }

        // Type-specific validation
        switch ($this->question_type) {
            case 'mcq':
                if (empty($this->options) || count($this->options) < 2) {
                    $errors[] = 'MCQ questions must have at least 2 options.';
                }
                if (empty($this->correct_answer)) {
                    $errors[] = 'MCQ questions must have a correct answer.';
                }
                break;

            case 'true_false':
                if (empty($this->correct_answer) || !in_array(strtolower($this->correct_answer), ['true', 'false', '1', '0'])) {
                    $errors[] = 'True/False questions must have a correct answer (true or false).';
                }
                break;

            case 'fill_blank':
                if (empty($this->correct_answer)) {
                    $errors[] = 'Fill in the blank questions must have a correct answer.';
                }
                if (strpos($this->question_text, '_____') === false && strpos($this->question_text, '______') === false) {
                    $errors[] = 'Fill in the blank questions should contain blank spaces (_____ or ______).';
                }
                break;
        }

        return $errors;
    }

    public function duplicate($newExamPaperId = null)
    {
        $newQuestion = $this->replicate();
        
        if ($newExamPaperId) {
            $newQuestion->exam_paper_id = $newExamPaperId;
        }
        
        // Reset order number if duplicating to different paper
        if ($newExamPaperId && $newExamPaperId !== $this->exam_paper_id) {
            $maxOrder = Question::where('exam_paper_id', $newExamPaperId)->max('order_number') ?? 0;
            $newQuestion->order_number = $maxOrder + 1;
        }
        
        $newQuestion->save();
        
        return $newQuestion;
    }

    public function moveUp()
    {
        $previousQuestion = Question::where('exam_paper_id', $this->exam_paper_id)
            ->where('order_number', '<', $this->order_number)
            ->orderBy('order_number', 'desc')
            ->first();

        if ($previousQuestion) {
            $tempOrder = $this->order_number;
            $this->order_number = $previousQuestion->order_number;
            $previousQuestion->order_number = $tempOrder;
            
            $this->save();
            $previousQuestion->save();
        }
    }

    public function moveDown()
    {
        $nextQuestion = Question::where('exam_paper_id', $this->exam_paper_id)
            ->where('order_number', '>', $this->order_number)
            ->orderBy('order_number', 'asc')
            ->first();

        if ($nextQuestion) {
            $tempOrder = $this->order_number;
            $this->order_number = $nextQuestion->order_number;
            $nextQuestion->order_number = $tempOrder;
            
            $this->save();
            $nextQuestion->save();
        }
    }

    public function getEstimatedTimeMinutes()
    {
        $baseTime = [
            'mcq' => 1.5,
            'true_false' => 1,
            'fill_blank' => 2,
            'short_answer' => 3,
            'long_answer' => 5
        ];

        $timePerMark = $baseTime[$this->question_type] ?? 2;
        
        return round($timePerMark * $this->marks, 1);
    }

    public function getSimilarQuestions($limit = 5)
    {
        return Question::where('id', '!=', $this->id)
            ->where('question_type', $this->question_type)
            ->whereHas('examPaper', function($query) {
                $query->where('subject_id', $this->examPaper->subject_id)
                      ->where('class_id', $this->examPaper->class_id);
            })
            ->where('marks', $this->marks)
            ->limit($limit)
            ->get();
    }

    public function getAnswerKey()
    {
        switch ($this->question_type) {
            case 'mcq':
                $options = $this->formatted_options;
                $correctOption = collect($options)->firstWhere('is_correct');
                return $correctOption ? $correctOption['label'] . '. ' . $correctOption['text'] : $this->correct_answer;

            case 'true_false':
                return ucfirst($this->correct_answer);

            case 'fill_blank':
            case 'short_answer':
            case 'long_answer':
                return $this->correct_answer;

            default:
                return $this->correct_answer;
        }
    }

    // Static methods
    public static function getQuestionTypes()
    {
        return [
            'mcq' => 'Multiple Choice Question',
            'short_answer' => 'Short Answer',
            'long_answer' => 'Long Answer',
            'true_false' => 'True/False',
            'fill_blank' => 'Fill in the Blank'
        ];
    }

    public static function getDifficultyLevels()
    {
        return [
            'easy' => 'Easy',
            'medium' => 'Medium',
            'hard' => 'Hard'
        ];
    }

    public static function getBloomTaxonomyLevels()
    {
        return [
            'remember' => 'Remember',
            'understand' => 'Understand',
            'apply' => 'Apply',
            'analyze' => 'Analyze',
            'evaluate' => 'Evaluate',
            'create' => 'Create'
        ];
    }
}