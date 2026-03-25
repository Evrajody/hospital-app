<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Imputation Comptable - {{ $facture->numero_piece }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            color: #000000;
            line-height: 1.4;
        }

        /* En-tete */
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            /* border-bottom: 2px solid #000000; */
        }

        .hospital-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .hospital-info {
            font-size: 11px;
            color: #444444;
            line-height: 1.6;
        }

        /* Titre document */
        .document-title {
            text-align: center;
            margin: 20px 0 15px 0;
        }

        .document-title h1 {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: 2px solid #000000;
            display: inline-block;
            padding: 6px 20px;
        }

        /* Numero piece */
        .numero-piece {
            margin: 15px 0;
            font-size: 13px;
        }

        .numero-piece strong {
            font-size: 14px;
            text-decoration: underline;
        }

        /* Table imputation */
        .imputation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .imputation-table thead th {
            border: 1px solid #000000;
            padding: 8px 10px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            background-color: #ffffff;
        }

        .imputation-table tbody td {
            border-left: 1px solid #000000;
            border-right: 1px solid #000000;
            border-bottom: 1px solid #cccccc;
            padding: 6px 10px;
            vertical-align: top;
        }

        .imputation-table tbody tr:last-child td {
            border-bottom: 1px solid #000000;
        }

        .date-row td {
            font-weight: bold;
            padding-top: 10px;
            border-top: 3px solid #000000;
        }

        .col-date {
            width: 90px;
        }

        .col-compte {
            width: 120px;
            font-family: 'Courier New', monospace;
            text-align: center;
        }

        .col-debit,
        .col-credit {
            width: 130px;
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .col-libelle {
            /* Rest of the width */
        }

        /* Footer */
        .footer-info {
            position: fixed;
            bottom: 10mm;
            left: 0;
            right: 0;
            padding: 0 15mm;
            font-size: 11px;
            color: #444444;
        }

        .footer-info table {
            width: 100%;
        }

        .footer-left {
            text-align: left;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- En-tete -->
    <div class="header">
        <div class="hospital-name">{{ $etablissement['nom'] }}</div>
        <div class="hospital-info">
            {{ $etablissement['pays'] }}<br>
            {{ $etablissement['adresse'] }}{{ $etablissement['telephone'] ? ' - Tel: ' . $etablissement['telephone'] : '' }}
        </div>
    </div>

    <!-- Titre -->
    <div class="document-title">
        <h1>Imputation Comptable</h1>
    </div>

    <!-- Numero piece -->
    <div class="numero-piece">
        <strong>Numero piece :</strong> {{ $facture->numero_piece }}
    </div>

    <!-- Table -->
    <table class="imputation-table">
        <thead>
            <tr>
                <th class="col-date">Date</th>
                <th class="col-compte">Compte</th>
                <th class="col-debit">Debit</th>
                <th class="col-credit">Credit</th>
                <th class="col-libelle">Libelle</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ecrituresParDate as $date => $ecritures)
                {{-- Ligne de date --}}
                <tr class="date-row">
                    <td class="col-date">{{ $date }}</td>
                    <td class="col-compte"></td>
                    <td class="col-debit"></td>
                    <td class="col-credit"></td>
                    <td class="col-libelle"></td>
                </tr>
                @foreach($ecritures as $ecriture)
                    <tr>
                        <td class="col-date"></td>
                        <td class="col-compte">{{ $ecriture->numero_compte }}</td>
                        <td class="col-debit">{{ $ecriture->debit > 0 ? number_format($ecriture->debit, 0, ',', ' ') : '0' }}</td>
                        <td class="col-credit">{{ $ecriture->credit > 0 ? number_format($ecriture->credit, 0, ',', ' ') : '0' }}</td>
                        <td class="col-libelle">{{ $ecriture->libelle }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer-info">
        <table>
            <tr>
                <td class="footer-left">
                    Edite par {{ $user?->name ?? 'Systeme' }} - {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                </td>
                <td class="footer-right">
                    Page 1 sur 1
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
