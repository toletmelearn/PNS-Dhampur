<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Models\MaintenanceSchedule;
use App\Models\AssetAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InventoryManagementService
{
    protected $cachePrefix = 'inventory_';
    protected $cacheTTL = 300; // 5 minutes

    /**
     * Get comprehensive inventory dashboard
     */
    public function getInventoryDashboard()
    {
        $cacheKey = $this->cachePrefix . 'dashboard';
        
        return Cache::remember($cacheKey, $this->cacheTTL, function () {
            return [
                'overview' => $this->getInventoryOverview(),
                'low_stock_alerts' => $this->getLowStockAlerts(),
                'pending_orders' => $this->getPendingPurchaseOrders(),
                'asset_summary' => $this->getAssetSummary(),
                'maintenance_due' => $this->getMaintenanceDue(),
                'vendor_performance' => $this->getVendorPerformance(),
                'depreciation_summary' => $this->getDepreciationSummary(),
                'inventory_trends' => $this->getInventoryTrends(),
                'cost_analysis' => $this->getCostAnalysis()
            ];
        });
    }

    /**
     * Get inventory overview metrics
     */
    protected function getInventoryOverview()
    {
        $totalItems = InventoryItem::count();
        $activeItems = InventoryItem::active()->count();
        $lowStockItems = InventoryItem::lowStock()->count();
        $outOfStockItems = InventoryItem::where('current_stock', 0)->count();
        $assetsCount = InventoryItem::where('is_asset', true)->count();
        
        $totalValue = InventoryItem::selectRaw('SUM(current_stock * unit_price) as total')
                                 ->first()->total ?? 0;
        
        $averageStockLevel = InventoryItem::avg('current_stock');
        
        return [
            'total_items' => $totalItems,
            'active_items' => $activeItems,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'assets_count' => $assetsCount,
            'total_inventory_value' => round($totalValue, 2),
            'average_stock_level' => round($averageStockLevel, 2),
            'stock_turnover_rate' => $this->calculateStockTurnoverRate(),
            'reorder_points_hit' => $this->getReorderPointsHit(),
            'categories_count' => InventoryItem::distinct('category_id')->count()
        ];
    }

    /**
     * Get low stock alerts with automatic notifications
     */
    public function getLowStockAlerts($autoNotify = true)
    {
        $lowStockItems = InventoryItem::with(['category'])
                                    ->where(function ($query) {
                                        $query->whereColumn('current_stock', '<=', 'minimum_stock_level')
                                              ->orWhere('current_stock', 0);
                                    })
                                    ->get();

        $alerts = $lowStockItems->map(function ($item) {
            $stockStatus = $this->getStockStatus($item);
            $reorderQuantity = $this->calculateReorderQuantity($item);
            $estimatedCost = $reorderQuantity * $item->unit_price;
            
            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'Uncategorized',
                'current_stock' => $item->current_stock,
                'minimum_level' => $item->minimum_stock_level,
                'maximum_level' => $item->maximum_stock_level,
                'stock_status' => $stockStatus,
                'urgency_level' => $this->getUrgencyLevel($item),
                'suggested_reorder_quantity' => $reorderQuantity,
                'estimated_reorder_cost' => $estimatedCost,
                'last_restocked' => $item->last_restocked_at,
                'days_since_restock' => $item->last_restocked_at ? 
                    now()->diffInDays($item->last_restocked_at) : null,
                'supplier_info' => $this->getPreferredSupplier($item),
                'lead_time_days' => $item->lead_time_days ?? 7,
                'auto_reorder_enabled' => $item->auto_reorder_enabled ?? false
            ];
        });

        if ($autoNotify && $alerts->isNotEmpty()) {
            $this->sendLowStockNotifications($alerts);
        }

        return $alerts->sortByDesc('urgency_level')->values()->all();
    }

    /**
     * Automated purchase order workflow
     */
    public function processPurchaseOrderAutomation()
    {
        $autoReorderItems = InventoryItem::where('auto_reorder_enabled', true)
                                        ->where(function ($query) {
                                            $query->whereColumn('current_stock', '<=', 'minimum_stock_level')
                                                  ->orWhere('current_stock', 0);
                                        })
                                        ->get();

        $createdOrders = [];

        foreach ($autoReorderItems as $item) {
            // Check if there's already a pending order for this item
            $existingOrder = PurchaseOrderItem::whereHas('purchaseOrder', function ($query) {
                                                $query->whereIn('status', ['pending', 'approved']);
                                            })
                                            ->where('inventory_item_id', $item->id)
                                            ->exists();

            if (!$existingOrder) {
                $order = $this->createAutomaticPurchaseOrder($item);
                if ($order) {
                    $createdOrders[] = $order;
                }
            }
        }

        return $createdOrders;
    }

    /**
     * Create automatic purchase order
     */
    protected function createAutomaticPurchaseOrder($item)
    {
        try {
            $vendor = $this->getPreferredSupplier($item);
            $quantity = $this->calculateReorderQuantity($item);
            $unitPrice = $item->unit_price;
            $totalAmount = $quantity * $unitPrice;

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->generatePONumber(),
                'vendor_id' => $vendor['id'] ?? null,
                'department' => $item->department ?? 'General',
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'priority' => $this->getOrderPriority($item),
                'requested_by' => 'system', // Auto-generated
                'notes' => "Auto-generated order for low stock item: {$item->name}",
                'expected_delivery_date' => now()->addDays($item->lead_time_days ?? 7),
                'created_at' => now()
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalAmount,
                'specifications' => $item->specifications
            ]);

            // Send notification for approval
            $this->sendPurchaseOrderNotification($purchaseOrder);

            Log::info("Auto-generated purchase order {$purchaseOrder->po_number} for item {$item->name}");

            return $purchaseOrder;

        } catch (\Exception $e) {
            Log::error("Failed to create automatic purchase order for item {$item->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Comprehensive vendor management system
     */
    public function getVendorManagement()
    {
        return [
            'vendor_list' => $this->getVendorList(),
            'vendor_performance' => $this->getVendorPerformanceMetrics(),
            'vendor_comparison' => $this->getVendorComparison(),
            'payment_terms' => $this->getVendorPaymentTerms(),
            'delivery_performance' => $this->getVendorDeliveryPerformance(),
            'quality_ratings' => $this->getVendorQualityRatings(),
            'cost_analysis' => $this->getVendorCostAnalysis(),
            'contract_status' => $this->getVendorContractStatus()
        ];
    }

    /**
     * Get vendor performance metrics
     */
    protected function getVendorPerformanceMetrics()
    {
        return Vendor::with(['purchaseOrders'])
                    ->get()
                    ->map(function ($vendor) {
                        $orders = $vendor->purchaseOrders;
                        $completedOrders = $orders->where('status', 'completed');
                        
                        return [
                            'vendor_id' => $vendor->id,
                            'vendor_name' => $vendor->name,
                            'total_orders' => $orders->count(),
                            'completed_orders' => $completedOrders->count(),
                            'completion_rate' => $orders->count() > 0 ? 
                                ($completedOrders->count() / $orders->count()) * 100 : 0,
                            'average_delivery_time' => $this->calculateAverageDeliveryTime($vendor),
                            'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($vendor),
                            'total_value' => $completedOrders->sum('total_amount'),
                            'average_order_value' => $completedOrders->avg('total_amount'),
                            'quality_score' => $this->calculateVendorQualityScore($vendor),
                            'payment_terms' => $vendor->payment_terms,
                            'last_order_date' => $orders->max('created_at'),
                            'performance_trend' => $this->getVendorPerformanceTrend($vendor),
                            'issues_count' => $this->getVendorIssuesCount($vendor),
                            'preferred_status' => $vendor->is_preferred ?? false
                        ];
                    });
    }

    /**
     * Asset depreciation tracking with automated calculations
     */
    public function getAssetDepreciationTracking()
    {
        $assets = InventoryItem::where('is_asset', true)
                              ->where('depreciation_rate', '>', 0)
                              ->get();

        return $assets->map(function ($asset) {
            $purchaseDate = $asset->purchase_date ?? $asset->created_at;
            $monthsOwned = now()->diffInMonths($purchaseDate);
            $monthlyDepreciation = ($asset->purchase_price * $asset->depreciation_rate) / 100 / 12;
            $totalDepreciation = $monthlyDepreciation * $monthsOwned;
            $currentValue = max(0, $asset->purchase_price - $totalDepreciation);
            $remainingLife = $this->calculateRemainingUsefulLife($asset);

            return [
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'category' => $asset->category->name ?? 'Uncategorized',
                'purchase_date' => $purchaseDate,
                'purchase_price' => $asset->purchase_price,
                'depreciation_rate' => $asset->depreciation_rate,
                'depreciation_method' => $asset->depreciation_method ?? 'straight_line',
                'months_owned' => $monthsOwned,
                'monthly_depreciation' => round($monthlyDepreciation, 2),
                'total_depreciation' => round($totalDepreciation, 2),
                'current_book_value' => round($currentValue, 2),
                'depreciation_percentage' => $asset->purchase_price > 0 ? 
                    round(($totalDepreciation / $asset->purchase_price) * 100, 2) : 0,
                'remaining_useful_life' => $remainingLife,
                'annual_depreciation' => round($monthlyDepreciation * 12, 2),
                'salvage_value' => $asset->salvage_value ?? 0,
                'location' => $asset->location,
                'condition' => $asset->condition ?? 'good',
                'last_maintenance' => $this->getLastMaintenanceDate($asset),
                'next_maintenance_due' => $this->getNextMaintenanceDue($asset)
            ];
        });
    }

    /**
     * Maintenance scheduling system with automated reminders
     */
    public function getMaintenanceScheduling()
    {
        return [
            'upcoming_maintenance' => $this->getUpcomingMaintenance(),
            'overdue_maintenance' => $this->getOverdueMaintenance(),
            'maintenance_calendar' => $this->getMaintenanceCalendar(),
            'maintenance_costs' => $this->getMaintenanceCosts(),
            'asset_health_scores' => $this->getAssetHealthScores(),
            'preventive_schedule' => $this->getPreventiveMaintenanceSchedule(),
            'maintenance_history' => $this->getMaintenanceHistory(),
            'technician_workload' => $this->getTechnicianWorkload()
        ];
    }

    /**
     * Get upcoming maintenance with automated scheduling
     */
    protected function getUpcomingMaintenance()
    {
        $upcomingMaintenance = MaintenanceSchedule::with(['inventoryItem'])
                                                 ->where('scheduled_date', '>=', now())
                                                 ->where('scheduled_date', '<=', now()->addDays(30))
                                                 ->where('status', 'scheduled')
                                                 ->orderBy('scheduled_date')
                                                 ->get();

        return $upcomingMaintenance->map(function ($maintenance) {
            $asset = $maintenance->inventoryItem;
            $daysUntilDue = now()->diffInDays($maintenance->scheduled_date, false);
            
            return [
                'maintenance_id' => $maintenance->id,
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'maintenance_type' => $maintenance->maintenance_type,
                'scheduled_date' => $maintenance->scheduled_date,
                'days_until_due' => $daysUntilDue,
                'priority' => $maintenance->priority ?? 'medium',
                'estimated_cost' => $maintenance->estimated_cost,
                'estimated_duration' => $maintenance->estimated_duration,
                'assigned_technician' => $maintenance->assigned_technician,
                'description' => $maintenance->description,
                'required_parts' => $this->getRequiredParts($maintenance),
                'asset_location' => $asset->location,
                'last_maintenance' => $this->getLastMaintenanceDate($asset),
                'maintenance_frequency' => $asset->maintenance_frequency,
                'criticality' => $this->getAssetCriticality($asset),
                'preparation_checklist' => $this->getMaintenanceChecklist($maintenance)
            ];
        });
    }

    /**
     * Generate automatic maintenance schedules
     */
    public function generateAutomaticMaintenanceSchedules()
    {
        $assets = InventoryItem::where('is_asset', true)
                              ->whereNotNull('maintenance_frequency')
                              ->get();

        $scheduledCount = 0;

        foreach ($assets as $asset) {
            $lastMaintenance = $this->getLastMaintenanceDate($asset);
            $nextDueDate = $this->calculateNextMaintenanceDate($asset, $lastMaintenance);

            if ($nextDueDate && $nextDueDate <= now()->addDays(60)) {
                // Check if maintenance is already scheduled
                $existingSchedule = MaintenanceSchedule::where('inventory_item_id', $asset->id)
                                                      ->where('scheduled_date', $nextDueDate)
                                                      ->where('status', 'scheduled')
                                                      ->exists();

                if (!$existingSchedule) {
                    MaintenanceSchedule::create([
                        'inventory_item_id' => $asset->id,
                        'maintenance_type' => 'preventive',
                        'scheduled_date' => $nextDueDate,
                        'priority' => $this->getMaintenancePriority($asset),
                        'estimated_cost' => $this->estimateMaintenanceCost($asset),
                        'estimated_duration' => $this->estimateMaintenanceDuration($asset),
                        'description' => "Scheduled preventive maintenance for {$asset->name}",
                        'status' => 'scheduled',
                        'created_by' => 'system'
                    ]);

                    $scheduledCount++;
                }
            }
        }

        return $scheduledCount;
    }

    // Helper methods for calculations and data processing

    protected function getStockStatus($item)
    {
        if ($item->current_stock == 0) return 'out_of_stock';
        if ($item->current_stock <= $item->minimum_stock_level) return 'low_stock';
        if ($item->current_stock >= $item->maximum_stock_level) return 'overstock';
        return 'normal';
    }

    protected function getUrgencyLevel($item)
    {
        if ($item->current_stock == 0) return 'critical';
        if ($item->current_stock <= ($item->minimum_stock_level * 0.5)) return 'high';
        if ($item->current_stock <= $item->minimum_stock_level) return 'medium';
        return 'low';
    }

    protected function calculateReorderQuantity($item)
    {
        $maxLevel = $item->maximum_stock_level ?? ($item->minimum_stock_level * 3);
        return max(1, $maxLevel - $item->current_stock);
    }

    protected function getPreferredSupplier($item)
    {
        // This would return the preferred supplier for the item
        return [
            'id' => 1,
            'name' => 'Default Supplier',
            'contact' => 'supplier@example.com',
            'lead_time' => 7
        ];
    }

    protected function generatePONumber()
    {
        $lastPO = PurchaseOrder::orderBy('id', 'desc')->first();
        $nextNumber = $lastPO ? (intval(substr($lastPO->po_number, -4)) + 1) : 1;
        return 'PO' . date('Y') . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function getOrderPriority($item)
    {
        $urgency = $this->getUrgencyLevel($item);
        return match($urgency) {
            'critical' => 'urgent',
            'high' => 'high',
            'medium' => 'normal',
            default => 'low'
        };
    }

    protected function calculateStockTurnoverRate()
    {
        // Implementation for stock turnover calculation
        return 4.2; // Example value
    }

    protected function getReorderPointsHit()
    {
        return InventoryItem::whereColumn('current_stock', '<=', 'minimum_stock_level')->count();
    }

    // Additional helper methods would be implemented here...
    protected function sendLowStockNotifications($alerts) { /* Implementation */ }
    protected function sendPurchaseOrderNotification($order) { /* Implementation */ }
    protected function getVendorList() { /* Implementation */ }
    protected function getVendorComparison() { /* Implementation */ }
    protected function getVendorPaymentTerms() { /* Implementation */ }
    protected function getVendorDeliveryPerformance() { /* Implementation */ }
    protected function getVendorQualityRatings() { /* Implementation */ }
    protected function getVendorCostAnalysis() { /* Implementation */ }
    protected function getVendorContractStatus() { /* Implementation */ }
    protected function calculateAverageDeliveryTime($vendor) { /* Implementation */ }
    protected function calculateOnTimeDeliveryRate($vendor) { /* Implementation */ }
    protected function calculateVendorQualityScore($vendor) { /* Implementation */ }
    protected function getVendorPerformanceTrend($vendor) { /* Implementation */ }
    protected function getVendorIssuesCount($vendor) { /* Implementation */ }
    protected function calculateRemainingUsefulLife($asset) { /* Implementation */ }
    protected function getLastMaintenanceDate($asset) { /* Implementation */ }
    protected function getNextMaintenanceDue($asset) { /* Implementation */ }

    protected function getOverdueMaintenance() { /* Implementation */ }
    protected function getMaintenanceCalendar() { /* Implementation */ }
    protected function getMaintenanceCosts() { /* Implementation */ }
    protected function getAssetHealthScores() { /* Implementation */ }
    protected function getPreventiveMaintenanceSchedule() { /* Implementation */ }
    protected function getMaintenanceHistory() { /* Implementation */ }
    protected function getTechnicianWorkload() { /* Implementation */ }
    protected function getRequiredParts($maintenance) { /* Implementation */ }
    protected function getAssetCriticality($asset) { /* Implementation */ }
    protected function getMaintenanceChecklist($maintenance) { /* Implementation */ }
    protected function calculateNextMaintenanceDate($asset, $lastMaintenance) { /* Implementation */ }
    protected function getMaintenancePriority($asset) { /* Implementation */ }
    protected function estimateMaintenanceCost($asset) { /* Implementation */ }
    protected function estimateMaintenanceDuration($asset) { /* Implementation */ }
    protected function getPendingPurchaseOrders() { /* Implementation */ }
    protected function getAssetSummary() { /* Implementation */ }
    protected function getMaintenanceDue() { /* Implementation */ }
    protected function getVendorPerformance() { /* Implementation */ }
    protected function getDepreciationSummary() { /* Implementation */ }
    protected function getInventoryTrends() { /* Implementation */ }
    protected function getCostAnalysis() { /* Implementation */ }

    /**
     * Clear inventory cache
     */
    public function clearCache()
    {
        Cache::flush(); // For simplicity, flush all cache
    }

    /**
     * Process daily inventory tasks
     */
    public function processDailyTasks()
    {
        $results = [
            'low_stock_alerts' => $this->getLowStockAlerts(),
            'auto_orders_created' => $this->processPurchaseOrderAutomation(),
            'maintenance_scheduled' => $this->generateAutomaticMaintenanceSchedules(),
            'depreciation_updated' => $this->updateAssetDepreciation()
        ];

        $this->clearCache();
        
        return $results;
    }

    /**
     * Update asset depreciation values
     */
    protected function updateAssetDepreciation()
    {
        $assets = InventoryItem::where('is_asset', true)
                              ->where('depreciation_rate', '>', 0)
                              ->get();

        $updatedCount = 0;

        foreach ($assets as $asset) {
            $depreciation = $this->calculateCurrentDepreciation($asset);
            $asset->update([
                'current_value' => max(0, $asset->purchase_price - $depreciation),
                'last_depreciation_update' => now()
            ]);
            $updatedCount++;
        }

        return $updatedCount;
    }

    protected function calculateCurrentDepreciation($asset)
    {
        $purchaseDate = $asset->purchase_date ?? $asset->created_at;
        $monthsOwned = now()->diffInMonths($purchaseDate);
        $monthlyDepreciation = ($asset->purchase_price * $asset->depreciation_rate) / 100 / 12;
        return $monthlyDepreciation * $monthsOwned;
    }
}
