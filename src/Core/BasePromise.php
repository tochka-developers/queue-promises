<?php

namespace Tochka\Promises\Core;

use Carbon\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\Support\ConditionTransitions;
use Tochka\Promises\Core\Support\States;
use Tochka\Promises\Core\Support\Time;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;

/**
 * @api
 */
class BasePromise implements StatesContract, ConditionTransitionsContract
{
    use ConditionTransitions;
    use SerializesModels;
    use States;
    use Time;

    private ?int $id = null;
    private ?int $parentJobId;
    private PromiseHandler $promiseHandler;
    private Promise $model;
    private Carbon $watchAt;
    private Carbon $timeoutAt;

    public function __construct(PromiseHandler $promiseHandler)
    {
        $this->promiseHandler = $promiseHandler;
        $this->parentJobId = $promiseHandler->getBaseJobId();
        $this->state = StateEnum::WAITING();
        $this->model = new Promise();
        $this->watchAt = Carbon::now()->addSeconds(watcher_watch_timeout());
        $this->timeoutAt = Carbon::now()->addSeconds(Config::get('promises.global_promise_timeout', 432000));
        $this->created_at = Carbon::now();
        $this->updated_at = Carbon::now();
    }

    public function getPromiseHandler(): PromiseHandler
    {
        return $this->promiseHandler;
    }

    public function setPromiseHandler(PromiseHandler $promiseHandler): void
    {
        $this->promiseHandler = $promiseHandler;
    }

    /**
     * @psalm-ignore-nullable-return
     */
    public function getPromiseId(): ?int
    {
        return $this->id;
    }

    public function setPromiseId(int $id): void
    {
        $this->id = $id;
    }

    public function getParentJobId(): ?int
    {
        return $this->parentJobId;
    }

    public function setParentJobId(?int $id): void
    {
        $this->parentJobId = $id;
    }

    public function getAttachedModel(): Promise
    {
        return $this->model;
    }

    public function setAttachedModel(Promise $model): void
    {
        $this->model = $model;
    }

    public function getWatchAt(): Carbon
    {
        return $this->watchAt;
    }

    public function setWatchAt(Carbon $watchAt): void
    {
        $this->watchAt = $watchAt;
    }

    public function getTimeoutAt(): Carbon
    {
        return $this->timeoutAt;
    }

    public function setTimeoutAt(Carbon $timeoutAt): void
    {
        $this->timeoutAt = $timeoutAt;
    }

    public function dispatch(): void
    {
        $this->setState(StateEnum::RUNNING());

        Promise::saveBasePromise($this);
    }
}
