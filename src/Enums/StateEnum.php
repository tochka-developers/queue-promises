<?php

namespace Tochka\Promises\Enums;

use BenSampo\Enum\Enum;

/**
 * @api
 *
 * @method static static WAITING()
 * @method static static RUNNING()
 * @method static static SUCCESS()
 * @method static static FAILED()
 * @method static static TIMEOUT()
 * @method static static CANCELED()
 * @method static static INCORRECT()
 *
 * @template-extends Enum<string>
 */
final class StateEnum extends Enum
{
    public const WAITING = 'waiting';
    public const RUNNING = 'running';
    public const SUCCESS = 'success';
    public const FAILED = 'failed';
    public const TIMEOUT = 'timeout';
    public const CANCELED = 'canceled';
    public const INCORRECT = 'incorrect';

    public static function successStates(): array
    {
        return [
            self::SUCCESS(),
        ];
    }

    public static function failedStates(): array
    {
        return [
            self::FAILED(),
            self::TIMEOUT(),
        ];
    }

    public static function finishedStates(): array
    {
        return [
            self::SUCCESS(),
            self::FAILED(),
            self::TIMEOUT(),
        ];
    }
}
