<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreelancePayment extends Model
{
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'employee_id',
        'work_date',
        'total_service_amount',
        'service_commission',
        'total_product_qty',
        'product_commission',
        'total_commission',
        'expense_id',
        'paid_at',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'total_service_amount' => 'decimal:2',
            'service_commission' => 'decimal:2',
            'total_product_qty' => 'integer',
            'product_commission' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === self::STATUS_UNPAID;
    }
}
