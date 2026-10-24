@props(['config'])

<div wire:ignore x-data="apexChart(@js($config))" {{ $attributes }}>
    <div x-ref="el"></div>
</div>
