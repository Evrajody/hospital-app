@extends('pdf.rapports._layout-rapport')

@section('title', $titre ?? 'Banques par compte')
@section('page-size', 'A4 landscape')
@section('page-margin', '22mm 20mm')
@section('report-title', $titre)

@section('content')
    @if(count($data) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucun compte bancaire trouvé.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th>Banque</th>
                    <th style="width: 150px">N° Compte</th>
                    <th class="montant" style="width: 150px">Approvisionnements</th>
                    <th class="montant" style="width: 150px">Débits (règlements)</th>
                    <th class="montant" style="width: 140px">Solde actuel</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $ligne)
                    <tr>
                        <td>{{ $ligne['banque'] ?? '-' }}</td>
                        <td>{{ $ligne['numero_compte'] }}</td>
                        <td class="montant">{{ number_format($ligne['approvisionnements'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['debits'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="total-label">TOTAL :</td>
                    <td class="montant">{{ number_format(collect($data)->sum('approvisionnements'), 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format(collect($data)->sum('debits'), 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format(collect($data)->sum('solde'), 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
