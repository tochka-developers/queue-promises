<?php

namespace Tochka\Promises\Contracts;

/**
 * @api
 */
interface PromiseHandler extends MayPromised
{
    public function run(): void;

    public function setPromiseId(int $promise_id): void;

    public function getPromiseId(): ?int;
}
