<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $title
 * @property array<int, string> $categories
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $place
 * @property int $capacity
 * @property string|null $content
 * @property int|null $registrations_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, CourseRegistration> $registrations
 */
#[Fillable(['title', 'categories', 'starts_at', 'ends_at', 'place', 'capacity', 'content'])]
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
            'categories' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
        ];
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
        $query->where('starts_at', '>', now())->orderBy('starts_at');
    }

    /**
     * Courses that have already started, most recent first.
     *
     * @param  Builder<Course>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->where('starts_at', '<=', now())->orderByDesc('starts_at');
    }

    public function hasStarted(): bool
    {
        return $this->starts_at->isPast();
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
     * Human readable date and time range, e.g. "19. 11. 2026, 9:00 – 16:00".
     */
    public function formattedTerm(): string
    {
        $start = $this->starts_at->format('j. n. Y, G:i');

        return $this->starts_at->isSameDay($this->ends_at)
            ? $start.' – '.$this->ends_at->format('G:i')
            : $start.' – '.$this->ends_at->format('j. n. Y, G:i');
    }
}
