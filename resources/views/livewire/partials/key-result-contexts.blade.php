{{-- Verknüpfte Kontexte (Aufgaben, Projekte …) eines Key Results mit Status und Link --}}
@php
    $primaryContexts = $keyResult->relationLoaded('primaryContexts')
        ? $keyResult->primaryContexts
        : $keyResult->primaryContexts()->with('context')->get();
    $colors = [
        'green' => 'bg-[var(--nx-success)]/10 text-[var(--nx-success)] border-[var(--nx-success)]/20',
        'default' => 'bg-[var(--nx-accent)]/10 text-[var(--nx-accent)] border-[var(--nx-accent)]/20',
    ];
@endphp
@if($primaryContexts->isNotEmpty())
    <div class="flex flex-wrap items-center gap-1.5 mt-2">
        @foreach($primaryContexts as $ctx)
            @php
                $model = $ctx->context;
                $item = $model instanceof \Platform\Core\Contracts\AgendaRenderable ? rescue(fn () => $model->toAgendaItem(), [], false) : [];
                $done = ($item['status_color'] ?? null) === 'green';
                $label = $ctx->context_label ?? ($item['title'] ?? class_basename($ctx->context_type));
                $url = $item['url'] ?? null;
            @endphp
            <a @if($url) href="{{ $url }}" wire:navigate @endif
               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium border {{ $done ? $colors['green'] : $colors['default'] }} {{ $url ? 'hover:underline' : '' }}"
               title="{{ $item['status'] ?? '' }}">
                @svg($done ? 'heroicon-o-check-circle' : 'heroicon-o-link', 'w-3 h-3')
                <span class="{{ $done ? 'line-through' : '' }}">{{ $label }}</span>
                @if(! empty($item['status']) && ! $done)
                    <span class="opacity-60 font-normal">· {{ $item['status'] }}</span>
                @endif
            </a>
        @endforeach
    </div>
@endif
