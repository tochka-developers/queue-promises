<?php

namespace Tochka\Promises\Core\Support;

use Carbon\Carbon;

trait Time
{
    private Carbon $created_at;
    private Carbon $updated_at;

    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updated_at;
    }

    public function setCreatedAt(Carbon $value): void
    {
        $this->created_at = $value;
    }

    public function setUpdatedAt(Carbon $value): void
    {
        $this->updated_at = $value;
    }
}
