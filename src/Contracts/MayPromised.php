<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\BaseJob;

interface MayPromised
{
    public function setBaseJob(BaseJob $baseJob): void;
    public function getBaseJob(): BaseJob;
}
