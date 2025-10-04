<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DepreciationEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_depreciation_id',
        'calculation_date',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'calculation_method',
        'is_manual_entry',
        'adjustment_reason',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'is_manual_entry' => 'boolean'
    ];

    // Relationships
    public function assetDepreciation()
    {
        return $this->belongsTo(AssetDepreciation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeByAsset($query, $assetDepreciationId)
    {
        return $query->where('asset_depreciation_id', $assetDepreciationId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('calculation_date', [$startDate, $endDate]);
    }

    public function scopeManualEntries($query)
    {
        return $query->where('is_manual_entry', true);
    }

    public function scopeAutomaticEntries($query)
    {
        return $query->where('is_manual_entry', false);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('calculation_date', '>=', now()->subDays($days));
    }

    // Accessors
    public function getEntryTypeAttribute()
    {
        return $this->is_manual_entry ? 'Manual' : 'Automatic';
    }

    public function getEntryTypeBadgeAttribute()
    {
        return $this->is_manual_entry ? 'bg-warning' : 'bg-success';
    }

    public function getFormattedCalculationDateAttribute()
    {
        return $this->calculation_date->format('M d, Y');
    }

    // Helper Methods
    public function isAdjustment()
    {
        return !empty($this->adjustment_reason);
    }

    public function getVarianceFromPrevious()
    {
        $previousEntry = self::where('asset_depreciation_id', $this->asset_depreciation_id)
            ->where('calculation_date', '<', $this->calculation_date)
            ->orderBy('calculation_date', 'desc')
            ->first();

        if (!$previousEntry) {
            return null;
        }

        return [
            'amount_variance' => $this->depreciation_amount - $previousEntry->depreciation_amount,
            'percentage_variance' => $previousEntry->depreciation_amount > 0 
                ? (($this->depreciation_amount - $previousEntry->depreciation_amount) / $previousEntry->depreciation_amount) * 100 
                : 0
        ];
    }

    // Static methods
    public static function createManualEntry($assetDepreciationId, $data)
    {
        return self::create([
            'asset_depreciation_id' => $assetDepreciationId,
            'calculation_date' => $data['calculation_date'] ?? now(),
            'depreciation_amount' => $data['depreciation_amount'],
            'accumulated_depreciation' => $data['accumulated_depreciation'],
            'book_value' => $data['book_value'],
            'calculation_method' => $data['calculation_method'] ?? 'manual',
            'is_manual_entry' => true,
            'adjustment_reason' => $data['adjustment_reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id()
        ]);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }
}