<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttendanceSheetController extends Controller
{
    /**
     * Download a printable attendance sheet of the course.
     */
    public function __invoke(Course $course): Response
    {
        $registrations = $course->registrations()
            ->with('user')
            ->get()
            ->sortBy('user.name')
            ->values();

        return Pdf::loadView('pdf.attendance-sheet', [
            'course' => $course,
            'registrations' => $registrations,
        ])->setPaper('a4')->download('prezencni-listina-'.Str::slug($course->name).'.pdf');
    }
}
