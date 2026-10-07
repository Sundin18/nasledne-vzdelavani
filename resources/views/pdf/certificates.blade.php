<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Certifikát</title>
    <style>
        @page { margin: 12mm; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #171717; }
        /* Explicit heights keep each certificate on one A4 page (186 mm printable). */
        .frame { height: 179mm; border: 2px solid #1d4ed8; padding: 2.5mm; }
        .inner { position: relative; height: 154mm; border: 1px solid #d4d4d4; padding: 12mm 20mm; text-align: center; }
        .organization { font-size: 15pt; font-weight: bold; }
        .label { margin-top: 10mm; font-size: 11pt; letter-spacing: 4pt; text-transform: uppercase; color: #1d4ed8; font-weight: bold; }
        h1 { margin: 2mm 0 0; font-size: 22pt; font-weight: bold; }
        .muted { color: #525252; font-size: 11pt; }
        .name { display: inline-block; margin-top: 3mm; padding: 0 12mm 2mm; border-bottom: 1px solid #d4d4d4; font-size: 26pt; font-weight: bold; }
        .course { margin-top: 3mm; font-size: 16pt; font-weight: bold; }
        .footer { position: absolute; left: 20mm; right: 20mm; bottom: 10mm; }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { vertical-align: bottom; font-size: 9.5pt; color: #525252; }
        .signature { border-top: 1px solid #a3a3a3; padding-top: 2mm; text-align: center; color: #171717; }
    </style>
</head>
<body>
@foreach ($registrations as $registration)
    <div @if (! $loop->last) style="page-break-after: always;" @endif>
        <div class="frame">
            <div class="inner">
                <div class="organization">{{ $organization }}</div>

                <div class="label">Certifikát</div>
                <h1>o absolvování kurzu následného vzdělávání</h1>

                <p class="muted" style="margin-top: 8mm;">Tímto potvrzujeme, že</p>
                <div class="name">{{ $registration->user->name }}</div>

                <p class="muted" style="margin-top: 6mm;">
                    se dne <strong style="color: #171717;">{{ $registration->course->start->format('j. n. Y') }}</strong> zúčastnil(a) kurzu
                </p>
                <div class="course">{{ $registration->course->name }}</div>
                <p class="muted" style="margin-top: 3mm;">
                    Kategorie: {{ $registration->course->categoryNames() }}
                    · Termín: {{ $registration->course->formattedTerm() }}
                    · Místo: {{ $registration->course->place }}
                </p>

                <div class="footer">
                    <table>
                        <tr>
                            <td style="text-align: left;">
                                Vystaveno: {{ now()->format('j. n. Y') }}<br>
                                Číslo certifikátu: {{ $registration->certificateNumber() }}
                            </td>
                            <td style="width: 70mm;">
                                <div class="signature">
                                    {{ $signatory ?? ' ' }}<br>
                                    <span style="font-size: 8.5pt; color: #525252;">podpis</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endforeach
</body>
</html>
