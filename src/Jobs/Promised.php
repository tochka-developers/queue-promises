<?php

namespace Tochka\Queue\Promises\Jobs;

use Illuminate\Queue\Jobs\Job;

/**
 * Методы для реализации интерфейса MayPromised для использования Job вместе с Promise
 * @see MayPromised
 */
trait Promised
{
    protected $unique_id;
    protected $parent_promise_id;

    protected $promise_job_status;
    protected $promise_job_errors = [];

    /**
     * Получение уникального идентификатора отложенной задачи
     * @return string
     */
    public function getUniqueId(): string
    {
        if (empty($this->unique_id)) {
            $this->unique_id = str_random();
        }

        return $this->unique_id;
    }

    /**
     * Связывает отложенную задачу с промисом
     * @param Promise $promise
     */
    public function setPromise(Promise $promise)
    {
        $this->parent_promise_id = $promise->promise_id;
    }

    /**
     * Перехватывать или нет события завершения этой задачи
     * @return bool
     */
    public function hasResult(): bool
    {
        return true;
    }

    /**
     * Получает ID связанного промиса
     * @return int
     */
    public function getPromiseId()
    {
        return $this->parent_promise_id;
    }

    /**
     * Устанавливает статус задачи
     * @param $status
     */
    public function setJobStatus($status)
    {
        $this->promise_job_status = $status;
    }

    /**
     * Возвращает статус задачи
     * @return string
     */
    public function getJobStatus(): string
    {
        return $this->promise_job_status;
    }

    /**
     * Сохраняет ошибки из задачи
     * @param \Exception[]
     */
    public function setJobErrors(array $errors = [])
    {
        $this->promise_job_errors = $errors;
    }

    /**
     * Возвращает ошибки из задачи
     * @return \Exception[]
     */
    public function getJobErrors(): array
    {
        return $this->promise_job_errors;
    }

    /**
     * @param \Exception $e
     */
    public function failed(\Exception $e): void
    {
        $error = [
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ];

        $this->setJobErrors([$error]);
    }

    /**
     * Возвращает статус задачи
     * @return bool
     */
    public function hasFailed(): bool
    {
        if ($this->job instanceof Job) {
            return $this->job->hasFailed();
        }

        return false;
    }
}