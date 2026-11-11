<?php

namespace Platform\Okr\Livewire\Panel;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Core\Livewire\Terminal\Concerns\WithTerminalContext;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Services\KeyResultContextLinks;

/**
 * OKR-Reiter im Kontext-Panel: den aktuellen Datensatz (z. B. eine Aufgabe) mit
 * einem oder mehreren Key Results verknüpfen und wieder lösen. Über das Projekt
 * abgedeckte KRs werden mit angezeigt, lassen sich hier aber nicht lösen.
 */
class KeyResults extends Component
{
    use WithTerminalContext;

    public string $search = '';

    #[Computed]
    public function linked(): array
    {
        if (! $this->hasContext()) {
            return [];
        }

        return app(KeyResultContextLinks::class)
            ->linkedTo($this->contextType, $this->contextId)
            ->map(fn (KeyResult $kr) => $this->present($kr))
            ->values()
            ->all();
    }

    #[Computed]
    public function covered(): array
    {
        if (! $this->hasContext()) {
            return [];
        }

        $direct = array_column($this->linked, 'id');

        return app(KeyResultContextLinks::class)
            ->coveredFor($this->contextType, $this->contextId)
            ->reject(fn ($row) => in_array($row['key_result']->id, $direct, true))
            ->map(fn ($row) => $this->present($row['key_result']) + ['via' => $row['via']])
            ->values()
            ->all();
    }

    #[Computed]
    public function candidates(): array
    {
        $teamId = $this->okrTeamId();
        if (! $teamId || ! $this->hasContext()) {
            return [];
        }

        $exclude = array_merge(array_column($this->linked, 'id'), array_column($this->covered, 'id'));

        return app(KeyResultContextLinks::class)
            ->candidates($teamId, trim($this->search), $exclude, 25)
            ->map(fn (KeyResult $kr) => $this->present($kr))
            ->values()
            ->all();
    }

    public function link(int $keyResultId): void
    {
        $this->apply(fn (KeyResultContextLinks $links, KeyResult $kr) => $links->link($kr, $this->contextType, $this->contextId), $keyResultId);
    }

    public function unlink(int $keyResultId): void
    {
        $this->apply(fn (KeyResultContextLinks $links, KeyResult $kr) => $links->unlink($kr, $this->contextType, $this->contextId), $keyResultId);
    }

    protected function apply(callable $action, int $keyResultId): void
    {
        $teamId = $this->okrTeamId();
        if (! $teamId || ! $this->hasContext()) {
            return;
        }

        $kr = KeyResult::where('team_id', $teamId)->find($keyResultId);
        if (! $kr) {
            return;
        }

        $action(app(KeyResultContextLinks::class), $kr);

        unset($this->linked, $this->covered, $this->candidates);
        $this->dispatch('context-panel-badges');
    }

    protected function okrTeamId(): ?int
    {
        return app(KeyResultContextLinks::class)->okrTeamIdFor(auth()->user());
    }

    protected function present(KeyResult $kr): array
    {
        $perf = $kr->performance;
        $target = (float) ($perf?->target_value ?? 0);
        $current = (float) ($perf?->current_value ?? 0);

        return [
            'id' => $kr->id,
            'title' => $kr->title,
            'objective' => $kr->objective?->title,
            'cycle' => $kr->objective?->cycle?->template?->label ?? $kr->objective?->cycle?->label,
            'progress' => $perf?->type === 'boolean'
                ? ($perf->is_completed ? 100 : 0)
                : ($target > 0 ? (int) round(min(100, max(0, $current / $target * 100))) : null),
            'done' => (bool) ($perf?->is_completed ?? false),
            'url' => $kr->objective && \Illuminate\Support\Facades\Route::has('okr.objectives.show')
                ? route('okr.objectives.show', $kr->objective)
                : null,
        ];
    }

    public function render()
    {
        return view('okr::livewire.panel.key-results');
    }
}
