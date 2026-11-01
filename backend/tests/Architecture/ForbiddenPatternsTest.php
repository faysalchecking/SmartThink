<?php

namespace Tests\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Static guard for the tenant rules in TENANT_ISOLATION.md.
 * Any use of these patterns in application code fails the build.
 * If a reviewed exception is ever needed, it must be added here with a written reason.
 */
class ForbiddenPatternsTest extends TestCase
{
    /** @var list<string> */
    private const FORBIDDEN = ['withoutGlobalScope', 'withoutGlobalScopes', 'DB::unprepared'];

    public function test_no_forbidden_tenant_bypass_patterns_in_application_code(): void
    {
        $violations = [];
        $root = app_path();

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($root) + 1);
            $contents = (string) file_get_contents($file->getPathname());

            foreach (self::FORBIDDEN as $pattern) {
                if (str_contains($contents, $pattern)) {
                    $violations[] = $relative.' contains '.$pattern;
                }
            }
        }

        $this->assertSame([], $violations);
    }
}
