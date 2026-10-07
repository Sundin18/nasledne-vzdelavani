<?php

use App\Models\CourseRegistration;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Moje kurzy')] class extends Component {
    /**
     * Which registrations are shown: "upcoming" or "past".
     */
    #[Url]
    public string $show = 'upcoming';

    /**
     * @return Collection<int, CourseRegistration>
     */
    #[Computed]
    public function registrations(): Collection
    {
        return Auth::user()->courseRegistrations()
            ->with('course')
            ->get()
            ->sortBy('course.starts_at')
            ->values();
    }

    /**
     * @return Collection<int, CourseRegistration>
     */
    #[Computed]
    public function upcoming(): Collection
    {
        return $this->registrations->reject(fn (CourseRegistration $r) => $r->course->hasStarted())->values();
    }

    /**
     * @return Collection<int, CourseRegistration>
     */
    #[Computed]
    public function past(): Collection
    {
        return $this->registrations->filter(fn (CourseRegistration $r) => $r->course->hasStarted())->reverse()->values();
    }

    public function unregister(int $registrationId): void
    {
        $registration = Auth::user()->courseRegistrations()->with('course')->findOrFail($registrationId);

        if ($registration->course->hasStarted()) {
            Flux::toast(variant: 'danger', text: 'Z kurzu, který už začal, se nelze odhlásit.');

            return;
        }

        $registration->delete();

        unset($this->registrations, $this->upcoming, $this->past);

        Flux::toast(text: 'Byli jste odhlášeni z kurzu „'.$registration->course->title.'“.');
    }
}; ?>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">Moje kurzy</flux:heading>
        <flux:text class="mt-2">Kurzy, na které jste přihlášeni. U absolvovaných kurzů si stáhnete certifikát.</flux:text>
    </div>

    <flux:radio.group wire:model.live="show" variant="segmented" class="w-fit">
        <flux:radio value="upcoming" label="Nadcházející ({{ $this->upcoming->count() }})" />
        <flux:radio value="past" label="Proběhlé ({{ $this->past->count() }})" />
    </flux:radio.group>

    @php($list = $show === 'past' ? $this->past : $this->upcoming)

    <div class="flex flex-col gap-3">
        @forelse ($list as $registration)
            @php($course = $registration->course)

            <flux:card wire:key="registration-{{ $registration->id }}" class="flex flex-wrap items-center gap-x-6 gap-y-4">
                <div class="w-32 shrink-0">
                    <div class="font-semibold text-zinc-900 dark:text-white">{{ $course->starts_at->format('j. n. Y') }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $course->starts_at->format('G:i') }} – {{ $course->ends_at->format('G:i') }}
                    </div>
                </div>

                <div class="min-w-0 flex-1 basis-72">
                    <flux:heading>{{ $course->title }}</flux:heading>
                    <flux:text class="mt-1">{{ implode(', ', $course->categories) }} · {{ $course->place }}</flux:text>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if (! $course->hasStarted())
                        <flux:badge color="blue">Přihlášen</flux:badge>
                        <flux:button size="sm" wire:click="unregister({{ $registration->id }})" wire:confirm="Opravdu se chcete z kurzu odhlásit?">
                            Odhlásit se
                        </flux:button>
                    @elseif ($registration->attended)
                        <flux:badge color="green">Absolvováno</flux:badge>
                        <flux:button size="sm" variant="primary" icon="arrow-down-tray" :href="route('certificates.show', $registration)">
                            Certifikát PDF
                        </flux:button>
                    @else
                        <flux:badge>Účast nepotvrzena</flux:badge>
                    @endif
                </div>
            </flux:card>
        @empty
            <flux:card class="flex flex-wrap items-center justify-between gap-4">
                <flux:text>
                    {{ $show === 'past' ? 'Zatím nemáte žádný proběhlý kurz.' : 'Nejste přihlášeni na žádný nadcházející kurz.' }}
                </flux:text>
                <flux:button variant="primary" :href="route('courses.index')" wire:navigate>Zobrazit kurzy</flux:button>
            </flux:card>
        @endforelse
    </div>
</div>
