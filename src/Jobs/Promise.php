<?php

namespace Tochka\Queue\Promises\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Tochka\Queue\Promises\Contracts\MayPromised;

abstract class Promise implements ShouldQueue, MayPromised
{
    use InteractsWithQueue, Queueable, SerializesModels, Promised;

    const PROMISE_TYPE_ASYNC = 0;
    const PROMISE_TYPE_SYNC = 1;

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    public $promise_id;

    /** @var int Максимальное количество попыток выполнения задания */
    public $tries = 3;

    /** @var MayPromised[] */
    protected $jobs = [];

    /** @var MayPromised[] */
    protected $results = [];

    protected $type = self::PROMISE_TYPE_ASYNC;
    protected $status = self::STATUS_SUCCESS;

    /**
     * Добавляет задачу в очередь
     *
     * @param MayPromised $job
     *
     * @return $this
     */
    public function add(MayPromised $job): self
    {
        $this->jobs[$job->getUniqueId()] = $job;

        if ($this->promise_id === null) {
            $this->save();
        }

        $job->setPromise($this);

        return $this;
    }

    /**
     * Запускает очередь задач
     *
     * @param int $type Как будут выполняться задачи
     * PROMISE_TYPE_ASYNC - все задачи запускаются одновременно, промис выполнится, как только все задачи завершатся
     * PROMISE_TYPE_SYNC - задачи запускаются по очереди, промис выполнится, как только все задачи завершатся,
     * либо когда одна из задач завершится с ошибкой
     */
    public function run($type = self::PROMISE_TYPE_ASYNC)
    {
        $this->type = $type;

        if ($type === self::PROMISE_TYPE_ASYNC) {
            foreach ($this->jobs as $job) {
                if ($job instanceof self) {
                    $job->run();
                } else {
                    dispatch($job);
                }
            }
        } else {
            dispatch(reset($jobs));
        }

        $this->save();
    }

    /**
     * Запускает очередь задач
     * Все задачи запускаются одновременно, промис выполнится, как только все задачи завершатся
     */
    public function runAsync()
    {
        $this->run(self::PROMISE_TYPE_ASYNC);
    }

    /**
     * Запускает очередь задач
     * Задачи запускаются по очереди, промис выполнится, как только все задачи завершатся, либо когда одна из задач
     * завершится с ошибкой
     */
    public function runSync()
    {
        $this->run(self::PROMISE_TYPE_SYNC);
    }

    /**
     * Сохранение информации о Promise
     */
    protected function save()
    {
        $table = self::getDatabaseTable();

        $payload = [
            'commandName' => \get_class($this),
            'command'     => serialize(clone $this),
        ];

        if ($this->promise_id === null) {
            $this->promise_id = $table->insertGetId([
                'payload'    => json_encode($payload),
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]);
        } else {
            $table->where('id', $this->promise_id)
                ->update([
                    'payload'    => json_encode($payload),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]);
        }
    }

    /**
     * Удаляет информацию о Promise
     */
    protected function deleteRaw()
    {
        $table = self::getDatabaseTable();

        $table->where('id', $this->promise_id)->delete();
    }

    /**
     * Проверяет, пришла ли пора вызвать Promise
     *
     * @param MayPromised $job
     */
    public static function checkPromise(MayPromised $job)
    {
        if ($job->getPromiseId() === null) {
            return;
        }

        // получаем сам Promise
        $promise = self::resolve($job->getPromiseId());

        if (!$promise) {
            return;
        }

        // если такого запроса нет - игнорируем
        if (!isset($promise->jobs[$job->getUniqueId()])) {
            return;
        }

        // убираем из списка запросов и запоминаем ответ
        unset($promise->jobs[$job->getUniqueId()]);

        $promise->results[$job->getUniqueId()] = $job;

        // если ответ с ошибкой - статус Promise меняем на ошибку
        if ($job->getJobStatus() === MayPromised::JOB_STATUS_ERROR) {
            $promise->status = self::STATUS_ERROR;
        }

        // если закончились запросы, либо если у нас вызов цепочкой и в одном из запросов произошла ошибка
        if (empty($promise->jobs) || ($promise->type === self::PROMISE_TYPE_SYNC && $promise->status === self::STATUS_ERROR)) {
            $promise->deleteRaw();

            // вызываем Promise
            dispatch($promise);
        } else {
            $promise->save();

            // если вызываем запросы цепочкой - отправим следующий запрос
            if ($promise->type === self::PROMISE_TYPE_SYNC) {
                $next_job = reset($promise->jobs);

                if ($promise->runNextJob($next_job)) {
                    dispatch($next_job);
                }
            }
        }
    }

    /**
     * Определяет
     *
     * @param MayPromised $job
     *
     * @return bool
     */
    public function runNextJob($job): bool
    {
        return true;
    }

    /**
     * Восстанавливает Promise из БД
     *
     * @param int $promise_id
     *
     * @return self
     */
    public static function resolve(int $promise_id): self
    {
        $table = self::getDatabaseTable();

        $row = $table->where('id', $promise_id)->first();
        if (!$row) {
            return null;
        }

        $data = json_decode($row->payload, true);

        return unserialize($data['command']);
    }

    /**
     * @return bool
     * @throws \ReflectionException
     */
    public function handle(): bool
    {
        if ($this->status === self::STATUS_SUCCESS) {
            return $this->dispatchMethodWithParams('success');
        }

        return $this->dispatchMethodWithParams('errors');
    }

    /**
     * @param $method
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function dispatchMethodWithParams($method): bool
    {
        if (!method_exists($this, $method)) {
            if (!method_exists($this, 'done')) {
                return true;
            }
            $method = 'done';
        }

        $params = [];
        // подготавливаем аргументы для вызова метода
        $reflectionMethod = new \ReflectionMethod($this, $method);

        $allResults = $this->getResults();

        foreach ($allResults as $result) {
            $results[\get_class($result)][] = $result;
        }

        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $param = null;

            $type = (string)$parameter->getType();

            if (\in_array(MayPromised::class, class_implements($type), true)) {
                if (!empty($results[$type])) {
                    $param = array_shift($results[$type]);
                } else {
                    $param = null;
                }
            } else {
                $param = app($type);
            }

            $params[$i] = $param;
        }

        return $this->$method(...$params);
    }

    /**
     * Возвращает результаты работы задач
     * @return MayPromised[]
     */
    public function getResults(): array
    {
        return $this->results;
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

        return $db->table(config('promises.database.table', 'promises'));
    }

    public function getJobStatus(): string
    {
        if ($this->status === self::STATUS_SUCCESS) {
            return MayPromised::JOB_STATUS_SUCCESS;
        }

        return MayPromised::JOB_STATUS_ERROR;
    }
}