<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Helpers\SecurityHelper;

class InventoryController extends Controller
{
    /**
     * Display a listing of inventory items with filtering and pagination
     */
    public function index(Request $request)
    {
        $query = InventoryItem::with(['category']);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'low':
                    $query->lowStock();
                    break;
                case 'out':
                    $query->outOfStock();
                    break;
                case 'in_stock':
                    $query->inStock();
                    break;
                case 'needs_reorder':
                    $query->needsReorder();
                    break;
            }
        }

        if ($request->filled('is_asset')) {
            if ($request->is_asset) {
                $query->assets();
            } else {
                $query->consumables();
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('item_code', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('description', 'like', SecurityHelper::buildLikePattern($search));
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $items,
            'summary' => [
                'total_items' => InventoryItem::count(),
                'low_stock_items' => InventoryItem::lowStock()->count(),
                'out_of_stock_items' => InventoryItem::outOfStock()->count(),
                'total_value' => InventoryItem::sum(DB::raw('current_stock * unit_price')),
            ]
        ]);
    }

    /**
     * Store a newly created inventory item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'nullable|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'unit_of_measurement' => 'required|string|max:50',
            'location' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:inventory_items,barcode',
            'purchase_date' => 'nullable|date',
            'warranty_period_months' => 'nullable|integer|min:0',
            'warranty_expiry' => 'nullable|date',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'is_asset' => 'boolean',
            'asset_tag' => 'nullable|string|max:100',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'status' => ['required', Rule::in(['active', 'inactive', 'discontinued', 'allocated', 'maintenance'])],
            'notes' => 'nullable|string',
        ]);

        $item = InventoryItem::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item created successfully',
            'data' => $item->load('category')
        ], 201);
    }

    /**
     * Display the specified inventory item
     */
    public function show($id)
    {
        $item = InventoryItem::with(['category', 'purchaseOrderItems', 'assetAllocations', 'maintenanceSchedules'])
                            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item,
            'computed' => [
                'stock_status' => $item->stock_status,
                'stock_percentage' => $item->stock_percentage,
                'current_value' => $item->current_value,
                'depreciated_value' => $item->depreciated_value,
                'is_warranty_valid' => $item->is_warranty_valid,
                'warranty_days_remaining' => $item->warranty_days_remaining,
                'is_low_stock' => $item->isLowStock(),
                'needs_reorder' => $item->needsReorder(),
                'is_allocated' => $item->isAllocated(),
                'next_maintenance_date' => $item->getNextMaintenanceDate(),
            ]
        ]);
    }

    /**
     * Update the specified inventory item
     */
    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|exists:inventory_categories,id',
            'unit_price' => 'sometimes|numeric|min:0',
            'current_stock' => 'sometimes|integer|min:0',
            'minimum_stock' => 'sometimes|integer|min:0',
            'maximum_stock' => 'nullable|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'unit_of_measurement' => 'sometimes|string|max:50',
            'location' => 'nullable|string|max:255',
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('inventory_items', 'barcode')->ignore($id)],
            'purchase_date' => 'nullable|date',
            'warranty_period_months' => 'nullable|integer|min:0',
            'warranty_expiry' => 'nullable|date',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'is_asset' => 'boolean',
            'asset_tag' => 'nullable|string|max:100',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'discontinued', 'allocated', 'maintenance'])],
            'notes' => 'nullable|string',
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated successfully',
            'data' => $item->load('category')
        ]);
    }

    /**
     * Remove the specified inventory item
     */
    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        
        // Check if item has active allocations
        if ($item->isAllocated()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete item that is currently allocated'
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory item deleted successfully'
        ]);
    }

    /**
     * Mark the specified inventory item as disposed
     */
    public function dispose(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'disposed_at' => 'nullable|date',
            'disposal_reason' => 'required|string|max:255',
            'disposal_method' => 'nullable|string|max:100',
            'disposal_value' => 'nullable|numeric|min:0',
            'disposal_notes' => 'nullable|string',
        ]);

        $item->is_disposed = true;
        $item->disposed_at = $validated['disposed_at'] ?? now();
        $item->disposed_by = auth()->id();
        $item->disposal_reason = $validated['disposal_reason'] ?? null;
        $item->disposal_method = $validated['disposal_method'] ?? null;
        $item->disposal_value = $validated['disposal_value'] ?? null;
        $item->disposal_notes = $validated['disposal_notes'] ?? null;

        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Inventory item marked as disposed',
            'data' => $item->fresh()
        ]);
    }

    /**
     * Adjust stock for an inventory item
     */
    public function adjustStock(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'adjustment_type' => 'required|in:add,remove,set',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $oldStock = $item->current_stock;

        try {
            switch ($validated['adjustment_type']) {
                case 'add':
                    $item->addStock($validated['quantity']);
                    break;
                case 'remove':
                    $item->removeStock($validated['quantity']);
                    break;
                case 'set':
                    $item->setStock($validated['quantity']);
                    break;
            }

            // Log the stock adjustment (you might want to create a StockAdjustment model for this)
            // StockAdjustment::create([
            //     'inventory_item_id' => $item->id,
            //     'old_stock' => $oldStock,
            //     'new_stock' => $item->current_stock,
            //     'adjustment_type' => $validated['adjustment_type'],
            //     'quantity' => $validated['quantity'],
            //     'reason' => $validated['reason'],
            //     'notes' => $validated['notes'],
            //     'adjusted_by' => auth()->id(),
            // ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'data' => [
                    'old_stock' => $oldStock,
                    'new_stock' => $item->current_stock,
                    'adjustment' => $validated['quantity'],
                    'type' => $validated['adjustment_type']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get low stock items
     */
    public function lowStock()
    {
        $items = InventoryItem::with('category')
                             ->lowStock()
                             ->orderBy('current_stock', 'asc')
                             ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => $items->count()
        ]);
    }

    /**
     * Get items that need reordering
     */
    public function needsReorder()
    {
        $items = InventoryItem::with('category')
                             ->needsReorder()
                             ->get()
                             ->map(function ($item) {
                                 return [
                                     'item' => $item,
                                     'reorder_suggestion' => $item->calculateReorderSuggestion()
                                 ];
                             });

        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => $items->count()
        ]);
    }

    /**
     * Get inventory dashboard data
     */
    public function dashboard()
    {
        $totalItems = InventoryItem::count();
        $lowStockItems = InventoryItem::lowStock()->count();
        $outOfStockItems = InventoryItem::outOfStock()->count();
        $totalValue = InventoryItem::sum(DB::raw('current_stock * unit_price'));
        $assetsCount = InventoryItem::assets()->count();
        $consumablesCount = InventoryItem::consumables()->count();

        // Stock status distribution
        $stockDistribution = [
            'in_stock' => InventoryItem::inStock()->count(),
            'low_stock' => $lowStockItems,
            'out_of_stock' => $outOfStockItems,
            'needs_reorder' => InventoryItem::needsReorder()->count(),
        ];

        // Category-wise distribution
        $categoryDistribution = InventoryCategory::withCount('items')->get();

        // Recent items (last 30 days)
        $recentItems = InventoryItem::where('created_at', '>=', now()->subDays(30))->count();

        // Warranty expiring items (next 30 days)
        $warrantyExpiring = InventoryItem::warrantyExpiring(30)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_items' => $totalItems,
                    'low_stock_items' => $lowStockItems,
                    'out_of_stock_items' => $outOfStockItems,
                    'total_value' => $totalValue,
                    'assets_count' => $assetsCount,
                    'consumables_count' => $consumablesCount,
                    'recent_items' => $recentItems,
                    'warranty_expiring' => $warrantyExpiring,
                ],
                'distributions' => [
                    'stock_status' => $stockDistribution,
                    'categories' => $categoryDistribution,
                ],
            ]
        ]);
    }

    /**
     * Generate inventory report
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:stock_summary,low_stock,valuation,category_wise,asset_summary',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = InventoryItem::with('category');

        if ($validated['category_id']) {
            $query->where('category_id', $validated['category_id']);
        }

        switch ($validated['report_type']) {
            case 'stock_summary':
                $data = $query->get()->map(function ($item) {
                    return [
                        'item_code' => $item->item_code,
                        'name' => $item->name,
                        'category' => $item->category->name,
                        'current_stock' => $item->current_stock,
                        'minimum_stock' => $item->minimum_stock,
                        'unit_price' => $item->unit_price,
                        'total_value' => $item->current_value,
                        'stock_status' => $item->stock_status,
                    ];
                });
                break;

            case 'low_stock':
                $data = $query->lowStock()->get()->map(function ($item) {
                    return [
                        'item_code' => $item->item_code,
                        'name' => $item->name,
                        'category' => $item->category->name,
                        'current_stock' => $item->current_stock,
                        'minimum_stock' => $item->minimum_stock,
                        'shortage' => $item->minimum_stock - $item->current_stock,
                        'reorder_suggestion' => $item->calculateReorderSuggestion(),
                    ];
                });
                break;

            case 'valuation':
                $data = $query->get()->map(function ($item) {
                    return [
                        'item_code' => $item->item_code,
                        'name' => $item->name,
                        'category' => $item->category->name,
                        'current_stock' => $item->current_stock,
                        'unit_price' => $item->unit_price,
                        'current_value' => $item->current_value,
                        'depreciated_value' => $item->depreciated_value,
                    ];
                });
                break;

            case 'category_wise':
                $data = InventoryCategory::withCount('items')
                                       ->with(['items' => function ($query) {
                                           $query->select('category_id', DB::raw('SUM(current_stock * unit_price) as total_value'))
                                                 ->groupBy('category_id');
                                       }])
                                       ->get()
                                       ->map(function ($category) {
                                           return [
                                               'category' => $category->name,
                                               'items_count' => $category->items_count,
                                               'total_value' => $category->items->sum('total_value') ?? 0,
                                           ];
                                       });
                break;

            case 'asset_summary':
                $data = $query->assets()->get()->map(function ($item) {
                    return [
                        'item_code' => $item->item_code,
                        'name' => $item->name,
                        'asset_tag' => $item->asset_tag,
                        'purchase_date' => $item->purchase_date,
                        'current_value' => $item->current_value,
                        'depreciated_value' => $item->depreciated_value,
                        'is_allocated' => $item->isAllocated(),
                        'allocated_to' => $item->getAllocatedTo(),
                        'warranty_status' => $item->is_warranty_valid ? 'Valid' : 'Expired',
                    ];
                });
                break;
        }

        return response()->json([
            'success' => true,
            'report_type' => $validated['report_type'],
            'generated_at' => now(),
            'data' => $data,
            'summary' => [
                'total_records' => $data->count(),
                'total_value' => $data->sum('total_value') ?? $data->sum('current_value') ?? 0,
            ]
        ]);
    }

    /**
     * Display the settings index page
     */
    public function settingsIndex()
    {
        return view('inventory.settings.index');
    }

    /**
     * Display the general settings page
     */
    public function generalSettings()
    {
        return view('inventory.settings.general');
    }

    /**
     * Display the inventory settings page
     */
    public function inventorySettings()
    {
        return view('inventory.settings.inventory');
    }

    /**
     * Display the notification settings page
     */
    public function notificationSettings()
    {
        return view('inventory.settings.notifications');
    }

    /**
     * Display the security settings page
     */
    public function securitySettings()
    {
        return view('inventory.settings.security');
    }

    /**
     * Display the backup settings page
     */
    public function backupSettings()
    {
        return view('inventory.settings.backup');
    }

    /**
     * Display the system settings page
     */
    public function systemSettings()
    {
        return view('inventory.settings.system');
    }

    /**
     * Display the API settings page
     */
    public function apiSettings()
    {
        return view('inventory.settings.api');
    }

    /**
     * Save general settings
     */
    public function saveGeneralSettings(Request $request)
    {
        // Implementation for saving general settings
        return response()->json(['success' => true, 'message' => 'General settings saved successfully']);
    }

    /**
     * Save inventory settings
     */
    public function saveInventorySettings(Request $request)
    {
        // Implementation for saving inventory settings
        return response()->json(['success' => true, 'message' => 'Inventory settings saved successfully']);
    }

    /**
     * Save notification settings
     */
    public function saveNotificationSettings(Request $request)
    {
        // Implementation for saving notification settings
        return response()->json(['success' => true, 'message' => 'Notification settings saved successfully']);
    }

    /**
     * Save security settings
     */
    public function saveSecuritySettings(Request $request)
    {
        // Implementation for saving security settings
        return response()->json(['success' => true, 'message' => 'Security settings saved successfully']);
    }

    /**
     * Save backup settings
     */
    public function saveBackupSettings(Request $request)
    {
        // Implementation for saving backup settings
        return response()->json(['success' => true, 'message' => 'Backup settings saved successfully']);
    }

    /**
     * Save system settings
     */
    public function saveSystemSettings(Request $request)
    {
        // Implementation for saving system settings
        return response()->json(['success' => true, 'message' => 'System settings saved successfully']);
    }

    /**
     * Save API settings
     */
    public function saveApiSettings(Request $request)
    {
        // Implementation for saving API settings
        return response()->json(['success' => true, 'message' => 'API settings saved successfully']);
    }

    /**
     * Export settings
     */
    public function exportSettings()
    {
        // Implementation for exporting settings
        return response()->json(['success' => true, 'message' => 'Settings exported successfully']);
    }

    /**
     * Import settings
     */
    public function importSettings(Request $request)
    {
        // Implementation for importing settings
        return response()->json(['success' => true, 'message' => 'Settings imported successfully']);
    }
}
