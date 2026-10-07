<?php

use App\Models\Category;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kategorie')] class extends Component {
    public string $name = '';

    /**
     * Which categories are listed: "active" or "trashed".
     */
    public string $show = 'active';

    public ?int $editingId = null;

    public string $editName = '';

    public ?int $deletingId = null;

    public function mount(): void
    {
        Gate::authorize('admin');
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->when($this->show === 'trashed', fn ($query) => $query->onlyTrashed())
            ->withCount('courses')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function trashedCount(): int
    {
        return Category::onlyTrashed()->count();
    }

    #[Computed]
    public function deleting(): ?Category
    {
        return $this->deletingId ? Category::withCount('courses')->find($this->deletingId) : null;
    }

    public function createCategory(): void
    {
        Gate::authorize('admin');

        $validated = $this->validate(
            ['name' => ['required', 'string', 'max:255', $this->uniqueName()]],
            attributes: ['name' => 'název'],
        );

        Category::create(['name' => trim($validated['name'])]);

        $this->reset('name');
        $this->forgetCachedQueries();

        Flux::toast(variant: 'success', text: 'Kategorie byla přidána.');
    }

    public function editCategory(int $categoryId): void
    {
        Gate::authorize('admin');

        $category = Category::findOrFail($categoryId);

        $this->resetValidation();
        $this->editingId = $category->id;
        $this->editName = $category->name;

        Flux::modal('edit-category')->show();
    }

    public function updateCategory(): void
    {
        Gate::authorize('admin');

        $category = Category::findOrFail($this->editingId);

        $validated = $this->validate(
            ['editName' => ['required', 'string', 'max:255', $this->uniqueName()->ignore($category->id)]],
            attributes: ['editName' => 'název'],
        );

        $category->update(['name' => trim($validated['editName'])]);

        $this->reset('editingId', 'editName');
        $this->forgetCachedQueries();

        Flux::modal('edit-category')->close();
        Flux::toast(variant: 'success', text: 'Kategorie byla přejmenována.');
    }

    public function confirmDelete(int $categoryId): void
    {
        Gate::authorize('admin');

        $this->deletingId = Category::findOrFail($categoryId)->id;
        unset($this->deleting);

        Flux::modal('delete-category')->show();
    }

    /**
     * Soft delete: the category disappears from the course form but stays
     * attached to the courses that already use it.
     */
    public function deleteCategory(): void
    {
        Gate::authorize('admin');

        Category::findOrFail($this->deletingId)->delete();

        $this->deletingId = null;
        $this->forgetCachedQueries();

        Flux::modal('delete-category')->close();
        Flux::toast(text: 'Kategorie byla smazána. U stávajících kurzů zůstává.');
    }

    public function restoreCategory(int $categoryId): void
    {
        Gate::authorize('admin');

        $category = Category::onlyTrashed()->findOrFail($categoryId);

        if (Category::where('name', $category->name)->exists()) {
            Flux::toast(variant: 'danger', text: 'Aktivní kategorie se stejným názvem už existuje.');

            return;
        }

        $category->restore();
        $this->forgetCachedQueries();

        Flux::toast(variant: 'success', text: 'Kategorie byla obnovena.');
    }

    private function uniqueName(): Unique
    {
        return Rule::unique('categories', 'name')->whereNull('deleted_at');
    }

    private function forgetCachedQueries(): void
    {
        unset($this->categories, $this->trashedCount, $this->deleting);
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">Kategorie</flux:heading>
        <flux:text class="mt-2">Kategorie, které lze přiřadit kurzům. Smazaná kategorie se už nenabízí u nových kurzů, ale u stávajících kurzů zůstává.</flux:text>
    </div>

    <flux:card>
        <form wire:submit="createCategory" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1 basis-64">
                <flux:input wire:model="name" label="Nová kategorie" placeholder="Např. Investice" required />
            </div>
            <flux:button type="submit" variant="primary" icon="plus">Přidat</flux:button>
        </form>
    </flux:card>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:radio.group wire:model.live="show" variant="segmented" class="w-fit">
            <flux:radio value="active" label="Aktivní" />
            <flux:radio value="trashed" label="Smazané ({{ $this->trashedCount }})" />
        </flux:radio.group>
    </div>

    <flux:card class="overflow-x-auto p-0!">
        @if ($this->categories->isEmpty())
            <flux:text class="p-6 text-center">
                {{ $show === 'trashed' ? 'Žádné smazané kategorie.' : 'Zatím tu nejsou žádné kategorie.' }}
            </flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="ps-6!">Název</flux:table.column>
                    <flux:table.column>Kurzů</flux:table.column>
                    <flux:table.column align="end" class="pe-6!">Akce</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->categories as $category)
                        <flux:table.row :key="$category->id">
                            <flux:table.cell class="ps-6! font-medium text-zinc-900 dark:text-white">{{ $category->name }}</flux:table.cell>
                            <flux:table.cell>{{ $category->courses_count }}</flux:table.cell>
                            <flux:table.cell align="end" class="pe-6!">
                                @if ($show === 'trashed')
                                    <flux:button size="sm" icon="arrow-uturn-left" wire:click="restoreCategory({{ $category->id }})">Obnovit</flux:button>
                                @else
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editCategory({{ $category->id }})" aria-label="Přejmenovat {{ $category->name }}" />
                                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600! dark:text-red-400!" wire:click="confirmDelete({{ $category->id }})" aria-label="Smazat {{ $category->name }}" />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:modal name="edit-category" class="w-full max-w-md">
        <form wire:submit="updateCategory" class="space-y-6">
            <flux:heading size="lg">Přejmenovat kategorii</flux:heading>
            <flux:input wire:model="editName" label="Název" required />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Zrušit</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Uložit</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-category" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Smazat kategorii „{{ $this->deleting?->name }}“?</flux:heading>
                <flux:text class="mt-2">
                    @if ($this->deleting?->courses_count)
                        Kategorie je přiřazená ke stávajícím kurzům (počet: {{ $this->deleting->courses_count }}). U nich zůstane, jen ji už nepůjde vybrat u dalších kurzů.
                    @else
                        Kategorie se přestane nabízet u kurzů. Později ji můžete obnovit.
                    @endif
                </flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Zrušit</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteCategory">Smazat</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
