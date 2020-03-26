<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

class QueuePromiseMiddleware
{
    /** @var int */
    private $base_job_id;

    public function __construct(int $base_job_id)
    {
        $this->base_job_id = $base_job_id;
    }

    /**
     * @param MayPromised $job
     * @param             $next
     *
     * @throws \Exception
     */
    public function handle(MayPromised $job, $next): void
    {
        try {
            $baseJob = PromiseJobRegistry::load($this->base_job_id);
            $next($job);
        } catch (\Exception $e) {
            $baseJob->setResult($job);
            PromiseJobRegistry::save($baseJob);
            throw $e;
        }

        $baseJob->setResult($job);
        $baseJob->setState(StateEnum::SUCCESS());
        PromiseJobRegistry::save($baseJob);
    }
}
