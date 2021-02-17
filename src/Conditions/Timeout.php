<?php

namespace Tochka\Promises\Conditions;

use Carbon\Carbon;
use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;

final class Timeout implements ConditionContract
{
    /** @var \Carbon\Carbon */
    private $expired_at;

    /**
     * Timeout constructor.
     *
     * @param int|string $timeout Таймаут в минутах (int), Таймаут в любых единицах (string, в формате 1h2m3s)
     */
    public function __construct($timeout)
    {
        if (\is_int($timeout)) {
            $this->expired_at = Carbon::now()->addMinutes($timeout);

            return;
        }

        $expiredAt = Carbon::now();
        if (\preg_match('/\d+d/', $timeout,$matches)) {
            $expiredAt->addDays((int) $matches[0]);
        }
        if (\preg_match('/\d+h/', $timeout,$matches)) {
            $expiredAt->addHours((int) $matches[0]);
        }
        if (\preg_match('/\d+m/', $timeout,$matches)) {
            $expiredAt->addMinutes((int) $matches[0]);
        }
        if (\preg_match('/\d+s/', $timeout, $matches)) {
            $expiredAt->addSeconds((int) $matches[0]);
        }
        $this->expired_at = $expiredAt;
    }

    public function condition(BasePromise $basePromise): bool
    {
        return Carbon::now() >= $this->expired_at;
    }
}
