<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Forecast;
use Platform\Organization\Services\EntityAncestorService;
use Platform\Organization\Services\EntityDimensionBridge;
use Platform\Organization\Models\OrganizationEntity;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    #[On('updateSidebar')] 
    public function updateSidebar()
    {
        
    }

    // Modal State
    public $modalShow = false;
    
    // Form Data
    public $title = '';
    public $description = '';
    public $performance_score = 0;
    public $auto_transfer = false;
    public $is_template = false;
    public $manager_user_id = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'performance_score' => 'required|numeric|min:0|max:100',
        'auto_transfer' => 'boolean',
        'is_template' => 'boolean',
        'manager_user_id' => 'nullable|exists:users,id',
    ];

    public function openCreateModal()
    {
        $this->modalShow = true;
    }

    public function closeCreateModal()
    {
        $this->modalShow = false;
        $this->resetForm();
    }

    public function createOkr()
    {
        $this->validate();
        
        $user = Auth::user();
        $baseTeam = $user->currentTeamRelation;
        
        // Für Parent Tools (scope_type = 'parent') das Root-Team verwenden
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;
        
        $okr = \Platform\Okr\Models\Okr::create([
            'title' => $this->title,
            'description' => $this->description ?: null,
            'performance_score' => $this->performance_score ?: 0,
            'auto_transfer' => $this->auto_transfer,
            'is_template' => $this->is_template,
            'manager_user_id' => $this->manager_user_id ?: null,
            'team_id' => $teamId,
            'user_id' => $user->id,
        ]);

        $this->resetForm();
        $this->modalShow = false;
        
        session()->flash('message', 'Zielsteuerung erfolgreich erstellt!');
        
        // Sidebar aktualisieren
        $this->dispatch('updateSidebar');
    }

    public function resetForm()
    {
        $this->reset([
            'title', 'description', 'performance_score', 'auto_transfer', 
            'is_template', 'manager_user_id'
        ]);
        $this->performance_score = 0;
        $this->auto_transfer = false;
        $this->is_template = false;
    }



    public function render()
    {
        if (!Auth::check() || !Auth::user()->currentTeamRelation) {
            return view('okr::livewire.sidebar', [
                'entityTypeGroups' => collect(),
                'unlinkedOkrs' => collect(),
                'forecasts' => collect(),
                'okrs' => collect(),
                'users' => collect(),
            ]);
        }

        $user = Auth::user();
        $baseTeam = $user->currentTeamRelation;
        
        // Für Parent Tools (scope_type = 'parent') das Root-Team verwenden
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;
        
        // Team-basierte OKRs holen (inkl. Performance für Score-Badge)
        $okrs = Okr::query()
            ->with('performance')
            ->where('team_id', $teamId)
            ->orderBy('title')
            ->get();

        // Team-basierte Forecasts holen
        $forecasts = Forecast::query()
            ->where('team_id', $teamId)
            ->orderBy('target_date', 'desc')
            ->orderBy('title')
            ->get();

        // Users für Manager-Dropdown (vom Root-Team wenn Parent Tool)
        $rootTeam = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam() 
            : $baseTeam;
        
        $users = $rootTeam->users()
            ->orderBy('name')
            ->get();

        // Entity-Gruppierung: OKRs an Organisations-Knoten haengen
        [$entityTypeGroups, $unlinkedOkrs] = $this->buildEntityTypeGroups($okrs);

        return view('okr::livewire.sidebar', [
            'entityTypeGroups' => $entityTypeGroups,
            'unlinkedOkrs' => $unlinkedOkrs,
            'okrs' => $okrs,
            'forecasts' => $forecasts,
            'users' => $users,
        ]);
    }

    /**
     * Gruppiert OKRs nach dem Organisations-Entity-Baum (EntityType -> Entity-Baum -> OKRs).
     * Spiegelt die Planner-Sidebar-Logik, nur mit morph-alias 'okr'. Verknuepfung laeuft
     * ueber OrganizationDimensionLink (siehe OkrEntityLinkProvider). OKRs ohne Entity-Link
     * landen in der zweiten Rueckgabe (unverknuepft).
     *
     * @param  \Illuminate\Support\Collection  $okrs
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    protected function buildEntityTypeGroups($okrs): array
    {
        $entityTypeGroups = collect();

        $okrIds = $okrs->pluck('id')->toArray();
        if (empty($okrIds)) {
            return [$entityTypeGroups, collect()];
        }

        // 1. Entity-Verknuepfungen via DimensionLink laden
        $entityOkrMap = []; // entity_id => [okr_ids]
        $linkedOkrIds = [];

        $entityLinks = EntityDimensionBridge::linksForLinkables(['okr'], $okrIds);
        foreach ($entityLinks as $link) {
            $entityOkrMap[$link->entity_id][] = $link->linkable_id;
            $linkedOkrIds[] = $link->linkable_id;
        }
        foreach ($entityOkrMap as $entityId => $ids) {
            $entityOkrMap[$entityId] = array_unique($ids);
        }
        $linkedOkrIds = array_unique($linkedOkrIds);

        // 2. Aufwaerts-Traversierung: Tree-Parents UND Channel-Targets (engagement_with).
        //    Customer wird virtual-parent von Engagement -> zweite parallele Sicht ohne Datenduplikat.
        $ancestorService = new EntityAncestorService();
        $expandedEntityIds = $ancestorService->expandEntitiesWithAncestors(
            array_keys($entityOkrMap),
            ['engagement_with']
        );
        foreach ($expandedEntityIds as $entityId) {
            if (!isset($entityOkrMap[$entityId])) {
                $entityOkrMap[$entityId] = [];
            }
        }

        $entityIds = array_keys($entityOkrMap);
        if (empty($entityIds)) {
            return [$entityTypeGroups, $okrs->values()];
        }

        $entities = OrganizationEntity::with('type')
            ->whereIn('id', $entityIds)
            ->get()
            ->keyBy('id');

        $hierarchy = $ancestorService->buildParentChildrenMap($entities, ['engagement_with']);
        $entityChildrenMap = $hierarchy['parent_to_children'];
        $rootEntityIds = $hierarchy['roots'];

        // 3. Rekursiver Baum-Builder
        $buildTree = function (int $entityId) use (&$buildTree, $entities, $entityChildrenMap, $entityOkrMap, $okrs): ?array {
            $entity = $entities->get($entityId);
            if (!$entity) {
                return null;
            }

            $childIds = $entityChildrenMap[$entityId] ?? [];
            $childNodes = collect($childIds)
                ->map(fn ($childId) => $buildTree($childId))
                ->filter();

            $childrenByType = $childNodes
                ->groupBy(fn ($child) => $child['type_id'])
                ->map(function ($group) use ($entities) {
                    $firstChild = $group->first();
                    $typeEntity = $entities->get($firstChild['entity_id']);
                    $type = $typeEntity?->type;

                    return [
                        'type_id' => $firstChild['type_id'],
                        'type_name' => $type?->name ?? 'Sonstige',
                        'type_icon' => $type?->icon ?? null,
                        'sort_order' => $type?->sort_order ?? 999,
                        'children' => $group->sortBy('entity_name')->values(),
                    ];
                })
                ->sortBy('sort_order')
                ->values();

            $nodeOkrs = collect($entityOkrMap[$entityId] ?? [])
                ->map(fn ($oid) => $okrs->firstWhere('id', $oid))
                ->filter()
                ->values();

            // Gesamtzahl OKRs (eigene + aller Kinder)
            $totalOkrs = $nodeOkrs->count();
            foreach ($childNodes as $child) {
                $totalOkrs += $child['total_okrs'];
            }

            // Entity nur anzeigen wenn sie OKRs hat oder Kinder mit OKRs
            if ($totalOkrs === 0) {
                return null;
            }

            return [
                'entity_id' => $entityId,
                'entity_name' => $entity->name,
                'type_id' => $entity->type?->id,
                'okrs' => $nodeOkrs,
                'children_by_type' => $childrenByType,
                'total_okrs' => $totalOkrs,
            ];
        };

        // 4. Root-Entities nach Typ gruppieren
        $groupedByType = [];
        foreach ($rootEntityIds as $entityId) {
            $entity = $entities->get($entityId);
            if (!$entity || !$entity->type) {
                continue;
            }

            $tree = $buildTree($entityId);
            if (!$tree) {
                continue;
            }

            $typeId = $entity->type->id;
            if (!isset($groupedByType[$typeId])) {
                $groupedByType[$typeId] = [
                    'type_id' => $typeId,
                    'type_name' => $entity->type->name,
                    'type_icon' => $entity->type->icon,
                    'sort_order' => $entity->type->sort_order ?? 999,
                    'entities' => [],
                ];
            }
            $groupedByType[$typeId]['entities'][] = $tree;
        }

        $entityTypeGroups = collect($groupedByType)
            ->sortBy('sort_order')
            ->map(function ($group) {
                $group['entities'] = collect($group['entities'])
                    ->sortBy('entity_name')
                    ->values();
                return $group;
            })
            ->values();

        // 5. Unverknuepfte OKRs
        $unlinkedOkrs = $okrs->filter(fn ($okr) => !in_array($okr->id, $linkedOkrIds))->values();

        return [$entityTypeGroups, $unlinkedOkrs];
    }
}
