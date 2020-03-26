<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\Condition;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;

class ConditionTransition
{
    /** @var \Tochka\Promises\Contracts\Condition */
    private $condition;
    /** @var string */
    private $from_state;
    /** @var string */
    private $to_state;

    public function __construct(Condition $condition, string $from_state, string $to_state)
    {
        $this->condition = $condition;
        $this->from_state = $from_state;
        $this->to_state = $to_state;
    }

    public function getCondition(): Condition
    {
        return $this->condition;
    }

    public function getFromState(): string
    {
        return $this->from_state;
    }

    public function getToState(): string
    {
        return $this->to_state;
    }

    public function toArray(): array
    {
        return [
            'condition'  => serialize($this->condition),
            'from_state' => $this->from_state,
            'to_state'   => $this->to_state,
        ];
    }

    /**
     * @param array $value
     *
     * @return static
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
                sprintf('ConditionTransition array must contains required elements [condition,from_state,to_state]')
            );
        }

        $condition = unserialize($value['condition'], ['allowed_classes' => true]);
        if (!$condition instanceof Condition) {
            throw new IncorrectResolvingClass(
                sprintf(
                    'Condition must implements contract [%s], but class [%s] is incorrect',
                    Condition::class,
                    get_class($condition)
                )
            );
        }

        return new self($condition, $value['from_state'], $value['to_state']);
    }
}