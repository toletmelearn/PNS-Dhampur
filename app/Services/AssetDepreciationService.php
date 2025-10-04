<?php

namespace App\Services;

use App\Models\AssetDepreciation;
use App\Models\DepreciationEntry;
use App\Models\InventoryItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssetDepreciationService
{
    /**
     * Get depreciation dashboard data
     */
    public function getDepreciationDashboard()
    {
        return [
            'summary' => $this->getDepreciationSummary(),
            'recent_calculations' => $this->getRecentCalculations(),
            'overdue_assets' => $this->getOverdueAssets(),
            'depreciation_trends' => $this->getDepreciationTrends(),
            'method_distribution' => $this->getMethodDistribution(),
            'asset_status_breakdown' => $this->getAssetStatusBreakdown()
        ];
    }

    /**
     * Get depreciation summary statistics
     */
    public function getDepreciationSummary()
    {
        $totalAssets = AssetDepreciation::count();
        $activeAssets = AssetDepreciation::active()->count();
        $fullyDepreciated = AssetDepreciation::fullyDepreciated()->count();
        $overdueCalculations = AssetDepreciation::dueForCalculation()->count();

        $totalOriginalValue = AssetDepreciation::sum('purchase_price');
        $totalCurrentValue = AssetDepreciation::sum('current_book_value');
        $totalAccumulatedDepreciation = AssetDepreciation::sum('accumulated_depreciation');

        return [
            'total_assets' => $totalAssets,
            'active_assets' => $activeAssets,
            'fully_depreciated' => $fullyDepreciated,
            'overdue_calculations' => $overdueCalculations,
            'total_original_value' => $totalOriginalValue,
            'total_current_value' => $totalCurrentValue,
            'total_accumulated_depreciation' => $totalAccumulatedDepreciation,
            'overall_depreciation_rate' => $totalOriginalValue > 0 ? 
                round(($totalAccumulatedDepreciation / $totalOriginalValue) * 100, 2) : 0,
            'average_asset_age_months' => $this->getAverageAssetAge()
        ];
    }

    /**
     * Get recent depreciation calculations
     */
    public function getRecentCalculations($limit = 10)
    {
        return DepreciationEntry::with(['assetDepreciation.inventoryItem'])
            ->orderBy('calculation_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'asset_name' => $entry->assetDepreciation->inventoryItem->name ?? 'Unknown Asset',
                    'calculation_date' => $entry->calculation_date->format('M d, Y'),
                    'depreciation_amount' => $entry->depreciation_amount,
                    'book_value' => $entry->book_value,
                    'method' => $entry->calculation_method,
                    'entry_type' => $entry->entry_type,
                    'entry_type_badge' => $entry->entry_type_badge
                ];
            });
    }

    /**
     * Get assets with overdue calculations
     */
    public function getOverdueAssets()
    {
        return AssetDepreciation::dueForCalculation()
            ->with('inventoryItem')
            ->get()
            ->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'asset_name' => $asset->inventoryItem->name ?? 'Unknown Asset',
                    'last_calculation' => $asset->last_calculation_date?->format('M d, Y') ?? 'Never',
                    'days_overdue' => $asset->getDaysOverdue(),
                    'current_book_value' => $asset->current_book_value,
                    'depreciation_method' => $asset->depreciation_method_display
                ];
            });
    }

    /**
     * Get depreciation trends over time
     */
    public function getDepreciationTrends($months = 12)
    {
        $startDate = now()->subMonths($months);
        
        $trends = DepreciationEntry::select(
                DB::raw('DATE_FORMAT(calculation_date, "%Y-%m") as month'),
                DB::raw('SUM(depreciation_amount) as total_depreciation'),
                DB::raw('COUNT(*) as calculation_count'),
                DB::raw('AVG(depreciation_amount) as avg_depreciation')
            )
            ->where('calculation_date', '>=', $startDate)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $trends->pluck('month')->map(function ($month) {
                return Carbon::createFromFormat('Y-m', $month)->format('M Y');
            }),
            'depreciation_amounts' => $trends->pluck('total_depreciation'),
            'calculation_counts' => $trends->pluck('calculation_count'),
            'average_amounts' => $trends->pluck('avg_depreciation')
        ];
    }

    /**
     * Get distribution of depreciation methods
     */
    public function getMethodDistribution()
    {
        $distribution = AssetDepreciation::select('depreciation_method', DB::raw('COUNT(*) as count'))
            ->groupBy('depreciation_method')
            ->get();

        return [
            'labels' => $distribution->pluck('depreciation_method')->map(function ($method) {
                return AssetDepreciation::getDepreciationMethods()[$method] ?? $method;
            }),
            'data' => $distribution->pluck('count'),
            'colors' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
        ];
    }

    /**
     * Get asset status breakdown
     */
    public function getAssetStatusBreakdown()
    {
        $assets = AssetDepreciation::with('inventoryItem')->get();
        
        $breakdown = $assets->groupBy('depreciation_status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_value' => $group->sum('current_book_value'),
                'percentage' => 0 // Will be calculated below
            ];
        });

        $totalAssets = $assets->count();
        
        return $breakdown->map(function ($item) use ($totalAssets) {
            $item['percentage'] = $totalAssets > 0 ? round(($item['count'] / $totalAssets) * 100, 1) : 0;
            return $item;
        });
    }

    /**
     * Run automated depreciation calculations
     */
    public function runAutomatedCalculations()
    {
        $overdueAssets = AssetDepreciation::dueForCalculation()->get();
        $results = [
            'processed' => 0,
            'errors' => 0,
            'total_depreciation' => 0,
            'details' => []
        ];

        foreach ($overdueAssets as $asset) {
            try {
                $calculation = $asset->updateDepreciation();
                
                $results['processed']++;
                $results['total_depreciation'] += $calculation['monthly_depreciation'];
                $results['details'][] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->inventoryItem->name ?? 'Unknown',
                    'depreciation_amount' => $calculation['monthly_depreciation'],
                    'new_book_value' => $calculation['current_book_value'],
                    'status' => 'success'
                ];

                Log::info("Depreciation calculated for asset {$asset->id}", $calculation);
                
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->inventoryItem->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];

                Log::error("Failed to calculate depreciation for asset {$asset->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Calculate depreciation for specific asset
     */
    public function calculateAssetDepreciation($assetId, $asOfDate = null)
    {
        $asset = AssetDepreciation::findOrFail($assetId);
        
        try {
            $calculation = $asset->updateDepreciation($asOfDate);
            
            return [
                'success' => true,
                'asset_id' => $assetId,
                'calculation' => $calculation,
                'message' => 'Depreciation calculated successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Failed to calculate depreciation for asset {$assetId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'asset_id' => $assetId,
                'error' => $e->getMessage(),
                'message' => 'Failed to calculate depreciation'
            ];
        }
    }

    /**
     * Create depreciation schedule for asset
     */
    public function createDepreciationSchedule($assetId)
    {
        $asset = AssetDepreciation::findOrFail($assetId);
        return $asset->generateDepreciationSchedule();
    }

    /**
     * Get depreciation report
     */
    public function getDepreciationReport($filters = [])
    {
        $query = AssetDepreciation::with(['inventoryItem', 'depreciationEntries']);

        // Apply filters
        if (!empty($filters['method'])) {
            $query->where('depreciation_method', $filters['method']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'fully_depreciated') {
                $query->fullyDepreciated();
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('purchase_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('purchase_date', '<=', $filters['date_to']);
        }

        $assets = $query->get();

        return [
            'assets' => $assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'asset_name' => $asset->inventoryItem->name ?? 'Unknown Asset',
                    'purchase_price' => $asset->purchase_price,
                    'current_book_value' => $asset->current_book_value,
                    'accumulated_depreciation' => $asset->accumulated_depreciation,
                    'depreciation_percentage' => $asset->depreciation_percentage,
                    'depreciation_method' => $asset->depreciation_method_display,
                    'useful_life' => $asset->useful_life_years . ' years, ' . $asset->useful_life_months . ' months',
                    'remaining_life' => $asset->remaining_life_months . ' months',
                    'status' => $asset->depreciation_status,
                    'last_calculation' => $asset->last_calculation_date?->format('M d, Y'),
                    'is_overdue' => $asset->isOverdue()
                ];
            }),
            'summary' => [
                'total_assets' => $assets->count(),
                'total_original_value' => $assets->sum('purchase_price'),
                'total_current_value' => $assets->sum('current_book_value'),
                'total_depreciation' => $assets->sum('accumulated_depreciation'),
                'average_depreciation_rate' => $assets->avg('depreciation_percentage')
            ]
        ];
    }

    /**
     * Create manual depreciation entry
     */
    public function createManualEntry($assetId, $entryData)
    {
        $asset = AssetDepreciation::findOrFail($assetId);
        
        DB::beginTransaction();
        
        try {
            // Create the manual entry
            $entry = DepreciationEntry::createManualEntry($assetId, $entryData);
            
            // Update the asset's current values
            $asset->update([
                'accumulated_depreciation' => $entryData['accumulated_depreciation'],
                'current_book_value' => $entryData['book_value'],
                'last_calculation_date' => $entryData['calculation_date'] ?? now(),
                'is_fully_depreciated' => $entryData['book_value'] <= $asset->salvage_value
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'entry' => $entry,
                'message' => 'Manual depreciation entry created successfully'
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create manual depreciation entry'
            ];
        }
    }

    /**
     * Get asset depreciation history
     */
    public function getAssetDepreciationHistory($assetId)
    {
        $asset = AssetDepreciation::with(['inventoryItem', 'depreciationEntries' => function ($query) {
            $query->orderBy('calculation_date', 'desc');
        }])->findOrFail($assetId);

        return [
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->inventoryItem->name ?? 'Unknown Asset',
                'purchase_price' => $asset->purchase_price,
                'current_book_value' => $asset->current_book_value,
                'depreciation_method' => $asset->depreciation_method_display,
                'status' => $asset->depreciation_status
            ],
            'entries' => $asset->depreciationEntries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'date' => $entry->calculation_date->format('M d, Y'),
                    'depreciation_amount' => $entry->depreciation_amount,
                    'accumulated_depreciation' => $entry->accumulated_depreciation,
                    'book_value' => $entry->book_value,
                    'method' => $entry->calculation_method,
                    'type' => $entry->entry_type,
                    'notes' => $entry->notes,
                    'variance' => $entry->getVarianceFromPrevious()
                ];
            })
        ];
    }

    /**
     * Get assets requiring attention
     */
    public function getAssetsRequiringAttention()
    {
        return [
            'overdue_calculations' => $this->getOverdueAssets(),
            'nearly_depreciated' => $this->getNearlyDepreciatedAssets(),
            'high_variance_assets' => $this->getHighVarianceAssets(),
            'missing_depreciation_setup' => $this->getAssetsWithoutDepreciation()
        ];
    }

    /**
     * Get nearly depreciated assets (>90% depreciated)
     */
    protected function getNearlyDepreciatedAssets()
    {
        return AssetDepreciation::with('inventoryItem')
            ->whereRaw('(accumulated_depreciation / purchase_price) * 100 >= 90')
            ->where('is_fully_depreciated', false)
            ->get()
            ->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'asset_name' => $asset->inventoryItem->name ?? 'Unknown Asset',
                    'depreciation_percentage' => $asset->depreciation_percentage,
                    'current_book_value' => $asset->current_book_value,
                    'remaining_months' => $asset->remaining_life_months
                ];
            });
    }

    /**
     * Get assets with high depreciation variance
     */
    protected function getHighVarianceAssets()
    {
        // This would require more complex logic to compare actual vs expected depreciation
        // For now, return assets with manual adjustments
        return DepreciationEntry::with(['assetDepreciation.inventoryItem'])
            ->where('is_manual_entry', true)
            ->where('created_at', '>=', now()->subMonths(3))
            ->get()
            ->groupBy('asset_depreciation_id')
            ->map(function ($entries) {
                $asset = $entries->first()->assetDepreciation;
                return [
                    'id' => $asset->id,
                    'asset_name' => $asset->inventoryItem->name ?? 'Unknown Asset',
                    'manual_entries_count' => $entries->count(),
                    'last_manual_entry' => $entries->sortByDesc('created_at')->first()->created_at->format('M d, Y')
                ];
            })
            ->values();
    }

    /**
     * Get assets without depreciation setup
     */
    protected function getAssetsWithoutDepreciation()
    {
        return InventoryItem::where('is_asset', true)
            ->whereDoesntHave('assetDepreciation')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'purchase_price' => $item->purchase_price,
                    'purchase_date' => $item->purchase_date?->format('M d, Y')
                ];
            });
    }

    /**
     * Get average asset age in months
     */
    protected function getAverageAssetAge()
    {
        $assets = AssetDepreciation::whereNotNull('purchase_date')->get();
        
        if ($assets->isEmpty()) return 0;
        
        $totalMonths = $assets->sum(function ($asset) {
            return $asset->purchase_date->diffInMonths(now());
        });
        
        return round($totalMonths / $assets->count(), 1);
    }

    /**
     * Setup depreciation for asset
     */
    public function setupAssetDepreciation($inventoryItemId, $depreciationData)
    {
        $inventoryItem = InventoryItem::findOrFail($inventoryItemId);
        
        if (!$inventoryItem->is_asset) {
            throw new \Exception('Item is not marked as an asset');
        }
        
        // Check if depreciation already exists
        if ($inventoryItem->assetDepreciation) {
            throw new \Exception('Depreciation setup already exists for this asset');
        }
        
        DB::beginTransaction();
        
        try {
            $depreciation = AssetDepreciation::createForAsset($inventoryItem, $depreciationData);
            
            // Run initial calculation
            $depreciation->updateDepreciation();
            
            DB::commit();
            
            return [
                'success' => true,
                'depreciation' => $depreciation,
                'message' => 'Asset depreciation setup completed successfully'
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}