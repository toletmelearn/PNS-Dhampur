<?php

namespace App\Http\Controllers;

use App\Models\AssetAllocation;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetAllocationController extends Controller
{
    /**
     * Display a listing of asset allocations
     */
    public function index(Request $request)
    {
        $query = AssetAllocation::with(['inventoryItem', 'allocatedBy', 'returnedBy']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('allocated_to_type')) {
            $query->where('allocated_to_type', $request->allocated_to_type);
        }

        if ($request->filled('allocated_to_id')) {
            $query->where('allocated_to_id', $request->allocated_to_id);
        }

        if ($request->filled('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        if ($request->filled('allocated_by')) {
            $query->where('allocated_by', $request->allocated_by);
        }

        if ($request->filled('date_from')) {
            $query->where('allocation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('allocation_date', '<=', $request->date_to);
        }

        if ($request->filled('overdue_only') && $request->overdue_only) {
            $query->overdue();
        }

        if ($request->filled('due_today') && $request->due_today) {
            $query->dueToday();
        }

        if ($request->filled('due_tomorrow') && $request->due_tomorrow) {
            $query->dueTomorrow();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('allocated_to_name', 'like', "%{$search}%")
                  ->orWhere('allocation_purpose', 'like', "%{$search}%")
                  ->orWhere('usage_notes', 'like', "%{$search}%")
                  ->orWhereHas('inventoryItem', function ($itemQuery) use ($search) {
                      $itemQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('barcode', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'allocation_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $allocations = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $allocations,
            'summary' => [
                'total_allocations' => AssetAllocation::count(),
                'active_allocations' => AssetAllocation::active()->count(),
                'returned_allocations' => AssetAllocation::returned()->count(),
                'overdue_allocations' => AssetAllocation::overdue()->count(),
                'due_today' => AssetAllocation::dueToday()->count(),
                'due_tomorrow' => AssetAllocation::dueTomorrow()->count(),
            ]
        ]);
    }

    /**
     * Store a newly created asset allocation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'allocated_to_type' => 'required|string|max:100',
            'allocated_to_id' => 'required|integer',
            'allocated_to_name' => 'required|string|max:255',
            'expected_return_date' => 'required|date|after:today',
            'allocation_purpose' => 'required|string|max:500',
            'condition_at_allocation' => 'required|in:excellent,good,fair,poor,damaged',
            'usage_notes' => 'nullable|string',
        ]);

        // Check if the inventory item is available for allocation
        $inventoryItem = InventoryItem::findOrFail($validated['inventory_item_id']);
        
        if (!$inventoryItem->is_asset) {
            return response()->json([
                'success' => false,
                'message' => 'Only assets can be allocated'
            ], 422);
        }

        if ($inventoryItem->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item is not available for allocation'
            ], 422);
        }

        $allocation = AssetAllocation::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Asset allocated successfully',
            'data' => $allocation->load(['inventoryItem', 'allocatedBy'])
        ], 201);
    }

    /**
     * Display the specified asset allocation
     */
    public function show($id)
    {
        $allocation = AssetAllocation::with([
                                      'inventoryItem',
                                      'allocatedBy',
                                      'returnedBy'
                                  ])
                                  ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $allocation,
            'computed' => [
                'status_badge' => $allocation->status_badge,
                'is_overdue' => $allocation->is_overdue,
                'days_overdue' => $allocation->days_overdue,
                'days_until_due' => $allocation->days_until_due,
                'allocation_duration' => $allocation->allocation_duration,
                'return_status' => $allocation->return_status,
                'condition_change' => $allocation->condition_change,
                'usage_rate' => $allocation->usage_rate,
                'can_be_returned' => $allocation->canBeReturned(),
                'can_be_extended' => $allocation->canBeExtended(),
            ]
        ]);
    }

    /**
     * Update the specified asset allocation
     */
    public function update(Request $request, $id)
    {
        $allocation = AssetAllocation::findOrFail($id);

        // Check if allocation can be updated
        if ($allocation->status === 'returned') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update returned allocation'
            ], 422);
        }

        $validated = $request->validate([
            'expected_return_date' => 'sometimes|date|after:allocation_date',
            'allocation_purpose' => 'sometimes|string|max:500',
            'usage_notes' => 'nullable|string',
            'usage_hours' => 'sometimes|numeric|min:0',
        ]);

        $allocation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Asset allocation updated successfully',
            'data' => $allocation->load(['inventoryItem', 'allocatedBy'])
        ]);
    }

    /**
     * Remove the specified asset allocation
     */
    public function destroy($id)
    {
        $allocation = AssetAllocation::findOrFail($id);
        
        // Check if allocation can be deleted
        if ($allocation->status === 'returned') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete returned allocation'
            ], 422);
        }

        // Return the asset to available status
        $allocation->inventoryItem->update(['status' => 'available']);
        
        $allocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset allocation deleted successfully'
        ]);
    }

    /**
     * Return an allocated asset
     */
    public function returnAsset(Request $request, $id)
    {
        $allocation = AssetAllocation::findOrFail($id);
        
        $validated = $request->validate([
            'condition_at_return' => 'required|in:excellent,good,fair,poor,damaged',
            'return_notes' => 'nullable|string',
            'usage_hours' => 'nullable|numeric|min:0',
            'damage_cost' => 'nullable|numeric|min:0',
        ]);

        if (!$allocation->canBeReturned()) {
            return response()->json([
                'success' => false,
                'message' => 'Asset cannot be returned in current status'
            ], 422);
        }

        $allocation->returnAsset(
            $validated['condition_at_return'],
            $validated['return_notes'] ?? null,
            $validated['usage_hours'] ?? null,
            $validated['damage_cost'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Asset returned successfully',
            'data' => $allocation->load(['inventoryItem', 'allocatedBy', 'returnedBy'])
        ]);
    }

    /**
     * Extend an asset allocation
     */
    public function extendAllocation(Request $request, $id)
    {
        $allocation = AssetAllocation::findOrFail($id);
        
        $validated = $request->validate([
            'new_return_date' => 'required|date|after:expected_return_date',
            'extension_reason' => 'required|string|max:500',
        ]);

        if (!$allocation->canBeExtended()) {
            return response()->json([
                'success' => false,
                'message' => 'Asset allocation cannot be extended'
            ], 422);
        }

        $allocation->extendAllocation(
            $validated['new_return_date'],
            $validated['extension_reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Asset allocation extended successfully',
            'data' => $allocation
        ]);
    }

    /**
     * Mark asset as lost
     */
    public function markAsLost(Request $request, $id)
    {
        $allocation = AssetAllocation::findOrFail($id);
        
        $validated = $request->validate([
            'loss_reason' => 'required|string|max:500',
            'replacement_cost' => 'nullable|numeric|min:0',
        ]);

        $allocation->markAsLost(
            $validated['loss_reason'],
            $validated['replacement_cost'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Asset marked as lost',
            'data' => $allocation
        ]);
    }

    /**
     * Mark asset as damaged
     */
    public function markAsDamaged(Request $request, $id)
    {
        $allocation = AssetAllocation::findOrFail($id);
        
        $validated = $request->validate([
            'damage_description' => 'required|string|max:500',
            'damage_cost' => 'nullable|numeric|min:0',
            'repair_required' => 'required|boolean',
        ]);

        $allocation->markAsDamaged(
            $validated['damage_description'],
            $validated['damage_cost'] ?? null,
            $validated['repair_required']
        );

        return response()->json([
            'success' => true,
            'message' => 'Asset marked as damaged',
            'data' => $allocation
        ]);
    }

    /**
     * Update usage information
     */
    public function updateUsage(Request $request, $id)
    {
        $allocation = AssetAllocation::findOrFail($id);
        
        $validated = $request->validate([
            'usage_hours' => 'required|numeric|min:0',
            'usage_notes' => 'nullable|string',
        ]);

        $allocation->updateUsage(
            $validated['usage_hours'],
            $validated['usage_notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Usage information updated successfully',
            'data' => $allocation
        ]);
    }

    /**
     * Get usage report for an allocation
     */
    public function usageReport($id)
    {
        $allocation = AssetAllocation::findOrFail($id);
        
        $report = $allocation->getUsageReport();

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Get allocation history for an inventory item
     */
    public function allocationHistory($inventoryItemId)
    {
        $inventoryItem = InventoryItem::findOrFail($inventoryItemId);
        
        $history = $inventoryItem->getAllocationHistory();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Duplicate an allocation
     */
    public function duplicate($id)
    {
        $allocation = AssetAllocation::findOrFail($id);
        
        $duplicatedAllocation = $allocation->duplicate();

        return response()->json([
            'success' => true,
            'message' => 'Asset allocation duplicated successfully',
            'data' => $duplicatedAllocation->load(['inventoryItem', 'allocatedBy'])
        ]);
    }

    /**
     * Get overdue allocations
     */
    public function overdueAllocations(Request $request)
    {
        $query = AssetAllocation::overdue()
                               ->with(['inventoryItem', 'allocatedBy']);

        if ($request->filled('days_overdue')) {
            $daysOverdue = $request->days_overdue;
            $query->whereRaw('DATEDIFF(NOW(), expected_return_date) >= ?', [$daysOverdue]);
        }

        $allocations = $query->orderBy('expected_return_date', 'asc')
                            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $allocations
        ]);
    }

    /**
     * Get allocations due today
     */
    public function dueToday()
    {
        $allocations = AssetAllocation::dueToday()
                                     ->with(['inventoryItem', 'allocatedBy'])
                                     ->get();

        return response()->json([
            'success' => true,
            'data' => $allocations
        ]);
    }

    /**
     * Get allocations due tomorrow
     */
    public function dueTomorrow()
    {
        $allocations = AssetAllocation::dueTomorrow()
                                     ->with(['inventoryItem', 'allocatedBy'])
                                     ->get();

        return response()->json([
            'success' => true,
            'data' => $allocations
        ]);
    }

    /**
     * Get allocation statistics
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subMonth());
        $dateTo = $request->get('date_to', now());

        $totalAllocations = AssetAllocation::whereBetween('allocation_date', [$dateFrom, $dateTo])->count();

        // Status distribution
        $statusDistribution = AssetAllocation::whereBetween('allocation_date', [$dateFrom, $dateTo])
                                            ->selectRaw('status, COUNT(*) as count')
                                            ->groupBy('status')
                                            ->get();

        // Allocated to type distribution
        $typeDistribution = AssetAllocation::whereBetween('allocation_date', [$dateFrom, $dateTo])
                                          ->selectRaw('allocated_to_type, COUNT(*) as count')
                                          ->groupBy('allocated_to_type')
                                          ->get();

        // Top allocated items
        $topItems = AssetAllocation::with('inventoryItem')
                                  ->whereBetween('allocation_date', [$dateFrom, $dateTo])
                                  ->selectRaw('inventory_item_id, COUNT(*) as allocation_count')
                                  ->groupBy('inventory_item_id')
                                  ->orderBy('allocation_count', 'desc')
                                  ->limit(10)
                                  ->get();

        // Monthly trends
        $monthlyTrends = AssetAllocation::whereBetween('allocation_date', [$dateFrom, $dateTo])
                                       ->selectRaw('DATE_FORMAT(allocation_date, "%Y-%m") as month, COUNT(*) as count')
                                       ->groupBy('month')
                                       ->orderBy('month')
                                       ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_allocations' => $totalAllocations,
                    'active_allocations' => AssetAllocation::active()->count(),
                    'overdue_allocations' => AssetAllocation::overdue()->count(),
                    'returned_allocations' => AssetAllocation::returned()->count(),
                ],
                'status_distribution' => $statusDistribution,
                'type_distribution' => $typeDistribution,
                'top_items' => $topItems,
                'monthly_trends' => $monthlyTrends,
            ]
        ]);
    }

    /**
     * Generate allocation report
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:summary,detailed,overdue,usage',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|in:allocated,returned,lost,damaged,overdue',
            'allocated_to_type' => 'nullable|string',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
        ]);

        $query = AssetAllocation::with(['inventoryItem', 'allocatedBy', 'returnedBy']);

        // Apply filters
        if ($validated['date_from'] ?? null) {
            $query->where('allocation_date', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->where('allocation_date', '<=', $validated['date_to']);
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if ($validated['allocated_to_type'] ?? null) {
            $query->where('allocated_to_type', $validated['allocated_to_type']);
        }

        if ($validated['inventory_item_id'] ?? null) {
            $query->where('inventory_item_id', $validated['inventory_item_id']);
        }

        $allocations = $query->orderBy('allocation_date', 'desc')->get();

        $reportData = [
            'type' => $validated['type'],
            'filters' => $validated,
            'generated_at' => now(),
            'allocations' => $allocations,
            'summary' => [
                'total_allocations' => $allocations->count(),
                'active_allocations' => $allocations->where('status', 'allocated')->count(),
                'returned_allocations' => $allocations->where('status', 'returned')->count(),
                'overdue_allocations' => $allocations->where('status', 'overdue')->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $reportData
        ]);
    }
}
