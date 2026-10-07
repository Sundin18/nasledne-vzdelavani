<?php

namespace App\Models;

use Database\Factories\CourseRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_id
 * @property int $user_id
 * @property bool $attended
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Course $course
 * @property-read User $user
 */
#[Fillable(['course_id', 'user_id', 'attended'])]
class CourseRegistration extends Model
{
    /** @use HasFactory<CourseRegistrationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Unique number printed on the certificate, e.g. "NV-2026-00042".
     */
    public function certificateNumber(): string
    {
        return sprintf('NV-%s-%05d', $this->course->starts_at->format('Y'), $this->id);
    }
}
