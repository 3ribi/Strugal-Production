@php
    $header = [
        'Ligne de laquage' => $report->line,
        'Période de travail' => $report->work_period,
        'Opérateurs' => $report->operators,
        'Vérification micromètre' => $report->micrometer_check,
        'Brillomètre' => $report->brillometer,
        'Phase' => $report->phase,
        'Température du four' => $report->furnace_temperature !== null && $report->furnace_temperature !== ''
            ? $report->furnace_temperature.' °C'
            : null,
        'Créé par' => $report->user?->name,
    ];

    $columns = [
        'source_cart' => 'Nº CHAR. SOURCE',
        'order_number' => 'COMMANDE',
        'of_number' => 'O.F',
        'reference' => 'RÉFÉRENCE',
        'bars_count' => 'Nº BARRES',
        'cart_number' => 'Nº CHAR',
        'rejected' => 'REJET',
        'thickness_min' => 'MIN.',
        'thickness_max' => 'MAX.',
        'color' => 'COULEUR',
        'supplier' => 'FOURNISSEUR',
        'category' => 'CAT.',
        'brilliance_average' => 'MOYENNE',
        'aspect' => 'ASPECT',
    ];
@endphp

<div class="mx-auto w-full max-w-full space-y-6">
    <x-ui.page-header
        :title="'Rapport du '.$report->report_date->format('d/m/Y')"
        :subtitle="$rows->count().' ligne'.($rows->count() > 1 ? 's' : '').' de production'"
    >
        <x-slot:actions>
            <flux:button variant="ghost" icon="arrow-left" :href="route('departments.laquage.index')" wire:navigate>
                Retour
            </flux:button>
            <flux:button
                variant="danger"
                icon="trash"
                wire:click="delete"
                wire:confirm="Supprimer ce rapport et toutes ses lignes de production ?"
            >
                Supprimer
            </flux:button>
            <flux:button variant="primary" icon="arrow-down-tray" :href="route('departments.laquage.export', $report->id)">
                Télécharger Excel
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat label="Barres traitées" :value="number_format($totalBars, 0, ',', ' ')" icon="cube" tone="green" />
        <x-ui.stat label="Barres rejetées" :value="number_format($totalRejected, 0, ',', ' ')" icon="exclamation-triangle" tone="amber" />
        <x-ui.stat
            label="Brillance moyenne"
            :value="$averageBrilliance === null ? '—' : number_format($averageBrilliance, 2, ',', ' ')"
            icon="chart-bar"
            tone="blue"
        />
    </div>

    <x-ui.card title="Informations du rapport">
        <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($header as $label => $value)
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ filled($value) ? $value : '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </x-ui.card>

    <x-ui.card title="Lignes de production" :padded="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1400px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        @foreach ($columns as $label)
                            <th class="whitespace-nowrap px-4 py-3 font-medium">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($rows as $index => $row)
                        <tr wire:key="row-{{ $index }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 text-gray-400">{{ $index + 1 }}</td>
                            @foreach ($columns as $field => $label)
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ filled($row->{$field}) || $row->{$field} === 0 ? $row->{$field} : '—' }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                Ce rapport ne contient aucune ligne de production.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
