<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\SecurityHelper;
use App\Http\Traits\EmailValidationTrait;
use App\Http\Traits\VendorValidationTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    use EmailValidationTrait, VendorValidationTrait;
    /**
     * Display a listing of vendors
     */
    public function index(Request $request)
    {
        $query = Vendor::withCount(['purchaseOrders', 'activePurchaseOrders']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_active')) {
            if ($request->is_active) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('rating_min')) {
            $query->where('rating', '>=', $request->rating_min);
        }

        if ($request->filled('rating_max')) {
            $query->where('rating', '<=', $request->rating_max);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('code', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('email', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('phone', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('contact_person', 'like', SecurityHelper::buildLikePattern($search));
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $vendors = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $vendors,
            'summary' => [
                'total_vendors' => Vendor::count(),
                'active_vendors' => Vendor::active()->count(),
                'approved_vendors' => Vendor::approved()->count(),
                'pending_vendors' => Vendor::pending()->count(),
                'average_rating' => Vendor::avg('rating'),
            ]
        ]);
    }

    /**
     * Store a newly created vendor
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->getVendorCreateRules(),
            $this->getVendorValidationMessages()
        );

        $vendor = Vendor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor created successfully',
            'data' => $vendor
        ], 201);
    }

    /**
     * Display the specified vendor
     */
    public function show($id)
    {
        $vendor = Vendor::with(['purchaseOrders' => function ($query) {
                            $query->latest()->limit(10);
                        }])
                        ->withCount(['purchaseOrders', 'activePurchaseOrders'])
                        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vendor,
            'computed' => [
                'full_address' => $vendor->full_address,
                'status_badge' => $vendor->status_badge,
                'rating_badge' => $vendor->rating_badge,
                'performance_score' => $vendor->performance_score,
                'total_orders_value' => $vendor->getTotalOrdersValue(),
                'average_order_value' => $vendor->getAverageOrderValue(),
                'on_time_delivery_rate' => $vendor->getOnTimeDeliveryRate(),
                'quality_score' => $vendor->getQualityScore(),
                'last_order_date' => $vendor->getLastOrderDate(),
            ]
        ]);
    }

    /**
     * Update the specified vendor
     */
    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate(
            $this->getVendorUpdateRules($vendor->id),
            $this->getVendorValidationMessages()
        );

        $vendor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor updated successfully',
            'data' => $vendor
        ]);
    }

    /**
     * Remove the specified vendor
     */
    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        
        // Check if vendor has active purchase orders
        if ($vendor->activePurchaseOrders()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete vendor with active purchase orders'
            ], 422);
        }

        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted successfully'
        ]);
    }

    /**
     * Approve a vendor
     */
    public function approve($id)
    {
        $vendor = Vendor::findOrFail($id);
        
        if (!$vendor->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor cannot be approved in current status'
            ], 422);
        }

        $vendor->approve();

        return response()->json([
            'success' => true,
            'message' => 'Vendor approved successfully',
            'data' => $vendor
        ]);
    }

    /**
     * Reject a vendor
     */
    public function reject(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        if (!$vendor->canBeRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor cannot be rejected in current status'
            ], 422);
        }

        $vendor->reject($validated['rejection_reason']);

        return response()->json([
            'success' => true,
            'message' => 'Vendor rejected successfully',
            'data' => $vendor
        ]);
    }

    /**
     * Suspend a vendor
     */
    public function suspend(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        
        $validated = $request->validate([
            'suspension_reason' => 'required|string|max:500'
        ]);

        if (!$vendor->canBeSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor cannot be suspended in current status'
            ], 422);
        }

        $vendor->suspend($validated['suspension_reason']);

        return response()->json([
            'success' => true,
            'message' => 'Vendor suspended successfully',
            'data' => $vendor
        ]);
    }

    /**
     * Reactivate a suspended vendor
     */
    public function reactivate($id)
    {
        $vendor = Vendor::findOrFail($id);
        
        if (!$vendor->canBeReactivated()) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor cannot be reactivated in current status'
            ], 422);
        }

        $vendor->reactivate();

        return response()->json([
            'success' => true,
            'message' => 'Vendor reactivated successfully',
            'data' => $vendor
        ]);
    }

    /**
     * Update vendor rating
     */
    public function updateRating(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        
        $validated = $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
            'rating_notes' => 'nullable|string|max:500'
        ]);

        $vendor->updateRating($validated['rating'], $validated['rating_notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Vendor rating updated successfully',
            'data' => $vendor
        ]);
    }

    /**
     * Get vendor performance report
     */
    public function performanceReport($id)
    {
        $vendor = Vendor::findOrFail($id);
        
        $report = $vendor->getPerformanceReport();

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Get vendor purchase orders
     */
    public function purchaseOrders(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        
        $query = $vendor->purchaseOrders();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        $orders = $query->orderBy('order_date', 'desc')
                       ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders,
            'vendor' => $vendor
        ]);
    }

    /**
     * Get vendor statistics
     */
    public function statistics()
    {
        $totalVendors = Vendor::count();
        $activeVendors = Vendor::active()->count();
        $approvedVendors = Vendor::approved()->count();
        $pendingVendors = Vendor::pending()->count();
        $suspendedVendors = Vendor::suspended()->count();

        // Status distribution
        $statusDistribution = Vendor::selectRaw('status, COUNT(*) as count')
                                   ->groupBy('status')
                                   ->get();

        // Rating distribution
        $ratingDistribution = Vendor::selectRaw('
                                CASE 
                                    WHEN rating >= 4.5 THEN "Excellent (4.5-5.0)"
                                    WHEN rating >= 3.5 THEN "Good (3.5-4.4)"
                                    WHEN rating >= 2.5 THEN "Average (2.5-3.4)"
                                    WHEN rating >= 1.5 THEN "Poor (1.5-2.4)"
                                    ELSE "Very Poor (0-1.4)"
                                END as rating_range,
                                COUNT(*) as count
                            ')
                            ->whereNotNull('rating')
                            ->groupBy('rating_range')
                            ->get();

        // Top vendors by rating
        $topVendors = Vendor::whereNotNull('rating')
                           ->orderBy('rating', 'desc')
                           ->limit(10)
                           ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_vendors' => $totalVendors,
                    'active_vendors' => $activeVendors,
                    'approved_vendors' => $approvedVendors,
                    'pending_vendors' => $pendingVendors,
                    'suspended_vendors' => $suspendedVendors,
                    'average_rating' => Vendor::avg('rating'),
                ],
                'status_distribution' => $statusDistribution,
                'rating_distribution' => $ratingDistribution,
                'top_vendors' => $topVendors,
            ]
        ]);
    }

    /**
     * Toggle vendor active status
     */
    public function toggleStatus($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->is_active = !$vendor->is_active;
        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor status updated successfully',
            'data' => [
                'id' => $vendor->id,
                'is_active' => $vendor->is_active
            ]
        ]);
    }
}
