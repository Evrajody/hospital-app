@extends('pdf.rapports._layout-rapport')

@section('title', ($titre ?? 'Etat des factures réglées') . ' - Détail')
@section('page-size', 'A4 landscape')
@section('page-margin', '15mm 20mm')
@section('report-title', $titre ?? 'Etat des factures réglées')
@section('report-subtitle', '(Détail par fournisseur)')

@section('content')
    @if(count($detail) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
    @else
        @foreach($detail as $fData)
            <div class="fournisseur-label">
                <strong>Fournisseur :</strong> {{ $fData['fournisseur'] }}
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 70px">N°PC</th>
                        <th style="width: 65px">Date PC</th>
                        <th style="width: 65px">Date Règ.</th>
                        <th class="montant">Mt TTC</th>
                        <th class="montant">Avoir</th>
                        <th class="montant">Mt M.O.</th>
                        <th class="montant" style="width: 45px">AIB (%)</th>
                        <th class="montant">Mt AIB</th>
                        <th class="montant">Rég. Période</th>
                        <th class="montant">Mt Total Rég.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fData['lignes'] as $ligne)
                        <tr>
                            <td>{{ $ligne['numero_piece'] }}</td>
                            <td>{{ $ligne['date'] }}</td>
                            <td>{{ $ligne['date_reglement'] }}</td>
                            <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($ligne['avoir'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($ligne['montant_mo'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ $ligne['taux_aib'] ? number_format($ligne['taux_aib'], 1) . '%' : '' }}</td>
                            <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($ligne['reg_periode'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($ligne['mt_total_reg'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="total-label">Total Fournisseur :</td>
                        <td class="montant">{{ number_format($fData['totaux']['montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($fData['totaux']['avoir'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($fData['totaux']['montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant"></td>
                        <td class="montant">{{ number_format($fData['totaux']['montant_aib'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($fData['totaux']['reg_periode'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($fData['totaux']['mt_total_reg'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endforeach

        {{-- Grand Total --}}
        <table class="report-table" style="margin-top: 15px;">
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="total-label">TOTAL GÉNÉRAL</td>
                    <td class="montant">{{ number_format($grandTotaux['montant_facture'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['avoir'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['montant_mo'], 0, ',', ' ') }}</td>
                    <td class="montant"></td>
                    <td class="montant">{{ number_format($grandTotaux['montant_aib'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['reg_periode'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandTotaux['mt_total_reg'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
