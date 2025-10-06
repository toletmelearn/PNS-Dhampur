<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class BiometricDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'device_id',
        'device_name',
        'device_type',
        'ip_address',
        'port',
        'location',
        'manufacturer',
        'model',
        'firmware_version',
        'api_endpoint',
        'api_key',
        'connection_type',
        'status',
        'last_sync_at',
        'last_heartbeat_at',
        'configuration',
        'is_active',
        'registered_by',
        'notes'
    ];

    protected $casts = [
        'configuration' => 'array',
        'last_sync_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'last_sync_at',
        'last_heartbeat_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Device types
    const DEVICE_TYPES = [
        'fingerprint' => 'Fingerprint Scanner',
        'face' => 'Face Recognition',
        'iris' => 'Iris Scanner',
        'rfid' => 'RFID Card Reader',
        'palm' => 'Palm Recognition',
        'hybrid' => 'Multi-Modal Device'
    ];

    // Connection types
    const CONNECTION_TYPES = [
        'tcp' => 'TCP/IP',
        'http' => 'HTTP API',
        'websocket' => 'WebSocket',
        'serial' => 'Serial Port',
        'usb' => 'USB Connection'
    ];

    // Device status
    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_ERROR = 'error';

    /**
     * Get the user who registered this device
     */
    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Get biometric attendance records from this device
     */
    public function attendanceRecords()
    {
        return $this->hasMany(BiometricAttendance::class, 'device_id', 'device_id');
    }

    /**
     * Get biometric data templates stored for this device
     */
    public function biometricData()
    {
        return $this->hasMany(BiometricData::class, 'device_id', 'device_id');
    }

    /**
     * Check if device is online
     */
    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE && 
               $this->last_heartbeat_at && 
               $this->last_heartbeat_at->diffInMinutes(now()) <= 5;
    }

    /**
     * Check if device needs maintenance
     */
    public function needsMaintenance(): bool
    {
        return $this->status === self::STATUS_MAINTENANCE ||
               ($this->last_sync_at && $this->last_sync_at->diffInHours(now()) > 24);
    }

    /**
     * Get device uptime percentage
     */
    public function getUptimePercentage(int $days = 30): float
    {
        // This would calculate based on heartbeat logs
        // For now, return a simulated value
        return $this->isOnline() ? 98.5 : 85.2;
    }

    /**
     * Get today's scan count
     */
    public function getTodaysScanCount(): int
    {
        return $this->attendanceRecords()
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get device configuration with defaults
     */
    public function getConfiguration(): array
    {
        $defaults = [
            'timeout' => 30,
            'retry_attempts' => 3,
            'heartbeat_interval' => 60,
            'sync_interval' => 300,
            'max_records_per_sync' => 1000,
            'enable_real_time' => true,
            'log_level' => 'info'
        ];

        return array_merge($defaults, $this->configuration ?? []);
    }

    /**
     * Update device heartbeat
     */
    public function updateHeartbeat(): void
    {
        $this->update([
            'last_heartbeat_at' => now(),
            'status' => self::STATUS_ONLINE
        ]);
    }

    /**
     * Mark device as offline
     */
    public function markOffline(): void
    {
        $this->update([
            'status' => self::STATUS_OFFLINE
        ]);
    }

    /**
     * Update sync timestamp
     */
    public function updateSyncTimestamp(): void
    {
        $this->update([
            'last_sync_at' => now()
        ]);
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for online devices
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE)
                    ->where('last_heartbeat_at', '>=', now()->subMinutes(5));
    }

    /**
     * Scope for devices by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('device_type', $type);
    }

    /**
     * Get formatted device info
     */
    public function getDeviceInfoAttribute(): string
    {
        return "{$this->device_name} ({$this->device_type}) - {$this->location}";
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ONLINE => 'badge-success',
            self::STATUS_OFFLINE => 'badge-danger',
            self::STATUS_MAINTENANCE => 'badge-warning',
            self::STATUS_ERROR => 'badge-danger',
            default => 'badge-secondary'
        };
    }
}