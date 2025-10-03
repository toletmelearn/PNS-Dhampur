<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClassDataVersion extends Model
{
    use HasFactory;

    protected $table = 'class_data_versions';

    protected $fillable = [
        'audit_id',
        'version_number',
        'data_snapshot',
        'changes_summary',
        'created_by',
        'version_type',
        'is_current_version',
        'parent_version_id',
        'merge_source_versions',
        'checksum',
        'size_bytes',
        'compression_type',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'data_snapshot' => 'array',
        'changes_summary' => 'array',
        'merge_source_versions' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_current_version' => 'boolean',
        'size_bytes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Version types
    const TYPE_AUTOMATIC = 'automatic';
    const TYPE_MANUAL = 'manual';
    const TYPE_SCHEDULED = 'scheduled';
    const TYPE_ROLLBACK = 'rollback';
    const TYPE_MERGE = 'merge';

    // Compression types
    const COMPRESSION_NONE = 'none';
    const COMPRESSION_GZIP = 'gzip';
    const COMPRESSION_JSON = 'json';

    /**
     * Relationships
     */
    public function audit()
    {
        return $this->belongsTo(ClassDataAudit::class, 'audit_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentVersion()
    {
        return $this->belongsTo(ClassDataVersion::class, 'parent_version_id');
    }

    public function childVersions()
    {
        return $this->hasMany(ClassDataVersion::class, 'parent_version_id');
    }

    /**
     * Scopes
     */
    public function scopeForAudit($query, $auditId)
    {
        return $query->where('audit_id', $auditId);
    }

    public function scopeCurrentVersion($query)
    {
        return $query->where('is_current_version', true);
    }

    public function scopeByVersionType($query, $type)
    {
        return $query->where('version_type', $type);
    }

    public function scopeByCreator($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeWithTags($query, array $tags)
    {
        return $query->whereJsonContains('tags', $tags);
    }

    public function scopeOrderByVersion($query, $direction = 'desc')
    {
        return $query->orderBy('version_number', $direction);
    }

    /**
     * Accessors & Mutators
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('M d, Y H:i:s');
    }

    public function getVersionTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->version_type));
    }

    public function getFormattedSizeAttribute()
    {
        if ($this->size_bytes < 1024) {
            return $this->size_bytes . ' B';
        } elseif ($this->size_bytes < 1048576) {
            return round($this->size_bytes / 1024, 2) . ' KB';
        } else {
            return round($this->size_bytes / 1048576, 2) . ' MB';
        }
    }

    public function getChangesCountAttribute()
    {
        return is_array($this->changes_summary) ? count($this->changes_summary) : 0;
    }

    /**
     * Helper Methods
     */
    public function isCurrent()
    {
        return $this->is_current_version;
    }

    public function isInitialVersion()
    {
        return $this->version_type === self::TYPE_AUTOMATIC;
    }

    public function isMergeVersion()
    {
        return $this->version_type === self::TYPE_MERGE;
    }

    public function isRollbackVersion()
    {
        return $this->version_type === self::TYPE_ROLLBACK;
    }

    public function hasParentVersion()
    {
        return !is_null($this->parent_version_id);
    }

    public function hasChildVersions()
    {
        return $this->childVersions()->exists();
    }

    public function generateChecksum()
    {
        $data = [
            'audit_id' => $this->audit_id,
            'version_number' => $this->version_number,
            'data_snapshot' => $this->data_snapshot,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at->toISOString()
        ];

        return hash('sha256', json_encode($data, 64)); // JSON_SORT_KEYS equivalent
    }

    public function verifyIntegrity()
    {
        return $this->checksum === $this->generateChecksum();
    }

    public function getDataSnapshot($decompress = true)
    {
        $snapshot = $this->data_snapshot;

        if ($decompress && $this->compression_type === self::COMPRESSION_GZIP) {
            $snapshot = json_decode(gzuncompress(base64_decode($snapshot)), true);
        }

        return $snapshot;
    }

    public function setDataSnapshot($data, $compress = false)
    {
        if ($compress) {
            $this->data_snapshot = base64_encode(gzcompress(json_encode($data)));
            $this->compression_type = self::COMPRESSION_GZIP;
        } else {
            $this->data_snapshot = $data;
            $this->compression_type = self::COMPRESSION_NONE;
        }

        $this->size_bytes = strlen(json_encode($this->data_snapshot));
    }

    public function compareWith(ClassDataVersion $otherVersion)
    {
        $thisSnapshot = $this->getDataSnapshot();
        $otherSnapshot = $otherVersion->getDataSnapshot();

        $differences = [];
        $allKeys = array_unique(array_merge(array_keys($thisSnapshot), array_keys($otherSnapshot)));

        foreach ($allKeys as $key) {
            $thisValue = $thisSnapshot[$key] ?? null;
            $otherValue = $otherSnapshot[$key] ?? null;

            if ($thisValue !== $otherValue) {
                $differences[$key] = [
                    'this_version' => $thisValue,
                    'other_version' => $otherValue,
                    'change_type' => $this->determineChangeType($thisValue, $otherValue)
                ];
            }
        }

        return $differences;
    }

    private function determineChangeType($thisValue, $otherValue)
    {
        if (is_null($thisValue) && !is_null($otherValue)) {
            return 'added';
        } elseif (!is_null($thisValue) && is_null($otherValue)) {
            return 'removed';
        } else {
            return 'modified';
        }
    }

    public function createRollbackVersion($targetVersionId, $userId)
    {
        $targetVersion = static::findOrFail($targetVersionId);
        
        $rollbackVersion = new static([
            'audit_id' => $this->audit_id,
            'version_number' => $this->getNextVersionNumber(),
            'data_snapshot' => $targetVersion->data_snapshot,
            'changes_summary' => [
                'rollback_to_version' => $targetVersion->version_number,
                'rollback_reason' => 'Manual rollback',
                'original_version' => $this->version_number
            ],
            'created_by' => $userId,
            'version_type' => self::TYPE_ROLLBACK,
            'is_current_version' => true,
            'parent_version_id' => $this->id,
            'compression_type' => $targetVersion->compression_type,
            'metadata' => [
                'rollback_target' => $targetVersionId,
                'rollback_timestamp' => now()->toISOString()
            ]
        ]);

        $rollbackVersion->checksum = $rollbackVersion->generateChecksum();
        $rollbackVersion->save();

        // Update current version flags
        $this->update(['is_current_version' => false]);

        return $rollbackVersion;
    }

    public function getNextVersionNumber()
    {
        $maxVersion = static::where('audit_id', $this->audit_id)
            ->max('version_number');

        return ($maxVersion ?? 0) + 1;
    }

    /**
     * Static Methods
     */
    public static function createVersion($auditId, $dataSnapshot, $options = [])
    {
        $audit = ClassDataAudit::findOrFail($auditId);
        
        // Get next version number
        $versionNumber = static::where('audit_id', $auditId)->max('version_number') + 1;

        // Mark previous versions as not current
        static::where('audit_id', $auditId)->update(['is_current_version' => false]);

        $version = new static([
            'audit_id' => $auditId,
            'version_number' => $versionNumber,
            'data_snapshot' => $dataSnapshot,
            'changes_summary' => $options['changes_summary'] ?? [],
            'created_by' => $options['created_by'] ?? auth()->id(),
            'version_type' => $options['version_type'] ?? self::TYPE_MANUAL,
            'is_current_version' => true,
            'parent_version_id' => $options['parent_version_id'] ?? null,
            'merge_source_versions' => $options['merge_source_versions'] ?? [],
            'compression_type' => $options['compression_type'] ?? self::COMPRESSION_NONE,
            'metadata' => $options['metadata'] ?? [],
            'tags' => $options['tags'] ?? []
        ]);

        if (isset($options['compress']) && $options['compress']) {
            $version->setDataSnapshot($dataSnapshot, true);
        } else {
            $version->setDataSnapshot($dataSnapshot, false);
        }

        $version->checksum = $version->generateChecksum();
        $version->save();

        return $version;
    }

    public static function getVersionHistory($auditId, $limit = null)
    {
        $query = static::where('audit_id', $auditId)
            ->with(['creator', 'parentVersion'])
            ->orderBy('version_number', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public static function getVersionStatistics($auditId = null)
    {
        $query = static::query();
        
        if ($auditId) {
            $query->where('audit_id', $auditId);
        }

        return [
            'total_versions' => $query->count(),
            'by_type' => $query->selectRaw('version_type, COUNT(*) as count')
                ->groupBy('version_type')
                ->pluck('count', 'version_type')
                ->toArray(),
            'total_size' => $query->sum('size_bytes'),
            'average_size' => $query->avg('size_bytes'),
            'current_versions' => $query->where('is_current_version', true)->count(),
            'compressed_versions' => $query->where('compression_type', '!=', self::COMPRESSION_NONE)->count()
        ];
    }
}