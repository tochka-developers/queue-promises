<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

trait PromisedJob
{
    use BaseJobId;

    public function failed(\Exception $exception): void
    {
        $baseJob = PromiseJobRegistry::load($this->base_job_id);
        $baseJob->setState(StateEnum::FAILED());
        $baseJob->setException($exception);

        PromiseJobRegistry::save($baseJob);
    }
}
