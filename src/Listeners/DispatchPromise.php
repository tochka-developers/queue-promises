<?php

namespace Tochka\Promises\Listeners;

use Tochka\Promises\Core\Support\PromiseQueueJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Listeners\Support\FilterTransitionsTrait;

class DispatchPromise
{
    use FilterTransitionsTrait;

    public array $transitions = [
        'dispatchPromise' => [
            'from' => [
                StateEnum::WAITING,
                StateEnum::RUNNING,
            ],
            'to'   => [
                StateEnum::SUCCESS,
                StateEnum::FAILED,
                StateEnum::TIMEOUT,
            ],
        ],
    ];

    public function dispatchPromise(PromiseStateChanged $event): void
    {
        dispatch(
            new PromiseQueueJob(
                $event->getPromise()->getPromiseId(),
                $event->getPromise()->getPromiseHandler(),
                $event->getPromise()->getState()
            )
        );
    }
}
