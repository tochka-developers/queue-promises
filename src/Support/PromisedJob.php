<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Core\Support\QueuePromiseMiddleware;
use Tochka\Promises\Facades\PromiseJobRegistry;

trait PromisedJob
{
    /** @var BaseJob */
    private $baseJob;

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
