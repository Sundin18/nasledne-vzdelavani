<?php

namespace App\Mail;

use App\Models\CourseRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseRegistrationCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public CourseRegistration $registration) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $course = $this->registration->course;

        return new Envelope(
            subject: 'Nová přihláška: '.$course->title.' ('.$course->starts_at->format('j. n. Y').')',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.course-registration-created',
            with: [
                'course' => $this->registration->course,
                'user' => $this->registration->user,
            ],
        );
    }
}
