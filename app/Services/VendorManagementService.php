<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorPerformance;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\VendorPerformanceAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VendorManagementService
{
    /**
     * Get vendor management dashboard data
     */
    public function getDashboardData()
    {
        return Cache::remember('vendor_management_dashboard', 300, function () {
            return [
                'summary' => $this->getVendorSummary(),
                'performance_overview' => $this->getPerformanceOverview(),
                'top_performers' => $this->getTopPerformers(),
                'poor_performers' => $this->getPoorPerformers(),
                'vendor_distribution' => $this->getVendorDistribution(),
                'recent_evaluations' => $this->getRecentEvaluations(),
                'pending_evaluations' => $this->getPendingEvaluations(),
                'risk_analysis' => $this->getRiskAnalysis(),
                'cost_analysis' => $this->getCostAnalysis(),
                'delivery_trends' => $this->getDeliveryTrends()
            ];
        });
    }

    /**
     * Get vendor summary statistics
     */
    private function getVendorSummary()
    {
        $totalVendors = Vendor::count();
        $activeVendors = Vendor::active()->count();
        $blacklistedVendors = Vendor::blacklisted()->count();
        $vendorsWithOutstanding = Vendor::withOutstandingBalance()->count();
        $vendorsExceedingCredit = Vendor::exceedingCreditLimit()->count();

        return [
            'total_vendors' => $totalVendors,
            'active_vendors' => $activeVendors,
            'inactive_vendors' => $totalVendors - $activeVendors - $blacklistedVendors,
            'blacklisted_vendors' => $blacklistedVendors,
            'vendors_with_outstanding' => $vendorsWithOutstanding,
            'vendors_exceeding_credit' => $vendorsExceedingCredit,
            'total_outstanding_amount' => Vendor::sum('outstanding_balance'),
            'total_credit_limit' => Vendor::sum('credit_limit'),
            'average_rating' => round(Vendor::active()->avg('rating'), 2)
        ];
    }

    /**
     * Get performance overview
     */
    private function getPerformanceOverview()
    {
        $currentYear = now()->year;
        $performances = VendorPerformance::currentYear()->get();

        $gradeDistribution = $performances->groupBy('performance_grade')
            ->map(function ($group) {
                return $group->count();
            });

        return [
            'total_evaluations' => $performances->count(),
            'average_performance_score' => round($performances->avg('performance_score'), 2),
            'average_on_time_delivery' => round($performances->avg('on_time_delivery_percentage'), 2),
            'average_quality_rating' => round($performances->avg('quality_rating'), 2),
            'grade_distribution' => $gradeDistribution,
            'high_risk_vendors' => $performances->where('risk_score', '>=', 3.5)->count(),
            'evaluations_due' => VendorPerformance::dueForEvaluation()->count()
        ];
    }

    /**
     * Get top performing vendors
     */
    private function getTopPerformers($limit = 10)
    {
        return VendorPerformance::with('vendor')
            ->topPerformers()
            ->limit($limit)
            ->get()
            ->map(function ($performance) {
                return [
                    'vendor_id' => $performance->vendor_id,
                    'vendor_name' => $performance->vendor->name,
                    'performance_score' => $performance->performance_score,
                    'performance_grade' => $performance->performance_grade,
                    'on_time_delivery' => $performance->on_time_delivery_percentage,
                    'quality_rating' => $performance->quality_rating,
                    'total_orders' => $performance->total_orders,
                    'total_value' => $performance->total_order_value
                ];
            });
    }

    /**
     * Get poor performing vendors
     */
    private function getPoorPerformers($limit = 10)
    {
        return VendorPerformance::with('vendor')
            ->poorPerformers()
            ->limit($limit)
            ->get()
            ->map(function ($performance) {
                return [
                    'vendor_id' => $performance->vendor_id,
                    'vendor_name' => $performance->vendor->name,
                    'performance_score' => $performance->performance_score,
                    'performance_grade' => $performance->performance_grade,
                    'on_time_delivery' => $performance->on_time_delivery_percentage,
                    'quality_rating' => $performance->quality_rating,
                    'risk_score' => $performance->risk_score,
                    'action_items' => count($performance->action_items ?? [])
                ];
            });
    }

    /**
     * Get vendor distribution by location, rating, etc.
     */
    private function getVendorDistribution()
    {
        return [
            'by_location' => Vendor::active()
                ->select('state', DB::raw('count(*) as count'))
                ->groupBy('state')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'by_rating' => Vendor::active()
                ->select('rating', DB::raw('count(*) as count'))
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->get(),
            'by_order_volume' => Vendor::active()
                ->select(
                    DB::raw('CASE 
                        WHEN total_orders >= 100 THEN "High Volume (100+)"
                        WHEN total_orders >= 50 THEN "Medium Volume (50-99)"
                        WHEN total_orders >= 10 THEN "Low Volume (10-49)"
                        ELSE "New Vendor (<10)"
                    END as volume_category'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('volume_category')
                ->get()
        ];
    }

    /**
     * Get recent vendor evaluations
     */
    private function getRecentEvaluations($limit = 10)
    {
        return VendorPerformance::with(['vendor', 'evaluator'])
            ->recentEvaluations()
            ->orderBy('evaluation_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($performance) {
                return [
                    'id' => $performance->id,
                    'vendor_name' => $performance->vendor->name,
                    'evaluator_name' => $performance->evaluator->name ?? 'System',
                    'performance_score' => $performance->performance_score,
                    'performance_grade' => $performance->performance_grade,
                    'evaluation_date' => $performance->evaluation_date->format('M d, Y'),
                    'status' => $performance->status
                ];
            });
    }

    /**
     * Get pending evaluations
     */
    private function getPendingEvaluations()
    {
        return VendorPerformance::with('vendor')
            ->dueForEvaluation()
            ->orderBy('next_evaluation_date', 'asc')
            ->get()
            ->map(function ($performance) {
                return [
                    'vendor_id' => $performance->vendor_id,
                    'vendor_name' => $performance->vendor->name,
                    'last_evaluation' => $performance->evaluation_date->format('M d, Y'),
                    'due_date' => $performance->next_evaluation_date ? 
                        $performance->next_evaluation_date->format('M d, Y') : 'Overdue',
                    'days_overdue' => $performance->getDaysUntilNextEvaluation(),
                    'priority' => $performance->isOverdue() ? 'high' : 'medium'
                ];
            });
    }

    /**
     * Get risk analysis
     */
    private function getRiskAnalysis()
    {
        $highRiskVendors = VendorPerformance::highRisk()->with('vendor')->get();
        
        $riskFactors = [
            'late_deliveries' => $highRiskVendors->where('late_delivery_percentage', '>', 20)->count(),
            'quality_issues' => $highRiskVendors->where('quality_rating', '<', 3)->count(),
            'communication_issues' => $highRiskVendors->where('communication_rating', '<', 3)->count(),
            'payment_issues' => Vendor::exceedingCreditLimit()->count(),
            'single_source_dependency' => $this->getSingleSourceDependencies()
        ];

        return [
            'high_risk_count' => $highRiskVendors->count(),
            'risk_factors' => $riskFactors,
            'risk_mitigation_actions' => $this->generateRiskMitigationActions($highRiskVendors)
        ];
    }

    /**
     * Get cost analysis
     */
    private function getCostAnalysis()
    {
        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        $currentYearSpend = PurchaseOrder::whereYear('order_date', $currentYear)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $lastYearSpend = PurchaseOrder::whereYear('order_date', $lastYear)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $topSpendingVendors = Vendor::with(['purchaseOrders' => function ($query) use ($currentYear) {
                $query->whereYear('order_date', $currentYear)
                      ->where('status', '!=', 'cancelled');
            }])
            ->get()
            ->map(function ($vendor) {
                return [
                    'vendor_id' => $vendor->id,
                    'vendor_name' => $vendor->name,
                    'total_spend' => $vendor->purchaseOrders->sum('total_amount'),
                    'order_count' => $vendor->purchaseOrders->count(),
                    'average_order_value' => $vendor->purchaseOrders->count() > 0 ? 
                        $vendor->purchaseOrders->avg('total_amount') : 0
                ];
            })
            ->sortByDesc('total_spend')
            ->take(10)
            ->values();

        return [
            'current_year_spend' => $currentYearSpend,
            'last_year_spend' => $lastYearSpend,
            'spend_change_percentage' => $lastYearSpend > 0 ? 
                round((($currentYearSpend - $lastYearSpend) / $lastYearSpend) * 100, 2) : 0,
            'top_spending_vendors' => $topSpendingVendors,
            'cost_savings_opportunities' => $this->identifyCostSavingsOpportunities()
        ];
    }

    /**
     * Get delivery trends
     */
    private function getDeliveryTrends($months = 12)
    {
        $trends = [];
        
        for ($i = 0; $i < $months; $i++) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $orders = PurchaseOrder::whereBetween('order_date', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->get();
            
            $onTimeDeliveries = $orders->filter(function ($po) {
                return $po->actual_delivery_date && $po->expected_delivery_date &&
                       $po->actual_delivery_date <= $po->expected_delivery_date;
            })->count();
            
            $trends[] = [
                'month' => $date->format('M Y'),
                'total_orders' => $orders->count(),
                'on_time_deliveries' => $onTimeDeliveries,
                'on_time_percentage' => $orders->count() > 0 ? 
                    round(($onTimeDeliveries / $orders->count()) * 100, 2) : 0,
                'average_delivery_days' => $orders->filter(function ($po) {
                    return $po->actual_delivery_date && $po->order_date;
                })->map(function ($po) {
                    return $po->order_date->diffInDays($po->actual_delivery_date);
                })->avg() ?? 0
            ];
        }
        
        return array_reverse($trends);
    }

    /**
     * Create vendor performance evaluation
     */
    public function createPerformanceEvaluation($vendorId, $startDate, $endDate, $evaluationData = [])
    {
        try {
            DB::beginTransaction();

            // Create base performance record from vendor data
            $performance = VendorPerformance::createFromVendorData(
                $vendorId, 
                $startDate, 
                $endDate, 
                auth()->id()
            );

            // Add manual evaluation data
            if (!empty($evaluationData)) {
                $performance->fill($evaluationData);
            }

            // Calculate scores
            $performance->calculatePerformanceScore();
            $performance->calculatePerformanceGrade();
            $performance->calculateRiskScore();
            $performance->generateActionItems();

            $performance->save();

            // Update vendor's overall rating based on latest performance
            $this->updateVendorRating($vendorId);

            // Send notifications if performance is poor
            if ($performance->performance_score < 3.0) {
                $this->sendPerformanceAlert($performance);
            }

            DB::commit();

            Log::info('Vendor performance evaluation created', [
                'vendor_id' => $vendorId,
                'performance_id' => $performance->id,
                'performance_score' => $performance->performance_score,
                'evaluator_id' => auth()->id()
            ]);

            return $performance;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create vendor performance evaluation', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update vendor performance evaluation
     */
    public function updatePerformanceEvaluation($performanceId, $evaluationData)
    {
        try {
            $performance = VendorPerformance::findOrFail($performanceId);
            
            $performance->fill($evaluationData);
            
            // Recalculate scores
            $performance->calculatePerformanceScore();
            $performance->calculatePerformanceGrade();
            $performance->calculateRiskScore();
            
            if (empty($performance->action_items)) {
                $performance->generateActionItems();
            }
            
            $performance->save();

            // Update vendor's overall rating
            $this->updateVendorRating($performance->vendor_id);

            // Clear cache
            Cache::forget('vendor_management_dashboard');

            return $performance;

        } catch (\Exception $e) {
            Log::error('Failed to update vendor performance evaluation', [
                'performance_id' => $performanceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get vendor performance trends
     */
    public function getVendorPerformanceTrends($vendorId, $periods = 6)
    {
        $trends = VendorPerformance::getVendorTrends($vendorId, $periods);
        
        return $trends->map(function ($performance) {
            return [
                'period' => $performance->evaluation_period,
                'performance_score' => $performance->performance_score,
                'on_time_delivery' => $performance->on_time_delivery_percentage,
                'quality_rating' => $performance->quality_rating,
                'service_rating' => $performance->service_rating,
                'risk_score' => $performance->risk_score,
                'total_orders' => $performance->total_orders,
                'total_value' => $performance->total_order_value
            ];
        });
    }

    /**
     * Generate vendor comparison report
     */
    public function generateVendorComparison($vendorIds, $metrics = [])
    {
        $defaultMetrics = [
            'performance_score',
            'on_time_delivery_percentage',
            'quality_rating',
            'service_rating',
            'pricing_rating',
            'risk_score'
        ];

        $metricsToCompare = !empty($metrics) ? $metrics : $defaultMetrics;
        
        $vendors = Vendor::whereIn('id', $vendorIds)
            ->with(['vendorPerformances' => function ($query) {
                $query->latest('evaluation_date')->first();
            }])
            ->get();

        $comparison = [];
        
        foreach ($vendors as $vendor) {
            $latestPerformance = $vendor->vendorPerformances->first();
            
            $vendorData = [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'overall_rating' => $vendor->rating,
                'total_orders' => $vendor->total_orders,
                'total_purchase_amount' => $vendor->total_purchase_amount
            ];

            if ($latestPerformance) {
                foreach ($metricsToCompare as $metric) {
                    $vendorData[$metric] = $latestPerformance->$metric;
                }
            } else {
                foreach ($metricsToCompare as $metric) {
                    $vendorData[$metric] = null;
                }
            }

            $comparison[] = $vendorData;
        }

        return $comparison;
    }

    /**
     * Get vendor recommendations for items
     */
    public function getVendorRecommendations($itemIds)
    {
        $recommendations = [];
        
        foreach ($itemIds as $itemId) {
            // Get vendors who have supplied this item before
            $vendors = Vendor::whereHas('purchaseOrders.items', function ($query) use ($itemId) {
                $query->where('inventory_item_id', $itemId);
            })
            ->with(['vendorPerformances' => function ($query) {
                $query->latest('evaluation_date')->first();
            }])
            ->get();

            // Score vendors based on performance and history
            $scoredVendors = $vendors->map(function ($vendor) use ($itemId) {
                $latestPerformance = $vendor->vendorPerformances->first();
                
                // Get historical data for this item
                $itemHistory = $vendor->purchaseOrders()
                    ->whereHas('items', function ($query) use ($itemId) {
                        $query->where('inventory_item_id', $itemId);
                    })
                    ->with('items')
                    ->get();

                $avgPrice = $itemHistory->flatMap->items
                    ->where('inventory_item_id', $itemId)
                    ->avg('unit_price');

                $totalQuantity = $itemHistory->flatMap->items
                    ->where('inventory_item_id', $itemId)
                    ->sum('quantity_ordered');

                $score = 0;
                
                // Performance score (40%)
                if ($latestPerformance) {
                    $score += $latestPerformance->performance_score * 8;
                } else {
                    $score += $vendor->rating * 8;
                }
                
                // Price competitiveness (30%)
                // This would need market price data for proper scoring
                $score += 15; // Placeholder
                
                // Order history (20%)
                $score += min(10, $totalQuantity / 100);
                
                // Availability/reliability (10%)
                $score += $vendor->isActive() ? 5 : 0;

                return [
                    'vendor_id' => $vendor->id,
                    'vendor_name' => $vendor->name,
                    'score' => round($score, 2),
                    'performance_rating' => $latestPerformance ? 
                        $latestPerformance->performance_score : $vendor->rating,
                    'average_price' => $avgPrice,
                    'total_quantity_supplied' => $totalQuantity,
                    'last_order_date' => $vendor->last_order_date,
                    'is_preferred' => false // This would be set based on business rules
                ];
            })
            ->sortByDesc('score')
            ->values();

            $recommendations[$itemId] = $scoredVendors;
        }

        return $recommendations;
    }

    /**
     * Helper methods
     */
    private function updateVendorRating($vendorId)
    {
        $vendor = Vendor::find($vendorId);
        if (!$vendor) return;

        $latestPerformances = VendorPerformance::where('vendor_id', $vendorId)
            ->orderBy('evaluation_date', 'desc')
            ->limit(3)
            ->get();

        if ($latestPerformances->count() > 0) {
            $avgScore = $latestPerformances->avg('performance_score');
            $vendor->rating = round($avgScore);
            $vendor->save();
        }
    }

    private function sendPerformanceAlert($performance)
    {
        $managers = User::where('role', 'manager')->get();
        
        foreach ($managers as $manager) {
            $manager->notify(new VendorPerformanceAlert($performance));
        }
    }

    private function getSingleSourceDependencies()
    {
        // Count items that are only supplied by one vendor
        return DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->select('inventory_item_id')
            ->groupBy('inventory_item_id')
            ->havingRaw('COUNT(DISTINCT vendor_id) = 1')
            ->count();
    }

    private function generateRiskMitigationActions($highRiskVendors)
    {
        $actions = [];
        
        foreach ($highRiskVendors as $performance) {
            if ($performance->late_delivery_percentage > 30) {
                $actions[] = "Review delivery schedules with {$performance->vendor->name}";
            }
            
            if ($performance->quality_rating < 2.5) {
                $actions[] = "Conduct quality audit for {$performance->vendor->name}";
            }
            
            if ($performance->risk_score >= 4.0) {
                $actions[] = "Develop contingency plan for {$performance->vendor->name}";
            }
        }
        
        return $actions;
    }

    private function identifyCostSavingsOpportunities()
    {
        $opportunities = [];
        
        // Find vendors with high prices for similar items
        // Find vendors with poor performance that could be replaced
        // Find consolidation opportunities
        
        $opportunities[] = "Consolidate orders with top-performing vendors";
        $opportunities[] = "Negotiate volume discounts with high-spend vendors";
        $opportunities[] = "Review pricing with vendors rated below 3.0";
        
        return $opportunities;
    }
}