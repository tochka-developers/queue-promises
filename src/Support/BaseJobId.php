<?php

namespace Tochka\Promises\Support;

trait BaseJobId
{
    protected ?int $base_job_id = null;

    public function setBaseJobId(int $base_job_id): void
    {
        $this->base_job_id = $base_job_id;
    }

    public function getBaseJobId(): ?int
    {
        return $this->base_job_id;
    }
}
