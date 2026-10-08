<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $start
 * @property Carbon $end
 * @property string $name
 * @property string $place
 * @property int $capacity
 * @property string|null $content
 * @property int|null $user_id
 * @property int|null $registrations_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, CourseRegistration> $registrations
 */
#[Fillable(['start', 'end', 'name', 'place', 'capacity', 'content', 'user_id'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start' => 'datetime',
            'end' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    /**
     * The administrator who created the course.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Categories of the course, including soft deleted ones: deleting a
     * category hides it from new courses but keeps it on existing ones.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTrashed()->orderBy('name');
    }

    /**
     * @return HasMany<CourseRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_registrations')
            ->withPivot('attended')
            ->withTimestamps();
    }

    /**
     * Courses that have not started yet, nearest first.
     *
     * @param  Builder<Course>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('start', '>', now())->orderBy('start');
    }

    /**
     * Courses that have already started, most recent first.
     *
     * @param  Builder<Course>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->where('start', '<=', now())->orderByDesc('start');
    }

    public function hasStarted(): bool
    {
        return $this->start->isPast();
    }

    public function registeredCount(): int
    {
        return $this->registrations_count ?? $this->registrations()->count();
    }

    public function freeSpots(): int
    {
        return max(0, $this->capacity - $this->registeredCount());
    }

    public function isFull(): bool
    {
        return $this->freeSpots() === 0;
    }

    /**
     * Category names joined for display, e.g. "Obecné, Investice".
     */
    public function categoryNames(): string
    {
        return $this->categories->pluck('name')->implode(', ');
    }

    /**
     * Human readable date and time range, e.g. "19. 11. 2026, 9:00 – 16:00".
     */
    public function formattedTerm(): string
    {
        $start = $this->start->format('j. n. Y, G:i');

        return $this->start->isSameDay($this->end)
            ? $start.' – '.$this->end->format('G:i')
            : $start.' – '.$this->end->format('j. n. Y, G:i');
    }
}
