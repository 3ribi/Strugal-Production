<div class="mx-auto w-full max-w-full space-y-6">
    <x-ui.page-header title="Utilisateurs" subtitle="Gérez les comptes et leur département">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>
                Nouvel utilisateur
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    @error('delete')
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
    @enderror

    <x-ui.card :padded="false">
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Rechercher par nom ou e-mail…"
                clearable
                class="max-w-md"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Utilisateur</th>
                        <th class="px-5 py-3 font-medium">Rôle</th>
                        <th class="px-5 py-3 font-medium">Département</th>
                        <th class="px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :name="$user->name" :initials="$user->initials()" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($user->role === 'admin')
                                    <flux:badge size="sm" color="purple">Administrateur</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Département</flux:badge>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-200">
                                {{ $user->department?->name ?? 'Tous les départements' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" inset="top bottom" title="Modifier" :href="route('admin.users.edit', $user)" wire:navigate />

                                @if ($user->id !== auth()->id())
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        inset="top bottom"
                                        title="Supprimer"
                                        class="text-error-600! hover:text-error-700!"
                                        wire:click="delete({{ $user->id }})"
                                        wire:confirm="Voulez-vous vraiment supprimer cet utilisateur ?"
                                    />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center text-gray-500 dark:text-gray-400">
                                Aucun utilisateur trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                {{ $users->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
