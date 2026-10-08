<x-layouts::public :title="$page->title">
    <article class="flex max-w-3xl flex-col gap-6">
        <flux:heading size="xl" level="1">{{ $page->title }}</flux:heading>

        <div class="course-content">
            {!! Str::markdown($page->content) !!}
        </div>
    </article>
</x-layouts::public>
