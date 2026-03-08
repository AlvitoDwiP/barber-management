<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'item_type',
        'item_id',
        'item_name',
        'item_price',
        'qty',
        'subtotal',
        'commission',
    ];

    protected function casts(): array
    {
        return [
            'item_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'commission' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
