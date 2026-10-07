<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Prezenční listina</title>
    <style>
        @page { margin: 16mm 14mm; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #171717; }
        h1 { margin: 0 0 2mm; font-size: 16pt; }
        .meta { margin: 0 0 6mm; color: #525252; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #a3a3a3; padding: 2.5mm 2mm; text-align: left; vertical-align: middle; }
        th { background: #f5f5f5; font-size: 9pt; }
        .num { width: 8mm; text-align: center; }
        .check { width: 22mm; text-align: center; }
        .sign { width: 45mm; }
    </style>
</head>
<body>
    <h1>Prezenční listina</h1>
    <p class="meta">
        <strong style="color: #171717;">{{ $course->title }}</strong><br>
        {{ $course->formattedTerm() }} · {{ $course->place }} · přihlášeno {{ $registrations->count() }} z {{ $course->capacity }}
    </p>

    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Jméno a příjmení</th>
                <th>E-mail</th>
                <th class="check">Účast</th>
                <th class="sign">Podpis</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registrations as $registration)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $registration->user->name }}</td>
                    <td>{{ $registration->user->email }}</td>
                    <td class="check">{{ $registration->attended ? 'ano' : '' }}</td>
                    <td class="sign"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #525252;">Na kurz zatím není nikdo přihlášen.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
