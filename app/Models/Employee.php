<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    use HasFactory;

    public const EMPLOYMENT_TYPE_PERMANENT = 'permanent';
    public const EMPLOYMENT_TYPE_FREELANCE = 'freelance';

    protected $fillable = [
        'name',
        'employment_type',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopePermanent(Builder $query): Builder
    {
        return $query->where('employment_type', self::EMPLOYMENT_TYPE_PERMANENT);
    }

    public function scopeFreelance(Builder $query): Builder
    {
        return $query->where('employment_type', self::EMPLOYMENT_TYPE_FREELANCE);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function payrollResults(): HasMany
    {
        return $this->hasMany(PayrollResult::class);
    }

    public function freelancePayments(): HasMany
    {
        return $this->hasMany(FreelancePayment::class);
    }

    public static function employmentTypes(): array
    {
        return [
            self::EMPLOYMENT_TYPE_PERMANENT,
            self::EMPLOYMENT_TYPE_FREELANCE,
        ];
    }

    public function isPermanent(): bool
    {
        return $this->employment_type === self::EMPLOYMENT_TYPE_PERMANENT;
    }

    public function isFreelance(): bool
    {
        return $this->employment_type === self::EMPLOYMENT_TYPE_FREELANCE;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isInactive(): bool
    {
        return ! $this->isActive();
    }

    public function operationalStatusLabel(): string
    {
        return $this->isActive() ? 'Aktif' : 'Nonaktif';
    }

    public function hasHistoricalRecords(): bool
    {
        return $this->relationCountIsPositive('transactions_count', fn (): bool => $this->transactions()->exists())
            || $this->relationCountIsPositive('payroll_results_count', fn (): bool => $this->payrollResults()->exists())
            || $this->relationCountIsPositive('freelance_payments_count', fn (): bool => $this->freelancePayments()->exists());
    }

    public function canBeDeletedPhysically(): bool
    {
        return ! $this->hasHistoricalRecords();
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return match ($this->employment_type) {
            self::EMPLOYMENT_TYPE_PERMANENT => 'Permanent',
            self::EMPLOYMENT_TYPE_FREELANCE => 'Freelance',
            default => ucfirst((string) $this->employment_type),
        };
    }

    public function setEmploymentTypeAttribute(?string $value): void
    {
        $normalizedValue = $this->normalizeEmploymentType($value);

        $this->attributes['employment_type'] = $normalizedValue;

        if ($normalizedValue !== null) {
            $this->attributes['status'] = $normalizedValue === self::EMPLOYMENT_TYPE_PERMANENT
                ? 'tetap'
                : 'freelance';
        }
    }

    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = $value;

        $normalizedValue = $this->normalizeEmploymentType($value);

        if ($normalizedValue !== null) {
            $this->attributes['employment_type'] = $normalizedValue;
        }
    }

    private function normalizeEmploymentType(?string $value): ?string
    {
        return match ($value) {
            self::EMPLOYMENT_TYPE_PERMANENT, 'tetap' => self::EMPLOYMENT_TYPE_PERMANENT,
            self::EMPLOYMENT_TYPE_FREELANCE, 'freelance' => self::EMPLOYMENT_TYPE_FREELANCE,
            default => blank($value) ? null : $value,
        };
    }

    private function relationCountIsPositive(string $countAttribute, callable $fallback): bool
    {
        $count = $this->getAttribute($countAttribute);

        if ($count !== null) {
            return (int) $count > 0;
        }

        return $fallback();
    }
}
