<?php

namespace Tochka\Queue\Promises\Contracts;

interface PromisedEvent
{
    /**
     * Получение уникального идентификатора события
     * @return string|null
     */
    public function getUniqueId();
}