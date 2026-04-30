<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Phase 1: Base Model
 * Provides common functionality for all models
 */
abstract class BaseModel extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return $this->hasMany(\App\Models\AuditLog::class, 'entity_id')
            ->where('entity_type', $this->getTable());
    }

    /**
     * Create audit log entry
     */
    public function logAudit(string $action, array $oldValues = [], array $newValues = [], ?int $userId = null): void
    {
        \App\Models\AuditLog::create([
            'entity_type' => $this->getTable(),
            'entity_id' => $this->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }

    /**
     * Get readable attribute name
     */
    public function getReadableAttributes(): array
    {
        return $this->getAttributes();
    }
}
