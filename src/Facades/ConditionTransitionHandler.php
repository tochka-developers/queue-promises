<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;

/**
 * @method static ConditionTransition[] getConditionsForState(StatesContract $state, ConditionTransitionsContract $conditionTransitions)
 * @method static ConditionTransition|null getTransitionForConditions(ConditionTransition[] $conditionTransitions, BasePromise $promise)
 * @method static bool checkConditionAndApplyTransition(StatesContract $statesInstance,ConditionTransitionsContract $conditionTransitionsInstance, BasePromise $basePromise)
 * @see \Tochka\Promises\Core\Support\ConditionTransitionHandler
 * @codeCoverageIgnore
 */
class ConditionTransitionHandler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
