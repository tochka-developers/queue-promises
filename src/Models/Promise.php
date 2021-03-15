<?php

namespace Tochka\Promises\Models;

use Illuminate\Database\Eloquent\Builder;
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

/**
 * @property int                            $id
 * @property StateEnum                      $state
 * @property array<ConditionTransition>     $conditions
 * @property PromiseHandler                 $promise_handler
 * @property \Carbon\Carbon                 $created_at
 * @property \Carbon\Carbon                 $updated_at
 * @property array<PromiseJob>|Collection   $jobs
 * @property array<PromiseEvent>|Collection $events
 * @method static Builder inStates(array $states)
 * @method static self|null find(int $id)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Promise extends Model
{
    protected $casts = [
        'state'           => StateEnum::class,
        'conditions'      => ConditionsCast::class,
        'promise_handler' => SerializableClassCast::class,
    ];

    /** @var BasePromise|null */
    private $basePromise = null;

    public function getConnectionName()
    {
        return Config::get('promises.database.connection', null);
    }

    public function getTable()
    {
        return Config::get('promises.database.table_promises', 'promises');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(PromiseJob::class, 'promise_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PromiseJob::class, 'promise_id', 'id');
    }

    public function scopeInStates(Builder $query, array $states): Builder
    {
        return $query->whereIn('state', $states);
    }

    public function getBasePromise(): BasePromise
    {
        if ($this->basePromise === null) {
            $this->basePromise = new BasePromise($this->promise_handler);

            $this->basePromise->setPromiseId($this->id);
            $this->basePromise->setState($this->state);
            $this->basePromise->setConditions($this->conditions);
            $this->basePromise->setCreatedAt($this->created_at);
            $this->basePromise->setUpdatedAt($this->updated_at);
        }

        return $this->basePromise;
    }

    public static function saveBasePromise(BasePromise $basePromise): void
    {
        $model = $basePromise->getAttachedModel();

        if ($model === null) {
            $model = new self();
        }

        $model->state = $basePromise->getState();
        $model->conditions = $basePromise->getConditions();
        $model->promise_handler = clone $basePromise->getPromiseHandler();

        $model->save();

        $basePromise->setPromiseId($model->id);
        $basePromise->setAttachedModel($model);
    }
}
