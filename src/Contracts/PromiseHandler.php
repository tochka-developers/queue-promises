<?php

namespace Tochka\Promises\Contracts;

interface PromiseHandler
{
    /**
     *
     */
    public function run(): void;

    /**
     * @param int $promise_id
     */
    public function setPromiseId(int $promise_id): void;

    /**
     * @return int
     */
    public function getPromiseId(): int;
}
