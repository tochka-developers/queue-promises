<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

/**
 * Задача, выполняющая обработку результата промиса
 */
class PromiseQueueJob implements ShouldQueue
{
    /** @var int */
    private $promise_id;
    /** @var \Tochka\Promises\Contracts\PromiseHandler */
    private $promise_handler;
    /** @var StateEnum */
    private $state;

    public function __construct(int $promise_id, PromiseHandler $promiseHandler, StateEnum $state)
    {
        $this->promise_id = $promise_id;
        $this->promise_handler = $promiseHandler;
        $this->state = $state;
    }

    /**
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(): void
    {
        $result = $this->dispatchMethodWithParams('before');

        if ($result !== false) {
            switch ($this->state->value) {
                case StateEnum::SUCCESS:
                    $this->dispatchMethodWithParams('success');
                    break;
                case StateEnum::FAILED:
                    $this->dispatchMethodWithParams('failed');
                    break;
                case StateEnum::TIMEOUT:
                    $this->dispatchMethodWithParams('timeout');
                    break;
            }

            $this->dispatchMethodWithParams('handle');
        }

        $this->dispatchMethodWithParams('after');
    }

    /**
     * @param string $method
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function dispatchMethodWithParams(string $method)
    {
        if (!method_exists($this->promise_handler, $method)) {
            return true;
        }

        $params = [];
        // подготавливаем аргументы для вызова метода
        $reflectionMethod = new \ReflectionMethod($this->promise_handler, $method);

        $jobs = PromiseJobRegistry::loadByPromiseId($this->promise_id);

        foreach ($jobs as $job) {
            $resultJob = $job->getResultJob();
            $results[\get_class($resultJob)][] = $resultJob;
        }

        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $param = null;

            $type = (string) $parameter->getType();

            if (\in_array(MayPromised::class, class_implements($type), true)) {
                if (!empty($results[$type])) {
                    $param = array_shift($results[$type]);
                } else {
                    $param = null;
                }
            } else {
                $param = Container::getInstance()->make($param);
            }

            $params[$i] = $param;
        }

        return $this->promise_handler->$method(...$params);
    }
}
