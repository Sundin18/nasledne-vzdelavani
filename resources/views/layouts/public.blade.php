<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-900 dark:text-white">
        <flux:header container class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <x-app-logo href="{{ route('home') }}" />

            <flux:spacer />

            <flux:navbar class="gap-1">
                @auth
                    <flux:navbar.item :href="route('courses.index')">Kurzy</flux:navbar.item>
                    <flux:navbar.item :href="route('courses.mine')">Moje kurzy</flux:navbar.item>
                @else
                    <flux:navbar.item :href="route('login')">Přihlásit se</flux:navbar.item>
                    @if (Route::has('register'))
                        <flux:button variant="primary" size="sm" :href="route('register')" class="ms-2">Registrace</flux:button>
                    @endif
                @endauth
            </flux:navbar>
        </flux:header>

        <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-16 px-6 py-16 lg:px-8 lg:py-20">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mx-auto flex max-w-5xl flex-wrap justify-between gap-2 px-6 py-5 text-sm text-zinc-500 lg:px-8 dark:text-zinc-400">
                <span>© {{ now()->year }} {{ config('courses.organization') }}</span>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <flux:link :href="route('pages.show', 'o-aplikaci')" variant="subtle">O aplikaci</flux:link>
                    <flux:link href="mailto:{{ config('courses.notification_email') }}" variant="subtle">{{ config('courses.notification_email') }}</flux:link>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
