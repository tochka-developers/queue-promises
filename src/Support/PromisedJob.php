<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

/**
 * @api
 */
trait PromisedJob
{
    use BaseJobId;

    public function getBaseJob(): ?BaseJob
    {
        $baseJobId = $this->getBaseJobId();
        if ($baseJobId === null) {
            return null;
        }

        return PromiseJob::find($baseJobId)?->getBaseJob();
    }

    public function getBasePromise(): ?BasePromise
    {
        $baseJob = $this->getBaseJob();

        if ($baseJob === null) {
            return null;
        }

        $basePromiseModel = Promise::find($baseJob->getPromiseId());

        return $basePromiseModel?->getBasePromise();
    }
}
