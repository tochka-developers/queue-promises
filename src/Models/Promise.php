<?php
/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Events\PromiseStateChanging;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Models\Casts\ConditionsCast;
use Tochka\Promises\Models\Casts\SerializableClassCast;
use Tochka\Promises\Models\Factories\PromiseFactory;

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
    use HasFactory;

    /** @var array<string, string> */
    protected $casts = [
        'state'           => StateEnum::class,
        'conditions'      => ConditionsCast::class,
        'promise_handler' => SerializableClassCast::class,
    ];

    private ?BasePromise $basePromise = null;
    private ?StateEnum $changedState = null;

    protected static function booted(): void
    {
        static::updating(
            function (Promise $promise) {
                if ($promise->isDirty('state')) {
                    $oldState = $promise->getOriginal('state');
                    $currentState = $promise->state;
                    $promise->setChangedState($oldState);

                    Event::dispatch(new StateChanging($promise->getBasePromise(), $oldState, $currentState));
                    Event::dispatch(new PromiseStateChanging($promise->getBasePromise(), $oldState, $currentState));
                }
            }
        );

        static::updated(
            function (Promise $promise) {
                if ($promise->wasChanged('state')) {
                    $oldState = $promise->getChangedState();
                    $currentState = $promise->state;

                    Event::dispatch(new StateChanged($promise->getBasePromise(), $oldState, $currentState));
                    Event::dispatch(new PromiseStateChanged($promise->getBasePromise(), $oldState, $currentState));
                }
            }
        );
    }

    public function getConnectionName(): ?string
    {
        return Config::get('promises.database.connection', null);
    }

    public function getTable(): string
    {
        return Config::get('promises.database.table_promises', 'promises');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(PromiseJob::class, 'promise_id', 'id');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
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
            $this->basePromise->setAttachedModel($this);
        }

        return $this->basePromise;
    }

    public static function saveBasePromise(BasePromise $basePromise): void
    {
        $model = $basePromise->getAttachedModel();

        $model->state = $basePromise->getState();
        $model->conditions = $basePromise->getConditions();
        $model->promise_handler = clone $basePromise->getPromiseHandler();

        $model->save();

        $basePromise->setPromiseId($model->id);
        $basePromise->setAttachedModel($model);
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
