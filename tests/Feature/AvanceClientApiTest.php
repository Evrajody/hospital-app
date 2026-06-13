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
            'client_id' => ClientFactory::new()->create()->id,
            'societe_emettrice' => 'Société Émettrice SARL',
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

        $this->postJson('/api/avances-clients', $this->payload())
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('avances_clients', [
            'societe_emettrice' => 'Société Émettrice SARL',
            'statut' => 'disponible',
        ]);
    }

    public function test_validation_champs_obligatoires(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.creer']);

        $this->postJson('/api/avances-clients', ['montant' => 1000])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['client_id', 'societe_emettrice', 'numero_cheque', 'date_cheque']);
    }

    public function test_suppression(): void
    {
        $this->actingAsWithPermissions(['reglements-clients.supprimer']);
        $avance = AvanceClientFactory::new()->create();

        $this->deleteJson("/api/avances-clients/{$avance->id}")->assertSuccessful();
    }
}
