<?php

namespace Tochka\Promises\Models;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Casts\ConditionsCast;
use Tochka\Promises\Models\Casts\SerializableClassCast;

/**
 * @property int                       $id
 * @property int                       $promise_id
 * @property StateEnum                 $state
 * @property array                     $conditions
 * @property MayPromised|PromisedEvent $initial_job
 * @property MayPromised|PromisedEvent $result_job
 * @property \Exception                $exception
 * @property \Carbon\Carbon            $created_at
 * @property \Carbon\Carbon            $updated_at
 * @property Promise                   $promise
 * @method static Builder byPromise(int $promiseId)
 * @method static self|null find(int $id)
 * @mixin Builder
 */
class PromiseJob extends Model
{
    protected $casts = [
        'promise_id'  => 'int',
        'state'       => StateEnum::class,
        'conditions'  => ConditionsCast::class,
        'initial_job' => SerializableClassCast::class,
        'result_job'  => SerializableClassCast::class,
        'exception'   => SerializableClassCast::class,
    ];

    /** @var BaseJob|null */
    private $baseJob = null;

    public function getConnectionName()
    {
        return Config::get('promises.database.connection', null);
    }

    public function getTable()
    {
        return Config::get('promises.database.table_jobs', 'promise_jobs');
    }

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

            $this->baseJob->setJobId($this->id);
            $this->baseJob->setConditions($this->conditions);
            $this->baseJob->setState($this->state);
            $this->baseJob->setException($this->exception);
            $this->baseJob->setCreatedAt($this->created_at);
            $this->baseJob->setUpdatedAt($this->updated_at);
        }

        return $this->baseJob;
    }

    public static function saveBaseJob(BaseJob $baseJob): void
    {
        $model = $baseJob->getAttachedModel();

        if ($model === null) {
            $model = new self();
        }

        $model->promise_id = $baseJob->getPromiseId();
        $model->state = $baseJob->getState();
        $model->conditions = $baseJob->getConditions();
        $model->initial_job = $model->clearJobs(clone $baseJob->getInitialJob());
        $model->result_job = $model->clearJobs(clone $baseJob->getResultJob());
        $model->exception = $baseJob->getException();

        $model->save();

        $baseJob->setJobId($model->id);
        $baseJob->setAttachedModel($model);
    }

    private function clearJobs(MayPromised $job): MayPromised
    {
        try {
            $property = (new \ReflectionClass($job))->getProperty('job');
        } catch (\Exception $e) {
            return $job;
        }

        $property->setAccessible(true);
        $internalJob = $property->getValue($job);
        if ($internalJob instanceof Job) {
            $property->setValue($job, null);
        }

        return $job;
    }
}
