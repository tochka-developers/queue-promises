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
     * @param int|Carbon $timeout Таймаут в минутах или время истечения
     */
    public function __construct($timeout)
    {
        if ($timeout instanceof Carbon) {
            $this->expired_at = $timeout;
        } elseif (is_int($timeout)) {
            $this->expired_at = Carbon::now()->addMinutes($timeout);
        } else {
            $this->expired_at = Carbon::parse($timeout);
        }
    }

    public function condition(BasePromise $basePromise): bool
    {
        return Carbon::now() >= $this->expired_at;
    }
}
