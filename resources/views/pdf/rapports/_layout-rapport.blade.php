<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Rapport')</title>
    <style>
        @page {
            size: @yield('page-size', 'A4 landscape');
            margin: @yield('page-margin', '24mm 20mm');
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            padding: 0 10mm;
        }

        /* En-tête hôpital — logo à gauche, infos centrées */
        .header-section {
            width: 100%;
            margin-bottom: 10px;
        }
        .header-section td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .header-logo-cell {
            width: 150px;
        }
        .header-logo {
            height: 44px;
            width: auto;
        }
        .header-spacer-cell {
            width: 150px;
        }
        .header-section .hospital-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-section .hospital-info {
            font-size: 10px;
            color: #333;
            line-height: 1.6;
            margin-top: 4px;
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
            font-family: 'Courier New', monospace;
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
            bottom: 10mm;
            left: 0;
            right: 0;
            font-size: 10px;
            padding: 0 10mm;
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

        @yield('extra-styles')
    </style>
</head>
<body>
    @php
        $etablissement = $etablissement ?? \App\Models\Setting::getEtablissement();
    @endphp
    {{-- En-tête hôpital : logo en haut à gauche, infos centrées --}}
    <table class="header-section">
        <tr>
            <td class="header-logo-cell">
                <img class="header-logo" src="{{ public_path('images/ministere_sante.png') }}"
                     alt="Ministère de la Santé - République du Bénin" />
            </td>
            <td style="text-align: center;">
                <div class="hospital-name">{{ $etablissement['nom'] }}</div>
                <div class="hospital-info">
                    {{ $etablissement['pays'] }}<br>
                    {{ $etablissement['adresse'] }}
                    @if(!empty($etablissement['telephone']))
                        - Tél: {{ $etablissement['telephone'] }}
                    @endif
                    @if(!empty($etablissement['ifu']))
                        <br>IFU: {{ $etablissement['ifu'] }}
                    @endif
                </div>
            </td>
            <td class="header-spacer-cell"></td>
        </tr>
    </table>

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
