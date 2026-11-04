<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Pages;

use Modules\Xot\Filament\Pages\BaseSettingPage;

/** Tenant settings entry point for AI; no mutable settings are exposed yet. */
class SettingPage extends BaseSettingPage
{
    /**
     * @return array<string, object>
     */
    protected function getFormSchema(): array
    {
        return parent::getFormSchema();
    }
}
