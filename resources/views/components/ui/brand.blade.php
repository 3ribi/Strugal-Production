{{-- Logo provisoire : remplacez la lettre par le logo Strugal (SVG/PNG) quand il est disponible. --}}
<span {{ $attributes->class('flex items-center gap-3') }}>
    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-lg font-bold text-white">S</span>
    <span class="leading-tight [.sidebar-expanded_&]:block hidden" data-brand-text>
        <span class="block text-lg font-semibold tracking-wide text-gray-800 dark:text-white/90">{{ config('app.name') }}</span>
    </span>
</span>
