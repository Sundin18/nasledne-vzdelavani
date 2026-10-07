<x-mail::message>
# Nová přihláška na kurz

Na kurz se přihlásil nový účastník.

<x-mail::table>
| | |
|:--|:--|
| **Jméno a příjmení** | {{ $user->name }} |
| **E-mail** | {{ $user->email }} |
| **Kurz** | {{ $course->title }} |
| **Datum kurzu** | {{ $course->formattedTerm() }} |
| **Místo** | {{ $course->place }} |
</x-mail::table>

<x-mail::button :url="route('admin.courses.show', $course)">
Zobrazit účastníky
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
