<?php

namespace Tochka\Promises\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static WAITING()
 * @method static static RUNNING()
 * @method static static SUCCESS()
 * @method static static FAILED()
 * @method static static TIMEOUT()
 * @method static static FINISHED()
 */
final class StateEnum extends Enum
{
    public const WAITING = 'waiting';
    public const RUNNING = 'running';
    public const SUCCESS = 'success';
    public const FAILED = 'failed';
    public const TIMEOUT = 'timeout';
    public const FINISHED = 'finished';
}
