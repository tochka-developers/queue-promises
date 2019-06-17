<?php

namespace Tochka\Queue\Promises\Jobs;

use Tochka\Queue\Promises\Contracts\MayPromised;

/**
 * Все, что касается управления условиями завершения промиса
 *
 * @package Tochka\Queue\Promises\Jobs
 */
trait PromiseFinishConditions
{
    protected $promise_finish_on_first_error = null;
    protected $promise_finish_on_first_success = null;

    /**
     * Убедиться, что условия завершения промиса выставлены корректно для выбранного режима запуска
     */
    final protected function ensureFinishConditionsConfigured(): void
    {
        if ($this->promise_finish_on_first_error !== null) {
            return;
        }

        switch ($this->promise_type) {
            case self::PROMISE_TYPE_SYNC:
                $this->setPromiseFinishConditions(false, true);

                return;
            case self::PROMISE_TYPE_ASYNC:
            default:
                $this->setPromiseFinishConditions(false, false);

                return;
        }
    }

    /**
     * Установить условия выполнения промиса
     *
     * @param bool $onFirstSuccess Останавливаться при первом же успехе
     * @param bool $onFirstError   Останавливаться при первой же ошибке
     */
    public function setPromiseFinishConditions(bool $onFirstSuccess = false, bool $onFirstError = false): void
    {
        $this->promise_finish_on_first_success = $onFirstSuccess;
        $this->promise_finish_on_first_error = $onFirstError;
    }

    /**
     * Получить условия выполнения промиса
     *
     * @return bool[]
     */
    public function getPromiseFinishConditions(): array
    {
        return [$this->promise_finish_on_first_success, $this->promise_finish_on_first_error];
    }

    /**
     * Следует ли промису завершиться при завершении джобы
     *
     * @param MayPromised $job
     *
     * @return bool
     */
    protected function shouldFinish(MayPromised $job): bool
    {
        // Если других джобов не осталось - завершаемся в любом случае
        if (empty($this->promise_jobs)) {
            return true;
        }

        $jobStatus = $job->getJobStatus();

        // Если задача завершилась успешно, то проверим, надо ли завершиться самому промису.
        if ($jobStatus === MayPromised::JOB_STATUS_SUCCESS) {
            return $this->promise_finish_on_first_success;
        }

        // Если мы здесь, то джоб либо завершен с ошибкой, либо в неизвестном статусе
        // Неизвестный статус приравниваем к ошибке
        return $this->promise_finish_on_first_error;
    }
}
