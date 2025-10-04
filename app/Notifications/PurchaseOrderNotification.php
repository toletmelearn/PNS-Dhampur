<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PurchaseOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $purchaseOrder;
    protected $type;
    protected $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder, string $type, array $additionalData = [])
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->type = $type;
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        // Send email for critical notifications
        if (in_array($this->type, ['approval_required', 'approved', 'rejected', 'overdue', 'escalated'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting($this->getEmailGreeting())
            ->line($this->getEmailMessage());

        // Add action button based on notification type
        if ($this->type === 'approval_required') {
            $mail->action('Review Purchase Order', $this->getPurchaseOrderUrl());
        } elseif (in_array($this->type, ['approved', 'sent_to_vendor'])) {
            $mail->action('View Purchase Order', $this->getPurchaseOrderUrl());
        } elseif ($this->type === 'overdue') {
            $mail->action('Track Delivery', $this->getPurchaseOrderUrl());
        }

        // Add purchase order details
        $mail->line('**Purchase Order Details:**')
            ->line('PO Number: ' . $this->purchaseOrder->po_number)
            ->line('Vendor: ' . $this->purchaseOrder->vendor->name)
            ->line('Total Amount: ₹' . number_format($this->purchaseOrder->total_amount, 2))
            ->line('Status: ' . ucfirst($this->purchaseOrder->status));

        if ($this->purchaseOrder->expected_delivery_date) {
            $mail->line('Expected Delivery: ' . $this->purchaseOrder->expected_delivery_date->format('M d, Y'));
        }

        // Add additional context based on type
        if ($this->type === 'overdue') {
            $mail->line('**Overdue by:** ' . $this->purchaseOrder->days_overdue . ' days');
        } elseif ($this->type === 'rejected' && isset($this->additionalData['reason'])) {
            $mail->line('**Rejection Reason:** ' . $this->additionalData['reason']);
        } elseif ($this->type === 'changes_requested' && isset($this->additionalData['comments'])) {
            $mail->line('**Requested Changes:** ' . $this->additionalData['comments']);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'purchase_order_id' => $this->purchaseOrder->id,
            'po_number' => $this->purchaseOrder->po_number,
            'vendor_name' => $this->purchaseOrder->vendor->name,
            'total_amount' => $this->purchaseOrder->total_amount,
            'status' => $this->purchaseOrder->status,
            'priority' => $this->getNotificationPriority(),
            'action_url' => $this->getPurchaseOrderUrl(),
            'additional_data' => $this->additionalData,
            'created_at' => now()->toISOString()
        ];
    }

    /**
     * Get notification title based on type
     */
    private function getNotificationTitle(): string
    {
        switch ($this->type) {
            case 'approval_required':
                return 'Purchase Order Approval Required';
            case 'approved':
                return 'Purchase Order Approved';
            case 'rejected':
                return 'Purchase Order Rejected';
            case 'sent_to_vendor':
                return 'Purchase Order Sent to Vendor';
            case 'received':
                return 'Purchase Order Items Received';
            case 'completed':
                return 'Purchase Order Completed';
            case 'cancelled':
                return 'Purchase Order Cancelled';
            case 'overdue':
                return 'Purchase Order Overdue';
            case 'changes_requested':
                return 'Changes Requested for Purchase Order';
            case 'escalated':
                return 'Purchase Order Escalated';
            case 'auto_generated':
                return 'Purchase Order Auto-Generated';
            default:
                return 'Purchase Order Update';
        }
    }

    /**
     * Get notification message based on type
     */
    private function getNotificationMessage(): string
    {
        $poNumber = $this->purchaseOrder->po_number;
        $vendorName = $this->purchaseOrder->vendor->name;
        $amount = '₹' . number_format($this->purchaseOrder->total_amount, 2);

        switch ($this->type) {
            case 'approval_required':
                return "Purchase Order {$poNumber} for {$vendorName} ({$amount}) requires your approval.";
            case 'approved':
                return "Purchase Order {$poNumber} for {$vendorName} has been approved and is ready to be sent.";
            case 'rejected':
                $reason = $this->additionalData['reason'] ?? 'No reason provided';
                return "Purchase Order {$poNumber} for {$vendorName} has been rejected. Reason: {$reason}";
            case 'sent_to_vendor':
                return "Purchase Order {$poNumber} has been sent to {$vendorName}.";
            case 'received':
                $itemsCount = $this->additionalData['items_count'] ?? 'Some';
                return "{$itemsCount} items from Purchase Order {$poNumber} have been received.";
            case 'completed':
                return "Purchase Order {$poNumber} for {$vendorName} has been completed successfully.";
            case 'cancelled':
                return "Purchase Order {$poNumber} for {$vendorName} has been cancelled.";
            case 'overdue':
                $daysOverdue = $this->purchaseOrder->days_overdue;
                return "Purchase Order {$poNumber} from {$vendorName} is overdue by {$daysOverdue} days.";
            case 'changes_requested':
                return "Changes have been requested for Purchase Order {$poNumber}. Please review and update.";
            case 'escalated':
                $escalatedTo = $this->additionalData['escalated_to'] ?? 'higher authority';
                return "Purchase Order {$poNumber} has been escalated to {$escalatedTo} for approval.";
            case 'auto_generated':
                $itemsCount = $this->additionalData['items_count'] ?? 'multiple';
                return "Purchase Order {$poNumber} has been auto-generated for {$itemsCount} low stock items from {$vendorName}.";
            default:
                return "Purchase Order {$poNumber} status has been updated.";
        }
    }

    /**
     * Get email subject based on type
     */
    private function getEmailSubject(): string
    {
        $poNumber = $this->purchaseOrder->po_number;
        
        switch ($this->type) {
            case 'approval_required':
                return "Action Required: Approve Purchase Order {$poNumber}";
            case 'approved':
                return "Purchase Order {$poNumber} Approved";
            case 'rejected':
                return "Purchase Order {$poNumber} Rejected";
            case 'overdue':
                return "URGENT: Purchase Order {$poNumber} Overdue";
            case 'escalated':
                return "Escalated: Purchase Order {$poNumber} Approval Required";
            default:
                return "Purchase Order {$poNumber} Update";
        }
    }

    /**
     * Get email greeting based on type
     */
    private function getEmailGreeting(): string
    {
        switch ($this->type) {
            case 'approval_required':
            case 'escalated':
                return 'Hello!';
            case 'overdue':
                return 'Urgent Notice!';
            default:
                return 'Hello!';
        }
    }

    /**
     * Get email message based on type
     */
    private function getEmailMessage(): string
    {
        switch ($this->type) {
            case 'approval_required':
                return 'A new purchase order requires your approval. Please review the details below and take appropriate action.';
            case 'approved':
                return 'The purchase order has been approved and is now ready to be processed.';
            case 'rejected':
                return 'The purchase order has been rejected. Please review the rejection reason and take necessary action.';
            case 'overdue':
                return 'This purchase order is overdue for delivery. Please follow up with the vendor immediately.';
            case 'escalated':
                return 'This purchase order has been escalated to you for approval due to amount limits or other factors.';
            case 'auto_generated':
                return 'A purchase order has been automatically generated based on low stock levels. Please review and approve if appropriate.';
            default:
                return 'The purchase order status has been updated. Please review the details below.';
        }
    }

    /**
     * Get notification priority based on type
     */
    private function getNotificationPriority(): string
    {
        switch ($this->type) {
            case 'overdue':
                return 'urgent';
            case 'approval_required':
            case 'escalated':
                return 'high';
            case 'rejected':
            case 'changes_requested':
                return 'medium';
            default:
                return 'normal';
        }
    }

    /**
     * Get purchase order URL
     */
    private function getPurchaseOrderUrl(): string
    {
        return URL::to("/purchase-orders/{$this->purchaseOrder->id}");
    }

    /**
     * Static method to create approval notification
     */
    public static function approvalRequired(PurchaseOrder $purchaseOrder): self
    {
        return new self($purchaseOrder, 'approval_required');
    }

    /**
     * Static method to create approved notification
     */
    public static function approved(PurchaseOrder $purchaseOrder): self
    {
        return new self($purchaseOrder, 'approved');
    }

    /**
     * Static method to create rejected notification
     */
    public static function rejected(PurchaseOrder $purchaseOrder, string $reason): self
    {
        return new self($purchaseOrder, 'rejected', ['reason' => $reason]);
    }

    /**
     * Static method to create overdue notification
     */
    public static function overdue(PurchaseOrder $purchaseOrder): self
    {
        return new self($purchaseOrder, 'overdue');
    }

    /**
     * Static method to create escalated notification
     */
    public static function escalated(PurchaseOrder $purchaseOrder, string $escalatedTo): self
    {
        return new self($purchaseOrder, 'escalated', ['escalated_to' => $escalatedTo]);
    }

    /**
     * Static method to create auto-generated notification
     */
    public static function autoGenerated(PurchaseOrder $purchaseOrder, int $itemsCount): self
    {
        return new self($purchaseOrder, 'auto_generated', ['items_count' => $itemsCount]);
    }

    /**
     * Static method to create changes requested notification
     */
    public static function changesRequested(PurchaseOrder $purchaseOrder, string $comments): self
    {
        return new self($purchaseOrder, 'changes_requested', ['comments' => $comments]);
    }

    /**
     * Static method to create sent to vendor notification
     */
    public static function sentToVendor(PurchaseOrder $purchaseOrder): self
    {
        return new self($purchaseOrder, 'sent_to_vendor');
    }

    /**
     * Static method to create items received notification
     */
    public static function itemsReceived(PurchaseOrder $purchaseOrder, int $itemsCount): self
    {
        return new self($purchaseOrder, 'received', ['items_count' => $itemsCount]);
    }

    /**
     * Static method to create completed notification
     */
    public static function completed(PurchaseOrder $purchaseOrder): self
    {
        return new self($purchaseOrder, 'completed');
    }
}