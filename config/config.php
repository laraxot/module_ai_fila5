<?php

declare(strict_types=1);

return [
    'name' => 'AI',
    // 'icon' => 'heroicon-o-cog', // icon on dashboard
    // 'icon' => 'fas-air-freshener',
    'icon' => 'ui-brain',
    'navigation_sort' => 1,
    'fine_tuning_url' => config('FINE_TUNING_API_URL', 'http://localhost:8000/api/fine-tuning'), // Usare config() invece di env() per compatibilità con la cache di configurazione
];
