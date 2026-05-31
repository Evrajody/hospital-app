@extends('pdf.rapports._layout-rapport')

@section('title', $titre ?? 'Déclaration TVA')
@section('page-size', 'A4 landscape')
@section('page-margin', '15mm 20mm')
@section('report-title', $titre)

@section('content')
    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune facture assujettie à la TVA pour la période.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 70px">Date</th>
                    <th style="width: 85px">N° PC</th>
                    <th>Fournisseur</th>
                    <th style="width: 105px">IFU</th>
                    <th>Libellé facture</th>
                    <th class="montant" style="width: 95px">Montant TTC</th>
                    <th class="montant" style="width: 65px">Taux TVA</th>
                    <th class="montant" style="width: 95px">Montant TVA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td>{{ $ligne['date'] }}</td>
                        <td>{{ $ligne['numero_piece'] }}</td>
                        <td>{{ $ligne['fournisseur'] }}</td>
                        <td>{{ $ligne['fournisseur_ifu'] }}</td>
                        <td>{{ $ligne['libelle'] }}</td>
                        <td class="montant">{{ number_format($ligne['montant_ttc'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['taux_tva'], 2) }}%</td>
                        <td class="montant">{{ number_format($ligne['montant_tva'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="total-label">TOTAL :</td>
                    <td class="montant">{{ number_format($totaux['ttc'], 0, ',', ' ') }}</td>
                    <td></td>
                    <td class="montant">{{ number_format($totaux['tva'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
