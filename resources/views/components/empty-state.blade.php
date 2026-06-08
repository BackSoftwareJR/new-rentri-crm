@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->merge(['class' => 'seg-empty-state', 'role' => 'status']) }}>
    <p class="seg-empty-state-title">{{ $title }}</p>
    @if ($description)
        <p class="seg-empty-state-desc">{{ $description }}</p>
    @endif
    @if ($actionHref && $actionLabel)
        <div class="seg-empty-state-action">
            <x-btn variant="primary" :href="$actionHref" wire:navigate>{{ $actionLabel }}</x-btn>
        </div>
    @endif
    {{ $slot }}
</div>
