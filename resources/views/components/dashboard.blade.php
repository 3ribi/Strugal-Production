<div class="mx-auto w-full space-y-6">
    <x-ui.page-header
        :title="'Bonjour, '.\Illuminate\Support\Str::before($user->name, ' ')"
        :subtitle="ucfirst(now()->locale('fr')->isoFormat('dddd D MMMM YYYY'))"
    >
        @if ($hasLaquage)
            <x-slot:actions>
                <flux:button variant="primary" icon="plus" :href="route('departments.laquage.create')" wire:navigate>
                    Nouveau rapport
                </flux:button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if ($hasLaquage)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
            <x-ui.stat
                label="Rapports Laquage"
                :value="number_format($totalReports, 0, ',', ' ')"
                :hint="$monthReports.' ce mois-ci'"
                icon="clipboard-document-list"
                tone="blue"
            />
            <x-ui.stat
                label="Lignes de production"
                :value="number_format($totalLines, 0, ',', ' ')"
                hint="Toutes périodes"
                icon="document-text"
            />
            <x-ui.stat
                label="Barres traitées"
                :value="number_format($totalBars, 0, ',', ' ')"
                hint="Somme des Nº barres"
                icon="cube"
                tone="green"
            />
            <x-ui.stat
                label="Taux de rejet"
                :value="$rejectRate === null ? '—' : number_format($rejectRate, 1, ',', ' ').' %'"
                :hint="number_format($totalRejected, 0, ',', ' ').' barres rejetées'"
                icon="exclamation-triangle"
                tone="amber"
            />
        </div>

        <div class="grid gap-4 md:gap-6 xl:grid-cols-3">
            <x-ui.card title="Activité" description="Rapports créés ces 14 derniers jours" class="xl:col-span-2">
                <x-ui.chart :config="[
                    'type' => 'bar',
                    'height' => 300,
                    'colors' => ['#465fff'],
                    'categories' => $activity->pluck('label')->all(),
                    'series' => [['name' => 'Rapports', 'data' => $activity->pluck('count')->all()]],
                ]" />
            </x-ui.card>

            <x-ui.card title="Qualité" description="Barres conformes et rejetées">
                @if ($totalBars > 0)
                    <x-ui.chart :config="[
                        'type' => 'donut',
                        'height' => 300,
                        'colors' => ['#12b76a', '#f04438'],
                        'labels' => ['Conformes', 'Rejetées'],
                        'totalLabel' => 'Barres',
                        'series' => [max(0, $totalBars - $totalRejected), $totalRejected],
                    ]" />
                @else
                    <p class="py-16 text-center text-theme-sm text-gray-500 dark:text-gray-400">
                        Les statistiques apparaîtront après le premier rapport.
                    </p>
                @endif
            </x-ui.card>
        </div>

        <x-ui.card title="Derniers rapports" :padded="false">
            <x-slot:actions>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('departments.laquage.index')" wire:navigate>
                    Tout voir
                </flux:button>
            </x-slot:actions>

            @forelse ($recent as $report)
                <a
                    href="{{ route('departments.laquage.show', $report->id) }}"
                    wire:navigate
                    wire:key="recent-{{ $report->id }}"
                    class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4 last:border-b-0 hover:bg-gray-50 sm:px-6 dark:border-gray-800 dark:hover:bg-white/[0.03]"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium text-gray-800 dark:text-white/90">
                            {{ $report->report_date->format('d/m/Y') }}
                            @if ($report->line)
                                <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $report->line }}</span>
                            @endif
                        </p>
                        <p class="truncate text-theme-sm text-gray-500 dark:text-gray-400">
                            {{ $report->operators ?: 'Opérateurs non renseignés' }}
                        </p>
                    </div>
                    <flux:badge size="sm" color="zinc">{{ $report->rows_count }} ligne{{ $report->rows_count > 1 ? 's' : '' }}</flux:badge>
                </a>
            @empty
                <div class="px-5 py-12 text-center text-theme-sm text-gray-500 dark:text-gray-400">
                    Aucun rapport pour le moment.
                </div>
            @endforelse
        </x-ui.card>
    @else
        <x-ui.card>
            <p class="py-6 text-center text-theme-sm text-gray-500 dark:text-gray-400">
                Utilisez le menu de gauche pour accéder à votre département.
            </p>
        </x-ui.card>
    @endif

    @if ($isAdmin)
        <div class="grid gap-4 sm:grid-cols-2 md:gap-6">
            <a href="{{ route('admin.users.index') }}" wire:navigate class="block transition hover:opacity-90">
                <x-ui.stat label="Utilisateurs" :value="$usersCount" hint="Gérer les comptes" icon="users" />
            </a>
            <a href="{{ route('admin.departments.index') }}" wire:navigate class="block transition hover:opacity-90">
                <x-ui.stat label="Départements" :value="$departmentsCount" hint="Gérer les départements" icon="building-office-2" />
            </a>
        </div>
    @endif
</div>
