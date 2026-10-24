@props([
    'title' => null,
    'description' => null,
    'padded' => true,
])

<section {{ $attributes->class('overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]') }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6 sm:py-5">
            <div>
                @if ($title)
                    <h2 class="text-base font-medium text-gray-800 dark:text-white/90">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class([
        'p-5 sm:p-6' => $padded,
        'border-t border-gray-100 dark:border-gray-800' => $title || isset($actions),
    ])>
        {{ $slot }}
    </div>
</section>
