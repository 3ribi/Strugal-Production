@if (session('success'))
    <flux:callout variant="success" icon="check-circle" :heading="session('success')" {{ $attributes }} />
@endif
