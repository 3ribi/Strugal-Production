@php
    use App\Support\LaquageFields;

    // [label, input type] per field, all coming from LaquageFields (single source of truth).
    $headerFields = collect(LaquageFields::header())
        ->map(fn ($f) => [$f['label'].(isset($f['unit']) ? ' ('.$f['unit'].')' : ''), $f['type']])
        ->all();
    $rowFields = collect(LaquageFields::row())->map(fn ($f) => [$f['label'], $f['type']])->all();
    $sections = LaquageFields::sections();
    $decimalFields = LaquageFields::decimalKeys();
    $isEditing = $editingLineId !== null;
    $title = $reportId ? 'Modifier le rapport' : 'Nouveau rapport Laquage';
@endphp

<div class="mx-auto w-full space-y-6">
    <x-ui.page-header
        :title="$title"
        :subtitle="$reportId
            ? 'Les lignes sont enregistrées dès que vous les ajoutez.'
            : 'Remplissez les informations, puis ajoutez vos lignes : tout est enregistré au fur et à mesure.'"
    >
        <x-slot:actions>
            <flux:button variant="ghost" icon="arrow-left" :href="route('departments.laquage.index')" wire:navigate>
                Rapports
            </flux:button>
            @if ($reportId)
                <flux:button icon="arrow-down-tray" :href="route('departments.laquage.export', $reportId)">
                    Télécharger Excel
                </flux:button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    {{-- 1. Informations du rapport --}}
    <x-ui.card title="Informations du rapport" description="Ces informations sont communes à toutes les lignes de production.">
        <form wire:submit="saveHeader" class="space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($headerFields as $field => [$label, $type])
                    <flux:input
                        wire:key="header-{{ $field }}"
                        wire:model="{{ $field }}"
                        :type="$type"
                        :label="$label"
                        :required="$field === 'report_date'"
                        :step="$type === 'number' ? 'any' : null"
                    />
                @endforeach
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" :variant="$reportId ? 'filled' : 'primary'" icon="check">
                    {{ $reportId ? 'Enregistrer les informations' : 'Créer le rapport' }}
                </flux:button>
            </div>
        </form>
    </x-ui.card>



    {{-- 2. Lignes enregistrées --}}
    <x-ui.card :title="'Lignes enregistrées ('.$rows->count().')'" :padded="false">
        @if ($rows->isNotEmpty())
            <x-slot:actions>
                <span class="text-theme-sm text-gray-500 dark:text-gray-400">
                    {{ number_format($totalBars, 0, ',', ' ') }} barres · {{ number_format($totalRejected, 0, ',', ' ') }} rejetées
                </span>
            </x-slot:actions>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1500px] text-left text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 text-theme-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-3 font-medium">#</th>
                        @foreach ($rowFields as [$label])
                            <th class="whitespace-nowrap px-3 py-3 font-medium">{{ $label }}</th>
                        @endforeach
                        <th class="px-3 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($rows as $index => $row)
                        <tr
                            wire:key="saved-row-{{ $row->id }}"
                            @class([
                                'hover:bg-gray-50/70 dark:hover:bg-white/[0.03]',
                                'bg-brand-50/60 dark:bg-brand-500/10' => $editingLineId === $row->id,
                            ])
                        >
                            <td class="whitespace-nowrap px-3 py-3 text-gray-400">{{ $index + 1 }}</td>
                            @foreach ($rowFields as $field => $meta)
                                <td class="max-w-48 truncate whitespace-nowrap px-3 py-3 text-gray-700 dark:text-gray-200">
                                    {{ filled($row->{$field}) || $row->{$field} === 0 ? $row->{$field} : '—' }}
                                </td>
                            @endforeach
                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" inset="top bottom" title="Modifier la ligne" wire:click="editLine({{ $row->id }})" />
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    inset="top bottom"
                                    title="Supprimer la ligne"
                                    class="text-error-600! hover:text-error-700!"
                                    wire:click="deleteLine({{ $row->id }})"
                                    wire:confirm="Supprimer cette ligne ?"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($rowFields) + 2 }}" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                Aucune ligne pour le moment. Remplissez le formulaire ci-dessus puis cliquez sur « Ajouter la ligne ».
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reportId)
            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 p-4 dark:border-gray-800 sm:px-6">
                <flux:button variant="primary" icon="check" :href="route('departments.laquage.index')" wire:navigate>
                    Terminer
                </flux:button>
            </div>
        @endif
    </x-ui.card>




    


    {{-- 3. Ligne de production --}}
    <div x-data x-on:line-editing.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })">
        <x-ui.card
            :title="$isEditing ? 'Modifier la ligne' : 'Nouvelle ligne de production'"
            :description="$reportId
                ? 'Chaque colonne est facultative. Appuyez sur Entrée ou sur « Ajouter » : la ligne est enregistrée tout de suite.'
                : 'Le rapport sera créé automatiquement quand vous ajouterez la première ligne.'"
        >
            <form
                wire:submit="saveLine"
                class="space-y-6"
                x-data
                x-on:line-saved.window="$nextTick(() => $el.querySelector('input')?.focus())"
            >
                @error('lineForm')
                    <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
                @enderror

                @foreach ($sections as $sectionTitle => $fields)
                    <div wire:key="section-{{ $sectionTitle }}">
                        <h3 class="mb-3 text-theme-xs font-medium uppercase tracking-wide text-gray-400">{{ $sectionTitle }}</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                            @foreach ($fields as $field)
                                @php([$label, $type] = $rowFields[$field])
                                <flux:input
                                    wire:key="line-{{ $field }}"
                                    wire:model="lineForm.{{ $field }}"
                                    :type="$type"
                                    :label="$label"
                                    :step="$type === 'number' ? (in_array($field, $decimalFields) ? 'any' : '1') : null"
                                    :min="$type === 'number' ? 0 : null"
                                />
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                    @if ($isEditing)
                        <flux:button type="button" variant="ghost" wire:click="cancelLineEdit">Annuler</flux:button>
                    @endif
                    <flux:button type="submit" variant="primary" :icon="$isEditing ? 'check' : 'plus'" wire:loading.attr="disabled" wire:target="saveLine">
                        {{ $isEditing ? 'Mettre à jour la ligne' : 'Ajouter la ligne' }}
                    </flux:button>
                </div>
            </form>
        </x-ui.card>
    </div>

   
</div>
