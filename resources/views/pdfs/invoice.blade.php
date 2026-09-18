@php
    $logoBase64 = base64_encode(file_get_contents(public_path('images/logo-accurity.png')));
    $periodLabel = ucfirst(\Illuminate\Support\Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->translatedFormat('F Y'));
    $blendedRate = $totalHours > 0 ? round($invoice->subtotal / $totalHours, 2) : $invoice->project->rate;
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'Bitter';
            font-weight: 700;
            font-style: normal;
            src: url('{{ 'file://'.resource_path('fonts/Bitter.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Calibri';
            font-weight: 400;
            font-style: normal;
            src: url('{{ 'file://'.resource_path('fonts/Calibri.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Calibri';
            font-weight: 600;
            font-style: normal;
            src: url('{{ 'file://'.resource_path('fonts/Calibri.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Calibri';
            font-weight: 700;
            font-style: normal;
            src: url('{{ 'file://'.resource_path('fonts/Calibri.ttf') }}') format('truetype');
        }
        @page {
            margin: 56px 60px 90px 60px;
        }
        body {
            font-family: 'Calibri', sans-serif;
            color: #0F1A21;
            font-size: 12px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .heading {
            font-family: 'Bitter', serif;
            font-weight: 700;
        }
        .footer {
            position: fixed;
            bottom: -70px;
            left: 0;
            right: 0;
            border-top: 1px solid #DEE5EA;
            padding-top: 14px;
            font-size: 10.5px;
            color: #6B7C88;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="footer">
        <table>
            <tr>
                <td style="text-align:left">{{ $settings->company_name }}</td>
                <td style="text-align:center">
                    @if ($settings->kvk_number)KvK {{ $settings->kvk_number }}@endif
                    @if ($settings->kvk_number && $settings->btw_number) &middot; @endif
                    @if ($settings->btw_number)Btw {{ $settings->btw_number }}@endif
                </td>
                <td style="text-align:right">{{ $invoice->invoice_number }}</td>
            </tr>
        </table>
    </div>

    <table style="margin-bottom:40px">
        <tr>
            <td style="width:52%;vertical-align:top">
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Accurity" style="height:34px;width:auto;display:block;margin-bottom:18px">
                <div style="font-size:12px;line-height:19px;color:#4A5C68">
                    {{ $settings->company_name }}<br>
                    {{ $settings->address }}<br>
                    {{ $settings->postal_code }} {{ $settings->city }}
                </div>
            </td>
            <td style="width:44%;text-align:right;vertical-align:top">
                <div class="heading" style="font-size:30px;line-height:38px;color:#027CB5;margin-bottom:14px">Factuur</div>
                <table style="font-size:12px">
                    <tr><td style="padding:3px 0;color:#4A5C68;text-align:right">Factuurnummer</td><td style="padding:3px 0 3px 16px;text-align:right;font-weight:600">{{ $invoice->invoice_number }}</td></tr>
                    <tr><td style="padding:3px 0;color:#4A5C68;text-align:right">Factuurdatum</td><td style="padding:3px 0 3px 16px;text-align:right">{{ $invoice->invoice_date->format('d-m-Y') }}</td></tr>
                    <tr><td style="padding:3px 0;color:#4A5C68;text-align:right">Vervaldatum</td><td style="padding:3px 0 3px 16px;text-align:right">{{ $invoice->due_date->format('d-m-Y') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-bottom:34px">
        <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#6B7C88;margin-bottom:8px">Factuur aan</div>
        <div style="font-size:13px;line-height:21px">
            <strong style="font-weight:600">{{ $invoice->client->name }}</strong><br>
            {{ $invoice->client->address }}<br>
            {{ $invoice->client->postal_code }} {{ $invoice->client->city }}
            @if ($invoice->client->btw_number)
                <br><span style="color:#4A5C68">Btw-nummer {{ $invoice->client->btw_number }}</span>
            @endif
        </div>
    </div>

    <table style="font-size:12.5px;margin-bottom:28px">
        <tr style="background:#027CB5;color:#fff">
            <td style="padding:9px 12px;text-align:left;font-weight:600">Omschrijving</td>
            <td style="padding:9px 12px;text-align:right;font-weight:600;width:70px">Uren</td>
            <td style="padding:9px 12px;text-align:right;font-weight:600;width:90px">Tarief</td>
            <td style="padding:9px 12px;text-align:right;font-weight:600;width:110px">Bedrag</td>
        </tr>
        <tr>
            <td style="padding:12px;border-bottom:1px solid #DEE5EA">
                {{ $invoice->project->name }} &mdash; werkzaamheden {{ $periodLabel }}<br>
                <span style="color:#4A5C68;font-size:11.5px">Urenspecificatie: zie bijlage op pagina 2</span>
            </td>
            <td style="padding:12px;border-bottom:1px solid #DEE5EA;text-align:right">{{ number_format($totalHours, 2, ',', '.') }}</td>
            <td style="padding:12px;border-bottom:1px solid #DEE5EA;text-align:right">&euro; {{ number_format($blendedRate, 2, ',', '.') }}</td>
            <td style="padding:12px;border-bottom:1px solid #DEE5EA;text-align:right">&euro; {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td style="padding:8px 12px;text-align:right;color:#4A5C68">Subtotaal</td>
            <td style="padding:8px 12px;text-align:right">&euro; {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td style="padding:8px 12px;text-align:right;color:#4A5C68">Btw {{ number_format($invoice->vat_percentage, 0) }}%</td>
            <td style="padding:8px 12px;text-align:right">&euro; {{ number_format($invoice->vat_amount, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td style="padding:11px 12px;text-align:right;font-weight:600;background:#E2F1F8;border-top:2px solid #027CB5">Totaal te voldoen</td>
            <td style="padding:11px 12px;text-align:right;font-weight:600;background:#E2F1F8;border-top:2px solid #027CB5">&euro; {{ number_format($invoice->total, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div style="font-size:12px;line-height:20px;color:#4A5C68">
        Wij verzoeken u het totaalbedrag v&oacute;&oacute;r {{ $invoice->due_date->format('d-m-Y') }} over te maken
        @if ($settings->iban)
            op {{ $settings->iban }} t.n.v. {{ $settings->company_name }},
        @endif
        onder vermelding van factuurnummer {{ $invoice->invoice_number }}.
    </div>

    <div class="page-break"></div>

    <table style="margin-bottom:34px">
        <tr>
            <td style="width:50%;vertical-align:top">
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Accurity" style="height:24px;width:auto;display:block">
            </td>
            <td style="width:50%;text-align:right;vertical-align:top;font-size:11.5px;color:#4A5C68">Bijlage bij factuur {{ $invoice->invoice_number }}</td>
        </tr>
    </table>

    <h2 class="heading" style="margin:0 0 4px;font-size:20px;line-height:28px">Urenspecificatie {{ $periodLabel }}</h2>
    <div style="font-size:12.5px;color:#4A5C68;margin-bottom:22px">
        {{ $invoice->project->name }} &middot; {{ $invoice->client->name }}
        @if ($approval && $approval->isApproved())
            &middot; goedgekeurd op {{ $approval->approved_at->format('d-m-Y') }}
        @endif
    </div>

    <table style="font-size:12.5px">
        <tr style="background:#F6F8FA">
            <td style="padding:9px 12px;border-bottom:1px solid #C3CDD4;font-weight:600;width:110px">Datum</td>
            <td style="padding:9px 12px;border-bottom:1px solid #C3CDD4;font-weight:600">Omschrijving</td>
            <td style="padding:9px 12px;border-bottom:1px solid #C3CDD4;font-weight:600;text-align:right;width:80px">Uren</td>
        </tr>
        @foreach ($timeEntries as $entry)
            <tr>
                <td style="padding:9px 12px;border-bottom:1px solid #EDF1F4">{{ $entry->date->format('d-m-Y') }}</td>
                <td style="padding:9px 12px;border-bottom:1px solid #EDF1F4">{{ $entry->description }}</td>
                <td style="padding:9px 12px;border-bottom:1px solid #EDF1F4;text-align:right">{{ number_format($entry->hours, 2, ',', '.') }}</td>
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
