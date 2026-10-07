<?php

use App\Models\Course;
use Flux\Flux;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kurz')] class extends Component {
    public ?Course $course = null;

    public string $title = '';

    /** @var array<int, string> */
    public array $categories = [];

    public string $starts_at = '';

    public string $ends_at = '';

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
        $this->title = $course->title;
        $this->categories = $course->categories;
        $this->starts_at = $course->starts_at->format('Y-m-d\TH:i');
        $this->ends_at = $course->ends_at->format('Y-m-d\TH:i');
        $this->place = $course->place;
        $this->capacity = $course->capacity;
        $this->content = (string) $course->content;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $registered = $this->course?->registrations()->count() ?? 0;

        return [
            'title' => ['required', 'string', 'max:255'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['string', Rule::in(config('courses.categories'))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
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
            'title' => 'název',
            'categories' => 'kategorie',
            'starts_at' => 'začátek',
            'ends_at' => 'konec',
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
            ...$validated,
            'starts_at' => Date::parse($validated['starts_at']),
            'ends_at' => Date::parse($validated['ends_at']),
            'capacity' => (int) $validated['capacity'],
            'content' => filled($validated['content']) ? $validated['content'] : null,
        ];

        if ($this->course) {
            $this->course->update($data);
            $course = $this->course;
        } else {
            $course = Course::create($data);
        }

        Flux::toast(variant: 'success', text: $this->course ? 'Kurz byl upraven.' : 'Kurz byl vytvořen.');

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
            <flux:input wire:model="title" label="Název kurzu" required autofocus />

            <flux:checkbox.group wire:model="categories" label="Kategorie" description="Lze vybrat více kategorií.">
                <div class="flex flex-wrap gap-x-6 gap-y-3">
                    @foreach (config('courses.categories') as $category)
                        <flux:checkbox :value="$category" :label="$category" />
                    @endforeach
                </div>
            </flux:checkbox.group>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="starts_at" type="datetime-local" label="Začátek" required />
                <flux:input wire:model="ends_at" type="datetime-local" label="Konec" required />
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
