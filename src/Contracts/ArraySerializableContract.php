<?php

namespace Tochka\Promises\Contracts;

interface ArraySerializableContract
{
    public function getAsArray(): array;

    public static function restoreFromArray(array $values): self;
}
