<?php

namespace App\Modules\Cv\Rendering;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

/**
 * Renders a CV layout to PDF with dompdf (ADR-0005, D-46).
 * Remote fetches and PHP execution are disabled, so CV content cannot make the server call out.
 */
final class DompdfCvRenderer implements CvPdfRenderer
{
    private const BASE_CSS = '
        @page { margin: 18mm 16mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.4; }
        h1 { font-size: 20pt; margin: 0 0 4pt 0; }
        h2 { font-size: 12pt; margin: 14pt 0 4pt 0; padding-bottom: 2pt; page-break-after: avoid; }
        .contact { color: #4b5563; margin: 0 0 6pt 0; }
        .entry { margin: 0 0 6pt 0; page-break-inside: avoid; }
        .meta { color: #4b5563; }
        p { margin: 0 0 4pt 0; }
    ';

    private const TEMPLATE_CSS = [
        'classic' => 'h2 { color: #111827; border-bottom: 1pt solid #9ca3af; } h1 { font-family: serif; }',
        'modern' => 'h1, h2 { color: #0f766e; } h2 { border-bottom: 2pt solid #0f766e; }',
        'compact' => 'body { font-size: 9pt; } h2 { font-size: 10.5pt; margin-top: 8pt; } h1 { font-size: 17pt; }',
    ];

    public function render(array $layout): string
    {
        $workDir = storage_path('app/private/dompdf');

        if (! is_dir($workDir) && ! mkdir($workDir, 0700, true) && ! is_dir($workDir)) {
            throw new RuntimeException('Could not create the renderer working directory.');
        }

        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setChroot(storage_path('app/private'));
        $options->setTempDir($workDir);
        $options->setFontCache($workDir);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->toHtml($layout), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Builds the HTML. Every value is escaped. Empty sections are skipped, so missing data
     * never prints an empty heading.
     *
     * @param  array<string, mixed>  $layout
     */
    public function toHtml(array $layout): string
    {
        $templateKey = (string) ($layout['template_key'] ?? 'classic');
        $templateCss = self::TEMPLATE_CSS[$templateKey] ?? self::TEMPLATE_CSS['classic'];

        $body = '';
        $sections = is_array($layout['sections'] ?? null) ? $layout['sections'] : [];

        foreach ($sections as $section) {
            if (! is_array($section) || ! ($section['visible'] ?? false)) {
                continue;
            }

            $body .= $this->section((string) ($section['key'] ?? ''), $section['items'] ?? null);
        }

        return '<!doctype html><html><head><meta charset="UTF-8"><style>'
            .self::BASE_CSS.$templateCss
            .'</style></head><body>'.$body.'</body></html>';
    }

    private function section(string $key, mixed $items): string
    {
        return match ($key) {
            'personal' => $this->personal(is_array($items) ? $items : []),
            'summary' => is_string($items) && trim($items) !== ''
                ? $this->block('Summary', '<p>'.$this->e($items).'</p>')
                : '',
            'experience' => $this->rows('Work experience', $items, ['title', 'employer', 'country', 'start', 'end', 'description']),
            'education' => $this->rows('Education', $items, ['institution', 'qualification', 'year']),
            'skills' => $this->skills($items),
            'languages' => $this->rows('Languages', $items, ['name', 'level']),
            default => '',
        };
    }

    /**
     * @param  array<array-key, mixed>  $p
     */
    private function personal(array $p): string
    {
        $name = $this->e($p['full_name'] ?? '');
        $contact = array_filter([
            $p['email'] ?? null,
            $p['phone'] ?? null,
            $p['nationality'] ?? null,
            $p['date_of_birth'] ?? null,
        ], fn ($value): bool => is_string($value) && trim($value) !== '');

        return '<h1>'.$name.'</h1><p class="contact">'.$this->e(implode('  |  ', $contact)).'</p>';
    }

    /**
     * @param  list<string>  $fields
     */
    private function rows(string $title, mixed $items, array $fields): string
    {
        if (! is_array($items) || $items === []) {
            return '';
        }

        $html = '';

        foreach ($items as $row) {
            if (! is_array($row) || blank($row[$fields[0]] ?? null)) {
                continue;
            }

            $meta = [];
            foreach (array_slice($fields, 1) as $field) {
                $value = $row[$field] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    $meta[] = $value;
                }
            }

            $html .= '<div class="entry"><strong>'.$this->e($row[$fields[0]]).'</strong>'
                .($meta !== [] ? '<div class="meta">'.$this->e(implode('  ·  ', $meta)).'</div>' : '')
                .'</div>';
        }

        return $html === '' ? '' : $this->block($title, $html);
    }

    private function skills(mixed $items): string
    {
        if (! is_array($items) || $items === []) {
            return '';
        }

        $names = array_filter(array_map(
            fn ($item): string => is_string($item) ? trim($item) : '',
            $items,
        ), fn (string $name): bool => $name !== '');

        return $names === [] ? '' : $this->block('Skills', '<p>'.$this->e(implode(', ', $names)).'</p>');
    }

    private function block(string $title, string $content): string
    {
        return '<section><h2>'.$this->e($title).'</h2>'.$content.'</section>';
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
