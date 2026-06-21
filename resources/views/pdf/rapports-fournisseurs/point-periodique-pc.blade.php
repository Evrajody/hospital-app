{{-- Point périodique des PC — UNE pièce comptable par page.
     Template AUTONOME : chaque page reprend l'en-tête (logo + établissement + titre)
     afin que chaque fiche PC soit un document complet et imprimable séparément. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre ?? 'Etat des PC' }}</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }

        .pc-page { padding: 26mm 20mm 24mm; }
        .pc-page + .pc-page { page-break-before: always; }

        .header-section { width: 100%; margin-bottom: 10px; }
        .header-section td { border: none; padding: 0; vertical-align: middle; }
        .header-logo-cell { width: 150px; }
        .header-logo { height: 44px; width: auto; }
        .header-spacer-cell { width: 150px; }
        .hospital-name { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .hospital-info { font-size: 10px; color: #333; line-height: 1.6; margin-top: 4px; }

        .report-title-wrapper { text-align: center; margin: 15px 0; }
        .report-title { display: inline-block; text-align: center; padding: 10px 20px; border: 1px solid #000; }
        .report-title h1 { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0; }

        .date-header { font-size: 12px; margin: 20px 0 10px; }
        .date-header strong { text-decoration: underline; }
        .date-header em { font-style: italic; }

        table.report-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 10px; }
        table.report-table th { background: #fff; border: 1px solid #000; padding: 6px 4px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        table.report-table td { background: #fff; border: 1px solid #000; padding: 6px 8px; }
        table.report-table .montant { text-align: right; }
        table.report-table .col-num, table.report-table .col-date { text-align: center; }

        .footer-section { position: fixed; bottom: 8mm; left: 0; right: 0; font-size: 10px; padding: 0 20mm; color: #666; font-style: italic; }
        .footer-section td { border: none; padding: 4px 0; }
        .page-num:after { content: counter(page); }
    </style>
</head>
<body>
    @php
        $etablissement = $etablissement ?? \App\Models\Setting::getEtablissement();
    @endphp

    @if(count($pieces ?? []) === 0)
        <div class="pc-page">
            <p style="text-align: center; padding: 40px; color: #666;">Aucune pi&egrave;ce comptable trouv&eacute;e.</p>
        </div>
    @else
        @foreach($pieces as $piece)
            <div class="pc-page">
                @if($loop->first)
                {{-- En-tête officielle (identique au bordereau de paiement) — uniquement sur la 1ère page --}}
                @include('pdf._entete-officiel')

                {{-- Titre du rapport --}}
                <div class="report-title-wrapper">
                    <div class="report-title"><h1>{{ $titre }}</h1></div>
                </div>
                @endif

                {{-- Date d'enregistrement de la pièce --}}
                <div class="date-header">
                    <strong>Date d'enregistrement :</strong> <em>{{ $piece['date_longue'] }}</em>
                </div>

                {{-- La pièce comptable --}}
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 140px">N&deg; PC</th>
                            <th>Objet PC</th>
                            <th class="montant" style="width: 140px">Montant TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">{{ $piece['numero_piece'] }}</td>
                            <td>{{ $piece['libelle'] }}</td>
                            <td class="montant">{{ number_format($piece['montant'], 0, ',', ' ') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    {{-- Pied de page répété --}}
    <table class="footer-section">
        <tr>
            <td>Édité par {{ $generatedBy ?? 'Utilisateur' }} - {{ $generatedAt ?? now()->format('d/m/Y à H:i') }}</td>
            <td style="text-align: right;">Page <span class="page-num"></span></td>
        </tr>
    </table>
</body>
</html>
