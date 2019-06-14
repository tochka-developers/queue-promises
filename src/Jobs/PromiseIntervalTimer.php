<?php

namespace Tochka\Queue\Promises\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tochka\Queue\Promises\Contracts\MayPromised;
use Tochka\Queue\Promises\Contracts\NowDispatchingJob;

/**
 * Интервальный таймер для промиса. Добавляется в промис как обычный джоб, например так:
 * $promise->add(new PromiseIntervalTimer(60, MyTimerJob::class, true);
 *
 * @package Tochka\Queue\Promises\Jobs
 */
class PromiseIntervalTimer implements ShouldQueue, MayPromised, NowDispatchingJob
{
    use InteractsWithQueue, Queueable, SerializesModels, Promised;

    public $tries = PHP_INT_MAX;

    public $invocationInterval;
    public $timerJobClassName;
    public $timerJobParams;

    protected $promise = null;

    /**
     * @param int    $invocationInterval Интервал запуска таймера в секундах
     * @param string $timerJobClassName Имя класса джоба, который будет запускаться по заданному интервалу
     *                                  Этот класс должен принимать Promise первым параметром конструктора
     * @param mixed  ...$timerJobParams Прочие параметры конструктора джоба (фиксированные на момент запуска таймера)
     */
    public function __construct(int $invocationInterval, string $timerJobClassName, ...$timerJobParams)
    {
        $this->invocationInterval = $invocationInterval;
        $this->timerJobClassName = $timerJobClassName;
        $this->timerJobParams = $timerJobParams;
    }

    /**
     * Первый запуск таймера. Собственно код таймера не выполняется, просто запланируем следующий запуск
     */
    public function run()
    {
        if ($this->shouldEnqueue()) {
            dispatch($this)->delay($this->invocationInterval);
        }
    }

    /**
     * Поставить в очередь следующий запуск таймера, если это нужно и имеет смысл
     * @return bool
     */
    final protected function shouldEnqueue(): bool
    {
        // Не ставим, если интервал не задан
        if (!$this->invocationInterval) {
            return false;
        }

        // Не ставим, если промис уже завершен
        if (!$promise = $this->getPromise()) {
            return false;
        }

        $expiryTime = $promise->getExpiredAt();

        // Не ставим, если мы можем определить, что таймаут промиса случится раньше
        if (
            $expiryTime instanceof Carbon &&
            $expiryTime->toDateTimeString() <= Carbon::now()->addSeconds($this->invocationInterval)->toDateTimeString()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Получить экземпляр промиса, связанного с этим таймером
     *
     * @return Promise|null
     */
    final protected function getPromise(): ?Promise
    {
        return $this->promise ?? $this->promise = Promise::resolve($this->getPromiseId());
    }

    /**
     * Обработать очередной запуск таймера
     *
     * @return bool
     */
    public function handle(): bool
    {
        // Ничего не делаем, если промис уже завершился
        if (!$promise = $this->getPromise()) {
            return true;
        }

        // Создадим экземпляр джоба и тут же его обработаем.
        // Если джоб вернул не true, то не будем планировать следующий запуск
        if (!(new $this->timerJobClassName($promise, ...$this->timerJobParams))->handle()) {
            return true;
        }

        // Если все еще необходимо, отложимся до следующего интервала
        if ($this->shouldEnqueue()) {
            $this->release($this->invocationInterval);
        }

        return true;
    }
}
