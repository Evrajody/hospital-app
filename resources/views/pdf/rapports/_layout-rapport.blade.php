<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Rapport')</title>
    <style>
        @page {
            size: @yield('page-size', 'A4 landscape');
            margin: 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* dompdf ignore les marges @page avec setPaper() : les marges sont portées
           par le body (haut / côtés / bas) pour garantir un rendu correct. */
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            padding: 14mm 20mm 22mm;
        }

        /* Titre du rapport — cadre simple */
        .report-title {
            text-align: center;
            margin: 15px 0;
            padding: 10px 20px;
            border: 1px solid #000;
            display: inline-block;
        }
        .report-title-wrapper {
            text-align: center;
            margin: 15px 0;
        }
        .report-title h1 {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .report-title .sous-titre {
            font-size: 10px;
            margin-top: 4px;
            font-style: italic;
        }

        /* Tableaux — fond blanc, traits simples */
        table.report-table {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }
        table.report-table th {
            background: #fff;
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.report-table td {
            background: #fff;
            border: 1px solid #000;
            padding: 5px 4px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.report-table .montant {
            text-align: right;
        }
        /* Colonnes N° de ligne et date : contenu centré (cf. demande uniformisation). */
        table.report-table .col-num,
        table.report-table .col-date,
        table.report-table td.col-num,
        table.report-table td.col-date {
            text-align: center;
        }
        table.report-table .total-row {
            font-weight: bold;
        }
        table.report-table .total-row td {
            border: 1px solid #000;
        }
        table.report-table .total-label {
            text-align: right;
            text-transform: uppercase;
        }

        /* Bloc fournisseur (pour détail groupé) */
        .fournisseur-label {
            font-size: 12px;
            margin: 16px 0 6px;
        }

        /* Pied de page */
        .footer-section {
            position: fixed;
            bottom: 8mm;
            left: 0;
            right: 0;
            font-size: 10px;
            padding: 0 20mm;
            color: #666;
            font-style: italic;
        }
        .footer-section td {
            border: none !important;
            background: none !important;
            padding: 4px 0;
        }
        .footer-section .edite-par {
            font-style: italic;
        }
        /* Numéro de page : dompdf remplit ce pseudo-élément via le compteur de page.
           Sans cette règle, le <span class="page-num"> restait vide. */
        .page-num:after { content: counter(page); }

        @yield('extra-styles')
    </style>
</head>
<body>
    @php
        $etablissement = $etablissement ?? \App\Models\Setting::getEtablissement();
    @endphp
    {{-- En-tête officielle commune (logo Ministère + Direction / Zone / Hôpital),
         identique au bordereau de paiement. --}}
    @include('pdf._entete-officiel')

    {{-- Titre du rapport --}}
    <div class="report-title-wrapper">
        <div class="report-title">
            <h1>@yield('report-title', 'Rapport')</h1>
            @hasSection('report-subtitle')
                <div class="sous-titre">@yield('report-subtitle')</div>
            @endif
        </div>
    </div>

    {{-- Contenu du rapport --}}
    @yield('content')

    {{-- Pied de page --}}
    <table class="footer-section">
        <tr>
            <td class="edite-par">
                Édité par {{ $generatedBy ?? 'Utilisateur' }} - {{ $generatedAt ?? now()->format('d/m/Y à H:i') }}
            </td>
            <td style="text-align: right;">
                Page <span class="page-num"></span>
            </td>
        </tr>
    </table>
</body>
</html>
