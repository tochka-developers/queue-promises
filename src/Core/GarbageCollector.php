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

    /**
     * @param null|callable(): bool $shouldQuitCallback
     * @param null|callable(): bool $shouldPausedCallback
     * @return void
     */
    public function handle(?callable $shouldQuitCallback = null, ?callable $shouldPausedCallback = null): void
    {
        $this->daemon(function () use ($shouldQuitCallback, $shouldPausedCallback) {
            $this->clean($shouldQuitCallback, $shouldPausedCallback);
        }, $shouldQuitCallback, $shouldPausedCallback);
    }

    /**
     * @param null|callable(): bool $shouldQuitCallback
     * @param null|callable(): bool $shouldPausedCallback
     * @return void
     */
    public function clean(?callable $shouldQuitCallback = null, ?callable $shouldPausedCallback = null): void
    {
        if ($shouldQuitCallback === null) {
            $shouldQuitCallback = fn () => false;
        }

        if ($shouldPausedCallback === null) {
            $shouldPausedCallback = fn () => false;
        }

        while (!$shouldQuitCallback() && !$shouldPausedCallback()) {
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

            $this->handlePromiseChunks($promises, $shouldQuitCallback, $shouldPausedCallback);

            $this->sleep(0.05);
        }
    }

    /**
     * @param array<int, int> $promiseIds
     * @param callable(): bool $shouldQuitCallback
     * @param callable(): bool $shouldPausedCallback
     * @return bool
     */
    private function handlePromiseChunks(array $promiseIds, callable $shouldQuitCallback, callable $shouldPausedCallback): bool
    {
        if ($shouldQuitCallback() || $shouldPausedCallback()) {
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
        $jobsIds = $jobs->pluck('id')->all();

        DB::table($this->promiseEventsTable)->whereIn('job_id', $jobsIds)->delete();
        DB::table($this->promiseJobsTable)->whereIn('id', $jobsIds)->delete();

        return true;
    }

    private function promiseColumn(string $columnName): string
    {
        return $this->promisesTable . '.' . $columnName;
    }

    private function promiseJobsColumn(string $columnName): string
    {
        return $this->promiseJobsTable . '.' . $columnName;
    }
}
