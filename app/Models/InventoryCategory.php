<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'code',
        'parent_id',
        'level',
        'is_active',
        'icon',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
        'sort_order' => 'integer'
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(InventoryCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(InventoryCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMainCategories($query)
    {
        return $query->where('level', 1)->whereNull('parent_id');
    }

    public function scopeSubCategories($query)
    {
        return $query->where('level', '>', 1)->whereNotNull('parent_id');
    }

    // Accessors & Mutators
    public function getFullNameAttribute()
    {
        $names = collect([$this->name]);
        $parent = $this->parent;
        
        while ($parent) {
            $names->prepend($parent->name);
            $parent = $parent->parent;
        }
        
        return $names->implode(' > ');
    }

    // Helper Methods
    public function getAllChildren()
    {
        $children = collect();
        
        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildren());
        }
        
        return $children;
    }

    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    public function getItemsCount()
    {
        return $this->items()->count();
    }

    public function getTotalItemsCount()
    {
        $count = $this->getItemsCount();
        
        foreach ($this->getAllChildren() as $child) {
            $count += $child->getItemsCount();
        }
        
        return $count;
    }

    // Boot method for auto-generating code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->code)) {
                $category->code = static::generateCode($category->name);
            }
        });
    }

    private static function generateCode($name)
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));
        $counter = 1;
        $originalCode = $code;

        while (static::where('code', $code)->exists()) {
            $code = $originalCode . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $code;
    }
}
