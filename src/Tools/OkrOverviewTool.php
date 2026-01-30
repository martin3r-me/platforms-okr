<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class OkrOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'okr.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/overview - Zeigt Übersicht über OKR-Konzepte und Beziehungen (OKRs, CycleTemplates, Cycles, Objectives, Key Results, Performance). EMPFOHLEN als Einstieg, bevor du CRUD-Tools nutzt.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        return ToolResult::success([
            'module' => 'okr',
            'scope' => [
                'scope_type' => 'parent',
                'description' => 'OKR ist root-scoped (scope_type=parent): Daten hängen am Root-Team (nicht am aktuellen Unterteam).',
                'team_resolution' => 'Tools sollten intern die Root-Team-ID aus dem aktuellen Team ableiten.',
            ],
            'entities' => [
                'forecasts' => [
                    'description' => 'Regnosen (Forecasts) - Strategische Ausrichtung & Transformationssteuerung. Enthalten versionierbaren Content und Fokusräume.',
                    'relations' => ['forecast -> focus_areas', 'forecast -> versions'],
                    'important_fields' => ['title', 'target_date', 'content (versionierbar)'],
                ],
                'focus_areas' => [
                    'description' => 'Fokusräume - gehören zu einer Regnose. Enthalten Zielbilder, Hindernisse und Meilensteine.',
                    'relations' => ['focus_area -> vision_images', 'focus_area -> obstacles', 'focus_area -> milestones'],
                    'important_fields' => ['title', 'description', 'content', 'order'],
                ],
                'vision_images' => [
                    'description' => 'Zielbilder - gehören zu einem Fokusraum.',
                    'important_fields' => ['title', 'description', 'order'],
                ],
                'obstacles' => [
                    'description' => 'Hindernisse - gehören zu einem Fokusraum.',
                    'important_fields' => ['title', 'description', 'order'],
                ],
                'milestones' => [
                    'description' => 'Meilensteine - gehören zu einem Fokusraum. Haben optional target_year und target_quarter.',
                    'important_fields' => ['title', 'description', 'target_year', 'target_quarter', 'order'],
                    'note' => 'target_quarter kann nur gesetzt werden, wenn target_year gesetzt ist.',
                ],
                'okrs' => [
                    'description' => 'OKR-Container (z.B. Company/Team OKR). Enthält mehrere Cycles.',
                    'relations' => ['okr -> cycles', 'okr -> objectives (über cycles)', 'okr -> key_results (über objectives)'],
                ],
                'cycle_templates' => [
                    'description' => 'Zeit-Templates (starts_at/ends_at/type). Markieren mit is_current, welche Perioden aktuell sind.',
                    'important_fields' => ['type', 'starts_at', 'ends_at', 'is_current', 'is_standard'],
                ],
                'cycles' => [
                    'description' => 'Konkrete Instanz eines Zyklus für ein OKR (okr_id + cycle_template_id). Enthält Objectives.',
                    'relations' => ['cycle -> objectives -> key_results'],
                    'status' => ['draft', 'active', 'ending_soon', 'completed', 'past'],
                ],
                'objectives' => [
                    'description' => 'Hauptziele in einem Cycle (cycle_id required). Enthält Key Results.',
                    'relations' => ['objective -> key_results'],
                ],
                'key_results' => [
                    'description' => 'Messbare Ergebnisse zu einem Objective. Gehören indirekt immer zu einem Cycle (über objective).',
                    'value_types' => [
                        'note' => 'Der KR-Typ ist aktuell am latest_performance.type (okr_key_result_performances.type) gespeichert.',
                        'supported' => [
                            'boolean' => [
                                'meaning' => 'Binär: erreicht oder nicht erreicht.',
                                'fields' => ['is_completed'],
                            ],
                            'absolute' => [
                                'meaning' => 'Absolut: current_value / target_value.',
                                'fields' => ['current_value', 'target_value'],
                            ],
                            'relative' => [
                                'meaning' => 'Relativ: current_value / target_value (intern als percentage gespeichert).',
                                'mapping' => ['relative' => 'percentage'],
                                'fields' => ['current_value', 'target_value'],
                            ],
                        ],
                        'internal_types' => [
                            'percentage' => 'wird nach außen als relative gezeigt',
                            'calculated' => 'wird nach außen als absolute gezeigt (z.B. Counter Sync)',
                        ],
                        'tool_output' => 'Tools liefern zusätzlich value_summary.value_type (boolean|absolute|relative) + value + progress_percent.',
                    ],
                ],
                'performances' => [
                    'description' => 'Snapshots/Performance-Daten (Team/Okr/Cycle/Objective/KeyResult). READ-ONLY.',
                ],
            ],
            'relationships' => [
                'core' => 'OKR → Cycles (cycle_template_id) → Objectives (cycle_id) → Key Results (objective_id)',
                'forecasts' => 'Forecast → FocusAreas → VisionImages/Obstacles/Milestones',
                'current_cycles' => 'CycleTemplates.is_current=true markieren aktuelle Perioden; Cycles referenzieren Templates.',
            ],
            'workflows' => [
                'create_cycle' => [
                    'step_1' => 'okr.okrs.GET / okr.okr.GET → okr_id auswählen',
                    'step_2' => 'okr.cycle_templates.GET → passendes Template wählen (ggf. is_current=true)',
                    'step_3' => 'okr.cycles.POST (okr_id + cycle_template_id)',
                ],
                'create_objective' => [
                    'step_1' => 'okr.cycles.GET oder okr.cycle.GET → cycle_id auswählen',
                    'step_2' => 'okr.objectives.POST (cycle_id + title + ... + optional vision_id/regnose_id)',
                ],
                'create_key_result' => [
                    'step_1' => 'okr.objectives.GET (cycle_id) → objective_id auswählen',
                    'step_2' => 'okr.key_results.POST (cycle_id + objective_id + title + ...)',
                ],
                'read_performance' => [
                    'step_1' => 'okr.performances.GET scope=team|okr|cycle|objective|key_result + ids',
                    'note' => 'Performance ist READ-ONLY (keine Schreibtools).',
                ],
            ],
            'related_tools' => [
                'entry' => [
                    'overview' => 'okr.overview.GET',
                ],
                'read' => [
                    'forecasts' => ['okr.forecasts.GET', 'okr.forecast.GET'],
                    'focus_areas' => ['okr.focus_areas.GET'],
                    'vision_images' => ['okr.vision_images.GET'],
                    'obstacles' => ['okr.obstacles.GET'],
                    'milestones' => ['okr.milestones.GET'],
                    'okrs' => [
                        'okr.okrs.GET' => 'Listet OKRs (alle Team-Mitglieder sehen alle OKRs). Um "meine OKRs" (die ich angelegt habe) zu finden: my_okrs=true oder filters=[{"field":"user_id","value":USER_ID}]. Um "OKRs die ich verwalte" zu finden: managed_okrs=true oder filters=[{"field":"manager_user_id","value":USER_ID}].',
                        'okr.okr.GET',
                    ],
                    'cycle_templates' => ['okr.cycle_templates.GET'],
                    'cycles' => ['okr.cycles.GET', 'okr.cycle.GET'],
                    'objectives' => ['okr.objectives.GET', 'okr.objective.GET'],
                    'key_results' => ['okr.key_results.GET', 'okr.key_result.GET'],
                    'performances' => ['okr.performances.GET'],
                ],
                'write' => [
                    'forecasts' => ['okr.forecasts.POST', 'okr.forecasts.PUT', 'okr.forecasts.DELETE'],
                    'focus_areas' => [
                        'okr.focus_areas.POST',
                        'okr.focus_areas.PUT',
                        'okr.focus_areas.DELETE',
                        'okr.focus_areas.bulk.POST',
                        'okr.focus_areas.bulk.PUT',
                        'okr.focus_areas.bulk.DELETE',
                    ],
                    'vision_images' => [
                        'okr.vision_images.POST',
                        'okr.vision_images.PUT',
                        'okr.vision_images.DELETE',
                        'okr.vision_images.bulk.POST',
                        'okr.vision_images.bulk.PUT',
                        'okr.vision_images.bulk.DELETE',
                    ],
                    'obstacles' => [
                        'okr.obstacles.POST',
                        'okr.obstacles.PUT',
                        'okr.obstacles.DELETE',
                        'okr.obstacles.bulk.POST',
                        'okr.obstacles.bulk.PUT',
                        'okr.obstacles.bulk.DELETE',
                    ],
                    'milestones' => [
                        'okr.milestones.POST',
                        'okr.milestones.PUT',
                        'okr.milestones.DELETE',
                        'okr.milestones.bulk.POST',
                        'okr.milestones.bulk.PUT',
                        'okr.milestones.bulk.DELETE',
                    ],
                    'cycles' => ['okr.cycles.POST', 'okr.cycles.PUT', 'okr.cycles.DELETE'],
                    'objectives' => ['okr.objectives.POST', 'okr.objectives.PUT', 'okr.objectives.DELETE'],
                    'key_results' => ['okr.key_results.POST', 'okr.key_results.PUT', 'okr.key_results.DELETE'],
                ],
            ],
        ]);
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'okr', 'concepts', 'structure'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


