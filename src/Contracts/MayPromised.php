<?php

namespace Tochka\Promises\Contracts;

interface MayPromised
{
    public function setBaseJobId(int $base_job_id): void;

    public function getBaseJobId(): ?int;
}
