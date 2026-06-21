@extends('pdf.rapports._layout-rapport')

@section('title', 'Mouvement Factures Fournisseur')
@section('page-size', 'A4 landscape')
@section('page-margin', '32mm 20mm 22mm')
@section('report-title', $titre ?? 'ÉTAT DES MOUVEMENTS FACTURES')

@if(!empty($periode['debut']) && !empty($periode['fin']))
    @section('report-subtitle', 'Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y'))
@endif

@section('extra-styles')
    .fournisseur-header { border: 1px solid #000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
@endsection

@section('content')
    @if($fournisseur)
        <div class="fournisseur-header">
            <strong><u>N° Compte :</u></strong> {{ $fournisseur['code'] }}
            &nbsp;&nbsp;&nbsp;&nbsp;
            <strong><u>Raison sociale :</u></strong> {{ $fournisseur['nom'] }}
        </div>
    @endif

    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 70px">N&deg;PC</th>
                    <th style="width: 65px">Date PC</th>
                    <th style="width: 80px">R&eacute;f. Fact.</th>
                    <th class="montant">Mt TTC</th>
                    <th class="montant">Avoir</th>
                    <th class="montant">Mt M.O.</th>
                    <th class="montant" style="width: 45px">AIB (%)</th>
                    <th class="montant">Mt AIB</th>
                    <th class="montant">Mt D&ucirc;</th>
                    <th class="montant">Total R&egrave;g.</th>
                    <th class="montant">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td class="col-num"><strong>{{ $ligne['numero_piece'] }}</strong></td>
                        <td class="col-date">{{ $ligne['date'] }}</td>
                        <td>{{ $ligne['reference_facture'] }}</td>
                        <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['avoir'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ $ligne['taux_aib'] ? number_format($ligne['taux_aib'], 1) . '%' : '' }}</td>
                        <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['montant_du'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['total_reglement'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="total-label">TOTAUX</td>
                    <td class="montant">{{ number_format($totaux['montant_facture'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totaux['avoir'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totaux['montant_mo'], 0, ',', ' ') }}</td>
                    <td class="montant"></td>
                    <td class="montant">{{ number_format($totaux['montant_aib'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totaux['montant_du'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totaux['total_reglement'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totaux['solde'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
