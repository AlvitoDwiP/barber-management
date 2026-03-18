<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    use HasFactory;

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';
    public const DEFAULT_SERVICE_COMMISSION_TYPE = self::TYPE_PERCENT;
    public const DEFAULT_SERVICE_COMMISSION_VALUE = '50.00';
    public const DEFAULT_PRODUCT_COMMISSION_TYPE = self::TYPE_FIXED;
    public const DEFAULT_PRODUCT_COMMISSION_VALUE = '5000.00';

    protected $fillable = [
        'default_service_commission_type',
        'default_service_commission_value',
        'default_product_commission_type',
        'default_product_commission_value',
    ];

    public static function validTypes(): array
    {
        return [
            self::TYPE_PERCENT,
            self::TYPE_FIXED,
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'default_service_commission_type' => self::DEFAULT_SERVICE_COMMISSION_TYPE,
            'default_service_commission_value' => self::DEFAULT_SERVICE_COMMISSION_VALUE,
            'default_product_commission_type' => self::DEFAULT_PRODUCT_COMMISSION_TYPE,
            'default_product_commission_value' => self::DEFAULT_PRODUCT_COMMISSION_VALUE,
        ];
    }

    protected function casts(): array
    {
        return [
            'default_service_commission_value' => 'decimal:2',
            'default_product_commission_value' => 'decimal:2',
        ];
    }
}
