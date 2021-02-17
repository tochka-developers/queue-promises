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
     * @param int $timeoutMinutes Таймаут в минутах
     * @param int $timeoutSeconds Таймаут в секундах (необязательный)
     */
    public function __construct(int $timeoutMinutes, int $timeoutSeconds = 0)
    {
        $this->expired_at = Carbon::now()->addMinutes($timeoutMinutes)->addSeconds($timeoutSeconds);
    }

    public function condition(BasePromise $basePromise): bool
    {
        return Carbon::now() >= $this->expired_at;
    }
}
