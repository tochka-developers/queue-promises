<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Facades\PromiseRegistry;

class PromiseWatcher
{
    private $iteration_time = 5;

    public function watch(): void
    {
        while (true) {
            $time = microtime(true);

            /** @var \Tochka\Promises\Core\BasePromise $promise */
            foreach (PromiseRegistry::loadAllCursor() as $promise) {
                echo $promise->getPromiseId() . PHP_EOL;
            }

            $sleep_time = floor($this->iteration_time - (microtime(true) - $time));

            if ($sleep_time < 1) {
                $sleep_time = 1;
            }

            sleep($sleep_time);
        }
    }

    private function getCurrentConditions()
    {

    }
}