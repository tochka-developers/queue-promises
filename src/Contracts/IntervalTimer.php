<?php

namespace Tochka\Queue\Promises\Contracts;

interface IntervalTimer extends MayPromised
{
    /**
     * Обработка джоба должна вернуть true, если все ок, и false, если что-то пошло не так,
     * в этом случае в промис зарядится ошибка и таймер будет остановлен.
     *
     * @return bool
     */
    public function handle(): bool;
}
