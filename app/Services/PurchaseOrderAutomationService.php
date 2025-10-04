<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\InventoryItem;
use App\Models\Vendor;
use App\Models\User;
use App\Notifications\PurchaseOrderNotification;
use App\Jobs\AutoGeneratePurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class PurchaseOrderAutomationService
{
    /**
     * Get purchase order automation dashboard data
     */
    public function getDashboardData()
    {
        return Cache::remember('po_automation_dashboard', 300, function () {
            $summary = $this->getPurchaseOrderSummary();
            $pendingApprovals = $this->getPendingApprovals();
            $automationStats = $this->getAutomationStats();
            $vendorPerformance = $this->getVendorPerformance();
            $recentActivity = $this->getRecentActivity();
            $lowStockItems = $this->getLowStockItems();
            
            return [
                'summary' => $summary,
                'pending_approvals' => $pendingApprovals,
                'automation_stats' => $automationStats,
                'vendor_performance' => $vendorPerformance,
                'recent_activity' => $recentActivity,
                'low_stock_items' => $lowStockItems
            ];
        });
    }

    /**
     * Auto-generate purchase orders for low stock items
     */
    public function autoGeneratePurchaseOrders()
    {
        $lowStockItems = InventoryItem::where('current_stock', '<=', DB::raw('reorder_point'))
            ->where('is_active', true)
            ->with(['preferredVendor'])
            ->get();

        $generatedPOs = [];
        $vendorGroups = $lowStockItems->groupBy('preferred_vendor_id');

        foreach ($vendorGroups as $vendorId => $items) {
            if (!$vendorId) {
                continue; // Skip items without preferred vendor
            }

            $vendor = Vendor::find($vendorId);
            if (!$vendor || !$vendor->isActive()) {
                continue;
            }

            $po = $this->createAutomaticPurchaseOrder($vendor, $items);
            if ($po) {
                $generatedPOs[] = $po;
            }
        }

        Log::info('Auto-generated purchase orders', [
            'count' => count($generatedPOs),
            'po_numbers' => collect($generatedPOs)->pluck('po_number')->toArray()
        ]);

        return $generatedPOs;
    }

    /**
     * Create automatic purchase order for vendor and items
     */
    private function createAutomaticPurchaseOrder($vendor, $items)
    {
        try {
            DB::beginTransaction();

            $po = PurchaseOrder::create([
                'vendor_id' => $vendor->id,
                'requested_by' => $this->getSystemUserId(),
                'order_date' => now(),
                'expected_delivery_date' => now()->addDays($vendor->lead_time_days ?? 7),
                'status' => 'pending',
                'priority' => $this->calculatePriority($items),
                'delivery_address' => $this->getDefaultDeliveryAddress(),
                'terms_and_conditions' => $vendor->payment_terms,
                'notes' => 'Auto-generated based on low stock levels',
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 0
            ]);

            foreach ($items as $item) {
                $reorderQuantity = $this->calculateReorderQuantity($item);
                $unitPrice = $item->last_purchase_price ?? $item->unit_cost ?? 0;

                $po->addItem(
                    $item->id,
                    $reorderQuantity,
                    $unitPrice,
                    "Auto-reorder for low stock (Current: {$item->current_stock}, Reorder Point: {$item->reorder_point})"
                );
            }

            // Apply vendor-specific discount if available
            if ($vendor->discount_percentage > 0) {
                $po->discount_amount = $po->subtotal * ($vendor->discount_percentage / 100);
                $po->calculateTotals();
            }

            DB::commit();

            // Send notification for approval
            $this->sendApprovalNotification($po);

            return $po;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create automatic purchase order', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process purchase order approval workflow
     */
    public function processApprovalWorkflow($poId, $action, $userId, $comments = null)
    {
        $po = PurchaseOrder::findOrFail($poId);
        $user = User::findOrFail($userId);

        switch ($action) {
            case 'approve':
                return $this->approvePurchaseOrder($po, $user, $comments);
            case 'reject':
                return $this->rejectPurchaseOrder($po, $user, $comments);
            case 'request_changes':
                return $this->requestChanges($po, $user, $comments);
            case 'escalate':
                return $this->escalateApproval($po, $user, $comments);
            default:
                throw new \InvalidArgumentException('Invalid approval action');
        }
    }

    /**
     * Approve purchase order
     */
    private function approvePurchaseOrder($po, $approver, $comments)
    {
        if (!$po->canBeApproved()) {
            throw new \Exception('Purchase order cannot be approved in current status');
        }

        // Check approval authority
        if (!$this->hasApprovalAuthority($approver, $po->total_amount)) {
            return $this->escalateApproval($po, $approver, $comments);
        }

        $po->approve($approver->id);
        
        // Add approval comments
        if ($comments) {
            $po->notes = ($po->notes ? $po->notes . "\n" : '') . 
                        "Approved by {$approver->name}: {$comments}";
            $po->save();
        }

        // Send to vendor automatically if configured
        if ($this->shouldAutoSendToVendor($po)) {
            $this->sendToVendor($po);
        }

        // Notify relevant parties
        $this->sendApprovalNotification($po, 'approved');

        Log::info('Purchase order approved', [
            'po_number' => $po->po_number,
            'approved_by' => $approver->name,
            'total_amount' => $po->total_amount
        ]);

        return [
            'success' => true,
            'message' => 'Purchase order approved successfully',
            'po' => $po
        ];
    }

    /**
     * Reject purchase order
     */
    private function rejectPurchaseOrder($po, $rejector, $reason)
    {
        if (!$po->canBeRejected()) {
            throw new \Exception('Purchase order cannot be rejected in current status');
        }

        $po->reject($reason, $rejector->id);

        // Notify requester and other stakeholders
        $this->sendApprovalNotification($po, 'rejected');

        Log::info('Purchase order rejected', [
            'po_number' => $po->po_number,
            'rejected_by' => $rejector->name,
            'reason' => $reason
        ]);

        return [
            'success' => true,
            'message' => 'Purchase order rejected',
            'po' => $po
        ];
    }

    /**
     * Request changes to purchase order
     */
    private function requestChanges($po, $reviewer, $comments)
    {
        $po->status = 'changes_requested';
        $po->notes = ($po->notes ? $po->notes . "\n" : '') . 
                    "Changes requested by {$reviewer->name}: {$comments}";
        $po->save();

        // Notify requester
        $this->sendApprovalNotification($po, 'changes_requested');

        return [
            'success' => true,
            'message' => 'Changes requested for purchase order',
            'po' => $po
        ];
    }

    /**
     * Escalate approval to higher authority
     */
    private function escalateApproval($po, $escalator, $comments)
    {
        $higherAuthority = $this->getHigherApprovalAuthority($escalator, $po->total_amount);
        
        if (!$higherAuthority) {
            throw new \Exception('No higher approval authority found');
        }

        $po->notes = ($po->notes ? $po->notes . "\n" : '') . 
                    "Escalated by {$escalator->name}: {$comments}";
        $po->save();

        // Notify higher authority
        $higherAuthority->notify(new PurchaseOrderNotification($po, 'escalated'));

        Log::info('Purchase order escalated', [
            'po_number' => $po->po_number,
            'escalated_by' => $escalator->name,
            'escalated_to' => $higherAuthority->name
        ]);

        return [
            'success' => true,
            'message' => 'Purchase order escalated for approval',
            'escalated_to' => $higherAuthority->name,
            'po' => $po
        ];
    }

    /**
     * Send purchase order to vendor
     */
    public function sendToVendor($po)
    {
        if (!$po->canBeSent()) {
            throw new \Exception('Purchase order cannot be sent in current status');
        }

        $po->send();

        // Generate and send PO document (email, PDF, etc.)
        $this->generateAndSendPODocument($po);

        // Update vendor statistics
        $po->vendor->increment('total_orders');
        $po->vendor->increment('total_purchase_amount', $po->total_amount);
        $po->vendor->update(['last_order_date' => now()]);

        Log::info('Purchase order sent to vendor', [
            'po_number' => $po->po_number,
            'vendor' => $po->vendor->name
        ]);

        return $po;
    }

    /**
     * Track purchase order delivery
     */
    public function trackDelivery($poId)
    {
        $po = PurchaseOrder::with(['items.inventoryItem', 'vendor'])->findOrFail($poId);
        
        $deliveryInfo = [
            'po_number' => $po->po_number,
            'vendor' => $po->vendor->name,
            'order_date' => $po->order_date,
            'expected_delivery_date' => $po->expected_delivery_date,
            'actual_delivery_date' => $po->actual_delivery_date,
            'status' => $po->status,
            'delivery_status' => $po->delivery_status,
            'is_overdue' => $po->is_overdue,
            'days_overdue' => $po->days_overdue,
            'completion_percentage' => $po->completion_percentage,
            'items' => $po->items->map(function ($item) {
                return [
                    'name' => $item->inventoryItem->name,
                    'quantity_ordered' => $item->quantity_ordered,
                    'quantity_received' => $item->quantity_received,
                    'quantity_pending' => $item->quantity_pending,
                    'status' => $item->status,
                    'received_date' => $item->received_date
                ];
            })
        ];

        return $deliveryInfo;
    }

    /**
     * Process item receipt
     */
    public function processItemReceipt($poId, $itemReceipts)
    {
        $po = PurchaseOrder::findOrFail($poId);
        
        if (!$po->canReceiveItems()) {
            throw new \Exception('Cannot receive items for this purchase order');
        }

        DB::beginTransaction();
        
        try {
            foreach ($itemReceipts as $receipt) {
                $po->receiveItem(
                    $receipt['item_id'],
                    $receipt['quantity_received'],
                    $receipt['notes'] ?? null
                );
            }

            DB::commit();

            // Check if PO is fully received
            if ($po->fresh()->status === 'completed') {
                $this->handlePOCompletion($po);
            }

            return [
                'success' => true,
                'message' => 'Items received successfully',
                'po' => $po->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get vendor recommendations for items
     */
    public function getVendorRecommendations($itemIds)
    {
        $items = InventoryItem::whereIn('id', $itemIds)->get();
        $recommendations = [];

        foreach ($items as $item) {
            $vendors = $this->findSuitableVendors($item);
            $recommendations[$item->id] = [
                'item' => $item,
                'recommended_vendors' => $vendors->take(3),
                'preferred_vendor' => $item->preferredVendor
            ];
        }

        return $recommendations;
    }

    /**
     * Generate purchase order recommendations
     */
    public function generatePORecommendations()
    {
        $recommendations = [];

        // Low stock recommendations
        $lowStockItems = InventoryItem::where('current_stock', '<=', DB::raw('reorder_point'))
            ->where('is_active', true)
            ->with(['preferredVendor'])
            ->get();

        if ($lowStockItems->count() > 0) {
            $recommendations[] = [
                'type' => 'low_stock',
                'priority' => 'high',
                'title' => 'Low Stock Items Need Reordering',
                'description' => "{$lowStockItems->count()} items are below reorder point",
                'items' => $lowStockItems,
                'action' => 'auto_generate_po'
            ];
        }

        // Seasonal recommendations
        $seasonalRecommendations = $this->getSeasonalRecommendations();
        if (!empty($seasonalRecommendations)) {
            $recommendations[] = $seasonalRecommendations;
        }

        // Vendor consolidation recommendations
        $consolidationRecommendations = $this->getConsolidationRecommendations();
        if (!empty($consolidationRecommendations)) {
            $recommendations[] = $consolidationRecommendations;
        }

        return $recommendations;
    }

    /**
     * Helper Methods
     */
    private function getPurchaseOrderSummary()
    {
        return [
            'total_pos' => PurchaseOrder::count(),
            'pending_approval' => PurchaseOrder::pending()->count(),
            'approved_today' => PurchaseOrder::approved()->whereDate('approved_at', today())->count(),
            'overdue_deliveries' => PurchaseOrder::overdue()->count(),
            'total_value_this_month' => PurchaseOrder::whereMonth('order_date', now()->month)->sum('total_amount'),
            'auto_generated_this_month' => PurchaseOrder::whereMonth('created_at', now()->month)
                ->where('notes', 'like', '%Auto-generated%')->count()
        ];
    }

    private function getPendingApprovals()
    {
        return PurchaseOrder::pending()
            ->with(['vendor', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getAutomationStats()
    {
        $totalPOs = PurchaseOrder::whereMonth('created_at', now()->month)->count();
        $autoPOs = PurchaseOrder::whereMonth('created_at', now()->month)
            ->where('notes', 'like', '%Auto-generated%')->count();

        return [
            'automation_rate' => $totalPOs > 0 ? round(($autoPOs / $totalPOs) * 100, 2) : 0,
            'auto_generated_count' => $autoPOs,
            'manual_count' => $totalPOs - $autoPOs,
            'time_saved_hours' => $autoPOs * 0.5 // Assuming 30 minutes saved per auto PO
        ];
    }

    private function getVendorPerformance()
    {
        return Vendor::active()
            ->select([
                'id', 'name', 'total_orders', 'total_purchase_amount', 'rating',
                DB::raw('(SELECT COUNT(*) FROM purchase_orders WHERE vendor_id = vendors.id AND status = "completed" AND actual_delivery_date <= expected_delivery_date) as on_time_deliveries'),
                DB::raw('(SELECT COUNT(*) FROM purchase_orders WHERE vendor_id = vendors.id AND status = "completed") as total_completed_orders')
            ])
            ->having('total_orders', '>', 0)
            ->orderBy('rating', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($vendor) {
                $vendor->on_time_percentage = $vendor->total_completed_orders > 0 
                    ? round(($vendor->on_time_deliveries / $vendor->total_completed_orders) * 100, 2) 
                    : 0;
                return $vendor;
            });
    }

    private function getRecentActivity()
    {
        return PurchaseOrder::with(['vendor', 'requestedBy', 'approvedBy'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getLowStockItems()
    {
        return InventoryItem::where('current_stock', '<=', DB::raw('reorder_point'))
            ->where('is_active', true)
            ->with(['preferredVendor'])
            ->orderBy('current_stock', 'asc')
            ->limit(10)
            ->get();
    }

    private function calculatePriority($items)
    {
        $criticalCount = $items->where('current_stock', '<=', 0)->count();
        $lowStockCount = $items->where('current_stock', '>', 0)
            ->where('current_stock', '<=', DB::raw('reorder_point * 0.5'))->count();

        if ($criticalCount > 0) {
            return 'urgent';
        } elseif ($lowStockCount > 0) {
            return 'high';
        } else {
            return 'medium';
        }
    }

    private function calculateReorderQuantity($item)
    {
        // Use economic order quantity if available, otherwise use max stock level
        if ($item->economic_order_quantity > 0) {
            return $item->economic_order_quantity;
        }
        
        if ($item->max_stock_level > 0) {
            return $item->max_stock_level - $item->current_stock;
        }
        
        // Default to 3 months of average consumption
        return max($item->reorder_point * 2, 10);
    }

    private function hasApprovalAuthority($user, $amount)
    {
        // Define approval limits based on user role
        $approvalLimits = [
            'admin' => PHP_INT_MAX,
            'manager' => 100000,
            'supervisor' => 50000,
            'team_lead' => 25000,
            'employee' => 5000
        ];

        $userLimit = $approvalLimits[$user->role] ?? 0;
        return $amount <= $userLimit;
    }

    private function getHigherApprovalAuthority($user, $amount)
    {
        // Find user with higher approval authority
        $hierarchy = ['employee', 'team_lead', 'supervisor', 'manager', 'admin'];
        $currentIndex = array_search($user->role, $hierarchy);
        
        if ($currentIndex === false || $currentIndex >= count($hierarchy) - 1) {
            return null;
        }

        $higherRoles = array_slice($hierarchy, $currentIndex + 1);
        
        return User::whereIn('role', $higherRoles)
            ->where('is_active', true)
            ->first();
    }

    private function shouldAutoSendToVendor($po)
    {
        // Auto-send for low-value orders or trusted vendors
        return $po->total_amount <= 10000 || $po->vendor->rating >= 4.5;
    }

    private function getSystemUserId()
    {
        // Return system user ID or admin user ID for auto-generated POs
        return User::where('email', 'system@company.com')->first()->id ?? 1;
    }

    private function getDefaultDeliveryAddress()
    {
        return "Main Warehouse\nCompany Address\nCity, State, ZIP";
    }

    private function sendApprovalNotification($po, $type = 'pending')
    {
        // Implementation for sending notifications
        // This would send emails/notifications to relevant users
    }

    private function generateAndSendPODocument($po)
    {
        // Implementation for generating and sending PO document
        // This would create PDF and send via email
    }

    private function handlePOCompletion($po)
    {
        // Update vendor performance metrics
        $po->vendor->updateRating();
        
        // Log completion
        Log::info('Purchase order completed', [
            'po_number' => $po->po_number,
            'vendor' => $po->vendor->name,
            'total_amount' => $po->total_amount
        ]);
    }

    private function findSuitableVendors($item)
    {
        // Find vendors that can supply this item
        return Vendor::active()
            ->where('rating', '>=', 3.0)
            ->orderBy('rating', 'desc')
            ->get();
    }

    private function getSeasonalRecommendations()
    {
        // Implementation for seasonal purchasing recommendations
        return [];
    }

    private function getConsolidationRecommendations()
    {
        // Implementation for vendor consolidation recommendations
        return [];
    }
}