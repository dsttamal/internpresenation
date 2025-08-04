<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Submission Model
 * 
 * Represents a form submission with data and payment information.
 */
class Submission extends Model
{
    protected $table = 'submissions';

    protected $fillable = [
        'uniqueId',
        'editCode',
        'formId',
        'data',
        'submitterInfo',
        'paymentInfo',
        'status',
        'files',
        'adminNotes',
        'editHistory',
        'paymentMethod'
    ];

    protected $casts = [
        'data' => 'array',
        'submitterInfo' => 'array',
        'paymentInfo' => 'array',
        'files' => 'array',
        'editHistory' => 'array',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Payment method constants
    const PAYMENT_CARD = 'card';
    const PAYMENT_STRIPE = 'stripe';
    const PAYMENT_BKASH = 'bkash';
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    // Relationships
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'formId');
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function hasPaymentInfo(): bool
    {
        return !empty($this->paymentInfo);
    }

    public function getPaymentAmount(): ?float
    {
        $paymentInfo = $this->paymentInfo ?? [];
        return $paymentInfo['amount'] ?? null;
    }

    public function getPaymentStatus(): ?string
    {
        $paymentInfo = $this->paymentInfo ?? [];
        return $paymentInfo['status'] ?? null;
    }

    public function addToEditHistory(array $changes, ?int $userId = null): void
    {
        $history = $this->editHistory ?? [];
        $history[] = [
            'timestamp' => now()->toISOString(),
            'user_id' => $userId,
            'changes' => $changes
        ];
        $this->update(['editHistory' => $history]);
    }

    public function updateStatus(string $status, ?string $notes = null): void
    {
        $this->update([
            'status' => $status,
            'adminNotes' => $notes ? ($this->adminNotes . "\n" . $notes) : $this->adminNotes
        ]);
    }

    public function generateEditCode(): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update(['editCode' => $code]);
        return $code;
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByForm($query, int $formId)
    {
        return $query->where('formId', $formId);
    }

    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('paymentMethod', $method);
    }

    public function scopeWithPayment($query)
    {
        return $query->whereNotNull('paymentInfo');
    }

    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('createdAt', [$startDate, $endDate]);
    }
}
