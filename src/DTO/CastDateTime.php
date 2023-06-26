<?php

namespace Tochka\Promises\DTO;

use Carbon\Carbon;

trait CastDateTime
{
    private function castToDateTime(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function castFromDateTime(?Carbon $value): ?string
    {
        return $value?->toDateTimeString();
    }
}
