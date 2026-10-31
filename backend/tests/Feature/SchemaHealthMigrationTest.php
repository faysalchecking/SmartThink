<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaHealthMigrationTest extends TestCase
{
    public function test_schema_health_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::connection('pgsql')->hasTable('schema_health'),
            'schema_health is missing in smartthink_test. Run the test migration step first.',
        );
    }
}
