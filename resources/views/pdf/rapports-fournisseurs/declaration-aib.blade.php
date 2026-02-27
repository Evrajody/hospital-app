<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titreDeclaration }}</title>
    <style>
        @page { size: A4 landscape; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0; padding: 14px; border: 3px double #000000; }
        .title-box h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th { background: #eeeeee; border: 1px solid #000000; padding: 6px 4px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        td { border: 1px solid #cccccc; padding: 5px 4px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .total-row { background: #eeeeee; border-top: 2px solid #000000; font-weight: bold; }
        .total-row td { border: 1px solid #000000; font-size: 10px; }
        .total-label { text-align: right; text-transform: uppercase; }
        .footer-section { margin-top: 30px; font-size: 10px; }
        .footer-section .edite-par { font-style: italic; }
    </style>
</head>
<body>
    <div style="font-size: 16px; font-weight: bold; margin-bottom: 15px;">HÔPITAL DE MÉNONTIN</div>

    <div class="title-box">
        <h1>{{ $titreDeclaration }}</h1>
    </div>

    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 80px">N&deg;PC</th>
                    <th style="width: 70px">Date AIB</th>
                    <th style="width: 180px">Fournisseur</th>
                    <th>Libell&eacute; facture</th>
                    <th class="montant" style="width: 90px">Mt Fac</th>
                    <th class="montant" style="width: 90px">Mt M.O.</th>
                    <th class="montant" style="width: 60px">Taux AIB</th>
                    <th class="montant" style="width: 90px">Montant AIB</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td>{{ $ligne['numero_piece'] }}</td>
                        <td>{{ $ligne['date'] }}</td>
                        <td>{{ $ligne['fournisseur'] }}</td>
                        <td>{{ $ligne['libelle'] }}</td>
                        <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['taux_aib'], 0) }}%</td>
                        <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="total-label">TOTAL :</td>
                    <td class="montant">{{ number_format($totaux['montant_mo'], 0, ',', ' ') }}</td>
                    <td class="montant"></td>
                    <td class="montant">{{ number_format($totaux['montant_aib'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <table class="footer-section" style="width: 100%; border: none; margin-top: 30px;">
        <tr>
            <td style="border: none;" class="edite-par">
                &Eacute;dit&eacute; par {{ $generatedBy ?? 'Utilisateur' }} - {{ $generatedAtLong ?? now()->format('d/m/Y') }}
            </td>
            <td style="border: none; text-align: right;">
                Page <span class="page-num"></span>
            </td>
        </tr>
    </table>
</body>
</html>
