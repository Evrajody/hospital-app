<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre ?? 'Etat des factures réglées' }} - Résumé</title>
    <style>
        @page { size: A4 landscape; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0; padding: 14px; border: 3px double #000000; }
        .title-box h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .title-box .sous-titre { font-size: 11px; margin-top: 6px; font-style: italic; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #eeeeee; border: 1px solid #000000; padding: 6px 4px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        td { border: 1px solid #cccccc; padding: 5px 4px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .total-row { background: #eeeeee; border-top: 2px solid #000000; font-weight: bold; }
        .total-row td { border: 1px solid #000000; font-size: 11px; }
        .total-label { text-align: right; text-transform: uppercase; }
        .footer-section { margin-top: 30px; font-size: 10px; }
        .footer-section .edite-par { font-style: italic; }
    </style>
</head>
<body>
    @include('pdf.rapports-clients._header')

    <div class="title-box">
        <h1>{{ $titre ?? 'Etat des factures réglées' }}</h1>
        <div class="sous-titre">(R&eacute;sum&eacute; par fournisseur)</div>
    </div>

    @if(count($resume) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Fournisseur</th>
                    <th class="montant" style="width: 110px">Total Mt Fact.</th>
                    <th class="montant" style="width: 95px">Total Avoir</th>
                    <th class="montant" style="width: 100px">Total Mt M.O.</th>
                    <th class="montant" style="width: 95px">Total AIB</th>
                    <th class="montant" style="width: 120px">Total R&eacute;g. P&eacute;riode</th>
                    <th class="montant" style="width: 110px">Total Mt R&eacute;g.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resume as $row)
                    <tr>
                        <td>{{ $row['fournisseur'] }}</td>
                        <td class="montant">{{ number_format($row['total_montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_avoir'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_aib'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_reg_periode'], 0, ',', ' ') }}</td>
                        <td class="montant" style="font-weight: bold;">{{ number_format($row['total_mt_reg'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td class="total-label">TOTAL G&Eacute;N&Eacute;RAL</td>
                    <td class="montant">{{ number_format($grandTotaux['montant_facture'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['avoir'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['montant_mo'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['montant_aib'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['reg_periode'], 0, ',', ' ') }}</td>
                    <td class="montant" style="font-weight: bold;">{{ number_format($grandTotaux['mt_total_reg'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <table class="footer-section" style="width: 100%; border: none; margin-top: 30px;">
        <tr>
            <td style="border: none;" class="edite-par">
                &Eacute;dit&eacute; par {{ $generatedBy ?? 'Utilisateur' }} - {{ $generatedAt ?? now()->format('d/m/Y à H:i') }}
            </td>
            <td style="border: none; text-align: right;">
                Page <span class="page-num"></span>
            </td>
        </tr>
    </table>
</body>
</html>
