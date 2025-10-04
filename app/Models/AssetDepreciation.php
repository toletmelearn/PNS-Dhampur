<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AssetDepreciation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'depreciation_method',
        'useful_life_years',
        'useful_life_months',
        'salvage_value',
        'purchase_price',
        'purchase_date',
        'depreciation_start_date',
        'annual_depreciation_rate',
        'monthly_depreciation_amount',
        'accumulated_depreciation',
        'current_book_value',
        'depreciation_schedule',
        'last_calculation_date',
        'next_calculation_date',
        'is_fully_depreciated',
        'depreciation_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'annual_depreciation_rate' => 'decimal:4',
        'monthly_depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'current_book_value' => 'decimal:2',
        'purchase_date' => 'date',
        'depreciation_start_date' => 'date',
        'last_calculation_date' => 'date',
        'next_calculation_date' => 'date',
        'depreciation_schedule' => 'array',
        'is_fully_depreciated' => 'boolean',
        'useful_life_years' => 'integer',
        'useful_life_months' => 'integer'
    ];

    // Depreciation method constants
    const METHOD_STRAIGHT_LINE = 'straight_line';
    const METHOD_DECLINING_BALANCE = 'declining_balance';
    const METHOD_DOUBLE_DECLINING = 'double_declining_balance';
    const METHOD_SUM_OF_YEARS = 'sum_of_years_digits';
    const METHOD_UNITS_OF_PRODUCTION = 'units_of_production';

    // Relationships
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function depreciationEntries()
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_fully_depreciated', false);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('depreciation_method', $method);
    }

    public function scopeByAsset($query, $assetId)
    {
        return $query->where('inventory_item_id', $assetId);
    }

    public function scopeDueForCalculation($query)
    {
        return $query->where('next_calculation_date', '<=', now())
                    ->where('is_fully_depreciated', false);
    }

    public function scopeFullyDepreciated($query)
    {
        return $query->where('is_fully_depreciated', true);
    }

    // Accessors
    public function getDepreciationMethodDisplayAttribute()
    {
        return match($this->depreciation_method) {
            self::METHOD_STRAIGHT_LINE => 'Straight Line',
            self::METHOD_DECLINING_BALANCE => 'Declining Balance',
            self::METHOD_DOUBLE_DECLINING => 'Double Declining Balance',
            self::METHOD_SUM_OF_YEARS => 'Sum of Years Digits',
            self::METHOD_UNITS_OF_PRODUCTION => 'Units of Production',
            default => 'Unknown Method'
        };
    }

    public function getDepreciationPercentageAttribute()
    {
        if ($this->purchase_price <= 0) return 0;
        return round(($this->accumulated_depreciation / $this->purchase_price) * 100, 2);
    }

    public function getRemainingValueAttribute()
    {
        return max(0, $this->current_book_value);
    }

    public function getRemainingLifeMonthsAttribute()
    {
        if ($this->is_fully_depreciated) return 0;
        
        $totalLifeMonths = ($this->useful_life_years * 12) + $this->useful_life_months;
        $elapsedMonths = $this->depreciation_start_date->diffInMonths(now());
        
        return max(0, $totalLifeMonths - $elapsedMonths);
    }

    public function getDepreciationStatusAttribute()
    {
        if ($this->is_fully_depreciated) {
            return 'Fully Depreciated';
        }
        
        $percentage = $this->depreciation_percentage;
        
        if ($percentage >= 90) return 'Nearly Depreciated';
        if ($percentage >= 75) return 'Highly Depreciated';
        if ($percentage >= 50) return 'Moderately Depreciated';
        if ($percentage >= 25) return 'Lightly Depreciated';
        
        return 'New Asset';
    }

    public function getDepreciationStatusBadgeAttribute()
    {
        return match($this->depreciation_status) {
            'Fully Depreciated' => 'bg-dark',
            'Nearly Depreciated' => 'bg-danger',
            'Highly Depreciated' => 'bg-warning',
            'Moderately Depreciated' => 'bg-info',
            'Lightly Depreciated' => 'bg-primary',
            'New Asset' => 'bg-success',
            default => 'bg-secondary'
        };
    }

    // Helper Methods
    public function calculateDepreciation($asOfDate = null)
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : now();
        
        return match($this->depreciation_method) {
            self::METHOD_STRAIGHT_LINE => $this->calculateStraightLineDepreciation($asOfDate),
            self::METHOD_DECLINING_BALANCE => $this->calculateDecliningBalanceDepreciation($asOfDate),
            self::METHOD_DOUBLE_DECLINING => $this->calculateDoubleDecliningDepreciation($asOfDate),
            self::METHOD_SUM_OF_YEARS => $this->calculateSumOfYearsDepreciation($asOfDate),
            default => $this->calculateStraightLineDepreciation($asOfDate)
        };
    }

    protected function calculateStraightLineDepreciation($asOfDate)
    {
        $depreciableAmount = $this->purchase_price - $this->salvage_value;
        $totalLifeMonths = ($this->useful_life_years * 12) + $this->useful_life_months;
        
        if ($totalLifeMonths <= 0) return 0;
        
        $monthlyDepreciation = $depreciableAmount / $totalLifeMonths;
        $elapsedMonths = $this->depreciation_start_date->diffInMonths($asOfDate);
        
        $totalDepreciation = min($monthlyDepreciation * $elapsedMonths, $depreciableAmount);
        
        return [
            'monthly_depreciation' => round($monthlyDepreciation, 2),
            'accumulated_depreciation' => round($totalDepreciation, 2),
            'current_book_value' => round($this->purchase_price - $totalDepreciation, 2),
            'remaining_life_months' => max(0, $totalLifeMonths - $elapsedMonths)
        ];
    }

    protected function calculateDecliningBalanceDepreciation($asOfDate)
    {
        $rate = $this->annual_depreciation_rate / 100;
        $monthlyRate = $rate / 12;
        $elapsedMonths = $this->depreciation_start_date->diffInMonths($asOfDate);
        
        $currentValue = $this->purchase_price;
        $totalDepreciation = 0;
        
        for ($month = 1; $month <= $elapsedMonths; $month++) {
            $monthlyDepreciation = $currentValue * $monthlyRate;
            
            // Don't depreciate below salvage value
            if ($currentValue - $monthlyDepreciation < $this->salvage_value) {
                $monthlyDepreciation = max(0, $currentValue - $this->salvage_value);
            }
            
            $totalDepreciation += $monthlyDepreciation;
            $currentValue -= $monthlyDepreciation;
            
            if ($currentValue <= $this->salvage_value) break;
        }
        
        return [
            'monthly_depreciation' => round($currentValue * $monthlyRate, 2),
            'accumulated_depreciation' => round($totalDepreciation, 2),
            'current_book_value' => round($currentValue, 2),
            'remaining_life_months' => $this->remaining_life_months
        ];
    }

    protected function calculateDoubleDecliningDepreciation($asOfDate)
    {
        $totalLifeYears = $this->useful_life_years + ($this->useful_life_months / 12);
        $rate = (2 / $totalLifeYears) / 12; // Monthly rate
        
        $elapsedMonths = $this->depreciation_start_date->diffInMonths($asOfDate);
        
        $currentValue = $this->purchase_price;
        $totalDepreciation = 0;
        
        for ($month = 1; $month <= $elapsedMonths; $month++) {
            $monthlyDepreciation = $currentValue * $rate;
            
            // Don't depreciate below salvage value
            if ($currentValue - $monthlyDepreciation < $this->salvage_value) {
                $monthlyDepreciation = max(0, $currentValue - $this->salvage_value);
            }
            
            $totalDepreciation += $monthlyDepreciation;
            $currentValue -= $monthlyDepreciation;
            
            if ($currentValue <= $this->salvage_value) break;
        }
        
        return [
            'monthly_depreciation' => round($currentValue * $rate, 2),
            'accumulated_depreciation' => round($totalDepreciation, 2),
            'current_book_value' => round($currentValue, 2),
            'remaining_life_months' => $this->remaining_life_months
        ];
    }

    protected function calculateSumOfYearsDepreciation($asOfDate)
    {
        $totalLifeYears = $this->useful_life_years + ($this->useful_life_months / 12);
        $sumOfYears = ($totalLifeYears * ($totalLifeYears + 1)) / 2;
        $depreciableAmount = $this->purchase_price - $this->salvage_value;
        
        $elapsedYears = $this->depreciation_start_date->diffInYears($asOfDate, false);
        $currentYear = floor($elapsedYears) + 1;
        
        $totalDepreciation = 0;
        
        for ($year = 1; $year <= min($currentYear, $totalLifeYears); $year++) {
            $yearFraction = ($totalLifeYears - $year + 1) / $sumOfYears;
            $yearDepreciation = $depreciableAmount * $yearFraction;
            
            if ($year < $currentYear) {
                $totalDepreciation += $yearDepreciation;
            } else {
                // Partial year calculation
                $monthsInYear = ($elapsedYears - floor($elapsedYears)) * 12;
                $totalDepreciation += ($yearDepreciation / 12) * $monthsInYear;
            }
        }
        
        $currentBookValue = $this->purchase_price - $totalDepreciation;
        
        return [
            'monthly_depreciation' => round($totalDepreciation / max(1, $this->depreciation_start_date->diffInMonths($asOfDate)), 2),
            'accumulated_depreciation' => round($totalDepreciation, 2),
            'current_book_value' => round($currentBookValue, 2),
            'remaining_life_months' => $this->remaining_life_months
        ];
    }

    public function updateDepreciation($asOfDate = null)
    {
        $calculation = $this->calculateDepreciation($asOfDate);
        
        $this->update([
            'monthly_depreciation_amount' => $calculation['monthly_depreciation'],
            'accumulated_depreciation' => $calculation['accumulated_depreciation'],
            'current_book_value' => $calculation['current_book_value'],
            'last_calculation_date' => now(),
            'next_calculation_date' => now()->addMonth(),
            'is_fully_depreciated' => $calculation['current_book_value'] <= $this->salvage_value
        ]);
        
        // Create depreciation entry
        $this->depreciationEntries()->create([
            'calculation_date' => $asOfDate ?? now(),
            'depreciation_amount' => $calculation['monthly_depreciation'],
            'accumulated_depreciation' => $calculation['accumulated_depreciation'],
            'book_value' => $calculation['current_book_value'],
            'calculation_method' => $this->depreciation_method,
            'notes' => 'Automated depreciation calculation'
        ]);
        
        return $calculation;
    }

    public function generateDepreciationSchedule()
    {
        $schedule = [];
        $totalLifeMonths = ($this->useful_life_years * 12) + $this->useful_life_months;
        $currentDate = $this->depreciation_start_date->copy();
        
        for ($month = 1; $month <= $totalLifeMonths; $month++) {
            $calculation = $this->calculateDepreciation($currentDate);
            
            $schedule[] = [
                'month' => $month,
                'date' => $currentDate->format('Y-m-d'),
                'depreciation_amount' => $calculation['monthly_depreciation'],
                'accumulated_depreciation' => $calculation['accumulated_depreciation'],
                'book_value' => $calculation['current_book_value']
            ];
            
            $currentDate->addMonth();
            
            if ($calculation['current_book_value'] <= $this->salvage_value) {
                break;
            }
        }
        
        $this->update(['depreciation_schedule' => $schedule]);
        
        return $schedule;
    }

    public function isOverdue()
    {
        return $this->next_calculation_date && $this->next_calculation_date->isPast();
    }

    public function getDaysOverdue()
    {
        if (!$this->isOverdue()) return 0;
        return $this->next_calculation_date->diffInDays(now());
    }

    // Static methods
    public static function createForAsset($inventoryItem, $depreciationData)
    {
        return self::create([
            'inventory_item_id' => $inventoryItem->id,
            'depreciation_method' => $depreciationData['method'] ?? self::METHOD_STRAIGHT_LINE,
            'useful_life_years' => $depreciationData['useful_life_years'] ?? 5,
            'useful_life_months' => $depreciationData['useful_life_months'] ?? 0,
            'salvage_value' => $depreciationData['salvage_value'] ?? 0,
            'purchase_price' => $inventoryItem->purchase_price,
            'purchase_date' => $inventoryItem->purchase_date,
            'depreciation_start_date' => $depreciationData['start_date'] ?? $inventoryItem->purchase_date,
            'annual_depreciation_rate' => $depreciationData['annual_rate'] ?? 20,
            'current_book_value' => $inventoryItem->purchase_price,
            'next_calculation_date' => now()->addMonth(),
            'created_by' => auth()->id()
        ]);
    }

    public static function getDepreciationMethods()
    {
        return [
            self::METHOD_STRAIGHT_LINE => 'Straight Line',
            self::METHOD_DECLINING_BALANCE => 'Declining Balance',
            self::METHOD_DOUBLE_DECLINING => 'Double Declining Balance',
            self::METHOD_SUM_OF_YEARS => 'Sum of Years Digits',
            self::METHOD_UNITS_OF_PRODUCTION => 'Units of Production'
        ];
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
        
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
        
        static::created(function ($model) {
            $model->generateDepreciationSchedule();
        });
    }
}