<?php

namespace App\Services;

use App\Models\CommissionSetting;

class CommissionSettingsService
{
    private const SETTINGS_ID = 1;

    public function get(): CommissionSetting
    {
        $settings = CommissionSetting::query()->find(self::SETTINGS_ID);

        if ($settings instanceof CommissionSetting) {
            return $settings;
        }

        $settings = new CommissionSetting(CommissionSetting::defaultAttributes());
        $settings->id = self::SETTINGS_ID;
        $settings->save();

        return $settings;
    }

    public function getDefaultServiceCommission(): array
    {
        $settings = $this->get();

        return [
            'commission_type' => $settings->default_service_commission_type,
            'commission_value' => $settings->default_service_commission_value,
        ];
    }

    public function getDefaultProductCommission(): array
    {
        $settings = $this->get();

        return [
            'commission_type' => $settings->default_product_commission_type,
            'commission_value' => $settings->default_product_commission_value,
        ];
    }
}
