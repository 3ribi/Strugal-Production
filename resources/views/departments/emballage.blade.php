<x-layouts::app :title="__('Emballage')">
    <div class="mx-auto w-full max-w-full space-y-6">
        <x-ui.page-header title="Emballage" subtitle="Suivi de la production du département Emballage" />

        <x-ui.card>
            <div class="flex flex-col items-center gap-3 px-4 py-14 text-center">
                <div class="flex size-14 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                    <flux:icon name="archive-box" class="size-7" />
                </div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bientôt disponible</h2>
                <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Les rapports du département Emballage seront disponibles ici prochainement.
                </p>
            </div>
        </x-ui.card>
    </div>
</x-layouts::app>
