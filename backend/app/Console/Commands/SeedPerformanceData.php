<?php

namespace App\Console\Commands;

use App\Modules\Recruitment\Services\PipelineStageSeeder;
use App\Support\LocalGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates fake companies, users, jobs, candidates and applications for timing tests.
 * Runs only in local development on smartthink_dev. It writes through the migrator connection,
 * which owns the tables, because this is seed data and not application traffic.
 */
final class SeedPerformanceData extends Command
{
    protected $signature = 'smartthink:seed-performance
        {--companies=3 : Number of test companies to create}
        {--candidates=10000 : Candidates per company}
        {--jobs=50 : Job orders per company}
        {--applications=5000 : Applications per company}';

    protected $description = 'Creates fake test data for performance measurement (local development only)';

    private const CHUNK = 1000;

    private const FIRST_NAMES = ['Karim', 'Rahim', 'Ayesha', 'Fatema', 'Nusrat', 'Sabbir', 'Tanvir', 'Mim', 'Rafiq', 'Sultana'];

    private const LAST_NAMES = ['Hossain', 'Uddin', 'Rahman', 'Akter', 'Islam', 'Khatun', 'Ahmed', 'Begum'];

    public function handle(): int
    {
        if (! LocalGuard::allows()) {
            $this->error('Refused: this command runs only in local development on smartthink_dev.');

            return self::FAILURE;
        }

        $companies = (int) $this->option('companies');
        $candidates = (int) $this->option('candidates');
        $jobs = (int) $this->option('jobs');
        $applications = (int) $this->option('applications');

        $label = 'perf-'.Str::lower(Str::random(6));

        for ($n = 1; $n <= $companies; $n++) {
            $this->info("Company {$n} of {$companies}...");
            $this->seedCompany($label, $n, $candidates, $jobs, $applications);
        }

        $this->info('Done. Label: '.$label);

        return self::SUCCESS;
    }

    private function seedCompany(string $label, int $n, int $candidates, int $jobs, int $applications): void
    {
        $db = DB::connection('pgsql_migrator');
        $now = now()->toDateTimeString();
        $companyId = (string) Str::uuid7();

        $db->table('companies')->insert([
            'id' => $companyId,
            'name' => "Perf Company {$n} ({$label})",
            'slug' => "{$label}-{$n}",
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $adminId = $db->table('users')->insertGetId([
            'name' => "Perf Admin {$n}",
            'email' => "{$label}-admin-{$n}@example.test",
            'password' => Hash::make(Str::random(40)),
            'is_platform_admin' => false,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        app(PipelineStageSeeder::class)->seed($companyId);

        $stageIds = $db->table('pipeline_stages')->where('company_id', $companyId)->pluck('id')->all();

        $clientIds = [];
        for ($i = 1; $i <= 10; $i++) {
            $clientId = (string) Str::uuid7();
            $clientIds[] = $clientId;
            $db->table('clients')->insert([
                'id' => $clientId,
                'company_id' => $companyId,
                'name' => "Perf Client {$i}",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $jobIds = [];
        for ($i = 1; $i <= $jobs; $i++) {
            $jobId = (string) Str::uuid7();
            $jobIds[] = $jobId;
            $db->table('job_orders')->insert([
                'id' => $jobId,
                'company_id' => $companyId,
                'client_id' => $clientIds[array_rand($clientIds)],
                'title' => "Perf Job {$i}",
                'status' => 'open',
                'openings' => 1,
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $candidateIds = [];
        for ($offset = 0; $offset < $candidates; $offset += self::CHUNK) {
            $rows = [];
            for ($i = $offset; $i < min($offset + self::CHUNK, $candidates); $i++) {
                $id = (string) Str::uuid7();
                $candidateIds[] = $id;
                $first = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
                $last = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
                $rows[] = [
                    'id' => $id,
                    'company_id' => $companyId,
                    'full_name' => "{$first} {$last} {$i}",
                    'email' => "seed-{$i}-".bin2hex(random_bytes(3)).'@example.test',
                    'created_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $db->table('candidates')->insert($rows);
        }

        // Unique (candidate, job) pairs, as the database requires.
        $pairs = [];
        $target = min($applications, count($candidateIds) * count($jobIds));
        while (count($pairs) < $target) {
            $candidate = $candidateIds[array_rand($candidateIds)];
            $job = $jobIds[array_rand($jobIds)];
            $pairs[$candidate.'|'.$job] = [$candidate, $job];
        }

        $rows = [];
        foreach ($pairs as [$candidate, $job]) {
            $rows[] = [
                'id' => (string) Str::uuid7(),
                'company_id' => $companyId,
                'candidate_id' => $candidate,
                'job_order_id' => $job,
                'stage_id' => $stageIds[array_rand($stageIds)],
                'status' => 'active',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) === self::CHUNK) {
                $db->table('candidate_applications')->insert($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            $db->table('candidate_applications')->insert($rows);
        }
    }
}
