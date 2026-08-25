<?php

declare(strict_types=1);

return [
    'name' => 'AI',
<<<<<<< HEAD
   'description' => 'Integrazione AI e fine-tuning',
=======
    'description' => 'Integrazione AI e fine-tuning',
>>>>>>> laraxot/dev
    'icon' => 'ai-icon',
    'navigation' => [
        'enabled' => true,
        'sort' => 1,
    ],
    'fine_tuning_url' => config('services.openai.fine_tuning_url', 'http://localhost:8000/api/fine-tuning'),
];
