<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorPerformance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'evaluation_period_start',
        'evaluation_period_end',
        'total_orders',
        'completed_orders',
        'cancelled_orders',
        'total_order_value',
        'on_time_deliveries',
        'late_deliveries',
        'early_deliveries',
        'average_delivery_days',
        'quality_rating',
        'service_rating',
        'communication_rating',
        'pricing_rating',
        'overall_rating',
        'defect_rate',
        'return_rate',
        'compliance_score',
        'payment_terms_adherence',
        'response_time_hours',
        'cost_savings_achieved',
        'innovation_score',
        'sustainability_score',
        'risk_score',
        'performance_score',
        'performance_grade',
        'strengths',
        'weaknesses',
        'improvement_areas',
        'action_items',
        'evaluator_id',
        'evaluation_date',
        'next_evaluation_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'evaluation_period_start' => 'date',
        'evaluation_period_end' => 'date',
        'total_order_value' => 'decimal:2',
        'average_delivery_days' => 'decimal:1',
        'quality_rating' => 'decimal:1',
        'service_rating' => 'decimal:1',
        'communication_rating' => 'decimal:1',
        'pricing_rating' => 'decimal:1',
        'overall_rating' => 'decimal:1',
        'defect_rate' => 'decimal:2',
        'return_rate' => 'decimal:2',
        'compliance_score' => 'decimal:1',
        'payment_terms_adherence' => 'decimal:1',
        'response_time_hours' => 'decimal:1',
        'cost_savings_achieved' => 'decimal:2',
        'innovation_score' => 'decimal:1',
        'sustainability_score' => 'decimal:1',
        'risk_score' => 'decimal:1',
        'performance_score' => 'decimal:1',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'improvement_areas' => 'array',
        'action_items' => 'array',
        'evaluation_date' => 'datetime',
        'next_evaluation_date' => 'date'
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    // Scopes
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('evaluation_period_start', now()->year);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('evaluation_period_start', [$startDate, $endDate]);
    }

    public function scopeByGrade($query, $grade)
    {
        return $query->where('performance_grade', $grade);
    }

    public function scopeTopPerformers($query, $minScore = 4.0)
    {
        return $query->where('performance_score', '>=', $minScore)
                    ->orderBy('performance_score', 'desc');
    }

    public function scopePoorPerformers($query, $maxScore = 2.5)
    {
        return $query->where('performance_score', '<=', $maxScore)
                    ->orderBy('performance_score', 'asc');
    }

    public function scopeHighRisk($query, $minRiskScore = 3.5)
    {
        return $query->where('risk_score', '>=', $minRiskScore);
    }

    public function scopeRecentEvaluations($query, $days = 30)
    {
        return $query->where('evaluation_date', '>=', now()->subDays($days));
    }

    public function scopeDueForEvaluation($query)
    {
        return $query->where('next_evaluation_date', '<=', now())
                    ->orWhereNull('next_evaluation_date');
    }

    // Accessors
    public function getOnTimeDeliveryPercentageAttribute()
    {
        if ($this->completed_orders == 0) {
            return 0;
        }
        return round(($this->on_time_deliveries / $this->completed_orders) * 100, 2);
    }

    public function getLateDeliveryPercentageAttribute()
    {
        if ($this->completed_orders == 0) {
            return 0;
        }
        return round(($this->late_deliveries / $this->completed_orders) * 100, 2);
    }

    public function getOrderCompletionRateAttribute()
    {
        if ($this->total_orders == 0) {
            return 0;
        }
        return round(($this->completed_orders / $this->total_orders) * 100, 2);
    }

    public function getAverageOrderValueAttribute()
    {
        if ($this->total_orders == 0) {
            return 0;
        }
        return round($this->total_order_value / $this->total_orders, 2);
    }

    public function getPerformanceGradeColorAttribute()
    {
        $colors = [
            'A+' => '#28a745', // Green
            'A' => '#28a745',
            'B+' => '#6f42c1', // Purple
            'B' => '#6f42c1',
            'C+' => '#fd7e14', // Orange
            'C' => '#fd7e14',
            'D' => '#dc3545', // Red
            'F' => '#dc3545'
        ];

        return $colors[$this->performance_grade] ?? '#6c757d';
    }

    public function getRiskLevelAttribute()
    {
        if ($this->risk_score >= 4.0) {
            return 'High';
        } elseif ($this->risk_score >= 3.0) {
            return 'Medium';
        } elseif ($this->risk_score >= 2.0) {
            return 'Low';
        } else {
            return 'Very Low';
        }
    }

    public function getRiskLevelColorAttribute()
    {
        $colors = [
            'High' => '#dc3545',
            'Medium' => '#fd7e14',
            'Low' => '#ffc107',
            'Very Low' => '#28a745'
        ];

        return $colors[$this->risk_level] ?? '#6c757d';
    }

    public function getEvaluationPeriodAttribute()
    {
        return $this->evaluation_period_start->format('M d, Y') . ' - ' . 
               $this->evaluation_period_end->format('M d, Y');
    }

    // Helper Methods
    public function calculatePerformanceScore()
    {
        $weights = [
            'quality_rating' => 0.25,
            'service_rating' => 0.20,
            'on_time_delivery' => 0.20,
            'communication_rating' => 0.15,
            'pricing_rating' => 0.10,
            'compliance_score' => 0.10
        ];

        $onTimeDeliveryScore = $this->on_time_delivery_percentage / 20; // Convert percentage to 5-point scale

        $score = ($this->quality_rating * $weights['quality_rating']) +
                ($this->service_rating * $weights['service_rating']) +
                ($onTimeDeliveryScore * $weights['on_time_delivery']) +
                ($this->communication_rating * $weights['communication_rating']) +
                ($this->pricing_rating * $weights['pricing_rating']) +
                ($this->compliance_score * $weights['compliance_score']);

        $this->performance_score = round($score, 2);
        return $this->performance_score;
    }

    public function calculatePerformanceGrade()
    {
        $score = $this->performance_score;

        if ($score >= 4.8) {
            $grade = 'A+';
        } elseif ($score >= 4.5) {
            $grade = 'A';
        } elseif ($score >= 4.0) {
            $grade = 'B+';
        } elseif ($score >= 3.5) {
            $grade = 'B';
        } elseif ($score >= 3.0) {
            $grade = 'C+';
        } elseif ($score >= 2.5) {
            $grade = 'C';
        } elseif ($score >= 2.0) {
            $grade = 'D';
        } else {
            $grade = 'F';
        }

        $this->performance_grade = $grade;
        return $grade;
    }

    public function calculateRiskScore()
    {
        $factors = [
            'late_delivery_rate' => $this->late_delivery_percentage / 20, // Convert to 5-point scale
            'defect_rate' => $this->defect_rate * 5, // Convert percentage to 5-point scale
            'return_rate' => $this->return_rate * 5,
            'payment_compliance' => 5 - $this->payment_terms_adherence, // Inverse scoring
            'communication_issues' => 5 - $this->communication_rating
        ];

        $riskScore = array_sum($factors) / count($factors);
        $this->risk_score = round(min(5, max(1, $riskScore)), 2);
        
        return $this->risk_score;
    }

    public function generateActionItems()
    {
        $actionItems = [];

        // Performance-based action items
        if ($this->quality_rating < 3.0) {
            $actionItems[] = 'Implement quality improvement plan with vendor';
        }

        if ($this->on_time_delivery_percentage < 80) {
            $actionItems[] = 'Review and optimize delivery schedules';
        }

        if ($this->communication_rating < 3.0) {
            $actionItems[] = 'Establish regular communication protocols';
        }

        if ($this->defect_rate > 5) {
            $actionItems[] = 'Conduct quality audit and implement corrective measures';
        }

        if ($this->return_rate > 3) {
            $actionItems[] = 'Analyze return patterns and address root causes';
        }

        if ($this->risk_score >= 3.5) {
            $actionItems[] = 'Develop risk mitigation strategies';
        }

        if ($this->performance_score < 3.0) {
            $actionItems[] = 'Schedule performance improvement meeting';
        }

        $this->action_items = $actionItems;
        return $actionItems;
    }

    public function scheduleNextEvaluation($months = 6)
    {
        $this->next_evaluation_date = now()->addMonths($months);
        return $this;
    }

    public function isOverdue()
    {
        return $this->next_evaluation_date && $this->next_evaluation_date->isPast();
    }

    public function getDaysUntilNextEvaluation()
    {
        if (!$this->next_evaluation_date) {
            return null;
        }

        return now()->diffInDays($this->next_evaluation_date, false);
    }

    // Static Methods
    public static function createFromVendorData($vendorId, $startDate, $endDate, $evaluatorId = null)
    {
        $vendor = Vendor::findOrFail($vendorId);
        
        // Get purchase orders for the evaluation period
        $purchaseOrders = $vendor->purchaseOrders()
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        $completedOrders = $purchaseOrders->where('status', 'completed');
        $cancelledOrders = $purchaseOrders->where('status', 'cancelled');

        // Calculate delivery performance
        $onTimeDeliveries = $completedOrders->filter(function ($po) {
            return $po->actual_delivery_date && $po->expected_delivery_date &&
                   $po->actual_delivery_date <= $po->expected_delivery_date;
        })->count();

        $lateDeliveries = $completedOrders->filter(function ($po) {
            return $po->actual_delivery_date && $po->expected_delivery_date &&
                   $po->actual_delivery_date > $po->expected_delivery_date;
        })->count();

        $earlyDeliveries = $completedOrders->filter(function ($po) {
            return $po->actual_delivery_date && $po->expected_delivery_date &&
                   $po->actual_delivery_date < $po->expected_delivery_date;
        })->count();

        // Calculate average delivery days
        $deliveryDays = $completedOrders->filter(function ($po) {
            return $po->actual_delivery_date && $po->order_date;
        })->map(function ($po) {
            return $po->order_date->diffInDays($po->actual_delivery_date);
        });

        $avgDeliveryDays = $deliveryDays->count() > 0 ? $deliveryDays->avg() : 0;

        // Create performance record
        $performance = new static([
            'vendor_id' => $vendorId,
            'evaluation_period_start' => $startDate,
            'evaluation_period_end' => $endDate,
            'total_orders' => $purchaseOrders->count(),
            'completed_orders' => $completedOrders->count(),
            'cancelled_orders' => $cancelledOrders->count(),
            'total_order_value' => $purchaseOrders->sum('total_amount'),
            'on_time_deliveries' => $onTimeDeliveries,
            'late_deliveries' => $lateDeliveries,
            'early_deliveries' => $earlyDeliveries,
            'average_delivery_days' => round($avgDeliveryDays, 1),
            'evaluator_id' => $evaluatorId ?: auth()->id(),
            'evaluation_date' => now(),
            'status' => 'draft'
        ]);

        return $performance;
    }

    public static function getVendorTrends($vendorId, $periods = 6)
    {
        return static::where('vendor_id', $vendorId)
            ->orderBy('evaluation_period_start', 'desc')
            ->limit($periods)
            ->get()
            ->reverse()
            ->values();
    }

    public static function getBenchmarkData($period = 'current_year')
    {
        $query = static::query();

        if ($period === 'current_year') {
            $query->currentYear();
        }

        $performances = $query->get();

        return [
            'average_performance_score' => $performances->avg('performance_score'),
            'average_on_time_delivery' => $performances->avg('on_time_delivery_percentage'),
            'average_quality_rating' => $performances->avg('quality_rating'),
            'average_service_rating' => $performances->avg('service_rating'),
            'top_performers_count' => $performances->where('performance_grade', 'A+')->count() + 
                                    $performances->where('performance_grade', 'A')->count(),
            'poor_performers_count' => $performances->where('performance_grade', 'D')->count() + 
                                     $performances->where('performance_grade', 'F')->count()
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($performance) {
            // Auto-calculate scores if not set
            if (is_null($performance->performance_score)) {
                $performance->calculatePerformanceScore();
            }
            
            if (is_null($performance->performance_grade)) {
                $performance->calculatePerformanceGrade();
            }
            
            if (is_null($performance->risk_score)) {
                $performance->calculateRiskScore();
            }
            
            // Generate action items if empty
            if (empty($performance->action_items)) {
                $performance->generateActionItems();
            }
            
            // Schedule next evaluation if not set
            if (is_null($performance->next_evaluation_date)) {
                $performance->scheduleNextEvaluation();
            }
        });
    }
}