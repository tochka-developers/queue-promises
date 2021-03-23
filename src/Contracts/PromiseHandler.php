<?php

namespace Tochka\Promises\Contracts;

/**
 * @codeCoverageIgnore
 */
interface PromiseHandler extends MayPromised
{
    public function run(): void;

    public function setPromiseId(int $promise_id): void;

    public function getPromiseId(): ?int;
}
