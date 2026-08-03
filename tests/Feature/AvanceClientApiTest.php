<?php

namespace Tests\Feature;

use Database\Factories\AvanceClientFactory;
use Database\Factories\ClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class AvanceClientApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'societe_emettrice_client_id' => ClientFactory::new()->create(['type_client' => 'societe'])->id,
            'beneficiaires' => ['Patient Jean', 'Patiente Jeanne'],
            'numero_cheque' => '7654321',
            'date_cheque' => '2026-05-01',
            'montant' => 300000,
        ], $overrides);
    }

    public function test_creation_refusee_sans_permission(): void
    {
        // Les avances utilisent les permissions règlements-clients
        $this->actingAsWithPermissions(['reglements-clients.voir']);
        $this->postJson('/api/avances-clients', $this->payload())->assertStatus(403);
    }

    public function test_creation(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);

        $emetteur = ClientFactory::new()->create(['type_client' => 'societe', 'nom' => 'NSIA Assurances']);
        $this->postJson('/api/avances-clients', $this->payload([
            'societe_emettrice_client_id' => $emetteur->id,
            'beneficiaires' => ['Patient non enregistré', 'Deuxième patient'],
        ]))
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('avances_clients', [
            'societe_emettrice_client_id' => $emetteur->id,
            'societe_emettrice' => 'NSIA Assurances',
            'client_id' => $emetteur->id,
            'statut' => 'disponible',
        ]);
        $avance = \App\Models\AvanceClient::latest('id')->firstOrFail();
        $this->assertSame(['Patient non enregistré', 'Deuxième patient'], $avance->beneficiaires_noms);
    }

    public function test_societe_emettrice_doit_etre_de_type_societe(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);

        $nonSociete = ClientFactory::new()->create(['type_client' => 'divers']);

        $this->postJson('/api/avances-clients', $this->payload([
            'societe_emettrice_client_id' => $nonSociete->id,
        ]))->assertStatus(422);
    }

    public function test_validation_champs_obligatoires(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);

        $this->postJson('/api/avances-clients', ['montant' => 1000])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['societe_emettrice_client_id', 'beneficiaires', 'numero_cheque', 'date_cheque']);
    }

    public function test_suppression(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.supprimer']);
        $avance = AvanceClientFactory::new()->create();

        $this->deleteJson("/api/avances-clients/{$avance->id}")->assertSuccessful();
    }
}
