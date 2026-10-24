<div class="mx-auto w-full max-w-full space-y-6">
    <x-ui.page-header title="Nouvel utilisateur" subtitle="Créez un compte rattaché à un département">
        <x-slot:actions>
            <flux:button variant="ghost" icon="arrow-left" :href="route('admin.users.index')" wire:navigate>Retour</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <x-ui.card>
        <form wire:submit="createUser" class="space-y-5">
            <flux:input wire:model="name" label="Nom complet" autocomplete="off" required />
            <flux:input wire:model="email" type="email" label="Adresse e-mail" autocomplete="off" required />
            <flux:input
                wire:model="password"
                type="password"
                label="Mot de passe temporaire"
                description:trailing="12 caractères minimum"
                viewable
                autocomplete="new-password"
                required
            />

            <flux:select wire:model="departmentId" label="Département" placeholder="Choisir un département">
                @foreach ($departments as $department)
                    <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>Annuler</flux:button>
                <flux:button type="submit" variant="primary">Créer l'utilisateur</flux:button>
            </div>
        </form>
    </x-ui.card>
</div>
