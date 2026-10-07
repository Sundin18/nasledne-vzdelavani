<?php

use App\Models\Course;
use App\Models\CourseRegistration;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail kurzu')] class extends Component {
    public Course $course;

    public function mount(Course $course): void
    {
        Gate::authorize('admin');

        $this->course = $course->load('categories');
    }

    /**
     * @return Collection<int, CourseRegistration>
     */
    #[Computed]
    public function registrations(): Collection
    {
        return $this->course->registrations()
            ->with('user')
            ->get()
            ->sortBy('user.name')
            ->values();
    }

    #[Computed]
    public function attendedCount(): int
    {
        return $this->registrations->where('attended', true)->count();
    }

    public function toggleAttendance(int $registrationId): void
    {
        Gate::authorize('admin');

        $registration = $this->course->registrations()->findOrFail($registrationId);
        $registration->update(['attended' => ! $registration->attended]);

        unset($this->registrations, $this->attendedCount);
    }

    public function markAllAttended(): void
    {
        Gate::authorize('admin');

        $everyone = $this->attendedCount === $this->registrations->count();

        $this->course->registrations()->update(['attended' => ! $everyone]);

        unset($this->registrations, $this->attendedCount);
    }

    public function deleteCourse(): void
    {
        Gate::authorize('admin');

        $name = $this->course->name;
        $this->course->delete();

        Flux::toast(text: 'Kurz „'.$name.'“ byl smazán.');

        $this->redirectRoute('courses.index', navigate: true);
    }
}; ?>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div>
        <flux:link :href="route('courses.index', ['show' => $course->hasStarted() ? 'past' : 'upcoming'])" variant="subtle" class="inline-flex items-center gap-1.5 text-sm" wire:navigate>
            <flux:icon.arrow-left variant="micro" />
            Kurzy
        </flux:link>
    </div>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="space-y-2">
            <div class="flex flex-wrap gap-1.5 mb-5">
                @foreach ($course->categories as $category)
                    <flux:badge size="sm">{{ $category->name }}</flux:badge>
                @endforeach
                @if ($course->hasStarted())
                    <flux:badge size="sm" color="zinc">Proběhlo</flux:badge>
                @endif
            </div>
            <flux:heading size="xl" level="1">{{ $course->name }}</flux:heading>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="pencil-square" :href="route('admin.courses.edit', $course)" wire:navigate>Upravit</flux:button>
            <flux:modal.trigger name="delete-course">
                <flux:button icon="trash" class="text-red-600! dark:text-red-400!">Smazat</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:card>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">Začátek</dt>
                <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $course->start->format('j. n. Y, G:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">Konec</dt>
                <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $course->end->format('j. n. Y, G:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">Místo</dt>
                <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $course->place }}</dd>
            </div>
            <div>
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">Přihlášeno / účast</dt>
                <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">
                    {{ $this->registrations->count() }} z {{ $course->capacity }} · účast {{ $this->attendedCount }}
                </dd>
            </div>
        </dl>
    </flux:card>

    <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">Účastníci ({{ $this->registrations->count() }})</flux:heading>

            <div class="flex flex-wrap gap-2">
                <flux:button icon="printer" :href="route('admin.courses.attendance-sheet', $course)">
                    Prezenční listina PDF
                </flux:button>
                @if ($this->attendedCount > 0)
                    <flux:button variant="primary" icon="arrow-down-tray" :href="route('admin.courses.certificates', $course)">
                        Certifikáty PDF ({{ $this->attendedCount }})
                    </flux:button>
                @else
                    <flux:button variant="primary" icon="arrow-down-tray" disabled>Certifikáty PDF (0)</flux:button>
                @endif
            </div>
        </div>

        <flux:card class="overflow-x-auto p-0!">
            @if ($this->registrations->isEmpty())
                <flux:text class="p-6 text-center">Na kurz zatím není nikdo přihlášen.</flux:text>
            @else
                <flux:table class="w-full">
                    <flux:table.columns>
                        <flux:table.column class="ps-6!">Jméno a příjmení</flux:table.column>
                        <flux:table.column>E-mail</flux:table.column>
                        <flux:table.column>Přihlášen</flux:table.column>
                        <flux:table.column>
                            <button type="button" wire:click="markAllAttended" class="inline-flex cursor-pointer items-center gap-2 font-medium">
                                <flux:icon.check-circle variant="micro" />
                                Zúčastnil se
                            </button>
                        </flux:table.column>
                        <flux:table.column align="end" class="pe-6!">Certifikát</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->registrations as $registration)
                            <flux:table.row :key="$registration->id">
                                <flux:table.cell class="ps-6! font-medium text-zinc-900 dark:text-white">{{ $registration->user->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:link href="mailto:{{ $registration->user->email }}" variant="subtle">{{ $registration->user->email }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $registration->created_at?->format('j. n. Y') }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:switch
                                        wire:key="attended-{{ $registration->id }}-{{ (int) $registration->attended }}"
                                        align="left"
                                        :checked="$registration->attended"
                                        wire:click="toggleAttendance({{ $registration->id }})"
                                        :label="$registration->attended ? 'Ano' : 'Ne'"
                                        aria-label="{{ $registration->user->name }} se zúčastnil"
                                    />
                                </flux:table.cell>
                                <flux:table.cell align="end" class="pe-6!">
                                    @if ($registration->attended)
                                        <flux:button size="xs" icon="arrow-down-tray" :href="route('certificates.show', $registration)">Certifikát</flux:button>
                                    @else
                                        <span class="text-sm text-zinc-400">Jen po účasti</span>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
        <flux:text class="text-xs">Účast se ukládá hned po přepnutí. Kliknutím na záhlaví „Zúčastnil se“ označíte nebo odznačíte všechny.</flux:text>
    </section>

    @if (filled($course->content))
        <flux:card>
            <flux:heading size="lg" class="mb-3">Obsah kurzu</flux:heading>
            <div class="course-content">{!! $course->content !!}</div>
        </flux:card>
    @endif

    <flux:modal name="delete-course" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Smazat kurz?</flux:heading>
                <flux:text class="mt-2">
                    Kurz „{{ $course->name }}“ bude smazán včetně {{ $this->registrations->count() }} přihlášek a záznamů o účasti. Akci nelze vrátit.
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Zrušit</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteCourse">Smazat kurz</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
