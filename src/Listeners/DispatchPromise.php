<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Contracts\CustomConnection;
use Tochka\Promises\Contracts\CustomQueue;
use Tochka\Promises\Core\Support\PromiseQueueJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseHandlerDispatched;
use Tochka\Promises\Events\PromiseHandlerDispatching;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Listeners\Support\FilterTransitionsTrait;

/**
 * @api
 */
class DispatchPromise
{
    use FilterTransitionsTrait;

    public array $transitions = [
        'dispatchPromise' => [
            'from' => [
                StateEnum::WAITING,
                StateEnum::RUNNING,
            ],
            'to' => [
                StateEnum::SUCCESS,
                StateEnum::FAILED,
                StateEnum::TIMEOUT,
            ],
        ],
    ];

    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function dispatchPromise(PromiseStateChanged $event): void
    {
        Event::dispatch(new PromiseHandlerDispatching($event->getPromise()));

        $promiseQueueJob = new PromiseQueueJob(
            $event->getPromise()->getPromiseId(),
            $event->getPromise()->getPromiseHandler(),
            $event->getPromise()->getState(),
        );

        $promiseHandler = $event->getPromise()->getPromiseHandler();

        if ($promiseHandler instanceof CustomConnection) {
            $promiseQueueJob->onConnection($promiseHandler->getConnection());
        }

        if ($promiseHandler instanceof CustomQueue) {
            $promiseQueueJob->onQueue($promiseHandler->getQueue());
        }

        $this->dispatcher->dispatch($promiseQueueJob);

        Event::dispatch(new PromiseHandlerDispatched($event->getPromise()));
    }
}
