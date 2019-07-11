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
use Tochka\Queue\Promises\Contracts\NowDispatchingJob;
use Tochka\Queue\Promises\Contracts\PromisedEvent;
use Tochka\Queue\Promises\Exceptions\PromiseNotFoundException;

abstract class Promise implements ShouldQueue, MayPromised, NowDispatchingJob
{
    use InteractsWithQueue, Queueable, SerializesModels, Promised, PromiseFinishConditions;

    public const PROMISE_TYPE_ASYNC = 0;
    public const PROMISE_TYPE_SYNC = 1;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_TIMEOUT = 'timeout';

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
    protected $promise_expired_at = null;

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
        // если в очереди запросов нет ничего - сразу запускаем Promise
        if (empty($this->promise_jobs)) {
            if (null !== $this->promise_queue) {
                $this->onQueue($this->promise_queue);
            }

            // вызываем Promise
            dispatch($this);

            return;
        }

        if ($this->promise_expired_at !== null) {
            dispatch(new PromiseTimeout($this))
                ->delay($this->promise_expired_at);
        }

        $this->ensureFinishConditionsConfigured();

        if ($this->promise_type === self::PROMISE_TYPE_SYNC) {
            $this->save();
            $this->dispatchJob(reset($this->promise_jobs));

            return;
        }

        foreach ($this->promise_jobs as $job) {
            $this->dispatchJob($job);
        }

        $this->save();
    }

    /**
     * Диспатчит указанную джобу
     *
     * @param MayPromised $job
     */
    protected function dispatchJob($job)
    {
        $this->setQueue($job);

        if ($job instanceof NowDispatchingJob) {
            $job->run();

            return;
        }

        dispatch($job);
    }

    /**
     * Задать очереди для исполнения джобов (при необходимости)
     *
     * @param MayPromised $job
     */
    final protected function setQueue($job): void
    {
        if (!$this->promise_queue) {
            return;
        }

        if ($job instanceof ShouldQueue) {
            $job->onQueue($this->promise_queue);
        }

        // если задана очередь по умолчанию для всех - устанавливаем эту очередь
        if ($job instanceof self) {
            $job->setQueueForAll($this->promise_queue);
        }
    }

    /**
     * Запускает очередь задач
     * Все задачи запускаются одновременно, промис выполнится, как только все задачи завершатся
     *
     * @param bool $finishOnFirstSuccess следует ли остановиться при первой же завершенной задаче
     * @param bool $finishOnFirstError   следует ли остановиться при первой же ошибке
     */
    public function runAsync(bool $finishOnFirstSuccess = false, bool $finishOnFirstError = false)
    {
        $this->setPromiseType(self::PROMISE_TYPE_ASYNC);
        $this->setPromiseFinishConditions($finishOnFirstSuccess, $finishOnFirstError);
        $this->run();
    }

    /**
     * Запускает очередь задач
     * Задачи запускаются по очереди, промис выполнится, как только все задачи завершатся, либо когда одна из задач
     * завершится с ошибкой
     *
     * @param bool $finishOnFirstSuccess следует ли остановиться при первой же завершенной задаче
     * @param bool $finishOnFirstError   следует ли остановиться при первой же ошибке
     */
    public function runSync(bool $finishOnFirstSuccess = false, bool $finishOnFirstError = true)
    {
        $this->setPromiseType(self::PROMISE_TYPE_SYNC);
        $this->setPromiseFinishConditions($finishOnFirstSuccess, $finishOnFirstError);

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

    /**
     * Устанавливает статус промиса
     *
     * @param $status
     */
    public function setPromiseStatus($status)
    {
        $this->promise_status = $status;
    }

    /**
     * Устанавливает выполнение всех связанных задач в определенной очереди
     *
     * @param $queue
     */
    public function setQueueForAll($queue)
    {
        $this->promise_queue = $queue;
    }

    /**
     * Устанавливает максимальное время ожидания выполнения зависимых задач
     *
     * @param $timeout
     */
    public function setTimeout($timeout)
    {
        $this->promise_expired_at = Carbon::now()->addSeconds($timeout);
    }

    /**
     * Устанавливает время истемчения срока ожилдания выполнения зависимых задач
     *
     * @param $expired_at
     */
    public function setExpiredAt($expired_at)
    {
        $this->promise_expired_at = $expired_at;
    }

    /**
     * Получить момент времени, когда промис завершится
     *
     * @return mixed
     */
    public function getExpiredAt()
    {
        return $this->promise_expired_at;
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
     * Выполнить действия, связанные с завершением джобы (установить статусы, очистить очереди и т.д.)
     *
     * @param MayPromised $job
     *
     * @throws \ReflectionException
     * @throws PromiseNotFoundException
     */
    protected function finalizeJob(MayPromised $job): void
    {
        // если такого запроса нет - игнорируем
        if (!isset($this->promise_jobs[$job->getUniqueId()])) {
            throw new PromiseNotFoundException('Job #' . $job->getUniqueId() . ' in promise ' . $job->getPromiseId() . ' not found');
        }

        $this->setJobResults($job);
    }

    /**
     * Записать результат выполнения джоба в промис.
     * Выставить статус самого промиса, если надо.
     *
     * @param MayPromised $job
     *
     * @throws \ReflectionException
     */
    final public function setJobResults(MayPromised $job): void
    {
        // убираем из списка запросов и запоминаем ответ
        unset($this->promise_jobs[$job->getUniqueId()]);

        $this->promise_results[$job->getUniqueId()] = $this->getJobResults($job);

        // если ответ с ошибкой - статус Promise меняем на ошибку
        if ($job->getJobStatus() === MayPromised::JOB_STATUS_ERROR) {
            $this->promise_status = self::STATUS_ERROR;
        }
    }

    /**
     * Запустить сам промис (в нужную очередь)
     */
    protected function doDispatch(): void
    {
        if ($this->promise_queue !== null) {
            $this->onQueue($this->promise_queue);
        }

        // вызываем Promise
        dispatch($this);
    }

    /**
     * Запустить следующую задачу из цепочки, если надо
     */
    protected function doNextJob(): void
    {
        // если вызываем запросы цепочкой - отправим следующий запрос
        if ($this->promise_type !== self::PROMISE_TYPE_SYNC) {
            return;
        }

        $nextJob = reset($this->promise_jobs);

        if ($nextJob && $this->runNextJob($nextJob)) {
            $this->dispatchJob($nextJob);
        }
    }

    /**
     * Обернуть какое-то действие в транзакцию
     *
     * @param callable    $callable
     * @param int|null    $promiseId
     * @param MayPromised $job
     *
     * @throws \Exception
     */
    protected static function transaction(callable $callable, ?int $promiseId = null, ?MayPromised $job = null)
    {
        if (!$promiseId) {
            return;
        }

        DB::beginTransaction();

        $level = DB::transactionLevel();

        try {
            // получаем сам Promise
            $promise = self::resolve($promiseId);

            if (!$promise) {
                throw new PromiseNotFoundException('Promise #' . $promiseId . ' not found');
            }

            // Коммит должен произойти внутри этой функции!
            $callable($promise, $job);

            // Но если нет, то закоммитимся тут
            if (DB::transactionLevel() === $level) {
                DB::commit();
            }

        } catch (PromiseNotFoundException $e) {
            DB::rollBack();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * Проверяет, пришла ли пора вызвать Promise
     *
     * @param MayPromised $job
     *
     * @throws \Exception
     */
    public static function checkPromise(MayPromised $job)
    {
        self::transaction(function (self $promise, MayPromised $job) {
            $promise->finalizeJob($job);

            // промис будет выполнен сам, если:
            // - либо закончились запросы
            // - либо условия заставляют нас прекратить исполнение
            if ($promise->shouldFinish($job)) {
                $promise->deleteRaw();
                DB::commit();
                $promise->doDispatch();

            } else {
                $promise->save();
                DB::commit();
                $promise->doNextJob();
            }
        }, $job->getPromiseId(), $job);
    }

    /**
     * Проверяет, что промис еще не был выполнен - и выполняет его со статусом вылета по таймауту
     *
     * @param int $promise_id
     *
     * @throws \Exception
     */
    public static function promiseTimeout(int $promise_id)
    {
        self::transaction(function (self $promise) {
            $promise->setPromiseStatus(Promise::STATUS_TIMEOUT);
            $promise->deleteRaw();
            DB::commit();
            $promise->doDispatch();
        }, $promise_id);
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
    public static function resolve(int $promise_id): ?self
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
            switch ($this->promise_status) {
                case self::STATUS_SUCCESS:
                    $result = $this->dispatchMethodWithParams('success');
                    break;
                case self::STATUS_ERROR:
                    $result = $this->dispatchMethodWithParams('errors');
                    break;
                case self::STATUS_TIMEOUT:
                    $result = $this->dispatchMethodWithParams('timeout');
                    break;
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

            $type = (string) $parameter->getType();

            if (\in_array(MayPromised::class, class_implements($type), true) ||
                \in_array(PromisedEvent::class, class_implements($type), true)) {
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
     *
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
        if ($job instanceof WaitEvent) {
            return $job->getEvent();
        }

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