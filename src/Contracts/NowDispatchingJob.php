<?php

namespace Tochka\Queue\Promises\Contracts;

interface NowDispatchingJob
{
    public function run();
}