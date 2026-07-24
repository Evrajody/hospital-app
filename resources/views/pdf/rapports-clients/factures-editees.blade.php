@extends('pdf.rapports._layout-rapport')

@section('title', 'État des factures clients éditées')
@section('page-size', 'A4 portrait')
@section('page-margin', '32mm 25mm 22mm')
@section('report-title', $titre ?? 'ÉTAT DES FACTURES CLIENTS ÉDITÉES')
@section('report-subtitle',
    (!empty($periode['debut']) && !empty($periode['fin'])
        ? 'Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y')
        : (!empty($periode['fin'])
            ? 'Au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y')
            : (!empty($periode['debut'])
                ? 'À partir du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y')
                : 'Toutes périodes'))))

@section('content')
    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune facture éditée pour la période sélectionnée.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 4%">N°</th>
                    <th style="width: 16%">N° Facture</th>
                    <th style="width: 12%">Date Facture</th>
                    <th style="width: 48%">Nom Client</th>
                    <th class="montant" style="width: 20% !important">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td class="col-num"><strong>{{ $ligne['numero'] }}</strong></td>
                        <td>{{ $ligne['reference'] }}</td>
                        <td class="col-date">{{ $ligne['date_facture'] }}</td>
                        <td>{{ $ligne['client'] ?: '-' }}</td>
                        <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="total-label">TOTAL :</td>
                    <td class="montant">{{ number_format($total, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
