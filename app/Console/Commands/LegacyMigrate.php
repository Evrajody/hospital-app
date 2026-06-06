<?php

namespace App\Console\Commands;

use App\Models\ApprovisionnementBanque;
use App\Models\Banque;
use App\Models\Classe;
use App\Models\Client;
use App\Models\CompteBancaire;
use App\Models\CompteComptable;
use App\Models\EcritureComptable;
use App\Models\FactureClient;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\ImputationFactureFournisseur;
use App\Models\ReglementClient;
use App\Models\ReglementFournisseur;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Migration des données de l'ancien système (MS Access) vers le nouveau schéma.
 *
 * Les données de l'ancien système sont d'abord chargées dans deux schémas de
 * STAGING de la base PostgreSQL (via `make migrate-legacy`) :
 *   - legacy_clients : export de « Base Factures Clients.accdb »
 *   - legacy_fsr     : export de « Base Factures des Fournisseurs.accdb »
 *
 * Cette commande lit ces schémas bruts et insère dans les tables applicatives,
 * dans l'ordre des dépendances, de façon IDEMPOTENTE (clés naturelles) et
 * TRANSACTIONNELLE. L'option --dry-run annule la transaction à la fin (aucune
 * écriture) en affichant seulement le décompte de ce qui serait importé.
 */
class LegacyMigrate extends Command
{
    protected $signature = 'legacy:migrate
        {--dry-run : Simule sans rien écrire (transaction annulée à la fin)}
        {--only= : Sous-ensemble : plan,fournisseurs,clients,banques,factures-fournisseurs,factures-clients,reglements-fournisseurs,reglements-clients,imputations,users}
        {--clients-schema=legacy_clients : Schéma de staging côté Clients}
        {--fsr-schema=legacy_fsr : Schéma de staging côté Fournisseurs}';

    protected $description = "Migre les données de l'ancien système Access (schémas de staging) vers le nouveau schéma.";

    private string $schemaClients;
    private string $schemaFsr;
    private array $only = [];
    private array $stats = [];

    /** Caches de remappage clé naturelle -> id */
    private array $compteByNumero = [];
    private array $fournisseurByCompte = [];
    private array $clientByNumcli = [];
    private array $factureClientByRef = [];
    private array $factureFsrByNumP = [];
    private array $compteBancaireByNum = [];

    public function handle(): int
    {
        $this->schemaClients = (string) $this->option('clients-schema');
        $this->schemaFsr = (string) $this->option('fsr-schema');
        $dryRun = (bool) $this->option('dry-run');
        $this->only = $this->option('only')
            ? array_map('trim', explode(',', (string) $this->option('only')))
            : ['plan', 'fournisseurs', 'clients', 'banques', 'factures-fournisseurs', 'factures-clients', 'reglements-fournisseurs', 'reglements-clients', 'imputations', 'users'];

        $this->info('=== Migration des données héritées (ancien système Access) ===');
        $this->line(($dryRun ? '<comment>MODE DRY-RUN</comment> (aucune écriture)' : '<info>MODE RÉEL</info> (écriture en base)'));
        $this->line('Staging Clients     : '.$this->schemaClients.($this->schemaExists($this->schemaClients) ? ' <info>[présent]</info>' : ' <comment>[absent]</comment>'));
        $this->line('Staging Fournisseurs: '.$this->schemaFsr.($this->schemaExists($this->schemaFsr) ? ' <info>[présent]</info>' : ' <comment>[absent]</comment>'));
        $this->newLine();

        if (! $this->schemaExists($this->schemaClients) && ! $this->schemaExists($this->schemaFsr)) {
            $this->error("Aucun schéma de staging trouvé. Lancez d'abord :  make migrate-legacy-load");
            return self::FAILURE;
        }

        // En DRY-RUN on se contente de COMPTER les volumes sources (rapide, aucune écriture).
        // Simuler des dizaines de milliers d'insertions dans une seule transaction
        // saturerait les verrous PostgreSQL (max_locks_per_transaction).
        if ($dryRun) {
            $this->dryRunReport();
            return self::SUCCESS;
        }

        try {
            // Pas de transaction géante : chaque insertion est idempotente (clés naturelles)
            // et auto-validée, ce qui libère les verrous au fil de l'eau. Un backup est
            // réalisé en amont par `make migrate-legacy`.
            $this->runStep('plan', fn () => $this->migratePlanComptable());
            $this->runStep('fournisseurs', fn () => $this->migrateFournisseurs());
            $this->runStep('clients', fn () => $this->migrateClients());
            $this->runStep('banques', fn () => $this->migrateBanques());
            $this->runStep('factures-fournisseurs', fn () => $this->migrateFacturesFournisseurs());
            $this->runStep('factures-clients', fn () => $this->migrateFacturesClients());
            $this->runStep('reglements-fournisseurs', fn () => $this->migrateReglementsFournisseurs());
            $this->runStep('reglements-clients', fn () => $this->migrateReglementsClients());
            $this->runStep('imputations', fn () => $this->migrateImputations());
            $this->runStep('users', fn () => $this->migrateUsers());

            $this->recomputeSoldes();
            $this->newLine();
            $this->info('Migration terminée.');
        } catch (\Throwable $e) {
            $this->error('Échec : '.$e->getMessage());
            $this->line($e->getFile().':'.$e->getLine());
            $this->renderStats();
            $this->comment('La migration est idempotente : corrigez la cause et relancez, elle reprendra sans doublon.');
            return self::FAILURE;
        }

        $this->renderStats();
        return self::SUCCESS;
    }

    /** Compte les volumes sources qui seraient importés (sans rien écrire). */
    private function dryRunReport(): void
    {
        $map = [
            'plan' => [[$this->schemaClients, 'listcompt'], [$this->schemaFsr, 'classecompte']],
            'fournisseurs' => [[$this->schemaFsr, 'fournisseur']],
            'clients' => [[$this->schemaClients, 'client']],
            'banques' => [[$this->schemaClients, 'banque'], [$this->schemaClients, 'bordereau'], [$this->schemaFsr, 'approvisionnement']],
            'factures-fournisseurs' => [[$this->schemaFsr, 'facture']],
            'factures-clients' => [[$this->schemaClients, 'facture']],
            'reglements-fournisseurs' => [[$this->schemaFsr, 'reglement']],
            'reglements-clients' => [[$this->schemaClients, 'reglement']],
            'imputations' => [[$this->schemaFsr, 'imputation']],
            'users' => [[$this->schemaClients, 'user'], [$this->schemaClients, 'user 1'], [$this->schemaFsr, 'user']],
        ];
        $rows = [];
        foreach ($map as $token => $sources) {
            if (! $this->wants($token)) {
                continue;
            }
            $total = 0;
            $detail = [];
            foreach ($sources as [$schema, $table]) {
                $n = $this->countStaging($schema, $table);
                $total += $n;
                if ($n > 0) {
                    $detail[] = "$schema.$table=$n";
                }
            }
            $rows[] = [$token, $total, implode(' ', $detail)];
        }
        $this->newLine();
        $this->comment('DRY-RUN — volumes sources qui seraient importés (aucune écriture) :');
        $this->table(['Entité', 'Lignes sources', 'Détail'], $rows);
    }

    private function countStaging(string $schema, string $table): int
    {
        if (! $this->schemaExists($schema) || ! $this->tableExists($schema, $table)) {
            return 0;
        }
        return (int) DB::selectOne('SELECT count(*) AS c FROM "'.$schema.'"."'.$table.'"')->c;
    }

    // ===================================================================
    // Étapes
    // ===================================================================

    private function migratePlanComptable(): void
    {
        // Plan comptable hérité : table listcompt (numcmpt, libelle, classe, numord)
        foreach ($this->staging($this->schemaClients, 'listcompt') as $r) {
            $this->ensureCompte($r->numcmpt ?? null, $r->libelle ?? null, $r->classe ?? null);
        }
        // Côté fournisseurs : hiérarchie COMPTENIV1..9 si présente
        foreach (range(1, 9) as $n) {
            $table = 'compteniv'.$n;
            foreach ($this->staging($this->schemaFsr, $table) as $r) {
                $num = $r->{'numcmpt'.$n} ?? null;
                $this->ensureCompte($num, $r->intitule ?? null);
            }
        }
        foreach ($this->staging($this->schemaFsr, 'classecompte') as $r) {
            $this->ensureClasse($r->numclass ?? null, $r->intitule ?? null);
        }
    }

    private function migrateFournisseurs(): void
    {
        foreach ($this->staging($this->schemaFsr, 'fournisseur') as $r) {
            $nom = $this->clean($r->rsfsr ?? null);
            if (! $nom) {
                continue;
            }
            $compteId = $this->ensureCompte($r->comptfsr ?? null, $nom);
            $ifu = preg_replace('/\D/', '', (string) ($r->numifu ?? ''));
            $f = Fournisseur::updateOrCreate(
                ['nom' => $this->cut($nom, 255)],
                [
                    'type_fournisseur' => 'Autres',
                    'contact' => $this->cut($r->contact ?? null, 255),
                    'telephone' => $this->cut($r->numtel ?? null, 30),
                    'ville' => $this->cut($r->ville ?? null, 100),
                    'pays' => $this->paysCode($r->pays ?? null),
                    'ifu' => strlen($ifu) >= 8 ? substr($ifu, 0, 13) : null,
                    'compte_comptable_id' => $compteId,
                    'observations' => $this->clean($r->info ?? null),
                ]
            );
            if ($r->comptfsr ?? null) {
                $this->fournisseurByCompte[$this->key($r->comptfsr)] = $f->id;
            }
            $this->fournisseurByCompte['NOM:'.$this->key($nom)] = $f->id;
            $this->bump('fournisseurs');
        }
    }

    private function migrateClients(): void
    {
        foreach ($this->staging($this->schemaClients, 'client') as $r) {
            $nom = $this->cut($r->rscli ?? null, 255) ?: 'Client '.($r->numcli ?? '');
            $compteId = $this->ensureCompte($r->numcli ?? null, $nom);
            $c = Client::updateOrCreate(
                ['compte_comptable_id' => $compteId, 'nom' => $nom],
                [
                    'adresse' => $this->clean($r->adrcli ?? null),
                    'type_client' => 'divers',
                ]
            );
            if ($r->numcli ?? null) {
                $this->clientByNumcli[$this->key($r->numcli)] = $c->id;
            }
            $this->bump('clients');
        }
    }

    private function migrateBanques(): void
    {
        // Banques côté clients (banque) + côté fournisseurs si présent
        foreach (['clients' => $this->schemaClients, 'fsr' => $this->schemaFsr] as $schema) {
            foreach ($this->staging($schema, 'banque') as $r) {
                $num = $this->clean($r->cptbanqdep ?? null);
                $lib = $this->clean($r->libbanqdep ?? null) ?: ($num ?: 'Banque');
                if (! $num) {
                    continue;
                }
                $banque = Banque::firstOrCreate(['nom' => $lib]);
                $compte = CompteBancaire::updateOrCreate(
                    ['numero_compte' => $num],
                    ['banque_id' => $banque->id, 'solde' => 0]
                );
                $this->compteBancaireByNum[$this->key($num)] = $compte->id;
                $this->bump('banques');
            }
            // Bordereaux / approvisionnements
            foreach ($this->staging($schema, 'bordereau') as $r) {
                $num = $this->clean($r->cptbanqdep ?? null);
                $ref = $this->clean($r->refbordep ?? null);
                $cbId = $this->compteBancaireByNum[$this->key($num)] ?? null;
                if (! $cbId || ! $ref) {
                    continue;
                }
                ApprovisionnementBanque::updateOrCreate(
                    ['compte_bancaire_id' => $cbId, 'reference_bordereau' => $ref],
                    [
                        'date_depot' => $this->date($r->datdep ?? null),
                        'montant' => $this->num($r->mtdep ?? 0),
                    ]
                );
                $this->bump('approvisionnements');
            }
        }

        // Côté fournisseurs : table APPROVISIONNEMENT (numdep, numcmptbanq, datedep, mtdep, observ)
        // — pas de table BANQUE dédiée ; on rattache les comptes à une banque générique.
        $banqueFsr = null;
        foreach ($this->staging($this->schemaFsr, 'approvisionnement') as $r) {
            $num = $this->clean($r->numcmptbanq ?? null);
            $ref = $this->clean($r->numdep ?? null);
            if (! $num || ! $ref) {
                continue;
            }
            $cbId = $this->compteBancaireByNum[$this->key($num)] ?? null;
            if (! $cbId) {
                $banqueFsr ??= Banque::firstOrCreate(['nom' => 'Banque (héritée fournisseurs)']);
                $compte = CompteBancaire::updateOrCreate(
                    ['numero_compte' => $num],
                    ['banque_id' => $banqueFsr->id, 'solde' => 0]
                );
                $cbId = $this->compteBancaireByNum[$this->key($num)] = $compte->id;
                $this->bump('banques');
            }
            ApprovisionnementBanque::updateOrCreate(
                ['compte_bancaire_id' => $cbId, 'reference_bordereau' => $ref],
                [
                    'date_depot' => $this->date($r->datedep ?? null),
                    'montant' => $this->num($r->mtdep ?? 0),
                    'observations' => $this->clean($r->observ ?? null),
                ]
            );
            $this->bump('approvisionnements');
        }
    }

    private function migrateFacturesFournisseurs(): void
    {
        foreach ($this->staging($this->schemaFsr, 'facture') as $r) {
            $numP = $this->clean($r->nump ?? null);
            if (! $numP) {
                continue;
            }
            $fId = $this->fournisseurByCompte[$this->key($r->comptimp ?? '')]
                ?? $this->fournisseurByCompte['NOM:'.$this->key($r->rsfsr ?? '')]
                ?? null;
            $f = FactureFournisseur::firstOrNew(['numero_piece' => $this->cut($numP, 50)]);
            $f->fill([
                'date' => $this->date($r->datfac ?? $r->datenreg ?? null),
                'date_facture_bc' => $this->date($r->datfac ?? null),
                'reference_facture' => $this->cut($r->reffac ?? null, 100),
                'fournisseur_id' => $fId,
                'fournisseur_nom' => $this->cut($r->rsfsr ?? null, 255),
                'libelle' => $this->cut($r->libfac ?? null, 500),
                'montant_facture' => $this->num($r->mtfac ?? 0),
                'montant_mo' => $this->num($r->mtmd ?? 0),
                'avoir' => $this->num($r->avoir ?? 0),
                'taux' => $this->num($r->taib ?? 0),
                'montant_reduction' => $this->num($r->mtaib ?? 0),
                'type_reduction' => $this->clean($r->numcptacpt ?? null),
                'assujetti_tva' => false,
                'statut' => 'validee',
                'created_by_name' => $this->clean($r->user ?? null),
            ]);
            $f->save();
            $this->factureFsrByNumP[$this->key($numP)] = $f->id;
            $this->bump('factures_fournisseurs');
        }
    }

    private function migrateFacturesClients(): void
    {
        foreach ($this->staging($this->schemaClients, 'facture') as $r) {
            $ref = $this->clean($r->reffac ?? null);
            if (! $ref) {
                continue;
            }
            $clientId = $this->clientByNumcli[$this->key($r->numcli ?? '')] ?? null;
            if (! $clientId) {
                $this->bump('factures_clients_orphelines');
                continue;
            }
            $fc = FactureClient::firstOrNew(['reference' => $this->cut($ref, 20)]);
            $fc->fill([
                'date_facture' => $this->date($r->datfac ?? null),
                'montant' => $this->num($r->mtfac ?? 0),
                'client_id' => $clientId,
                'client_nom' => $this->cut(optional(Client::find($clientId))->nom, 255),
                'statut' => 'non_payee',
                'created_by_name' => $this->clean($r->user ?? null),
            ]);
            $fc->save();
            $this->factureClientByRef[$this->key($ref)] = $fc->id;
            $this->bump('factures_clients');
        }
    }

    private function migrateReglementsFournisseurs(): void
    {
        foreach ($this->staging($this->schemaFsr, 'reglement') as $r) {
            $factureId = $this->factureFsrByNumP[$this->key($r->nump ?? '')] ?? null;
            if (! $factureId) {
                continue;
            }
            $facture = FactureFournisseur::find($factureId);
            $montant = $this->num($r->mtreg ?? 0);
            $ref = $this->cut($r->numch ?? null, 100);
            $date = $this->date($r->datreg ?? null);
            // Clé naturelle basée sur le CONTENU (lreg vaut souvent 0 dans l'ancien système)
            ReglementFournisseur::updateOrCreate(
                ['facture_id' => $factureId, 'date_reglement' => $date, 'montant' => $montant, 'reference' => $ref],
                [
                    'numero_reglement' => 'LEG/'.substr(md5(($r->nump ?? '').'|'.($r->lreg ?? '').'|'.$ref.'|'.$date.'|'.$montant), 0, 10),
                    'fournisseur_id' => $facture?->fournisseur_id,
                    'fournisseur_nom' => $facture?->fournisseur_nom,
                    'facture_numero' => $facture?->numero_piece,
                    'mode_paiement' => $this->mode($r->modreg ?? null),
                    'beneficiaire' => $this->cut($r->insreg ?? null, 255) ?: $facture?->fournisseur_nom,
                    'deduire_aib' => (bool) ($this->num($r->raib ?? 0) > 0),
                    'statut' => 'valide',
                    'created_by_name' => $this->clean($r->user ?? null),
                ]
            );
            $this->bump('reglements_fournisseurs');
        }
    }

    private function migrateReglementsClients(): void
    {
        foreach ($this->staging($this->schemaClients, 'reglement') as $r) {
            $factureId = $this->factureClientByRef[$this->key($r->reffac ?? '')] ?? null;
            if (! $factureId) {
                $this->bump('reglements_clients_orphelins');
                continue;
            }
            $facture = FactureClient::find($factureId);
            $montant = $this->num($r->mtch ?? 0);
            $date = $this->date($r->datreg ?? null);
            $refch = $this->cut($r->refch ?? null, 100);
            ReglementClient::updateOrCreate(
                [
                    'facture_id' => $factureId,
                    'date_reglement' => $date,
                    'montant' => $montant,
                    'reference_cheque' => $refch,
                ],
                [
                    'numero_ligne' => $this->cut((string) ($r->lreg ?? ''), 50),
                    'type_reglement' => 'reglement',
                    'client_id' => $facture?->client_id,
                    'client_nom' => $facture?->client_nom,
                    'facture_reference' => $facture?->reference,
                    'institution' => $this->cut($r->insreg ?? null, 255),
                    'created_by_name' => $this->clean($r->user ?? null),
                ]
            );
            $this->bump('reglements_clients');
        }
    }

    private function migrateImputations(): void
    {
        // Imputations / écritures fournisseurs reprises telles quelles
        foreach ($this->staging($this->schemaFsr, 'imputation') as $r) {
            $factureId = $this->factureFsrByNumP[$this->key($r->nump ?? '')]
                ?? FactureFournisseur::where('numero_piece', $this->cut($r->nump ?? null, 50))->value('id');
            if (! $factureId) {
                continue;
            }
            $numCompte = $this->cut($r->numcpt ?? null, 50);
            if (! $numCompte) {
                $this->bump('imputations_ignorees_sans_compte');
                continue; // ligne d'imputation sans compte exploitable
            }
            $debit = $this->num($r->debit ?? 0);
            $credit = $this->num($r->credit ?? 0);
            $compteId = $this->ensureCompte($numCompte, $this->clean($r->libelle ?? null));
            if ($compteId) {
                ImputationFactureFournisseur::firstOrCreate([
                    'facture_id' => $factureId,
                    'compte_id' => $compteId,
                    'nature' => $debit >= $credit ? 'debit' : 'credit',
                    'montant' => max($debit, $credit),
                ], [
                    'libelle' => $this->clean($r->libelle ?? null),
                ]);
            }
            EcritureComptable::firstOrCreate([
                'facture_id' => $factureId,
                'numero_compte' => $numCompte,
                'debit' => $debit,
                'credit' => $credit,
                'libelle' => $this->clean($r->libelle ?? null),
            ], [
                'date_ecriture' => $this->date($r->datimp ?? null),
                'type' => ($r->lreg ?? null) ? 'reglement' : 'facture',
            ]);
            $this->bump('imputations');
        }
    }

    private function migrateUsers(): void
    {
        $rows = array_merge(
            $this->staging($this->schemaClients, 'user'),
            $this->staging($this->schemaClients, 'user 1'),
            $this->staging($this->schemaFsr, 'user'),
        );
        foreach ($rows as $r) {
            $name = $this->clean($r->nompre ?? $r->nomuser ?? $r->pseudo ?? null);
            if (! $name) {
                continue;
            }
            $email = Str::slug($name).'@legacy.local';
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(Str::random(24)), // aucun mot de passe hérité
                    'is_active' => false,                       // à réactiver manuellement
                    'role' => User::ROLE_USER,
                    'poste' => $this->clean($r->typuser ?? $r->service ?? null),
                ]
            );
            $this->bump('users');
        }
    }

    /** Recalcule montant_payé / reste / statut des factures (2 requêtes groupées, rapides). */
    private function recomputeSoldes(): void
    {
        // Toujours exécuté (2 requêtes groupées, idempotentes) pour finaliser les soldes.
        {
            DB::statement(<<<'SQL'
                UPDATE factures_clients f SET
                    montant_paye  = COALESCE(r.s, 0),
                    reste_a_payer = GREATEST((f.montant - COALESCE(f.ristourne,0)) - COALESCE(r.s,0), 0),
                    statut = CASE WHEN COALESCE(r.s,0) <= 0 THEN 'non_payee'
                                  WHEN COALESCE(r.s,0) + 0.01 >= (f.montant - COALESCE(f.ristourne,0)) THEN 'payee'
                                  ELSE 'partiellement_payee' END
                FROM (SELECT facture_id, SUM(montant) s FROM reglements_clients
                      WHERE type_reglement = 'reglement' AND deleted_at IS NULL
                      GROUP BY facture_id) r
                WHERE r.facture_id = f.id
            SQL);
        }
        {
            DB::statement(<<<'SQL'
                UPDATE factures_fournisseurs f SET
                    montant_paye  = COALESCE(r.s, 0),
                    reste_a_payer = GREATEST(COALESCE(f.montant_net,0) - COALESCE(r.s,0), 0),
                    statut = CASE WHEN COALESCE(r.s,0) <= 0 THEN 'validee'
                                  WHEN COALESCE(r.s,0) + 0.01 >= COALESCE(f.montant_net,0) THEN 'payee'
                                  ELSE 'partiellement_payee' END
                FROM (SELECT facture_id, SUM(montant) s FROM reglements_fournisseurs
                      WHERE statut <> 'annule' AND deleted_at IS NULL
                      GROUP BY facture_id) r
                WHERE r.facture_id = f.id
            SQL);
        }
    }

    // ===================================================================
    // Helpers
    // ===================================================================

    private function ensureClasse(?string $code, ?string $libelle): void
    {
        $code = $this->clean($code);
        if (! $code) {
            return;
        }
        Classe::firstOrCreate(
            ['code' => $code],
            ['libelle' => $libelle ?: ('Classe '.$code), 'prefixe_compte' => $code, 'is_active' => true]
        );
    }

    private function ensureCompte(?string $numero, ?string $libelle = null, ?string $classe = null): ?int
    {
        $numero = $this->clean($numero);
        if (! $numero) {
            return null;
        }
        $k = $this->key($numero);
        if (isset($this->compteByNumero[$k])) {
            return $this->compteByNumero[$k];
        }
        $classeDigit = $classe ? substr(preg_replace('/\D/', '', $classe) ?: $numero, 0, 1) : substr($numero, 0, 1);
        $niveau = min(max(strlen(preg_replace('/\D/', '', $numero)) - 1, 1), 5);
        $compte = CompteComptable::firstOrCreate(
            ['numero_compte' => mb_substr($numero, 0, 50)],
            [
                'libelle' => mb_substr($libelle ?: $numero, 0, 255),
                'classe' => $classeDigit,
                'niveau' => $niveau,
                'is_custom' => true,
            ]
        );
        return $this->compteByNumero[$k] = $compte->id;
    }

    private function runStep(string $token, callable $fn): void
    {
        if (! $this->wants($token)) {
            return;
        }
        $this->line("→ <info>$token</info>");
        $fn();
    }

    private function wants(string $token): bool
    {
        return in_array($token, $this->only, true);
    }

    private function schemaExists(string $schema): bool
    {
        return (bool) DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.schemata WHERE schema_name = ?',
            [$schema]
        );
    }

    private function tableExists(string $schema, string $table): bool
    {
        return (bool) DB::selectOne('SELECT to_regclass(?) AS r', ['"'.$schema.'"."'.$table.'"'])?->r;
    }

    /** Lit toutes les lignes d'une table de staging (vide si absente). */
    private function staging(string $schema, string $table): array
    {
        if (! $this->schemaExists($schema) || ! $this->tableExists($schema, $table)) {
            return [];
        }
        return DB::select('SELECT * FROM "'.$schema.'"."'.$table.'"');
    }

    private function clean($v): ?string
    {
        if ($v === null) {
            return null;
        }
        // Retire le BOM UTF-8 (U+FEFF) que mdb-export place en tête de chaque table
        $v = str_replace("\u{FEFF}", '', (string) $v);
        $v = trim($v);
        return $v === '' ? null : $v;
    }

    private function cut($v, int $max): ?string
    {
        $v = $this->clean($v);
        return $v === null ? null : mb_substr($v, 0, $max);
    }

    private function num($v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $v);
    }

    private function date($v): ?string
    {
        $v = $this->clean($v);
        if (! $v) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($v)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function paysCode(?string $v): string
    {
        $v = strtoupper($this->clean($v) ?? '');
        if (strlen($v) === 2) {
            return $v;
        }
        return match (true) {
            str_contains($v, 'BEN') => 'BJ',
            str_contains($v, 'TOG') => 'TG',
            str_contains($v, 'GHAN') => 'GH',
            str_contains($v, 'NIGE') && str_contains($v, 'IA') => 'NG',
            str_contains($v, 'NIG') => 'NE',
            str_contains($v, 'BURK') => 'BF',
            str_contains($v, 'CÔTE') || str_contains($v, 'COTE') || str_contains($v, 'IVOIRE') => 'CI',
            str_contains($v, 'FRAN') => 'FR',
            default => 'BJ',
        };
    }

    private function mode(?string $v): string
    {
        $v = strtolower($this->clean($v) ?? '');
        return match (true) {
            str_contains($v, 'esp') => 'especes',
            str_contains($v, 'vir') => 'virement',
            str_contains($v, 'mobile') || str_contains($v, 'momo') => 'mobile_money',
            str_contains($v, 'carte') => 'carte',
            default => 'cheque',
        };
    }

    private function key($v): string
    {
        return strtolower(trim((string) $v));
    }

    private function bump(string $k): void
    {
        $this->stats[$k] = ($this->stats[$k] ?? 0) + 1;
    }

    private function renderStats(): void
    {
        $this->newLine();
        $this->info('Décompte importé :');
        ksort($this->stats);
        $rows = [];
        foreach ($this->stats as $k => $v) {
            $rows[] = [$k, $v];
        }
        $this->table(['Entité', 'Lignes'], $rows ?: [['(rien)', 0]]);
    }
}
