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
     * @param int $timeout Таймаут в секундах
     */
    public function __construct(int $timeout)
    {
        $this->expired_at = Carbon::now()->addSeconds($timeout);
    }

    public function condition(BasePromise $basePromise): bool
    {
        return Carbon::now() >= $this->expired_at;
    }
}
