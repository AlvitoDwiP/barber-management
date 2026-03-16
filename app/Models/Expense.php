<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Expense extends Model
{
    use HasFactory;

    public const CATEGORY_ELECTRICITY = 'listrik';
    public const CATEGORY_PRODUCT_STOCK = 'beli produk stok';
    public const CATEGORY_EQUIPMENT = 'beli alat';
    public const CATEGORY_PAY_FREELANCE = 'bayar freelance';
    public const CATEGORY_OTHER = 'lainnya';

    protected $fillable = [
        'expense_date',
        'category',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public static function categories(): array
    {
        return [
            self::CATEGORY_ELECTRICITY,
            self::CATEGORY_PRODUCT_STOCK,
            self::CATEGORY_EQUIPMENT,
            self::CATEGORY_PAY_FREELANCE,
            self::CATEGORY_OTHER,
        ];
    }

    public function freelancePayment(): HasOne
    {
        return $this->hasOne(FreelancePayment::class);
    }
}
