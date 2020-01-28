<?php

namespace Tochka\Promises\Contracts;

interface States
{
    public const WAITING = 'waiting';
    public const RUNNING = 'running';
    public const SUCCESS = 'success';
    public const FAILED = 'failed';
    public const TIMEOUT = 'timeout';
}
