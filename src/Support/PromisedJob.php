<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Core\Support\QueuePromiseMiddleware;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

trait PromisedJob
{
    /** @var int */
    private $base_job_id;

    public function setBaseJobId(int $base_job_id): void
    {
        $this->base_job_id = $base_job_id;
    }

    public function getBaseJobId(): int
    {
        return $this->base_job_id;
    }

    public function failed(): void
    {
        $baseJob = PromiseJobRegistry::load($this->base_job_id);
        $baseJob->setState(StateEnum::FAILED());

        PromiseJobRegistry::save($baseJob);
    }

    public function middleware(): array
    {
        return [
            new QueuePromiseMiddleware($this->base_job_id),
        ];
    }
}
