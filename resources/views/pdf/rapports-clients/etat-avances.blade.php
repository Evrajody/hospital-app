@extends('pdf.rapports._layout-rapport')

@section('title', 'État des Avances Clients')
@section('page-size', 'A4 portrait')
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
    .avance-bloc { margin-bottom: 10px; }
    .avance-bloc.page-suivante { page-break-before: always; }
    .avance-entete {
        border: 1px solid #000;
        margin-bottom: 8px;
        font-size: 10px;
    }
    .avance-entete td {
        border: none;
        padding: 4px 8px;
        vertical-align: top;
    }
    .avance-entete .lib { font-weight: bold; text-transform: uppercase; font-size: 9px; color: #333; }
    .avance-entete .val { font-size: 11px; }
    .reste { color: #cc0000; font-weight: bold; }
    .ligne-vide td { color: #999; font-style: italic; }
    .avance-pied {
        margin-top: 6px;
        font-size: 10px;
        width: 100%;
        border-collapse: collapse;
    }
    .avance-pied td {
        border: 1px solid #000;
        padding: 5px 8px;
        font-weight: bold;
    }
    .avance-pied .label { text-transform: uppercase; text-align: right; }
    .avance-pied .montant { text-align: right; }
@endsection

@section('content')
    @if(count($avances) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune avance trouvée pour les critères choisis.</p>
    @else
        @foreach($avances as $av)
            <div class="avance-bloc {{ !$loop->first ? 'page-suivante' : '' }}">
                {{-- En-tête de l'avance --}}
                <table class="avance-entete" style="width: 100%; table-layout: fixed;">
                    <tr>
                        <td style="width: 60%;">
                            <div class="lib">Société émettrice</div>
                            <div class="val">
                                @if($av['emetteur_compte'])<strong>{{ $av['emetteur_compte'] }}</strong> — @endif
                                {{ $av['emetteur_nom'] ?: '-' }}
                            </div>
                        </td>
                        <td style="width: 40%;">
                            <div class="lib">Montant du chèque</div>
                            <div class="val"><strong>{{ number_format($av['montant'], 0, ',', ' ') }}</strong> F CFA</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="lib">Date du chèque</div>
                            <div class="val">{{ $av['date_cheque'] ?: '-' }}</div>
                        </td>
                        <td>
                            <div class="lib">Référence du chèque</div>
                            <div class="val">{{ $av['numero_cheque'] ?: '-' }}</div>
                        </td>
                    </tr>
                </table>

                {{-- Détail des bénéficiaires / factures réglées --}}
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 28px">N°</th>
                            <th>Bénéficiaire</th>
                            <th style="width: 95px">Réf. facture</th>
                            <th class="col-date" style="width: 70px">Date fac.</th>
                            <th class="montant" style="width: 95px">Mt facture</th>
                            <th class="montant" style="width: 100px">Mt réglé (avance)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($av['rows'] as $row)
                            <tr class="{{ is_null($row['montant_regle']) ? 'ligne-vide' : '' }}">
                                <td class="col-num">{{ $loop->iteration }}</td>
                                <td>{{ $row['beneficiaire'] ?: '-' }}</td>
                                <td>{{ $row['facture_ref'] ?: '—' }}</td>
                                <td class="col-date">{{ $row['date_facture'] ?: '—' }}</td>
                                <td class="montant">{{ is_null($row['montant_facture']) ? '—' : number_format($row['montant_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ is_null($row['montant_regle']) ? '—' : number_format($row['montant_regle'], 0, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: #666;">Aucun bénéficiaire déclaré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pied de l'avance : total utilisé + restant --}}
                <table class="avance-pied">
                    <tr>
                        <td class="label" style="width: 70%;">Total utilisé :</td>
                        <td class="montant" style="width: 30%;">{{ number_format($av['montant_utilise'], 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td class="label reste">Restant de l'avance :</td>
                        <td class="montant reste">{{ number_format($av['montant_restant'], 0, ',', ' ') }}</td>
                    </tr>
                </table>
            </div>
        @endforeach
    @endif
@endsection
