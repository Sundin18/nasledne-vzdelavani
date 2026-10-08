<x-layouts::public>
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
</x-layouts::public>
