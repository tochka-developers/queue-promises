<?php

namespace Tochka\Promises\Core;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tochka\Promises\Core\Support\DaemonWorker;

class GarbageCollector
{
    use DaemonWorker;

    private int $deleteOlderThen;
    /** @var array<string> */
    private array $states;

    private string $promisesTable;
    private string $promiseJobsTable;
    private string $promiseEventsTable;
    private int $promiseChunkSize;
    private int $jobsChunkSize;

    public function __construct(
        int $sleepTime,
        int $deleteOlderThen,
        array $states,
        string $promisesTable,
        string $promiseJobsTable,
        string $promiseEventsTable,
        int $promiseChunkSize = 100,
        int $jobsChunkSize = 500,
    ) {
        $this->sleepTime = $sleepTime;
        $this->deleteOlderThen = $deleteOlderThen;
        $this->states = $states;

        $this->promisesTable = $promisesTable;
        $this->promiseJobsTable = $promiseJobsTable;
        $this->promiseEventsTable = $promiseEventsTable;

        $this->promiseChunkSize = $promiseChunkSize;
        $this->jobsChunkSize = $jobsChunkSize;

        $this->lastIteration = Carbon::minValue();
    }

    public function handle(): void
    {
        $this->daemon(function () {
            $this->clean();
        });
    }

    public function clean(): void
    {
        while (true) {
            $promises = DB::table($this->promisesTable)
                ->select([$this->promiseColumn('id')])
                ->leftJoin(
                    $this->promiseJobsTable,
                    $this->promiseJobsColumn('id'),
                    '=',
                    $this->promiseColumn('parent_job_id')
                )
                ->whereIn($this->promiseColumn('state'), $this->states)
                ->where($this->promiseColumn('updated_at'), '<', Carbon::now()->subSeconds($this->deleteOlderThen))
                ->whereNull($this->promiseJobsColumn('id'))
                ->limit($this->promiseChunkSize)
                ->pluck('id')
                ->all();

            if (empty($promises)) {
                return;
            }

            $this->handlePromiseChunks($promises);

            $this->sleep(0.05);
        }
    }

    private function promiseColumn(string $columnName): string
    {
        return $this->promisesTable . '.' . $columnName;
    }

    private function promiseJobsColumn(string $columnName): string
    {
        return $this->promiseJobsTable . '.' . $columnName;
    }

    /**
     * @param array<int, int> $promiseIds
     * @return bool
     */
    private function handlePromiseChunks(array $promiseIds): bool
    {
        if ($this->paused() || $this->shouldQuit()) {
            return false;
        }

        DB::table($this->promiseJobsTable)
            ->select(['id'])
            ->whereIn('promise_id', $promiseIds)
            ->chunkById(
                $this->jobsChunkSize,
                $this->handleJobsChunks(...)
            );

        DB::table($this->promisesTable)->whereIn('id', $promiseIds)->delete();

        return true;
    }

    /**
     * @param Collection<int, object{id: string}> $jobs
     * @return bool
     */
    private function handleJobsChunks(Collection $jobs): bool
    {
        if ($this->paused() || $this->shouldQuit()) {
            return false;
        }

        $jobsIds = $jobs->pluck('id')->all();

        DB::table($this->promiseEventsTable)->whereIn('job_id', $jobsIds)->delete();
        DB::table($this->promiseJobsTable)->whereIn('id', $jobsIds)->delete();

        return true;
    }
}
