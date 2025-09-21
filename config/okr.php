<?php

return [
    'name' => 'OKR',
    'description' => 'OKR Module',
    'version' => '1.0.0',
    
    'routing' => [
        'prefix' => 'okr',
        'middleware' => ['web', 'auth'],
    ],
    
    'guard' => 'web',
    
    'navigation' => [
        'main' => [
            'okr' => [
                'title' => 'OKR',
                'icon' => 'heroicon-o-target',
                'route' => 'okr.dashboard',
            ],
        ],
    ],
    
    'sidebar' => [
        'okr' => [
            'title' => 'OKR',
            'icon' => 'heroicon-o-target',
            'items' => [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'route' => 'okr.dashboard',
                    'icon' => 'heroicon-o-home',
                ],
                'cycles' => [
                    'title' => 'Zyklen',
                    'route' => 'okr.cycles',
                    'icon' => 'heroicon-o-calendar',
                ],
                'objectives' => [
                    'title' => 'Objectives',
                    'route' => 'okr.objectives',
                    'icon' => 'heroicon-o-flag',
                ],
                'key-results' => [
                    'title' => 'Key Results',
                    'route' => 'okr.key-results',
                    'icon' => 'heroicon-o-check-circle',
                ],
            ],
        ],
    ],
];
