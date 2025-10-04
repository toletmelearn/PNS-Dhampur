<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\InventoryItem;
use App\Models\Vendor;
use App\Services\PurchaseOrderAutomationService;
use App\Jobs\AutoGeneratePurchaseOrder;
use App\Notifications\PurchaseOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderAutomationController extends Controller
{
    protected $automationService;

    public function __construct(PurchaseOrderAutomationService $automationService)
    {
        $this->automationService = $automationService;
        $this->middleware('auth');
    }

    /**
     * Display purchase order automation dashboard
     */
    public function dashboard()
    {
        try {
            $data = $this->automationService->getDashboardData();
            
            return view('inventory.purchase-order-automation', [
                'summary' => $data['summary'],
                'pendingApprovals' => $data['pending_approvals'],
                'automationStats' => $data['automation_stats'],
                'vendorPerformance' => $data['vendor_performance'],
                'recentActivity' => $data['recent_activity'],
                'lowStockItems' => $data['low_stock_items']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load PO automation dashboard', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }

    /**
     * Auto-generate purchase orders for low stock items
     */
    public function autoGenerate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_ids' => 'sometimes|array',
            'item_ids.*' => 'exists:inventory_items,id',
            'vendor_id' => 'sometimes|exists:vendors,id',
            'auto_approve_limit' => 'sometimes|numeric|min:0',
            'priority' => 'sometimes|in:low,medium,high,urgent'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $options = [
                'auto_approve_limit' => $request->input('auto_approve_limit', 10000),
                'priority' => $request->input('priority', 'medium'),
                'send_notifications' => true,
                'update_cache' => true
            ];

            if ($request->has('item_ids')) {
                // Generate for specific items
                AutoGeneratePurchaseOrder::dispatchForItems(
                    $request->input('item_ids'),
                    $request->input('vendor_id'),
                    $options
                );
                
                $message = 'Purchase order generation started for selected items.';
            } else {
                // Generate for all low stock items
                AutoGeneratePurchaseOrder::dispatchForLowStock($options);
                $message = 'Purchase order generation started for all low stock items.';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start auto PO generation', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start purchase order generation. Please try again.'
            ], 500);
        }
    }

    /**
     * Process purchase order approval
     */
    public function processApproval(Request $request, $poId)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject,request_changes,escalate',
            'comments' => 'sometimes|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->automationService->processApprovalWorkflow(
                $poId,
                $request->input('action'),
                Auth::id(),
                $request->input('comments')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to process PO approval', [
                'po_id' => $poId,
                'action' => $request->input('action'),
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send purchase order to vendor
     */
    public function sendToVendor($poId)
    {
        try {
            $po = PurchaseOrder::findOrFail($poId);
            
            // Check permissions
            if (!Auth::user()->can('send_purchase_orders')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to send purchase orders.'
                ], 403);
            }

            $updatedPO = $this->automationService->sendToVendor($po);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order sent to vendor successfully.',
                'po' => $updatedPO
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send PO to vendor', [
                'po_id' => $poId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track purchase order delivery
     */
    public function trackDelivery($poId)
    {
        try {
            $deliveryInfo = $this->automationService->trackDelivery($poId);

            return response()->json([
                'success' => true,
                'delivery_info' => $deliveryInfo
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track PO delivery', [
                'po_id' => $poId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track delivery information.'
            ], 500);
        }
    }

    /**
     * Process item receipt
     */
    public function processReceipt(Request $request, $poId)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.notes' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->automationService->processItemReceipt(
                $poId,
                $request->input('items')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to process item receipt', [
                'po_id' => $poId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor recommendations for items
     */
    public function getVendorRecommendations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:inventory_items,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recommendations = $this->automationService->getVendorRecommendations(
                $request->input('item_ids')
            );

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get vendor recommendations', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get vendor recommendations.'
            ], 500);
        }
    }

    /**
     * Get purchase order recommendations
     */
    public function getRecommendations()
    {
        try {
            $recommendations = $this->automationService->generatePORecommendations();

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get PO recommendations', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get recommendations.'
            ], 500);
        }
    }

    /**
     * Get pending approvals for current user
     */
    public function getPendingApprovals()
    {
        try {
            $user = Auth::user();
            
            // Get POs that require approval based on user's authority
            $approvalLimits = [
                'admin' => PHP_INT_MAX,
                'manager' => 100000,
                'supervisor' => 50000,
                'team_lead' => 25000,
                'employee' => 5000
            ];

            $userLimit = $approvalLimits[$user->role] ?? 0;

            $pendingApprovals = PurchaseOrder::pending()
                ->where('total_amount', '<=', $userLimit)
                ->with(['vendor', 'requestedBy', 'items.inventoryItem'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'pending_approvals' => $pendingApprovals
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get pending approvals', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending approvals.'
            ], 500);
        }
    }

    /**
     * Get automation statistics
     */
    public function getAutomationStats(Request $request)
    {
        try {
            $period = $request->input('period', 'month'); // month, quarter, year
            
            $stats = [];
            
            switch ($period) {
                case 'month':
                    $stats = $this->getMonthlyStats();
                    break;
                case 'quarter':
                    $stats = $this->getQuarterlyStats();
                    break;
                case 'year':
                    $stats = $this->getYearlyStats();
                    break;
            }

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get automation stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get automation statistics.'
            ], 500);
        }
    }

    /**
     * Bulk approve purchase orders
     */
    public function bulkApprove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'po_ids' => 'required|array|min:1',
            'po_ids.*' => 'exists:purchase_orders,id',
            'comments' => 'sometimes|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($request->input('po_ids') as $poId) {
                try {
                    $result = $this->automationService->processApprovalWorkflow(
                        $poId,
                        'approve',
                        Auth::id(),
                        $request->input('comments')
                    );
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                    
                    $results[$poId] = $result;

                } catch (\Exception $e) {
                    $failureCount++;
                    $results[$poId] = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => "Approved {$successCount} purchase orders. {$failureCount} failed.",
                'results' => $results,
                'summary' => [
                    'total' => count($request->input('po_ids')),
                    'success' => $successCount,
                    'failure' => $failureCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to bulk approve POs', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk approval.'
            ], 500);
        }
    }

    /**
     * Get low stock items for PO generation
     */
    public function getLowStockItems()
    {
        try {
            $lowStockItems = InventoryItem::where('current_stock', '<=', DB::raw('reorder_point'))
                ->where('is_active', true)
                ->with(['preferredVendor', 'category'])
                ->orderBy('current_stock', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'sku' => $item->sku,
                        'current_stock' => $item->current_stock,
                        'reorder_point' => $item->reorder_point,
                        'max_stock_level' => $item->max_stock_level,
                        'unit_cost' => $item->unit_cost,
                        'last_purchase_price' => $item->last_purchase_price,
                        'preferred_vendor' => $item->preferredVendor ? [
                            'id' => $item->preferredVendor->id,
                            'name' => $item->preferredVendor->name,
                            'rating' => $item->preferredVendor->rating
                        ] : null,
                        'category' => $item->category->name ?? 'Uncategorized',
                        'stock_status' => $item->current_stock <= 0 ? 'out_of_stock' : 'low_stock',
                        'recommended_quantity' => max($item->max_stock_level - $item->current_stock, $item->reorder_point * 2, 10)
                    ];
                });

            return response()->json([
                'success' => true,
                'items' => $lowStockItems,
                'total_count' => $lowStockItems->count(),
                'out_of_stock_count' => $lowStockItems->where('stock_status', 'out_of_stock')->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get low stock items', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get low stock items.'
            ], 500);
        }
    }

    /**
     * Helper methods for statistics
     */
    private function getMonthlyStats()
    {
        $currentMonth = now()->startOfMonth();
        
        return [
            'total_pos' => PurchaseOrder::whereMonth('created_at', $currentMonth->month)->count(),
            'auto_generated' => PurchaseOrder::whereMonth('created_at', $currentMonth->month)
                ->where('notes', 'like', '%Auto-generated%')->count(),
            'approved' => PurchaseOrder::whereMonth('approved_at', $currentMonth->month)->count(),
            'completed' => PurchaseOrder::completed()->whereMonth('updated_at', $currentMonth->month)->count(),
            'total_value' => PurchaseOrder::whereMonth('created_at', $currentMonth->month)->sum('total_amount'),
            'avg_approval_time' => $this->getAverageApprovalTime($currentMonth, $currentMonth->copy()->endOfMonth())
        ];
    }

    private function getQuarterlyStats()
    {
        $currentQuarter = now()->startOfQuarter();
        $endQuarter = now()->endOfQuarter();
        
        return [
            'total_pos' => PurchaseOrder::whereBetween('created_at', [$currentQuarter, $endQuarter])->count(),
            'auto_generated' => PurchaseOrder::whereBetween('created_at', [$currentQuarter, $endQuarter])
                ->where('notes', 'like', '%Auto-generated%')->count(),
            'approved' => PurchaseOrder::whereBetween('approved_at', [$currentQuarter, $endQuarter])->count(),
            'completed' => PurchaseOrder::completed()->whereBetween('updated_at', [$currentQuarter, $endQuarter])->count(),
            'total_value' => PurchaseOrder::whereBetween('created_at', [$currentQuarter, $endQuarter])->sum('total_amount'),
            'avg_approval_time' => $this->getAverageApprovalTime($currentQuarter, $endQuarter)
        ];
    }

    private function getYearlyStats()
    {
        $currentYear = now()->startOfYear();
        $endYear = now()->endOfYear();
        
        return [
            'total_pos' => PurchaseOrder::whereBetween('created_at', [$currentYear, $endYear])->count(),
            'auto_generated' => PurchaseOrder::whereBetween('created_at', [$currentYear, $endYear])
                ->where('notes', 'like', '%Auto-generated%')->count(),
            'approved' => PurchaseOrder::whereBetween('approved_at', [$currentYear, $endYear])->count(),
            'completed' => PurchaseOrder::completed()->whereBetween('updated_at', [$currentYear, $endYear])->count(),
            'total_value' => PurchaseOrder::whereBetween('created_at', [$currentYear, $endYear])->sum('total_amount'),
            'avg_approval_time' => $this->getAverageApprovalTime($currentYear, $endYear)
        ];
    }

    private function getAverageApprovalTime($startDate, $endDate)
    {
        $approvedPOs = PurchaseOrder::approved()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('approved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours'))
            ->first();

        return $approvedPOs->avg_hours ?? 0;
    }
}