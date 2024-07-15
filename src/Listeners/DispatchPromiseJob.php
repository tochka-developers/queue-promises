<?php

namespace Tochka\Promises\Listeners;

use Tochka\Promises\Core\Support\BaseJobDispatcherInterface;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Listeners\Support\FilterTransitionsTrait;

/**
 * @api
 */
class DispatchPromiseJob
{
    use FilterTransitionsTrait;

    public array $transitions = [
        'dispatchJob' => [
            'from' => [
                StateEnum::WAITING,
            ],
            'to' => [
                StateEnum::RUNNING,
            ],
        ],
    ];

    public function __construct(
        private readonly BaseJobDispatcherInterface $baseJobDispatcher,
    ) {}

    public function dispatchJob(PromiseJobStateChanged $event): void
    {
        $this->baseJobDispatcher->dispatch($event->getPromiseJob()->getInitialJob());
    }
}
