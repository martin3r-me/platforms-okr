<?php

return [
    'name' => 'Zielsteuerung',
    'description' => 'Zielsteuerung Module',
    'version' => '1.0.0',
    
    // Scope-Type: 'parent' = root-scoped (immer Root-Team-ID), 'single' = team-spezifisch
    'scope_type' => 'parent',
    
    'routing' => [
        'mode' => env('OKR_MODE', 'path'),
        'prefix' => 'okr',
    ],
    'guard' => 'web',

    'navigation' => [
        'route' => 'okr.dashboard',
        'icon'  => 'heroicon-o-flag',
        'order' => 35,
    ],

    'billables' => [
        [
            'model' => \Platform\Okr\Models\Okr::class,
            'type' => 'per_item',
            'label' => 'Zielsteuerung',
            'description' => 'Jede erstellte Zielsteuerung verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                [
                    'cost_per_day' => 0.01,
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                ]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
        [
            'model' => \Platform\Okr\Models\Cycle::class,
            'type' => 'per_item',
            'label' => 'Zielsteuerung-Cycle',
            'description' => 'Jeder erstellte Cycle verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                [
                    'cost_per_day' => 0.005,
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                ]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
        [
            'model' => \Platform\Okr\Models\Objective::class,
            'type' => 'per_item',
            'label' => 'Zielsteuerung-Objective',
            'description' => 'Jedes erstellte Objective verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                [
                    'cost_per_day' => 0.0025,
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                ]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
        [
            'model' => \Platform\Okr\Models\KeyResult::class,
            'type' => 'per_item',
            'label' => 'Zielsteuerung-Erfolgskriterium',
            'description' => 'Jedes erstellte Erfolgskriterium verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                [
                    'cost_per_day' => 0.001,
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                ]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ]
    ]
];
