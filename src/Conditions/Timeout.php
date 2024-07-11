<?php

namespace Tochka\Promises\Conditions;

use Carbon\Carbon;
use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;

/**
 * @api
 */
final class Timeout implements ConditionContract
{
    private Carbon $expired_at;

    /**
     * @param int|Carbon|\DateInterval|string $timeout Таймаут в минутах или время истечения
     */
    public function __construct($timeout)
    {
        if ($timeout instanceof Carbon) {
            $this->expired_at = $timeout;
        } elseif ($timeout instanceof \DateInterval) {
            $this->expired_at = Carbon::now()->add($timeout);
        } elseif (is_int($timeout)) {
            $this->expired_at = Carbon::now()->addMinutes($timeout);
        } else {
            $this->expired_at = Carbon::parse($timeout);
        }
    }

    public function getExpiredAt(): Carbon
    {
        return $this->expired_at;
    }

    public function condition(BasePromise $basePromise): bool
    {
        return Carbon::now() >= $this->expired_at;
    }
}
