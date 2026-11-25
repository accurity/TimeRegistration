@php
    $logoBase64 = base64_encode(file_get_contents(public_path('images/logo-accurity.png')));
    $blendedRate = $totalHours > 0 ? round($invoice->subtotal / $totalHours, 2) : $invoice->project->rate;
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    @include('pdfs._styles')
</head>
<body>
    @include('pdfs._footer')

    <table style="margin-bottom:40px">
        <tr>
            <td style="width:52%;vertical-align:top">
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Accurity" style="height:34px;width:auto;display:block;margin-bottom:18px">
                {{-- dompdf renders line-height about 1.35x larger with this font, so these values look small. --}}
                <div style="font-size:12px;line-height:11px;color:#4A5C68">
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
        <div style="font-size:13px;line-height:12px">
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
                {{ $invoice->lineDescription() }}<br>
                <span style="color:#4A5C68;font-size:11.5px">Urenspecificatie: zie aparte bijlage</span>
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

    <table style="font-size:12px;line-height:11px">
        <tr>
            <td style="width:130px;padding-bottom:12px;vertical-align:top">Betalingstermijn:</td>
            <td style="padding-bottom:12px;vertical-align:top">Binnen {{ (int) $invoice->invoice_date->diffInDays($invoice->due_date) }} dagen na factuurdatum van {{ $invoice->invoice_date->format('d-m-Y') }}</td>
        </tr>
        @if ($settings->iban)
            <tr>
                <td style="padding-bottom:12px;vertical-align:top">Rekeningnummer:</td>
                <td style="padding-bottom:12px;vertical-align:top">{{ $settings->iban }}</td>
            </tr>
        @endif
        <tr>
            <td style="vertical-align:top">Ten name van:</td>
            <td style="vertical-align:top">
                {{ $settings->company_name }}<br>
                te {{ $settings->city }}
            </td>
        </tr>
    </table>
</body>
</html>
