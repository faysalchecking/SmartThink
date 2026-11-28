<?php

namespace Tests\Feature\Support;

use Tests\TestCase;

class LocalOnlyCommandsTest extends TestCase
{
    public function test_seed_command_refuses_outside_local_development(): void
    {
        $this->artisan('smartthink:seed-performance', ['--candidates' => 1])
            ->assertFailed();
    }

    public function test_perf_command_refuses_outside_local_development(): void
    {
        $this->artisan('smartthink:perf-check')
            ->assertFailed();
    }
}
