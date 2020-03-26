<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\BaseJob;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Facades\PromiseJobRegistry;

trait PromisedJob
{
    /** @var BaseJob */
    private BaseJob $baseJob;

    public function setBaseJob(BaseJob $baseJob): void
    {
        $this->baseJob = $baseJob;
    }

    public function getBaseJob(): BaseJob
    {
        return $this->baseJob;
    }

    public function failed(): void
    {
        $this->baseJob->setState(States::FAILED);
        PromiseJobRegistry::save($this->baseJob);
    }

    public function middleware(): array
    {
        return [
            new QueuePromiseMiddleware($this->baseJob),
        ];
    }
}
