@php
    $headerFields = [
        'report_date' => ['Date du rapport', 'date'],
        'line' => ['Ligne de laquage', 'text'],
        'work_period' => ['Période de travail', 'text'],
        'operators' => ['Opérateurs', 'text'],
        'micrometer_check' => ['Vérification micromètre', 'text'],
        'brillometer' => ['Brillomètre', 'number'],
        'phase' => ['Phase', 'text'],
        'furnace_temperature' => ['Température du four (°C)', 'number'],
    ];

    $rowFields = [
        'source_cart' => ['Nº CHAR. SOURCE', 'text'],
        'order_number' => ['COMMANDE', 'text'],
        'of_number' => ['O.F', 'text'],
        'reference' => ['RÉFÉRENCE', 'text'],
        'bars_count' => ['Nº BARRES', 'number'],
        'cart_number' => ['Nº CHAR', 'text'],
        'rejected' => ['REJET', 'number'],
        'thickness_min' => ['MIN.', 'number'],
        'thickness_max' => ['MAX.', 'number'],
        'color' => ['COULEUR', 'text'],
        'supplier' => ['FOURNISSEUR', 'text'],
        'category' => ['CAT.', 'text'],
        'brilliance_average' => ['MOYENNE', 'number'],
        'aspect' => ['ASPECT', 'text'],
    ];
    $decimalFields = ['thickness_min', 'thickness_max', 'brilliance_average'];

    $steps = [1 => 'Rapport', 2 => 'Production'];
@endphp

<div class="mx-auto w-full max-w-full space-y-6">
    <x-ui.page-header
        title="Nouveau rapport Laquage"
        :subtitle="'Étape '.$step.' sur 2 : '.($step === 1 ? 'informations du rapport' : 'lignes de production')"
    >
        <x-slot:actions>
            <flux:button variant="ghost" icon="arrow-left" :href="route('departments.laquage.index')" wire:navigate>
                Mes rapports
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Indicateur d'étapes --}}
    <ol class="flex items-center gap-3 text-sm">
        @foreach ($steps as $number => $label)
            <li class="flex items-center gap-3">
                <span @class([
                    'flex size-7 items-center justify-center rounded-full text-xs font-semibold',
                    'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $step >= $number,
                    'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $step < $number,
                ])>
                    @if ($step > $number)
                        <flux:icon name="check" class="size-4" />
                    @else
                        {{ $number }}
                    @endif
                </span>
                <span @class([
                    'font-medium',
                    'text-gray-900 dark:text-white' => $step >= $number,
                    'text-gray-500 dark:text-gray-400' => $step < $number,
                ])>{{ $label }}</span>
                @if (! $loop->last)
                    <span class="h-px w-10 bg-gray-300 dark:bg-gray-600"></span>
                @endif
            </li>
        @endforeach
    </ol>

    @if ($successMessage)
        <flux:callout variant="success" icon="check-circle" :heading="$successMessage">
            <x-slot name="actions">
                <flux:button size="sm" :href="route('departments.laquage.index')" wire:navigate>Voir mes rapports</flux:button>
                <flux:button size="sm" :href="route('departments.laquage.create')">Nouveau rapport</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    @if ($step === 1)
        <x-ui.card title="Informations du rapport" description="Ces informations sont communes à toutes les lignes de production.">
            <form wire:submit="nextStep" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
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

                <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                    <flux:button type="submit" variant="primary" icon-trailing="arrow-right">
                        Suivant : lignes de production
                    </flux:button>
                </div>
            </form>
        </x-ui.card>
    @else
        <x-ui.card title="Lignes de production" description="Les colonnes sont facultatives. Ajoutez une ligne à la fois ; tout sera enregistré avec le rapport.">
            @if (! $savedReportId && ! $showLineForm)
                <x-slot:actions>
                    <flux:button icon="plus" variant="primary" wire:click="openLineForm">Nouvelle ligne</flux:button>
                </x-slot:actions>
            @endif

            @if ($showLineForm && ! $savedReportId)
                <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
                    <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">
                        {{ $editingLineIndex === null ? 'Nouvelle ligne de production' : 'Modifier la ligne '.($editingLineIndex + 1) }}
                    </h3>

                    <form wire:submit="saveLine" class="space-y-5">
                        @error('lineForm')
                            <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
                        @enderror

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach ($rowFields as $field => [$label, $type])
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

                        <div class="flex flex-wrap justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <flux:button type="button" variant="ghost" wire:click="cancelLine">Annuler</flux:button>
                            <flux:button type="submit" variant="primary">Enregistrer la ligne</flux:button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full min-w-[1500px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-3 font-medium">#</th>
                            @foreach ($rowFields as [$label])
                                <th class="whitespace-nowrap px-3 py-3 font-medium">{{ $label }}</th>
                            @endforeach
                            @if (! $savedReportId)
                                <th class="px-3 py-3 text-right font-medium">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($lineRows as $index => $row)
                            <tr wire:key="production-row-{{ $index }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                                <td class="whitespace-nowrap px-3 py-3 text-gray-400">{{ $index + 1 }}</td>
                                @foreach ($rowFields as $field => $meta)
                                    <td class="max-w-48 truncate whitespace-nowrap px-3 py-3 text-gray-700 dark:text-gray-200">{{ $row[$field] ?? '' }}</td>
                                @endforeach
                                @if (! $savedReportId)
                                    <td class="whitespace-nowrap px-3 py-3 text-right">
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" inset="top bottom" title="Modifier" wire:click="editLine({{ $index }})" />
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                            inset="top bottom"
                                            title="Supprimer"
                                            class="text-error-600! hover:text-error-700!"
                                            wire:click="removeLine({{ $index }})"
                                            wire:confirm="Supprimer cette ligne ?"
                                        />
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($rowFields) + 2 }}" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    Aucune ligne ajoutée. Cliquez sur « Nouvelle ligne » pour commencer.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @error('lineRows')
                <p class="mt-2 text-sm text-error-600">{{ $message }}</p>
            @enderror

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                @if (! $savedReportId)
                    <flux:button type="button" variant="ghost" icon="arrow-left" wire:click="previousStep">Précédent</flux:button>

                    @if (! $showLineForm)
                        <div class="flex flex-wrap gap-3">
                            <flux:button type="button" icon="plus" wire:click="openLineForm">Nouvelle ligne</flux:button>
                            <flux:button type="button" variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Enregistrer le rapport</span>
                                <span wire:loading wire:target="save">Enregistrement…</span>
                            </flux:button>
                        </div>
                    @endif
                @else
                    <div></div>
                    <flux:button type="button" variant="primary" icon="arrow-down-tray" wire:click="export" wire:loading.attr="disabled" wire:target="export">
                        <span wire:loading.remove wire:target="export">Télécharger Excel</span>
                        <span wire:loading wire:target="export">Préparation du fichier…</span>
                    </flux:button>
                @endif
            </div>
        </x-ui.card>
    @endif
</div>
