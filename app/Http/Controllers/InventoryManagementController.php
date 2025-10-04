<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InventoryManagementService;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\MaintenanceSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InventoryManagementController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryManagementService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
        $this->middleware('auth');
    }

    /**
     * Display the inventory management dashboard
     */
    public function dashboard(Request $request)
    {
        $filters = $request->only(['category', 'location', 'status']);
        $dashboardData = $this->inventoryService->getDashboardData($filters);
        
        return view('inventory.dashboard', compact('dashboardData', 'filters'));
    }

    /**
     * Get low stock alerts
     */
    public function lowStockAlerts(Request $request)
    {
        $severity = $request->get('severity', 'all'); // all, critical, warning
        $category = $request->get('category');
        
        $alerts = $this->inventoryService->getLowStockAlerts($severity, $category);
        
        if ($request->ajax()) {
            return response()->json($alerts);
        }
        
        return view('inventory.low-stock-alerts', compact('alerts', 'severity', 'category'));
    }

    /**
     * Process purchase order automation
     */
    public function purchaseOrderAutomation(Request $request)
    {
        $action = $request->get('action', 'view'); // view, generate, approve
        $filters = $request->only(['status', 'vendor', 'priority']);
        
        if ($action === 'generate') {
            try {
                $result = $this->inventoryService->generateAutomaticPurchaseOrders();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase orders generated successfully',
                    'generated_orders' => $result['generated_orders'],
                    'total_amount' => $result['total_amount']
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate purchase orders: ' . $e->getMessage()
                ], 500);
            }
        }
        
        $automationData = $this->inventoryService->getPurchaseOrderAutomationData($filters);
        
        if ($request->ajax()) {
            return response()->json($automationData);
        }
        
        return view('inventory.purchase-order-automation', compact('automationData', 'filters'));
    }

    /**
     * Vendor management
     */
    public function vendorManagement(Request $request)
    {
        $filters = $request->only(['status', 'rating', 'location']);
        $vendorData = $this->inventoryService->getVendorManagementData($filters);
        
        if ($request->ajax()) {
            return response()->json($vendorData);
        }
        
        return view('inventory.vendor-management', compact('vendorData', 'filters'));
    }

    /**
     * Get vendor performance analytics
     */
    public function vendorPerformance(Request $request)
    {
        $vendorId = $request->get('vendor_id');
        $period = $request->get('period', '12_months');
        
        $performance = $this->inventoryService->getVendorPerformance($vendorId, $period);
        
        return response()->json($performance);
    }

    /**
     * Asset depreciation tracking
     */
    public function assetDepreciation(Request $request)
    {
        $filters = $request->only(['category', 'location', 'depreciation_method']);
        $depreciationData = $this->inventoryService->getAssetDepreciationData($filters);
        
        if ($request->ajax()) {
            return response()->json($depreciationData);
        }
        
        return view('inventory.asset-depreciation', compact('depreciationData', 'filters'));
    }

    /**
     * Calculate asset depreciation
     */
    public function calculateDepreciation(Request $request)
    {
        $assetId = $request->get('asset_id');
        $method = $request->get('method', 'straight_line');
        $asOfDate = $request->get('as_of_date', now());
        
        try {
            $depreciation = $this->inventoryService->calculateAssetDepreciation($assetId, $method, $asOfDate);
            
            return response()->json([
                'success' => true,
                'depreciation' => $depreciation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate depreciation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Maintenance scheduling
     */
    public function maintenanceScheduling(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'type', 'assigned_to']);
        $maintenanceData = $this->inventoryService->getMaintenanceSchedulingData($filters);
        
        if ($request->ajax()) {
            return response()->json($maintenanceData);
        }
        
        return view('inventory.maintenance-scheduling', compact('maintenanceData', 'filters'));
    }

    /**
     * Create maintenance schedule
     */
    public function createMaintenanceSchedule(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'maintenance_type' => 'required|in:preventive,corrective,emergency,routine',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date|after:now',
            'priority' => 'required|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_duration' => 'nullable|integer|min:1',
            'frequency' => 'nullable|in:daily,weekly,monthly,quarterly,yearly',
            'frequency_interval' => 'nullable|integer|min:1',
            'requires_downtime' => 'boolean'
        ]);

        try {
            $schedule = $this->inventoryService->createMaintenanceSchedule($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Maintenance schedule created successfully',
                'schedule' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create maintenance schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update maintenance schedule
     */
    public function updateMaintenanceSchedule(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'sometimes|date',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_duration' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled'
        ]);

        try {
            $schedule = $this->inventoryService->updateMaintenanceSchedule($id, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Maintenance schedule updated successfully',
                'schedule' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get maintenance reminders
     */
    public function maintenanceReminders(Request $request)
    {
        $period = $request->get('period', 'upcoming'); // upcoming, overdue, today, this_week
        $reminders = $this->inventoryService->getMaintenanceReminders($period);
        
        return response()->json($reminders);
    }

    /**
     * Send maintenance reminders
     */
    public function sendMaintenanceReminders(Request $request)
    {
        $scheduleIds = $request->get('schedule_ids', []);
        
        try {
            $result = $this->inventoryService->sendMaintenanceReminders($scheduleIds);
            
            return response()->json([
                'success' => true,
                'message' => 'Reminders sent successfully',
                'sent_count' => $result['sent_count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reminders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inventory analytics
     */
    public function inventoryAnalytics(Request $request)
    {
        $period = $request->get('period', '12_months');
        $category = $request->get('category');
        
        $analytics = $this->inventoryService->getInventoryAnalytics($period, $category);
        
        return response()->json($analytics);
    }

    /**
     * Get stock movement history
     */
    public function stockMovementHistory(Request $request)
    {
        $itemId = $request->get('item_id');
        $period = $request->get('period', '3_months');
        $movementType = $request->get('movement_type'); // in, out, adjustment
        
        $history = $this->inventoryService->getStockMovementHistory($itemId, $period, $movementType);
        
        return response()->json($history);
    }

    /**
     * Process stock adjustment
     */
    public function processStockAdjustment(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'adjustment_type' => 'required|in:increase,decrease,set',
            'quantity' => 'required|numeric',
            'reason' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        try {
            $result = $this->inventoryService->processStockAdjustment($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment processed successfully',
                'adjustment' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process stock adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get purchase order recommendations
     */
    public function purchaseOrderRecommendations(Request $request)
    {
        $category = $request->get('category');
        $urgency = $request->get('urgency', 'all'); // all, urgent, normal
        
        $recommendations = $this->inventoryService->getPurchaseOrderRecommendations($category, $urgency);
        
        return response()->json($recommendations);
    }

    /**
     * Create purchase order from recommendations
     */
    public function createPurchaseOrderFromRecommendations(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'priority' => 'required|in:low,medium,high,urgent',
            'delivery_date' => 'required|date|after:today',
            'notes' => 'nullable|string'
        ]);

        try {
            $purchaseOrder = $this->inventoryService->createPurchaseOrderFromRecommendations($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'purchase_order' => $purchaseOrder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get asset lifecycle data
     */
    public function assetLifecycle(Request $request)
    {
        $assetId = $request->get('asset_id');
        $lifecycle = $this->inventoryService->getAssetLifecycleData($assetId);
        
        return response()->json($lifecycle);
    }

    /**
     * Export inventory report
     */
    public function exportReport(Request $request)
    {
        $format = $request->get('format', 'excel'); // excel, pdf, csv
        $reportType = $request->get('report_type', 'inventory'); // inventory, low_stock, depreciation, maintenance
        $filters = $request->only(['category', 'location', 'status', 'period']);
        
        try {
            $filePath = $this->inventoryService->exportReport($reportType, $format, $filters);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export report: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory valuation
     */
    public function inventoryValuation(Request $request)
    {
        $method = $request->get('method', 'current_cost'); // current_cost, average_cost, fifo, lifo
        $category = $request->get('category');
        $asOfDate = $request->get('as_of_date', now());
        
        $valuation = $this->inventoryService->getInventoryValuation($method, $category, $asOfDate);
        
        return response()->json($valuation);
    }

    /**
     * Get reorder point suggestions
     */
    public function reorderPointSuggestions(Request $request)
    {
        $itemId = $request->get('item_id');
        $suggestions = $this->inventoryService->getReorderPointSuggestions($itemId);
        
        return response()->json($suggestions);
    }

    /**
     * Update reorder points
     */
    public function updateReorderPoints(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.item_id' => 'required|exists:inventory_items,id',
            'updates.*.minimum_stock_level' => 'required|numeric|min:0',
            'updates.*.reorder_quantity' => 'required|numeric|min:1'
        ]);

        try {
            $result = $this->inventoryService->updateReorderPoints($request->updates);
            
            return response()->json([
                'success' => true,
                'message' => 'Reorder points updated successfully',
                'updated_count' => $result['updated_count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reorder points: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inventory turnover analysis
     */
    public function inventoryTurnover(Request $request)
    {
        $period = $request->get('period', '12_months');
        $category = $request->get('category');
        
        $turnover = $this->inventoryService->getInventoryTurnoverAnalysis($period, $category);
        
        return response()->json($turnover);
    }

    /**
     * Get dead stock analysis
     */
    public function deadStockAnalysis(Request $request)
    {
        $threshold = $request->get('threshold', 180); // days without movement
        $category = $request->get('category');
        
        $deadStock = $this->inventoryService->getDeadStockAnalysis($threshold, $category);
        
        return response()->json($deadStock);
    }

    /**
     * Get inventory notifications
     */
    public function getNotifications(Request $request)
    {
        $type = $request->get('type', 'all'); // all, low_stock, maintenance, purchase_order
        $limit = $request->get('limit', 10);
        
        $notifications = $this->inventoryService->getInventoryNotifications($type, $limit);
        
        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Request $request)
    {
        $notificationId = $request->get('notification_id');
        $result = $this->inventoryService->markNotificationAsRead($notificationId);
        
        return response()->json(['success' => $result]);
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request)
    {
        $cacheKeys = $request->get('cache_keys', []);
        
        if (empty($cacheKeys)) {
            // Clear all inventory-related cache
            Cache::tags(['inventory_management'])->flush();
        } else {
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
        }
        
        return response()->json(['success' => true, 'message' => 'Cache cleared successfully']);
    }

    /**
     * Get inventory settings
     */
    public function getSettings()
    {
        $settings = $this->inventoryService->getInventorySettings();
        
        return response()->json($settings);
    }

    /**
     * Update inventory settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'auto_reorder' => 'boolean',
            'low_stock_threshold' => 'numeric|min:0',
            'maintenance_reminder_days' => 'integer|min:1',
            'depreciation_method' => 'in:straight_line,declining_balance,sum_of_years',
            'notification_preferences' => 'array'
        ]);

        try {
            $settings = $this->inventoryService->updateInventorySettings($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }
}