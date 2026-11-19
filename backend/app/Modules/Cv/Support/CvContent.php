<?php

namespace App\Modules\Cv\Support;

use App\Modules\Cv\Models\CvDraft;
use App\Modules\Recruitment\Models\Candidate;

/**
 * Shape and rules of CV content. Nothing here adds facts that are not in the input.
 */
final class CvContent
{
    /**
     * Returns the full six-section structure. Missing values become null or empty lists.
     *
     * @param  array<array-key, mixed>  $content
     * @return array<string, mixed>
     */
    public static function normalize(array $content): array
    {
        $personal = is_array($content['personal'] ?? null) ? $content['personal'] : [];
        $skills = is_array($content['skills'] ?? null) ? $content['skills'] : [];

        $cleanSkills = [];
        foreach ($skills as $skill) {
            $value = self::text($skill);
            if ($value !== null) {
                $cleanSkills[] = $value;
            }
        }

        return [
            'personal' => [
                'full_name' => self::text($personal['full_name'] ?? null),
                'email' => self::text($personal['email'] ?? null),
                'phone' => self::text($personal['phone'] ?? null),
                'nationality' => self::text($personal['nationality'] ?? null),
                'date_of_birth' => self::text($personal['date_of_birth'] ?? null),
            ],
            'summary' => self::text($content['summary'] ?? null),
            'experience' => self::rows($content['experience'] ?? [], ['title', 'employer', 'country', 'start', 'end', 'description']),
            'education' => self::rows($content['education'] ?? [], ['institution', 'qualification', 'year']),
            'skills' => $cleanSkills,
            'languages' => self::rows($content['languages'] ?? [], ['name', 'level']),
        ];
    }

    /**
     * The first draft copies only the verified master fields. Passport data is never copied.
     *
     * @return array<string, mixed>
     */
    public static function seed(Candidate $candidate): array
    {
        return self::normalize([
            'personal' => [
                'full_name' => $candidate->full_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'nationality' => $candidate->nationality,
                'date_of_birth' => $candidate->date_of_birth?->toDateString(),
            ],
            'summary' => $candidate->summary,
        ]);
    }

    /**
     * A SHA-256 over the exact content, section order, visibility and template.
     * Key order does not matter, so the same content always gives the same fingerprint.
     *
     * @param  array<string, mixed>  $content
     * @param  list<string>  $order
     * @param  array<string, bool>  $visibility
     */
    public static function fingerprint(array $content, array $order, array $visibility, string $template): string
    {
        $payload = self::sortRecursive([
            'content' => $content,
            'section_order' => $order,
            'section_visibility' => $visibility,
            'template_key' => $template,
        ]);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public static function fingerprintOf(CvDraft $draft): string
    {
        return self::fingerprint($draft->content, $draft->section_order, $draft->section_visibility, $draft->template_key);
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private static function sortRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sortRecursive($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    /**
     * @param  list<string>  $fields
     * @return list<array<string, ?string>>
     */
    private static function rows(mixed $items, array $fields): array
    {
        if (! is_array($items)) {
            return [];
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];
            foreach ($fields as $field) {
                $row[$field] = self::text($item[$field] ?? null);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
