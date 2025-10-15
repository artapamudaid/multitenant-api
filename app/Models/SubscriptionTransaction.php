<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'package_id',
        'previous_package_id',
        'type',
        'status',
        'amount',
        'currency',
        'duration_months',
        'started_at',
        'expires_at',
        'completed_at',
        'payment_method',
        'payment_reference',
        'invoice_number',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function previousPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'previous_package_id');
    }

    // Helper methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getTypeDisplayAttribute(): string
    {
        $types = [
            'registration' => 'Registrasi',
            'upgrade' => 'Upgrade Paket',
            'downgrade' => 'Downgrade Paket',
            'renewal' => 'Perpanjangan',
            'cancellation' => 'Pembatalan',
            'trial_start' => 'Mulai Trial',
            'trial_end' => 'Akhir Trial',
        ];

        return $types[$this->type] ?? $this->type;
    }

    public function getStatusBadgeAttribute(): array
    {
        $badges = [
            'pending' => ['text' => 'Menunggu', 'color' => 'yellow'],
            'completed' => ['text' => 'Selesai', 'color' => 'green'],
            'failed' => ['text' => 'Gagal', 'color' => 'red'],
            'refunded' => ['text' => 'Refund', 'color' => 'blue'],
        ];

        return $badges[$this->status] ?? ['text' => $this->status, 'color' => 'gray'];
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return "{$prefix}-{$date}-{$random}";
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
