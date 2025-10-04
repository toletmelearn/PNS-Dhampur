<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\InventoryItem;
use Illuminate\Support\Collection;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $inventoryItem;
    protected $alertType;
    protected $stockLevel;
    protected $minimumLevel;
    protected $reorderPoint;

    /**
     * Create a new notification instance.
     */
    public function __construct(InventoryItem $inventoryItem, $alertType = 'low_stock')
    {
        $this->inventoryItem = $inventoryItem;
        $this->alertType = $alertType; // 'low_stock', 'out_of_stock', 'critical_stock'
        $this->stockLevel = $inventoryItem->current_stock;
        $this->minimumLevel = $inventoryItem->minimum_stock_level;
        $this->reorderPoint = $inventoryItem->reorder_point ?? $inventoryItem->minimum_stock_level;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = ['database'];
        
        // Add email for out of stock and critical alerts
        if (in_array($this->alertType, ['out_of_stock', 'critical_stock'])) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $subject = $this->getEmailSubject();
        $greeting = $this->getEmailGreeting();
        $message = $this->getEmailMessage();
        
        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($message)
            ->line($this->getItemDetails())
            ->action('View Inventory Dashboard', url('/inventory-management'))
            ->line('Please take immediate action to restock this item.')
            ->salutation('Best regards, PNS Dhampur Inventory System');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'low_stock_alert',
            'alert_type' => $this->alertType,
            'inventory_item_id' => $this->inventoryItem->id,
            'item_name' => $this->inventoryItem->name,
            'item_code' => $this->inventoryItem->item_code,
            'category' => $this->inventoryItem->category->name ?? 'Uncategorized',
            'current_stock' => $this->stockLevel,
            'minimum_stock_level' => $this->minimumLevel,
            'reorder_point' => $this->reorderPoint,
            'unit' => $this->inventoryItem->unit,
            'location' => $this->inventoryItem->location,
            'is_asset' => $this->inventoryItem->is_asset,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'priority' => $this->getPriority(),
            'action_url' => '/inventory-management/items/' . $this->inventoryItem->id,
            'suggested_action' => $this->getSuggestedAction(),
            'created_at' => now(),
        ];
    }

    /**
     * Get notification title based on alert type
     */
    private function getNotificationTitle()
    {
        switch ($this->alertType) {
            case 'out_of_stock':
                return 'Out of Stock Alert';
            case 'critical_stock':
                return 'Critical Stock Level Alert';
            case 'low_stock':
                return 'Low Stock Alert';
            default:
                return 'Inventory Alert';
        }
    }

    /**
     * Get notification message
     */
    private function getNotificationMessage()
    {
        $itemName = $this->inventoryItem->name;
        $itemCode = $this->inventoryItem->item_code;
        
        switch ($this->alertType) {
            case 'out_of_stock':
                return "Item '{$itemName}' ({$itemCode}) is out of stock. Current stock: {$this->stockLevel} {$this->inventoryItem->unit}";
            case 'critical_stock':
                return "Critical: Item '{$itemName}' ({$itemCode}) has reached critical stock level. Current stock: {$this->stockLevel} {$this->inventoryItem->unit}";
            case 'low_stock':
                return "Item '{$itemName}' ({$itemCode}) is running low. Current stock: {$this->stockLevel} {$this->inventoryItem->unit}, Minimum required: {$this->minimumLevel} {$this->inventoryItem->unit}";
            default:
                return "Stock alert for item '{$itemName}' ({$itemCode})";
        }
    }

    /**
     * Get email subject
     */
    private function getEmailSubject()
    {
        $itemName = $this->inventoryItem->name;
        
        switch ($this->alertType) {
            case 'out_of_stock':
                return "🚨 Out of Stock Alert: {$itemName} - PNS Dhampur";
            case 'critical_stock':
                return "⚠️ Critical Stock Alert: {$itemName} - PNS Dhampur";
            case 'low_stock':
                return "📦 Low Stock Alert: {$itemName} - PNS Dhampur";
            default:
                return "Inventory Alert: {$itemName} - PNS Dhampur";
        }
    }

    /**
     * Get email greeting
     */
    private function getEmailGreeting()
    {
        switch ($this->alertType) {
            case 'out_of_stock':
            case 'critical_stock':
                return 'Urgent Inventory Alert!';
            default:
                return 'Inventory Notification';
        }
    }

    /**
     * Get email message
     */
    private function getEmailMessage()
    {
        $itemName = $this->inventoryItem->name;
        $itemCode = $this->inventoryItem->item_code;
        
        switch ($this->alertType) {
            case 'out_of_stock':
                return "The inventory item '{$itemName}' (Code: {$itemCode}) is completely out of stock. Immediate restocking is required to avoid operational disruptions.";
            case 'critical_stock':
                return "The inventory item '{$itemName}' (Code: {$itemCode}) has reached a critical stock level. Urgent restocking is recommended to prevent stockouts.";
            case 'low_stock':
                return "The inventory item '{$itemName}' (Code: {$itemCode}) is running low and needs to be restocked soon to maintain adequate inventory levels.";
            default:
                return "An inventory alert has been triggered for item '{$itemName}' (Code: {$itemCode}). Please review the current stock status.";
        }
    }

    /**
     * Get item details for email
     */
    private function getItemDetails()
    {
        $category = $this->inventoryItem->category->name ?? 'Uncategorized';
        $location = $this->inventoryItem->location ?? 'Not specified';
        $suggestedReorder = $this->calculateSuggestedReorderQuantity();
        
        return sprintf(
            "Item Details:\n• Item Code: %s\n• Category: %s\n• Location: %s\n• Current Stock: %s %s\n• Minimum Level: %s %s\n• Reorder Point: %s %s\n• Suggested Reorder Quantity: %s %s",
            $this->inventoryItem->item_code,
            $category,
            $location,
            $this->stockLevel,
            $this->inventoryItem->unit,
            $this->minimumLevel,
            $this->inventoryItem->unit,
            $this->reorderPoint,
            $this->inventoryItem->unit,
            $suggestedReorder,
            $this->inventoryItem->unit
        );
    }

    /**
     * Calculate suggested reorder quantity
     */
    private function calculateSuggestedReorderQuantity()
    {
        $maxStock = $this->inventoryItem->maximum_stock_level ?? ($this->minimumLevel * 3);
        $currentDeficit = max(0, $this->reorderPoint - $this->stockLevel);
        $suggestedQuantity = $maxStock - $this->stockLevel + $currentDeficit;
        
        return max($suggestedQuantity, $this->minimumLevel);
    }

    /**
     * Get notification priority
     */
    private function getPriority()
    {
        switch ($this->alertType) {
            case 'out_of_stock':
                return 'critical';
            case 'critical_stock':
                return 'high';
            case 'low_stock':
                return 'medium';
            default:
                return 'low';
        }
    }

    /**
     * Get suggested action
     */
    private function getSuggestedAction()
    {
        switch ($this->alertType) {
            case 'out_of_stock':
                return 'Create emergency purchase order immediately';
            case 'critical_stock':
                return 'Create urgent purchase order';
            case 'low_stock':
                return 'Schedule reorder within next few days';
            default:
                return 'Review stock levels and reorder if necessary';
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Create bulk low stock alert for multiple items
     */
    public static function createBulkAlert(Collection $lowStockItems, $notifiable)
    {
        $bulkData = [
            'type' => 'bulk_low_stock_alert',
            'alert_type' => 'bulk_low_stock',
            'total_items' => $lowStockItems->count(),
            'out_of_stock_count' => $lowStockItems->where('current_stock', '<=', 0)->count(),
            'critical_stock_count' => $lowStockItems->where('current_stock', '>', 0)
                ->where('current_stock', '<=', function($item) { return $item->minimum_stock_level * 0.5; })->count(),
            'low_stock_count' => $lowStockItems->where('current_stock', '>', function($item) { return $item->minimum_stock_level * 0.5; })
                ->where('current_stock', '<=', function($item) { return $item->minimum_stock_level; })->count(),
            'items' => $lowStockItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'item_code' => $item->item_code,
                    'current_stock' => $item->current_stock,
                    'minimum_stock_level' => $item->minimum_stock_level,
                    'unit' => $item->unit,
                    'category' => $item->category->name ?? 'Uncategorized',
                ];
            })->toArray(),
            'title' => 'Bulk Low Stock Alert',
            'message' => "Multiple items ({$lowStockItems->count()}) require attention due to low stock levels.",
            'priority' => 'high',
            'action_url' => '/inventory-management/low-stock',
            'created_at' => now(),
        ];

        return $notifiable->notify(new \Illuminate\Notifications\DatabaseNotification([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => self::class,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'data' => $bulkData,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}