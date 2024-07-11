<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;

class ConditionTransition
{
    private ConditionContract $condition;
    private StateEnum $from_state;
    private StateEnum $to_state;

    public function __construct(ConditionContract $condition, StateEnum $from_state, StateEnum $to_state)
    {
        $this->condition = $condition;
        $this->from_state = $from_state;
        $this->to_state = $to_state;
    }

    public function getCondition(): ConditionContract
    {
        return $this->condition;
    }

    public function getFromState(): StateEnum
    {
        return $this->from_state;
    }

    public function getToState(): StateEnum
    {
        return $this->to_state;
    }

    public function toArray(): array
    {
        return [
            'condition'  => serialize($this->condition),
            'from_state' => $this->from_state->value,
            'to_state'   => $this->to_state->value,
        ];
    }

    /**
     * @throws IncorrectResolvingClass
     */
    public static function fromArray(array $value): self
    {
        if (
            !array_key_exists('condition', $value)
            || !array_key_exists('from_state', $value)
            || !array_key_exists('to_state', $value)
        ) {
            throw new IncorrectResolvingClass(
                'ConditionTransition array must contains required elements [condition,from_state,to_state]',
            );
        }

        $condition = unserialize($value['condition'], ['allowed_classes' => true]);
        if (!$condition instanceof ConditionContract) {
            throw new IncorrectResolvingClass(
                sprintf(
                    'Condition must implements contract [%s], but class [%s] is incorrect',
                    ConditionContract::class,
                    get_class($condition),
                ),
            );
        }

        $fromState = StateEnum::coerce($value['from_state']);
        $toState = StateEnum::coerce($value['to_state']);

        if ($fromState === null || $toState === null) {
            throw new IncorrectResolvingClass('ConditionTransition array must contains required elements [from_state,to_state]');
        }

        return new self($condition, $fromState, $toState);
    }
}
