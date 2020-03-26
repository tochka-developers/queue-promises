<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Facades\PromiseRegistry;

class PromiseWatcher
{
    public function watch(): void
    {
        /** @var \Tochka\Promises\Core\BasePromise $promise */
        foreach (PromiseRegistry::loadAllCursor() as $promise) {
            echo $promise->getPromiseId() . PHP_EOL;
            sleep(5);
        }
    }
}