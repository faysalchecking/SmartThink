<?php

namespace App\Console\Commands;

use App\Modules\Recruitment\Models\Candidate;
use App\Modules\Recruitment\Models\CandidateApplication;
use App\Modules\Recruitment\Models\JobOrder;
use App\Modules\Recruitment\Services\DashboardService;
use App\Modules\Tenancy\Context\TenantScope;
use App\Support\LocalGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class MeasurePerformance extends Command
{
    protected $signature = 'smartthink:perf-check {company? : Company ID. Defaults to the newest seeded perf company}';

    protected $description = 'Times the main list, search, report and dashboard queries (local development only)';

    private const RUNS = 5;

    public function __construct(private readonly TenantScope $scope, private readonly DashboardService $dashboard)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! LocalGuard::allows()) {
            $this->error('Refused: this command runs only in local development on smartthink_dev.');

            return self::FAILURE;
        }

        // Only companies that actually received candidates, so partial runs are skipped.
        // Lookup uses the migrator connection, because RLS hides other companies from the app role.
        $owner = DB::connection('pgsql_migrator');

        $companyId = $this->argument('company') ?? $owner->table('companies')
            ->where('name', 'like', 'Perf Company%')
            ->whereExists(fn ($q) => $q->select(DB::raw('1'))
                ->from('candidates')
                ->whereColumn('candidates.company_id', 'companies.id'))
            ->orderByDesc('created_at')
            ->value('id');

        if ($companyId === null) {
            $this->error('No seeded perf company found. Run smartthink:seed-performance first.');

            return self::FAILURE;
        }

        $this->scope->run((string) $companyId, function () use ($companyId): void {
            $this->info('Company: '.$companyId);
            $this->info('Each figure is the median of '.self::RUNS.' runs.');

            $this->time('candidates list (page 1)', fn () => Candidate::query()
                ->whereNull('archived_at')
                ->orderBy('full_name')
                ->orderBy('id')
                ->paginate(25));

            $this->time('candidates search "karim"', fn () => $this->searchQuery()->get());

            $this->time('jobs list (page 1)', fn () => JobOrder::query()
                ->orderByDesc('created_at')
                ->orderBy('id')
                ->paginate(25));

            $this->time('pipeline report', fn () => CandidateApplication::query()
                ->where('status', 'active')
                ->groupBy('job_order_id', 'stage_id')
                ->selectRaw('job_order_id, stage_id, count(*) as total')
                ->get());

            $this->time('dashboard summary', fn () => $this->dashboard->summary((string) $companyId));

            $this->newLine();
            $this->info('Query plan for the search:');
            $query = $this->searchQuery();
            $plan = DB::select('EXPLAIN (ANALYZE, BUFFERS) '.$query->toSql(), $query->getBindings());
            foreach ($plan as $line) {
                $this->line('  '.$line->{'QUERY PLAN'});
            }
        });

        return self::SUCCESS;
    }

    private function searchQuery(): Builder
    {
        return Candidate::query()
            ->whereNull('archived_at')
            ->where(fn (Builder $inner) => $inner
                ->where('full_name', 'ilike', '%karim%')
                ->orWhere('email', 'ilike', '%karim%'))
            ->orderBy('full_name')
            ->orderBy('id')
            ->limit(25);
    }

    private function time(string $label, callable $query): void
    {
        $samples = [];
        for ($i = 0; $i < self::RUNS; $i++) {
            $start = hrtime(true);
            $query();
            $samples[] = (hrtime(true) - $start) / 1_000_000;
        }

        sort($samples);
        $median = $samples[intdiv(self::RUNS, 2)];

        $this->line(sprintf('  %-30s %8.1f ms', $label, $median));
    }
}
