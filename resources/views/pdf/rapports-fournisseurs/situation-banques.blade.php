<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre ?? 'Situation des banques' }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0 25px; }
        .title-box h1 { font-size: 16px; font-weight: bold; text-decoration: underline; margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { border-top: 2px solid #000000; border-bottom: 2px solid #000000; padding: 6px; text-align: left; font-weight: bold; }
        td { border-bottom: 1px solid #cccccc; padding: 5px 6px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .total-row { font-weight: bold; }
        .total-row td { border-top: 2px solid #000000; border-bottom: 2px solid #000000; padding: 6px; }
        .total-label { text-align: right; text-transform: uppercase; }
        .negatif { color: #cc0000; }
        .footer-section { margin-top: 40px; font-size: 10px; }
    </style>
</head>
<body>
    <div style="font-size: 16px; font-weight: bold; margin-bottom: 15px;">HÔPITAL DE MÉNONTIN</div>

    <div class="title-box">
        <h1>{{ $titre }}</h1>
    </div>

    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 100px">N&deg; Compte</th>
                    <th>Intitul&eacute;</th>
                    <th class="montant" style="width: 130px">Total D&eacute;bit</th>
                    <th class="montant" style="width: 130px">Total Cr&eacute;dit</th>
                    <th class="montant" style="width: 130px">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td>{{ $ligne['numero_compte'] }}</td>
                        <td>{{ $ligne['intitule'] }}</td>
                        <td class="montant">{{ number_format($ligne['total_debit'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['total_credit'], 0, ',', ' ') }}</td>
                        <td class="montant {{ $ligne['solde'] < 0 ? 'negatif' : '' }}">{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="total-label">TOTAL SOLDE :</td>
                    <td class="montant">{{ number_format($totalDebit, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totalCredit, 0, ',', ' ') }}</td>
                    <td class="montant {{ $totalSolde < 0 ? 'negatif' : '' }}">{{ number_format($totalSolde, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
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
