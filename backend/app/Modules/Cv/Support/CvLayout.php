<?php

namespace App\Modules\Cv\Support;

use App\Modules\Cv\Models\CvDraft;

/**
 * Builds the ordered, visible sections of a draft, and lists what is missing.
 * The PDF renderer in Phase 4C uses the same output, so the preview and the PDF agree.
 */
final class CvLayout
{
    private const TITLES = [
        'personal' => 'Personal details',
        'summary' => 'Summary',
        'experience' => 'Work experience',
        'education' => 'Education',
        'skills' => 'Skills',
        'languages' => 'Languages',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function build(CvDraft $draft): array
    {
        $sections = [];

        foreach ($draft->section_order as $key) {
            $visible = $key === 'personal' || (bool) ($draft->section_visibility[$key] ?? true);

            $sections[] = [
                'key' => $key,
                'title' => self::TITLES[$key],
                'visible' => $visible,
                'missing' => self::missing($key, $draft->content),
                'items' => $draft->content[$key] ?? null,
            ];
        }

        return [
            'template_key' => $draft->template_key,
            'status' => $draft->status,
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return list<string>
     */
    private static function missing(string $key, array $content): array
    {
        if ($key === 'personal') {
            $personal = is_array($content['personal'] ?? null) ? $content['personal'] : [];
            $missing = [];

            foreach (['full_name', 'email', 'phone', 'nationality', 'date_of_birth'] as $field) {
                if (blank($personal[$field] ?? null)) {
                    $missing[] = $field;
                }
            }

            return $missing;
        }

        if ($key === 'summary') {
            return blank($content['summary'] ?? null) ? ['summary'] : [];
        }

        $entries = $content[$key] ?? [];

        return is_array($entries) && $entries !== [] ? [] : ['entries'];
    }
}
