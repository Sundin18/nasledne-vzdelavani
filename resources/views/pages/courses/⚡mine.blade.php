<?php

use App\Models\CourseRegistration;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Moje kurzy')] class extends Component {
    use WithPagination;

    /**
     * Which registrations are shown: "upcoming" or "past".
     */
    #[Url]
    public string $show = 'upcoming';

    /**
     * Registrations for courses that have not started yet, nearest first.
     *
     * @return Collection<int, CourseRegistration>
     */
    #[Computed]
    public function upcoming(): Collection
    {
        return Auth::user()->courseRegistrations()
            ->select('course_registrations.*')
            ->join('courses', 'courses.id', '=', 'course_registrations.course_id')
            ->where('courses.start', '>', now())
            ->orderBy('courses.start')
            ->with('course.categories')
            ->get();
    }

    /**
     * Registrations for courses that have already started, most recent first, 30 per page.
     *
     * @return LengthAwarePaginator<int, CourseRegistration>
     */
    #[Computed]
    public function past(): LengthAwarePaginator
    {
        return Auth::user()->courseRegistrations()
            ->select('course_registrations.*')
            ->join('courses', 'courses.id', '=', 'course_registrations.course_id')
            ->where('courses.start', '<=', now())
            ->orderByDesc('courses.start')
            ->with('course.categories')
            ->paginate(30);
    }

    public function updatedShow(): void
    {
        $this->resetPage();
    }

    public function unregister(int $registrationId): void
    {
        $registration = Auth::user()->courseRegistrations()->with('course')->findOrFail($registrationId);

        if ($registration->course->hasStarted()) {
            Flux::toast(variant: 'danger', text: 'Z kurzu, který už začal, se nelze odhlásit.');

            return;
        }

        $registration->delete();

        unset($this->upcoming, $this->past);

        Flux::toast(text: 'Byli jste odhlášeni z kurzu „'.$registration->course->name.'“.');
    }
}; ?>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">Moje kurzy</flux:heading>
        <flux:text class="mt-2">Kurzy, na které jste přihlášeni. U absolvovaných kurzů si stáhnete certifikát.</flux:text>
    </div>

    <flux:radio.group wire:model.live="show" variant="segmented" class="w-fit">
        <flux:radio value="upcoming" label="Nadcházející ({{ $this->upcoming->count() }})" />
        <flux:radio value="past" label="Proběhlé ({{ $this->past->total() }})" />
    </flux:radio.group>

    @php($list = $show === 'past' ? $this->past : $this->upcoming)

    <div class="flex flex-col gap-3">
        @forelse ($list as $registration)
            @php($course = $registration->course)

            <flux:card wire:key="registration-{{ $registration->id }}" class="flex flex-wrap items-center gap-x-6 gap-y-4">
                <div class="w-32 shrink-0">
                    <div class="font-semibold text-zinc-900 dark:text-white">{{ $course->start->format('j. n. Y') }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $course->start->format('G:i') }} – {{ $course->end->format('G:i') }}
                    </div>
                </div>

                <div class="min-w-0 flex-1 basis-72">
                    <flux:heading>{{ $course->name }}</flux:heading>
                    <flux:text class="mt-1">{{ $course->categoryNames() }} · {{ $course->place }}</flux:text>
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

    @if ($show === 'past')
        <flux:pagination :paginator="$this->past" />
    @endif
</div>
