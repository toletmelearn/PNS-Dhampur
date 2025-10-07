<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\SecurityHelper;

class CategoryController extends Controller
{
    /**
     * Display a listing of inventory categories
     */
    public function index(Request $request)
    {
        $query = InventoryCategory::withCount('items');

        // Apply filters
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('is_active')) {
            if ($request->is_active) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('code', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('description', 'like', SecurityHelper::buildLikePattern($search));
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Check if hierarchical structure is requested
        if ($request->get('hierarchical', false)) {
            $categories = $query->mainCategories()->with(['children.children'])->get();
        } else {
            $categories = $query->paginate($request->get('per_page', 15));
        }

        return response()->json([
            'success' => true,
            'data' => $categories,
            'summary' => [
                'total_categories' => InventoryCategory::count(),
                'active_categories' => InventoryCategory::active()->count(),
                'main_categories' => InventoryCategory::mainCategories()->count(),
                'sub_categories' => InventoryCategory::subCategories()->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:inventory_categories,id',
            'is_active' => 'boolean',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Calculate level based on parent
        if ($validated['parent_id']) {
            $parent = InventoryCategory::findOrFail($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
        } else {
            $validated['level'] = 0;
        }

        $category = InventoryCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category->load(['parent', 'children'])
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show($id)
    {
        $category = InventoryCategory::with(['parent', 'children', 'items'])
                                   ->withCount(['items', 'children'])
                                   ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
            'computed' => [
                'full_name' => $category->full_name,
                'has_children' => $category->hasChildren(),
                'items_count' => $category->getItemsCount(),
                'total_items_count' => $category->getTotalItemsCount(),
                'all_children' => $category->getAllChildren(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        $category = InventoryCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                'exists:inventory_categories,id',
                function ($attribute, $value, $fail) use ($id) {
                    // Prevent setting self as parent
                    if ($value == $id) {
                        $fail('Category cannot be its own parent.');
                    }
                    
                    // Prevent circular references
                    if ($value) {
                        $category = InventoryCategory::find($id);
                        $allChildren = $category->getAllChildren();
                        if ($allChildren->contains('id', $value)) {
                            $fail('Cannot set a child category as parent (circular reference).');
                        }
                    }
                }
            ],
            'is_active' => 'boolean',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Recalculate level if parent changed
        if (isset($validated['parent_id'])) {
            if ($validated['parent_id']) {
                $parent = InventoryCategory::findOrFail($validated['parent_id']);
                $validated['level'] = $parent->level + 1;
            } else {
                $validated['level'] = 0;
            }
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->load(['parent', 'children'])
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        $category = InventoryCategory::findOrFail($id);
        
        // Check if category has items
        if ($category->items()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category that has items assigned to it'
            ], 422);
        }

        // Check if category has children
        if ($category->hasChildren()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category that has subcategories'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Get main categories (level 0)
     */
    public function mainCategories()
    {
        $categories = InventoryCategory::mainCategories()
                                     ->active()
                                     ->withCount('items')
                                     ->orderBy('sort_order')
                                     ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get subcategories of a specific category
     */
    public function subcategories($parentId)
    {
        $parent = InventoryCategory::findOrFail($parentId);
        
        $subcategories = $parent->children()
                               ->active()
                               ->withCount('items')
                               ->orderBy('sort_order')
                               ->get();

        return response()->json([
            'success' => true,
            'data' => $subcategories,
            'parent' => $parent
        ]);
    }

    /**
     * Get category hierarchy tree
     */
    public function tree()
    {
        $categories = InventoryCategory::mainCategories()
                                     ->active()
                                     ->with(['children' => function ($query) {
                                         $query->active()->orderBy('sort_order');
                                     }])
                                     ->withCount('items')
                                     ->orderBy('sort_order')
                                     ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:inventory_categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['categories'] as $categoryData) {
            InventoryCategory::where('id', $categoryData['id'])
                           ->update(['sort_order' => $categoryData['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully'
        ]);
    }

    /**
     * Toggle category active status
     */
    public function toggleStatus($id)
    {
        $category = InventoryCategory::findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Category status updated successfully',
            'data' => [
                'id' => $category->id,
                'is_active' => $category->is_active
            ]
        ]);
    }

    /**
     * Get category statistics
     */
    public function statistics()
    {
        $totalCategories = InventoryCategory::count();
        $activeCategories = InventoryCategory::active()->count();
        $mainCategories = InventoryCategory::mainCategories()->count();
        $subCategories = InventoryCategory::subCategories()->count();

        // Category distribution by level
        $levelDistribution = InventoryCategory::selectRaw('level, COUNT(*) as count')
                                            ->groupBy('level')
                                            ->orderBy('level')
                                            ->get();

        // Top categories by item count
        $topCategories = InventoryCategory::withCount('items')
                                        ->orderBy('items_count', 'desc')
                                        ->limit(10)
                                        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_categories' => $totalCategories,
                    'active_categories' => $activeCategories,
                    'inactive_categories' => $totalCategories - $activeCategories,
                    'main_categories' => $mainCategories,
                    'sub_categories' => $subCategories,
                ],
                'level_distribution' => $levelDistribution,
                'top_categories' => $topCategories,
            ]
        ]);
    }
}
