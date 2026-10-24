<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @php($authUser = auth()->user())
                @php($inLaquage = $authUser?->isAdmin() || $authUser?->department?->slug === 'laquage')
                @php($inEmballage = $authUser?->isAdmin() || $authUser?->department?->slug === 'emballage')

                <flux:sidebar.group :heading="__('Navigation')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if ($inLaquage || $inEmballage)
                    <flux:sidebar.group :heading="__('Départements')" class="grid">
                        @if ($inLaquage)
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('departments.laquage.index')" :current="request()->routeIs('departments.laquage.index', 'departments.laquage.show')" wire:navigate>
                                Rapports Laquage
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="plus-circle" :href="route('departments.laquage.create')" :current="request()->routeIs('departments.laquage.create')" wire:navigate>
                                Nouveau rapport
                            </flux:sidebar.item>
                        @endif

                        @if ($inEmballage)
                            <flux:sidebar.item icon="archive-box" :href="route('departments.emballage')" :current="request()->routeIs('departments.emballage')" wire:navigate>
                                Emballage
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                @if ($authUser?->isAdmin())
                    <flux:sidebar.group :heading="__('Administration')" class="grid">
                        <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                            {{ __('Utilisateurs') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-office-2" :href="route('admin.departments.index')" :current="request()->routeIs('admin.departments.*')" wire:navigate>
                            {{ __('Départements') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
