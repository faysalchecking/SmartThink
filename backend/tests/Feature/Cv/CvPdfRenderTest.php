<?php

namespace Tests\Feature\Cv;

use App\Modules\Cv\Rendering\DompdfCvRenderer;
use Tests\TestCase;

class CvPdfRenderTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function layout(string $template = 'classic', mixed $summary = 'Hotel receptionist.'): array
    {
        return [
            'template_key' => $template,
            'status' => 'reviewed',
            'sections' => [
                ['key' => 'personal', 'title' => 'Personal details', 'visible' => true, 'missing' => [], 'items' => [
                    'full_name' => 'Ayesha Rahman',
                    'email' => 'ayesha@example.test',
                    'phone' => '+8801700000000',
                    'nationality' => 'Bangladeshi',
                    'date_of_birth' => '1995-04-12',
                ]],
                ['key' => 'summary', 'title' => 'Summary', 'visible' => true, 'missing' => [], 'items' => $summary],
                ['key' => 'experience', 'title' => 'Work experience', 'visible' => true, 'missing' => [], 'items' => [[
                    'title' => 'Receptionist', 'employer' => 'Hotel Example', 'country' => 'SA',
                    'start' => '2019-01', 'end' => '2023-12', 'description' => 'Front desk operations.',
                ]]],
                ['key' => 'education', 'title' => 'Education', 'visible' => true, 'missing' => [], 'items' => [[
                    'institution' => 'Dhaka College', 'qualification' => 'HSC', 'year' => '2014',
                ]]],
                ['key' => 'skills', 'title' => 'Skills', 'visible' => true, 'missing' => [], 'items' => ['Customer service', 'Reservations']],
                ['key' => 'languages', 'title' => 'Languages', 'visible' => true, 'missing' => [], 'items' => [[
                    'name' => 'English', 'level' => 'Good',
                ]]],
            ],
        ];
    }

    private function renderer(): DompdfCvRenderer
    {
        return new DompdfCvRenderer;
    }

    public function test_each_template_renders_a_pdf(): void
    {
        foreach (['classic', 'modern', 'compact'] as $template) {
            $bytes = $this->renderer()->render($this->layout($template));

            $this->assertStringStartsWith('%PDF-', $bytes, $template);
            $this->assertStringContainsString('%%EOF', $bytes, $template);
            $this->assertGreaterThan(1000, strlen($bytes), $template);

            // Optional: write fixtures for visual review. Run with CV_WRITE_FIXTURES=1.
            if (getenv('CV_WRITE_FIXTURES') === '1') {
                $dir = storage_path('app/private/fixtures');
                if (! is_dir($dir)) {
                    mkdir($dir, 0700, true);
                }
                file_put_contents($dir.'/cv-'.$template.'.pdf', $bytes);
            }
        }
    }

    public function test_every_value_is_escaped_in_the_html(): void
    {
        $layout = $this->layout('classic', '<script>alert(1)</script> & "quotes"');
        $layout['sections'][0]['items']['full_name'] = '<img src=x onerror=alert(1)>';

        $html = $this->renderer()->toHtml($layout);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    public function test_sections_follow_the_order_and_hidden_sections_are_absent(): void
    {
        $layout = $this->layout();
        $layout['sections'][2]['visible'] = false; // experience hidden

        $html = $this->renderer()->toHtml($layout);

        $this->assertStringNotContainsString('Work experience', $html);
        $this->assertLessThan(strpos($html, 'Skills'), strpos($html, 'Education'));
        $this->assertLessThan(strpos($html, 'Languages'), strpos($html, 'Skills'));
    }

    public function test_missing_data_does_not_print_empty_headings(): void
    {
        $layout = $this->layout('compact', null);
        $layout['sections'][2]['items'] = [];   // no experience entries
        $layout['sections'][4]['items'] = [];   // no skills

        $html = $this->renderer()->toHtml($layout);

        $this->assertStringNotContainsString('Summary', $html);
        $this->assertStringNotContainsString('Work experience', $html);
        $this->assertStringNotContainsString('Skills', $html);
        $this->assertStringContainsString('Ayesha Rahman', $html);
    }

    public function test_long_text_renders_without_error(): void
    {
        $long = str_repeat('Responsible for front desk operations and guest relations. ', 60);

        $bytes = $this->renderer()->render($this->layout('modern', $long));

        $this->assertStringStartsWith('%PDF-', $bytes);
    }

    public function test_remote_resources_are_never_referenced(): void
    {
        $html = $this->renderer()->toHtml($this->layout());

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('http://', $html);
        $this->assertStringNotContainsString('https://', $html);
    }
}
