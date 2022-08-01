<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

trait PromisedJob
{
    use BaseJobId;

    public function getBaseJob(): ?BaseJob
    {
        $baseJobModel = PromiseJob::find($this->getBaseJobId());

        if ($baseJobModel === null) {
            return null;
        }

        return $baseJobModel->getBaseJob();
    }

    public function getBasePromise(): ?BasePromise
    {
        $baseJob = $this->getBaseJob();

        if ($baseJob === null) {
            return null;
        }

        $basePromiseModel = Promise::find($baseJob->getPromiseId());
        if ($basePromiseModel === null) {
            return null;
        }

        return $basePromiseModel->getBasePromise();
    }
}
