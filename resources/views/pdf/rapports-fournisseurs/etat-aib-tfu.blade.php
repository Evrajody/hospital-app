@extends('pdf.rapports._layout-rapport')

@section('title', $titreEtat)
@section('page-size', 'A4 landscape')
@section('page-margin', '15mm 20mm')
@section('report-title', $titreEtat)

@section('content')
    @if(count($lignesTfu) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 35px">N&deg;</th>
                    <th style="width: 90px">N&deg; IFU</th>
                    <th style="width: 180px">Fournisseur</th>
                    <th>Adresses</th>
                    <th class="montant" style="width: 100px">Mt prestation</th>
                    <th class="montant" style="width: 60px">Taux AIB</th>
                    <th class="montant" style="width: 100px">Montant AIB</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignesTfu as $ligne)
                    <tr>
                        <td style="text-align: center;">{{ $ligne['numero'] }}</td>
                        <td>{{ $ligne['ifu'] }}</td>
                        <td>{{ $ligne['fournisseur'] }}</td>
                        <td>{{ $ligne['adresse'] }}</td>
                        <td class="montant">{{ number_format($ligne['mt_prestation'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['taux_aib'], 0) }}%</td>
                        <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="total-label">TOTAL :</td>
                    <td class="montant">{{ number_format($totaux['montant_mo'], 0, ',', ' ') }}</td>
                    <td class="montant"></td>
                    <td class="montant">{{ number_format($totaux['montant_aib'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
