<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function payrollResults(): HasMany
    {
        return $this->hasMany(PayrollResult::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payroll_id');
    }

    protected static function booted(): void
    {
        static::updating(function (PayrollPeriod $payrollPeriod): void {
            if (! $payrollPeriod->isDirty(['start_date', 'end_date'])) {
                return;
            }

            $isClosingLegacyOpen = $payrollPeriod->getOriginal('status') === 'open'
                && $payrollPeriod->status === 'closed'
                && $payrollPeriod->getOriginal('end_date') === null
                && $payrollPeriod->isDirty('end_date');

            if ($isClosingLegacyOpen) {
                return;
            }

            $isClosed = $payrollPeriod->getOriginal('status') === 'closed' || $payrollPeriod->status === 'closed';
            $hasLinkedData = $payrollPeriod->transactions()->exists() || $payrollPeriod->payrollResults()->exists();

            if ($isClosed || $hasLinkedData) {
                throw new DomainException('Periode payroll tidak dapat diubah setelah payroll dibuat.');
            }
        });
    }
}
