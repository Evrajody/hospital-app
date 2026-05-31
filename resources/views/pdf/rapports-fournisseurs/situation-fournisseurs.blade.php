@extends('pdf.rapports._layout-rapport')

@section('title', 'Situation des Fournisseurs')
@section('page-size', 'A4 {{ $mode === "par_fournisseur" ? "landscape" : "portrait" }}')
@section('page-margin', '32mm 25mm 22mm')
@php
    $suffixe = '';
    $dp = $date_point ?? null;
    $dd = $date_debut ?? null;
    $df = $date_fin ?? null;
    if ($dp) {
        $suffixe = ' — Point au ' . \Carbon\Carbon::parse($dp)->format('d/m/Y');
    } elseif ($dd && $df && $dd === $df) {
        $suffixe = ' au ' . \Carbon\Carbon::parse($df)->format('d/m/Y');
    } elseif ($dd && $df) {
        $suffixe = ' du ' . \Carbon\Carbon::parse($dd)->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($df)->format('d/m/Y');
    } elseif ($dd) {
        $suffixe = ' à partir du ' . \Carbon\Carbon::parse($dd)->format('d/m/Y');
    } elseif ($df) {
        $suffixe = ' au ' . \Carbon\Carbon::parse($df)->format('d/m/Y');
    }
@endphp
@section('report-title', ($titre ?? 'Situation des fournisseurs (point des dettes)') . $suffixe)

@section('extra-styles')
    .compte-header { font-size: 12px; margin: 20px 0 10px; font-style: italic; }
    .fournisseur-header { font-size: 12px; margin: 15px 0 8px; text-decoration: underline; }
    .restant { color: #000; font-weight: bold; }
    .page-break { page-break-before: always; }
@endsection

@section('content')
    @if($mode === 'tous')
        @if(count($data) === 0)
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @else
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px">N&deg;</th>
                        <th>Raison sociale</th>
                        <th class="montant" style="width: 130px">Montant total d&ucirc;</th>
                        <th class="montant" style="width: 130px">Montant total des r&egrave;glements</th>
                        <th class="montant restant" style="width: 120px">Restant d&ucirc;</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            <td><strong>{{ $row['numero'] }}</strong></td>
                            <td>{{ $row['raison_sociale'] }}</td>
                            <td class="montant">{{ number_format($row['montant_du'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($row['montant_reglements'], 0, ',', ' ') }}</td>
                            <td class="montant restant">{{ number_format($row['restant_du'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="total-label">TOTAL G&Eacute;N&Eacute;RAL</td>
                        <td class="montant">{{ number_format($grandTotal['montant_du'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotal['montant_reglements'], 0, ',', ' ') }}</td>
                        <td class="montant restant">{{ number_format($grandTotal['restant_du'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

    @elseif($mode === 'par_compte')
        @if(count($data) === 0)
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @else
            @if(!empty($compte))
                <div class="compte-header">
                    <strong>Compte :</strong> <em>{{ $compte['numero_compte'] }} {{ $compte['libelle'] }}</em>
                </div>
            @endif
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px">N&deg;</th>
                        <th>Raison sociale</th>
                        <th class="montant" style="width: 130px">Montant total d&ucirc;</th>
                        <th class="montant" style="width: 130px">Montant total des r&egrave;glements</th>
                        <th class="montant restant" style="width: 120px">Restant d&ucirc;</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            <td><strong>{{ $row['numero'] }}</strong></td>
                            <td>[{{ $row['numero_compte'] }}] {{ $row['raison_sociale'] }}</td>
                            <td class="montant">{{ number_format($row['montant_du'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($row['montant_reglements'], 0, ',', ' ') }}</td>
                            <td class="montant restant">{{ number_format($row['restant_du'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="total-label">TOTAL G&Eacute;N&Eacute;RAL</td>
                        <td class="montant">{{ number_format($grandTotal['montant_du'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotal['montant_reglements'], 0, ',', ' ') }}</td>
                        <td class="montant restant">{{ number_format($grandTotal['restant_du'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

    @elseif($mode === 'par_fournisseur')
        @if(count($data) === 0)
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @else
            @foreach($data as $fData)
                <div class="fournisseur-header">
                    <strong>Fournisseur :</strong> {{ $fData['fournisseur'] }}
                </div>
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
                        @foreach($fData['lignes'] as $ligne)
                            <tr>
                                <td>{{ $ligne['numero_piece'] }}</td>
                                <td>{{ $ligne['date'] }}</td>
                                <td>{{ $ligne['reference_facture'] }}</td>
                                <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['avoir'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['montant_mo'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ $ligne['taux_aib'] ? number_format($ligne['taux_aib'], 1) . '%' : '0%' }}</td>
                                <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['montant_du'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['total_reglement'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" class="total-label">TOTAL :</td>
                            <td class="montant">{{ number_format($fData['totaux']['montant_facture'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['avoir'], 0, ',', ' ') }}</td>
                            <td class="montant"></td>
                            <td class="montant"></td>
                            <td class="montant"></td>
                            <td class="montant">{{ number_format($fData['totaux']['montant_du'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['total_reglement'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['solde'], 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endforeach

            <table class="report-table" style="margin-top: 20px;">
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="total-label">TOTAL G&Eacute;N&Eacute;RAL :</td>
                        <td class="montant">{{ number_format($grandTotaux['montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['avoir'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant"></td>
                        <td class="montant">{{ number_format($grandTotaux['montant_aib'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['montant_du'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['total_reglement'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['solde'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @endif
@endsection
