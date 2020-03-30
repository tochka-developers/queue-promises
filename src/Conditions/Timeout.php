<?php

namespace Tochka\Promises\Conditions;

use Carbon\Carbon;
use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;

class Timeout implements ConditionContract
{
    /** @var \Carbon\Carbon */
    private $expired_at;

    /**
     * Timeout constructor.
     *
     * @param int $timeout Таймаут в минутах
     */
    public function __construct(int $timeout)
    {
        $this->expired_at = Carbon::now()->addMinutes($timeout);
    }

    public function condition(BasePromise $basePromise): bool
    {
        return Carbon::now() >= $this->expired_at;
    }
}
