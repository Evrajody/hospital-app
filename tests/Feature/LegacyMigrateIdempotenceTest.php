<?php

namespace Tests\Feature;

use App\Models\FactureClient;
use App\Models\FactureFournisseur;
use App\Models\ReglementClient;
use App\Models\ReglementFournisseur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Prouve que `legacy:migrate` est IDEMPOTENT : la relancer sur une base déjà
 * peuplée n'ajoute aucun doublon et ne provoque aucun conflit d'unicité.
 * On monte un staging minimal (schemas legacy_clients / legacy_fsr) puis on
 * lance la migration DEUX fois et on compare les volumes.
 */
class LegacyMigrateIdempotenceTest extends TestCase
{
    use RefreshDatabase;

    private function setUpStaging(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS legacy_fsr');
        DB::statement('CREATE SCHEMA IF NOT EXISTS legacy_clients');

        // --- Fournisseurs ---
        DB::statement('CREATE TABLE legacy_fsr.fournisseur (rsfsr text, comptfsr text, numifu text, contact text, numtel text, ville text, pays text, info text)');
        DB::statement("INSERT INTO legacy_fsr.fournisseur VALUES ('FOURN A','401001','12345678901','M. X','90000000','Cotonou','BJ','note')");

        DB::statement('CREATE TABLE legacy_fsr.facture (nump text, datenreg text, comptimp text, typimp text, reffac text, datfac text, libfac text, mtfac int, mtmd int, avoir int, taib int, mtaib int, numcptacpt text, rsfsr text, datreg text, statu boolean, "user" text, annee int)');
        DB::statement("INSERT INTO legacy_fsr.facture VALUES ('PC/T/0001','2020-03-01',NULL,NULL,'REF1','2020-03-01','Achat',100000,0,0,0,0,NULL,'FOURN A','2020-04-02',true,'AGENT',2020)");

        // 2 règlements DISTINCTS pour la même facture (lignes lreg différentes) → 2 lignes attendues
        DB::statement('CREATE TABLE legacy_fsr.reglement (nump text, mtreg int, numch text, datreg text, lreg int, modreg text, insreg text, raib int, "user" text)');
        DB::statement("INSERT INTO legacy_fsr.reglement VALUES ('PC/T/0001',40000,'C1','2020-04-01',1,'cheque','BOA',0,'AGENT')");
        DB::statement("INSERT INTO legacy_fsr.reglement VALUES ('PC/T/0001',60000,'C2','2020-04-02',2,'cheque','BOA',0,'AGENT')");

        // --- Clients ---
        DB::statement('CREATE TABLE legacy_clients.client (rscli text, numcli text, adrcli text)');
        DB::statement("INSERT INTO legacy_clients.client VALUES ('CLIENT A','4111001','Cotonou')");

        DB::statement('CREATE TABLE legacy_clients.facture (reffac text, datfac text, mtfac int, numcli text, etatfac boolean, datregfac text, "user" text, ann int)');
        DB::statement("INSERT INTO legacy_clients.facture VALUES ('0001/03/20','2020-03-01',50000,'4111001',true,'2020-04-01','AGENT',2020)");

        DB::statement('CREATE TABLE legacy_clients.reglement (reffac text, datreg text, mtch int, refch text, insreg text, lreg int, cptbanqdep text, refbordep text, "user" text)');
        DB::statement("INSERT INTO legacy_clients.reglement VALUES ('0001/03/20','2020-04-01',50000,'CHQ1','BOA',1,NULL,NULL,'AGENT')");

        // Un utilisateur hérité (pour tester --except=users)
        DB::statement('CREATE TABLE legacy_fsr."user" (nompre text, typuser text)');
        DB::statement("INSERT INTO legacy_fsr.\"user\" VALUES ('VIEUX UTILISATEUR','comptable')");
    }

    private function runMigration(array $opts = []): int
    {
        return Artisan::call('legacy:migrate', array_merge([
            '--clients-schema' => 'legacy_clients',
            '--fsr-schema' => 'legacy_fsr',
        ], $opts));
    }

    public function test_except_users_migre_tout_sauf_les_utilisateurs(): void
    {
        $this->setUpStaging();

        $this->assertSame(0, $this->runMigration(['--except' => 'users']));

        // Les données métier sont bien importées…
        $this->assertSame(1, FactureFournisseur::count());
        $this->assertSame(2, ReglementFournisseur::count());
        $this->assertSame(1, FactureClient::count());

        // …mais AUCUN utilisateur hérité n'est créé.
        $this->assertSame(0, \App\Models\User::where('email', 'like', '%@legacy.local')->count());

        // Sans --except, le même staging créerait l'utilisateur hérité (contrôle).
        $this->runMigration();
        $this->assertSame(1, \App\Models\User::where('email', 'like', '%@legacy.local')->count());
    }

    public function test_relancer_la_migration_ne_cree_pas_de_doublons(): void
    {
        $this->setUpStaging();

        // 1er passage
        $this->assertSame(0, $this->runMigration(), 'Le 1er passage doit réussir');

        $snapshot = [
            'factures_fournisseurs' => FactureFournisseur::count(),
            'reglements_fournisseurs' => ReglementFournisseur::count(),
            'factures_clients' => FactureClient::count(),
            'reglements_clients' => ReglementClient::count(),
        ];

        // Données fidèlement importées
        $this->assertSame(1, $snapshot['factures_fournisseurs']);
        $this->assertSame(2, $snapshot['reglements_fournisseurs'], 'Les 2 lignes de règlement distinctes doivent être conservées');
        $this->assertSame(1, $snapshot['factures_clients']);
        $this->assertSame(1, $snapshot['reglements_clients']);

        // Facture fournisseur soldée (statu=true) → payee + date_solde
        $ff = FactureFournisseur::where('numero_piece', 'PC/T/0001')->first();
        $this->assertSame(FactureFournisseur::STATUT_PAYEE, $ff->statut);
        $this->assertNotNull($ff->date_solde);
        $this->assertSame('2020-03-01', $ff->date->format('Y-m-d'));

        // 2e passage (sur base DÉJÀ peuplée) — doit réussir et ne RIEN dupliquer
        $this->assertSame(0, $this->runMigration(), 'Le 2e passage (idempotent) doit réussir');

        $this->assertSame($snapshot['factures_fournisseurs'], FactureFournisseur::count(), 'Aucun doublon facture fsr');
        $this->assertSame($snapshot['reglements_fournisseurs'], ReglementFournisseur::count(), 'Aucun doublon règlement fsr');
        $this->assertSame($snapshot['factures_clients'], FactureClient::count(), 'Aucun doublon facture client');
        $this->assertSame($snapshot['reglements_clients'], ReglementClient::count(), 'Aucun doublon règlement client');

        // numero_reglement reste unique (contrainte UNIQUE non violée)
        $this->assertSame(
            ReglementFournisseur::count(),
            ReglementFournisseur::distinct('numero_reglement')->count('numero_reglement'),
            'numero_reglement doit rester unique'
        );
    }
}
