<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

trait PromisedJob
{
    use BaseJobId;

    public function failed(): void
    {
        $baseJob = PromiseJobRegistry::load($this->base_job_id);
        $baseJob->setState(StateEnum::FAILED());

        PromiseJobRegistry::save($baseJob);
    }
}
