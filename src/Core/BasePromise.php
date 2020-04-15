<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\Support\ConditionTransitions;
use Tochka\Promises\Core\Support\States;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseRegistry;

/**
 * Class BasePromise
 *
 * @package App\Promises\Package
 */
class BasePromise implements StatesContract, ConditionTransitionsContract
{
    use States, ConditionTransitions;

    /** @var \Tochka\Promises\Contracts\PromiseHandler */
    private $promiseHandler;
    /** @var int|null */
    private $id;

    public function __construct(PromiseHandler $promiseHandler)
    {
        $this->promiseHandler = $promiseHandler;
        $this->state = StateEnum::WAITING();
    }

    public function getPromiseHandler(): PromiseHandler
    {
        return $this->promiseHandler;
    }

    public function getPromiseId(): ?int
    {
        return $this->id;
    }

    public function setPromiseId(int $id): void
    {
        $this->id = $id;
    }

    public function dispatch(): void
    {
        $this->setState(StateEnum::RUNNING());

        PromiseRegistry::save($this);
    }
}
