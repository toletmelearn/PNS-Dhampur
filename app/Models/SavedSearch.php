<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavedSearch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'search_type',
        'search_criteria',
        'is_default',
        'is_public',
        'usage_count',
        'last_used_at'
    ];

    protected $casts = [
        'search_criteria' => 'array',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
        'last_used_at' => 'datetime'
    ];

    protected $dates = [
        'last_used_at',
        'deleted_at'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('search_type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('last_used_at', 'desc')->limit($limit);
    }

    /**
     * Methods
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function setAsDefault()
    {
        // Remove default from other searches of the same type for this user
        static::where('user_id', $this->user_id)
              ->where('search_type', $this->search_type)
              ->where('id', '!=', $this->id)
              ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function getSearchCriteriaAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setSearchCriteriaAttribute($value)
    {
        $this->attributes['search_criteria'] = json_encode($value);
    }

    /**
     * Get formatted search criteria for display
     */
    public function getFormattedCriteria()
    {
        $criteria = $this->search_criteria;
        $formatted = [];

        foreach ($criteria as $key => $value) {
            if (empty($value)) continue;

            switch ($key) {
                case 'search':
                    $formatted[] = "Text: {$value}";
                    break;
                case 'class_id':
                    $class = ClassModel::find($value);
                    $formatted[] = "Class: " . ($class ? $class->name . ' - ' . $class->section : $value);
                    break;
                case 'status':
                    $formatted[] = "Status: " . ucfirst($value);
                    break;
                case 'gender':
                    $formatted[] = "Gender: " . ucfirst($value);
                    break;
                case 'age_min':
                    $formatted[] = "Min Age: {$value}";
                    break;
                case 'age_max':
                    $formatted[] = "Max Age: {$value}";
                    break;
                case 'dob_from':
                    $formatted[] = "DOB From: {$value}";
                    break;
                case 'dob_to':
                    $formatted[] = "DOB To: {$value}";
                    break;
                case 'verification_status':
                    $formatted[] = "Verification: " . ucfirst($value);
                    break;
                case 'verified':
                    $formatted[] = "Verified: " . ($value ? 'Yes' : 'No');
                    break;
                case 'has_aadhaar':
                    $formatted[] = "Has Aadhaar: " . ($value ? 'Yes' : 'No');
                    break;
                case 'has_documents':
                    $formatted[] = "Has Documents: " . ($value ? 'Yes' : 'No');
                    break;
                case 'father_name':
                    $formatted[] = "Father: {$value}";
                    break;
                case 'mother_name':
                    $formatted[] = "Mother: {$value}";
                    break;
                case 'contact_number':
                    $formatted[] = "Contact: {$value}";
                    break;
                case 'email':
                    $formatted[] = "Email: {$value}";
                    break;
                case 'address':
                    $formatted[] = "Address: {$value}";
                    break;
                case 'academic_year':
                    $formatted[] = "Academic Year: {$value}";
                    break;
                case 'class_ids':
                    if (is_array($value)) {
                        $classes = ClassModel::whereIn('id', $value)->get();
                        $classNames = $classes->map(function($class) {
                            return $class->name . ' - ' . $class->section;
                        })->implode(', ');
                        $formatted[] = "Classes: {$classNames}";
                    }
                    break;
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Create a saved search from request parameters
     */
    public static function createFromRequest($request, $userId, $name, $description = null)
    {
        $searchCriteria = $request->only([
            'search', 'class_id', 'status', 'gender', 'age_min', 'age_max',
            'dob_from', 'dob_to', 'admission_from', 'admission_to',
            'verification_status', 'verified', 'has_aadhaar', 'has_documents',
            'father_name', 'mother_name', 'contact_number', 'email', 'address',
            'class_ids', 'academic_year', 'sort_by', 'sort_order'
        ]);

        // Remove empty values
        $searchCriteria = array_filter($searchCriteria, function($value) {
            return !empty($value) || $value === false || $value === 0;
        });

        return static::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'search_type' => 'student',
            'search_criteria' => $searchCriteria,
            'usage_count' => 1,
            'last_used_at' => now()
        ]);
    }

    /**
     * Apply saved search criteria to a request
     */
    public function applyToRequest($request)
    {
        foreach ($this->search_criteria as $key => $value) {
            $request->merge([$key => $value]);
        }

        $this->incrementUsage();
        
        return $request;
    }

    /**
     * Get popular searches for a specific type
     */
    public static function getPopularSearches($type = 'student', $limit = 5)
    {
        return static::byType($type)
                    ->public()
                    ->popular($limit)
                    ->get();
    }

    /**
     * Get user's recent searches
     */
    public static function getUserRecentSearches($userId, $type = 'student', $limit = 5)
    {
        return static::forUser($userId)
                    ->byType($type)
                    ->recent($limit)
                    ->get();
    }

    /**
     * Get user's default search
     */
    public static function getUserDefaultSearch($userId, $type = 'student')
    {
        return static::forUser($userId)
                    ->byType($type)
                    ->default()
                    ->first();
    }
}