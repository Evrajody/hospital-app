@extends('pdf.rapports._layout-rapport')

@section('title', 'État des Avances Clients')
@section('page-size', 'A4 landscape')
@section('report-title', $titre ?? 'ÉTAT DES AVANCES CLIENTS')
@section('report-subtitle',
    (!empty($periode['debut']) && !empty($periode['fin'])
        ? 'Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y')
        : (!empty($periode['fin'])
            ? 'Au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y')
            : (!empty($periode['debut'])
                ? 'À partir du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y')
                : 'Toutes périodes'))))

@section('extra-styles')
    .beneficiaires-list { font-size: 9px; }
    .reste { color: #cc0000; font-weight: bold; }
@endsection

@section('content')
    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune avance trouvée.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 28px">N°</th>
                    <th style="width: 180px">Société émettrice</th>
                    <th>Bénéficiaire(s)</th>
                    <th style="width: 80px">N° Chèque</th>
                    <th class="col-date" style="width: 65px">Date chèque</th>
                    <th class="montant" style="width: 85px">Montant</th>
                    <th class="montant" style="width: 85px">Utilisé</th>
                    <th class="montant reste" style="width: 85px">Restant</th>
                    <th style="width: 90px">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td class="col-num"><strong>{{ $loop->iteration }}</strong></td>
                        <td>
                            @if($ligne['emetteur_compte'])
                                <strong>{{ $ligne['emetteur_compte'] }}</strong> —
                            @endif
                            {{ $ligne['emetteur_nom'] ?: '-' }}
                        </td>
                        <td class="beneficiaires-list">
                            {{ count($ligne['beneficiaires']) ? implode(', ', $ligne['beneficiaires']) : '-' }}
                        </td>
                        <td>{{ $ligne['numero_cheque'] }}</td>
                        <td class="col-date">{{ $ligne['date_cheque'] }}</td>
                        <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['montant_utilise'], 0, ',', ' ') }}</td>
                        <td class="montant reste">{{ number_format($ligne['montant_restant'], 0, ',', ' ') }}</td>
                        <td>{{ $ligne['statut_libelle'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="total-label">Total :</td>
                    <td class="montant">{{ number_format($totaux['montant'], 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totaux['montant_utilise'], 0, ',', ' ') }}</td>
                    <td class="montant reste">{{ number_format($totaux['montant_restant'], 0, ',', ' ') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
