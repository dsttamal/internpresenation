<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Model
 * 
 * Represents payment transactions and details.
 * 
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Payment",
 *     description="Payment transaction details",
 *     @OA\Property(property="id", type="integer", example=1, description="Payment ID"),
 *     @OA\Property(property="submissionId", type="integer", example=123, description="Related submission ID"),
 *     @OA\Property(property="paymentMethod", type="string", example="stripe", description="Payment method used"),
 *     @OA\Property(property="paymentId", type="string", example="pi_1234567890", description="External payment ID"),
 *     @OA\Property(property="amount", type="number", format="float", example=99.99, description="Payment amount"),
 *     @OA\Property(property="currency", type="string", example="USD", description="Payment currency"),
 *     @OA\Property(property="status", type="string", example="completed", description="Payment status"),
 *     @OA\Property(property="metadata", type="object", example={"orderId": "ORD-123"}, description="Additional payment metadata"),
 *     @OA\Property(property="receiptUrl", type="string", example="https://example.com/receipt/123", description="Receipt URL"),
 *     @OA\Property(property="refundedAmount", type="number", format="float", example=0.00, description="Refunded amount"),
 *     @OA\Property(property="createdAt", type="string", format="date-time", example="2024-01-20T10:30:00Z"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", example="2024-01-20T11:00:00Z")
 * )
 */
class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'submissionId',
        'paymentMethod',
        'paymentId',
        'amount',
        'currency',
        'status',
        'metadata',
        'receiptUrl',
        'refundedAmount',
        'failureReason',
        'processedAt',
        'refundedAt'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refundedAmount' => 'decimal:2',
        'metadata' => 'array',
        'processedAt' => 'datetime',
        'refundedAt' => 'datetime',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime'
    ];

    // Payment method constants
    const METHOD_STRIPE = 'stripe';
    const METHOD_BKASH = 'bkash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CARD = 'card';
    const METHOD_PAYPAL = 'paypal';

    // Payment status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    // Currency constants
    const CURRENCY_USD = 'USD';
    const CURRENCY_BDT = 'BDT';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_GBP = 'GBP';

    // Relationships
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submissionId');
    }

    /**
     * Get available payment methods
     */
    public static function getAvailableMethods()
    {
        return [
            self::METHOD_STRIPE => [
                'name' => 'Credit/Debit Card (Stripe)',
                'enabled' => true,
                'description' => 'Pay securely with your credit or debit card',
                'currencies' => [self::CURRENCY_USD, self::CURRENCY_EUR, self::CURRENCY_GBP],
                'icon' => 'credit-card'
            ],
            self::METHOD_BKASH => [
                'name' => 'bKash',
                'enabled' => true,
                'description' => 'Pay with bKash mobile wallet',
                'currencies' => [self::CURRENCY_BDT],
                'icon' => 'mobile'
            ],
            self::METHOD_BANK_TRANSFER => [
                'name' => 'Bank Transfer',
                'enabled' => true,
                'description' => 'Transfer money directly from your bank account',
                'currencies' => [self::CURRENCY_USD, self::CURRENCY_BDT, self::CURRENCY_EUR],
                'icon' => 'bank'
            ]
        ];
    }

    /**
     * Get supported currencies
     */
    public static function getSupportedCurrencies()
    {
        return [
            self::CURRENCY_USD => [
                'name' => 'US Dollar',
                'symbol' => '$',
                'code' => 'USD'
            ],
            self::CURRENCY_BDT => [
                'name' => 'Bangladeshi Taka',
                'symbol' => '৳',
                'code' => 'BDT'
            ],
            self::CURRENCY_EUR => [
                'name' => 'Euro',
                'symbol' => '€',
                'code' => 'EUR'
            ],
            self::CURRENCY_GBP => [
                'name' => 'British Pound',
                'symbol' => '£',
                'code' => 'GBP'
            ]
        ];
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded()
    {
        return $this->status === self::STATUS_COMPLETED && 
               $this->refundedAmount < $this->amount;
    }

    /**
     * Get remaining refundable amount
     */
    public function getRefundableAmount()
    {
        if (!$this->canBeRefunded()) {
            return 0;
        }

        return $this->amount - $this->refundedAmount;
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted($paymentId = null, $receiptUrl = null)
    {
        $this->status = self::STATUS_COMPLETED;
        $this->processedAt = now();
        
        if ($paymentId) {
            $this->paymentId = $paymentId;
        }
        
        if ($receiptUrl) {
            $this->receiptUrl = $receiptUrl;
        }

        return $this->save();
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed($reason = null)
    {
        $this->status = self::STATUS_FAILED;
        $this->failureReason = $reason;
        
        return $this->save();
    }

    /**
     * Record a refund
     */
    public function recordRefund($amount, $refundId = null)
    {
        $this->refundedAmount += $amount;
        
        if ($this->refundedAmount >= $this->amount) {
            $this->status = self::STATUS_REFUNDED;
        } else {
            $this->status = self::STATUS_PARTIALLY_REFUNDED;
        }
        
        $this->refundedAt = now();
        
        // Update metadata with refund information
        $metadata = $this->metadata ?: [];
        $metadata['refunds'] = $metadata['refunds'] ?? [];
        $metadata['refunds'][] = [
            'amount' => $amount,
            'refundId' => $refundId,
            'refundedAt' => now()->toISOString()
        ];
        $this->metadata = $metadata;

        return $this->save();
    }

    /**
     * Get payment summary for analytics
     */
    public static function getPaymentSummary($startDate = null, $endDate = null)
    {
        $query = static::query();
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $payments = $query->get();
        
        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'completed_payments' => $payments->where('status', self::STATUS_COMPLETED)->count(),
            'completed_amount' => $payments->where('status', self::STATUS_COMPLETED)->sum('amount'),
            'failed_payments' => $payments->where('status', self::STATUS_FAILED)->count(),
            'pending_payments' => $payments->where('status', self::STATUS_PENDING)->count(),
            'refunded_amount' => $payments->sum('refundedAmount'),
            'by_method' => $payments->groupBy('paymentMethod')->map->count(),
            'by_currency' => $payments->groupBy('currency')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total_amount' => $items->sum('amount')
                ];
            })
        ];
    }

    /**
     * Format amount for display
     */
    public function getFormattedAmount()
    {
        $currencies = static::getSupportedCurrencies();
        $currency = $currencies[$this->currency] ?? $currencies[self::CURRENCY_USD];
        
        return $currency['symbol'] . number_format($this->amount, 2);
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColor()
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_PENDING, self::STATUS_PROCESSING => 'warning',
            self::STATUS_FAILED, self::STATUS_CANCELLED => 'danger',
            self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED => 'info',
            default => 'secondary'
        };
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for payments by method
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('paymentMethod', $method);
    }
}
