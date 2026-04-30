<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Phase 1: User Model
 * Authentication and authorization for all system users
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone_number',
        'role',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has loan officer role
     */
    public function isLoanOfficer(): bool
    {
        return $this->role === 'loan_officer';
    }

    /**
     * Check if user has collector role
     */
    public function isCollector(): bool
    {
        return $this->role === 'collector';
    }

    /**
     * Check if user has borrower role
     */
    public function isBorrower(): bool
    {
        return $this->role === 'borrower';
    }

    /**
     * Check if user has finance role
     */
    public function isFinance(): bool
    {
        return $this->role === 'finance';
    }

    /**
     * Check if user can perform action
     */
    public function canPerformAction(string $action): bool
    {
        $permissionMap = [
            // Admin can do everything
            'admin' => true,
            
            // Loan officers can originate and manage loans
            'loan_officer' => in_array($action, ['create_loan', 'approve_loan', 'view_loan']),
            
            // Collectors can manage collections
            'collector' => in_array($action, ['create_task', 'log_contact', 'view_task']),
            
            // Borrowers can view their loans
            'borrower' => in_array($action, ['view_own_loan', 'make_payment']),
            
            // Finance can view reports
            'finance' => in_array($action, ['view_reports', 'export_data']),
        ];

        return $permissionMap[$this->role] ?? false;
    }

    /**
     * Update last login timestamp
     */
    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Generate API token for service-to-service communication
     */
    public function generateServiceToken(string $name = 'api-token'): string
    {
        return $this->createToken($name)->plainTextToken;
    }

    /**
     * OAuth tokens relationship
     */
    public function oauthTokens()
    {
        return $this->hasMany(OAuthToken::class);
    }

    /**
     * Audit logs relationship
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
