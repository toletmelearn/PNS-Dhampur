<?php

namespace App\Http\Controllers;

use App\Models\AssetDepreciation;
use App\Models\DepreciationEntry;
use App\Models\InventoryItem;
use App\Services\AssetDepreciationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AssetDepreciationController extends Controller
{
    protected $depreciationService;

    public function __construct(AssetDepreciationService $depreciationService)
    {
        $this->depreciationService = $depreciationService;
    }

    /**
     * Display depreciation dashboard
     */
    public function index()
    {
        $dashboardData = $this->depreciationService->getDepreciationDashboard();
        return view('asset-depreciation.index', compact('dashboardData'));
    }

    /**
     * Get dashboard data via API
     */
    public function dashboard()
    {
        try {
            $data = $this->depreciationService->getDepreciationDashboard();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run automated depreciation calculations
     */
    public function runCalculations()
    {
        try {
            $results = $this->depreciationService->runAutomatedCalculations();
            
            return response()->json([
                'success' => true,
                'message' => "Processed {$results['processed']} assets with {$results['errors']} errors",
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run calculations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate depreciation for specific asset
     */
    public function calculateAsset(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'as_of_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $asOfDate = $request->as_of_date ? Carbon::parse($request->as_of_date) : null;
            $result = $this->depreciationService->calculateAssetDepreciation($id, $asOfDate);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate depreciation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get depreciation report
     */
    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'method' => 'nullable|string|in:straight_line,declining_balance,double_declining_balance,sum_of_years_digits',
            'status' => 'nullable|string|in:active,fully_depreciated',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = $request->only(['method', 'status', 'date_from', 'date_to']);
            $report = $this->depreciationService->getDepreciationReport($filters);
            
            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific asset depreciation
     */
    public function show($id)
    {
        try {
            $asset = AssetDepreciation::with(['inventoryItem', 'depreciationEntries' => function ($query) {
                $query->orderBy('calculation_date', 'desc')->limit(10);
            }])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'asset' => $asset,
                    'schedule' => $asset->depreciation_schedule,
                    'recent_entries' => $asset->depreciationEntries
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create depreciation setup for asset
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'depreciation_method' => 'required|string|in:straight_line,declining_balance,double_declining_balance,sum_of_years_digits',
            'useful_life_years' => 'required|integer|min:1|max:50',
            'useful_life_months' => 'nullable|integer|min:0|max:11',
            'salvage_value' => 'nullable|numeric|min:0',
            'annual_depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'depreciation_start_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->depreciationService->setupAssetDepreciation(
                $request->inventory_item_id,
                $request->only([
                    'method' => 'depreciation_method',
                    'useful_life_years',
                    'useful_life_months',
                    'salvage_value',
                    'annual_rate' => 'annual_depreciation_rate',
                    'start_date' => 'depreciation_start_date'
                ])
            );
            
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to setup depreciation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update depreciation settings
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'depreciation_method' => 'sometimes|string|in:straight_line,declining_balance,double_declining_balance,sum_of_years_digits',
            'useful_life_years' => 'sometimes|integer|min:1|max:50',
            'useful_life_months' => 'sometimes|integer|min:0|max:11',
            'salvage_value' => 'sometimes|numeric|min:0',
            'annual_depreciation_rate' => 'sometimes|numeric|min:0|max:100',
            'depreciation_notes' => 'sometimes|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $asset = AssetDepreciation::findOrFail($id);
            $asset->update($request->validated());
            
            // Regenerate schedule if key parameters changed
            if ($request->hasAny(['depreciation_method', 'useful_life_years', 'useful_life_months', 'salvage_value'])) {
                $asset->generateDepreciationSchedule();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Depreciation settings updated successfully',
                'data' => $asset->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update depreciation settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create manual depreciation entry
     */
    public function createManualEntry(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'calculation_date' => 'required|date',
            'depreciation_amount' => 'required|numeric|min:0',
            'adjustment_reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $asset = AssetDepreciation::findOrFail($id);
            
            // Calculate new accumulated depreciation and book value
            $newAccumulated = $asset->accumulated_depreciation + $request->depreciation_amount;
            $newBookValue = $asset->purchase_price - $newAccumulated;
            
            $entryData = [
                'calculation_date' => $request->calculation_date,
                'depreciation_amount' => $request->depreciation_amount,
                'accumulated_depreciation' => $newAccumulated,
                'book_value' => max($newBookValue, $asset->salvage_value),
                'adjustment_reason' => $request->adjustment_reason,
                'notes' => $request->notes
            ];
            
            $result = $this->depreciationService->createManualEntry($id, $entryData);
            
            return response()->json($result, $result['success'] ? 201 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create manual entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get asset depreciation history
     */
    public function history($id)
    {
        try {
            $history = $this->depreciationService->getAssetDepreciationHistory($id);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load depreciation history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate depreciation schedule
     */
    public function generateSchedule($id)
    {
        try {
            $schedule = $this->depreciationService->createDepreciationSchedule($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Depreciation schedule generated successfully',
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assets requiring attention
     */
    public function requiresAttention()
    {
        try {
            $data = $this->depreciationService->getAssetsRequiringAttention();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attention items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available assets for depreciation setup
     */
    public function availableAssets()
    {
        try {
            $assets = InventoryItem::where('is_asset', true)
                ->whereDoesntHave('assetDepreciation')
                ->select('id', 'name', 'purchase_price', 'purchase_date')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $assets
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load available assets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get depreciation methods
     */
    public function methods()
    {
        return response()->json([
            'success' => true,
            'data' => AssetDepreciation::getDepreciationMethods()
        ]);
    }

    /**
     * Delete depreciation setup
     */
    public function destroy($id)
    {
        try {
            $asset = AssetDepreciation::findOrFail($id);
            
            // Check if there are any entries
            if ($asset->depreciationEntries()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete depreciation setup with existing entries'
                ], 422);
            }
            
            $asset->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Depreciation setup deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete depreciation setup',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export depreciation report
     */
    public function exportReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|string|in:pdf,excel,csv',
            'method' => 'nullable|string',
            'status' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = $request->only(['method', 'status', 'date_from', 'date_to']);
            $report = $this->depreciationService->getDepreciationReport($filters);
            
            $filename = 'asset_depreciation_report_' . now()->format('Y_m_d_H_i_s');
            
            switch ($request->format) {
                case 'pdf':
                    // Implementation for PDF export would go here
                    return response()->json([
                        'success' => true,
                        'message' => 'PDF export functionality to be implemented',
                        'download_url' => '#'
                    ]);
                    
                case 'excel':
                    // Implementation for Excel export would go here
                    return response()->json([
                        'success' => true,
                        'message' => 'Excel export functionality to be implemented',
                        'download_url' => '#'
                    ]);
                    
                case 'csv':
                    // Implementation for CSV export would go here
                    return response()->json([
                        'success' => true,
                        'message' => 'CSV export functionality to be implemented',
                        'download_url' => '#'
                    ]);
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported export format'
                    ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}