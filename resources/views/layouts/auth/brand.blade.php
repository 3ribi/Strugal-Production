<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-gray-50 antialiased dark:bg-gray-900">
        <main class="flex min-h-dvh items-center justify-center p-4 sm:p-6">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-md sm:p-8 dark:border-gray-800 dark:bg-white/[0.03]">
                {{ $slot }}
            </div>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
