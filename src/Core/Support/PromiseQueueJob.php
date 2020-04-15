<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tochka\Promises\Contracts\JobStateContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;
use Tochka\Promises\Support\PromisedJob;

/**
 * Задача, выполняющая обработку результата промиса
 */
class PromiseQueueJob implements ShouldQueue, MayPromised, JobStateContract
{
    use PromisedJob;

    /** @var int */
    private $promise_id;
    /** @var \Tochka\Promises\Contracts\PromiseHandler */
    private $promise_handler;
    /** @var StateEnum */
    private $state;

    public function __construct(int $promise_id, PromiseHandler $promise_handler, StateEnum $state)
    {
        $this->promise_id = $promise_id;
        $this->promise_handler = $promise_handler;
        $this->state = $state;
        if ($this->promise_handler instanceof MayPromised) {
            $this->base_job_id = $this->promise_handler->getBaseJobId();
        }
    }

    /**
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(): void
    {
        $this->promise_handler->setPromiseId($this->promise_id);

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

    public function getState(): StateEnum
    {
        return $this->state;
    }

    public function getPromiseId(): int
    {
        return $this->promise_id;
    }

    public function tags(): array
    {
        return [
            get_class($this->promise_handler) . ':' . $this->promise_id,
        ];
    }
}
