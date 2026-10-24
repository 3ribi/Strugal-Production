<div class="mx-auto w-full max-w-full space-y-6">
    <x-ui.page-header title="Modifier l'utilisateur" :subtitle="$user->email">
        <x-slot:actions>
            <flux:button variant="ghost" icon="arrow-left" :href="route('admin.users.index')" wire:navigate>Retour</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <form wire:submit="update" class="space-y-5">
            <flux:input wire:model="name" label="Nom complet" required />
            <flux:input wire:model="email" type="email" label="Adresse e-mail" required />

            @if ($user->role === 'department')
                <flux:select wire:model="department_id" label="Département" placeholder="Choisir un département">
                    @foreach ($departments as $department)
                        <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:callout icon="information-circle" heading="Administrateur" text="Ce compte a accès à tous les départements." />
            @endif

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer les modifications</flux:button>
            </div>
        </form>
    </x-ui.card>
</div>
