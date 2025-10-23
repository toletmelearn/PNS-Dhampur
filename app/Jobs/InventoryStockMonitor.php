<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\InventoryItem;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\StockAlert;

class InventoryStockMonitor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $itemId;
    protected $categoryId;
    protected $forceCheck;

    /**
     * Create a new job instance.
     */
    public function __construct($itemId = null, $categoryId = null, $forceCheck = false)
    {
        $this->itemId = $itemId;
        $this->categoryId = $categoryId;
        $this->forceCheck = $forceCheck;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Inventory stock monitoring started', [
                'item_id' => $this->itemId,
                'category_id' => $this->categoryId,
                'force_check' => $this->forceCheck
            ]);

            if ($this->itemId) {
                $this->checkSpecificItem($this->itemId);
            } elseif ($this->categoryId) {
                $this->checkCategoryItems($this->categoryId);
            } else {
                $this->checkAllItems();
            }

            Log::info('Inventory stock monitoring completed successfully');
        } catch (\Exception $e) {
            Log::error('Inventory stock monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Check specific inventory item
     */
    private function checkSpecificItem($itemId)
    {
        $item = InventoryItem::with('category')->find($itemId);
        
        if (!$item) {
            Log::warning('Inventory item not found for stock check', ['item_id' => $itemId]);
            return;
        }

        $this->analyzeItemStock($item);
    }

    /**
     * Check items in specific category
     */
    private function checkCategoryItems($categoryId)
    {
        $items = InventoryItem::with('category')
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->get();

        foreach ($items as $item) {
            $this->analyzeItemStock($item);
        }
    }

    /**
     * Check all active inventory items
     */
    private function checkAllItems()
    {
        $items = InventoryItem::with('category')
            ->where('is_active', true)
            ->get();

        $lowStockItems = collect();
        $criticalStockItems = collect();
        $outOfStockItems = collect();

        foreach ($items as $item) {
            $stockStatus = $this->analyzeItemStock($item);
            
            if ($stockStatus) {
                switch ($stockStatus) {
                    case 'out_of_stock':
                        $outOfStockItems->push($item);
                        break;
                    case 'critical_stock':
                        $criticalStockItems->push($item);
                        break;
                    case 'low_stock':
                        $lowStockItems->push($item);
                        break;
                }
            }
        }

        // Send bulk alerts if there are multiple items with issues
        if ($lowStockItems->count() + $criticalStockItems->count() + $outOfStockItems->count() >= 5) {
            $this->sendBulkStockAlert($lowStockItems, $criticalStockItems, $outOfStockItems);
        }

        // Update inventory statistics cache
        $this->updateInventoryStatistics($lowStockItems, $criticalStockItems, $outOfStockItems);
    }

    /**
     * Analyze stock level for a specific item
     */
    private function analyzeItemStock(InventoryItem $item)
    {
        $currentStock = $item->current_stock;
        $minimumLevel = $item->minimum_stock_level;
        $reorderPoint = $item->reorder_point ?? $minimumLevel;
        
        // Determine stock status
        $stockStatus = null;
        $alertType = null;

        if ($currentStock <= 0) {
            $stockStatus = 'out_of_stock';
            $alertType = 'out_of_stock';
        } elseif ($currentStock <= ($minimumLevel * 0.5)) {
            $stockStatus = 'critical_stock';
            $alertType = 'critical_stock';
        } elseif ($currentStock <= $reorderPoint) {
            $stockStatus = 'low_stock';
            $alertType = 'low_stock';
        }

        if ($stockStatus) {
            $this->checkAndSendStockAlert($item, $alertType);
            
            Log::info('Stock level analyzed', [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'current_stock' => $currentStock,
                'minimum_level' => $minimumLevel,
                'reorder_point' => $reorderPoint,
                'stock_status' => $stockStatus
            ]);
        } else {
            // Resolve any active alerts when stock recovers
            $this->resolveAlertsForItem($item);
        }

        return $stockStatus;
    }

    /**
     * Check and send stock alert if needed
     */
    private function checkAndSendStockAlert(InventoryItem $item, $alertType)
    {
        // Check if we've already sent this type of alert recently
        $cacheKey = "stock_alert_{$item->id}_{$alertType}";
        
        if ($this->forceCheck || !Cache::has($cacheKey)) {
            $this->sendStockAlert($item, $alertType);
            
            // Cache the alert to prevent spam
            $cacheDuration = match($alertType) {
                'out_of_stock' => 120,      // 2 hours for out of stock
                'critical_stock' => 360,    // 6 hours for critical stock
                'low_stock' => 1440,        // 24 hours for low stock
                default => 1440
            };
            
            Cache::put($cacheKey, true, $cacheDuration);
        }
    }

    /**
     * Send stock alert to relevant users
     */
    private function sendStockAlert(InventoryItem $item, $alertType)
    {
        $recipients = $this->getStockAlertRecipients($item);

        foreach ($recipients as $user) {
            try {
                $user->notify(new LowStockAlert($item, $alertType));
                
                Log::info('Stock alert sent', [
                    'user_id' => $user->id,
                    'item_id' => $item->id,
                    'alert_type' => $alertType,
                    'current_stock' => $item->current_stock
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send stock alert', [
                    'user_id' => $user->id,
                    'item_id' => $item->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Persist alert to database for feed/history
        $this->persistStockAlert($item, $alertType);
    }

    /**
     * Send bulk stock alert for multiple items
     */
    private function sendBulkStockAlert($lowStockItems, $criticalStockItems, $outOfStockItems)
    {
        $allItems = $lowStockItems->merge($criticalStockItems)->merge($outOfStockItems);
        
        if ($allItems->isEmpty()) {
            return;
        }

        $recipients = $this->getBulkAlertRecipients();
        
        foreach ($recipients as $user) {
            try {
                // Use the static method from LowStockAlert for bulk notifications
                LowStockAlert::createBulkAlert($allItems, $user);
                
                Log::info('Bulk stock alert sent', [
                    'user_id' => $user->id,
                    'total_items' => $allItems->count(),
                    'out_of_stock' => $outOfStockItems->count(),
                    'critical_stock' => $criticalStockItems->count(),
                    'low_stock' => $lowStockItems->count()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send bulk stock alert', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get users who should receive stock alerts for specific item
     */
    private function getStockAlertRecipients(InventoryItem $item)
    {
        $query = User::where('is_active', true);

        // Include inventory managers, warehouse staff, and admins
        $query->where(function($q) {
            $q->whereIn('role', ['admin', 'inventory_manager', 'warehouse_staff', 'manager'])
              ->orWhere('department', 'Inventory')
              ->orWhere('department', 'Warehouse')
              ->orWhere('department', 'Operations');
        });

        return $query->get();
    }

    /**
     * Get users who should receive bulk stock alerts
     */
    private function getBulkAlertRecipients()
    {
        return User::where('is_active', true)
            ->whereIn('role', ['admin', 'inventory_manager', 'manager'])
            ->get();
    }

    /**
     * Update inventory statistics cache
     */
    private function updateInventoryStatistics($lowStockItems, $criticalStockItems, $outOfStockItems)
    {
        $statistics = [
            'total_items' => InventoryItem::where('is_active', true)->count(),
            'low_stock_count' => $lowStockItems->count(),
            'critical_stock_count' => $criticalStockItems->count(),
            'out_of_stock_count' => $outOfStockItems->count(),
            'items_needing_attention' => $lowStockItems->count() + $criticalStockItems->count() + $outOfStockItems->count(),
            'last_updated' => now(),
            'low_stock_items' => $lowStockItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'item_code' => $item->item_code,
                    'current_stock' => $item->current_stock,
                    'minimum_level' => $item->minimum_stock_level,
                    'category' => $item->category->name ?? 'Uncategorized'
                ];
            })->toArray(),
            'critical_stock_items' => $criticalStockItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'item_code' => $item->item_code,
                    'current_stock' => $item->current_stock,
                    'minimum_level' => $item->minimum_stock_level,
                    'category' => $item->category->name ?? 'Uncategorized'
                ];
            })->toArray(),
            'out_of_stock_items' => $outOfStockItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'item_code' => $item->item_code,
                    'current_stock' => $item->current_stock,
                    'minimum_level' => $item->minimum_stock_level,
                    'category' => $item->category->name ?? 'Uncategorized'
                ];
            })->toArray()
        ];

        Cache::put('inventory_stock_statistics', $statistics, 1440); // Cache for 24 hours
    }

    /**
     * Get inventory stock statistics
     */
    public static function getInventoryStatistics()
    {
        return Cache::get('inventory_stock_statistics', [
            'total_items' => 0,
            'low_stock_count' => 0,
            'critical_stock_count' => 0,
            'out_of_stock_count' => 0,
            'items_needing_attention' => 0,
            'last_updated' => null,
            'low_stock_items' => [],
            'critical_stock_items' => [],
            'out_of_stock_items' => []
        ]);
    }

    /**
     * Generate reorder recommendations
     */
    public static function generateReorderRecommendations()
    {
        $items = InventoryItem::with('category')
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereRaw('current_stock <= minimum_stock_level')
                      ->orWhereRaw('current_stock <= reorder_point');
            })
            ->get();

        $recommendations = [];

        foreach ($items as $item) {
            $suggestedQuantity = static::calculateSuggestedReorderQuantity($item);
            $estimatedCost = $suggestedQuantity * ($item->unit_cost ?? 0);
            
            $recommendations[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_code' => $item->item_code,
                'category' => $item->category->name ?? 'Uncategorized',
                'current_stock' => $item->current_stock,
                'minimum_level' => $item->minimum_stock_level,
                'reorder_point' => $item->reorder_point ?? $item->minimum_stock_level,
                'suggested_quantity' => $suggestedQuantity,
                'unit_cost' => $item->unit_cost ?? 0,
                'estimated_cost' => $estimatedCost,
                'priority' => static::calculateReorderPriority($item),
                'preferred_vendor' => $item->preferred_vendor_id,
                'lead_time_days' => $item->lead_time_days ?? 7
            ];
        }

        // Sort by priority (critical first)
        usort($recommendations, function($a, $b) {
            $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return $recommendations;
    }

    /**
     * Calculate suggested reorder quantity
     */
    private static function calculateSuggestedReorderQuantity(InventoryItem $item)
    {
        $maxStock = $item->maximum_stock_level ?? ($item->minimum_stock_level * 3);
        $currentDeficit = max(0, $item->reorder_point - $item->current_stock);
        $suggestedQuantity = $maxStock - $item->current_stock + $currentDeficit;
        
        return max($suggestedQuantity, $item->minimum_stock_level);
    }

    /**
     * Calculate reorder priority
     */
    private static function calculateReorderPriority(InventoryItem $item)
    {
        if ($item->current_stock <= 0) {
            return 'critical';
        } elseif ($item->current_stock <= ($item->minimum_stock_level * 0.5)) {
            return 'high';
        } elseif ($item->current_stock <= $item->minimum_stock_level) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function persistStockAlert(InventoryItem $item, string $alertType): void
    {
        $severity = match($alertType) {
            'out_of_stock' => 'critical',
            'critical_stock' => 'critical',
            'low_stock' => 'warning',
            default => 'warning'
        };

        StockAlert::updateOrCreate(
            [
                'inventory_item_id' => $item->id,
                'alert_type' => $alertType,
                'status' => 'active',
            ],
            [
                'severity' => $severity,
                'current_stock' => $item->current_stock,
                'threshold' => $item->minimum_stock_level,
                'reorder_point' => $item->reorder_point ?? $item->minimum_stock_level,
                'unit' => $item->unit,
                'triggered_at' => now(),
                'message' => "{$item->name} ({$item->item_code}) {$alertType}.",
                'suggested_action' => $alertType === 'out_of_stock' ? 'Immediate restock required' : 'Create purchase order',
                'notified_channels' => ['database', 'mail'],
            ]
        );
    }

    private function resolveAlertsForItem(InventoryItem $item): void
    {
        $minimumLevel = $item->minimum_stock_level;
        $reorderPoint = $item->reorder_point ?? $minimumLevel;
        $currentStock = $item->current_stock;

        // Consider recovered when above both minimum level and reorder point
        if ($currentStock > max($minimumLevel, $reorderPoint)) {
            StockAlert::where('inventory_item_id', $item->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);
        }
    }
}