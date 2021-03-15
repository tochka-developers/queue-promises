<?php

namespace Tochka\Promises\Listeners;

use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Facades\BaseJobDispatcher;
use Tochka\Promises\Listeners\Support\FilterTransitions;

class DispatchPromiseJob
{
    use FilterTransitions;

    public array $transitions = [
        'dispatchJob' => [
            'from' => [
                StateEnum::WAITING,
            ],
            'to'   => [
                StateEnum::RUNNING,
            ],
        ],
    ];

    public function dispatchJob(PromiseJobStateChanged $event): void
    {
        BaseJobDispatcher::dispatch($event->getPromiseJob()->getInitialJob());
    }
}
