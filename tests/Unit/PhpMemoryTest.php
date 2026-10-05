<?php

namespace Tests\Unit;

use App\Support\PhpMemory;
use Tests\TestCase;

class PhpMemoryTest extends TestCase
{
    private string $limiteInitiale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiteInitiale = (string) ini_get('memory_limit');
    }

    protected function tearDown(): void
    {
        ini_set('memory_limit', $this->limiteInitiale);
        parent::tearDown();
    }

    public function test_releve_la_limite_quand_elle_est_trop_basse(): void
    {
        ini_set('memory_limit', '256M');

        PhpMemory::raiseTo('1024M');

        $this->assertSame('1024M', ini_get('memory_limit'));
    }

    public function test_n_abaisse_jamais_une_limite_plus_haute(): void
    {
        ini_set('memory_limit', '2048M');

        PhpMemory::raiseTo('1024M');

        $this->assertSame('2048M', ini_get('memory_limit'));
    }

    public function test_ne_touche_pas_a_une_memoire_illimitee(): void
    {
        // Cas de la production : memory_limit=-1 (docker/php/production-overrides.ini).
        ini_set('memory_limit', '-1');

        PhpMemory::raiseTo('2048M');

        $this->assertSame('-1', ini_get('memory_limit'));
    }

    public function test_conversion_des_suffixes_ini(): void
    {
        $this->assertSame(512 * 1024 * 1024, PhpMemory::enOctets('512M'));
        $this->assertSame(2 * 1024 * 1024 * 1024, PhpMemory::enOctets('2G'));
        $this->assertSame(1024, PhpMemory::enOctets('1K'));
        $this->assertSame(PHP_INT_MAX, PhpMemory::enOctets('-1'));
    }
}
