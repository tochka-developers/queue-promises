<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Support\Facades\Log;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Events\StateChanged;

/**
 * @api
 */
class LogStateChanged
{
    public function handle(StateChanged $event): void
    {
        $instance = $event->getInstance();

        $context = [
            'from_state' => $event->getFromState()->value,
            'to_state'   => $event->getToState()->value,
        ];

        switch (get_class($instance)) {
            case BasePromise::class:
                $message = sprintf(
                    'Promise [%s:%s] change state from [%s] to [%s]',
                    get_class($instance->getPromiseHandler()),
                    $instance->getPromiseId(),
                    $event->getFromState()->value,
                    $event->getToState()->value,
                );
                $context['promise_handler'] = get_class($instance->getPromiseHandler());
                $context['promise_id'] = $instance->getPromiseId();
                break;
            case BaseJob::class:
                $message = sprintf(
                    'Promised job [%s:%s] change state from [%s] to [%s]',
                    get_class($instance->getInitialJob()),
                    $instance->getJobId(),
                    $event->getFromState()->value,
                    $event->getToState()->value,
                );
                $context['promise_id'] = $instance->getPromiseId();
                $context['job_handler'] = get_class($instance->getInitialJob());
                $context['job_id'] = $instance->getJobId();
                break;
            default:
                $message = sprintf(
                    'Some instance change state from [%s] to [%s]',
                    $event->getFromState()->value,
                    $event->getToState()->value,
                );
                break;
        }

        Log::debug($message, $context);
    }
}
