<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\User;
use App\Services\PurchaseOrderAutomationService;
use App\Notifications\PurchaseOrderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGeneratePurchaseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventoryItemIds;
    protected $vendorId;
    protected $options;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(array $inventoryItemIds = [], int $vendorId = null, array $options = [])
    {
        $this->inventoryItemIds = $inventoryItemIds;
        $this->vendorId = $vendorId;
        $this->options = array_merge([
            'auto_approve_limit' => 10000,
            'send_notifications' => true,
            'update_cache' => true,
            'priority' => 'medium'
        ], $options);
    }

    /**
     * Execute the job.
     */
    public function handle(PurchaseOrderAutomationService $automationService): void
    {
        Log::info('Starting auto purchase order generation', [
            'inventory_items' => count($this->inventoryItemIds),
            'vendor_id' => $this->vendorId,
            'options' => $this->options
        ]);

        try {
            if (empty($this->inventoryItemIds)) {
                // Generate POs for all low stock items
                $this->generateForAllLowStockItems($automationService);
            } else {
                // Generate POs for specific items
                $this->generateForSpecificItems($automationService);
            }

            // Update cache if requested
            if ($this->options['update_cache']) {
                $this->updateRelatedCache();
            }

            Log::info('Auto purchase order generation completed successfully');

        } catch (\Exception $e) {
            Log::error('Auto purchase order generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Generate purchase orders for all low stock items
     */
    private function generateForAllLowStockItems(PurchaseOrderAutomationService $automationService): void
    {
        $lowStockItems = InventoryItem::where('current_stock', '<=', DB::raw('reorder_point'))
            ->where('is_active', true)
            ->with(['preferredVendor'])
            ->get();

        if ($lowStockItems->isEmpty()) {
            Log::info('No low stock items found for auto PO generation');
            return;
        }

        // Group items by vendor
        $vendorGroups = $lowStockItems->groupBy('preferred_vendor_id');
        $generatedPOs = [];

        foreach ($vendorGroups as $vendorId => $items) {
            if (!$vendorId) {
                Log::warning('Skipping items without preferred vendor', [
                    'item_ids' => $items->pluck('id')->toArray()
                ]);
                continue;
            }

            $vendor = Vendor::find($vendorId);
            if (!$vendor || !$vendor->isActive()) {
                Log::warning('Skipping inactive vendor', ['vendor_id' => $vendorId]);
                continue;
            }

            try {
                $po = $this->createPurchaseOrderForVendor($vendor, $items);
                if ($po) {
                    $generatedPOs[] = $po;
                    
                    // Auto-approve if within limit
                    if ($po->total_amount <= $this->options['auto_approve_limit']) {
                        $this->autoApprovePurchaseOrder($po);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to create PO for vendor', [
                    'vendor_id' => $vendorId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send summary notification
        if ($this->options['send_notifications'] && !empty($generatedPOs)) {
            $this->sendSummaryNotification($generatedPOs);
        }

        Log::info('Generated purchase orders for low stock items', [
            'count' => count($generatedPOs),
            'po_numbers' => collect($generatedPOs)->pluck('po_number')->toArray()
        ]);
    }

    /**
     * Generate purchase orders for specific items
     */
    private function generateForSpecificItems(PurchaseOrderAutomationService $automationService): void
    {
        $items = InventoryItem::whereIn('id', $this->inventoryItemIds)
            ->where('is_active', true)
            ->with(['preferredVendor'])
            ->get();

        if ($items->isEmpty()) {
            Log::warning('No valid items found for PO generation', [
                'requested_ids' => $this->inventoryItemIds
            ]);
            return;
        }

        // If vendor is specified, use it; otherwise group by preferred vendor
        if ($this->vendorId) {
            $vendor = Vendor::find($this->vendorId);
            if (!$vendor || !$vendor->isActive()) {
                throw new \Exception("Invalid or inactive vendor: {$this->vendorId}");
            }

            $po = $this->createPurchaseOrderForVendor($vendor, $items);
            if ($po && $this->options['send_notifications']) {
                $this->sendPONotification($po);
            }
        } else {
            // Group by preferred vendor
            $vendorGroups = $items->groupBy('preferred_vendor_id');
            
            foreach ($vendorGroups as $vendorId => $vendorItems) {
                if (!$vendorId) continue;
                
                $vendor = Vendor::find($vendorId);
                if (!$vendor || !$vendor->isActive()) continue;

                $po = $this->createPurchaseOrderForVendor($vendor, $vendorItems);
                if ($po && $this->options['send_notifications']) {
                    $this->sendPONotification($po);
                }
            }
        }
    }

    /**
     * Create purchase order for vendor and items
     */
    private function createPurchaseOrderForVendor(Vendor $vendor, $items): ?PurchaseOrder
    {
        DB::beginTransaction();

        try {
            $po = PurchaseOrder::create([
                'vendor_id' => $vendor->id,
                'requested_by' => $this->getSystemUserId(),
                'order_date' => now(),
                'expected_delivery_date' => now()->addDays($vendor->lead_time_days ?? 7),
                'status' => 'pending',
                'priority' => $this->options['priority'],
                'delivery_address' => $this->getDefaultDeliveryAddress(),
                'terms_and_conditions' => $vendor->payment_terms,
                'notes' => $this->generatePONotes($items),
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 0
            ]);

            // Add items to purchase order
            foreach ($items as $item) {
                $reorderQuantity = $this->calculateReorderQuantity($item);
                $unitPrice = $this->getItemUnitPrice($item);

                $po->addItem(
                    $item->id,
                    $reorderQuantity,
                    $unitPrice,
                    $this->generateItemNotes($item)
                );
            }

            // Apply vendor discount if available
            if ($vendor->discount_percentage > 0) {
                $po->discount_amount = $po->subtotal * ($vendor->discount_percentage / 100);
                $po->calculateTotals();
            }

            // Update vendor statistics
            $vendor->increment('total_orders');
            $vendor->update(['last_order_date' => now()]);

            DB::commit();

            Log::info('Created purchase order', [
                'po_number' => $po->po_number,
                'vendor' => $vendor->name,
                'items_count' => $items->count(),
                'total_amount' => $po->total_amount
            ]);

            return $po;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create purchase order', [
                'vendor_id' => $vendor->id,
                'items_count' => $items->count(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Auto-approve purchase order if within limits
     */
    private function autoApprovePurchaseOrder(PurchaseOrder $po): void
    {
        try {
            $systemUser = $this->getSystemUser();
            $po->approve($systemUser->id);
            
            $po->notes = ($po->notes ? $po->notes . "\n" : '') . 
                        "Auto-approved by system (Amount: ₹{$po->total_amount})";
            $po->save();

            Log::info('Auto-approved purchase order', [
                'po_number' => $po->po_number,
                'amount' => $po->total_amount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to auto-approve purchase order', [
                'po_number' => $po->po_number,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate reorder quantity for item
     */
    private function calculateReorderQuantity(InventoryItem $item): int
    {
        // Use economic order quantity if available
        if ($item->economic_order_quantity > 0) {
            return $item->economic_order_quantity;
        }
        
        // Use max stock level minus current stock
        if ($item->max_stock_level > 0) {
            return max($item->max_stock_level - $item->current_stock, 1);
        }
        
        // Default to reorder point * 2 or minimum 10
        return max($item->reorder_point * 2, 10);
    }

    /**
     * Get unit price for item
     */
    private function getItemUnitPrice(InventoryItem $item): float
    {
        // Priority: last purchase price > unit cost > 0
        return $item->last_purchase_price ?? $item->unit_cost ?? 0;
    }

    /**
     * Generate purchase order notes
     */
    private function generatePONotes($items): string
    {
        $criticalItems = $items->where('current_stock', '<=', 0)->count();
        $lowStockItems = $items->where('current_stock', '>', 0)
            ->where('current_stock', '<=', DB::raw('reorder_point * 0.5'))->count();

        $notes = "Auto-generated purchase order based on stock levels.\n";
        
        if ($criticalItems > 0) {
            $notes .= "Critical: {$criticalItems} items are out of stock.\n";
        }
        
        if ($lowStockItems > 0) {
            $notes .= "Low Stock: {$lowStockItems} items are below 50% of reorder point.\n";
        }

        $notes .= "Generated on: " . now()->format('Y-m-d H:i:s');

        return $notes;
    }

    /**
     * Generate item-specific notes
     */
    private function generateItemNotes(InventoryItem $item): string
    {
        $status = $item->current_stock <= 0 ? 'OUT OF STOCK' : 'LOW STOCK';
        return "Auto-reorder - {$status} (Current: {$item->current_stock}, Reorder Point: {$item->reorder_point})";
    }

    /**
     * Send purchase order notification
     */
    private function sendPONotification(PurchaseOrder $po): void
    {
        try {
            // Notify approvers
            $approvers = $this->getApprovers($po->total_amount);
            foreach ($approvers as $approver) {
                $approver->notify(PurchaseOrderNotification::approvalRequired($po));
            }

            // Notify inventory managers
            $inventoryManagers = User::where('role', 'inventory_manager')
                ->where('is_active', true)
                ->get();
            
            foreach ($inventoryManagers as $manager) {
                $manager->notify(PurchaseOrderNotification::autoGenerated($po, $po->items->count()));
            }

        } catch (\Exception $e) {
            Log::error('Failed to send PO notifications', [
                'po_number' => $po->po_number,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send summary notification for multiple POs
     */
    private function sendSummaryNotification(array $purchaseOrders): void
    {
        try {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            $totalAmount = collect($purchaseOrders)->sum('total_amount');
            $totalItems = collect($purchaseOrders)->sum(function ($po) {
                return $po->items->count();
            });

            foreach ($admins as $admin) {
                // Send custom summary notification
                $admin->notify(new \App\Notifications\DatabaseNotification([
                    'type' => 'auto_po_summary',
                    'title' => 'Auto Purchase Orders Generated',
                    'message' => count($purchaseOrders) . " purchase orders auto-generated for {$totalItems} low stock items. Total value: ₹" . number_format($totalAmount, 2),
                    'data' => [
                        'po_count' => count($purchaseOrders),
                        'total_amount' => $totalAmount,
                        'total_items' => $totalItems,
                        'po_numbers' => collect($purchaseOrders)->pluck('po_number')->toArray()
                    ]
                ]));
            }

        } catch (\Exception $e) {
            Log::error('Failed to send summary notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update related cache
     */
    private function updateRelatedCache(): void
    {
        try {
            // Clear relevant cache keys
            Cache::forget('po_automation_dashboard');
            Cache::forget('inventory_dashboard');
            Cache::forget('low_stock_items');
            Cache::forget('pending_approvals');

            Log::info('Updated related cache after auto PO generation');

        } catch (\Exception $e) {
            Log::error('Failed to update cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get system user ID
     */
    private function getSystemUserId(): int
    {
        return $this->getSystemUser()->id;
    }

    /**
     * Get system user
     */
    private function getSystemUser(): User
    {
        return User::where('email', 'system@company.com')->first() 
            ?? User::where('role', 'admin')->where('is_active', true)->first()
            ?? User::find(1);
    }

    /**
     * Get default delivery address
     */
    private function getDefaultDeliveryAddress(): string
    {
        return "Main Warehouse\nCompany Address\nCity, State, ZIP";
    }

    /**
     * Get approvers based on amount
     */
    private function getApprovers(float $amount): \Illuminate\Database\Eloquent\Collection
    {
        if ($amount > 100000) {
            return User::whereIn('role', ['admin', 'manager'])->where('is_active', true)->get();
        } elseif ($amount > 50000) {
            return User::whereIn('role', ['admin', 'manager', 'supervisor'])->where('is_active', true)->get();
        } else {
            return User::whereIn('role', ['admin', 'manager', 'supervisor', 'team_lead'])->where('is_active', true)->get();
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Auto purchase order generation job failed', [
            'inventory_items' => $this->inventoryItemIds,
            'vendor_id' => $this->vendorId,
            'options' => $this->options,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Notify administrators about the failure
        try {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\DatabaseNotification([
                    'type' => 'job_failure',
                    'title' => 'Auto PO Generation Failed',
                    'message' => 'Automatic purchase order generation job failed. Please check logs and retry manually.',
                    'data' => [
                        'job' => 'AutoGeneratePurchaseOrder',
                        'error' => $exception->getMessage(),
                        'failed_at' => now()->toISOString()
                    ]
                ]));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send job failure notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Static method to dispatch job for all low stock items
     */
    public static function dispatchForLowStock(array $options = []): void
    {
        self::dispatch([], null, $options);
    }

    /**
     * Static method to dispatch job for specific items
     */
    public static function dispatchForItems(array $itemIds, int $vendorId = null, array $options = []): void
    {
        self::dispatch($itemIds, $vendorId, $options);
    }
}