<?php

namespace Tests\Feature;

use Database\Factories\ClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsPermissions;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_creation_refusee_sans_permission(): void
    {
        $this->actingAsWithPermissions(['clients.voir']);
        $this->postJson('/api/clients', ['nom' => 'Client X', 'type_client' => 'societe'])
            ->assertStatus(403);
    }

    public function test_creation_autorisee(): void
    {
        $this->actingAsWithPermissions(['clients.creer']);
        $this->postJson('/api/clients', [
            'nom' => 'Client Test',
            'type_client' => 'societe',
            'telephone' => '0102030405',
        ])->assertSuccessful()->assertJson(['success' => true]);

        $this->assertDatabaseHas('clients', ['nom' => 'Client Test']);
    }

    public function test_type_client_invalide_rejete(): void
    {
        $this->actingAsWithPermissions(['clients.creer']);
        $this->postJson('/api/clients', ['nom' => 'Client', 'type_client' => 'inexistant'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type_client');
    }

    public function test_modification_et_suppression(): void
    {
        $this->actingAsWithPermissions(['clients.modifier', 'clients.supprimer']);
        $client = ClientFactory::new()->create();

        $this->putJson("/api/clients/{$client->id}", ['nom' => 'Renommé', 'type_client' => 'divers'])
            ->assertSuccessful();
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'nom' => 'Renommé']);

        $this->deleteJson("/api/clients/{$client->id}")->assertSuccessful();
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }
}
