<div class="mx-auto w-full max-w-full space-y-6">
    <x-ui.page-header title="Départements" subtitle="Créez et gérez les départements de l'usine" />

    <x-ui.flash />

    <x-ui.card title="Ajouter un département">
        <form wire:submit="createDepartment" class="flex flex-wrap items-start gap-3">
            <div class="min-w-64 flex-1">
                <flux:input wire:model="name" placeholder="Nom du département" aria-label="Nom du département" />
            </div>
            <flux:button type="submit" variant="primary" icon="plus">Ajouter</flux:button>
        </form>
    </x-ui.card>

    <x-ui.card :padded="false">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nom</th>
                        <th class="px-5 py-3 font-medium">Identifiant</th>
                        <th class="px-5 py-3 text-center font-medium">Utilisateurs</th>
                        <th class="px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($departments as $department)
                        <tr wire:key="department-{{ $department->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-white">{{ $department->name }}</td>
                            <td class="px-5 py-3.5"><flux:badge size="sm" color="zinc">{{ $department->slug }}</flux:badge></td>
                            <td class="px-5 py-3.5 text-center text-gray-700 dark:text-gray-200">{{ $department->users_count }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    inset="top bottom"
                                    title="Supprimer"
                                    class="text-error-600! hover:text-error-700!"
                                    wire:click="deleteDepartment({{ $department->id }})"
                                    wire:confirm="Supprimer ce département ?"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center text-gray-500 dark:text-gray-400">Aucun département.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
