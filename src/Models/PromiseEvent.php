<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Tochka\Promises\Exceptions\UnknownBaseJobIdException;
use Tochka\Promises\Models\Casts\SerializableClassCast;
use Tochka\Promises\Models\Factories\PromiseEventFactory;
use Tochka\Promises\Support\WaitEvent;

/**
 * @api
 * @property int $id
 * @property int $job_id
 * @property string $event_name
 * @property string $event_unique_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property PromiseJob|null $job
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PromiseEvent extends Model
{
    use HasFactory;

    protected $casts = [
        'job_id' => 'int',
        'event_name' => 'string',
        'event_unique_id' => 'string',
        'event' => SerializableClassCast::class,
    ];

    private ?WaitEvent $baseEvent = null;

    public function getConnectionName(): ?string
    {
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        return Config::get('promises.database.connection', null);
    }

    public function getTable(): string
    {
        return Config::get('promises.database.table_events', 'promise_events');
    }

    /**
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(PromiseJob::class, 'promise_id', 'id');
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
        }

        $this->baseEvent->setId($this->id);
        $this->baseEvent->setBaseJobId($this->job_id);
        $this->baseEvent->setAttachedModel($this);

        return $this->baseEvent;
    }

    public static function saveWaitEvent(WaitEvent $waitEvent): self
    {
        $model = $waitEvent->getAttachedModel();
        if ($model === null) {
            $model = new self();
        }
        $baseJobId = $waitEvent->getBaseJobId();
        if ($baseJobId === null) {
            throw new UnknownBaseJobIdException();
        }

        $model->job_id = $baseJobId;
        $model->event_name = $waitEvent->getEventName();
        $model->event_unique_id = $waitEvent->getEventUniqueId();

        $model->save();

        $waitEvent->setId($model->id);
        $waitEvent->setAttachedModel($model);

        return $model;
    }

    protected static function newFactory(): PromiseEventFactory
    {
        return new PromiseEventFactory();
    }
}
