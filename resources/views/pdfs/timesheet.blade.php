@php
    $logoBase64 = base64_encode(file_get_contents(public_path('images/logo-accurity.png')));
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    @include('pdfs._styles')
</head>
<body>
    @include('pdfs._footer')

    <table style="margin-bottom:34px">
        <tr>
            <td style="width:50%;vertical-align:top">
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Accurity" style="height:24px;width:auto;display:block">
            </td>
            <td style="width:50%;text-align:right;vertical-align:top;font-size:11.5px;color:#4A5C68">Bijlage bij factuur {{ $invoice->invoice_number }}</td>
        </tr>
    </table>

    <h2 class="heading" style="margin:0 0 4px;font-size:20px;line-height:28px">Urenspecificatie {{ $invoice->periodLabel() }}</h2>
    <div style="font-size:12.5px;color:#4A5C68;margin-bottom:22px">
        {{ $invoice->project->name }} &middot; {{ $invoice->client->name }}
    </div>

    <table style="font-size:12.5px">
        <tr style="background:#F6F8FA">
            <td style="padding:9px 12px;border-bottom:1px solid #C3CDD4;font-weight:600;width:110px">Datum</td>
            <td style="padding:9px 12px;border-bottom:1px solid #C3CDD4;font-weight:600">Omschrijving</td>
            <td style="padding:9px 12px;border-bottom:1px solid #C3CDD4;font-weight:600;text-align:right;width:80px">Uren</td>
        </tr>
        @foreach ($timeEntriesPerMonth as $entries)
            @php($monthLabel = $entries->first()->date->translatedFormat('F Y'))
            <tr>
                <td colspan="3" style="padding:14px 12px 6px;font-weight:600;border-bottom:1px solid #C3CDD4">{{ ucfirst($monthLabel) }}</td>
            </tr>
            @foreach ($entries as $entry)
                <tr>
                    <td style="padding:9px 12px;border-bottom:1px solid #EDF1F4">{{ $entry->date->format('d-m-Y') }}</td>
                    <td style="padding:9px 12px;border-bottom:1px solid #EDF1F4">{{ $entry->description }}</td>
                    <td style="padding:9px 12px;border-bottom:1px solid #EDF1F4;text-align:right">{{ number_format($entry->hours, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2" style="padding:9px 12px;text-align:right;color:#4A5C68">Subtotaal {{ $monthLabel }}</td>
                <td style="padding:9px 12px;text-align:right;font-weight:600">{{ number_format($entries->sum('hours'), 2, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr style="background:#E2F1F8">
            <td style="padding:11px 12px;font-weight:600">Totaal</td>
            <td></td>
            <td style="padding:11px 12px;text-align:right;font-weight:600">{{ number_format($totalHours, 2, ',', '.') }}</td>
        </tr>
    </table>
</body>
</html>
