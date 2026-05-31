@extends('pdf.rapports._layout-rapport')

@section('title', ($titre ?? 'Etat des factures réglées') . ' - Résumé')
@section('page-size', 'A4 landscape')
@section('page-margin', '22mm 20mm')
@section('report-title', $titre ?? 'Etat des factures réglées')
@section('report-subtitle', '(Résumé par fournisseur)')

@section('content')
    @if(count($resume) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th>Fournisseur</th>
                    <th class="montant" style="width: 110px">Total Mt TTC</th>
                    <th class="montant" style="width: 95px">Total Avoir</th>
                    <th class="montant" style="width: 100px">Total Mt M.O.</th>
                    <th class="montant" style="width: 95px">Total AIB</th>
                    <th class="montant" style="width: 120px">Total Rég. Période</th>
                    <th class="montant" style="width: 110px">Total Mt Rég.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resume as $row)
                    <tr>
                        <td>{{ $row['fournisseur'] }}</td>
                        <td class="montant">{{ number_format($row['total_montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_avoir'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_aib'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_reg_periode'], 0, ',', ' ') }}</td>
                        <td class="montant" style="font-weight: bold;">{{ number_format($row['total_mt_reg'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
