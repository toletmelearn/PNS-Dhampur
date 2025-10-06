<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'category',
        'subject',
        'body',
        'variables',
        'settings',
        'is_active',
        'is_system',
        'description'
    ];

    protected $casts = [
        'variables' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean'
    ];

    /**
     * Template types
     */
    const TYPES = [
        'email' => 'Email',
        'sms' => 'SMS',
        'push' => 'Push Notification',
        'system' => 'System Notification'
    ];

    /**
     * Template categories
     */
    const CATEGORIES = [
        'student' => 'Student',
        'teacher' => 'Teacher',
        'parent' => 'Parent',
        'admin' => 'Admin',
        'system' => 'System'
    ];

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for templates by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for non-system templates (can be edited/deleted)
     */
    public function scopeEditable($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Generate slug from name
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        
        if (!$this->slug) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * Get template by slug
     */
    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Render template with variables
     */
    public function render($variables = [])
    {
        $subject = $this->subject;
        $body = $this->body;
        
        // Replace variables in subject and body
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }
        
        return [
            'subject' => $subject,
            'body' => $body
        ];
    }

    /**
     * Get available variables for this template
     */
    public function getAvailableVariables()
    {
        return $this->variables ?? [];
    }

    /**
     * Extract variables from template content
     */
    public function extractVariables()
    {
        $content = $this->subject . ' ' . $this->body;
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        
        return array_unique($matches[1]);
    }

    /**
     * Validate template variables
     */
    public function validateVariables($variables = [])
    {
        $requiredVariables = $this->extractVariables();
        $missingVariables = [];
        
        foreach ($requiredVariables as $variable) {
            if (!isset($variables[$variable])) {
                $missingVariables[] = $variable;
            }
        }
        
        return [
            'valid' => empty($missingVariables),
            'missing' => $missingVariables,
            'required' => $requiredVariables
        ];
    }

    /**
     * Get type label
     */
    public function getTypeLabel()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get category label
     */
    public function getCategoryLabel()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Create default system templates
     */
    public static function createSystemTemplates()
    {
        $templates = [
            [
                'name' => 'Student Registration Confirmation',
                'slug' => 'student-registration-confirmation',
                'type' => 'email',
                'category' => 'student',
                'subject' => 'Welcome to {{school_name}} - Registration Confirmed',
                'body' => 'Dear {{student_name}},\n\nWelcome to {{school_name}}! Your registration has been confirmed.\n\nStudent ID: {{student_id}}\nClass: {{class_name}}\nAdmission Date: {{admission_date}}\n\nPlease contact the school office if you have any questions.\n\nBest regards,\n{{school_name}} Administration',
                'variables' => ['school_name', 'student_name', 'student_id', 'class_name', 'admission_date'],
                'is_system' => true,
                'description' => 'Sent when a new student is registered'
            ],
            [
                'name' => 'Fee Payment Reminder',
                'slug' => 'fee-payment-reminder',
                'type' => 'email',
                'category' => 'parent',
                'subject' => 'Fee Payment Reminder - {{student_name}}',
                'body' => 'Dear Parent/Guardian,\n\nThis is a reminder that the fee payment for {{student_name}} ({{student_id}}) is due.\n\nAmount Due: {{amount_due}}\nDue Date: {{due_date}}\n\nPlease make the payment at your earliest convenience.\n\nThank you,\n{{school_name}}',
                'variables' => ['student_name', 'student_id', 'amount_due', 'due_date', 'school_name'],
                'is_system' => true,
                'description' => 'Sent to remind parents about pending fee payments'
            ],
            [
                'name' => 'Attendance Alert',
                'slug' => 'attendance-alert',
                'type' => 'sms',
                'category' => 'parent',
                'subject' => null,
                'body' => 'Alert: {{student_name}} was marked absent today ({{date}}). Please contact school if this is incorrect. - {{school_name}}',
                'variables' => ['student_name', 'date', 'school_name'],
                'is_system' => true,
                'description' => 'SMS sent to parents when student is marked absent'
            ],
            [
                'name' => 'Exam Results Published',
                'slug' => 'exam-results-published',
                'type' => 'email',
                'category' => 'student',
                'subject' => 'Exam Results Published - {{exam_name}}',
                'body' => 'Dear {{student_name}},\n\nYour results for {{exam_name}} have been published.\n\nTotal Marks: {{total_marks}}\nPercentage: {{percentage}}%\nGrade: {{grade}}\n\nYou can view detailed results by logging into the student portal.\n\nCongratulations!\n{{school_name}}',
                'variables' => ['student_name', 'exam_name', 'total_marks', 'percentage', 'grade', 'school_name'],
                'is_system' => true,
                'description' => 'Sent when exam results are published'
            ]
        ];

        foreach ($templates as $template) {
            static::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (!$template->slug) {
                $template->slug = Str::slug($template->name);
            }
        });
    }
}