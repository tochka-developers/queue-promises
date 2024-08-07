<?php

namespace Tochka\Promises\Listeners;

use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Listeners\Support\FilterTransitionsTrait;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Support\WaitEvent;

/**
 * @api
 */
class DeletePromisedEvent
{
    use FilterTransitionsTrait;

    public array $transitions = [
        'dispatchJob' => [
            'from' => [
                StateEnum::RUNNING,
            ],
            'to'   => [
                StateEnum::TIMEOUT,
                StateEnum::CANCELED,
                StateEnum::SUCCESS,
                StateEnum::FAILED,
            ],
        ],
    ];

    /**
     * @param PromiseJobStateChanged $event
     *
     * @throws \Exception
     */
    public function dispatchJob(PromiseJobStateChanged $event): void
    {
        $job = $event->getPromiseJob()->getInitialJob();
        if ($job instanceof WaitEvent) {
            $model = $job->getAttachedModel();
            if ($model !== null) {
                $model->delete();
            } else {
                PromiseEvent::where('id', $job->getId())->delete();
            }
        }
    }
}
