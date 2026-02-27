<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre ?? 'Etat des PC' }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0 25px; }
        .title-box h1 { font-size: 16px; font-weight: bold; text-decoration: underline; margin: 0; }
        .date-header { font-size: 12px; margin: 20px 0 10px; }
        .date-header strong { text-decoration: underline; }
        .date-header em { font-style: italic; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { border-top: 2px solid #000000; border-bottom: 2px solid #000000; padding: 6px; text-align: left; font-weight: bold; }
        td { border-bottom: 1px solid #cccccc; padding: 5px 6px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .total-row { font-weight: bold; }
        .total-row td { border-top: 2px solid #000000; border-bottom: 2px solid #000000; padding: 6px; }
        .total-label { text-align: right; text-transform: uppercase; }
        .footer-section { margin-top: 40px; font-size: 10px; }
    </style>
</head>
<body>
    <div style="font-size: 16px; font-weight: bold; margin-bottom: 15px;">HÔPITAL DE MÉNONTIN</div>

    <div class="title-box">
        <h1>{{ $titre }}</h1>
    </div>

    @if(count($groupes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune pi&egrave;ce comptable trouv&eacute;e.</p>
    @else
        @foreach($groupes as $groupe)
            <div class="date-header">
                <strong>Date d'enregistrement :</strong> <em>{{ $groupe['date_longue'] }}</em>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 120px">N&deg; PC</th>
                        <th>Objet PC</th>
                        <th class="montant" style="width: 130px">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupe['lignes'] as $ligne)
                        <tr>
                            <td>{{ $ligne['numero_piece'] }}</td>
                            <td>{{ $ligne['libelle'] }}</td>
                            <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="total-label">TOTAL :</td>
                        <td class="montant">{{ number_format($groupe['total'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endforeach
    @endif

    <table class="footer-section" style="width: 100%; border: none; margin-top: 40px;">
        <tr>
            <td style="border: none; font-style: italic;">
                &Eacute;dit&eacute; par {{ $generatedBy ?? 'Utilisateur' }} - {{ $generatedAt ?? now()->format('d/m/Y à H:i') }}
            </td>
            <td style="border: none; text-align: right;">
                Page <span class="page-num"></span>
            </td>
        </tr>
    </table>
</body>
</html>
