@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class('flex flex-wrap items-center justify-between gap-3') }}>
    <div class="min-w-0">
        <nav aria-label="Fil d'Ariane" class="mb-1">
            <ol class="flex items-center gap-1.5 text-sm">
                <li>
                    <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-1.5 text-gray-500 hover:text-brand-500 dark:text-gray-400">
                        Accueil
                        <flux:icon name="chevron-right" class="size-3.5" />
                    </a>
                </li>
                <li class="truncate text-gray-800 dark:text-white/90">{{ $title }}</li>
            </ol>
        </nav>
        <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
