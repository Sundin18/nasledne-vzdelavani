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
            <section class="flex max-w-2xl flex-col gap-5">
                <flux:badge class="w-fit">Kurzy následného vzdělávání</flux:badge>
                <h1 class="text-4xl font-semibold tracking-tight text-balance lg:text-5xl">
                    {{-- TODO: doplnit nadpis úvodní stránky --}}
                    Následné vzdělávání
                </h1>
                <p class="text-lg leading-relaxed text-zinc-600 dark:text-zinc-300">
                    {{-- TODO: doplnit úvodní popisek --}}
                    Přehled vypsaných kurzů na jednom místě. Zaregistrujte se, vyberte si termín a po absolvování si stáhněte certifikát.
                </p>
                <div class="mt-2 flex flex-wrap gap-3">
                    <flux:button variant="primary" icon:trailing="arrow-right" :href="route('courses.index')">Zobrazit kurzy</flux:button>
                    @guest
                        @if (Route::has('register'))
                            <flux:button :href="route('register')">Vytvořit účet</flux:button>
                        @endif
                    @endguest
                </div>
            </section>

            <section aria-label="Jak to funguje" class="grid gap-4 md:grid-cols-3">
                @foreach ([
                    ['Zaregistrujte se', 'Účet si založíte jménem, příjmením a e-mailem.'],
                    ['Vyberte kurz', 'Ve výpisu kurzů se přihlásíte jedním kliknutím, dokud je volné místo.'],
                    ['Stáhněte certifikát', 'V sekci Moje kurzy najdete své přihlášky i certifikáty z absolvovaných kurzů.'],
                ] as [$heading, $text])
                    <flux:card class="flex flex-col gap-2">
                        <span class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold dark:bg-zinc-700">{{ $loop->iteration }}</span>
                        <flux:heading size="lg" class="mt-1">{{ $heading }}</flux:heading>
                        <flux:text>{{ $text }}</flux:text>
                    </flux:card>
                @endforeach
            </section>
        </main>

        <footer class="border-t border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mx-auto flex max-w-5xl flex-wrap justify-between gap-2 px-6 py-5 text-sm text-zinc-500 lg:px-8 dark:text-zinc-400">
                <span>© {{ now()->year }} {{ config('courses.organization') }}</span>
                <flux:link href="mailto:{{ config('courses.notification_email') }}" variant="subtle">{{ config('courses.notification_email') }}</flux:link>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
