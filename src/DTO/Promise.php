<?php

namespace Tochka\Promises\DTO;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

class Promise implements StatesContract, ConditionTransitionsContract, Arrayable
{
    use CastConditions;
    use CastDateTime;
    use CastObject;
    use CastStateEnum;
    use FieldChanges;

    private int $id;
    private ?int $parent_job_id;
    private ?StateEnum $state;
    /** @var array<ConditionTransition>|null */
    private ?array $conditions;
    private ?PromiseHandler $promise_handler;
    private ?Carbon $created_at;
    private ?Carbon $updated_at;
    private ?Carbon $watch_at;
    private ?Carbon $timeout_at;

    /**
     * @throws \JsonException
     */
    public function __construct(object $value)
    {
        $this->id = $value->id;
        $this->parent_job_id = $value->parent_job_id ?? null;
        $this->state = $this->castToStateEnum($value->state ?? null);
        $this->promise_handler = $this->castToObject($value->promise_handler ?? null);
        $this->conditions = $this->castToConditions($value->conditions ?? null);
        $this->created_at = $this->castToDateTime($value->created_at ?? null);
        $this->updated_at = $this->castToDateTime($value->updated_at ?? null);
        $this->watch_at = $this->castToDateTime($value->watch_at ?? null);
        $this->timeout_at = $this->castToDateTime($value->timeout_at ?? null);
    }

    /**
     * @throws \JsonException
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_job_id' => $this->parent_job_id,
            'state' => $this->castFromStateEnum($this->state),
            'promise_handler' => $this->castFromObject($this->promise_handler),
            'conditions' => $this->castFromConditions($this->conditions),
            'created_at' => $this->castFromDateTime($this->created_at),
            'updated_at' => $this->castFromDateTime($this->updated_at),
            'watch_at' => $this->castFromDateTime($this->watch_at),
            'timeout_at' => $this->castFromDateTime($this->timeout_at),
        ];
    }

    /**
     * @throws \JsonException
     */
    public function getChanges(): array
    {
        return $this->getChangedValues($this->toArray());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentJobId(): ?int
    {
        return $this->parent_job_id;
    }

    public function setParentJobId(?int $parent_job_id): void
    {
        if ($this->parent_job_id !== $parent_job_id) {
            $this->fieldChange('parent_job_id');
        }

        $this->parent_job_id = $parent_job_id;
    }

    public function getState(): StateEnum
    {
        return $this->state;
    }

    public function setState(StateEnum $state): void
    {
        if ($state->isNot($this->state)) {
            $this->fieldChange('state');
        }

        $this->state = $state;
    }

    public function addCondition(ConditionTransition $conditionTransition): void
    {
        $this->fieldChange('conditions');

        $this->conditions[] = $conditionTransition;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getPromiseHandler(): ?PromiseHandler
    {
        return $this->promise_handler;
    }

    public function setPromiseHandler(?PromiseHandler $promise_handler): void
    {
        if ($this->promise_handler !== $promise_handler) {
            $this->fieldChange('promise_handler');
        }

        $this->promise_handler = $promise_handler;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->created_at;
    }

    public function setCreatedAt(?Carbon $created_at): void
    {
        if ($this->created_at !== $created_at) {
            $this->fieldChange('created_at');
        }

        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?Carbon $updated_at): void
    {
        if ($this->updated_at !== $updated_at) {
            $this->fieldChange('updated_at');
        }

        $this->updated_at = $updated_at;
    }

    public function getWatchAt(): ?Carbon
    {
        return $this->watch_at;
    }

    public function setWatchAt(?Carbon $watch_at): void
    {
        if ($this->watch_at !== $watch_at) {
            $this->fieldChange('watch_at');
        }

        $this->watch_at = $watch_at;
    }

    public function getTimeoutAt(): ?Carbon
    {
        return $this->timeout_at;
    }

    public function setTimeoutAt(?Carbon $timeout_at): void
    {
        if ($this->timeout_at !== $timeout_at) {
            $this->fieldChange('timeout_at');
        }

        $this->timeout_at = $timeout_at;
    }
}
