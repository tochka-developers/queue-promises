<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Contracts\CustomConnection;
use Tochka\Promises\Contracts\CustomQueue;
use Tochka\Promises\Core\Support\PromiseQueueJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseHandlerDispatched;
use Tochka\Promises\Events\PromiseHandlerDispatching;
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

    /**
     * @throws BindingResolutionException
     */
    public function dispatchPromise(PromiseStateChanged $event): void
    {
        Event::dispatch(new PromiseHandlerDispatching($event->getPromise()));

        $promiseQueueJob = new PromiseQueueJob(
            $event->getPromise()->getPromiseId(),
            $event->getPromise()->getPromiseHandler(),
            $event->getPromise()->getState()
        );

        $promiseHandler = $event->getPromise()->getPromiseHandler();

        if ($promiseHandler instanceof CustomConnection) {
            $promiseQueueJob->onConnection($promiseHandler->getConnection());
        }

        if ($promiseHandler instanceof CustomQueue) {
            $promiseQueueJob->onQueue($promiseHandler->getQueue());
        }

        $dispatcher = Container::getInstance()->make(Dispatcher::class);
        $dispatcher->dispatch($promiseQueueJob);

        Event::dispatch(new PromiseHandlerDispatched($event->getPromise()));
    }
}
