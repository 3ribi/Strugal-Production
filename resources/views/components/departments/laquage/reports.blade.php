<div class="mx-auto w-full max-w-full space-y-6">
    <x-ui.page-header
        title="Rapports Laquage"
        :subtitle="$isAdmin ? 'Tous les rapports du département' : 'Vos rapports de production'"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('departments.laquage.create')" wire:navigate>
                Nouveau rapport
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <x-ui.card :padded="false">
        <div class="grid gap-3 border-b border-gray-200 p-4 dark:border-gray-700 md:grid-cols-[1fr_auto_auto_auto] md:items-end">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Rechercher (ligne, opérateurs, phase…)"
                clearable
            />
            <flux:input type="date" wire:model.live="dateFrom" label="Du" />
            <flux:input type="date" wire:model.live="dateTo" label="Au" />
            <div>
                <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters" :disabled="! $hasFilters">
                    Réinitialiser
                </flux:button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Date</th>
                        <th class="px-5 py-3 font-medium">Ligne</th>
                        <th class="px-5 py-3 font-medium">Période</th>
                        <th class="px-5 py-3 font-medium">Opérateurs</th>
                        <th class="px-5 py-3 text-center font-medium">Lignes</th>
                        <th class="px-5 py-3 text-center font-medium">Rejets</th>
                        @if ($isAdmin)
                            <th class="px-5 py-3 font-medium">Créé par</th>
                        @endif
                        <th class="px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($reports as $report)
                        <tr wire:key="report-{{ $report->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-medium text-gray-900 dark:text-white">
                                <a href="{{ route('departments.laquage.show', $report->id) }}" wire:navigate class="hover:underline">
                                    {{ $report->report_date->format('d/m/Y') }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-200">{{ $report->line ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-200">{{ $report->work_period ?: '—' }}</td>
                            <td class="max-w-xs truncate px-5 py-3.5 text-gray-700 dark:text-gray-200">{{ $report->operators ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <flux:badge size="sm" color="zinc">{{ $report->rows_count }}</flux:badge>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ((int) $report->rejected_total > 0)
                                    <flux:badge size="sm" color="amber">{{ (int) $report->rejected_total }}</flux:badge>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            @if ($isAdmin)
                                <td class="px-5 py-3.5 text-gray-700 dark:text-gray-200">{{ $report->user?->name ?? '—' }}</td>
                            @endif
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <flux:button size="sm" variant="ghost" icon="eye" :href="route('departments.laquage.show', $report->id)" wire:navigate inset="top bottom" title="Voir" />
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('departments.laquage.edit', $report->id)" wire:navigate inset="top bottom" title="Modifier" />
                                <flux:button size="sm" variant="ghost" icon="arrow-down-tray" :href="route('departments.laquage.export', $report->id)" inset="top bottom" title="Télécharger Excel" />
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    inset="top bottom"
                                    title="Supprimer"
                                    class="text-error-600! hover:text-error-700!"
                                    wire:click="delete({{ $report->id }})"
                                    wire:confirm="Supprimer ce rapport et toutes ses lignes de production ?"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 8 : 7 }}" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                                    <flux:icon name="clipboard-document-list" class="size-8 text-gray-400" />
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $hasFilters ? 'Aucun rapport ne correspond à vos filtres' : 'Aucun rapport pour le moment' }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $hasFilters ? 'Essayez de modifier ou de réinitialiser la recherche.' : 'Créez votre premier rapport de production.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                {{ $reports->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
