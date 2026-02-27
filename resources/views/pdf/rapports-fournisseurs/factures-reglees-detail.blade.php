<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre ?? 'Etat des factures réglées' }} - Détail</title>
    <style>
        @page { size: A4 landscape; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0; padding: 14px; border: 3px double #000000; }
        .title-box h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .title-box .sous-titre { font-size: 11px; margin-top: 6px; font-style: italic; }
        .fournisseur-header { border: 2px solid #000000; padding: 8px 12px; margin: 15px 0 8px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th { background: #eeeeee; border: 1px solid #000000; padding: 6px 4px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        td { border: 1px solid #cccccc; padding: 5px 4px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .total-row { background: #eeeeee; border-top: 2px solid #000000; font-weight: bold; }
        .total-row td { border: 1px solid #000000; font-size: 10px; }
        .total-label { text-align: right; text-transform: uppercase; }
        .grand-total-row { background: #dddddd; font-weight: bold; }
        .grand-total-row td { border: 2px solid #000000; font-size: 11px; padding: 8px 4px; }
        .footer-section { margin-top: 30px; font-size: 10px; }
        .footer-section .edite-par { font-style: italic; }
    </style>
</head>
<body>
    @include('pdf.rapports-clients._header')

    <div class="title-box">
        <h1>{{ $titre ?? 'Etat des factures réglées' }}</h1>
        <div class="sous-titre">(D&eacute;tail par fournisseur)</div>
    </div>

    @if(count($detail) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        @foreach($detail as $fData)
            <div class="fournisseur-header">
                <strong>Fournisseur :</strong> {{ $fData['fournisseur'] }}
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 70px">N&deg;PC</th>
                        <th style="width: 65px">Date PC</th>
                        <th style="width: 65px">Date R&egrave;g.</th>
                        <th class="montant">Mt Fact.</th>
                        <th class="montant">Avoir</th>
                        <th class="montant">Mt M.O.</th>
                        <th class="montant" style="width: 45px">AIB (%)</th>
                        <th class="montant">Mt AIB</th>
                        <th class="montant">R&eacute;g. P&eacute;riode</th>
                        <th class="montant">Mt Total R&eacute;g.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fData['lignes'] as $ligne)
                        <tr>
                            <td><strong>{{ $ligne['numero_piece'] }}</strong></td>
                            <td>{{ $ligne['date'] }}</td>
                            <td>{{ $ligne['date_reglement'] }}</td>
                            <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($ligne['avoir'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($ligne['montant_mo'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ $ligne['taux_aib'] ? number_format($ligne['taux_aib'], 1) . '%' : '' }}</td>
                            <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($ligne['reg_periode'], 0, ',', ' ') }}</td>
                            <td class="montant" style="font-weight: bold;">{{ number_format($ligne['mt_total_reg'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="total-label">Total Fournisseur</td>
                        <td class="montant">{{ number_format($fData['totaux']['montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($fData['totaux']['avoir'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($fData['totaux']['montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant"></td>
                        <td class="montant">{{ number_format($fData['totaux']['montant_aib'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($fData['totaux']['reg_periode'], 0, ',', ' ') }}</td>
                        <td class="montant" style="font-weight: bold;">{{ number_format($fData['totaux']['mt_total_reg'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endforeach

        <table style="margin-top: 15px;">
            <tfoot>
                <tr class="grand-total-row">
                    <td colspan="3" class="total-label">TOTAL G&Eacute;N&Eacute;RAL</td>
                    <td class="montant">{{ number_format($grandTotaux['montant_facture'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['avoir'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['montant_mo'], 0, ',', ' ') }}</td>
                    <td class="montant"></td>
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
