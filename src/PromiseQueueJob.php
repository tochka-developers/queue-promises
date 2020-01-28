<?php

namespace Tochka\Promises;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;
use Illuminate\Contracts\Queue\ShouldQueue;

class PromiseQueueJob implements ShouldQueue
{
    /** @var \Tochka\Promises\Contracts\PromiseHandler */
    private $promiseHandler;
    /** @var string  */
    private $status;

    public function __construct(PromiseHandler $promiseHandler, string $status)
    {
        $this->promiseHandler = $promiseHandler;
        $this->status = $status;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     */
    public function handle()
    {
        $result = $this->dispatchMethodWithParams('before');

        if ($result) {
            $result = $this->dispatchMethodWithParams($this->status);
        }

        $this->dispatchMethodWithParams('after');

        return $result;
    }

    /**
     * @param string $method
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function dispatchMethodWithParams($method): bool
    {
        if (!method_exists($this->promiseHandler, $method)) {
            return true;
        }

        $params = [];
        // подготавливаем аргументы для вызова метода
        $reflectionMethod = new \ReflectionMethod($this->promiseHandler, $method);

        $allResults = $this->getResults();

        foreach ($allResults as $result) {
            $results[\get_class($result)][] = $result;
        }

        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $param = null;

            $type = (string) $parameter->getType();

            if (\in_array(MayPromised::class, class_implements($type), true) ||
                \in_array(PromisedEvent::class, class_implements($type), true)) {
                if (!empty($results[$type])) {
                    $param = array_shift($results[$type]);
                } else {
                    $param = null;
                }
            } else {
                $param = app($type);
            }

            $params[$i] = $param;
        }

        return $this->$method(...$params);
    }
}
