<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    use HasFactory;

    // Official snapshot model for rows stored in transaction_items.
    protected $fillable = [
        'transaction_id',
        'item_type',
        'service_id',
        'product_id',
        'employee_id',
        'employee_name',
        'employee_employment_type',
        'item_name',
        'unit_price',
        'qty',
        'subtotal',
        'commission_source',
        'commission_type',
        'commission_value',
        'commission_amount',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'qty' => 'integer',
            'subtotal' => 'decimal:2',
            'employee_id' => 'integer',
            'commission_value' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
