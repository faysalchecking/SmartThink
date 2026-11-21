<?php

namespace App\Modules\Cv\Rendering;

use RuntimeException;

/**
 * Used until a renderer is chosen. It refuses to produce output, so no unverified PDF can leave the system.
 */
final class UnconfiguredPdfRenderer implements CvPdfRenderer
{
    public function render(array $layout): string
    {
        throw new RuntimeException('No CV PDF renderer is configured yet (ADR-0005).');
    }
}
