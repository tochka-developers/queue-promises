<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Casts\ConditionsCast;
use Tochka\Promises\Models\Casts\SerializableClassCast;
use Tochka\Promises\Models\Factories\PromiseFactory;

/**
 * @api
 * @property int $id
 * @property int|null $parent_job_id
 * @property StateEnum $state
 * @property array<ConditionTransition> $conditions
 * @property PromiseHandler $promise_handler
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $watch_at
 * @property Carbon $timeout_at
 * @property Collection<int, PromiseJob> $jobs
 * @property Collection<int, PromiseEvent> $events
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Promise extends Model
{
    use HasFactory;

    protected $casts = [
        'state' => StateEnum::class,
        'conditions' => ConditionsCast::class,
        'promise_handler' => SerializableClassCast::class,
        'watch_at' => 'datetime',
        'timeout_at' => 'datetime',
    ];

    private ?BasePromise $basePromise = null;
    private ?StateEnum $changedState = null;
    private bool $nestedEvents = false;

    public function setNestedEvents(bool $nestedEvents): void
    {
        $this->nestedEvents = $nestedEvents;
    }

    public function isNestedEvents(): bool
    {
        return $this->nestedEvents;
    }

    public function getConnectionName(): ?string
    {
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        return Config::get('promises.database.connection', null);
    }

    public function getTable(): string
    {
        return Config::get('promises.database.table_promises', 'promises');
    }

    /**
     * @return HasMany
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(PromiseJob::class, 'promise_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(PromiseEvent::class, 'promise_id', 'id');
    }

    public function scopeInStates(Builder $query, array $states): Builder
    {
        return $query->whereIn('state', $states);
    }

    public function scopeForWatch(Builder $query): Builder
    {
        return $query->where(
            function (Builder $query) {
                $query->where('watch_at', '<=', Carbon::now())
                    ->orWhere('timeout_at', '<=', Carbon::now());
            },
        );
    }

    public function getBasePromise(): BasePromise
    {
        if ($this->basePromise === null) {
            $this->basePromise = new BasePromise($this->promise_handler);
        }

        $this->basePromise->setPromiseId($this->id);
        $this->basePromise->setState($this->state);
        $this->basePromise->setConditions($this->conditions);
        $this->basePromise->setPromiseHandler($this->promise_handler);
        $this->basePromise->setCreatedAt($this->created_at);
        $this->basePromise->setUpdatedAt($this->updated_at);
        $this->basePromise->setWatchAt($this->watch_at);
        $this->basePromise->setTimeoutAt($this->timeout_at);
        $this->basePromise->setParentJobId($this->parent_job_id);
        $this->basePromise->setAttachedModel($this);

        return $this->basePromise;
    }

    public static function saveBasePromise(BasePromise $basePromise): self
    {
        $model = $basePromise->getAttachedModel();

        $model->setChangedState($model->state);
        $model->state = $basePromise->getState();
        $model->conditions = $basePromise->getConditions();
        $model->promise_handler = clone $basePromise->getPromiseHandler();
        $model->watch_at = $basePromise->getWatchAt();
        $model->timeout_at = $basePromise->getTimeoutAt();
        $model->parent_job_id = $basePromise->getParentJobId();

        $model->save();

        $basePromise->setPromiseId($model->id);
        $basePromise->setCreatedAt($model->created_at);
        $basePromise->setUpdatedAt($model->updated_at);
        $basePromise->setAttachedModel($model);

        return $model;
    }

    protected static function newFactory(): PromiseFactory
    {
        return new PromiseFactory();
    }

    public function getChangedState(): ?StateEnum
    {
        return $this->changedState;
    }

    public function setChangedState(?StateEnum $state): void
    {
        $this->changedState = $state;
    }
}
