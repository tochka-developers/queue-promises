<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\Promises\Contracts\CustomConnection;
use Tochka\Promises\Contracts\CustomQueue;
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

    /**
     * @throws BindingResolutionException
     */
    public function dispatchPromise(PromiseStateChanged $event): void
    {
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
    }
}
