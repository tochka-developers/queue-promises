<?php

namespace Tochka\Promises\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseJobStateChanging;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Models\Casts\ConditionsCast;
use Tochka\Promises\Models\Casts\SerializableClassCast;
use Tochka\Promises\Models\Factories\PromiseJobFactory;

/**
 * @property int $id
 * @property int $promise_id
 * @property StateEnum $state
 * @property array<ConditionTransition> $conditions
 * @property MayPromised $initial_job
 * @property MayPromised $result_job
 * @property \Exception|null $exception
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Promise|null $promise
 * @method static Builder byPromise(int $promiseId)
 * @method static self|null find(int $id)
 * @method static lockForUpdate()
 */
class PromiseJob extends Model
{
    use HasFactory;

    /** @var array<string,string> */
    protected $casts = [
        'promise_id' => 'int',
        'state' => StateEnum::class,
        'conditions' => ConditionsCast::class,
        'initial_job' => SerializableClassCast::class,
        'result_job' => SerializableClassCast::class,
        'exception' => SerializableClassCast::class,
    ];

    private ?BaseJob $baseJob = null;
    private ?StateEnum $changedState = null;
    private bool $nestedEvents = false;

    protected static function booted(): void
    {
        static::updating(
            function (PromiseJob $promiseJob) {
                if ($promiseJob->isDirty('state')) {
                    $oldState = $promiseJob->getOriginal('state');
                    $currentState = $promiseJob->state;
                    $promiseJob->setChangedState($oldState);

                    Event::dispatch(new StateChanging($promiseJob->getBaseJob(), $oldState, $currentState));
                    Event::dispatch(
                        new PromiseJobStateChanging(
                            $promiseJob->getBaseJob(),
                            $oldState,
                            $currentState,
                            $promiseJob->nestedEvents
                        )
                    );
                }
            }
        );

        static::updated(
            function (PromiseJob $promiseJob) {
                if ($promiseJob->wasChanged('state')) {
                    $oldState = $promiseJob->getChangedState();
                    $currentState = $promiseJob->state;

                    Event::dispatch(new StateChanged($promiseJob->getBaseJob(), $oldState, $currentState));
                    Event::dispatch(
                        new PromiseJobStateChanged(
                            $promiseJob->getBaseJob(),
                            $oldState,
                            $currentState,
                            $promiseJob->nestedEvents
                        )
                    );
                }
            }
        );
    }

    public function setNestedEvents(bool $nestedEvents): void
    {
        $this->nestedEvents = $nestedEvents;
    }

    public function getConnectionName(): ?string
    {
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        return Config::get('promises.database.connection', null);
    }

    public function getTable(): string
    {
        return Config::get('promises.database.table_jobs', 'promise_jobs');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function promise(): BelongsTo
    {
        return $this->belongsTo(Promise::class, 'promise_id', 'id');
    }

    public function scopeByPromise(Builder $query, int $promiseId): Builder
    {
        return $query->where('promise_id', $promiseId);
    }

    public function getBaseJob(): BaseJob
    {
        if ($this->baseJob === null) {
            $this->baseJob = new BaseJob($this->promise_id, $this->initial_job, $this->result_job);
        }

        $this->baseJob->setJobId($this->id);
        $this->baseJob->setConditions($this->conditions);
        $this->baseJob->setState($this->state);
        $this->baseJob->setException($this->exception);
        $this->baseJob->setCreatedAt($this->created_at);
        $this->baseJob->setUpdatedAt($this->updated_at);
        $this->baseJob->setInitial($this->initial_job);
        $this->baseJob->setResult($this->result_job);
        $this->baseJob->setAttachedModel($this);

        return $this->baseJob;
    }

    public static function saveBaseJob(BaseJob $baseJob): self
    {
        $model = $baseJob->getAttachedModel();

        $model->promise_id = $baseJob->getPromiseId();
        $model->state = $baseJob->getState();
        $model->conditions = $baseJob->getConditions();
        $model->initial_job = $model->clearJobs(clone $baseJob->getInitialJob());
        $model->result_job = $model->clearJobs(clone $baseJob->getResultJob());
        $model->exception = $baseJob->getException();

        $model->save();

        $baseJob->setJobId($model->id);
        $baseJob->setCreatedAt($model->created_at);
        $baseJob->setUpdatedAt($model->updated_at);
        $baseJob->setAttachedModel($model);

        return $model;
    }

    private function clearJobs(MayPromised $job): MayPromised
    {
        try {
            $property = (new \ReflectionClass($job))->getProperty('job');
        } catch (\Exception) {
            return $job;
        }

        $property->setAccessible(true);
        $internalJob = $property->getValue($job);
        if ($internalJob instanceof Job) {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $property->setValue($job, null);
        }

        return $job;
    }

    protected static function newFactory(): PromiseJobFactory
    {
        return new PromiseJobFactory();
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
