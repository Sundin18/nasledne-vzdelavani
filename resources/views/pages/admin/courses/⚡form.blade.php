<?php

use App\Models\Category;
use App\Models\Course;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kurz')] class extends Component {
    public ?Course $course = null;

    public string $name = '';

    /** @var array<int, int|string> */
    public array $categories = [];

    public string $start = '';

    public string $end = '';

    public string $place = '';

    public int|string $capacity = 20;

    public string $content = '';

    public function mount(?Course $course = null): void
    {
        Gate::authorize('admin');

        if (! $course?->exists) {
            return;
        }

        $this->course = $course;
        $this->name = $course->name;
        $this->categories = $course->categories()->pluck('categories.id')->map(fn ($id) => (string) $id)->all();
        $this->start = $course->start->format('Y-m-d\TH:i');
        $this->end = $course->end->format('Y-m-d\TH:i');
        $this->place = $course->place;
        $this->capacity = $course->capacity;
        $this->content = (string) $course->content;
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function allCategories(): Collection
    {
        $assigned = $this->course?->categories()->pluck('categories.id')->all() ?? [];

        return Category::withTrashed()
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', $assigned))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $registered = $this->course?->registrations()->count() ?? 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')->where(
                fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', $this->course?->categories()->pluck('categories.id')->all() ?? []),
            )],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'place' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:'.max(1, $registered), 'max:10000'],
            'content' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'název',
            'categories' => 'kategorie',
            'start' => 'začátek',
            'end' => 'konec',
            'place' => 'místo',
            'capacity' => 'kapacita',
            'content' => 'obsah',
        ];
    }

    public function save(): void
    {
        Gate::authorize('admin');

        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'start' => Date::parse($validated['start']),
            'end' => Date::parse($validated['end']),
            'place' => $validated['place'],
            'capacity' => (int) $validated['capacity'],
            'content' => filled($validated['content']) ? $validated['content'] : null,
        ];

        $isNew = $this->course === null;

        $course = DB::transaction(function () use ($data, $validated) {
            $course = $this->course ?? new Course(['user_id' => Auth::id()]);
            $course->fill($data)->save();
            $course->categories()->sync(array_map('intval', $validated['categories']));

            return $course;
        });

        Flux::toast(variant: 'success', text: $isNew ? 'Kurz byl vytvořen.' : 'Kurz byl upraven.');

        $this->redirectRoute('admin.courses.show', $course, navigate: true);
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:link :href="$course ? route('admin.courses.show', $course) : route('courses.index')" variant="subtle" class="inline-flex items-center gap-1.5 text-sm" wire:navigate>
            <flux:icon.arrow-left variant="micro" />
            {{ $course ? 'Detail kurzu' : 'Kurzy' }}
        </flux:link>
        <flux:heading size="xl" level="1" class="mt-2">{{ $course ? 'Upravit kurz' : 'Nový kurz' }}</flux:heading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Název kurzu" required autofocus />

            <flux:checkbox.group wire:model="categories" label="Kategorie" description="Lze vybrat více kategorií.">
                <div class="flex flex-wrap gap-x-6 gap-y-3">
                    @foreach ($this->allCategories as $category)
                        <flux:checkbox :value="(string) $category->id" :label="$category->trashed() ? $category->name.' (smazaná)' : $category->name" />
                    @endforeach
                </div>
            </flux:checkbox.group>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="start" type="datetime-local" label="Začátek" required />
                <flux:input wire:model="end" type="datetime-local" label="Konec" required />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="place" label="Místo" placeholder="Např. Praha, Na Příkopě 1 nebo Online" required />
                <flux:input wire:model="capacity" type="number" min="1" label="Kapacita" required />
            </div>

            <flux:editor wire:model="content" label="Obsah" description="Popis a program kurzu, který uvidí účastníci." />
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="$course ? route('admin.courses.show', $course) : route('courses.index')" wire:navigate>
                Zrušit
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ $course ? 'Uložit změny' : 'Vytvořit kurz' }}
            </flux:button>
        </div>
    </form>
</div>
