<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_returns_ok_when_database_responds(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_health_returns_unavailable_without_internal_details(): void
    {
        $originalPort = config('database.connections.pgsql.port');

        try {
            // Point the connection at a closed port to simulate a database outage.
            config(['database.connections.pgsql.port' => '1']);
            DB::purge('pgsql');

            $response = $this->getJson('/api/v1/health');

            $response->assertStatus(503)->assertExactJson(['status' => 'unavailable']);
            $this->assertStringNotContainsStringIgnoringCase('connect', $response->getContent());
            $this->assertStringNotContainsString('127.0.0.1', $response->getContent());
        } finally {
            config(['database.connections.pgsql.port' => $originalPort]);
            DB::purge('pgsql');
        }
    }
}
