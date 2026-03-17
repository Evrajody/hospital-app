@extends('pdf.rapports._layout-rapport')

@section('title', $titreDeclaration)
@section('page-size', 'A4 landscape')
@section('page-margin', '15mm 25mm')
@section('report-title', $titreDeclaration)

@section('content')
    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 80px">N&deg;PC</th>
                    <th style="width: 70px">Date AIB</th>
                    <th style="width: 180px">Fournisseur</th>
                    <th>Libell&eacute; facture</th>
                    <th class="montant" style="width: 90px">Mt Fac</th>
                    <th class="montant" style="width: 90px">Mt M.O.</th>
                    <th class="montant" style="width: 60px">Taux AIB</th>
                    <th class="montant" style="width: 90px">Montant AIB</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td>{{ $ligne['numero_piece'] }}</td>
                        <td>{{ $ligne['date'] }}</td>
                        <td>{{ $ligne['fournisseur'] }}</td>
                        <td>{{ $ligne['libelle'] }}</td>
                        <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['taux_aib'], 0) }}%</td>
                        <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="total-label">TOTAL :</td>
                    <td class="montant">{{ number_format($totaux['montant_mo'], 0, ',', ' ') }}</td>
                    <td class="montant"></td>
                    <td class="montant">{{ number_format($totaux['montant_aib'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
