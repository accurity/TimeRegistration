<div class="footer">
    <table>
        <tr>
            <td style="width:40%;text-align:left;vertical-align:top">
                {{ $settings->company_name }}<br>
                {{ $settings->address }}<br>
                {{ $settings->postal_code }} {{ $settings->city }}
            </td>
            <td style="width:25%;text-align:left;vertical-align:top">
                @if ($settings->phone)Mobiel {{ $settings->phone }}@endif
            </td>
            <td style="width:35%;text-align:right;vertical-align:top">
                @if ($settings->kvk_number)<div>Chamber of commerce {{ $settings->kvk_number }}</div>@endif
                @if ($settings->btw_number)<div>VAT no. {{ $settings->btw_number }}</div>@endif
                @if ($settings->iban)<div>{{ $settings->iban }}</div>@endif
            </td>
        </tr>
    </table>
</div>
