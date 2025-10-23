<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'maintenance_schedule_id',
        'vendor_id',
        'performed_by', // user id or external
        'maintenance_date',
        'maintenance_type', // preventive, corrective, emergency, inspection, calibration, upgrade
        'issue_description',
        'work_performed',
        'parts_replaced',
        'actual_cost',
        'downtime_hours',
        'status', // completed, failed, pending_validation
        'warranty_claim', // bool
        'warranty_notes',
        'attachments',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'maintenance_date' => 'datetime',
        'actual_cost' => 'decimal:2',
        'downtime_hours' => 'decimal:2',
        'warranty_claim' => 'boolean',
        'attachments' => 'array',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function schedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}