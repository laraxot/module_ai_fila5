<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Module AI configuration
|--------------------------------------------------------------------------
| Existing keys preserved where possible. Prospect signal weights override
| WebsiteSignalCatalog::defaults() — set weight 0 to disable a gap detector.
*/

$existing = [];
// When published, hosts may merge; this file is the module default.

return array_replace_recursive([
    'prospect' => [
        'signals' => [
            /** @var array<string, int> */
            'weights' => [
                // 'not-mobile' => 4,
                // 'no-order-online' => 5,
            ],
            'slow_seconds' => 4.0,
        ],
        'draft' => [
            'send_permitted' => false,
            'daily_cap_hint' => 50,
        ],
    ],
], $existing);
