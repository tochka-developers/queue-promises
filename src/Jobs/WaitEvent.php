<?php

namespace Tochka\Queue\Promises\Jobs;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tochka\Queue\Promises\Contracts\MayPromised;
use Tochka\Queue\Promises\Contracts\NowDispatchingJob;
use Tochka\Queue\Promises\Contracts\PromisedEvent;

class WaitEvent implements MayPromised, NowDispatchingJob
{
    use Promised;

    protected $eventClassName;
    protected $eventUniqueId;
    protected $event;

    public function __construct(string $eventClassName, string $eventUniqueId = null)
    {
        $this->eventClassName = $eventClassName;
        $this->eventUniqueId = $eventUniqueId;
    }

    public function run()
    {
        $table = self::getDatabaseTable();
        $table->where('id', $this->getUniqueId())
            ->update(['promise_id' => $this->getPromiseId()]);
    }

    public function getUniqueId(): string
    {
        if (empty($this->unique_id)) {
            $table = self::getDatabaseTable();

            $this->unique_id = $table->insertGetId([
                'event_name' => $this->eventClassName,
                'event_id'   => $this->eventUniqueId,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]);
        }

        return $this->unique_id;
    }

    /**
     * @param PromisedEvent $event
     *
     * @return self[]
     */
    public static function resolve(PromisedEvent $event): array
    {
        $table = self::getDatabaseTable();

        $rows = $table->where('event_name', get_class($event))
            ->where('event_id', $event->getUniqueId())
            ->get();

        $instances = [];
        foreach ($rows as $row) {
            $instance = new self($row->event_name, $row->event_id);
            $instance->unique_id = $row->id;
            $instance->parent_promise_id = $row->promise_id;
            $instance->event = $event;

            $instances[] = $instance;
        }

        return $instances;
    }

    public static function flushAllForPromise($promise_id)
    {
        $table = self::getDatabaseTable();

        return $table->where('promise_id', $promise_id)
            ->delete();
    }

    public function flush()
    {
        $table = self::getDatabaseTable();

        return $table->where('id', $this->getUniqueId())
            ->delete();
    }

    /**
     * @return Builder
     */
    private static function getDatabaseTable(): Builder
    {
        $connection = config('promises.database.connection', null);
        if (empty($connection)) {
            $connection = DB::getDefaultConnection();
        }

        /** @var Connection $db */
        $db = DB::connection($connection);

        return $db->table(config('promises.database.events_table', 'promise_events'));
    }

    /**
     * Возвращает экземпляр произошедшего события
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }
}