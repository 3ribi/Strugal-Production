@props([
    'label',
    'value',
    'icon' => 'chart-bar',
    'hint' => null,
    'tone' => 'gray',
])

@php
    $tones = [
        'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-white/90',
        'blue' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
        'green' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'amber' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
        'red' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
    ];
@endphp

<div {{ $attributes->class('rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6') }}>
    <div class="flex size-12 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['gray'] }}">
        <flux:icon :name="$icon" class="size-6" />
    </div>

    <div class="mt-5">
        <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</span>
        <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $value }}</h4>
        @if ($hint)
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
        @endif
    </div>
</div>
