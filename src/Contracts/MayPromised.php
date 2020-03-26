<?php

namespace Tochka\Promises\Contracts;

interface MayPromised
{
    /**
     * @param int $base_job_id
     */
    public function setBaseJobId(int $base_job_id): void;

    /**
     * @return int
     */
    public function getBaseJobId(): int;
}
