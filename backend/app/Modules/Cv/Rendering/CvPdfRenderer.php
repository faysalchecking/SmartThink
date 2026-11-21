<?php

namespace App\Modules\Cv\Rendering;

/**
 * Turns a CV layout into PDF bytes. The implementation is chosen in Phase 4C-2 (ADR-0005).
 *
 * @param  array<string, mixed>  $layout  output of CvLayout::build()
 */
interface CvPdfRenderer
{
    /**
     * @param  array<string, mixed>  $layout
     */
    public function render(array $layout): string;
}
