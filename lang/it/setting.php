<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Impostazioni',
        'group' => 'Configurazione',
        'icon' => 'heroicon-o-cog-6-tooth',
        'sort' => 99,
    ],
    'title' => 'Impostazioni',
    'actions' => [
        'save' => [
            'label' => 'Salva',
            'icon' => 'heroicon-o-check',
            'tooltip' => 'Salva',
        ],
        'profile' => [
            'label' => 'Profilo',
            'icon' => 'heroicon-o-user-circle',
            'tooltip' => 'Profilo',
        ],
        'logout' => [
            'label' => 'Esci',
            'icon' => 'heroicon-o-arrow-left-on-rectangle',
            'tooltip' => 'Esci',
        ],
    ],
];
