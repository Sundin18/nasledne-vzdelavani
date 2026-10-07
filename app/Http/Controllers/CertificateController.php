<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    /**
     * Download the certificate of a single participant.
     */
    public function show(CourseRegistration $registration): Response
    {
        Gate::authorize('download-certificate', $registration);

        $registration->load(['course', 'user']);

        return $this->pdf(collect([$registration]))
            ->download('certifikat-'.Str::slug($registration->user->name).'-'.$registration->certificateNumber().'.pdf');
    }

    /**
     * Download certificates of every participant who attended the course.
     */
    public function course(Course $course): Response
    {
        $registrations = $course->registrations()
            ->where('attended', true)
            ->with(['course', 'user'])
            ->get()
            ->sortBy('user.name')
            ->values();

        abort_if($registrations->isEmpty(), 404, 'Žádný účastník nemá potvrzenou účast.');

        return $this->pdf($registrations)
            ->download('certifikaty-'.Str::slug($course->title).'.pdf');
    }

    /**
     * @param  Collection<int, CourseRegistration>  $registrations
     */
    private function pdf(Collection $registrations): DomPdf
    {
        return Pdf::loadView('pdf.certificates', [
            'registrations' => $registrations,
            'organization' => config('courses.organization'),
            'signatory' => config('courses.signatory'),
        ])->setPaper('a4', 'landscape');
    }
}
