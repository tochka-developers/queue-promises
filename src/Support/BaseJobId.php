<?php

namespace Tochka\Promises\Support;

trait BaseJobId
{
    /** @var int|null */
    private $base_job_id;

    public function setBaseJobId(int $base_job_id): void
    {
        $this->base_job_id = $base_job_id;
    }

    public function getBaseJobId(): ?int
    {
        return $this->base_job_id;
    }
}