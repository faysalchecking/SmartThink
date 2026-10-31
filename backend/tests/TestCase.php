<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected const TEST_DATABASE = 'smartthink_test';

    protected function setUp(): void
    {
        parent::setUp();

        // Safety guard: tests must never run against the development or production database.
        $database = config('database.connections.pgsql.database');
        if ($database !== self::TEST_DATABASE) {
            throw new RuntimeException(
                'Tests must run against '.self::TEST_DATABASE.' only. Current database: '.$database,
            );
        }
    }
}
