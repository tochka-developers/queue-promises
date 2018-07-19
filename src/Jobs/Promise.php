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
use ReflectionClass;
use ReflectionProperty;
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
    protected $promise_jobs = [];

    /** @var MayPromised[] */
    protected $promise_results = [];

    protected $promise_type = self::PROMISE_TYPE_ASYNC;
    protected $promise_status = self::STATUS_SUCCESS;
    protected $promise_queue = null;

    /**
     * Добавляет задачу в очередь
     *
     * @param MayPromised $job
     *
     * @return $this
     */
    public function add(MayPromised $job): self
    {
        $this->promise_jobs[$job->getUniqueId()] = $job;

        if ($this->promise_id === null) {
            $this->save();
        }

        $job->setPromise($this);

        return $this;
    }

    /**
     * Запускает очередь задач
     */
    public function run()
    {
        if ($this->promise_type === self::PROMISE_TYPE_ASYNC) {
            foreach ($this->promise_jobs as $job) {
                $this->dispatchJob($job);
            }
        } else {
            $this->dispatchJob(reset($this->promise_jobs));
        }

        $this->save();
    }

    /**
     * Диспатчит указанную джобу
     *
     * @param ShouldQueue $job
     */
    protected function dispatchJob($job)
    {
        // если задана очередь по умолчанию для всех - устанавливаем эту очередь
        if (null !== $this->promise_queue) {
            $job->onQueue($this->promise_queue);
        }

        if ($job instanceof self) {
            // если задана очередь по умолчанию для всех - устанавливаем эту очередь
            if (null !== $this->promise_queue) {
                $job->setQueueForAll($this->promise_queue);
            }

            $job->run();
        } else {
            dispatch($job);
        }
    }

    /**
     * Запускает очередь задач
     * Все задачи запускаются одновременно, промис выполнится, как только все задачи завершатся
     */
    public function runAsync()
    {
        $this->setPromiseType(self::PROMISE_TYPE_ASYNC);
        $this->run();
    }

    /**
     * Запускает очередь задач
     * Задачи запускаются по очереди, промис выполнится, как только все задачи завершатся, либо когда одна из задач
     * завершится с ошибкой
     */
    public function runSync()
    {
        $this->setPromiseType(self::PROMISE_TYPE_SYNC);
        $this->run();
    }

    /**
     * Устанавливает тип запуска задач
     *
     * @param $type
     */
    public function setPromiseType($type)
    {
        $this->promise_type = $type;
    }

    public function setQueueForAll($queue)
    {
        $this->promise_queue = $queue;
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
     *
     * @throws \ReflectionException
     */
    public static function checkPromise(MayPromised $job)
    {
        if ($job->getPromiseId() === null) {
            return;
        }

        DB::beginTransaction();

        // получаем сам Promise
        $promise = self::resolve($job->getPromiseId());

        if (!$promise) {
            return;
        }

        // если такого запроса нет - игнорируем
        if (!isset($promise->promise_jobs[$job->getUniqueId()])) {
            return;
        }

        // убираем из списка запросов и запоминаем ответ
        unset($promise->promise_jobs[$job->getUniqueId()]);

        $promise->promise_results[$job->getUniqueId()] = $promise->getJobResults($job);

        // если ответ с ошибкой - статус Promise меняем на ошибку
        if ($job->getJobStatus() === MayPromised::JOB_STATUS_ERROR) {
            $promise->promise_status = self::STATUS_ERROR;
        }

        // если закончились запросы, либо если у нас вызов цепочкой и в одном из запросов произошла ошибка
        if (empty($promise->promise_jobs) || ($promise->promise_type === self::PROMISE_TYPE_SYNC && $promise->promise_status === self::STATUS_ERROR)) {
            $promise->deleteRaw();
            DB::commit();

            if (null !== $promise->promise_queue) {
                $promise->onQueue($promise->promise_queue);
            }

            // вызываем Promise
            dispatch($promise);
        } else {
            $promise->save();
            DB::commit();

            // если вызываем запросы цепочкой - отправим следующий запрос
            if ($promise->promise_type === self::PROMISE_TYPE_SYNC) {
                $next_job = reset($promise->promise_jobs);

                if ($promise->runNextJob($next_job)) {
                    $promise->dispatchJob($next_job);
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
     * @return self|null
     */
    public static function resolve(int $promise_id)
    {
        $table = self::getDatabaseTable();

        $row = $table->where('id', $promise_id)
            ->lockForUpdate()
            ->first();
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
        $result = $this->dispatchMethodWithParams('before');

        if ($result) {
            if ($this->promise_status === self::STATUS_SUCCESS) {
                $result = $this->dispatchMethodWithParams('success');
            } else {
                $result = $this->dispatchMethodWithParams('errors');
            }
        }

        $this->dispatchMethodWithParams('after');

        return $result;
    }

    /**
     * @param string $method
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function dispatchMethodWithParams($method): bool
    {
        if (!method_exists($this, $method)) {
            return true;
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
        return $this->promise_results;
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
        if ($this->promise_status === self::STATUS_SUCCESS) {
            return MayPromised::JOB_STATUS_SUCCESS;
        }

        return MayPromised::JOB_STATUS_ERROR;
    }

    /**
     * @param $job
     *
     * @return mixed
     * @throws \ReflectionException
     */
    protected function getJobResults($job)
    {
        $properties = (new ReflectionClass($job))->getProperties();

        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            if (\in_array($property->getName(), $this->getNullableJobProperties())) {
                $property->setAccessible(true);
                $property->setValue($job, null);
            }
        }

        return $job;
    }

    protected function getNullableJobProperties()
    {
        return ['job'];
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function __sleep()
    {
        $properties = (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

        foreach ($properties as $property) {
            $property->setValue($this, $this->getSerializedPropertyValue(
                $this->getPropertyValue($property)
            ));
        }

        return array_values(array_filter(array_map(function ($p) {
            /** @var ReflectionProperty $p */
            return $p->isStatic() ? null : $p->getName();
        }, $properties)));
    }
}