<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre ?? 'Situation des banques' }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 5mm 5mm; }

        .hospital-name { font-size: 16px; font-weight: bold; margin-bottom: 20px; }

        .title-box { text-align: center; margin: 15px 0 25px; }
        .title-box h1 { font-size: 14px; font-weight: bold; font-style: italic; text-decoration: underline; margin: 0; }

        .bank-header { font-size: 12px; margin: 20px 0 10px; }
        .bank-header strong { text-decoration: underline; }
        .bank-header em { font-style: italic; }

        table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 0; }
        th { border: 1px solid #000000; padding: 5px 6px; text-align: center; font-weight: bold; background: #f0f0f0; }
        td { border: 1px solid #000000; padding: 4px 6px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .special-row { font-weight: bold; font-style: italic; }
        .total-row { font-weight: bold; font-style: italic; }
        .total-row td { border-top: 2px solid #000000; border-bottom: 2px solid #000000; }

        .footer-section { margin-top: 30px; font-size: 10px; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="hospital-name">{{ strtoupper($etablissement['nom'] ?? 'HÔPITAL DE MENONTIN') }}</div>

    <div class="title-box">
        <h1>{{ $titre }}</h1>
    </div>

    @if(count($sections ?? []) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        @foreach($sections as $si => $section)
            @if($si > 0)
                {{-- Saut de section --}}
            @endif

            <div class="bank-header">
                <strong>Banque :</strong>&nbsp; <em>{{ $section['numero_compte'] }} {{ $section['intitule'] }}</em>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 90px">DATE</th>
                        <th>LIBELLES</th>
                        <th class="montant" style="width: 110px">DEBIT</th>
                        <th class="montant" style="width: 110px">CREDIT</th>
                        <th class="montant" style="width: 120px">SOLDE</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Initial balance row --}}
                    <tr class="special-row">
                        <td></td>
                        <td><em>SOLDE AU&nbsp; {{ $dateAvant }}</em></td>
                        <td></td>
                        <td></td>
                        <td class="montant">{{ number_format($section['solde_initial'], 0, ',', ' ') }}</td>
                    </tr>

                    {{-- Transaction rows --}}
                    @foreach($section['transactions'] as $tx)
                        <tr>
                            <td>{{ $tx['date_fmt'] }}</td>
                            <td>{{ $tx['libelle'] }}</td>
                            <td class="montant">{{ $tx['debit'] > 0 ? number_format($tx['debit'], 0, ',', ' ') : '0' }}</td>
                            <td class="montant">{{ $tx['credit'] > 0 ? number_format($tx['credit'], 0, ',', ' ') : '0' }}</td>
                            <td class="montant">{{ number_format($tx['solde'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach

                    {{-- Period total row --}}
                    <tr class="total-row">
                        <td colspan="2"><em>SOLDE DE LA PERIODE</em></td>
                        <td class="montant">{{ number_format($section['total_debit'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($section['total_credit'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($section['solde_initial'] + $section['solde_periode'], 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif

    <table class="footer-section" style="width: 100%; border: none; margin-top: 30px;">
        <tr>
            <td style="border: none; font-style: italic;">
                Edité par {{ $generatedBy ?? 'Utilisateur' }} - {{ $generatedAtLong ?? now()->format('d/m/Y') }}
            </td>
            <td style="border: none; text-align: right;">
                Page <span class="page-num"></span>
            </td>
        </tr>
    </table>
</body>
</html>
