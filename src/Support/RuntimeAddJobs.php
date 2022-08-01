<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Facades\Promises;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

trait RuntimeAddJobs
{
    public function addNow(MayPromised $job): void
    {
        if (!$this instanceof PromiseHandler) {
            throw new \RuntimeException(
                sprintf(
                    'Trait [%s] may by used only in class, implements [%s]',
                    'RuntimeAddJobs',
                    PromiseHandler::class
                )
            );
        }
        /** @var PromiseHandler $this */

        $basePromiseId = $this->getPromiseId();
        $basePromise = Promise::find($basePromiseId);

        if ($basePromise === null) {
            throw new \RuntimeException('Not found base promise in database');
        }

        $baseJob = new BaseJob($basePromiseId, $job);
        PromiseJob::saveBaseJob($baseJob);

        $job->setBaseJobId($baseJob->getJobId());
        $baseJob->setInitial($job);

        Promises::hookTraitsMethod($this, 'jobConditions', $basePromise->getBasePromise(), $baseJob);

        PromiseJob::saveBaseJob($baseJob);
    }
}
