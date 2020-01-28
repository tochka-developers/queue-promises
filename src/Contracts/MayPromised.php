<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\BaseJob;

interface MayPromised
{
    public function setBaseJob(BaseJob $baseJob): void;
    public function getBaseJob(): BaseJob;
}
