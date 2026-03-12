<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'employee_name',
        'total_transactions',
        'total_transaction_count',
        'total_services',
        'total_products',
        'total_service_amount',
        'total_service_commission',
        'total_product_commission',
        'total_commission',
    ];

    protected function casts(): array
    {
        return [
            'total_transaction_count' => 'integer',
            'total_transactions' => 'integer',
            'total_services' => 'integer',
            'total_products' => 'integer',
            'total_service_amount' => 'decimal:2',
            'total_service_commission' => 'decimal:2',
            'total_product_commission' => 'decimal:2',
            'total_commission' => 'decimal:2',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDisplayEmployeeNameAttribute(): string
    {
        return $this->employee_name ?: ($this->employee?->name ?? '-');
    }
}
