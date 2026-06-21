<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Imputation Comptable - {{ $facture->numero_piece }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 20mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000000;
            line-height: 1.4;
            padding: 14mm 18mm 18mm;
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

        .imputation-table tbody tr.block-separator td {
            border-top: 2px solid #000000;
        }

        .imputation-table tbody tr:not(.block-separator) td {
            border-top: none;
        }

        /* Pas de séparateur horizontal interne sur la colonne libellé : la valeur paraît groupée */
        .imputation-table tbody td.col-libelle {
            border-bottom: none;
        }
        .imputation-table tbody tr:last-child td.col-libelle {
            border-bottom: 1px solid #000000;
        }
        .imputation-table tbody tr.block-separator td.col-libelle {
            border-top: 2px solid #000000;
        }

        .col-date {
            width: 90px;
            text-align: center;
        }

        .col-compte {
            width: 120px;
            text-align: center;
        }

        .col-debit,
        .col-credit {
            width: 130px;
            text-align: right;
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
    <!-- En-tete officielle (identique au bordereau de paiement) -->
    @include('pdf._entete-officiel')

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
            @foreach($blocs as $bIndex => $bloc)
                @foreach($bloc['lignes'] as $lIndex => $ecriture)
                    <tr @if($lIndex === 0 && $bIndex > 0) class="block-separator" @endif>
                        <td class="col-date">
                            @if($lIndex === 0)
                                @if($bloc['label'])
                                    <strong>{{ $bloc['label'] }}</strong><br>
                                    <span style="font-size: 10px; font-weight: normal;">{{ \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') }}</span>
                                @else
                                    <strong>{{ \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') }}</strong>
                                @endif
                            @elseif(\Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') !== \Carbon\Carbon::parse($bloc['lignes'][$lIndex - 1]->date_ecriture)->format('d/m/Y'))
                                {{ \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') }}
                            @endif
                        </td>
                        <td class="col-compte">{{ $ecriture->numero_compte }}</td>
                        <td class="col-debit">{{ $ecriture->debit > 0 ? number_format($ecriture->debit, 0, ',', ' ') : '0' }}</td>
                        <td class="col-credit">{{ $ecriture->credit > 0 ? number_format($ecriture->credit, 0, ',', ' ') : '0' }}</td>
                        <td class="col-libelle">{{ ($lIndex === 0 || $ecriture->libelle !== $bloc['lignes'][$lIndex - 1]->libelle) ? $ecriture->libelle : '' }}</td>
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
