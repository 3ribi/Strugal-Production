<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-white antialiased dark:bg-gray-900">
        <div class="relative z-1 min-h-dvh bg-white sm:p-0 dark:bg-gray-900">
            <div class="relative flex min-h-dvh w-full flex-col justify-center lg:flex-row dark:bg-gray-900">
                {{-- Formulaire --}}
                <div class="flex w-full flex-1 flex-col lg:w-1/2">
                    <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 py-10">
                        <a href="{{ route('home') }}" class="mb-8 inline-flex lg:hidden">
                            <x-ui.brand class="[&>span:last-child]:block!" />
                        </a>

                        {{ $slot }}
                    </div>
                </div>

                {{-- Panneau de marque --}}
                <div class="relative hidden h-full w-full items-center bg-brand-950 lg:grid lg:w-1/2 dark:bg-white/5">
                    <div class="z-1 flex items-center justify-center">
                        <div class="absolute top-0 right-0 -z-1 w-full max-w-[250px] xl:max-w-[450px]">
                            <img src="/images/shape/grid-01.svg" alt="" />
                        </div>
                        <div class="absolute bottom-0 left-0 -z-1 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
                            <img src="/images/shape/grid-01.svg" alt="" />
                        </div>

                        <div class="flex max-w-sm flex-col items-center px-6 text-center">
                            <a href="{{ route('home') }}" class="mb-6 block">
                                <span class="flex items-center gap-3">
                                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-500 text-2xl font-bold text-white">S</span>
                                    <span class="text-2xl font-semibold tracking-wide text-white">{{ config('app.name') }}</span>
                                </span>
                            </a>
                            <p class="text-gray-400 dark:text-white/60">
                                Rapports de laquage, suivi des rejets et exports Excel réunis dans un seul tableau de bord.
                            </p>
                        </div>
                    </div>
                </div>
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
