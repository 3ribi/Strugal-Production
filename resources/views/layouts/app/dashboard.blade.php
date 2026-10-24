@php
    $authUser = auth()->user();
    $inLaquage = $authUser?->canAccessDepartment('laquage');
    $inEmballage = $authUser?->canAccessDepartment('emballage');
    $roleLabel = $authUser?->isAdmin() ? 'Administrateur' : ($authUser?->department?->name ?? 'Utilisateur');

    // Menu : chaque entrée a un nom, une icône Flux, une route et les routes qui la rendent « active ».
    $menuGroups = [];

    $menuGroups[] = [
        'title' => 'Menu',
        'items' => [
            ['name' => __('Dashboard'), 'icon' => 'home', 'route' => 'dashboard', 'active' => ['dashboard']],
        ],
    ];

    $departmentItems = [];
    if ($inLaquage) {
        $departmentItems[] = [
            'name' => 'Laquage',
            'icon' => 'clipboard-document-list',
            'active' => ['departments.laquage.*'],
            'children' => [
                ['name' => 'Rapports', 'route' => 'departments.laquage.index', 'active' => ['departments.laquage.index', 'departments.laquage.show']],
                ['name' => 'Nouveau rapport', 'route' => 'departments.laquage.create', 'active' => ['departments.laquage.create']],
            ],
        ];
    }
    if ($inEmballage) {
        $departmentItems[] = ['name' => 'Emballage', 'icon' => 'archive-box', 'route' => 'departments.emballage', 'active' => ['departments.emballage']];
    }
    if ($departmentItems) {
        $menuGroups[] = ['title' => 'Départements', 'items' => $departmentItems];
    }

    if ($authUser?->isAdmin()) {
        $menuGroups[] = [
            'title' => 'Administration',
            'items' => [
                ['name' => 'Utilisateurs', 'icon' => 'users', 'route' => 'admin.users.index', 'active' => ['admin.users.*']],
                ['name' => 'Départements', 'icon' => 'building-office-2', 'route' => 'admin.departments.index', 'active' => ['admin.departments.*']],
            ],
        ];
    }

    $menuGroups[] = [
        'title' => 'Compte',
        'items' => [
            ['name' => __('Settings'), 'icon' => 'cog', 'route' => 'profile.edit', 'active' => ['profile.*', 'security.*', 'appearance.*']],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body>
        <div
            class="min-h-screen sidebar-expanded xl:flex"
            x-data
            :class="{ 'sidebar-expanded': $store.sidebar.showFull }"
        >
            {{-- Voile mobile --}}
            <div
                x-cloak
                x-show="$store.sidebar.isMobileOpen"
                @click="$store.sidebar.setMobileOpen(false)"
                class="fixed inset-0 z-50 bg-gray-900/50 xl:hidden"
            ></div>

            {{-- Barre latérale --}}
            <aside
                class="fixed top-0 left-0 z-99999 mt-0 flex h-screen w-[90px] flex-col border-r border-gray-200 bg-white px-5 text-gray-900 transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900 [.sidebar-expanded_&]:w-[290px]"
                :class="$store.sidebar.isMobileOpen ? 'translate-x-0' : 'max-xl:-translate-x-full'"
                @mouseenter="$store.sidebar.setHovered(true)"
                @mouseleave="$store.sidebar.setHovered(false)"
            >
                <div class="flex items-center gap-2 pt-8 pb-7" :class="$store.sidebar.showFull ? 'justify-between' : 'justify-center'">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-ui.brand />
                    </a>
                </div>

                <div class="no-scrollbar flex flex-1 flex-col overflow-y-auto duration-300 ease-linear">
                    <nav class="mb-6">
                        <div class="flex flex-col gap-4">
                            @foreach ($menuGroups as $group)
                                <div>
                                    <h2 class="mb-4 flex text-xs leading-5 text-gray-400 uppercase" :class="$store.sidebar.showFull ? 'justify-start' : 'xl:justify-center'">
                                        <span x-show="$store.sidebar.showFull">{{ $group['title'] }}</span>
                                        <flux:icon x-show="! $store.sidebar.showFull" name="ellipsis-horizontal" class="size-5" />
                                    </h2>

                                    <ul class="flex flex-col gap-1">
                                        @foreach ($group['items'] as $item)
                                            @php($isActive = request()->routeIs(...$item['active']))
                                            <li x-data="{ open: {{ $isActive && isset($item['children']) ? 'true' : 'false' }} }">
                                                @if (isset($item['children']))
                                                    <button
                                                        type="button"
                                                        @click="open = ! open"
                                                        class="menu-item group {{ $isActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                                                        :class="$store.sidebar.showFull ? 'xl:justify-start' : 'xl:justify-center'"
                                                    >
                                                        <span class="{{ $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                                            <flux:icon :name="$item['icon']" class="size-6" />
                                                        </span>
                                                        <span x-show="$store.sidebar.showFull" class="truncate">{{ $item['name'] }}</span>
                                                        <flux:icon
                                                            x-show="$store.sidebar.showFull"
                                                            name="chevron-down"
                                                            class="ml-auto size-5 transition-transform duration-200"
                                                            ::class="open ? 'rotate-180 text-brand-500' : 'text-gray-500 dark:text-gray-400'"
                                                        />
                                                    </button>

                                                    <div x-show="open && $store.sidebar.showFull" x-collapse>
                                                        <ul class="mt-2 ml-9 space-y-1">
                                                            @foreach ($item['children'] as $child)
                                                                <li>
                                                                    <a
                                                                        href="{{ route($child['route']) }}"
                                                                        wire:navigate
                                                                        class="menu-dropdown-item {{ request()->routeIs(...$child['active']) ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}"
                                                                    >
                                                                        {{ $child['name'] }}
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @else
                                                    <a
                                                        href="{{ route($item['route']) }}"
                                                        wire:navigate
                                                        class="menu-item group {{ $isActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                                                        :class="$store.sidebar.showFull ? 'xl:justify-start' : 'xl:justify-center'"
                                                    >
                                                        <span class="{{ $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                                            <flux:icon :name="$item['icon']" class="size-6" />
                                                        </span>
                                                        <span x-show="$store.sidebar.showFull" class="truncate">{{ $item['name'] }}</span>
                                                    </a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </nav>
                </div>
            </aside>

            {{-- Zone principale --}}
            <div class="ml-0 flex-1 transition-all duration-300 ease-in-out xl:ml-[90px] [.sidebar-expanded_&]:xl:ml-[290px]">
                <header class="sticky top-0 z-99 flex w-full border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 xl:border-b">
                    <div class="flex grow items-center justify-between gap-3 px-3 py-3 sm:gap-4 xl:px-6">
                        <div class="flex items-center gap-3">
                            {{-- Bouton barre latérale (bureau) --}}
                            <button
                                type="button"
                                class="hidden size-11 items-center justify-center rounded-lg border border-gray-200 text-gray-500 xl:flex dark:border-gray-800 dark:text-gray-400"
                                :class="{ 'bg-gray-100 dark:bg-white/[0.03]': ! $store.sidebar.isExpanded }"
                                @click="$store.sidebar.toggleExpanded()"
                                aria-label="Réduire ou agrandir le menu"
                            >
                                <flux:icon name="bars-3" class="size-5" />
                            </button>

                            {{-- Bouton menu (mobile) --}}
                            <button
                                type="button"
                                class="flex size-10 items-center justify-center rounded-lg text-gray-500 xl:hidden dark:text-gray-400"
                                :class="{ 'bg-gray-100 dark:bg-white/[0.03]': $store.sidebar.isMobileOpen }"
                                @click="$store.sidebar.toggleMobileOpen()"
                                aria-label="Ouvrir le menu"
                            >
                                <flux:icon name="bars-3" class="size-6" />
                            </button>

                            <a href="{{ route('dashboard') }}" wire:navigate class="xl:hidden">
                                <x-ui.brand class="[&>span:last-child]:block!" />
                            </a>

                            {{-- Recherche de rapports --}}
                            @if ($inLaquage)
                                <form action="{{ route('departments.laquage.index') }}" method="GET" class="hidden xl:block" role="search">
                                    <div class="relative">
                                        <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                            <flux:icon name="magnifying-glass" class="size-5" />
                                        </span>
                                        <input
                                            type="search"
                                            name="q"
                                            placeholder="Rechercher un rapport…"
                                            class="h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pr-4 pl-12 text-theme-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-500/10 focus:outline-hidden xl:w-[430px] dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        />
                                    </div>
                                </form>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Thème clair / sombre --}}
                            <button
                                type="button"
                                x-data
                                @click="$flux.dark = ! $flux.dark"
                                class="flex size-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                                aria-label="Changer de thème"
                            >
                                <flux:icon name="moon" class="size-5 dark:hidden" />
                                <flux:icon name="sun" class="hidden size-5 dark:block" />
                            </button>

                            {{-- Menu utilisateur --}}
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                                <button type="button" class="flex items-center text-gray-700 dark:text-gray-400" @click="open = ! open" :aria-expanded="open">
                                    <span class="mr-3 flex size-11 items-center justify-center rounded-full bg-brand-50 text-theme-sm font-semibold text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                                        {{ $authUser->initials() }}
                                    </span>
                                    <span class="mr-1 hidden text-theme-sm font-medium sm:block">{{ \Illuminate\Support\Str::before($authUser->name, ' ') }}</span>
                                    <flux:icon name="chevron-down" class="size-5 text-gray-500 transition-transform duration-200 dark:text-gray-400" ::class="{ 'rotate-180': open }" />
                                </button>

                                <div
                                    x-cloak
                                    x-show="open"
                                    x-transition
                                    class="absolute right-0 mt-4 w-65 rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900"
                                >
                                    <div>
                                        <span class="block text-theme-sm font-medium text-gray-700 dark:text-gray-400">{{ $authUser->name }}</span>
                                        <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $authUser->email }}</span>
                                        <span class="mt-2 inline-block rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">{{ $roleLabel }}</span>
                                    </div>

                                    <ul class="flex flex-col gap-1 border-b border-gray-200 pt-4 pb-3 dark:border-gray-800">
                                        <li>
                                            <a href="{{ route('profile.edit') }}" wire:navigate class="group flex items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                                <flux:icon name="cog" class="size-6 text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300" />
                                                {{ __('Settings') }}
                                            </a>
                                        </li>
                                    </ul>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" data-test="logout-button" class="group mt-3 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                            <flux:icon name="arrow-right-start-on-rectangle" class="size-6 text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300" />
                                            {{ __('Log out') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
