@php
    $etablissement = $etablissement ?? \App\Models\Setting::getEtablissement();
@endphp
<table style="width: 100%; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 3px double #000000;">
    <tr>
        <td style="text-align: center;">
            <div style="font-size: 17px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">
                {{ $etablissement['nom'] }}
            </div>
            <div style="font-size: 10px; color: #666666; line-height: 1.6; margin-top: 4px;">
                {{ $etablissement['pays'] }}<br>
                {{ $etablissement['adresse'] }}
                @if(!empty($etablissement['telephone']))
                    - Tél : {{ $etablissement['telephone'] }}
                @endif
            </div>
        </td>
    </tr>
</table>
