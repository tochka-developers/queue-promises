<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Support\Facades\App;
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

    public function dispatchJob(PromiseJobStateChanged $event): void
    {
        /** @var BaseJobDispatcherInterface $baseJobDispatcher */
        $baseJobDispatcher = App::make(BaseJobDispatcherInterface::class);
        $baseJobDispatcher->dispatch($event->getPromiseJob()->getInitialJob());
    }
}
