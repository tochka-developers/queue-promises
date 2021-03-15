<?php

namespace Tochka\Promises\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Tochka\Promises\Support\WaitEvent;

/**
 * @property int            $id
 * @property int            $job_id
 * @property string         $event_name
 * @property string         $event_unique_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Promise        $promise
 * @method static Builder byJob(int $jobId)
 * @method static Builder byEvent(string $eventName, string $eventUniqueId)
 * @method static self|null find(int $id)
 * @mixin Builder
 */
class PromiseEvent extends Model
{
    protected $casts = [
        'job_id'          => 'int',
        'event_name'      => 'string',
        'event_unique_id' => 'string',
    ];

    /** @var WaitEvent|null */
    private $baseEvent = null;

    public function getConnectionName()
    {
        return Config::get('promises.database.connection', null);
    }

    public function getTable()
    {
        return Config::get('promises.database.table_events', 'promise_events');
    }

    public function promise(): BelongsTo
    {
        return $this->belongsTo(Promise::class, 'promise_id', 'id');
    }

    public function scopeByJob(Builder $query, int $jobId): Builder
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeByEvent(Builder $query, string $eventName, string $eventUniqueId): Builder
    {
        return $query->where('event_name', $eventName)->where('event_unique_id', $eventUniqueId);
    }

    public function getWaitEvent(): WaitEvent
    {
        if ($this->baseEvent === null) {
            $this->baseEvent = new WaitEvent($this->event_name, $this->event_unique_id);
            $this->baseEvent->setId($this->id);
            $this->baseEvent->setBaseJobId($this->job_id);
        }

        return $this->baseEvent;
    }

    public static function saveWaitEvent(WaitEvent $waitEvent): void
    {
        $model = $waitEvent->getAttachedModel();

        if ($model === null) {
            $model = new self();
        }

        $model->job_id = $waitEvent->getBaseJobId();
        $model->event_name = $waitEvent->getEventName();
        $model->event_unique_id = $waitEvent->getEventUniqueId();

        $model->save();

        $waitEvent->setId($model->id);
        $waitEvent->setAttachedModel($model);
    }
}
