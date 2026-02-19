<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Chiffre d'Affaires</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0; padding: 14px; border: 3px double #000000; }
        .title-box h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .title-box .subtitle { font-size: 11px; font-style: italic; margin-top: 4px; }
        .title-box .periode { font-size: 11px; margin-top: 6px; }
        .client-header { border: 2px solid #000000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #000000; color: #ffffff; border: 1px solid #000000; padding: 8px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        td { border: 1px solid #cccccc; padding: 6px 8px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .ecart { color: #cc0000; font-weight: bold; }
        .total-row { background: #eeeeee; border-top: 2px solid #000000; font-weight: bold; }
        .total-row td { border: 1px solid #000000; font-size: 11px; }
        .total-label { text-align: right; text-transform: uppercase; }
        .global-table { max-width: 500px; margin: 30px auto; }
        .global-table th { text-align: center; font-size: 12px; padding: 10px; }
        .global-table td { font-size: 14px; padding: 12px 16px; text-align: center; }
    </style>
</head>
<body>
    @include('pdf.rapports-clients._header')

    <div class="title-box">
        <h1>{{ $titre ?? "CHIFFRE D'AFFAIRES" }}</h1>
        @if(in_array($mode, ['global_du', 'global_au', 'global_periode']))
            @if($mode === 'global_du' && !empty($dateRef))
                <div class="subtitle">CA du {{ \Carbon\Carbon::parse($dateRef)->format('d/m/Y') }}</div>
            @elseif($mode === 'global_au' && !empty($dateRef))
                <div class="subtitle">CA au {{ \Carbon\Carbon::parse($dateRef)->format('d/m/Y') }}</div>
            @elseif($mode === 'global_periode' && !empty($periode['debut']) && !empty($periode['fin']))
                <div class="periode">Période du {{ \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') }}</div>
            @endif
        @elseif($mode === 'par_client')
            <div class="subtitle">CA par client</div>
            @if(!empty($periode['debut']) && !empty($periode['fin']))
                <div class="periode">Période du {{ \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') }}</div>
            @endif
        @endif
    </div>

    @if(in_array($mode, ['global_du', 'global_au', 'global_periode']))
        @if(is_array($data) && isset($data['theorique']))
            <table class="global-table">
                <thead>
                    <tr>
                        <th>CA Théorique</th>
                        <th>CA Physique</th>
                        <th>Écart</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="montant" style="font-size: 16px; font-weight: bold;">{{ number_format($data['theorique'], 0, ',', ' ') }}</td>
                        <td class="montant" style="font-size: 16px; font-weight: bold;">{{ number_format($data['physique'], 0, ',', ' ') }}</td>
                        <td class="montant ecart" style="font-size: 16px;">{{ number_format($data['ecart'], 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @endif
    @elseif($mode === 'par_client')
        @if(is_array($data) && isset($data['lignes']))
            <div class="client-header">
                <strong><u>N° Compte :</u></strong> {{ $data['numero_compte'] }}
                &nbsp;&nbsp;&nbsp;&nbsp;
                <strong><u>Raison sociale :</u></strong> {{ $data['raison_sociale'] }}
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px; background: #eeeeee; color: #000;">N°</th>
                        <th style="background: #eeeeee; color: #000;">Réf. Facture</th>
                        <th style="background: #eeeeee; color: #000;">Date Facture</th>
                        <th class="montant" style="width: 130px; background: #eeeeee; color: #000;">Montant CA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['lignes'] as $ligne)
                        <tr>
                            <td><strong>{{ $ligne['numero'] }}</strong></td>
                            <td>{{ $ligne['reference'] }}</td>
                            <td>{{ $ligne['date_facture'] }}</td>
                            <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="total-label">Total CA Client :</td>
                        <td class="montant">{{ number_format($data['total_ca'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @endif
    @endif

    @include('pdf.rapports-clients._footer')
</body>
</html>
