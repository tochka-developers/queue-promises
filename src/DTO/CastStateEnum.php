<?php

namespace Tochka\Promises\DTO;

use Tochka\Promises\Enums\StateEnum;

trait CastStateEnum
{
    private function castToStateEnum(?string $value): ?StateEnum
    {
        if ($value === null) {
            return null;
        }

        return StateEnum::coerce($value);
    }

    private function castFromStateEnum(?StateEnum $value): ?string
    {
        return $value?->value;
    }
}
