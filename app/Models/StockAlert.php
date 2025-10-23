<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class StockAlert extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'alert_type', // low_stock, critical_stock, out_of_stock, overstock
        'severity',   // warning, critical
        'current_stock',
        'threshold',
        'reorder_point',
        'unit',
        'status', // active, acknowledged, resolved
        'triggered_at',
        'acknowledged_at',
        'resolved_at',
        'acknowledged_by',
        'resolved_by',
        'message',
        'suggested_action',
        'notified_channels',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'threshold' => 'integer',
        'reorder_point' => 'integer',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'notified_channels' => 'array',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->whereHas('item', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    // Helpers
    public function acknowledge($userId)
    {
        return $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);
    }

    public function resolve($userId)
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }
}