<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'requestedBy', 'approvedBy'])
                              ->withCount(['items', 'pendingItems', 'receivedItems']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }

        if ($request->filled('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        if ($request->filled('amount_min')) {
            $query->where('total_amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('total_amount', '<=', $request->amount_max);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'order_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders,
            'summary' => [
                'total_orders' => PurchaseOrder::count(),
                'pending_orders' => PurchaseOrder::pending()->count(),
                'approved_orders' => PurchaseOrder::approved()->count(),
                'sent_orders' => PurchaseOrder::sent()->count(),
                'received_orders' => PurchaseOrder::received()->count(),
                'overdue_orders' => PurchaseOrder::overdue()->count(),
                'total_value' => PurchaseOrder::sum('total_amount'),
            ]
        ]);
    }

    /**
     * Store a newly created purchase order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'expected_delivery_date' => 'required|date|after:today',
            'priority' => 'required|in:low,medium,high,urgent',
            'delivery_address' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.specifications' => 'nullable|string',
            'items.*.notes' => 'nullable|string',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'vendor_id' => $validated['vendor_id'],
            'expected_delivery_date' => $validated['expected_delivery_date'],
            'priority' => $validated['priority'],
            'delivery_address' => $validated['delivery_address'],
            'terms_and_conditions' => $validated['terms_and_conditions'],
            'notes' => $validated['notes'],
        ]);

        // Add items to the purchase order
        foreach ($validated['items'] as $itemData) {
            $purchaseOrder->addItem(
                $itemData['inventory_item_id'],
                $itemData['quantity_ordered'],
                $itemData['unit_price'],
                $itemData['specifications'] ?? null,
                $itemData['notes'] ?? null
            );
        }

        // Recalculate totals
        $purchaseOrder->calculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order created successfully',
            'data' => $purchaseOrder->load(['vendor', 'items.inventoryItem'])
        ], 201);
    }

    /**
     * Display the specified purchase order
     */
    public function show($id)
    {
        $order = PurchaseOrder::with([
                                'vendor',
                                'requestedBy',
                                'approvedBy',
                                'items.inventoryItem'
                            ])
                            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
            'computed' => [
                'status_badge' => $order->status_badge,
                'priority_badge' => $order->priority_badge,
                'is_overdue' => $order->is_overdue,
                'days_overdue' => $order->days_overdue,
                'delivery_status' => $order->delivery_status,
                'completion_percentage' => $order->completion_percentage,
                'total_received_amount' => $order->total_received_amount,
                'pending_amount' => $order->pending_amount,
            ]
        ]);
    }

    /**
     * Update the specified purchase order
     */
    public function update(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        // Check if order can be updated
        if (!in_array($order->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be updated in current status'
            ], 422);
        }

        $validated = $request->validate([
            'vendor_id' => 'sometimes|exists:vendors,id',
            'expected_delivery_date' => 'sometimes|date|after:today',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'delivery_address' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order updated successfully',
            'data' => $order->load(['vendor', 'items.inventoryItem'])
        ]);
    }

    /**
     * Remove the specified purchase order
     */
    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        // Check if order can be deleted
        if (!in_array($order->status, ['pending', 'cancelled', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete purchase order in current status'
            ], 422);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order deleted successfully'
        ]);
    }

    /**
     * Approve a purchase order
     */
    public function approve(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        if (!$order->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be approved in current status'
            ], 422);
        }

        $order->approve();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order approved successfully',
            'data' => $order
        ]);
    }

    /**
     * Reject a purchase order
     */
    public function reject(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        if (!$order->canBeRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be rejected in current status'
            ], 422);
        }

        $order->reject($validated['rejection_reason']);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order rejected successfully',
            'data' => $order
        ]);
    }

    /**
     * Send a purchase order to vendor
     */
    public function send($id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        if (!$order->canBeSent()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be sent in current status'
            ], 422);
        }

        $order->send();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order sent to vendor successfully',
            'data' => $order
        ]);
    }

    /**
     * Cancel a purchase order
     */
    public function cancel(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be cancelled in current status'
            ], 422);
        }

        $order->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order cancelled successfully',
            'data' => $order
        ]);
    }

    /**
     * Mark purchase order as received
     */
    public function markAsReceived($id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        if (!$order->canReceiveItems()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be marked as received in current status'
            ], 422);
        }

        $order->markAsReceived();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order marked as received successfully',
            'data' => $order
        ]);
    }

    /**
     * Receive items for a purchase order
     */
    public function receiveItems(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        if (!$order->canReceiveItems()) {
            return response()->json([
                'success' => false,
                'message' => 'Items cannot be received for this purchase order'
            ], 422);
        }

        foreach ($validated['items'] as $itemData) {
            $orderItem = PurchaseOrderItem::findOrFail($itemData['item_id']);
            
            // Verify the item belongs to this purchase order
            if ($orderItem->purchase_order_id !== $order->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid item for this purchase order'
                ], 422);
            }

            $order->receiveItem(
                $orderItem->id,
                $itemData['quantity_received'],
                $itemData['notes'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Items received successfully',
            'data' => $order->load(['items.inventoryItem'])
        ]);
    }

    /**
     * Add item to purchase order
     */
    public function addItem(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity_ordered' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'specifications' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Check if order can be modified
        if (!in_array($order->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add items to purchase order in current status'
            ], 422);
        }

        $order->addItem(
            $validated['inventory_item_id'],
            $validated['quantity_ordered'],
            $validated['unit_price'],
            $validated['specifications'] ?? null,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Item added to purchase order successfully',
            'data' => $order->load(['items.inventoryItem'])
        ]);
    }

    /**
     * Update item in purchase order
     */
    public function updateItem(Request $request, $id, $itemId)
    {
        $order = PurchaseOrder::findOrFail($id);
        $item = PurchaseOrderItem::where('purchase_order_id', $id)
                                ->where('id', $itemId)
                                ->firstOrFail();
        
        $validated = $request->validate([
            'quantity_ordered' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
            'specifications' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Check if order can be modified
        if (!in_array($order->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update items in purchase order in current status'
            ], 422);
        }

        $order->updateItem($itemId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $order->load(['items.inventoryItem'])
        ]);
    }

    /**
     * Remove item from purchase order
     */
    public function removeItem($id, $itemId)
    {
        $order = PurchaseOrder::findOrFail($id);
        $item = PurchaseOrderItem::where('purchase_order_id', $id)
                                ->where('id', $itemId)
                                ->firstOrFail();

        // Check if order can be modified
        if (!in_array($order->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove items from purchase order in current status'
            ], 422);
        }

        $order->removeItem($itemId);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from purchase order successfully',
            'data' => $order->load(['items.inventoryItem'])
        ]);
    }

    /**
     * Duplicate a purchase order
     */
    public function duplicate($id)
    {
        $order = PurchaseOrder::findOrFail($id);
        
        $duplicatedOrder = $order->duplicate();

        return response()->json([
            'success' => true,
            'message' => 'Purchase order duplicated successfully',
            'data' => $duplicatedOrder->load(['vendor', 'items.inventoryItem'])
        ]);
    }

    /**
     * Get purchase order statistics
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subMonth());
        $dateTo = $request->get('date_to', now());

        $totalOrders = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo])->count();
        $totalValue = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo])->sum('total_amount');

        // Status distribution
        $statusDistribution = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo])
                                          ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as value')
                                          ->groupBy('status')
                                          ->get();

        // Priority distribution
        $priorityDistribution = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo])
                                            ->selectRaw('priority, COUNT(*) as count, SUM(total_amount) as value')
                                            ->groupBy('priority')
                                            ->get();

        // Top vendors by order count and value
        $topVendors = PurchaseOrder::with('vendor')
                                  ->whereBetween('order_date', [$dateFrom, $dateTo])
                                  ->selectRaw('vendor_id, COUNT(*) as order_count, SUM(total_amount) as total_value')
                                  ->groupBy('vendor_id')
                                  ->orderBy('total_value', 'desc')
                                  ->limit(10)
                                  ->get();

        // Monthly trends
        $monthlyTrends = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo])
                                     ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, COUNT(*) as count, SUM(total_amount) as value')
                                     ->groupBy('month')
                                     ->orderBy('month')
                                     ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_orders' => $totalOrders,
                    'total_value' => $totalValue,
                    'average_order_value' => $totalOrders > 0 ? $totalValue / $totalOrders : 0,
                    'pending_orders' => PurchaseOrder::pending()->count(),
                    'overdue_orders' => PurchaseOrder::overdue()->count(),
                ],
                'status_distribution' => $statusDistribution,
                'priority_distribution' => $priorityDistribution,
                'top_vendors' => $topVendors,
                'monthly_trends' => $monthlyTrends,
            ]
        ]);
    }

    /**
     * Generate purchase order report
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:summary,detailed,vendor_performance,overdue',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'vendor_id' => 'nullable|exists:vendors,id',
            'status' => 'nullable|in:pending,approved,sent,received,completed,cancelled,rejected',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $query = PurchaseOrder::with(['vendor', 'requestedBy', 'approvedBy', 'items.inventoryItem']);

        // Apply filters
        if ($validated['date_from'] ?? null) {
            $query->where('order_date', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->where('order_date', '<=', $validated['date_to']);
        }

        if ($validated['vendor_id'] ?? null) {
            $query->where('vendor_id', $validated['vendor_id']);
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if ($validated['priority'] ?? null) {
            $query->where('priority', $validated['priority']);
        }

        $orders = $query->orderBy('order_date', 'desc')->get();

        $reportData = [
            'type' => $validated['type'],
            'filters' => $validated,
            'generated_at' => now(),
            'orders' => $orders,
            'summary' => [
                'total_orders' => $orders->count(),
                'total_value' => $orders->sum('total_amount'),
                'average_value' => $orders->avg('total_amount'),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $reportData
        ]);
    }
}
