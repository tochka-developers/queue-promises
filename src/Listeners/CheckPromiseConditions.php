<?php

namespace Tochka\Promises\Listeners;

use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Listeners\Support\ConditionTransitionsTrait;
use Tochka\Promises\Models\Promise;

class CheckPromiseConditions
{
    use ConditionTransitionsTrait;

    public function handle(PromiseJobStateChanged $event): void
    {
        $promise = $event->getPromiseJob()->getAttachedModel()->promise;
        if ($promise === null) {
            return;
        }


        $basePromise = $promise->getBasePromise();

        $conditions = $this->getConditionsForState($basePromise, $basePromise);
        $transition = $this->getTransitionForConditions($conditions, $basePromise);
        if ($transition) {
            $basePromise->setState($transition->getToState());
            Promise::saveBasePromise($basePromise);
        }
    }
}
