<?php

use App\Actions\Courses\RegisterForCourse;
use App\Models\Course;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Kurzy')] class extends Component {
    use WithPagination;

    /**
     * Which courses an administrator is looking at: "upcoming" or "past".
     */
    #[Url]
    public string $show = 'upcoming';

    public ?int $deletingId = null;

    #[Computed]
    public function isAdmin(): bool
    {
        return Gate::allows('admin');
    }

    /**
     * Upcoming courses, nearest first. Administrators can switch to past
     * ones, which are paginated by 30.
     *
     * @return Collection<int, Course>|LengthAwarePaginator<int, Course>
     */
    #[Computed]
    public function courses(): Collection|LengthAwarePaginator
    {
        $query = Course::query()->with('categories')->withCount('registrations');

        return $this->isAdmin && $this->show === 'past'
            ? $query->past()->paginate(30)
            : $query->upcoming()->get();
    }

    public function updatedShow(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<int, int>
     */
    #[Computed]
    public function registeredCourseIds(): array
    {
        return Auth::user()->courseRegistrations()->pluck('course_id')->all();
    }

    /**
     * Sign the current user up for a course.
     */
    public function register(int $courseId): void
    {
        $course = Course::findOrFail($courseId);

        try {
            app(RegisterForCourse::class)->handle(Auth::user(), $course);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: $e->validator->errors()->first());

            return;
        }

        $this->refreshCourses();

        Flux::toast(variant: 'success', text: 'Jste přihlášeni na kurz „'.$course->name.'“.');
    }

    /**
     * Cancel the current user's registration for a course that has not started yet.
     */
    public function unregister(int $courseId): void
    {
        $course = Course::findOrFail($courseId);

        if ($course->hasStarted()) {
            Flux::toast(variant: 'danger', text: 'Z kurzu, který už začal, se nelze odhlásit.');

            return;
        }

        $course->registrations()->where('user_id', Auth::id())->delete();

        $this->refreshCourses();

        Flux::toast(text: 'Byli jste odhlášeni z kurzu „'.$course->name.'“.');
    }

    public function confirmDelete(int $courseId): void
    {
        Gate::authorize('admin');

        $this->deletingId = $courseId;

        Flux::modal('delete-course')->show();
    }

    public function deleteCourse(): void
    {
        Gate::authorize('admin');

        $course = Course::findOrFail($this->deletingId);
        $course->delete();

        $this->deletingId = null;
        $this->refreshCourses();

        Flux::modal('delete-course')->close();
        Flux::toast(text: 'Kurz „'.$course->name.'“ byl smazán.');
    }

    private function refreshCourses(): void
    {
        unset($this->courses, $this->registeredCourseIds);
    }
}; ?>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Kurzy</flux:heading>
            <flux:text class="mt-2">
                @if ($this->isAdmin)
                    Správa kurzů, přihlášek a účasti.
                @else
                    Nadcházející kurzy seřazené od nejbližšího. Přihlásit se můžete, dokud je volné místo.
                @endif
            </flux:text>
        </div>

        @if ($this->isAdmin)
            <flux:button variant="primary" icon="plus" :href="route('admin.courses.create')" wire:navigate>
                Přidat kurz
            </flux:button>
        @endif
    </div>

    @if ($this->isAdmin)
        <flux:radio.group wire:model.live="show" variant="segmented" class="w-fit">
            <flux:radio value="upcoming" label="Nadcházející" />
            <flux:radio value="past" label="Proběhlé" />
        </flux:radio.group>
    @endif

    <div class="flex flex-col gap-3">
        @forelse ($this->courses as $course)
            @php
                $registered = in_array($course->id, $this->registeredCourseIds, true);
                $free = $course->freeSpots();
            @endphp

            <flux:card wire:key="course-{{ $course->id }}" class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center gap-5">
                    <div class="w-18 shrink-0 overflow-hidden rounded-lg border border-zinc-200 text-center dark:border-zinc-700">
                        <div class="bg-accent py-1 text-xs font-semibold uppercase tracking-wide text-accent-foreground">
                            {{ $course->start->locale('cs')->isoFormat('MMM') }}
                        </div>
                        <div class="pt-1.5 text-2xl font-bold text-zinc-900 dark:text-white">{{ $course->start->format('j') }}</div>
                        <div class="pb-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $course->start->locale('cs')->isoFormat('dddd') }}</div>
                    </div>

                    <div class="min-w-0 flex-1 basis-80 space-y-2">
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($course->categories as $category)
                                <flux:badge size="sm">{{ $category->name }}</flux:badge>
                            @endforeach
                        </div>

                        <flux:heading size="lg">
                            @if ($this->isAdmin)
                                <flux:link :href="route('admin.courses.show', $course)" variant="ghost" wire:navigate>{{ $course->name }}</flux:link>
                            @else
                                {{ $course->name }}
                            @endif
                        </flux:heading>

                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon.clock variant="micro" />
                                {{ $course->formattedTerm() }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon.map-pin variant="micro" />
                                {{ $course->place }}
                            </span>
                            @if ($free === 0)
                                <flux:badge size="sm" color="red">Obsazeno</flux:badge>
                            @elseif ($free <= 5)
                                <flux:badge size="sm" color="amber">Volno {{ $free }} z {{ $course->capacity }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="green">Volno {{ $free }} z {{ $course->capacity }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @unless ($course->hasStarted())
                            @if ($registered)
                                <flux:badge color="blue" icon="check">Přihlášen</flux:badge>
                                <flux:button wire:click="unregister({{ $course->id }})" wire:confirm="Opravdu se chcete z kurzu odhlásit?">
                                    Odhlásit se
                                </flux:button>
                            @elseif ($free === 0)
                                <flux:button disabled>Obsazeno</flux:button>
                            @else
                                <flux:button variant="primary" wire:click="register({{ $course->id }})">
                                    Přihlásit se
                                </flux:button>
                            @endif
                        @endunless

                        @if ($this->isAdmin)
                            <flux:button icon="users" :href="route('admin.courses.show', $course)" wire:navigate>Detail</flux:button>
                            <flux:button icon="pencil-square" variant="ghost" :href="route('admin.courses.edit', $course)" wire:navigate aria-label="Upravit kurz" />
                            <flux:button icon="trash" variant="ghost" class="text-red-600! dark:text-red-400!" wire:click="confirmDelete({{ $course->id }})" aria-label="Smazat kurz" />
                        @endif
                    </div>
                </div>

                @if (filled($course->content))
                    <details class="group border-t border-zinc-100 pt-3 dark:border-zinc-700">
                        <summary class="flex cursor-pointer list-none items-center gap-1.5 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                            Obsah kurzu
                            <flux:icon.chevron-down variant="micro" class="transition group-open:rotate-180" />
                        </summary>
                        <div class="course-content mt-3 max-w-3xl">
                            {!! $course->content !!}
                        </div>
                    </details>
                @endif
            </flux:card>
        @empty
            <flux:card class="py-10 text-center">
                <flux:text>
                    {{ $show === 'past' && $this->isAdmin ? 'Zatím neproběhl žádný kurz.' : 'Momentálně nejsou vypsané žádné kurzy.' }}
                </flux:text>
            </flux:card>
        @endforelse
    </div>

    @if ($this->courses instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
        <flux:pagination :paginator="$this->courses" />
    @endif

    @if ($this->isAdmin)
        <flux:modal name="delete-course" class="max-w-md">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Smazat kurz?</flux:heading>
                    <flux:text class="mt-2">Kurz bude smazán včetně všech přihlášek a záznamů o účasti. Akci nelze vrátit.</flux:text>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Zrušit</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteCourse">Smazat kurz</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
