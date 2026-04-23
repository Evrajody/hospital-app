@extends('pdf.rapports._layout-rapport')

@section('title', $titre ?? 'Situation fournisseurs')
@section('page-size', 'A4 landscape')
@section('page-margin', '15mm 20mm')
@section('report-title', $titre)

@section('content')
    @if(count($data) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucun fournisseur trouvé.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 110px">N° Compte</th>
                    <th style="width: 110px">IFU</th>
                    <th>Fournisseur</th>
                    <th class="montant" style="width: 130px">Total factures</th>
                    <th class="montant" style="width: 130px">Total payé</th>
                    <th class="montant" style="width: 130px">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $ligne)
                    <tr>
                        <td>{{ $ligne['numero_compte'] }}</td>
                        <td>{{ $ligne['ifu'] ?? '' }}</td>
                        <td>{{ $ligne['nom'] }}</td>
                        <td class="montant">{{ number_format($ligne['total_factures'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['total_paye'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="total-label">TOTAL :</td>
                    <td class="montant">{{ number_format($totaux['total_factures'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totaux['total_paye'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totaux['solde'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
